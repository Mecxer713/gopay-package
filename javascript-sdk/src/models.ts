export type GoPayCurrency = 'CDF' | 'USD' | string;

export type GoPayHttpMethod = 'GET' | 'POST' | 'DELETE';

export interface GoPayConfig {
  /** URL de base de l'API (HTTPS obligatoire). Défaut : https://gopay.gooomart.com */
  baseUrl?: string;
  /** Clé API de paiement (requise pour initPayment / checkPayment). */
  paymentApiKey?: string;
  /** Secret de signature HMAC (requis pour toutes les opérations). */
  paymentSecretKey?: string;
  /** Clé API Payout (requise uniquement pour les opérations payout — à garder côté serveur). */
  payoutApiKey?: string;
  /** Timeout par requête en millisecondes. Défaut : 30000. */
  timeoutMs?: number;
  /** Nombre de nouvelles tentatives pour les requêtes GET en cas d'erreur transitoire. Défaut : 2. */
  maxRetries?: number;
  /** Délai de base du backoff exponentiel (ms). Défaut : 300. */
  retryBaseDelayMs?: number;
  /** Implémentation fetch personnalisée (tests, polyfill, proxy). Défaut : globalThis.fetch. */
  fetch?: typeof fetch;
}

export interface GoPayResponse<T = Record<string, any>> {
  success: boolean;
  message?: string;
  errorCode?: string;
  transId?: string;
  transactionStatus?: string;
  amount?: number | string;
  currency?: string;
  raw: T;
}

export interface PaymentResponse extends GoPayResponse {
  url?: string;
  state?: string;
  source?: string;
  date?: string;
}

export interface PayoutBalanceResponse extends GoPayResponse {
  balance?: number;
  currency?: string;
}

export interface PayoutTransferResponse extends GoPayResponse {
  transId?: string;
  state?: string;
}

export interface GoPayHttpRequest {
  method: 'GET' | 'POST' | 'DELETE' | string;
  url: string;
  headers: Record<string, string>;
  body?: string;
}

export interface GoPayHttpResponse {
  statusCode: number;
  body: string;
}
