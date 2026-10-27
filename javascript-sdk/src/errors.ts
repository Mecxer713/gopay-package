export class GoPayError extends Error {
  public readonly cause?: unknown;

  constructor(message: string, cause?: unknown) {
    super(message);
    this.name = 'GoPayError';
    this.cause = cause;
    Object.setPrototypeOf(this, new.target.prototype);
  }
}

export class GoPayConfigurationError extends GoPayError {
  constructor(message: string = 'GoPAY API keys and signing secret must be configured.') {
    super(message);
    this.name = 'GoPayConfigurationError';
    Object.setPrototypeOf(this, new.target.prototype);
  }
}

export enum GoPayErrorCode {
  APIKEY_MISSING = 'ERR_APIKEY_MISSING',
  SIGNATURE_MISSING = 'ERR_SIGNATURE_MISSING',
  TIMESTAMP_MISSING = 'ERR_TIMESTAMP_MISSING',
  NONCE_MISSING = 'ERR_NONCE_MISSING',
  APIKEY_INVALID = 'ERR_APIKEY_INVALID',
  APIKEY_FORBIDDEN = 'ERR_APIKEY_FORBIDDEN',
  APIKEY_INACTIVE = 'ERR_APIKEY_INACTIVE',
  SIGNATURE_INVALID = 'ERR_SIGNATURE_INVALID',
  TIMESTAMP_EXPIRED = 'ERR_TIMESTAMP_EXPIRED',
  NONCE_REPLAY = 'ERR_NONCE_REPLAY',
  VALIDATION = 'ERR_VALIDATION',
  NO_PAYMENT_FOUND = 'ERR_NO_PAYMENT_FOUND',
}
