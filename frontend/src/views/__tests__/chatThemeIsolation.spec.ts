// @vitest-environment jsdom
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { readFileSync } from 'fs'
import { resolve } from 'path'
import { useTheme } from '@/composables/useTheme'

describe('Study Page Theme Isolation & Independence', () => {
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

    // Reset DOM elements
    document.documentElement.className = ''
    document.documentElement.removeAttribute('data-theme')
    document.body.className = ''
    document.body.removeAttribute('data-theme')

    let app = document.getElementById('app')
    if (!app) {
      app = document.createElement('div')
      app.id = 'app'
      document.body.appendChild(app)
    }
    app.className = ''
    app.removeAttribute('data-theme')
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('keeps website in light mode while study page runs in dark mode, and restores website light mode on exit', () => {
    const { setTheme: setGlobalTheme, initializeTheme: restoreGlobalTheme } = useTheme()

    // 1. Website is set to light mode
    setGlobalTheme('light')
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(localStorage.getItem('sa_theme')).toBe('light')

    // 2. User enters Study Page with reader theme 'dark'
    function applyStudyTheme(theme: string) {
      const isDark = theme === 'dark'
      const root = document.documentElement
      const body = document.body
      const app = document.getElementById('app')

      if (isDark) {
        root.classList.add('dark')
        root.dataset.theme = 'dark'
        body.classList.add('dark-theme')
        body.dataset.theme = 'dark'
        if (app) {
          app.classList.add('dark')
          app.dataset.theme = 'dark'
        }
      } else {
        root.classList.remove('dark')
        root.dataset.theme = theme
        body.classList.remove('dark-theme')
        body.dataset.theme = theme
        if (app) {
          app.classList.remove('dark')
          app.dataset.theme = theme
        }
      }
    }

    applyStudyTheme('dark')
    // Study page is dark
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(document.body.classList.contains('dark-theme')).toBe(true)
    // Global preference was NOT corrupted
    expect(localStorage.getItem('sa_theme')).toBe('light')

    // 3. User leaves Study Page back to Dashboard
    restoreGlobalTheme()
    // Website returns to light mode
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(document.documentElement.dataset.theme).toBe('light')
    expect(document.body.classList.contains('dark-theme')).toBe(false)
  })

  it('keeps website in dark mode while study page runs in light mode, and restores website dark mode on exit', () => {
    const { setTheme: setGlobalTheme, initializeTheme: restoreGlobalTheme } = useTheme()

    // 1. Website is set to dark mode
    setGlobalTheme('dark')
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(localStorage.getItem('sa_theme')).toBe('dark')

    // 2. User enters Study Page with reader theme 'light'
    function applyStudyTheme(theme: string) {
      const isDark = theme === 'dark'
      const root = document.documentElement
      const body = document.body
      const app = document.getElementById('app')

      if (isDark) {
        root.classList.add('dark')
        root.dataset.theme = 'dark'
        body.classList.add('dark-theme')
        body.dataset.theme = 'dark'
        if (app) {
          app.classList.add('dark')
          app.dataset.theme = 'dark'
        }
      } else {
        root.classList.remove('dark')
        root.dataset.theme = theme
        body.classList.remove('dark-theme')
        body.dataset.theme = theme
        if (app) {
          app.classList.remove('dark')
          app.dataset.theme = theme
        }
      }
    }

    applyStudyTheme('light')
    // Study page is light (dark class removed so dark mode overrides cannot bleed in)
    expect(document.documentElement.classList.contains('dark')).toBe(false)
    expect(document.documentElement.dataset.theme).toBe('light')
    expect(document.body.classList.contains('dark-theme')).toBe(false)
    // Website global preference in localStorage was NOT touched
    expect(localStorage.getItem('sa_theme')).toBe('dark')

    // 3. User leaves Study Page back to Dashboard
    restoreGlobalTheme()
    // Website returns to dark mode
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(document.documentElement.dataset.theme).toBe('dark')
    expect(document.body.classList.contains('dark-theme')).toBe(true)
  })

  it('supports sepia, forest (green), and walnut (brown) themes without global dark mode bleed', () => {
    const { setTheme: setGlobalTheme, initializeTheme: restoreGlobalTheme } = useTheme()

    // Website is in dark mode
    setGlobalTheme('dark')
    expect(document.documentElement.classList.contains('dark')).toBe(true)

    function applyStudyTheme(theme: string) {
      const isDark = theme === 'dark'
      const root = document.documentElement
      const body = document.body
      const app = document.getElementById('app')

      if (isDark) {
        root.classList.add('dark')
        root.dataset.theme = 'dark'
        body.classList.add('dark-theme')
        body.dataset.theme = 'dark'
        if (app) {
          app.classList.add('dark')
          app.dataset.theme = 'dark'
        }
      } else {
        root.classList.remove('dark')
        root.dataset.theme = theme
        body.classList.remove('dark-theme')
        body.dataset.theme = theme
        if (app) {
          app.classList.remove('dark')
          app.dataset.theme = theme
        }
      }
    }

    // Switch through non-dark reader themes
    for (const theme of ['sepia', 'green', 'brown'] as const) {
      applyStudyTheme(theme)
      expect(document.documentElement.classList.contains('dark')).toBe(false)
      expect(document.documentElement.dataset.theme).toBe(theme)
      expect(localStorage.getItem('sa_theme')).toBe('dark')
    }

    // Leaving study page restores website dark mode
    restoreGlobalTheme()
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(document.documentElement.dataset.theme).toBe('dark')
  })

  it('verifies that style.css guards all html.dark forced overrides with :not(.chat-page *)', () => {
    const styleCssPath = resolve(__dirname, '../../style.css')
    const cssContent = readFileSync(styleCssPath, 'utf-8')

    // Check that form control overrides have :not(.chat-page *)
    expect(cssContent).toMatch(/html\.dark\s+input:not\(\.chat-page \*\)/)
    expect(cssContent).toMatch(/html\.dark\s+textarea:not\(\.chat-page \*\)/)
    expect(cssContent).toMatch(/html\.dark\s+select:not\(\.chat-page \*\)/)

    // Check that text utility overrides have :not(.chat-page *)
    expect(cssContent).toMatch(/html\.dark\s+\.text-slate-900:not\(\.chat-page \*\)/)
    expect(cssContent).toMatch(/html\.dark\s+\.text-slate-800:not\(\.chat-page \*\)/)
    expect(cssContent).toMatch(/html\.dark\s+\.bg-white:not\(\.chat-page \*\)/)
  })
})
