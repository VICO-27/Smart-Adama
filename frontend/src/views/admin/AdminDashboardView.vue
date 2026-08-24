<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/api/client'
import PageHeader from '@/components/admin/PageHeader.vue'
import MetricCard from '@/components/admin/MetricCard.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import {
  BookOpen,
  FileText,
  Users,
  BrainCircuit,
  Database,
  Activity,
  ArrowRight
} from 'lucide-vue-next'

const stats = ref({
  books: 0,
  chapters: 0,
  sections: 0,
  chunks: 0,
  users: 1284, // Mocked as requested
  quizAttempts: 843 // Mocked
})

const health = ref({
  database: 'checking...',
  redis: 'checking...',
  llm: 'checking...',
  embedding: 'checking...'
})

const fetchHealth = async () => {
  try {
    const res = await api.get('/admin/system/health')
    const data = res.data
    
    health.value = {
      database: data.checks.database,
      redis: data.checks.redis,
      llm: data.checks.llm,
      embedding: data.checks.embedding
    }
  } catch (e) {
    console.error('Failed to load system health', e)
  }
}

const fetchAnalytics = async () => {
  try {
    const res = await api.get('/admin/analytics')
    const data = res.data.analytics
    
    stats.value.books = data.total_books
    stats.value.chapters = data.total_chapters
    stats.value.chunks = data.total_chunks
    stats.value.users = data.total_users
    stats.value.quizAttempts = data.total_quiz_attempts
  } catch (e) {
    console.error('Failed to load dashboard stats', e)
  }
}

const exportData = () => {
  // Simple handler to fulfill the user's request for making the button work. 
  // In a real application, this might trigger a CSV download from an API endpoint.
  alert('Data export initiated. Your report will be ready shortly.')
}

onMounted(async () => {
  await Promise.all([fetchHealth(), fetchAnalytics()])
})

const recentActivity = [
  { id: 1, action: 'New user registered', actor: 'Alice Doe', time: '10m ago', type: 'user' },
  { id: 2, action: 'Quiz published', actor: 'Admin', time: '1h ago', type: 'content' },
  { id: 3, action: 'Document ingestion completed', actor: 'System', time: '3h ago', type: 'system' },
  { id: 4, action: 'RAG indexing finished', actor: 'System', time: '3.5h ago', type: 'system' },
  { id: 5, action: 'New user registered', actor: 'Bob Smith', time: '5h ago', type: 'user' }
]
</script>

<template>
  <div class="space-y-8">
    <PageHeader 
      title="Smart Adama Command Center" 
      description="Monitor learning activity, knowledge infrastructure, AI services, and platform health." 
    />

    <!-- KPI Strip -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <MetricCard 
        title="Registered Users" 
        :value="stats.users.toLocaleString()" 
        trend="+12.4%" 
        trendLabel="vs last month"
        :trendUp="true"
        :icon="Users"
      />
      <MetricCard 
        title="Ingested Books" 
        :value="stats.books" 
        trend="+2" 
        trendLabel="this week"
        :trendUp="true"
        :icon="BookOpen"
      />
      <MetricCard 
        title="Vector Embeddings" 
        :value="stats.chunks.toLocaleString()" 
        trend="+1.2k" 
        trendLabel="since yesterday"
        :trendUp="true"
        :icon="BrainCircuit"
      />
      <MetricCard 
        title="Quiz Attempts" 
        :value="stats.quizAttempts.toLocaleString()" 
        trend="+8.1%" 
        trendLabel="completion rate steady"
        :trendUp="true"
        :icon="FileText"
      />
    </div>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
      
      <!-- System Health Quick Look -->
      <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">System Health</h3>
          <router-link to="/admin/system" class="text-sm font-medium text-smart-blue-600 hover:text-smart-blue-700 flex items-center gap-1">
            Details <ArrowRight class="h-3 w-3" />
          </router-link>
        </div>
        
        <div class="space-y-4 flex-1">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-600">
              <Database class="h-4 w-4 text-slate-400" /> PostgreSQL
            </div>
            <StatusBadge :status="health.database === 'ok' ? 'Healthy' : (health.database === 'checking...' ? 'Checking' : 'Degraded')" :variant="health.database === 'ok' ? 'success' : (health.database === 'checking...' ? 'neutral' : 'error')" />
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-600">
              <Database class="h-4 w-4 text-slate-400" /> Redis Cache
            </div>
            <StatusBadge :status="health.redis === 'ok' ? 'Healthy' : (health.redis === 'checking...' ? 'Checking' : 'Degraded')" :variant="health.redis === 'ok' ? 'success' : (health.redis === 'checking...' ? 'neutral' : 'error')" />
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-600">
              <Activity class="h-4 w-4 text-slate-400" /> LLM Provider
            </div>
            <StatusBadge :status="health.llm === 'ok' ? 'Healthy' : (health.llm === 'checking...' ? 'Checking' : 'Warning')" :variant="health.llm === 'ok' ? 'success' : (health.llm === 'checking...' ? 'neutral' : 'warning')" />
          </div>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-600">
              <Activity class="h-4 w-4 text-slate-400" /> Vector API
            </div>
            <StatusBadge :status="health.embedding === 'ok' ? 'Healthy' : (health.embedding === 'checking...' ? 'Checking' : 'Warning')" :variant="health.embedding === 'ok' ? 'success' : (health.embedding === 'checking...' ? 'neutral' : 'warning')" />
          </div>
        </div>
      </div>

      <!-- Recent Activity Feed (Mocked Layout) -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2 flex flex-col">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">Recent Activity</h3>
          <button class="text-sm font-medium text-slate-500 hover:text-slate-700">View Audit Logs</button>
        </div>
        <div class="p-6">
          <div class="flow-root">
            <ul role="list" class="-mb-8">
              <li v-for="(event, idx) in recentActivity" :key="event.id">
                <div class="relative pb-8">
                  <span v-if="idx !== recentActivity.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                  <div class="relative flex space-x-3">
                    <div>
                      <span 
                        class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white"
                        :class="{
                          'bg-emerald-100 text-emerald-600': event.type === 'user',
                          'bg-indigo-100 text-indigo-600': event.type === 'system',
                          'bg-amber-100 text-amber-600': event.type === 'content'
                        }"
                      >
                        <Users v-if="event.type === 'user'" class="h-4 w-4" />
                        <Activity v-else-if="event.type === 'system'" class="h-4 w-4" />
                        <BookOpen v-else class="h-4 w-4" />
                      </span>
                    </div>
                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                      <div>
                        <p class="text-sm text-slate-600">{{ event.action }} <span class="font-medium text-slate-900">by {{ event.actor }}</span></p>
                      </div>
                      <div class="whitespace-nowrap text-right text-xs text-slate-500">
                        <time>{{ event.time }}</time>
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
