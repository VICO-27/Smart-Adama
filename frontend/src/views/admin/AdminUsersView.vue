<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Search, Plus, X, Users, Activity, Mail } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()

const users = ref<any[]>([])
const loading = ref(true)
const searchQuery = ref('')
const statusFilter = ref('All Statuses')

const currentPage = ref(1)
const lastPage = ref(1)
const totalUsers = ref(0)

const isInviteModalOpen = ref(false)
const inviteForm = ref({ name: '', email: '' })
const isInviting = ref(false)

// Use a simple debounce for search
let searchTimeout: any = null

const loadUsers = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/users', {
      params: {
        status: statusFilter.value,
        search: searchQuery.value,
        page: currentPage.value
      }
    })
    users.value = res.data.data
    currentPage.value = res.data.meta.current_page
    lastPage.value = res.data.meta.last_page
    totalUsers.value = res.data.meta.total
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loading.value = false
  }
}

watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadUsers()
  }, 300)
})

watch(statusFilter, () => {
  currentPage.value = 1
  loadUsers()
})

onMounted(() => {
  loadUsers()
})

const changePage = (page: number) => {
  if (page >= 1 && page <= lastPage.value) {
    currentPage.value = page
    loadUsers()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const handleRowClick = (user: any) => {
  router.push(`/admin/users/${user.id}`)
}

const handleInvite = async () => {
  if (!inviteForm.value.name || !inviteForm.value.email) return
  isInviting.value = true
  try {
    await api.post('/admin/users/invite', inviteForm.value)
    alert('User invited successfully!')
    isInviteModalOpen.value = false
    inviteForm.value = { name: '', email: '' }
    loadUsers()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to invite user')
  } finally {
    isInviting.value = false
  }
}
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Header & Floating Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">User Directory</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-1">Manage platform learners, instructors, and administrators.</p>
      </div>

      <!-- Prominent Apple-Style Floating Search -->
      <div class="w-full md:max-w-xl">
        <div class="bg-white/60 dark:bg-slate-900/60 backdrop-blur-xl border border-black/5 dark:border-white/10 rounded-full px-4 py-3 flex items-center shadow-sm focus-within:ring-2 focus-within:ring-[#3B82F6] transition-all">
          <Search class="h-5 w-5 text-slate-400 mr-3" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by name, email, or ID..."
            class="w-full bg-transparent border-none p-0 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 text-sm font-medium"
          />
          <select
            v-model="statusFilter"
            class="ml-3 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full border-none py-1.5 pl-3 pr-8 text-xs font-semibold focus:ring-0"
          >
            <option>All Statuses</option>
            <option>Active</option>
            <option>Inactive</option>
            <option>Suspended</option>
          </select>
        </div>
      </div>

      <button
        @click="isInviteModalOpen = true"
        class="inline-flex items-center gap-2 rounded-full bg-[#395886] px-5 py-3 text-sm font-semibold text-white shadow-md hover:bg-[#2c446b] hover:shadow-lg transition-all"
      >
        <Plus class="h-4 w-4" />
        Invite User
      </button>
    </div>

    <!-- Bento Grid -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div v-for="n in 8" :key="n" class="animate-pulse bg-white/50 dark:bg-slate-900/50 backdrop-blur-md rounded-[24px] h-[240px] border border-slate-200/50 dark:border-slate-800/50"></div>
    </div>

    <div v-else-if="users.length === 0" class="text-center py-24 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-[32px] border border-slate-200/50 dark:border-slate-800/50">
      <Users class="mx-auto h-12 w-12 text-slate-400 mb-4" />
      <h3 class="text-lg font-medium text-slate-900 dark:text-white">No users found</h3>
      <p class="text-slate-500 mt-1">Try adjusting your search or filters.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <div
        v-for="user in users"
        :key="user.id"
        @click="handleRowClick(user)"
        class="group cursor-pointer bg-gradient-to-br from-[#8AAEE0] to-[#638ECB] dark:from-[#0B1220] dark:to-[#0B1220] backdrop-blur-2xl border-transparent dark:border-slate-800/80 rounded-[24px] p-6 shadow-sm hover:shadow-[0_12px_40px_rgba(99,142,203,0.3)] dark:hover:shadow-[0_12px_40px_rgba(0,0,0,0.4)] hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between"
      >
        <!-- Card Top -->
        <div class="flex items-start justify-between mb-6 gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <img :src="user.avatar" class="w-12 h-12 shrink-0 rounded-full object-cover ring-4 ring-white/30 dark:ring-slate-800/50 shadow-sm" />
            <div class="min-w-0">
              <h3 class="font-semibold text-white truncate transition-colors" :title="user.name">{{ user.name }}</h3>
              <p class="text-xs text-blue-50 dark:text-slate-500 truncate mt-0.5" :title="user.email">{{ user.email }}</p>
            </div>
          </div>
          <span
            class="shrink-0 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-full border border-transparent shadow-sm"
            :class="[
              user.status === 'Active'
                ? 'bg-emerald-500 text-white dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20'
                : 'bg-white/20 text-white dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700'
            ]"
          >
            {{ user.status }}
          </span>
        </div>

        <!-- Card Bottom (Metrics) -->
        <div class="mt-auto pt-5 border-t border-white/20 dark:border-slate-800/50">
          <div class="flex items-end justify-between mb-2">
            <span class="text-xs font-medium text-blue-100 dark:text-slate-500">Overall Progress</span>
            <span class="text-sm font-bold text-white">{{ user.progress }}</span>
          </div>
          <!-- Progress Bar -->
          <div class="h-1.5 w-full bg-black/10 dark:bg-slate-800 rounded-full overflow-hidden shadow-inner">
            <div
              class="h-full rounded-full transition-all duration-1000 ease-out"
              :class="parseFloat(user.progress) >= 100 ? 'bg-[#008A00]' : 'bg-white dark:bg-slate-400'"
              :style="{ width: user.progress }"
            ></div>
          </div>

          <div class="flex items-center gap-4 mt-4 text-[11px] font-medium text-white dark:text-slate-400">
            <div class="flex items-center gap-1.5 bg-black/10 dark:bg-slate-800/50 px-2 py-1 rounded-md shadow-sm border border-transparent dark:border-white/5">
              <Activity class="w-3.5 h-3.5" /> Lvl {{ user.level }}
            </div>
            <div class="flex items-center gap-1.5 bg-black/10 dark:bg-slate-800/50 px-2 py-1 rounded-md shadow-sm border border-transparent dark:border-white/5">
              <Mail class="w-3.5 h-3.5" /> Joined {{ user.registered }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination Controls -->
    <div v-if="!loading && users.length > 0 && lastPage > 1" class="flex justify-center items-center gap-4 mt-8">
      <button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="px-4 py-2 rounded-full text-sm font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
      >
        Previous
      </button>

      <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
        Page {{ currentPage }} of {{ lastPage }} <span class="mx-2">•</span> {{ totalUsers }} Total
      </span>

      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === lastPage"
        class="px-4 py-2 rounded-full text-sm font-semibold bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 shadow-sm border border-slate-200 dark:border-slate-700 disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
      >
        Next
      </button>
    </div>

    <!-- Invite User Modal -->
    <div v-if="isInviteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/30 backdrop-blur-md" @click="isInviteModalOpen = false"></div>
      <div class="relative w-full max-w-md bg-white/90 dark:bg-slate-900/90 backdrop-blur-2xl rounded-[32px] border border-white/20 dark:border-slate-700 shadow-2xl p-8">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">Invite User</h3>
          <button @click="isInviteModalOpen = false" class="text-slate-400 hover:text-slate-600 bg-slate-100 dark:bg-slate-800 p-2 rounded-full transition-colors">
            <X class="h-4 w-4" />
          </button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Full Name</label>
            <input v-model="inviteForm.name" type="text" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-3 px-4 focus:ring-[#3B82F6] dark:text-white" placeholder="Jane Doe" />
          </div>
          <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Email Address</label>
            <input v-model="inviteForm.email" type="email" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 py-3 px-4 focus:ring-[#3B82F6] dark:text-white" placeholder="jane@example.com" />
          </div>
          <button
            @click="handleInvite"
            :disabled="isInviting || !inviteForm.name || !inviteForm.email"
            class="w-full mt-4 rounded-xl bg-[#395886] py-3.5 text-white font-semibold shadow-md hover:bg-[#2c446b] disabled:opacity-50 transition-colors"
          >
            {{ isInviting ? 'Inviting...' : 'Send Invitation' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
