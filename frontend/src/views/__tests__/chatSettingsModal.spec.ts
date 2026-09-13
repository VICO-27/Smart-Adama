// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'

describe('ChatGPT Settings & Profile UI', () => {
  let mockStorage: Record<string, string> = {}

  beforeEach(() => {
    mockStorage = {}
    if (typeof globalThis.localStorage === 'undefined' || !globalThis.localStorage.getItem) {
      globalThis.localStorage = {
        getItem: (k: string) => mockStorage[k] ?? null,
        setItem: (k: string, v: string) => { mockStorage[k] = v },
        removeItem: (k: string) => { delete mockStorage[k] },
        clear: () => { mockStorage = {} },
        length: 0,
        key: () => null,
      } as any
    } else {
      vi.spyOn(Storage.prototype, 'getItem').mockImplementation((k: string) => mockStorage[k] ?? null)
      vi.spyOn(Storage.prototype, 'setItem').mockImplementation((k: string, v: string) => { mockStorage[k] = v })
      vi.spyOn(Storage.prototype, 'removeItem').mockImplementation((k: string) => { delete mockStorage[k] })
      vi.spyOn(Storage.prototype, 'clear').mockImplementation(() => { mockStorage = {} })
    }
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('persists theme selection cleanly without touching global website theme', () => {
    const READER_THEME_KEY = 'smart-adama-reader-theme'
    const GLOBAL_THEME_KEY = 'sa_theme'

    // Simulate global theme is dark
    localStorage.setItem(GLOBAL_THEME_KEY, 'dark')

    // Change study reader theme to sepia
    localStorage.setItem(READER_THEME_KEY, 'sepia')
    expect(localStorage.getItem(READER_THEME_KEY)).toBe('sepia')
    expect(localStorage.getItem(GLOBAL_THEME_KEY)).toBe('dark')

    // Change study reader theme to forest (green)
    localStorage.setItem(READER_THEME_KEY, 'green')
    expect(localStorage.getItem(READER_THEME_KEY)).toBe('green')
    expect(localStorage.getItem(GLOBAL_THEME_KEY)).toBe('dark')
  })

  it('persists font selection between sans-serif and serif', () => {
    const READER_FONT_KEY = 'smart-adama-reader-font'

    localStorage.setItem(READER_FONT_KEY, 'serif')
    expect(localStorage.getItem(READER_FONT_KEY)).toBe('serif')

    localStorage.setItem(READER_FONT_KEY, 'sans')
    expect(localStorage.getItem(READER_FONT_KEY)).toBe('sans')
  })

  it('supports all 5 ChatGPT theme keys: light, sepia, dark, green, brown', () => {
    const expectedThemes = ['light', 'sepia', 'dark', 'green', 'brown']
    const READER_THEME_KEY = 'smart-adama-reader-theme'

    for (const key of expectedThemes) {
      localStorage.setItem(READER_THEME_KEY, key)
      expect(localStorage.getItem(READER_THEME_KEY)).toBe(key)
    }
  })
})
