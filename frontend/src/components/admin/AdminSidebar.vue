<script setup lang="ts">
import { useRoute } from 'vue-router'
import {
  LayoutDashboard,
  FileText,
  BookOpen,
  ListChecks,
  Users,
  Trophy,
  Cpu,
  Database,
  Search,
  Activity,
  Settings,
  Shield,
  X
} from 'lucide-vue-next'

defineProps<{
  isCollapsed?: boolean
}>()

defineEmits(['close'])

const route = useRoute()

const navGroups = [
  {
    label: 'Overview',
    items: [
      { name: 'Dashboard', path: '/admin', icon: LayoutDashboard },
    ]
  },
  {
    label: 'Content',
    items: [
      { name: 'Documents', path: '/admin/documents', icon: FileText },
      { name: 'Quizzes', path: '/admin/quizzes', icon: ListChecks },
    ]
  },
  {
    label: 'Users',
    items: [
      { name: 'Users', path: '/admin/users', icon: Users },
    ]
  },
  {
    label: 'AI & Knowledge',
    items: [
      { name: 'AI & RAG', path: '/admin/ai-settings', icon: Cpu },
      { name: 'Retrieval Debugger', path: '/admin/rag-debugger', icon: Search },
    ]
  },
  {
    label: 'Platform',
    items: [
      { name: 'System Health', path: '/admin/system', icon: Activity },
    ]
  }
]

function isActive(path: string) {
  if (path === '/admin') return route.path === '/admin'
  return route.path.startsWith(path)
}
</script>

<template>
  <aside 
    class="flex flex-col border-r border-slate-200 bg-[var(--sa-dark)] transition-all duration-300"
    :class="isCollapsed ? 'w-20' : 'w-72'"
  >
    <!-- Logo Area -->
    <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-black/10">
      <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white font-display font-bold text-lg shadow-sm">
          S
        </div>
        <span v-if="!isCollapsed" class="font-display text-lg font-bold text-white tracking-tight">Smart Adama</span>
      </div>
      
      <!-- Mobile Close -->
      <button 
        class="lg:hidden text-smart-blue-200 hover:text-white"
        @click="$emit('close')"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-6 px-3 custom-scrollbar">
      <div v-for="(group, idx) in navGroups" :key="idx" class="mb-8 last:mb-0">
        <h3 
          v-if="!isCollapsed" 
          class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-wider text-[#D5DEEF]/80"
        >
          {{ group.label }}
        </h3>
        
        <ul class="space-y-1">
          <li v-for="item in group.items" :key="item.path">
            <router-link
              :to="item.path"
              class="group flex items-center rounded-lg px-3 py-2.5 transition-all duration-200"
              :class="isActive(item.path) 
                ? 'bg-[#638ECB] text-white shadow-inner' 
                : 'text-[#D5DEEF] hover:bg-[#8AAEE0]/30 hover:text-white'"
              :title="isCollapsed ? item.name : undefined"
            >
              <component 
                :is="item.icon" 
                class="shrink-0 transition-colors"
                :class="[
                  isCollapsed ? 'mx-auto h-5 w-5' : 'mr-3 h-5 w-5',
                  isActive(item.path) ? 'text-white' : 'text-[#B1C9EF] group-hover:text-white'
                ]" 
                stroke-width="2" 
              />
              <span 
                v-if="!isCollapsed" 
                class="truncate text-sm font-medium"
                :class="isActive(item.path) ? 'font-semibold' : ''"
              >
                {{ item.name }}
              </span>
            </router-link>
          </li>
        </ul>
      </div>
    </nav>
    
    <!-- Footer settings minimal -->
    <div class="border-t border-[#638ECB]/30 p-3">
      <router-link
        to="/dashboard"
        class="group flex items-center rounded-lg px-3 py-2.5 transition-all duration-200 text-[#D5DEEF] hover:bg-[#8AAEE0]/30 hover:text-white"
        title="Exit Admin"
      >
        <LayoutDashboard class="shrink-0 h-5 w-5 text-[#B1C9EF] group-hover:text-white" :class="!isCollapsed ? 'mr-3' : 'mx-auto'" />
        <span v-if="!isCollapsed" class="truncate text-sm font-medium">Exit Admin</span>
      </router-link>
    </div>
  </aside>
</template>

<style scoped>
aside {
  background-color: #395886;
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #638ECB; 
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #8AAEE0; 
}
</style>
