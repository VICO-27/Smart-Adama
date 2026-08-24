<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/admin/PageHeader.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import { ArrowLeft, User, Activity, BookOpen, Edit2, ShieldAlert } from 'lucide-vue-next'
import api from '@/api/client'

const route = useRoute()
const router = useRouter()
const userId = route.params.id

const user = ref<any>(null)
const loading = ref(true)

const loadUser = async () => {
  loading.value = true
  try {
    const res = await api.get(`/admin/users/${userId}`)
    user.value = res.data.user
  } catch (error) {
    console.error('Failed to load user:', error)
    alert('Failed to load user details.')
    router.push('/admin/users')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadUser()
})

const handleManage = () => {
  alert('Manual progress adjustment modal would open here.')
}
</script>

<template>
  <div class="space-y-6">
    <button @click="router.push('/admin/users')" class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-smart-blue-600 transition-colors">
      <ArrowLeft class="h-4 w-4" /> Back to Users
    </button>
    
    <div v-if="loading" class="animate-pulse space-y-6">
      <div class="h-32 bg-white rounded-xl border border-slate-200"></div>
      <div class="h-64 bg-white rounded-xl border border-slate-200"></div>
    </div>
    
    <template v-else-if="user">
      <PageHeader :title="user.name" :description="`User Profile & Learning Analytics`">
        <template #actions>
          <button class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors">
            <Edit2 class="h-4 w-4" /> Edit Profile
          </button>
          <button class="inline-flex items-center gap-2 rounded-lg bg-red-50 text-red-700 px-3 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset ring-red-200 hover:bg-red-100 transition-colors">
            <ShieldAlert class="h-4 w-4" /> Suspend
          </button>
        </template>
      </PageHeader>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Identity -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 text-center">
            <img :src="user.avatar" class="h-24 w-24 rounded-full bg-slate-50 mx-auto ring-4 ring-slate-50 object-cover" />
            <h2 class="mt-4 text-xl font-bold text-slate-900">{{ user.name }}</h2>
            <p class="text-slate-500 text-sm">{{ user.email }}</p>
            
            <div class="mt-4 flex justify-center">
              <StatusBadge :status="user.status" :variant="user.status === 'Active' ? 'success' : 'neutral'" />
            </div>
            
            <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4 text-left">
              <div>
                <p class="text-xs text-slate-400 font-medium">Level</p>
                <p class="text-lg font-semibold text-slate-900">{{ user.level }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-400 font-medium">Total XP</p>
                <p class="text-lg font-semibold text-slate-900">{{ user.xp }}</p>
              </div>
              <div class="col-span-2">
                <p class="text-xs text-slate-400 font-medium">Registered On</p>
                <p class="text-sm font-medium text-slate-900">{{ user.registered }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right: Progress & Activity -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Overall Progress -->
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                <Activity class="h-5 w-5 text-smart-blue-500" /> Study Progress
              </h3>
              <button @click="handleManage" class="text-sm text-smart-blue-600 font-medium hover:underline">
                Manage Progress
              </button>
            </div>
            
            <div class="flex items-center gap-4">
              <div class="flex-1 bg-slate-100 rounded-full h-3 overflow-hidden">
                <div 
                  class="h-3 rounded-full transition-all duration-1000"
                  :class="user.overall_progress >= 100 ? 'bg-emerald-500' : 'bg-amber-500'"
                  :style="{ width: user.overall_progress + '%' }"
                ></div>
              </div>
              <span class="font-bold text-lg" :class="user.overall_progress >= 100 ? 'text-emerald-600' : 'text-amber-600'">
                {{ user.overall_progress }}%
              </span>
            </div>
          </div>
          
          <!-- Recent Activity -->
          <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
              <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                <BookOpen class="h-5 w-5 text-slate-500" /> Recent Quiz Activity
              </h3>
            </div>
            <div class="p-0">
              <div v-if="user.quiz_attempts?.length === 0" class="p-6 text-center text-slate-500 text-sm">
                No quiz attempts yet.
              </div>
              <ul v-else class="divide-y divide-slate-100">
                <li v-for="attempt in user.quiz_attempts" :key="attempt.id" class="px-6 py-4 flex items-center justify-between">
                  <div>
                    <p class="font-medium text-slate-900">{{ attempt.quiz?.title || 'Quiz' }}</p>
                    <p class="text-xs text-slate-500">{{ new Date(attempt.created_at).toLocaleDateString() }}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-semibold" :class="attempt.passed ? 'text-emerald-600' : 'text-red-600'">
                      {{ attempt.score_pct }}%
                    </p>
                    <p class="text-xs text-slate-500">{{ attempt.passed ? 'Passed' : 'Failed' }}</p>
                  </div>
                </li>
              </ul>
            </div>
          </div>
          
        </div>
      </div>
    </template>
  </div>
</template>
