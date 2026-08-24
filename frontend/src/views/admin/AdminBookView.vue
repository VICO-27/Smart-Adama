<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useBooksStore } from '@/stores/books'
import { booksApi } from '@/api/books'
import SaCard from '@/components/ui/SaCard.vue'
import SaButton from '@/components/ui/SaButton.vue'

const books = useBooksStore()

// ─────────────────────────────────────────────────────────────
// Upload state
// ─────────────────────────────────────────────────────────────

const showUploadForm = ref(false)
const isUploading = ref(false)
const uploadError = ref('')
const title = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const selectedFileName = ref('')

// ─────────────────────────────────────────────────────────────
// Filters
// ─────────────────────────────────────────────────────────────

const searchQuery = ref('')
const statusFilter = ref<'all' | 'published' | 'processing' | 'draft'>('all')

// ─────────────────────────────────────────────────────────────
// Computed data
// ─────────────────────────────────────────────────────────────

const normalizedBooks = computed(() => {
  return books.books || []
})

const filteredBooks = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  return normalizedBooks.value.filter((book: any) => {
    const titleMatches =
      !query || String(book.title || '').toLowerCase().includes(query)

    const status = String(book.status || 'draft').toLowerCase()

    const statusMatches =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'published' && status === 'published') ||
      (statusFilter.value === 'processing' &&
        ['processing', 'pending', 'ingesting'].includes(status)) ||
      (statusFilter.value === 'draft' && ['draft', 'failed'].includes(status))

    return titleMatches && statusMatches
  })
})

const publishedCount = computed(() => {
  return normalizedBooks.value.filter(
    (book: any) => String(book.status || '').toLowerCase() === 'published'
  ).length
})

const processingCount = computed(() => {
  return normalizedBooks.value.filter((book: any) =>
    ['processing', 'pending', 'ingesting'].includes(
      String(book.status || '').toLowerCase()
    )
  ).length
})

const totalChapters = computed(() => {
  return normalizedBooks.value.reduce((total: number, book: any) => {
    const chapters = book.chapters?.data || book.chapters || []
    return total + (Array.isArray(chapters) ? chapters.length : 0)
  }, 0)
})

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

const getBookStatus = (book: any) => {
  const status = String(book.status || 'draft').toLowerCase()

  if (status === 'published') {
    return {
      label: 'Published',
      class: 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
      dot: 'bg-emerald-500',
    }
  }
  
  if (status === 'ready_for_chunking') {
    return {
      label: 'Ready',
      class: 'bg-indigo-50 text-indigo-700 ring-indigo-600/10',
      dot: 'bg-indigo-500',
    }
  }
  
  if (status === 'ocr_required') {
    return {
      label: 'OCR Required',
      class: 'bg-amber-100 text-amber-800 ring-amber-600/20',
      dot: 'bg-amber-600',
    }
  }

  if (['processing', 'pending', 'ingesting', 'extracting'].includes(status)) {
    return {
      label: 'Processing',
      class: 'bg-amber-50 text-amber-700 ring-amber-600/10',
      dot: 'bg-amber-500',
    }
  }

  if (status === 'failed') {
    return {
      label: 'Failed',
      class: 'bg-red-50 text-red-700 ring-red-600/10',
      dot: 'bg-red-500',
    }
  }

  return {
    label: 'Draft',
    class: 'bg-slate-50 text-slate-600 ring-slate-500/10',
    dot: 'bg-slate-400',
  }
}

const getChapterCount = (book: any) => {
  const chapters = book.chapters?.data || book.chapters || []
  return Array.isArray(chapters) ? chapters.length : 0
}

const formatDate = (date?: string) => {
  if (!date) return 'Recently added'

  const parsed = new Date(date)

  if (Number.isNaN(parsed.getTime())) {
    return 'Recently added'
  }

  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(parsed)
}

const getBookDate = (book: any) => {
  return book.created_at || book.createdAt || book.updated_at || book.updatedAt
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
}

const openUploadForm = () => {
  uploadError.value = ''
  showUploadForm.value = true
}

const closeUploadForm = () => {
  if (isUploading.value) return

  showUploadForm.value = false
  uploadError.value = ''
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement

  if (!target.files?.length) {
    selectedFileName.value = ''
    return
  }

  const file = target.files[0]

  if (file.type !== 'application/pdf') {
    uploadError.value = 'Please select a valid PDF file.'
    selectedFileName.value = ''
    target.value = ''
    return
  }

  selectedFileName.value = file.name
  uploadError.value = ''
}

// ─────────────────────────────────────────────────────────────
// Upload
// ─────────────────────────────────────────────────────────────

const handleUpload = async () => {
  uploadError.value = ''

  if (!title.value.trim()) {
    uploadError.value = 'Please provide a book title.'
    return
  }

  if (!fileInput.value?.files?.length) {
    uploadError.value = 'Please select a PDF file.'
    return
  }

  const file = fileInput.value.files[0]

  if (file.type !== 'application/pdf') {
    uploadError.value = 'Only PDF files are supported.'
    return
  }

  isUploading.value = true

  const formData = new FormData()
  formData.append('title', title.value.trim())
  formData.append('file', file)

  try {
    await booksApi.uploadBook(formData)

    title.value = ''
    selectedFileName.value = ''

    if (fileInput.value) {
      fileInput.value.value = ''
    }

    showUploadForm.value = false

    await books.loadBooks()
  } catch (err: any) {
    uploadError.value =
      err?.response?.data?.message ||
      err?.response?.data?.error ||
      'Failed to upload book. Please check the backend logs and try again.'
  } finally {
    isUploading.value = false
  }
}

// ─────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────

onMounted(() => {
  books.loadBooks()
})
</script>

<template>

    <div class="min-h-full">
      <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 lg:py-8">

        <!-- ═══════════════════════════════════════════════════════
             HEADER
        ═══════════════════════════════════════════════════════ -->

        <header class="mb-7">
          <div
            class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
          >
            <div>
              <div class="mb-2 flex items-center gap-2">
                <span
                  class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-[var(--sa-dark)] text-white"
                >
                  <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                  >
                    <path
                      d="M4 19.5V5.8A1.8 1.8 0 0 1 5.8 4H19v16H5.8A1.8 1.8 0 0 0 4 21.8"
                    />
                    <path d="M4 19.5A1.8 1.8 0 0 1 5.8 17H19" />
                  </svg>
                </span>

                <span
                  class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--sa-taupe)]"
                >
                  Content Management
                </span>
              </div>

              <h1
                class="font-display text-3xl font-semibold tracking-tight text-[var(--sa-dark)] sm:text-4xl"
              >
                Books
              </h1>

              <p class="mt-1.5 max-w-xl text-sm text-[var(--sa-taupe)]">
                Manage your learning library, upload new books, and monitor
                processing status.
              </p>
            </div>

            <SaButton
              variant="primary"
              @click="showUploadForm ? closeUploadForm() : openUploadForm()"
              class="inline-flex items-center justify-center gap-2 rounded-full bg-[var(--sa-dark)] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
            >
              <svg
                v-if="!showUploadForm"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path d="M12 5v14M5 12h14" />
              </svg>

              <svg
                v-else
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>

              {{ showUploadForm ? 'Close' : 'Upload Book' }}
            </SaButton>
          </div>
        </header>

        <!-- ═══════════════════════════════════════════════════════
             STATS
        ═══════════════════════════════════════════════════════ -->

        <section
          class="mb-7 grid grid-cols-2 gap-3 lg:grid-cols-4"
          aria-label="Book statistics"
        >
          <!-- Total -->
          <div
            class="group rounded-2xl border border-[var(--sa-gray)] bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:p-5"
          >
            <div class="flex items-start justify-between">
              <div>
                <p
                  class="text-xs font-medium uppercase tracking-wider text-[var(--sa-taupe)]"
                >
                  Total Books
                </p>

                <p
                  class="mt-2 text-2xl font-semibold tracking-tight text-[var(--sa-dark)] sm:text-3xl"
                >
                  {{ normalizedBooks.length }}
                </p>
              </div>

              <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600"
              >
                <svg
                  class="h-4.5 w-4.5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M4 19.5V5.8A1.8 1.8 0 0 1 5.8 4H19v16H5.8A1.8 1.8 0 0 0 4 21.8" />
                  <path d="M4 19.5A1.8 1.8 0 0 1 5.8 17H19" />
                </svg>
              </span>
            </div>
          </div>

          <!-- Published -->
          <div
            class="group rounded-2xl border border-[var(--sa-gray)] bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:p-5"
          >
            <div class="flex items-start justify-between">
              <div>
                <p
                  class="text-xs font-medium uppercase tracking-wider text-[var(--sa-taupe)]"
                >
                  Published
                </p>

                <p
                  class="mt-2 text-2xl font-semibold tracking-tight text-emerald-600 sm:text-3xl"
                >
                  {{ publishedCount }}
                </p>
              </div>

              <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
              >
                <svg
                  class="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M5 12.5l4 4L19 7" />
                </svg>
              </span>
            </div>
          </div>

          <!-- Processing -->
          <div
            class="group rounded-2xl border border-[var(--sa-gray)] bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:p-5"
          >
            <div class="flex items-start justify-between">
              <div>
                <p
                  class="text-xs font-medium uppercase tracking-wider text-[var(--sa-taupe)]"
                >
                  Processing
                </p>

                <p
                  class="mt-2 text-2xl font-semibold tracking-tight text-amber-600 sm:text-3xl"
                >
                  {{ processingCount }}
                </p>
              </div>

              <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600"
              >
                <svg
                  class="h-4 w-4 animate-spin"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M12 3a9 9 0 1 0 9 9" />
                </svg>
              </span>
            </div>
          </div>

          <!-- Chapters -->
          <div
            class="group rounded-2xl border border-[var(--sa-gray)] bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md sm:p-5"
          >
            <div class="flex items-start justify-between">
              <div>
                <p
                  class="text-xs font-medium uppercase tracking-wider text-[var(--sa-taupe)]"
                >
                  Chapters
                </p>

                <p
                  class="mt-2 text-2xl font-semibold tracking-tight text-[var(--sa-dark)] sm:text-3xl"
                >
                  {{ totalChapters }}
                </p>
              </div>

              <span
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"
              >
                <svg
                  class="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                >
                  <path d="M7 4h10v16H7z" />
                  <path d="M10 8h4M10 12h4M10 16h2" />
                </svg>
              </span>
            </div>
          </div>
        </section>

        <!-- ═══════════════════════════════════════════════════════
             UPLOAD PANEL
        ═══════════════════════════════════════════════════════ -->

        <transition name="slide-fade">
          <section v-if="showUploadForm" class="mb-7">
            <SaCard
              padding="p-0"
              class="overflow-hidden border border-[var(--sa-gray)] bg-white shadow-sm"
            >
              <div
                class="border-b border-[var(--sa-gray)] bg-gradient-to-r from-slate-50 to-white px-5 py-4 sm:px-6"
              >
                <div class="flex items-center gap-3">
                  <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[var(--sa-dark)] text-white"
                  >
                    <svg
                      class="h-5 w-5"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.8"
                    >
                      <path d="M12 16V4M7 9l5-5 5 5" />
                      <path d="M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" />
                    </svg>
                  </span>

                  <div>
                    <h2
                      class="font-semibold text-[var(--sa-dark)]"
                    >
                      Upload a new book
                    </h2>

                    <p class="text-xs text-[var(--sa-taupe)]">
                      Add a PDF manuscript to your learning library.
                    </p>
                  </div>
                </div>
              </div>

              <form
                @submit.prevent="handleUpload"
                class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[1fr_1.2fr_auto] lg:items-end"
              >
                <!-- Title -->
                <div>
                  <label
                    for="book-title"
                    class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-[var(--sa-taupe)]"
                  >
                    Book title
                  </label>

                  <input
                    id="book-title"
                    v-model="title"
                    type="text"
                    autocomplete="off"
                    placeholder="e.g. Smart Adama Guide"
                    :disabled="isUploading"
                    class="h-11 w-full rounded-xl border border-[var(--sa-gray)] bg-white px-3.5 text-sm text-[var(--sa-dark)] outline-none transition placeholder:text-slate-400 focus:border-[var(--sa-dark)] focus:ring-2 focus:ring-[var(--sa-dark)]/5 disabled:cursor-not-allowed disabled:bg-slate-50"
                  />
                </div>

                <!-- File -->
                <div>
                  <label
                    for="book-file"
                    class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-[var(--sa-taupe)]"
                  >
                    PDF manuscript
                  </label>

                  <label
                    for="book-file"
                    :class="[
                      'flex h-11 cursor-pointer items-center gap-3 rounded-xl border border-dashed px-3.5 transition-all',
                      isUploading
                        ? 'cursor-not-allowed border-[var(--sa-gray)] bg-slate-50 opacity-60'
                        : selectedFileName
                          ? 'border-emerald-300 bg-emerald-50/40'
                          : 'border-[var(--sa-gray)] bg-white hover:border-slate-400 hover:bg-slate-50',
                    ]"
                  >
                    <span
                      class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
                    >
                      <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                      >
                        <path d="M6 3h9l3 3v15H6z" />
                        <path d="M14 3v4h4M9 13h6M9 16h4" />
                      </svg>
                    </span>

                    <span class="min-w-0 flex-1">
                      <span
                        v-if="selectedFileName"
                        class="block truncate text-sm font-medium text-emerald-700"
                      >
                        {{ selectedFileName }}
                      </span>

                      <span
                        v-else
                        class="block truncate text-sm text-slate-500"
                      >
                        Choose PDF file
                      </span>
                    </span>

                    <span
                      class="hidden text-xs text-slate-400 sm:block"
                    >
                      PDF only
                    </span>
                  </label>

                  <input
                    id="book-file"
                    ref="fileInput"
                    type="file"
                    accept="application/pdf,.pdf"
                    class="hidden"
                    :disabled="isUploading"
                    @change="handleFileChange"
                  />
                </div>

                <!-- Submit -->
                <button
                  type="submit"
                  :disabled="isUploading"
                  class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-[var(--sa-dark)] px-5 text-sm font-medium text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50 lg:min-w-[150px]"
                >
                  <span
                    v-if="isUploading"
                    class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                  />

                  <svg
                    v-else
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path d="M12 5v14M5 12h14" />
                  </svg>

                  {{ isUploading ? 'Processing…' : 'Upload Book' }}
                </button>

                <!-- Error -->
                <div
                  v-if="uploadError"
                  class="flex items-start gap-2 rounded-xl border border-red-100 bg-red-50 px-3.5 py-3 text-sm text-red-700 lg:col-span-3"
                >
                  <svg
                    class="mt-0.5 h-4 w-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 8v5M12 16h.01" />
                  </svg>

                  <span>{{ uploadError }}</span>
                </div>
              </form>
            </SaCard>
          </section>
        </transition>

        <!-- ═══════════════════════════════════════════════════════
             TOOLBAR
        ═══════════════════════════════════════════════════════ -->

        <section class="mb-4">
          <div
            class="flex flex-col gap-3 rounded-2xl border border-[var(--sa-gray)] bg-white p-3 shadow-sm sm:flex-row sm:items-center"
          >
            <!-- Search -->
            <div class="relative min-w-0 flex-1">
              <svg
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <circle cx="11" cy="11" r="7" />
                <path d="m20 20-4-4" />
              </svg>

              <input
                v-model="searchQuery"
                type="search"
                placeholder="Search books…"
                class="h-10 w-full rounded-xl bg-slate-50 pl-9 pr-9 text-sm text-[var(--sa-dark)] outline-none ring-1 ring-transparent transition focus:bg-white focus:ring-[var(--sa-dark)]/10"
              />

              <button
                v-if="searchQuery"
                type="button"
                class="absolute right-2.5 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full text-slate-400 hover:bg-slate-200 hover:text-slate-700"
                @click="searchQuery = ''"
                aria-label="Clear search"
              >
                <svg
                  class="h-3.5 w-3.5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M6 6l12 12M18 6L6 18" />
                </svg>
              </button>
            </div>

            <!-- Filters -->
            <div
              class="flex shrink-0 items-center gap-1 rounded-xl bg-slate-50 p-1"
            >
              <button
                v-for="filter in [
                  { value: 'all', label: 'All' },
                  { value: 'published', label: 'Published' },
                  { value: 'processing', label: 'Processing' },
                  { value: 'draft', label: 'Draft' },
                ]"
                :key="filter.value"
                type="button"
                :class="[
                  'rounded-lg px-2.5 py-1.5 text-xs font-medium transition-all sm:px-3',
                  statusFilter === filter.value
                    ? 'bg-white text-[var(--sa-dark)] shadow-sm'
                    : 'text-slate-500 hover:text-slate-800',
                ]"
                @click="
                  statusFilter =
                    filter.value as
                      | 'all'
                      | 'published'
                      | 'processing'
                      | 'draft'
                "
              >
                {{ filter.label }}
              </button>
            </div>
          </div>
        </section>

        <!-- Result count -->
        <div class="mb-3 flex items-center justify-between px-1">
          <p class="text-xs text-[var(--sa-taupe)]">
            <span class="font-medium text-[var(--sa-dark)]">
              {{ filteredBooks.length }}
            </span>
            {{ filteredBooks.length === 1 ? 'book' : 'books' }}
            <span v-if="searchQuery || statusFilter !== 'all'">
              matching your filters
            </span>
          </p>

          <button
            v-if="searchQuery || statusFilter !== 'all'"
            type="button"
            class="text-xs font-medium text-[var(--sa-dark)] hover:underline"
            @click="clearFilters"
          >
            Clear filters
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             LOADING
        ═══════════════════════════════════════════════════════ -->

        <div v-if="books.loading" class="space-y-3">
          <div
            v-for="i in 4"
            :key="i"
            class="animate-pulse rounded-2xl border border-[var(--sa-gray)] bg-white p-5"
          >
            <div class="flex items-center gap-4">
              <div class="h-12 w-12 shrink-0 rounded-xl bg-slate-100" />

              <div class="min-w-0 flex-1 space-y-2">
                <div class="h-4 w-2/5 rounded bg-slate-100" />
                <div class="h-3 w-1/4 rounded bg-slate-100" />
              </div>

              <div class="hidden h-7 w-20 rounded-full bg-slate-100 sm:block" />
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             EMPTY / NO SEARCH RESULTS
        ═══════════════════════════════════════════════════════ -->

        <div
          v-else-if="!normalizedBooks.length"
          class="rounded-2xl border border-dashed border-[var(--sa-gray)] bg-white px-6 py-16 text-center shadow-sm"
        >
          <div
            class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
          >
            <svg
              class="h-6 w-6"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path d="M4 19.5V5.8A1.8 1.8 0 0 1 5.8 4H19v16H5.8A1.8 1.8 0 0 0 4 21.8" />
              <path d="M4 19.5A1.8 1.8 0 0 1 5.8 17H19" />
            </svg>
          </div>

          <h2
            class="text-base font-semibold text-[var(--sa-dark)]"
          >
            Your library is empty
          </h2>

          <p
            class="mx-auto mt-1.5 max-w-sm text-sm leading-6 text-[var(--sa-taupe)]"
          >
            Upload your first PDF manuscript to start building the Smart
            Adama learning library.
          </p>

          <button
            type="button"
            class="mt-5 rounded-full bg-[var(--sa-dark)] px-5 py-2.5 text-sm font-medium text-white transition hover:-translate-y-0.5 hover:shadow-md"
            @click="openUploadForm"
          >
            Upload your first book
          </button>
        </div>

        <div
          v-else-if="!filteredBooks.length"
          class="rounded-2xl border border-dashed border-[var(--sa-gray)] bg-white px-6 py-16 text-center shadow-sm"
        >
          <div
            class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500"
          >
            <svg
              class="h-6 w-6"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-4-4" />
            </svg>
          </div>

          <h2
            class="text-base font-semibold text-[var(--sa-dark)]"
          >
            No books found
          </h2>

          <p
            class="mx-auto mt-1.5 max-w-sm text-sm leading-6 text-[var(--sa-taupe)]"
          >
            Try changing your search or selecting a different status filter.
          </p>

          <button
            type="button"
            class="mt-5 rounded-full border border-[var(--sa-gray)] bg-white px-5 py-2.5 text-sm font-medium text-[var(--sa-dark)] transition hover:bg-slate-50"
            @click="clearFilters"
          >
            Clear filters
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════
             BOOK LIST
        ═══════════════════════════════════════════════════════ -->

        <div v-else class="space-y-3">
          <article
            v-for="book in filteredBooks"
            :key="book.id"
            class="group rounded-2xl border border-[var(--sa-gray)] bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:p-5"
          >
            <div class="flex items-center gap-3 sm:gap-4">

              <!-- Book Icon -->
              <div
                class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-[var(--sa-dark)] text-white shadow-sm sm:h-14 sm:w-14"
              >
                <svg
                  class="h-6 w-6 opacity-90 sm:h-7 sm:w-7"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <path d="M5 4.5A1.5 1.5 0 0 1 6.5 3H19v17H6.5A1.5 1.5 0 0 0 5 21.5z" />
                  <path d="M5 4.5v17M8.5 7H16M8.5 10H16M8.5 13H14" />
                </svg>

                <span
                  class="absolute -bottom-4 -right-4 h-10 w-10 rounded-full bg-white/10"
                />
              </div>

              <!-- Main info -->
              <div class="min-w-0 flex-1">
                <div
                  class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-2"
                >
                  <h2
                    class="truncate text-sm font-semibold text-[var(--sa-dark)] sm:text-base"
                    :title="book.title"
                  >
                    {{ book.title }}
                  </h2>

                  <!-- Mobile status -->
                  <span
                    :class="[
                      'inline-flex w-fit items-center gap-1.5 rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-wider ring-1 sm:hidden',
                      getBookStatus(book).class,
                    ]"
                  >
                    <span
                      :class="[
                        'h-1.5 w-1.5 rounded-full',
                        getBookStatus(book).dot,
                      ]"
                    />
                    {{ getBookStatus(book).label }}
                  </span>
                </div>

                <div
                  class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-[var(--sa-taupe)]"
                >
                  <span class="inline-flex items-center gap-1">
                    <svg
                      class="h-3.5 w-3.5"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.7"
                    >
                      <path d="M7 4h10v16H7z" />
                      <path d="M10 8h4M10 12h4M10 16h2" />
                    </svg>

                    {{ getChapterCount(book) }}
                    {{ getChapterCount(book) === 1 ? 'chapter' : 'chapters' }}
                  </span>

                  <span class="hidden text-slate-300 sm:inline">•</span>

                  <span>
                    {{ formatDate(getBookDate(book)) }}
                  </span>
                </div>
                
                <!-- Processing Metadata -->
                <div v-if="book.processing_metadata && book.processing_metadata.pages" class="mt-2 flex items-center gap-4 text-[10px] text-[var(--sa-taupe)]">
                  <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
                    </svg>
                    {{ book.processing_metadata.pages }} pages
                  </span>
                  
                  <span class="inline-flex items-center gap-1 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 7V4h16v3M9 20h6M12 4v16" />
                    </svg>
                    {{ (book.processing_metadata.cleaned_chars / 1000).toFixed(1) }}k / {{ (book.processing_metadata.original_chars / 1000).toFixed(1) }}k chars
                  </span>
                  
                  <span v-if="book.processing_metadata.suspicious_pages > 0" class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 px-2 py-0.5 rounded border border-amber-100 font-medium">
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01" />
                    </svg>
                    {{ book.processing_metadata.suspicious_pages }} suspicious pages
                  </span>
                </div>
                <div v-else-if="book.processing_metadata && book.processing_metadata.error" class="mt-2 text-[10px] text-red-600 font-medium bg-red-50 border border-red-100 px-2 py-1 rounded inline-block">
                  Error: {{ book.processing_metadata.error }}
                </div>
              </div>

              <!-- Desktop status -->
              <span
                :class="[
                  'hidden shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider ring-1 sm:inline-flex',
                  getBookStatus(book).class,
                ]"
              >
                <span
                  :class="[
                    'h-1.5 w-1.5 rounded-full',
                    getBookStatus(book).dot,
                    ['processing', 'pending', 'ingesting'].includes(
                      String(book.status || '').toLowerCase()
                    )
                      ? 'animate-pulse'
                      : '',
                  ]"
                />

                {{ getBookStatus(book).label }}
              </span>

              <!-- More action -->
              <button
                type="button"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-[var(--sa-dark)]"
                title="More options"
                aria-label="More options"
              >
                <svg
                  class="h-5 w-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <circle cx="5" cy="12" r="1" fill="currentColor" />
                  <circle cx="12" cy="12" r="1" fill="currentColor" />
                  <circle cx="19" cy="12" r="1" fill="currentColor" />
                </svg>
              </button>
            </div>

            <!-- Processing indicator -->
            <div
              v-if="
                ['processing', 'pending', 'ingesting'].includes(
                  String(book.status || '').toLowerCase()
                )
              "
              class="mt-4 border-t border-[var(--sa-gray)] pt-3"
            >
              <div class="flex items-center justify-between text-[11px]">
                <span class="text-[var(--sa-taupe)]">
                  Preparing content for the learning system…
                </span>

                <span class="font-medium text-amber-600">
                  Processing
                </span>
              </div>

              <div
                class="mt-2 h-1 overflow-hidden rounded-full bg-amber-100"
              >
                <div
                  class="h-full w-1/2 animate-[loading_1.5s_ease-in-out_infinite] rounded-full bg-amber-500"
                />
              </div>
            </div>
          </article>
        </div>

      </div>
  </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition:
    opacity 0.25s ease,
    transform 0.25s ease,
    max-height 0.3s ease;
  overflow: hidden;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

@keyframes loading {
  0% {
    transform: translateX(-100%);
  }

  100% {
    transform: translateX(300%);
  }
}

/* Keep search controls clean across browsers */
input[type='search']::-webkit-search-cancel-button {
  display: none;
}

input[type='search'] {
  -webkit-appearance: none;
  appearance: none;
}
</style>