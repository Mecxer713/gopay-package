import { GoPayConfig, GoPayResponse, PaymentResponse, PayoutBalanceResponse, PayoutTransferResponse, GoPayCurrency } from './models';
import { GoPayError, GoPayConfigurationError } from './errors';
import { buildQueryString, computeSignature, generateNonce } from './hmac';

export class GoPayClient {
  private readonly baseUrl: string;
  private readonly paymentApiKey: string;
  private readonly paymentSecretKey: string;
  private readonly payoutApiKey: string;
  private readonly timeoutMs: number;

  constructor(config: GoPayConfig) {
    this.baseUrl = (config.baseUrl ?? 'https://gopay.gooomart.com').replace(/\/+$/, '');
    this.paymentApiKey = config.paymentApiKey ?? '';
    this.paymentSecretKey = config.paymentSecretKey ?? '';
    this.payoutApiKey = config.payoutApiKey ?? '';
    this.timeoutMs = config.timeoutMs ?? 30000;
  }

  /**
   * Initialise une demande de paiement mobile money (CDF ou USD).
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
    const data = await this.request('GET', '/api/v3/payment/check', { ref: reference }, false);
    return this.parsePaymentResponse(data);
  }

  /**
   * Récupère le solde disponible du compte Payout.
   */
  public async getPayoutBalance(): Promise<PayoutBalanceResponse> {
    const data = await this.request('GET', '/api/payout/v3/balance', {}, true);
    const balanceData = (data as any)?.data ?? data;
    return {
      success: Boolean((data as any)?.success ?? true),
      message: (data as any)?.message,
      errorCode: (data as any)?.error_code,
      balance: typeof balanceData?.balance === 'number' ? balanceData.balance : Number(balanceData?.balance ?? 0),
      currency: balanceData?.currency ?? balanceData?.devise,
      raw: data as any,
    };
  }

  /**
   * Récupère la liste de tous les transferts Payouts émis.
   */
  public async getPayoutTransfers(): Promise<any[]> {
    const data = await this.request('GET', '/api/payout/v3/transfer', {}, true);
    return Array.isArray(data) ? data : (data as any)?.data ?? [data];
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
    const encoded = encodeURIComponent(transIdOrRef);
    const data = await this.request('GET', `/api/payout/v3/transfer/${encoded}`, {}, true);
    return this.parsePayoutResponse(data);
  }

  /**
   * Supprime / annule un transfert Payout (au statut EN ATTENTE).
   */
  public async deletePayoutTransfer(transId: string): Promise<PayoutTransferResponse> {
    const encoded = encodeURIComponent(transId);
    const data = await this.request('DELETE', `/api/payout/v3/transfer/${encoded}`, {}, true);
    return this.parsePayoutResponse(data);
  }

  private async request(
    method: 'GET' | 'POST' | 'DELETE',
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

    const timestamp = Math.floor(Date.now() / 1000);
    const nonce = generateNonce();
    const queryString = buildQueryString(payload);

    // Extraction du chemin d'URL pour la signature
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
    let body: string | undefined = undefined;

    if (method === 'GET' && queryString) {
      const sep = targetUrl.includes('?') ? '&' : '?';
      targetUrl += sep + queryString;
    } else if (method === 'POST') {
      body = JSON.stringify(payload);
    }

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), this.timeoutMs);

    try {
      const res = await fetch(targetUrl, {
        method,
        headers,
        body,
        signal: controller.signal,
      });

      const text = await res.text();
      clearTimeout(timer);

      if (!text) {
        return {};
      }

      try {
        return JSON.parse(text);
      } catch (jsonErr) {
        throw new GoPayError(`Invalid JSON response received from GoPAY (HTTP ${res.status}): ${text}`, jsonErr);
      }
    } catch (err: any) {
      clearTimeout(timer);
      if (err instanceof GoPayError) {
        throw err;
      }
      throw new GoPayError(`GoPAY HTTP request to ${targetUrl} failed: ${err?.message ?? err}`, err);
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
      url: raw?.url,
      state: raw?.state,
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
