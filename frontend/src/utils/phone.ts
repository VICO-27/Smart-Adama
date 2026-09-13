/**
 * Phone number normalization & validation utility.
 * Default country code: +251 (Ethiopia)
 * Standard mobile prefixes: 9 (Ethio telecom), 7 (Safaricom Ethiopia)
 */

export interface PhoneValidationResult {
  isValid: boolean
  e164: string
  display: string
  error?: string
}

/**
 * Normalizes an Ethiopian or international phone number to E.164 format.
 * Examples:
 *   - "0912345678"       -> "+251912345678"
 *   - "0712345678"       -> "+251712345678"
 *   - "912345678"        -> "+251912345678"
 *   - "251912345678"     -> "+251912345678"
 *   - "+251 91 234 5678" -> "+251912345678"
 */
export function normalizeEthiopianPhone(input: string): PhoneValidationResult {
  if (!input || !input.trim()) {
    return {
      isValid: false,
      e164: '',
      display: '',
      error: 'Phone number is required.',
    }
  }

  // Strip all whitespace, hyphens, parenthesis, and dots
  const cleaned = input.trim().replace(/[\s\-\(\)\.]/g, '')

  let e164 = ''

  // 1. Starts with international plus '+'
  if (cleaned.startsWith('+')) {
    const withoutPlus = cleaned.slice(1)
    if (!/^\d+$/.test(withoutPlus)) {
      return { isValid: false, e164: '', display: input, error: 'Invalid characters in phone number.' }
    }
    // If it starts with +251
    if (withoutPlus.startsWith('251')) {
      e164 = `+${withoutPlus}`
    } else {
      // Other international country code
      e164 = `+${withoutPlus}`
      const isValid = withoutPlus.length >= 8 && withoutPlus.length <= 15
      return {
        isValid,
        e164,
        display: e164,
        error: isValid ? undefined : 'Please enter a valid international phone number.',
      }
    }
  }
  // 2. Starts with "00251"
  else if (cleaned.startsWith('00251')) {
    e164 = `+${cleaned.slice(2)}`
  }
  // 3. Starts with "251"
  else if (cleaned.startsWith('251')) {
    e164 = `+${cleaned}`
  }
  // 4. Starts with local prefix "0" (e.g. 09... or 07...)
  else if (cleaned.startsWith('0')) {
    e164 = `+251${cleaned.slice(1)}`
  }
  // 5. Raw 9 digits starting with 9 or 7 (e.g. 912345678)
  else if (/^[79]\d{8}$/.test(cleaned)) {
    e164 = `+251${cleaned}`
  }
  // Fallback
  else {
    e164 = `+251${cleaned}`
  }

  // Ethiopian mobile validation: +251 followed by 9 or 7 and 8 digits (total 13 chars)
  const isEthiopian = /^\+251[79]\d{8}$/.test(e164)

  if (isEthiopian) {
    // Format display as +251 9XX XXX XXX
    const display = `${e164.slice(0, 4)} ${e164.slice(4, 6)} ${e164.slice(6, 9)} ${e164.slice(9)}`
    return {
      isValid: true,
      e164,
      display,
    }
  }

  // General E.164 fallback
  const isGeneralE164 = /^\+[1-9]\d{7,14}$/.test(e164)
  return {
    isValid: isGeneralE164,
    e164,
    display: e164,
    error: isGeneralE164
      ? undefined
      : 'Please enter a valid phone number (e.g. 0912 345 678 or +251 912 345 678)',
  }
}
