<script setup lang="ts">
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/admin/PageHeader.vue'
import { Cpu, BrainCircuit, Activity, Database, Key, SlidersHorizontal, Settings2, Save, RefreshCw } from 'lucide-vue-next'
import api from '@/api/client'

const settings = ref({
  llm_provider: 'groq',
  embedding_provider: 'ollama',
  embedding_dimensions: '768',
  rag_similarity_threshold: '0.75',
  rag_top_k: '4'
})

const loading = ref(true)
const saving = ref(false)

const loadSettings = async () => {
  try {
    const res = await api.get('/admin/settings')
    settings.value = { ...settings.value, ...res.data.settings }
  } catch (e) {
    console.error('Failed to load settings', e)
  } finally {
    loading.value = false
  }
}

const saveSettings = async () => {
  saving.value = true
  try {
    await api.put('/admin/settings', { settings: settings.value })
    alert('AI configuration saved successfully!')
  } catch (e) {
    alert('Failed to save settings.')
    console.error(e)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSettings()
})
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="AI & RAG Console" 
      description="Configure AI providers, LLM models, and tune Retrieval-Augmented Generation parameters."
    >
      <template #actions>
        <button 
          @click="saveSettings"
          :disabled="loading || saving"
          class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-colors disabled:opacity-50"
        >
          <RefreshCw v-if="saving" class="h-4 w-4 animate-spin" />
          <Save v-else class="h-4 w-4" />
          Save Configuration
        </button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Providers Settings -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- LLM Provider -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-smart-blue-50 text-smart-blue-600">
                <Cpu class="h-5 w-5" />
              </div>
              <div>
                <h3 class="font-semibold text-slate-900">LLM Provider</h3>
                <p class="text-xs text-slate-500">Configure the primary text generation model</p>
              </div>
            </div>
            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium leading-6 text-slate-900">Provider</label>
                <select v-model="settings.llm_provider" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-smart-blue-600 sm:text-sm sm:leading-6 bg-white">
                  <option value="groq">Groq</option>
                  <option value="openai">OpenAI</option>
                  <option value="ollama">Ollama (Local)</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium leading-6 text-slate-900">API Key</label>
                <div class="relative mt-2 rounded-md shadow-sm">
                  <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Key class="h-4 w-4 text-slate-400" />
                  </div>
                  <input type="password" value="************************" disabled class="block w-full rounded-md border-0 py-1.5 pl-10 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-smart-blue-600 sm:text-sm sm:leading-6 bg-slate-50 cursor-not-allowed" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Embedding Provider -->
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-smart-blue-50 text-smart-blue-600">
                <BrainCircuit class="h-5 w-5" />
              </div>
              <div>
                <h3 class="font-semibold text-slate-900">Embedding Engine</h3>
                <p class="text-xs text-slate-500">Configure vector generation for documents</p>
              </div>
            </div>
            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Active</span>
          </div>
          <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium leading-6 text-slate-900">Provider</label>
                <select v-model="settings.embedding_provider" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-smart-blue-600 sm:text-sm sm:leading-6 bg-white">
                  <option value="ollama">Ollama (Local)</option>
                  <option value="openai">OpenAI</option>
                  <option value="cohere">Cohere</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium leading-6 text-slate-900">Dimensions</label>
                <select v-model="settings.embedding_dimensions" class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-smart-blue-600 sm:text-sm sm:leading-6 bg-white">
                  <option value="768">768 (nomic-embed)</option>
                  <option value="1536">1536 (text-embedding-3)</option>
                </select>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- RAG Configuration Sidebar -->
      <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 flex items-center gap-2">
            <SlidersHorizontal class="h-5 w-5 text-slate-500" />
            <h3 class="font-semibold text-slate-900">RAG Parameters</h3>
          </div>
          <div class="p-6 space-y-6">
            
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-sm font-medium text-slate-900">Retrieval Top K</label>
                <span class="text-xs font-semibold text-smart-blue-600 bg-smart-blue-50 px-2 py-0.5 rounded">{{ settings.rag_top_k }} chunks</span>
              </div>
              <p class="text-xs text-slate-500 mb-3">Number of chunks fetched before RRF fusion</p>
              <input type="range" v-model="settings.rag_top_k" min="2" max="20" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-smart-blue-600">
            </div>

            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-sm font-medium text-slate-900">Similarity Threshold</label>
                <span class="text-xs font-semibold text-smart-blue-600 bg-smart-blue-50 px-2 py-0.5 rounded">{{ settings.rag_similarity_threshold }}</span>
              </div>
              <p class="text-xs text-slate-500 mb-3">Minimum cosine distance for vector matches</p>
              <input type="range" v-model="settings.rag_similarity_threshold" min="0" max="1" step="0.05" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-smart-blue-600">
            </div>
            
            <div class="pt-4 border-t border-slate-200">
              <label class="block text-sm font-medium leading-6 text-slate-900">Retrieval Strategy</label>
              <select class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-smart-blue-600 sm:text-sm sm:leading-6 bg-white">
                <option>Hybrid (RRF Fusion)</option>
                <option>Vector Only (Cosine)</option>
                <option>Lexical Only (BM25)</option>
              </select>
            </div>
            
          </div>
        </div>

        <div class="rounded-xl border border-smart-blue-200 bg-smart-blue-50 p-6 shadow-sm">
          <div class="flex items-start gap-3">
            <Settings2 class="h-5 w-5 text-smart-blue-500 shrink-0" />
            <div>
              <h4 class="text-sm font-medium text-smart-blue-900">Dynamic Reconfiguration Active</h4>
              <p class="text-xs text-smart-blue-700 mt-1 leading-relaxed">
                Settings are now managed dynamically via the database instead of the `.env` file, allowing instant live updates.
              </p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
