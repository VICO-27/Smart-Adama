import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAdminUIStore = defineStore('admin-ui', () => {
  const isSearchOpen = ref(false)
  const isNotificationsOpen = ref(false)
  const isSettingsOpen = ref(false)

  function toggleSearch() {
    isSearchOpen.value = !isSearchOpen.value
  }

  function toggleNotifications() {
    isNotificationsOpen.value = !isNotificationsOpen.value
  }

  function toggleSettings() {
    isSettingsOpen.value = !isSettingsOpen.value
  }

  function closeAll() {
    isSearchOpen.value = false
    isNotificationsOpen.value = false
    isSettingsOpen.value = false
  }

  return {
    isSearchOpen,
    isNotificationsOpen,
    isSettingsOpen,
    toggleSearch,
    toggleNotifications,
    toggleSettings,
    closeAll
  }
})
