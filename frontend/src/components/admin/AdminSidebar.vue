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
    class="flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 transition-all duration-300"
    :class="isCollapsed ? 'w-20' : 'w-72'"
  >
    <!-- Logo Area -->
    <div class="flex h-16 shrink-0 items-center justify-between px-6 border-b border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-3 overflow-hidden whitespace-nowrap">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500 text-white font-display font-bold text-lg shadow-sm">
          S
        </div>
        <span v-if="!isCollapsed" class="font-display text-lg font-bold text-slate-900 dark:text-white tracking-tight">Smart Adama</span>
      </div>

      <!-- Mobile Close -->
      <button
        class="lg:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white"
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
          class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400"
        >
          {{ group.label }}
        </h3>

        <ul class="space-y-1">
          <li v-for="item in group.items" :key="item.path">
            <router-link
              :to="item.path"
              class="group flex items-center rounded-lg px-3 py-2.5 transition-all duration-200"
              :class="isActive(item.path)
                ? 'bg-[#395886] text-white shadow-sm'
                : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100'"
              :title="isCollapsed ? item.name : undefined"
            >
              <component
                :is="item.icon"
                class="shrink-0 transition-colors"
                :class="[
                  isCollapsed ? 'mx-auto h-5 w-5' : 'mr-3 h-5 w-5',
                  isActive(item.path) ? 'text-white' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-200'
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
    <div class="border-t border-slate-100 dark:border-slate-800 p-3">
      <router-link
        to="/dashboard"
        class="group flex items-center rounded-lg px-3 py-2.5 transition-all duration-200 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100"
        title="Exit Admin"
      >
        <LayoutDashboard class="shrink-0 h-5 w-5 text-slate-400 dark:text-slate-500 group-hover:text-slate-600 dark:group-hover:text-slate-200" :class="!isCollapsed ? 'mr-3' : 'mx-auto'" />
        <span v-if="!isCollapsed" class="truncate text-sm font-medium">Exit Admin</span>
      </router-link>
    </div>
  </aside>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; /* slate-300 */
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #94a3b8; /* slate-400 */
}

/* Dark mode scrollbar */
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb {
  background: #334155; /* slate-700 */
}
:global(.dark) .custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #475569; /* slate-600 */
}
</style>
