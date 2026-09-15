import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { chatApi } from '@/api/chat'
import { apiBase } from '@/api/client'
import { useAuthStore } from './auth'
import { useChatStream, type ChatMessageStreamDone } from '@/composables/useChatStream'

export const useChatStore = defineStore('chat', () => {
  const CACHED_SESSIONS_KEY = 'smart_adama_cached_chat_sessions'

  let initialSessions: App.ChatSession[] = []
  try {
    const raw = localStorage.getItem(CACHED_SESSIONS_KEY)
    if (raw) initialSessions = JSON.parse(raw)
  } catch (e) {}

  const sessions        = ref<App.ChatSession[]>(initialSessions)
  const currentSession  = ref<App.ChatSession | null>(null)
  const meta            = ref<App.PaginationMeta | null>(null)
  const error           = ref<string | null>(null)

  // Expose the composable's reactive state and concurrent runner at store level
  const {
    streaming,
    activeStreams,
    accumulatedText,
    streamError,
    activityPayload,
    streamRequest,
    cancelStream,
    cancel,
  } = useChatStream()

  // Backwards-compatible streamingContent computed from currently active assistant message
  const streamingContent = computed(() => {
    const activeMsg = currentSession.value?.messages?.find(m => m.isStreaming)
    return activeMsg?.content || accumulatedText.value || ''
  })

  // ── Getters ────────────────────────────────────────────────────────────────
  const pinnedSessions = computed(() => sessions.value.filter(s => s.is_pinned && !s.is_archived))
  const activeSessions = computed(() => sessions.value.filter(s => !s.is_pinned && !s.is_archived))
  const archivedSessions = computed(() => sessions.value.filter(s => s.is_archived))

  // ── Session CRUD ───────────────────────────────────────────────────────────

  async function loadSessions(page = 1) {
    try {
      const { data } = await chatApi.listSessions(page)
      sessions.value = page === 1 ? data.sessions : [...sessions.value, ...data.sessions]
      meta.value     = data.meta
      if (page === 1 && data.sessions) {
        try {
          localStorage.setItem(CACHED_SESSIONS_KEY, JSON.stringify(data.sessions))
        } catch (e) {}
      }
    } catch (err) {
      console.error('Failed to load chat sessions:', err)
      if (page === 1 && sessions.value.length === 0) {
        sessions.value = []
      }
    }
  }

  async function createSession(title?: string): Promise<App.ChatSession> {
    const { data } = await chatApi.createSession(title)
    sessions.value.unshift(data.session)
    currentSession.value = data.session
    try {
      localStorage.setItem(CACHED_SESSIONS_KEY, JSON.stringify(sessions.value))
    } catch (e) {}
    return data.session
  }

  const pendingRequests = new Map<string, Promise<App.ChatSession>>()

  async function loadSession(sessionId: string): Promise<App.ChatSession> {
    if (currentSession.value?.id !== sessionId) {
      // Cancel active streams from old session when navigating away
      cancelAllStreams()
    }

    if (currentSession.value?.id === sessionId && (streaming.value || (currentSession.value?.messages && currentSession.value.messages.length > 0))) {
      return currentSession.value // Lock out stale DB fetches if already loaded in memory with messages or active stream
    }

    if (pendingRequests.has(sessionId)) {
      return pendingRequests.get(sessionId)!
    }

    const request = chatApi.getSession(sessionId).then(({ data }) => {
      currentSession.value = data.session
      return data.session
    }).finally(() => {
      pendingRequests.delete(sessionId)
    })

    pendingRequests.set(sessionId, request)
    return request
  }

  async function updateSession(sessionId: string, data: Partial<App.ChatSession>) {
    const { data: response } = await chatApi.updateSession(sessionId, data)
    const idx = sessions.value.findIndex((s) => s.id === sessionId)
    if (idx !== -1) sessions.value[idx] = response.session
    if (currentSession.value?.id === sessionId) currentSession.value = response.session
  }

  async function renameSession(sessionId: string, title: string) {
    await updateSession(sessionId, { title })
  }

  async function togglePinSession(session: App.ChatSession) {
    await updateSession(session.id, { is_pinned: !session.is_pinned })
  }

  async function toggleArchiveSession(session: App.ChatSession) {
    await updateSession(session.id, { is_archived: !session.is_archived, is_pinned: false })
  }

  async function deleteSession(sessionId: string) {
    if (currentSession.value?.id === sessionId) {
      cancelAllStreams()
    }
    await chatApi.deleteSession(sessionId)
    sessions.value = sessions.value.filter((s) => s.id !== sessionId)
    if (currentSession.value?.id === sessionId) currentSession.value = null
    try {
      localStorage.setItem(CACHED_SESSIONS_KEY, JSON.stringify(sessions.value))
    } catch (e) {}
  }

  async function deleteAllSessions() {
    cancelAllStreams()
    await chatApi.deleteAllSessions()
    sessions.value = []
    currentSession.value = null
    try {
      localStorage.removeItem(CACHED_SESSIONS_KEY)
    } catch (e) {}
  }

  // ── Message streaming with concurrency & discrete IDs ─────────────────────

  function sendMessage(
    sessionId: string,
    content: string,
    context?: any,
    onToken?: (token: string) => void,
    onDone?:  (payload: ChatMessageStreamDone) => void,
  ) {
    const authStore = useAuthStore()
    if (!authStore.token) throw new Error('Not authenticated')

    error.value = null

    const clientRequestId = `req_${crypto.randomUUID()}`
    const userMessageId   = `user_${crypto.randomUUID()}`
    const assistantMessageId = `asst_${crypto.randomUUID()}`

    // ── 1. Immediately append user message & assistant placeholder ─────────
    if (currentSession.value?.id === sessionId) {
      currentSession.value.messages ??= []

      // Append user message
      currentSession.value.messages.push({
        id:              userMessageId,
        chat_session_id: sessionId,
        role:            'user',
        content,
        created_at:      new Date().toISOString(),
        clientRequestId,
      })

      // Append assistant placeholder in strict chronological order
      currentSession.value.messages.push({
        id:              assistantMessageId,
        chat_session_id: sessionId,
        role:            'assistant',
        content:         '',
        created_at:      new Date().toISOString(),
        clientRequestId,
        isStreaming:     true,
        isPending:       true,
        activity:        null,
        sources:         [],
        error:           null,
      })
    }

    // ── 2. Launch SSE stream targeting assistant message discrete ID ───────
    const url = `${apiBase}/chat/sessions/${sessionId}/messages`

    const streamPromise = streamRequest({
      url,
      token: authStore.token,
      payload: { content, context },
      clientRequestId,
      onToken: (token) => {
        if (currentSession.value?.id === sessionId && currentSession.value.messages) {
          const msg = currentSession.value.messages.find(
            (m) => m.role === 'assistant' && (m.id === assistantMessageId || m.clientRequestId === clientRequestId)
          )
          if (msg) {
            msg.content += token
            msg.isPending = false
          }
        }
        onToken?.(token)
      },
      onActivity: (act) => {
        if (currentSession.value?.id === sessionId && currentSession.value.messages) {
          const msg = currentSession.value.messages.find(
            (m) => m.role === 'assistant' && (m.id === assistantMessageId || m.clientRequestId === clientRequestId)
          )
          if (msg) {
            msg.activity = act
          }
        }
      },
      onDone: (donePayload) => {
        if (currentSession.value?.id === sessionId && currentSession.value.messages) {
          const msg = currentSession.value.messages.find(
            (m) => m.role === 'assistant' && (m.id === assistantMessageId || m.clientRequestId === clientRequestId)
          )
          if (msg) {
            msg.id = donePayload.message_id || assistantMessageId
            msg.isStreaming = false
            msg.isPending = false
            msg.sources = donePayload.citations || []
            if (donePayload.html_content) {
              msg.content = donePayload.html_content
            }
          }
        }
        loadSessions(1).catch(() => {})
        onDone?.(donePayload)
      },
      onError: (errPayload) => {
        if (currentSession.value?.id === sessionId && currentSession.value.messages) {
          const msg = currentSession.value.messages.find(
            (m) => m.role === 'assistant' && (m.id === assistantMessageId || m.clientRequestId === clientRequestId)
          )
          if (msg) {
            msg.isStreaming = false
            msg.isPending = false
            msg.error = errPayload.error?.message || 'Failed to generate response'
          }
        }
        error.value = errPayload.error?.message || 'Stream error'
      },
    })

    return {
      clientRequestId,
      userMessageId,
      assistantMessageId,
      streamPromise,
    }
  }

  function cancelAllStreams() {
    cancelStream()
    if (currentSession.value?.messages) {
      for (const m of currentSession.value.messages) {
        if (m.isStreaming) {
          m.isStreaming = false
          m.isPending = false
        }
      }
    }
  }

  function cancelSingleStream(clientRequestId: string) {
    cancelStream(clientRequestId)
    if (currentSession.value?.messages) {
      const msg = currentSession.value.messages.find(m => m.clientRequestId === clientRequestId)
      if (msg) {
        msg.isStreaming = false
        msg.isPending = false
      }
    }
  }

  return {
    sessions,
    currentSession,
    meta,
    error,
    streaming,
    streamingContent,
    streamError,
    activityPayload,
    activeSessions,
    pinnedSessions,
    archivedSessions,
    loadSessions,
    createSession,
    loadSession,
    updateSession,
    renameSession,
    togglePinSession,
    toggleArchiveSession,
    deleteSession,
    deleteAllSessions,
    sendMessage,
    cancelStream,
    cancelAllStreams,
    cancelSingleStream,
  }
})
