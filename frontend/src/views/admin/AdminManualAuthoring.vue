<script setup lang="ts">
import { ref, onMounted, watch, nextTick, shallowRef } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import apiClient from '@/api/client'
import IngestionMonitor from './IngestionMonitor.vue'
import AuthoringSectionNode from './components/AuthoringSectionNode.vue'
import { useConfirm } from '@/composables/useConfirm'
import Quill from 'quill'
import 'quill/dist/quill.snow.css'

import {
  FileEdit,
  FolderTree,
  FileText,
  Save,
  CheckCircle,
  PlayCircle,
  AlertTriangle,
  Plus,
  Trash2,
  ChevronLeft,
  Settings2,
  Eye
} from 'lucide-vue-next'

const { confirm } = useConfirm()
const router = useRouter()
const route = useRoute()

// Modes
const mode = ref<'structured' | 'page'>('structured')

// State
const book = ref<any>(null)
const nodes = ref<any[]>([]) // Chapters or Pages
const activeNode = ref<any>(null) // the currently selected node
const isSaving = ref(false)
const saveStatus = ref<'saved' | 'unsaved' | 'saving'>('saved')
const validationErrors = ref<string[]>([])
const activeJobId = ref<string | null>(null)
const previewData = ref<any>(null)

// Quill
const editorContainer = ref<HTMLElement | null>(null)
let quill: Quill | null = null

onMounted(async () => {
  // Try to find draft manual book or create one
  try {
    const { data: booksData } = await apiClient.get('/admin/rag/documents')
    let draftBook = null

    if (route.params.id) {
      draftBook = booksData.documents.find((d: any) => d.id === route.params.id)
    } else {
      draftBook = booksData.documents.find((d: any) => d.status === 'draft' && d.source_type === 'manual')
    }

    if (!draftBook) {
      const { data } = await apiClient.post('/admin/manual/books', { title: 'New Manual Document', version: 1 })
      draftBook = data.book
    }

    book.value = draftBook
    await loadTree()
    initQuill()
  } catch (e) {
    console.error("Initialization failed", e)
  }
})

const initQuill = () => {
  if (editorContainer.value) {
    quill = new Quill(editorContainer.value, {
      theme: 'snow',
      placeholder: 'Write your content here...',
      modules: {
        toolbar: [
          [{ 'header': [1, 2, 3, false] }],
          ['bold', 'italic', 'underline'],
          [{ 'list': 'ordered'}, { 'list': 'bullet' }],
          [{ 'align': [] }],
          ['link', 'image', 'video', 'blockquote', 'code-block'],
          ['clean']
        ]
      }
    })

    quill.on('text-change', () => {
      saveStatus.value = 'unsaved'
      debouncedSave()
    })

    // Custom Image Click Handler to allow deleting
    quill.root.addEventListener('click', async (e: MouseEvent) => {
      const target = e.target as HTMLElement
      if (target && target.tagName === 'IMG') {
        const isConfirmed = await confirm({
          title: 'Manage Image',
          message: 'Do you want to delete this image?',
          confirmText: 'Delete Image',
          confirmColor: 'red'
        })
        if (isConfirmed) {
          target.remove()
          // Update activeNode content to trigger reactivity if needed
          if (activeNode.value) {
            activeNode.value.raw_text = quill?.root.innerHTML || ''
            saveStatus.value = 'unsaved'
          }
        }
      }
    })
  }
}

const loadTree = async () => {
  if (!book.value) return
  const { data } = await apiClient.get(`/admin/manual/books/${book.value.id}/tree`)

  if (mode.value === 'structured') {
    nodes.value = data.chapters
    if (nodes.value.length === 0) {
      // Auto-create first chapter and section if empty
      const { data: chapData } = await apiClient.post(`/admin/manual/books/${book.value.id}/chapters`, { title: 'Chapter 1', order: 1 })
      const { data: secData } = await apiClient.post(`/admin/manual/chapters/${chapData.chapter.id}/sections`, { title: 'Section 1.1', order: 1 })
      chapData.chapter.sections = [secData.section]
      nodes.value = [chapData.chapter]
      selectNode(secData.section, 'section')
    } else if (nodes.value[0].sections?.length > 0) {
      selectNode(nodes.value[0].sections[0], 'section')
    }
  } else {
    nodes.value = data.pages
    if (nodes.value.length === 0) {
      const { data: pageData } = await apiClient.post(`/admin/manual/books/${book.value.id}/pages`, { page_number: 1, raw_text: '' })
      nodes.value = [pageData.page]
      selectNode(pageData.page, 'page')
    } else {
      selectNode(nodes.value[0], 'page')
    }
  }
}

const selectNode = (node: any, type: 'chapter' | 'section' | 'page') => {
  activeNode.value = { ...node, _type: type }
  if (quill) {
    // Prevent trigger change
    quill.root.innerHTML = node.raw_text || node.content || ''
    saveStatus.value = 'saved'
  }
}

let saveTimeout: ReturnType<typeof setTimeout>
const debouncedSave = () => {
  clearTimeout(saveTimeout)
  saveTimeout = setTimeout(saveContent, 1000)
}

const saveContent = async () => {
  if (!activeNode.value || !quill) return
  isSaving.value = true
  saveStatus.value = 'saving'

  const content = quill.root.innerHTML

  try {
    if (activeNode.value._type === 'section') {
      await apiClient.put(`/admin/manual/sections/${activeNode.value.id}`, { raw_text: content, title: activeNode.value.title })
    } else if (activeNode.value._type === 'page') {
      await apiClient.put(`/admin/manual/pages/${activeNode.value.id}`, { raw_text: content })
    }
    saveStatus.value = 'saved'
    // Update local tree quietly
    activeNode.value.raw_text = content
  } catch (e) {
    console.error(e)
    saveStatus.value = 'unsaved'
  } finally {
    isSaving.value = false
  }
}

const validateAndStart = async () => {
  validationErrors.value = []
  // Simple validation first
  if (quill && quill.getText().trim().length === 0 && saveStatus.value === 'unsaved') {
    validationErrors.value.push("Current content is empty. Please save or write something.")
    return
  }

  // Ensure saved
  if (saveStatus.value !== 'saved') await saveContent()

  // Trigger ingestion job
  try {
    const { data } = await apiClient.post('/admin/ingestion-jobs/start', {
      book_id: book.value.id,
      mode: mode.value
    })
    activeJobId.value = data.job.id
  } catch (e: any) {
    validationErrors.value.push(e.response?.data?.message || "Failed to start ingestion.")
  }
}

const updateNodeTitle = async (node: any, type: string, newTitle: string) => {
  if (type === 'chapter') {
    await apiClient.put(`/admin/manual/chapters/${node.id}`, { title: newTitle })
  } else if (type === 'section') {
    await apiClient.put(`/admin/manual/sections/${node.id}`, { title: newTitle })
  }
  node.title = newTitle
}

const addNode = async (type: 'chapter' | 'section' | 'page', parentId?: string) => {
  if (type === 'chapter') {
    const order = nodes.value.length + 1
    const { data } = await apiClient.post(`/admin/manual/books/${book.value.id}/chapters`, { title: `Chapter ${order}`, order })
    data.chapter.sections = []
    nodes.value.push(data.chapter)
  } else if (type === 'section') {
    let parentSection = null
    let parentChapter = nodes.value.find(c => c.id === parentId)
    let isSubSection = false

    if (!parentChapter) {
      // Maybe it's a section adding a subsection. We need to find the parent section in the tree
      const findSection = (sections: any[]): any => {
        for (const s of sections) {
          if (s.id === parentId) return s
          if (s.descendants) {
            const found = findSection(s.descendants)
            if (found) return found
          }
        }
        return null
      }
      for (const chap of nodes.value) {
        if (chap.sections) {
          parentSection = findSection(chap.sections)
          if (parentSection) {
            parentChapter = chap
            isSubSection = true
            break
          }
        }
      }
    }

    if (!parentChapter) return

    let order = 1
    let apiParentId = null
    let targetChildrenArray = []

    if (isSubSection) {
      apiParentId = parentId
      order = (parentSection.descendants?.length || 0) + 1
      targetChildrenArray = parentSection.descendants || []
    } else {
      order = (parentChapter.sections?.length || 0) + 1
      targetChildrenArray = parentChapter.sections || []
    }

    const { data } = await apiClient.post(`/admin/manual/chapters/${parentChapter.id}/sections`, {
      title: `Section`,
      order,
      parent_id: apiParentId
    })

    if (isSubSection) {
      if (!parentSection.descendants) parentSection.descendants = []
      parentSection.descendants.push(data.section)
    } else {
      if (!parentChapter.sections) parentChapter.sections = []
      parentChapter.sections.push(data.section)
    }

    selectNode(data.section, 'section')
  } else if (type === 'page') {
    const order = nodes.value.length + 1
    const { data } = await apiClient.post(`/admin/manual/books/${book.value.id}/pages`, { page_number: order, raw_text: '' })
    nodes.value.push(data.page)
    selectNode(data.page, 'page')
  }
}

const deleteNode = async (type: 'chapter' | 'section' | 'page', id: string) => {
  const isConfirmed = await confirm({
    title: `Delete ${type.charAt(0).toUpperCase() + type.slice(1)}`,
    message: `Are you sure you want to delete this ${type}?`,
    confirmText: 'Delete',
    confirmColor: 'red'
  })
  if (!isConfirmed) return
  try {
    if (type === 'chapter') {
      await apiClient.delete(`/admin/manual/chapters/${id}`)
      nodes.value = nodes.value.filter(n => n.id !== id)
    } else if (type === 'section') {
      await apiClient.delete(`/admin/manual/sections/${id}`)
      await loadTree() // Reload tree to ensure recursive deletion sync
    } else if (type === 'page') {
      await apiClient.delete(`/admin/manual/pages/${id}`)
      nodes.value = nodes.value.filter(n => n.id !== id)
    }

    if (activeNode.value?.id === id) {
      activeNode.value = null
      if (quill) quill.root.innerHTML = ''
    }
  } catch (e) {
    console.error('Failed to delete node', e)
    alert('Failed to delete node')
  }
}
</script>

<template>
  <div class="h-[calc(100dvh-5rem)] flex flex-col bg-slate-50 -m-6 sm:-m-8">

    <!-- Topbar -->
    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-4">
        <router-link :to="{ name: 'admin-documents' }" class="text-slate-400 hover:text-slate-600 transition-colors">
          <ChevronLeft class="h-6 w-6" />
        </router-link>
        <div class="w-px h-6 bg-slate-200"></div>
        <div>
          <h1 class="text-lg font-bold text-slate-900">{{ book?.title || 'Loading...' }}</h1>
          <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
            <span class="text-slate-500">Manual Authoring</span>
            <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
            <span :class="{
              'text-emerald-500': saveStatus === 'saved',
              'text-amber-500': saveStatus === 'unsaved',
              'text-smart-blue-500': saveStatus === 'saving'
            }">
              <Save class="h-3 w-3 inline mr-1" />
              {{ saveStatus === 'saved' ? 'Saved' : (saveStatus === 'unsaved' ? 'Unsaved changes' : 'Saving...') }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="bg-slate-100 p-1 rounded-lg flex items-center mr-4">
          <button
            @click="mode = 'structured'; loadTree()"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="mode === 'structured' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
          >
            <FolderTree class="h-4 w-4 inline mr-1.5" /> Structure
          </button>
          <button
            @click="mode = 'page'; loadTree()"
            class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
            :class="mode === 'page' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-500 hover:text-slate-700'"
          >
            <FileText class="h-4 w-4 inline mr-1.5" /> Pages
          </button>
        </div>

        <button @click="validateAndStart" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
          <PlayCircle class="h-4 w-4" />
          Ingest Document
        </button>
      </div>
    </header>

    <!-- Main Workspace -->
    <div class="flex-1 flex overflow-hidden">

      <!-- Sidebar (Tree) -->
      <aside class="w-72 bg-white border-r border-slate-200 overflow-y-auto flex flex-col shrink-0">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white/90 backdrop-blur z-10">
          <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider">{{ mode === 'structured' ? 'Chapters' : 'Pages' }}</h2>
          <button @click="addNode(mode === 'structured' ? 'chapter' : 'page')" class="p-1 rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700">
            <Plus class="h-5 w-5" />
          </button>
        </div>

        <div class="p-2 space-y-1">
          <!-- Structured Mode -->
          <template v-if="mode === 'structured'">
            <div v-for="chapter in nodes" :key="chapter.id" class="mb-2">
              <div class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-slate-50 group">
                <input
                  type="text"
                  v-model="chapter.title"
                  @blur="updateNodeTitle(chapter, 'chapter', chapter.title)"
                  class="bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-800 w-full p-0"
                />
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button @click="addNode('section', chapter.id)" class="p-1 text-slate-400 hover:text-smart-blue-600 hover:bg-slate-200 rounded">
                    <Plus class="h-4 w-4" />
                  </button>
                  <button @click="deleteNode('chapter', chapter.id)" class="p-1 text-slate-400 hover:text-red-600 hover:bg-slate-200 rounded">
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </div>

              <div class="pl-2 space-y-0.5 mt-1 border-l border-slate-100 ml-3">
                <AuthoringSectionNode
                  v-for="section in chapter.sections"
                  :key="section.id"
                  :section="section"
                  :active-node-id="activeNode?.id"
                  @select="selectNode($event, 'section')"
                  @add="addNode('section', $event)"
                  @delete="deleteNode('section', $event)"
                  @update-title="updateNodeTitle($event.node, 'section', $event.title)"
                />
              </div>
            </div>
          </template>

          <!-- Page Mode -->
          <template v-else>
            <div
              v-for="page in nodes" :key="page.id"
              class="w-full text-left flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors mb-1 group cursor-pointer"
              :class="activeNode?.id === page.id ? 'bg-smart-blue-50 text-smart-blue-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              @click="selectNode(page, 'page')"
            >
              <div class="flex items-center gap-2.5">
                <FileText class="h-4 w-4 shrink-0" :class="activeNode?.id === page.id ? 'text-smart-blue-500' : 'text-slate-400'" />
                <span class="font-medium">Page {{ page.page_number }}</span>
              </div>

              <button @click.stop="deleteNode('page', page.id)" class="p-1 opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 hover:bg-slate-200 rounded">
                <Trash2 class="h-4 w-4" />
              </button>
            </div>
          </template>
        </div>
      </aside>

      <!-- Editor Canvas -->
      <main class="flex-1 flex flex-col bg-slate-50/50 overflow-hidden">

        <div v-if="validationErrors.length > 0" class="m-6 mb-0 p-4 bg-red-50 border border-red-200 rounded-xl shrink-0">
          <div class="flex gap-3 text-red-700">
            <AlertTriangle class="h-5 w-5 shrink-0 mt-0.5" />
            <div>
              <h3 class="font-semibold text-sm">Validation Failed</h3>
              <ul class="list-disc pl-5 mt-1 text-sm space-y-1">
                <li v-for="err in validationErrors" :key="err">{{ err }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex-1 p-6 flex flex-col overflow-hidden">
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm flex-1 flex flex-col overflow-hidden max-w-4xl mx-auto w-full">
            <!-- Node Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50 shrink-0" v-if="activeNode">
              <div class="flex-1">
                <input
                  v-if="activeNode._type === 'section'"
                  type="text"
                  v-model="activeNode.title"
                  @blur="updateNodeTitle(activeNode, 'section', activeNode.title)"
                  class="bg-transparent border-none focus:ring-0 text-xl font-bold text-slate-900 w-full p-0"
                />
                <h2 v-else class="text-xl font-bold text-slate-900">Page {{ activeNode.page_number }}</h2>
              </div>
            </div>

            <!-- Quill Container -->
            <div class="flex-1 flex flex-col overflow-hidden bg-white">
              <div ref="editorContainer" class="flex-1 overflow-hidden" style="font-size: 16px;"></div>
            </div>
          </div>
        </div>
      </main>

    </div>

    <!-- Ingestion Monitor Modal -->
    <IngestionMonitor v-if="activeJobId" :job-id="activeJobId" @close="activeJobId = null" />

  </div>
</template>

<style>
/* Quill Overrides for better aesthetics */
.ql-toolbar.ql-snow {
  border: none !important;
  border-bottom: 1px solid #e2e8f0 !important;
  padding: 12px 24px !important;
  background-color: #f8fafc;
}
.ql-container.ql-snow {
  border: none !important;
  flex: 1 !important;
  display: flex !important;
  flex-direction: column !important;
  overflow: hidden !important;
}
.ql-editor {
  padding: 32px 48px !important;
  font-family: 'Inter', sans-serif !important;
  line-height: 1.7 !important;
  color: #0f172a !important;
  flex: 1 !important;
  overflow-y: auto !important;
}
.ql-editor h1, .ql-editor h2, .ql-editor h3 {
  font-weight: 700 !important;
  margin-top: 1.5em !important;
  margin-bottom: 0.5em !important;
  color: #0f172a !important;
}
</style>
