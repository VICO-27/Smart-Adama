<script setup lang="ts">
import { ref, computed } from 'vue'
import apiClient from '@/api/client'
import debounce from 'lodash/debounce'
import PageHeader from '@/components/admin/PageHeader.vue'
import { Search, Loader2, LayoutPanelLeft, FileText, Hash, AlertTriangle, Layers } from 'lucide-vue-next'

// Types
interface StructuralContext {
  heading?: string
  [key: string]: any
}

interface RetrievedChunk {
  id: string
  chunk_text: string
  rrf_score: number
  vector_score: number
  vector_rank: number
  keyword_score: number
  keyword_rank: number
  book_id: string
  page_number: number
  structural_context?: StructuralContext
}

interface DebugResponse {
  query: string
  duration_seconds: number
  results_count: number
  chunks: RetrievedChunk[]
}

// State
const query = ref('')
const isSearching = ref(false)
const results = ref<DebugResponse | null>(null)
const error = ref<string | null>(null)

// Methods
const performSearch = async () => {
  if (!query.value.trim()) {
    results.value = null
    return
  }

  isSearching.value = true
  error.value = null

  try {
    const { data } = await apiClient.get('/admin/rag/debug-search', {
      params: { query: query.value, limit: 15 }
    })
    results.value = data
  } catch (err: any) {
    console.error('Search failed:', err)
    error.value = err.response?.data?.message || 'Failed to execute search.'
  } finally {
    isSearching.value = false
  }
}

// Debounce for live search while typing
const debouncedSearch = debounce(performSearch, 500)

const onInput = () => {
  debouncedSearch()
}

// Highlighting utility
const highlightText = (text: string, searchTerm: string) => {
  if (!searchTerm) return text
  
  // Basic case-insensitive highlight, escaping regex chars
  const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const regex = new RegExp(`(${escapedTerm})`, 'gi')
  return text.replace(regex, '<mark class="bg-amber-200 text-amber-900 rounded-sm px-1 py-0.5 font-medium">$1</mark>')
}

// Visual Helpers
const getScoreColor = (score: number, isKeyword = false) => {
  // If keyword score is 0, it didn't match lexically
  if (isKeyword && score === 0) return 'bg-slate-100 text-slate-400 border-slate-200'
  
  if (score > 0.8) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (score > 0.5) return 'bg-smart-blue-50 text-smart-blue-700 border-smart-blue-200'
  if (score > 0.0) return 'bg-amber-50 text-amber-700 border-amber-200'
  return 'bg-slate-50 text-slate-500 border-slate-200'
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="Retrieval Debugger" 
      description="Inspect the Reciprocal Rank Fusion (RRF) algorithm and see how semantic and lexical searches combine to rank chunks."
    />

    <!-- Search Bar -->
    <div class="relative rounded-xl bg-white shadow-sm ring-1 ring-inset ring-slate-200 focus-within:ring-2 focus-within:ring-smart-blue-600 transition-all">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
        <Search class="h-5 w-5 text-slate-400" :class="{ 'animate-pulse text-smart-blue-500': isSearching }" />
      </div>
      <input
        v-model="query"
        @input="onInput"
        @keyup.enter="performSearch"
        type="text"
        class="block w-full rounded-xl border-0 py-4 pl-12 pr-12 text-slate-900 placeholder:text-slate-400 focus:ring-0 sm:text-lg sm:leading-6 bg-transparent"
        placeholder="Type a query to debug (e.g. 'mayor' or 'What is the role of a mayor?')"
      />
      <div class="absolute inset-y-0 right-0 flex items-center pr-4" v-if="isSearching">
        <Loader2 class="h-5 w-5 text-smart-blue-500 animate-spin" />
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="rounded-xl bg-red-50 p-4 border border-red-200 flex items-start gap-3">
      <AlertTriangle class="h-5 w-5 text-red-600 shrink-0" />
      <div>
        <h3 class="text-sm font-semibold text-red-800">Search Failed</h3>
        <p class="mt-1 text-sm text-red-700">{{ error }}</p>
      </div>
    </div>

    <!-- Results Overview -->
    <div v-if="results" class="flex items-center justify-between text-sm text-slate-500 px-2 py-2">
      <div class="flex items-center gap-2">
        <Layers class="h-4 w-4" />
        Found <span class="font-semibold text-slate-900">{{ results.results_count }}</span> chunks for "<span class="font-medium italic">{{ results.query }}</span>"
      </div>
      <div class="flex items-center gap-2">
        Processed in <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700">{{ results.duration_seconds }}s</span>
      </div>
    </div>

    <!-- Chunk List -->
    <div v-if="results && results.chunks.length > 0" class="space-y-4 pb-12">
      <div 
        v-for="(chunk, index) in results.chunks" 
        :key="chunk.id"
        class="group bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden transition-all hover:shadow-premium-hover hover:border-smart-blue-200"
      >
        <!-- Card Header: Rank & Scores -->
        <div class="border-b border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors group-hover:bg-smart-blue-50/30">
          <div class="flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-smart-blue-900 text-sm font-bold text-white shadow-sm ring-2 ring-white">
              #{{ index + 1 }}
            </div>
            <div class="flex flex-col">
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">RRF Fusion Score</span>
              <span class="text-xl font-bold text-smart-blue-600 font-mono">{{ chunk.rrf_score.toFixed(4) }}</span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-4">
            <!-- Semantic Score -->
            <div class="flex flex-col items-end gap-1">
              <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Semantic (Rank #{{ chunk.vector_rank }})</span>
              <span :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-mono font-semibold border', getScoreColor(chunk.vector_score)]">
                {{ chunk.vector_score.toFixed(4) }}
              </span>
            </div>
            
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Keyword Score -->
            <div class="flex flex-col items-end gap-1">
              <span class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Lexical (Rank #{{ chunk.keyword_rank }})</span>
              <span :class="['inline-flex items-center rounded-md px-2 py-1 text-xs font-mono font-semibold border', getScoreColor(chunk.keyword_score, true)]">
                {{ chunk.keyword_score.toFixed(4) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Card Body: Metadata & Text -->
        <div class="px-4 py-5 sm:px-6 space-y-4">
          
          <!-- Metadata Badges -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 border border-slate-200">
              <FileText class="h-3.5 w-3.5 text-slate-400" />
              Page {{ chunk.page_number || 'N/A' }}
            </span>
            
            <span v-if="chunk.structural_context?.heading" class="inline-flex items-center gap-1.5 rounded-md bg-smart-blue-50 px-2.5 py-1 text-xs font-medium text-smart-blue-700 border border-smart-blue-200">
              <LayoutPanelLeft class="h-3.5 w-3.5 text-smart-blue-500" />
              {{ chunk.structural_context.heading }}
            </span>
            
            <span class="inline-flex items-center gap-1.5 rounded-md bg-slate-50 px-2.5 py-1 text-xs font-mono font-medium text-slate-500 border border-slate-200">
              <Hash class="h-3.5 w-3.5" />
              {{ chunk.id.substring(0,8) }}...
            </span>
          </div>

          <!-- Text Preview -->
          <div class="text-sm leading-relaxed text-slate-800 bg-slate-50/50 border border-slate-100 rounded-lg p-4 group-hover:bg-white group-hover:border-slate-200 transition-colors">
            <p v-html="highlightText(chunk.chunk_text, query)"></p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty Results -->
    <div v-else-if="results && results.chunks.length === 0" class="flex flex-col items-center justify-center py-16 px-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50">
      <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center mb-4">
        <Search class="h-6 w-6 text-slate-400" />
      </div>
      <h3 class="text-base font-semibold text-slate-900">No chunks retrieved</h3>
      <p class="mt-1 text-sm text-slate-500">The retrieval algorithm couldn't find matches for this query.</p>
    </div>
  </div>
</template>
