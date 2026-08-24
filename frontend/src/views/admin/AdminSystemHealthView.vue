<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/admin/PageHeader.vue'
import StatusBadge from '@/components/admin/StatusBadge.vue'
import api from '@/api/client'
import { Database, Activity, RefreshCw, Cpu, BrainCircuit, Box, Server } from 'lucide-vue-next'

const loading = ref(true)
const health = ref<any>(null)
const error = ref('')

async function fetchHealth() {
  loading.value = true
  try {
    const res = await api.get('/admin/system/health')
    health.value = res.data
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Failed to load system health'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchHealth()
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="System Health" 
      description="Live operational status of the Smart Adama infrastructure and AI services."
    >
      <template #actions>
        <button 
          @click="fetchHealth"
          class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition-colors"
          :disabled="loading"
        >
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': loading }" />
          Refresh
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex flex-col items-center justify-center p-12 text-slate-400">
      <RefreshCw class="h-8 w-8 animate-spin mb-4" />
      <p class="text-sm font-medium">Scanning infrastructure...</p>
    </div>

    <div v-else-if="error" class="rounded-xl bg-red-50 p-6 border border-red-200 text-red-700">
      <h3 class="font-semibold text-red-900 text-lg mb-2">Connection Failed</h3>
      <p>{{ error }}</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Core Infrastructure -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center gap-2">
          <Server class="h-5 w-5 text-slate-500" />
          <h3 class="font-semibold text-slate-900">Core Infrastructure</h3>
        </div>
        <div class="p-0">
          <ul class="divide-y divide-slate-100">
            <li class="px-6 py-5 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Database class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">PostgreSQL Database</h4>
                  <p class="text-xs text-slate-500 mt-0.5">Primary application data</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.database === 'ok' ? 'Healthy' : 'Offline'" :variant="health.checks.database === 'ok' ? 'success' : 'error'" />
            </li>
            
            <li class="px-6 py-5 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Box class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">Redis Cache</h4>
                  <p class="text-xs text-slate-500 mt-0.5">Session and queue backend</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.redis === 'ok' ? 'Healthy' : 'Offline'" :variant="health.checks.redis === 'ok' ? 'success' : 'error'" />
            </li>
            
            <li class="px-6 py-5 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Activity class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">Job Queue</h4>
                  <p class="text-xs text-slate-500 mt-0.5">Background processing</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.queue === 'ok' ? 'Healthy' : 'Degraded'" :variant="health.checks.queue === 'ok' ? 'success' : 'warning'" />
            </li>
            
            <li class="px-6 py-5 flex items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                  <Search class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">pgvector Search</h4>
                  <p class="text-xs text-slate-500 mt-0.5">Semantic retrieval engine</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.vector_search === 'ok' ? 'Healthy' : 'Offline'" :variant="health.checks.vector_search === 'ok' ? 'success' : 'error'" />
            </li>
          </ul>
        </div>
      </div>

      <!-- AI Infrastructure -->
      <div class="rounded-xl border border-slate-200 bg-white shadow-sm flex flex-col overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center gap-2">
          <BrainCircuit class="h-5 w-5 text-slate-500" />
          <h3 class="font-semibold text-slate-900">AI Infrastructure</h3>
        </div>
        <div class="p-0">
          <ul class="divide-y divide-slate-100">
            <li class="px-6 py-5 flex items-start justify-between flex-col sm:flex-row gap-4">
              <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-smart-blue-50 text-smart-blue-600 shrink-0">
                  <Cpu class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">LLM Provider</h4>
                  <p class="text-xs text-slate-500 mt-0.5 font-mono bg-slate-100 px-1.5 py-0.5 rounded inline-block">{{ health.providers.llm }}</p>
                  <p v-if="health.checks.llm_error" class="text-xs text-red-600 mt-2">{{ health.checks.llm_error }}</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.llm === 'ok' ? 'Healthy' : 'Failed'" :variant="health.checks.llm === 'ok' ? 'success' : 'error'" class="mt-1 sm:mt-0" />
            </li>
            
            <li class="px-6 py-5 flex items-start justify-between flex-col sm:flex-row gap-4">
              <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-smart-blue-50 text-smart-blue-600 shrink-0">
                  <BrainCircuit class="h-5 w-5" />
                </div>
                <div>
                  <h4 class="text-sm font-medium text-slate-900">Embedding Provider</h4>
                  <p class="text-xs text-slate-500 mt-0.5 font-mono bg-slate-100 px-1.5 py-0.5 rounded inline-block">{{ health.providers.embedding }}</p>
                  <p v-if="health.checks.embedding_error" class="text-xs text-red-600 mt-2">{{ health.checks.embedding_error }}</p>
                </div>
              </div>
              <StatusBadge :status="health.checks.embedding === 'ok' ? 'Healthy' : 'Failed'" :variant="health.checks.embedding === 'ok' ? 'success' : 'error'" class="mt-1 sm:mt-0" />
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>
