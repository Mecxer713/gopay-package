import {
  GoPayConfig,
  GoPayHttpMethod,
  PaymentResponse,
  PayoutBalanceResponse,
  PayoutTransferResponse,
  GoPayCurrency,
} from './models.js';
import { GoPayError, GoPayConfigurationError } from './errors.js';
import { buildQueryString, computeSignature, generateNonce } from './hmac.js';

const DEFAULT_BASE_URL = 'https://gopay.gooomart.com';

/** Codes HTTP considérés comme transitoires (retry autorisé sur requêtes idempotentes). */
const RETRYABLE_STATUS = new Set([408, 429, 500, 502, 503, 504]);

export class GoPayClient {
  private readonly baseUrl: string;
  private readonly paymentApiKey: string;
  private readonly paymentSecretKey: string;
  private readonly payoutApiKey: string;
  private readonly timeoutMs: number;
  private readonly maxRetries: number;
  private readonly retryBaseDelayMs: number;
  private readonly fetchImpl: typeof fetch;

  constructor(config: GoPayConfig) {
    this.baseUrl = (config.baseUrl ?? DEFAULT_BASE_URL).replace(/\/+$/, '');
    if (!this.baseUrl.startsWith('https://')) {
      throw new GoPayConfigurationError('GoPAY baseUrl must use HTTPS.');
    }
    this.paymentApiKey = config.paymentApiKey ?? '';
    this.paymentSecretKey = config.paymentSecretKey ?? '';
    this.payoutApiKey = config.payoutApiKey ?? '';
    this.timeoutMs = config.timeoutMs ?? 30000;
    this.maxRetries = Math.max(0, config.maxRetries ?? 2);
    this.retryBaseDelayMs = Math.max(0, config.retryBaseDelayMs ?? 300);
    const f = config.fetch ?? (globalThis as { fetch?: typeof fetch }).fetch;
    if (!f) {
      throw new GoPayConfigurationError('No fetch implementation available. Use Node.js >= 18 or pass `fetch` in config.');
    }
    this.fetchImpl = f;
  }

  /**
   * Initialise une demande de paiement mobile money (CDF ou USD).
   * Les requêtes POST ne sont jamais rejouées automatiquement (évite un double débit).
   */
  public async initPayment(
    amount: number,
    currency: GoPayCurrency,
    phone: string,
    reference: string,
    userId?: string
  ): Promise<PaymentResponse> {
    const payload: Record<string, unknown> = {
      amount,
      devise: currency,
      telephone: phone,
      myref: reference,
    };
    if (userId !== undefined && userId !== null && userId !== '') {
      payload.users_id = userId;
    }

    const data = await this.request('POST', '/api/v3/payment/init', payload, false);
    return this.parsePaymentResponse(data);
  }

  /**
   * Vérifie le statut d'une transaction de paiement par sa référence marchand.
   */
  public async checkPayment(reference: string): Promise<PaymentResponse> {
    const data = await this.request('GET', `/api/v3/payment/check/${encodeURIComponent(reference)}`, {}, false);
    return this.parsePaymentResponse(data);
  }

  /**
   * Récupère le solde disponible du compte Payout.
   */
  public async getPayoutBalance(): Promise<PayoutBalanceResponse> {
    const data = (await this.request('GET', '/api/payout/v3/balance', {}, true)) as any;
    const balanceData = data?.data ?? data;
    return {
      success: Boolean(data?.success ?? true),
      message: data?.message,
      errorCode: data?.error_code,
      balance: typeof balanceData?.balance === 'number' ? balanceData.balance : Number(balanceData?.balance ?? 0),
      currency: balanceData?.currency ?? balanceData?.devise,
      raw: data,
    };
  }

  /**
   * Récupère la liste de tous les transferts Payouts émis.
   */
  public async getPayoutTransfers(): Promise<unknown[]> {
    const data = (await this.request('GET', '/api/payout/v3/transfer', {}, true)) as any;
    return Array.isArray(data) ? data : data?.data ?? [data];
  }

  /**
   * Émet un ou plusieurs transferts Payouts groupés.
   */
  public async sendPayoutTransfer(
    amount: number,
    currency: GoPayCurrency,
    phones: string[],
    references: string[],
    sendDate?: string
  ): Promise<PayoutTransferResponse> {
    const payload: Record<string, unknown> = {
      montant: amount,
      devise: currency,
      telephone: phones,
      myref: references,
    };
    if (sendDate) {
      payload.date_denvoi = sendDate;
    }

    const data = await this.request('POST', '/api/payout/v3/transfer', payload, true);
    return this.parsePayoutResponse(data);
  }

  /**
   * Consulte le statut détaillé d'un transfert Payout par son ID ou sa référence.
   */
  public async getPayoutTransferStatus(transIdOrRef: string): Promise<PayoutTransferResponse> {
    const data = await this.request('GET', `/api/payout/v3/transfer/${encodeURIComponent(transIdOrRef)}`, {}, true);
    return this.parsePayoutResponse(data);
  }

  /**
   * Supprime / annule un transfert Payout (au statut EN ATTENTE).
   */
  public async deletePayoutTransfer(transId: string): Promise<PayoutTransferResponse> {
    const data = await this.request('DELETE', `/api/payout/v3/transfer/${encodeURIComponent(transId)}`, {}, true);
    return this.parsePayoutResponse(data);
  }

  private async request(
    method: GoPayHttpMethod,
    endpoint: string,
    payload: Record<string, unknown>,
    isPayout: boolean
  ): Promise<unknown> {
    const apiKey = isPayout ? this.payoutApiKey : this.paymentApiKey;
    const secret = this.paymentSecretKey;

    if (!apiKey || !secret) {
      throw new GoPayConfigurationError(
        `GoPAY ${isPayout ? 'payout' : 'payment'} API key and signing secret must be provided.`
      );
    }

    // Seules les lectures (GET) sont rejouées : POST/DELETE peuvent avoir des effets de bord.
    const attempts = method === 'GET' ? this.maxRetries + 1 : 1;
    let lastError: unknown;

    for (let attempt = 0; attempt < attempts; attempt++) {
      if (attempt > 0) {
        await sleep(backoffDelay(this.retryBaseDelayMs, attempt));
      }
      try {
        return await this.send(method, endpoint, payload, apiKey, secret);
      } catch (err) {
        lastError = err;
        if (!(err instanceof RetryableError)) {
          throw err;
        }
      }
    }
    throw (lastError as RetryableError).error;
  }

  private async send(
    method: GoPayHttpMethod,
    endpoint: string,
    payload: Record<string, unknown>,
    apiKey: string,
    secret: string
  ): Promise<unknown> {
    // Un nouveau nonce/timestamp à chaque tentative (protection anti-rejeu côté serveur).
    const timestamp = Math.floor(Date.now() / 1000);
    const nonce = generateNonce();
    const queryString = buildQueryString(payload);

    const urlObj = new URL(this.baseUrl + endpoint);
    const signature = computeSignature(urlObj.pathname, method, queryString, nonce, timestamp, secret);

    const headers: Record<string, string> = {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'x-api-key': apiKey,
      'x-signature': signature,
      'x-timestamp': String(timestamp),
      'x-nonce': nonce,
    };

    let targetUrl = urlObj.toString();
    let body: string | undefined;

    if (method === 'GET' && queryString) {
      targetUrl += (targetUrl.includes('?') ? '&' : '?') + queryString;
    } else if (method === 'POST') {
      body = JSON.stringify(payload);
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), this.timeoutMs);

    let res: Response;
    let text: string;
    try {
      res = await this.fetchImpl(targetUrl, { method, headers, body, signal: controller.signal });
      text = await res.text();
    } catch (err: any) {
      throw new RetryableError(
        new GoPayError(`GoPAY HTTP request to ${targetUrl} failed: ${err?.message ?? err}`, err)
      );
    } finally {
      clearTimeout(timer);
    }

    if (RETRYABLE_STATUS.has(res.status)) {
      const error = new GoPayError(`GoPAY responded with HTTP ${res.status}: ${text}`, undefined, res.status);
      throw new RetryableError(error);
    }

    if (!text) {
      return {};
    }

    try {
      return JSON.parse(text);
    } catch (jsonErr) {
      throw new GoPayError(`Invalid JSON response received from GoPAY (HTTP ${res.status}): ${text}`, jsonErr, res.status);
    }
  }

  private parsePaymentResponse(raw: any): PaymentResponse {
    const tx = raw?.transaction ?? raw?.data ?? {};
    const success = Boolean(raw?.success ?? raw?.status === 'success');
    return {
      success,
      message: raw?.message,
      errorCode: raw?.error_code,
      transId: tx?.trans_id ?? raw?.trans_id,
      transactionStatus: tx?.status ?? raw?.status,
      amount: tx?.amount ?? raw?.amount,
      currency: tx?.currency ?? raw?.currency ?? tx?.devise,
      url: tx?.url ?? raw?.url,
      state: tx?.state ?? raw?.state,
      source: tx?.source ?? raw?.source,
      date: tx?.date ?? raw?.date,
      raw,
    };
  }

  private parsePayoutResponse(raw: any): PayoutTransferResponse {
    const tx = raw?.data ?? raw?.transfer ?? {};
    const success = Boolean(raw?.success ?? true);
    return {
      success,
      message: raw?.message,
      errorCode: raw?.error_code,
      transId: tx?.trans_id ?? raw?.trans_id,
      transactionStatus: tx?.status ?? raw?.status,
      amount: tx?.montant ?? raw?.montant ?? tx?.amount,
      currency: tx?.devise ?? raw?.devise ?? tx?.currency,
      state: raw?.state,
      raw,
    };
  }
}

class RetryableError extends Error {
  constructor(public readonly error: GoPayError) {
    super(error.message);
  }
}

function backoffDelay(baseMs: number, attempt: number): number {
  const exp = baseMs * 2 ** (attempt - 1);
  return exp + Math.floor(Math.random() * baseMs);
}

function sleep(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}
