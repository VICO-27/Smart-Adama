<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import api from '@/api/client'
import { useAdminUIStore } from '@/stores/admin-ui'
import { Bell, CheckCircle, AlertTriangle, Info, UserPlus, X } from 'lucide-vue-next'

const adminUI = useAdminUIStore()
const notifications = ref<any[]>([])
const unreadCount = ref(0)
const isLoading = ref(false)

const dropdownRef = ref<HTMLElement | null>(null)

const fetchNotifications = async () => {
  isLoading.value = true
  try {
    const res = await api.get('/api/v1/admin/notifications')
    notifications.value = res.data.notifications || []
    unreadCount.value = res.data.unread_count || 0
  } catch (error) {
    console.error('Failed to fetch notifications', error)
  } finally {
    isLoading.value = false
  }
}

const markAsRead = async (id: string) => {
  try {
    await api.post(`/api/v1/admin/notifications/${id}/mark-read`)
    const n = notifications.value.find(x => x.id === id)
    if (n && !n.read_at) {
      n.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Failed to mark read', error)
  }
}

const markAllAsRead = async () => {
  try {
    await api.post('/api/v1/admin/notifications/mark-all-read')
    notifications.value.forEach(n => {
      if (!n.read_at) n.read_at = new Date().toISOString()
    })
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to mark all read', error)
  }
}

const seedMockData = async () => {
  try {
    await api.post('/api/v1/admin/notifications/seed')
    await fetchNotifications()
  } catch (error) {
    console.error('Failed to seed notifications', error)
  }
}

const getIcon = (iconStr: string) => {
  switch (iconStr) {
    case 'check-circle': return CheckCircle
    case 'alert-triangle': return AlertTriangle
    case 'user-plus': return UserPlus
    default: return Info
  }
}

const getIconClass = (colorStr: string) => {
  switch (colorStr) {
    case 'green': return 'text-emerald-500 bg-emerald-50 dark:bg-emerald-500/10'
    case 'red': return 'text-red-500 bg-red-50 dark:bg-red-500/10'
    case 'blue': return 'text-blue-500 bg-blue-50 dark:bg-blue-500/10'
    default: return 'text-slate-500 bg-slate-50 dark:bg-slate-500/10'
  }
}

const handleClickOutside = (e: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    // Check if click was on the toggle button itself (handled by store)
    const target = e.target as HTMLElement
    if (!target.closest('.notification-toggle-btn')) {
      adminUI.isNotificationsOpen = false
    }
  }
}

onMounted(() => {
  fetchNotifications()
  document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', handleClickOutside)
})
</script>

<template>
  <div ref="dropdownRef" class="absolute right-0 top-12 mt-2 w-80 sm:w-96 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl overflow-hidden z-50">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 px-4 py-3">
      <div class="flex items-center gap-2">
        <h3 class="font-semibold text-slate-800 dark:text-slate-100">Notifications</h3>
        <span v-if="unreadCount > 0" class="rounded-full bg-brand-100 dark:bg-brand-500/20 px-2 py-0.5 text-xs font-medium text-brand-600 dark:text-brand-400">
          {{ unreadCount }} new
        </span>
      </div>
      <div class="flex gap-2">
        <button 
          v-if="unreadCount > 0"
          @click="markAllAsRead" 
          class="text-xs text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 transition-colors"
        >
          Mark all read
        </button>
      </div>
    </div>
    
    <div class="max-h-[60vh] overflow-y-auto">
      <div v-if="isLoading" class="flex justify-center p-8">
        <div class="h-6 w-6 animate-spin rounded-full border-2 border-brand-500 border-t-transparent"></div>
      </div>
      
      <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center p-8 text-center">
        <Bell class="h-10 w-10 text-slate-300 dark:text-slate-600 mb-3" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">All caught up!</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Check back later for new alerts.</p>
        <button @click="seedMockData" class="mt-4 text-xs bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 px-3 py-1 rounded text-slate-600 dark:text-slate-400">Seed Mock Data</button>
      </div>
      
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/60">
        <div 
          v-for="notification in notifications" 
          :key="notification.id"
          class="relative flex gap-3 p-4 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
          :class="{'bg-brand-50/30 dark:bg-brand-500/5': !notification.read_at}"
        >
          <div 
            v-if="!notification.read_at"
            class="absolute left-1.5 top-1/2 -translate-y-1/2 h-1.5 w-1.5 rounded-full bg-brand-500"
          ></div>
          
          <div 
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
            :class="getIconClass(JSON.parse(notification.data).color || 'gray')"
          >
            <component :is="getIcon(JSON.parse(notification.data).icon)" class="h-4 w-4" />
          </div>
          
          <div class="flex-1 space-y-1">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-medium text-slate-800 dark:text-slate-200" :class="{'font-semibold': !notification.read_at}">
                {{ JSON.parse(notification.data).title }}
              </p>
              <span class="text-[10px] text-slate-400 dark:text-slate-500 whitespace-nowrap">
                {{ new Date(notification.created_at).toLocaleDateString() }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
              {{ JSON.parse(notification.data).message }}
            </p>
            <div class="pt-1">
              <span class="text-[10px] uppercase tracking-wider font-semibold text-slate-400 dark:text-slate-500">
                {{ JSON.parse(notification.data).category }}
              </span>
            </div>
          </div>
          
          <button 
            v-if="!notification.read_at"
            @click="markAsRead(notification.id)"
            class="absolute right-2 top-2 rounded p-1 text-slate-400 opacity-0 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-300 transition-all group-hover:opacity-100"
            title="Mark as read"
          >
            <CheckCircle class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
    
    <div class="border-t border-slate-100 dark:border-slate-800 p-2">
      <button class="w-full rounded-lg px-4 py-2 text-center text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors">
        View all notifications
      </button>
    </div>
  </div>
</template>
