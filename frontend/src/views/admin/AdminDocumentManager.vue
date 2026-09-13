<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onUnmounted } from 'vue'
import apiClient from '@/api/client'
import { useConfirm } from '@/composables/useConfirm'
import {
  FileText,
  FileEdit,
  UploadCloud,
  CheckCircle,
  AlertTriangle,
  Clock,
  ChevronRight,
  RefreshCw,
  Info,
  Terminal
} from 'lucide-vue-next'

const { confirm } = useConfirm()

// Types
interface Document {
  id: string
  title: string
  status: string
  file_size: string
  total_pages: number
  total_chunks: number
  created_at: string
  source_type?: string
  processing_metadata?: any
}

interface ProgressMetrics {
  total_pages: number
  extracted_pages: number
  cleaned_pages: number
  embedded_pages: number
  total_chunks: number
  embedded_chunks: number
}

interface DocumentProgress {
  id: string
  status: string
  progress_percent: number
  metrics: ProgressMetrics
  metadata: any
}

// State
const documents = ref<Document[]>([])
const isLoading = ref(true)
const isUploading = ref(false)
const selectedDocument = ref<Document | null>(null)
const liveProgress = ref<DocumentProgress | null>(null)
const uploadError = ref<string | null>(null)

// File Upload ref
const fileInput = ref<HTMLInputElement | null>(null)

// Polling interval
let pollInterval: ReturnType<typeof setInterval> | null = null

// Logs container ref for auto-scrolling
const logsContainer = ref<HTMLElement | null>(null)

watch(() => liveProgress.value?.metadata?.logs, async () => {
  if (logsContainer.value) {
    await nextTick()
    logsContainer.value.scrollTop = logsContainer.value.scrollHeight
  }
}, { deep: true })

const fetchDocuments = async () => {
  try {
    const { data } = await apiClient.get('/admin/rag/documents')
    documents.value = data.documents
  } catch (error) {
    console.error('Failed to fetch documents:', error)
  } finally {
    isLoading.value = false
  }
}

const triggerUpload = () => {
  fileInput.value?.click()
}

const handleFileUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files?.length) return

  const file = target.files[0]
  if (file.type !== 'application/pdf') {
    uploadError.value = 'Please select a valid PDF file.'
    return
  }

  isUploading.value = true
  uploadError.value = null

  const formData = new FormData()
  formData.append('file', file)
  formData.append('title', file.name.replace('.pdf', ''))

  try {
    const { data } = await apiClient.post('/admin/books', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // Select the new document to start polling
    await fetchDocuments()
    const newDoc = documents.value.find(d => d.id === data.book.id)
    if (newDoc) {
      selectDocument(newDoc)
    }
  } catch (error: any) {
    console.error('Upload failed:', error)
    uploadError.value = error.response?.data?.message || 'Failed to upload document.'
  } finally {
    isUploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const selectDocument = async (doc: Document) => {
  selectedDocument.value = doc
  liveProgress.value = null

  if (pollInterval) clearInterval(pollInterval)

  // Start polling if document is processing
  if (!['draft', 'ingested', 'ready', 'failed', 'ocr_required'].includes(doc.status)) {
    await fetchProgress(doc.id)
    pollInterval = setInterval(() => fetchProgress(doc.id), 3000)
  } else {
    // Just fetch once for stats
    await fetchProgress(doc.id)
  }
}

const fetchProgress = async (id: string) => {
  try {
    const { data } = await apiClient.get(`/admin/rag/documents/${id}/progress`)
    liveProgress.value = data

    // Stop polling if complete or failed
    if (['ingested', 'ready', 'failed', 'ocr_required'].includes(data.status)) {
      if (pollInterval) clearInterval(pollInterval)
      fetchDocuments() // Refresh list
    }
  } catch (error) {
    console.error('Failed to fetch progress:', error)
    if (pollInterval) clearInterval(pollInterval)
  }
}

const getStatusColor = (status: string) => {
  switch (status) {
    case 'ingested':
    case 'ready':
      return 'bg-emerald-100 text-emerald-700 border-emerald-200'
    case 'failed':
    case 'ocr_required':
      return 'bg-red-100 text-red-700 border-red-200'
    case 'draft':
      return 'bg-slate-100 text-slate-700 border-slate-200'
    default:
      return 'bg-blue-100 text-blue-700 border-blue-200'
  }
}

const getStatusLabel = (status: string) => {
  return status.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
}

const closeInspector = () => {
  selectedDocument.value = null
  liveProgress.value = null
  if (pollInterval) clearInterval(pollInterval)
}

const deleteDocument = async (id: string) => {
  const isConfirmed = await confirm({
    title: 'Delete Document',
    message: 'Are you sure you want to delete this document? This will also stop any ongoing ingestion.',
    confirmText: 'Delete',
    confirmColor: 'red'
  })
  if (!isConfirmed) return

  try {
    // We reuse the Books API for deleting
    await apiClient.delete(`/admin/books/${id}`)
    if (selectedDocument.value?.id === id) {
      closeInspector()
    }
    await fetchDocuments()
  } catch (error) {
    console.error('Failed to delete document:', error)
    alert('Failed to delete document.')
  }
}

onMounted(() => {
  fetchDocuments()
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <div class="h-[calc(100dvh-8rem)] flex flex-col xl:flex-row gap-6 pb-6">

    <!-- Left Pane: Document List -->
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">

      <!-- Header -->
      <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between bg-slate-50 gap-4">
        <div>
          <h2 class="text-lg font-semibold text-slate-900">Document Pipeline</h2>
          <p class="text-sm text-slate-500 mt-1">Upload PDFs to automatically extract, chunk, and embed them.</p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            :to="{ name: 'admin.manual-authoring' }"
            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition"
          >
            <FileText class="h-4 w-4" />
            Manual
          </router-link>

          <button
            @click="triggerUpload"
            :disabled="isUploading"
            class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 disabled:opacity-50 transition"
          >
            <UploadCloud v-if="!isUploading" class="h-4 w-4" />
            <RefreshCw v-else class="h-4 w-4 animate-spin" />
            {{ isUploading ? 'Uploading...' : 'Upload PDF' }}
          </button>
        </div>
        <input
          type="file"
          ref="fileInput"
          class="hidden"
          accept="application/pdf"
          @change="handleFileUpload"
        />
      </div>

      <!-- Error State -->
      <div v-if="uploadError" class="p-4 bg-red-50 border-b border-red-100 flex items-center gap-3 text-sm text-red-700">
        <AlertTriangle class="h-4 w-4" />
        {{ uploadError }}
      </div>

      <!-- List -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="isLoading" class="p-12 text-center text-slate-500">
          <RefreshCw class="h-8 w-8 animate-spin mx-auto text-slate-300" />
          <p class="mt-4 text-sm font-medium">Loading documents...</p>
        </div>

        <ul v-else-if="documents.length > 0" class="divide-y divide-slate-100">
          <li v-for="doc in documents" :key="doc.id">
            <button
              @click="selectDocument(doc)"
              class="w-full text-left transition-colors p-4 sm:px-6 flex items-center justify-between group"
              :class="selectedDocument?.id === doc.id ? 'bg-smart-blue-50/50 hover:bg-smart-blue-50' : 'hover:bg-slate-50'"
            >
              <div class="flex items-start gap-4">
                <div
                  class="h-10 w-10 rounded-xl border flex items-center justify-center flex-shrink-0 transition-colors"
                  :class="selectedDocument?.id === doc.id ? 'bg-white border-smart-blue-200 text-smart-blue-600 shadow-sm' : 'bg-slate-50 border-slate-200 text-slate-500 group-hover:bg-white'"
                >
                  <FileText class="h-5 w-5" />
                </div>
                <div>
                  <h3 class="text-sm font-semibold line-clamp-1" :class="selectedDocument?.id === doc.id ? 'text-smart-blue-900' : 'text-slate-900'">{{ doc.title }}</h3>
                  <div class="mt-1 flex items-center gap-2.5 text-xs font-medium text-slate-500">
                    <span class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-600">{{ doc.total_pages }} Pages</span>
                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                    <span>{{ doc.total_chunks }} Chunks</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-4 shrink-0">
                <span :class="[
                  'inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium ring-1 ring-inset',
                  getStatusColor(doc.status)
                ]">
                  <RefreshCw v-if="!['draft', 'ingested', 'ready', 'failed', 'ocr_required'].includes(doc.status)" class="h-3 w-3 mr-1.5 animate-spin" />
                  {{ getStatusLabel(doc.status) }}
                </span>

                <router-link
                  v-if="doc.source_type === 'manual'"
                  :to="{ name: 'admin.manual-authoring', params: { id: doc.id } }"
                  @click.stop
                  class="p-1.5 rounded hover:bg-emerald-50 text-slate-300 hover:text-emerald-500 transition-colors opacity-0 group-hover:opacity-100 hidden sm:block"
                  title="Edit Document"
                >
                  <FileEdit class="w-4 h-4" />
                </router-link>

                <button
                  @click.stop="deleteDocument(doc.id)"
                  class="p-1.5 rounded hover:bg-red-50 text-slate-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 hidden sm:block"
                  title="Delete Document"
                >
                  <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>

                <ChevronRight class="h-5 w-5 transition hidden sm:block" :class="selectedDocument?.id === doc.id ? 'text-smart-blue-400' : 'text-slate-300 group-hover:text-slate-400'" />
              </div>
            </button>
          </li>
        </ul>

        <div v-else class="flex flex-col items-center justify-center py-20 px-4">
          <div class="h-16 w-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
            <UploadCloud class="h-8 w-8 text-slate-400" />
          </div>
          <h3 class="text-base font-semibold text-slate-900">No documents yet</h3>
          <p class="text-sm text-slate-500 mt-1 text-center max-w-sm">Upload a PDF manuscript to begin the RAG ingestion pipeline.</p>
        </div>
      </div>
    </div>

    <!-- Right Pane: Live Inspector -->
    <div
      v-if="selectedDocument"
      class="w-full xl:w-[450px] 2xl:w-[500px] flex-shrink-0 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm flex flex-col"
    >
      <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900 truncate pr-4">Pipeline Inspector</h3>
        <button @click="closeInspector" class="rounded p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition-colors">
          <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
          </svg>
        </button>
      </div>

      <div class="p-6 overflow-y-auto flex-1 bg-white space-y-8">

        <!-- Header Info -->
        <div>
          <h4 class="text-lg font-bold text-slate-900 break-words">{{ selectedDocument.title }}</h4>
          <p class="text-sm text-slate-500 mt-1.5 flex items-center gap-2">
            <Clock class="h-4 w-4" />
            Started {{ new Date(selectedDocument.created_at).toLocaleString() }}
          </p>
        </div>

        <!-- Global Progress Bar -->
        <div v-if="liveProgress" class="space-y-2">
          <div class="flex items-center justify-between text-sm font-medium">
            <span class="text-slate-700">Overall Progress</span>
            <span class="text-emerald-600">
              {{ liveProgress.progress_percent }}%
            </span>
          </div>
          <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
            <div
              class="h-2.5 rounded-full transition-all duration-500 ease-out"
              :class="[
                liveProgress.status === 'failed' || liveProgress.status === 'ocr_required' ? 'bg-red-500' : 'bg-emerald-500'
              ]"
              :style="{ width: `${liveProgress.progress_percent}%` }"
            ></div>
          </div>
        </div>

        <!-- Pipeline Stages -->
        <div v-if="liveProgress" class="space-y-4">
          <h5 class="text-xs font-bold uppercase tracking-wider text-slate-400">Pipeline Stages</h5>

          <div class="relative pl-3">
            <!-- Vertical Line -->
            <div class="absolute left-[15px] top-4 bottom-4 w-px bg-slate-200 -z-10"></div>

            <ul class="space-y-6">

              <!-- 1. Extraction -->
              <li class="flex items-start gap-4">
                <div class="h-6 w-6 rounded-full flex items-center justify-center bg-white shadow-sm ring-1 ring-slate-200 mt-0.5">
                  <CheckCircle v-if="liveProgress.metrics.extracted_pages > 0" class="h-4 w-4 text-emerald-500" />
                  <div v-else-if="liveProgress.status === 'extracting'" class="h-2.5 w-2.5 rounded-full bg-smart-blue-500 animate-pulse"></div>
                  <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-slate-900">Page Extraction</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ liveProgress.metrics.extracted_pages }} / {{ liveProgress.metrics.total_pages || '?' }} pages extracted</p>
                </div>
              </li>

              <!-- 2. Normalization -->
              <li class="flex items-start gap-4">
                <div class="h-6 w-6 rounded-full flex items-center justify-center bg-white shadow-sm ring-1 ring-slate-200 mt-0.5">
                  <CheckCircle v-if="liveProgress.metrics.cleaned_pages === liveProgress.metrics.total_pages && liveProgress.metrics.total_pages > 0" class="h-4 w-4 text-emerald-500" />
                  <div v-else-if="liveProgress.status === 'extracting' && liveProgress.metrics.cleaned_pages > 0" class="h-2.5 w-2.5 rounded-full bg-smart-blue-500 animate-pulse"></div>
                  <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-slate-900">Text Normalization</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ liveProgress.metrics.cleaned_pages }} / {{ liveProgress.metrics.total_pages || '?' }} pages cleaned</p>
                </div>
              </li>

              <!-- 3. Semantic Chunking -->
              <li class="flex items-start gap-4">
                <div class="h-6 w-6 rounded-full flex items-center justify-center bg-white shadow-sm ring-1 ring-slate-200 mt-0.5">
                  <CheckCircle v-if="liveProgress.metrics.total_chunks > 0 && liveProgress.status !== 'ingesting'" class="h-4 w-4 text-emerald-500" />
                  <div v-else-if="liveProgress.status === 'ready_for_chunking' || (liveProgress.status === 'ingesting' && liveProgress.metrics.total_chunks === 0)" class="h-2.5 w-2.5 rounded-full bg-smart-blue-500 animate-pulse"></div>
                  <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-slate-900">Semantic Chunking</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ liveProgress.metrics.total_chunks }} chunks generated</p>
                </div>
              </li>

              <!-- 4. Local Embedding -->
              <li class="flex items-start gap-4">
                <div class="h-6 w-6 rounded-full flex items-center justify-center bg-white shadow-sm ring-1 ring-slate-200 mt-0.5">
                  <CheckCircle v-if="['ingested', 'ready', 'published'].includes(liveProgress.status)" class="h-4 w-4 text-emerald-500" />
                  <div v-else-if="liveProgress.status === 'ingesting'" class="h-2.5 w-2.5 rounded-full bg-smart-blue-500 animate-pulse"></div>
                  <div v-else class="h-2 w-2 rounded-full bg-slate-300"></div>
                </div>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-slate-900">Vector Embedding</p>
                  <p class="text-xs text-slate-500 mt-0.5">{{ liveProgress.metrics.embedded_chunks }} / {{ liveProgress.metrics.total_chunks || '?' }} vectors stored</p>
                </div>
              </li>

            </ul>
          </div>
        </div>

        <!-- Warning / Error Inspector -->
        <div v-if="liveProgress?.metadata" class="space-y-3 pt-4 border-t border-slate-100">

          <div v-if="liveProgress.status === 'failed' || liveProgress.status === 'ocr_required'" class="rounded-lg bg-red-50 p-4 border border-red-200">
            <div class="flex items-start">
              <AlertTriangle class="h-5 w-5 text-red-600 mt-0.5" />
              <div class="ml-3">
                <h3 class="text-sm font-semibold text-red-800">Pipeline Halted</h3>
                <p class="mt-1 text-sm text-red-700">{{ liveProgress.metadata.error || 'The document requires OCR or could not be parsed.' }}</p>
              </div>
            </div>
          </div>

          <div v-if="liveProgress.metadata.suspicious_pages" class="rounded-lg bg-amber-50 p-4 border border-amber-200">
            <div class="flex items-start">
              <Info class="h-5 w-5 text-amber-600 mt-0.5" />
              <div class="ml-3">
                <h3 class="text-sm font-semibold text-amber-800">Extraction Warnings</h3>
                <p class="mt-1 text-sm text-amber-700">
                  {{ liveProgress.metadata.suspicious_pages }} pages were flagged as suspicious (e.g. garbled fonts or heavy layout issues).
                </p>
              </div>
            </div>
          </div>

          <div v-if="liveProgress.metadata.duration_seconds" class="flex justify-between text-xs text-slate-500">
            <span>Processing Time</span>
            <span class="font-medium text-slate-700">{{ liveProgress.metadata.duration_seconds }}s</span>
          </div>
        </div>

        <!-- Live Logs Terminal (Premium Developer Aesthetic) -->
        <div v-if="liveProgress?.metadata?.logs" class="rounded-xl bg-slate-900 overflow-hidden shadow-inner flex flex-col border border-slate-800 ring-1 ring-white/10">
          <div class="px-4 py-2.5 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between backdrop-blur">
            <div class="flex items-center gap-2.5">
              <Terminal class="h-4 w-4 text-emerald-400" />
              <span class="text-xs font-mono font-medium text-slate-300">rag_pipeline.sh</span>
            </div>
            <div class="flex gap-1.5">
              <div class="h-2.5 w-2.5 rounded-full bg-slate-700"></div>
              <div class="h-2.5 w-2.5 rounded-full bg-slate-700"></div>
              <div class="h-2.5 w-2.5 rounded-full bg-slate-700"></div>
            </div>
          </div>
          <div
            ref="logsContainer"
            class="p-4 h-64 overflow-y-auto font-mono text-[11px] leading-relaxed space-y-2 custom-scrollbar bg-[#0d1117]"
          >
            <div v-for="(log, idx) in liveProgress.metadata.logs" :key="idx" class="flex items-start gap-3">
              <span class="text-slate-500 shrink-0 select-none">[{{ new Date(log.timestamp).toLocaleTimeString([], { hour12: false }) }}]</span>
              <span :class="{
                'text-emerald-400 font-semibold': log.level === 'success',
                'text-rose-400 font-semibold': log.level === 'error',
                'text-amber-400 font-medium': log.level === 'warn',
                'text-slate-300': !['success', 'error', 'warn'].includes(log.level)
              }">
                {{ log.message }}
              </span>
            </div>

            <div v-if="liveProgress.status === 'ingesting' || liveProgress.status === 'extracting' || liveProgress.status === 'ready_for_chunking'" class="flex items-center gap-2 text-slate-500 mt-3 pt-2 border-t border-slate-800/50">
              <span class="h-2 w-1.5 bg-emerald-500 animate-pulse inline-block"></span>
              <span class="text-slate-400 italic">Listening for output...</span>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(13, 17, 23, 1);
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(51, 65, 85, 0.8);
  border-radius: 3px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(71, 85, 105, 1);
}
</style>
