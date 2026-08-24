<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useAdminUIStore } from '@/stores/admin-ui'
import { Menu, Bell, Search, Settings } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import AdminNotificationDropdown from './AdminNotificationDropdown.vue'
import AdminSettingsDropdown from './AdminSettingsDropdown.vue'

const auth = useAuthStore()
const adminUI = useAdminUIStore()
const router = useRouter()

defineEmits(['toggle-sidebar', 'toggle-mobile-sidebar'])

const navigateToProfile = () => {
  router.push('/admin/profile')
}
</script>

<template>
  <header class="sticky top-0 z-30 flex h-16 w-full items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 px-4 backdrop-blur-md sm:px-6 lg:px-8 transition-colors duration-300">
    <div class="flex items-center gap-4">
      <button 
        class="rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 lg:hidden transition-colors"
        @click="$emit('toggle-mobile-sidebar')"
      >
        <Menu class="h-5 w-5" />
      </button>
      <button 
        class="hidden rounded-lg p-2 text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 lg:block transition-colors"
        @click="$emit('toggle-sidebar')"
      >
        <Menu class="h-5 w-5" />
      </button>
      
      <!-- Quick Search / Command Palette Trigger -->
      <button 
        @click="adminUI.toggleSearch"
        class="hidden lg:flex items-center gap-2 rounded-full border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-4 py-1.5 text-sm text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
      >
        <Search class="h-4 w-4 text-slate-400 dark:text-slate-500" />
        <span>Search command...</span>
        <div class="ml-4 flex items-center gap-1">
          <kbd class="rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-1.5 font-sans text-[10px] font-medium text-slate-400 dark:text-slate-300">⌘</kbd>
          <kbd class="rounded border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 px-1.5 font-sans text-[10px] font-medium text-slate-400 dark:text-slate-300">K</kbd>
        </div>
      </button>
    </div>

    <div class="flex items-center gap-4 relative">
      <div class="relative">
        <button 
          @click.stop="adminUI.toggleNotifications"
          class="notification-toggle-btn relative rounded-full p-2 text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300': adminUI.isNotificationsOpen }"
        >
          <Bell class="h-5 w-5" />
          <span class="absolute top-1.5 right-1.5 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white dark:ring-slate-900"></span>
        </button>
        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
          <AdminNotificationDropdown v-if="adminUI.isNotificationsOpen" />
        </transition>
      </div>

      <div class="relative">
        <button 
          @click.stop="adminUI.toggleSettings"
          class="settings-toggle-btn rounded-full p-2 text-slate-400 dark:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
          :class="{ 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300': adminUI.isSettingsOpen }"
        >
          <Settings class="h-5 w-5" />
        </button>
        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
          <AdminSettingsDropdown v-if="adminUI.isSettingsOpen" />
        </transition>
      </div>
      
      <div class="h-8 w-px bg-slate-200 dark:bg-slate-700 hidden sm:block"></div>
      
      <div 
        class="flex items-center gap-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 p-1.5 rounded-lg transition-colors"
        @click="navigateToProfile"
      >
        <div class="hidden flex-col items-end sm:flex">
          <span class="text-sm font-semibold text-slate-900 dark:text-slate-100 leading-none">{{ auth.user?.name || 'Administrator' }}</span>
          <span class="text-xs text-slate-500 dark:text-slate-400 mt-1">Platform Admin</span>
        </div>
        <img 
          :src="auth.user?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=F0F3FA&color=395886'" 
          alt="Avatar" 
          class="h-9 w-9 rounded-full ring-2 ring-slate-100 dark:ring-slate-700 object-cover"
        />
      </div>
    </div>
  </header>
</template>
