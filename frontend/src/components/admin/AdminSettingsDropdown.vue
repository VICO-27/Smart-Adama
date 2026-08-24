<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminUIStore } from '@/stores/admin-ui'
import { Settings, Shield, HardDrive, Database, Paintbrush, Moon, Sun, Monitor } from 'lucide-vue-next'

const router = useRouter()
const adminUI = useAdminUIStore()
const dropdownRef = ref<HTMLElement | null>(null)

const isDark = ref(document.documentElement.classList.contains('dark'))
const toggleDark = () => {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.theme = 'dark'
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.theme = 'light'
  }
}

const navigateToSettings = () => {
  adminUI.isSettingsOpen = false
  router.push('/admin/settings')
}

const handleClickOutside = (e: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    const target = e.target as HTMLElement
    if (!target.closest('.settings-toggle-btn')) {
      adminUI.isSettingsOpen = false
    }
  }
}

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>

<template>
  <div ref="dropdownRef" class="absolute right-0 top-12 mt-2 w-64 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden z-50">
    <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
      <h3 class="font-semibold text-slate-800 dark:text-slate-100">Quick Settings</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage control center preferences</p>
    </div>
    
    <div class="p-2 space-y-1">
      <div class="px-2 py-1.5 mt-1 mb-2">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Appearance</span>
      </div>
      
      <button 
        @click="toggleDark()"
        class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
      >
        <div class="flex items-center gap-3 text-slate-700 dark:text-slate-300">
          <Moon v-if="isDark" class="h-4 w-4" />
          <Sun v-else class="h-4 w-4" />
          <span>Theme Mode</span>
        </div>
        <span class="text-xs font-medium text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/10 px-2 py-0.5 rounded-full">
          {{ isDark ? 'Dark' : 'Light' }}
        </span>
      </button>
      
      <div class="my-2 border-t border-slate-100 dark:border-slate-800"></div>
      
      <div class="px-2 py-1.5 mt-2 mb-1">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">System</span>
      </div>

      <button class="w-full flex items-center gap-3 px-3 py-2 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-slate-700 dark:text-slate-300">
        <Database class="h-4 w-4 text-slate-400" />
        <span>Clear Cache</span>
      </button>
      
      <button class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-slate-700 dark:text-slate-300">
        <div class="flex items-center gap-3">
          <Shield class="h-4 w-4 text-slate-400" />
          <span>Maintenance Mode</span>
        </div>
        <div class="h-4 w-8 bg-slate-200 dark:bg-slate-700 rounded-full relative">
          <div class="absolute left-0.5 top-0.5 h-3 w-3 bg-white dark:bg-slate-400 rounded-full"></div>
        </div>
      </button>
    </div>
    
    <div class="p-2 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/30">
      <button 
        @click="navigateToSettings"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-lg hover:bg-white dark:hover:bg-slate-800 transition-colors text-slate-700 dark:text-slate-300 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 hover:shadow-sm"
      >
        <Settings class="h-4 w-4" />
        <span>All Settings</span>
      </button>
    </div>
  </div>
</template>
