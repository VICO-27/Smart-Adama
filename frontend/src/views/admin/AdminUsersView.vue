<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/admin/PageHeader.vue'
import DataTable from '@/components/admin/DataTable.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import { Download, Plus, X } from 'lucide-vue-next'
import api from '@/api/client'

const router = useRouter()
const columns = [
  { key: 'name', label: 'User' },
  { key: 'registered', label: 'Registered' },
  { key: 'progress', label: 'Study Progress' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' as const }
]

const users = ref<any[]>([])
const loading = ref(true)
const statusFilter = ref('All Statuses')

const isInviteModalOpen = ref(false)
const inviteForm = ref({ name: '', email: '' })
const isInviting = ref(false)

const loadUsers = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/users', {
      params: { status: statusFilter.value }
    })
    users.value = res.data.data
  } catch (error) {
    console.error('Failed to load users:', error)
  } finally {
    loading.value = false
  }
}

watch(statusFilter, () => {
  loadUsers()
})

onMounted(() => {
  loadUsers()
})

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
  <div class="space-y-6">
    <PageHeader title="User Management" description="View and manage registered learners, track their progress, and administer accounts.">
      <template #actions>
        <button class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
          <Download class="h-4 w-4" />
          Export
        </button>
        <button 
          @click="isInviteModalOpen = true"
          class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Invite User
        </button>
      </template>
    </PageHeader>

    <DataTable 
      :columns="columns" 
      :data="users" 
      :loading="loading"
      searchPlaceholder="Search users by name or email..."
      @row-click="handleRowClick"
    >
      <template #toolbar-actions>
        <select v-model="statusFilter" class="rounded-lg border-0 py-1.5 pl-3 pr-8 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-smart-blue-600">
          <option>All Statuses</option>
          <option>Active</option>
          <option>Inactive</option>
          <option>Suspended</option>
        </select>
      </template>
      
      <template #name="{ row }">
        <div class="flex items-center gap-3">
          <img :src="row.avatar" alt="" class="h-8 w-8 rounded-full bg-slate-50" />
          <div>
            <div class="font-medium text-slate-900">{{ row.name }}</div>
            <div class="text-xs text-slate-500">{{ row.email }}</div>
          </div>
        </div>
      </template>

      <template #progress="{ row }">
        <div class="flex items-center gap-3">
          <div class="w-24 bg-slate-200 rounded-full h-1.5 overflow-hidden">
            <div 
              class="h-1.5 rounded-full" 
              :class="parseFloat(row.progress) >= 100 ? 'bg-emerald-500' : 'bg-amber-500'"
              :style="{ width: row.progress }"
            ></div>
          </div>
          <span class="text-xs font-medium" :class="parseFloat(row.progress) >= 100 ? 'text-emerald-600' : 'text-amber-600'">{{ row.progress }}</span>
        </div>
      </template>

      <template #status="{ row }">
        <StatusBadge 
          :status="row.status" 
          :variant="row.status === 'Active' ? 'success' : (row.status === 'Suspended' ? 'error' : 'neutral')" 
        />
      </template>

      <template #actions="{ row }">
        <button class="text-slate-400 hover:text-smart-blue-600 font-medium text-sm transition-colors" @click.stop="handleRowClick(row)">
          Manage
        </button>
      </template>
    </DataTable>

    <!-- Invite User Modal -->
    <div v-if="isInviteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="isInviteModalOpen = false"></div>
      <div class="relative w-full max-w-md bg-white rounded-xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-5">
          <h3 class="text-lg font-semibold text-slate-900">Invite New User</h3>
          <button @click="isInviteModalOpen = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-5 w-5" />
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
            <input v-model="inviteForm.name" type="text" class="w-full rounded-lg border-slate-300 py-2" placeholder="Jane Doe" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
            <input v-model="inviteForm.email" type="email" class="w-full rounded-lg border-slate-300 py-2" placeholder="jane@example.com" />
          </div>
          <button 
            @click="handleInvite" 
            :disabled="isInviting || !inviteForm.name || !inviteForm.email"
            class="w-full rounded-lg bg-emerald-600 py-2 text-white font-semibold hover:bg-emerald-700 disabled:opacity-50"
          >
            {{ isInviting ? 'Inviting...' : 'Send Invitation' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
