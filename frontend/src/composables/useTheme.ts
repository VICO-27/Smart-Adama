import { ref } from 'vue'

export type ThemePreference = 'light' | 'dark' | 'system'

const themePreference = ref<ThemePreference>('light')

export function useTheme() {

  function getSystemTheme(): boolean {
    if (typeof window === 'undefined') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
  }

  function resolveTheme(theme: ThemePreference): 'light' | 'dark' {
    if (theme === 'dark') return 'dark'
    if (theme === 'light') return 'light'
    return getSystemTheme() ? 'dark' : 'light'
  }

  function applyTheme(theme: ThemePreference) {
    const activeTheme = resolveTheme(theme)
    const root = document.documentElement

    if (activeTheme === 'dark') {
      root.classList.add('dark')
      root.dataset.theme = 'dark'
      document.body.classList.add('dark-theme')
      document.body.dataset.theme = 'dark'
      const app = document.getElementById('app')
      if (app) {
        app.classList.add('dark')
        app.dataset.theme = 'dark'
      }
    } else {
      root.classList.remove('dark')
      root.dataset.theme = 'light'
      document.body.classList.remove('dark-theme')
      document.body.dataset.theme = 'light'
      const app = document.getElementById('app')
      if (app) {
        app.classList.remove('dark')
        app.dataset.theme = 'light'
      }
    }

    localStorage.setItem('sa_theme', theme)
    localStorage.setItem('theme', theme)
  }

  function initializeTheme() {
    const savedTheme = localStorage.getItem('sa_theme')
    const legacyTheme = localStorage.getItem('theme')

    if (savedTheme === 'light' || savedTheme === 'dark' || savedTheme === 'system') {
      themePreference.value = savedTheme as ThemePreference
    } else if (legacyTheme === 'light' || legacyTheme === 'dark' || legacyTheme === 'system') {
      themePreference.value = legacyTheme as ThemePreference
    } else {
      themePreference.value = 'light'
    }

    applyTheme(themePreference.value)
  }

  function setTheme(theme: ThemePreference) {
    themePreference.value = theme
    applyTheme(theme)
  }

  function handleSystemThemeChange(e: MediaQueryListEvent) {
    if (themePreference.value === 'system') {
      applyTheme('system')
    }
  }

  return {
    themePreference,
    initializeTheme,
    setTheme,
    handleSystemThemeChange,
  }
}
