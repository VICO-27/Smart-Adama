import { describe, it, expect } from 'vitest'
import { normalizeEthiopianPhone } from './phone'

describe('normalizeEthiopianPhone', () => {
  it('normalizes 10-digit 09... Ethiopian numbers', () => {
    const res = normalizeEthiopianPhone('0912345678')
    expect(res.isValid).toBe(true)
    expect(res.e164).toBe('+251912345678')
    expect(res.display).toBe('+251 91 234 5678')
  })

  it('normalizes 10-digit 07... Safaricom numbers', () => {
    const res = normalizeEthiopianPhone('0712345678')
    expect(res.isValid).toBe(true)
    expect(res.e164).toBe('+251712345678')
    expect(res.display).toBe('+251 71 234 5678')
  })

  it('normalizes 9-digit numbers without leading zero', () => {
    const res = normalizeEthiopianPhone('912345678')
    expect(res.isValid).toBe(true)
    expect(res.e164).toBe('+251912345678')
  })

  it('normalizes 2519... numbers without leading plus', () => {
    const res = normalizeEthiopianPhone('251912345678')
    expect(res.isValid).toBe(true)
    expect(res.e164).toBe('+251912345678')
  })

  it('normalizes +251 91 234 5678 formatted numbers', () => {
    const res = normalizeEthiopianPhone('+251 91 234 5678')
    expect(res.isValid).toBe(true)
    expect(res.e164).toBe('+251912345678')
  })

  it('handles empty or whitespace strings', () => {
    const res = normalizeEthiopianPhone('   ')
    expect(res.isValid).toBe(false)
  })

  it('rejects invalid numbers', () => {
    const res = normalizeEthiopianPhone('123')
    expect(res.isValid).toBe(false)
  })
})
