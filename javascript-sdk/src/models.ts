export type GoPayCurrency = 'CDF' | 'USD' | string;

export interface GoPayConfig {
  baseUrl?: string;
  paymentApiKey?: string;
  paymentSecretKey?: string;
  payoutApiKey?: string;
  timeoutMs?: number;
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
