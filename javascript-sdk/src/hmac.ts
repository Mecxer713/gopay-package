import { createHmac, randomBytes } from 'node:crypto';

export function phpUrlEncode(str: string): string {
  const bytes = Buffer.from(str, 'utf8');
  let result = '';
  for (let i = 0; i < bytes.length; i++) {
    const b = bytes[i];
    const isAlphaNumeric =
      (b >= 0x41 && b <= 0x5a) ||
      (b >= 0x61 && b <= 0x7a) ||
      (b >= 0x30 && b <= 0x39);
    const isUnreserved = isAlphaNumeric || b === 0x2d || b === 0x5f || b === 0x2e;
    if (isUnreserved) {
      result += String.fromCharCode(b);
    } else if (b === 0x20) {
      result += '+';
    } else {
      result += '%' + b.toString(16).padStart(2, '0').toUpperCase();
    }
  }
  return result;
}

export function formatQueryValue(value: unknown): string {
  if (typeof value === 'number' && Number.isFinite(value) && Math.floor(value) === value) {
    return value.toString();
  }
  return String(value);
}

export function buildQueryString(payload: Record<string, unknown>): string {
  const parts: string[] = [];
  for (const [key, value] of Object.entries(payload)) {
    if (Array.isArray(value)) {
      for (let i = 0; i < value.length; i++) {
        parts.push(`${phpUrlEncode(`${key}[${i}]`)}=${phpUrlEncode(formatQueryValue(value[i]))}`);
      }
    } else if (value !== undefined && value !== null) {
      parts.push(`${phpUrlEncode(key)}=${phpUrlEncode(formatQueryValue(value))}`);
    }
  }
  return parts.join('&');
}

export function computeSignature(
  endpointPath: string,
  method: string,
  queryString: string,
  nonce: string,
  timestamp: number | string,
  secretKey: string
): string {
  const message = `${endpointPath}${method}${queryString}${nonce}${timestamp}`;
  return createHmac('sha256', secretKey).update(message, 'utf8').digest('hex');
}

export function generateNonce(): string {
  return randomBytes(16).toString('hex');
}
