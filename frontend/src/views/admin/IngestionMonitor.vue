<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from 'vue'
import {
  CheckCircle,
  AlertTriangle,
  PlayCircle,
  PauseCircle,
  RefreshCw,
  XCircle,
  Activity
} from 'lucide-vue-next'
import apiClient from '@/api/client'

const props = defineProps<{
  jobId: string | null
}>()

const emit = defineEmits(['close', 'job-complete'])

const job = ref<any>(null)
const tasks = ref<any[]>([])
const terminalLogs = ref<string[]>([])
const terminalContainer = ref<HTMLElement | null>(null)
let eventSource: EventSource | null = null
let fallbackInterval: ReturnType<typeof setInterval> | null = null

const fetchJobDetails = async () => {
  if (!props.jobId) return
  try {
    // Just fetch active jobs or specific job. The SSE streams all active jobs.
    // For simplicity, we can fetch tasks or use SSE for the job
  } catch (error) {
    console.error("Failed fetching job", error)
  }
}

const startMonitoring = () => {
  if (!props.jobId) return
  startPolling()
}

const startPolling = () => {
  // Initial fetch
  fetchActiveJobs()

  fallbackInterval = setInterval(async () => {
    await fetchActiveJobs()
  }, 2000)
}

const fetchActiveJobs = async () => {
  try {
    const { data } = await apiClient.get('/admin/ingestion-jobs/active')
    const activeJob = data.jobs?.find((j: any) => j.id === props.jobId)
    if (activeJob) {
      job.value = activeJob
      if (['completed', 'failed', 'cancelled'].includes(activeJob.status)) {
        if (activeJob.status === 'completed') emit('job-complete')
        stopMonitoring()
      }
    } else if (job.value && job.value.status === 'processing') {
      // If it disappeared from active, it might have completed very fast
      job.value.status = 'completed'
      emit('job-complete')
      stopMonitoring()
    }

    // Always fetch logs if we have a job
    if (job.value) {
      await fetchLogs()
    }
  } catch (e) {
    console.error("Polling failed", e)
  }
}

const fetchLogs = async () => {
  if (!job.value) return
  try {
    const { data } = await apiClient.get(`/admin/ingestion-jobs/${job.value.id}/logs`)
    terminalLogs.value = data.logs || []

    // Auto-scroll to bottom
    setTimeout(() => {
      if (terminalContainer.value) {
        terminalContainer.value.scrollTop = terminalContainer.value.scrollHeight
      }
    }, 50)
  } catch (e) {
    // Ignore 404s if file isn't created yet
  }
}

const stopMonitoring = () => {
  if (eventSource) {
    eventSource.close()
    eventSource = null
  }
  if (fallbackInterval) {
    clearInterval(fallbackInterval)
    fallbackInterval = null
  }
}

onMounted(() => {
  if (props.jobId) startMonitoring()
})

onUnmounted(() => {
  stopMonitoring()
})

const getProgressPercent = () => {
  if (!job.value || !job.value.total_items) return 0
  return Math.round((job.value.completed_items / job.value.total_items) * 100)
}

const stages = ['validation', 'chunking', 'embedding', 'indexing']
const getStageIndex = (stage: string) => stages.indexOf(stage)

const retryJob = async () => {
  if (!job.value) return
  await apiClient.post(`/admin/ingestion-jobs/${job.value.id}/retry`)
  startMonitoring()
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" v-if="jobId">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="emit('close')"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col max-h-full overflow-hidden border border-slate-200">

      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <div class="flex items-center gap-3">
          <Activity class="h-6 w-6 text-smart-blue-600" />
          <h2 class="text-xl font-bold text-slate-900">Ingestion Monitor</h2>
        </div>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-md hover:bg-slate-200">
          <XCircle class="h-6 w-6" />
        </button>
      </div>

      <div class="p-6 overflow-y-auto" v-if="job">

        <!-- Status Bar -->
        <div class="flex items-center justify-between mb-8">
          <div>
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wider mb-1">Status</div>
            <div class="text-2xl font-bold" :class="{
              'text-emerald-600': job.status === 'completed',
              'text-red-600': job.status === 'failed',
              'text-smart-blue-600': job.status === 'processing' || job.status === 'pending'
            }">
              {{ job.status.toUpperCase() }}
            </div>
          </div>
          <div class="text-right">
            <div class="text-sm font-medium text-slate-500 uppercase tracking-wider mb-1">Progress</div>
            <div class="text-2xl font-bold text-slate-800">{{ getProgressPercent() }}%</div>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-slate-100 rounded-full h-3 mb-10 overflow-hidden shadow-inner">
          <div
            class="h-3 rounded-full transition-all duration-700 ease-out"
            :class="job.status === 'failed' ? 'bg-red-500' : 'bg-emerald-500'"
            :style="{ width: `${getProgressPercent()}%` }"
          ></div>
        </div>

        <!-- Stages -->
        <div class="space-y-6">
          <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Pipeline Stages</h3>

          <div class="relative pl-4">
            <div class="absolute left-[19px] top-4 bottom-4 w-0.5 bg-slate-100 -z-10"></div>

            <div v-for="(stage, idx) in stages" :key="stage" class="flex items-start gap-4 mb-6">
              <div class="h-8 w-8 rounded-full flex items-center justify-center bg-white shadow-sm border-2 mt-0.5 z-10 transition-colors"
                :class="{
                  'border-emerald-500': job.current_stage === stage || getStageIndex(job.current_stage) > idx,
                  'border-smart-blue-500 ring-4 ring-smart-blue-50': job.current_stage === stage && job.status === 'processing',
                  'border-red-500': job.current_stage === stage && job.status === 'failed',
                  'border-slate-200': getStageIndex(job.current_stage) < idx
                }"
              >
                <CheckCircle v-if="getStageIndex(job.current_stage) > idx || (job.current_stage === stage && job.status === 'completed')" class="h-4 w-4 text-emerald-500" />
                <RefreshCw v-else-if="job.current_stage === stage && job.status === 'processing'" class="h-4 w-4 text-smart-blue-500 animate-spin" />
                <AlertTriangle v-else-if="job.current_stage === stage && job.status === 'failed'" class="h-4 w-4 text-red-500" />
                <div v-else class="h-2 w-2 rounded-full bg-slate-200"></div>
              </div>
              <div>
                <h4 class="text-base font-semibold text-slate-900 capitalize">{{ stage }}</h4>
                <p class="text-sm text-slate-500 mt-1" v-if="stage === 'embedding'">
                  {{ job.completed_items }} / {{ job.total_items }} vectors generated
                  <span v-if="job.failed_items > 0" class="text-red-500 ml-2">({{ job.failed_items }} failed)</span>
                </p>
                <p class="text-sm text-slate-500 mt-1" v-else-if="job.current_stage === stage && job.status === 'processing'">Processing in progress...</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Error State -->
        <div v-if="job.status === 'failed'" class="mt-8 bg-red-50 rounded-xl p-5 border border-red-100">
          <div class="flex items-start gap-3">
            <AlertTriangle class="h-6 w-6 text-red-600 shrink-0" />
            <div>
              <h4 class="font-semibold text-red-900">Ingestion Failed</h4>
              <p class="text-sm text-red-700 mt-1 mb-4">{{ job.last_error }}</p>
              <button @click="retryJob" class="bg-white border border-red-200 text-red-700 hover:bg-red-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm">
                Retry Failed Operations
              </button>
            </div>
          </div>
        </div>

        <!-- Terminal Log -->
        <div class="mt-8 bg-slate-900 rounded-xl border border-slate-700 overflow-hidden flex flex-col shadow-inner">
          <div class="bg-slate-800 px-4 py-2 border-b border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="flex gap-1.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-500 opacity-80"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-amber-500 opacity-80"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 opacity-80"></div>
              </div>
              <div class="text-[11px] text-slate-400 font-medium ml-2 font-mono tracking-wider uppercase">Worker Terminal</div>
            </div>
            <div class="text-[10px] text-slate-500 font-mono">Live Sync</div>
          </div>
          <div ref="terminalContainer" class="p-4 h-48 overflow-y-auto font-mono text-xs text-emerald-400 leading-relaxed scroll-smooth flex flex-col gap-1">
            <div v-if="terminalLogs.length === 0" class="text-slate-500 italic">Waiting for background worker initialization...</div>
            <div v-for="(log, idx) in terminalLogs" :key="idx" class="whitespace-pre-wrap break-all">{{ log }}</div>
          </div>
        </div>

      </div>

      <div v-else class="p-12 flex flex-col items-center justify-center text-slate-500">
        <RefreshCw class="h-8 w-8 animate-spin mb-4 text-slate-300" />
        <p>Connecting to ingestion stream...</p>
      </div>

    </div>
  </div>
</template>
