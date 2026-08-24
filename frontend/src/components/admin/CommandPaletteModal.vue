<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAdminUIStore } from '@/stores/admin-ui'
import { Search, X, LayoutDashboard, FileText, ListChecks, Users, Cpu, Activity } from 'lucide-vue-next'

const adminUI = useAdminUIStore()
const router = useRouter()
const searchInput = ref<HTMLInputElement | null>(null)
const query = ref('')

const commands = [
  { name: 'Go to Dashboard', path: '/admin', icon: LayoutDashboard, category: 'Navigation' },
  { name: 'Manage Documents', path: '/admin/documents', icon: FileText, category: 'Navigation' },
  { name: 'Manage Quizzes', path: '/admin/quizzes', icon: ListChecks, category: 'Navigation' },
  { name: 'Manage Users', path: '/admin/users', icon: Users, category: 'Navigation' },
  { name: 'AI & RAG Settings', path: '/admin/ai-settings', icon: Cpu, category: 'Navigation' },
  { name: 'System Health', path: '/admin/system', icon: Activity, category: 'Navigation' },
]

const filteredCommands = ref([...commands])

watch(query, (val) => {
  if (!val.trim()) {
    filteredCommands.value = commands
    return
  }
  const q = val.toLowerCase()
  filteredCommands.value = commands.filter(c => c.name.toLowerCase().includes(q))
})

const executeCommand = (cmd: any) => {
  router.push(cmd.path)
  adminUI.toggleSearch()
}

watch(() => adminUI.isSearchOpen, (isOpen) => {
  if (isOpen) {
    query.value = ''
    setTimeout(() => searchInput.value?.focus(), 50)
  }
})

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
    e.preventDefault()
    adminUI.toggleSearch()
  }
  if (e.key === 'Escape' && adminUI.isSearchOpen) {
    adminUI.toggleSearch()
  }
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))
</script>

<template>
  <div v-if="adminUI.isSearchOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 px-4 pb-20 text-center sm:block sm:p-0">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" @click="adminUI.toggleSearch"></div>

    <div class="inline-block w-full max-w-2xl transform overflow-hidden rounded-xl bg-white text-left align-middle shadow-2xl transition-all sm:my-8 opacity-100 scale-100">
      <div class="relative flex items-center border-b border-slate-200 px-4">
        <Search class="h-5 w-5 text-slate-400" />
        <input 
          ref="searchInput"
          v-model="query"
          type="text" 
          class="h-14 w-full border-0 bg-transparent pl-4 pr-4 text-slate-900 placeholder:text-slate-400 focus:ring-0 sm:text-sm" 
          placeholder="Search for commands, documents, or users..." 
        />
        <button @click="adminUI.toggleSearch" class="p-2 text-slate-400 hover:text-slate-600 rounded-lg bg-slate-50 hover:bg-slate-100 transition">
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto p-2">
        <div v-if="filteredCommands.length > 0">
          <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Navigation</div>
          <ul class="space-y-1">
            <li v-for="cmd in filteredCommands" :key="cmd.path">
              <button 
                @click="executeCommand(cmd)"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-smart-blue-50 hover:text-smart-blue-700 transition"
              >
                <component :is="cmd.icon" class="h-5 w-5 text-slate-400" />
                {{ cmd.name }}
              </button>
            </li>
          </ul>
        </div>
        
        <div v-else class="py-14 px-6 text-center text-sm sm:px-14">
          <Search class="mx-auto h-6 w-6 text-slate-400" />
          <p class="mt-4 font-semibold text-slate-900">No results found</p>
          <p class="mt-2 text-slate-500">We couldn't find anything matching your search. Try again with a different term.</p>
        </div>
      </div>
      
      <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 text-xs text-slate-500 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1.5 font-sans font-medium text-slate-400">↑↓</kbd> to navigate</span>
          <span class="flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1.5 font-sans font-medium text-slate-400">↵</kbd> to select</span>
        </div>
        <span class="flex items-center gap-1"><kbd class="rounded border border-slate-200 bg-white px-1.5 font-sans font-medium text-slate-400">esc</kbd> to close</span>
      </div>
    </div>
  </div>
</template>
