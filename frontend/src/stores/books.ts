import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { booksApi } from '@/api/books'
import apiClient from '@/api/client'

export const useBooksStore = defineStore('books', () => {
  const CACHED_BOOKS_KEY = 'smart_adama_cached_books'
  const CACHED_CHAPTER_PREFIX = 'smart_adama_cached_chapter_'

  let initialBooks: any[] = []
  try {
    const rawBooks = localStorage.getItem(CACHED_BOOKS_KEY)
    if (rawBooks) initialBooks = JSON.parse(rawBooks)
  } catch (e) {}

  const books = ref<any[]>(initialBooks)
  const currentChapter = ref<any>(null)
  const currentQuiz = ref<any>(null)
  const bestAttempt = ref<any>(null)
  const chapterProgress = ref<any>(null)
  const loading = ref(false)
  const currentBook = computed(() => books.value[0] || null)

  // In-memory chapter cache for instant tab/chapter switching
  const chapterCache = new Map<string, { chapter: any; progress: any }>()

  async function loadBooks(force = false) {
    if (books.value.length > 0 && !force) {
      // Refresh in background if needed, but don't block
      booksApi.list().then((payload: any) => {
        const fetched = payload.books || payload.data?.books || payload.data || payload || []
        if (fetched.length) {
          books.value = fetched
          try { localStorage.setItem(CACHED_BOOKS_KEY, JSON.stringify(fetched)) } catch (e) {}
        }
      }).catch(() => {})
      return
    }
    loading.value = books.value.length === 0
    try {
      const payload = (await booksApi.list()) as any
      const fetched = payload.books || payload.data?.books || payload.data || payload || []
      books.value = fetched
      try { localStorage.setItem(CACHED_BOOKS_KEY, JSON.stringify(fetched)) } catch (e) {}
    } catch (error) {
      console.error('Failed to load books:', error)
      if (books.value.length === 0) books.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadChapter(chapterId: string) {
    if (!chapterId) return

    // 1. Instant Cache Hit (Memory Map): render immediately with zero delay
    if (chapterCache.has(chapterId)) {
      const cached = chapterCache.get(chapterId)!
      currentChapter.value = cached.chapter
      chapterProgress.value = cached.progress
    } else {
      // 2. Check localStorage cache
      try {
        const raw = localStorage.getItem(`${CACHED_CHAPTER_PREFIX}${chapterId}`)
        if (raw) {
          const cached = JSON.parse(raw)
          currentChapter.value = cached.chapter
          chapterProgress.value = cached.progress
          chapterCache.set(chapterId, cached)
        } else {
          loading.value = true
        }
      } catch (e) {
        loading.value = true
      }
    }

    try {
      const payload = (await booksApi.getChapter(chapterId)) as any
      const chapterData = payload.chapter || payload.data?.chapter || payload
      const progressData = payload.progress || payload.data?.progress || null

      currentChapter.value = chapterData
      chapterProgress.value = progressData
      chapterCache.set(chapterId, { chapter: chapterData, progress: progressData })
      try {
        localStorage.setItem(
          `${CACHED_CHAPTER_PREFIX}${chapterId}`,
          JSON.stringify({ chapter: chapterData, progress: progressData })
        )
      } catch (e) {}
    } catch (error) {
      console.error('Failed to load chapter:', error)
    } finally {
      loading.value = false
    }
  }

  async function markChapterRead(chapterId: string) {
    try {
      if (booksApi.markChapterRead) {
        await booksApi.markChapterRead(chapterId)
      } else {
        await apiClient.post(`/chapters/${chapterId}/read`)
      }
      console.log(`✅ Chapter ${chapterId} marked as read on backend!`)
    } catch (error) {
      console.error('Failed to mark chapter read on backend:', error)
    }
  }

  async function loadChapterQuiz(chapterId: string) {
    try {
      const payload = (await booksApi.getChapterQuiz(chapterId)) as any
      currentQuiz.value = payload.quiz || payload.data?.quiz || null
      bestAttempt.value = payload.best_attempt || payload.data?.best_attempt || null
    } catch (error) {
      currentQuiz.value = null
      bestAttempt.value = null
    }
  }

  return {
    books,
    currentBook,
    currentChapter,
    currentQuiz,
    bestAttempt,
    chapterProgress,
    loading,
    loadBooks,
    loadChapter,
    markChapterRead,
    loadChapterQuiz
  }
})