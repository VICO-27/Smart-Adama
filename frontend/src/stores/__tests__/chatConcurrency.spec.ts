import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'

if (typeof globalThis.localStorage === 'undefined') {
  const store: Record<string, string> = {}
  globalThis.localStorage = {
    getItem: (k: string) => store[k] ?? null,
    setItem: (k: string, v: string) => { store[k] = v },
    removeItem: (k: string) => { delete store[k] },
    clear: () => { Object.keys(store).forEach(k => delete store[k]) },
    length: 0,
    key: () => null,
  }
}

import { useChatStore } from '../chat'
import { useAuthStore } from '../auth'

describe('Study Page AI Concurrency & Conversation Lifecycle', () => {
  function setMockFetch(fn: any) {
    globalThis.fetch = fn
    if (typeof window !== 'undefined') {
      window.fetch = fn
    }
  }

  beforeEach(() => {
    setActivePinia(createPinia())
    const authStore = useAuthStore()
    authStore.token = 'test-bearer-token'
  })

  it('immediately appends user message and assistant placeholder with discrete IDs on submit', async () => {
    const mockFetch = vi.fn().mockResolvedValue({
      ok: true,
      body: {
        getReader() {
          let called = false
          return {
            read() {
              if (!called) {
                called = true
                const encoder = new TextEncoder()
                return Promise.resolve({
                  done: false,
                  value: encoder.encode('event: token\ndata: {"content": "Hello world"}\n\n'),
                })
              }
              return Promise.resolve({ done: true, value: undefined })
            },
          }
        },
      },
    })
    setMockFetch(mockFetch)

    const chatStore = useChatStore()
    chatStore.currentSession = {
      id: 'session-1',
      title: 'Test Session',
      is_pinned: false,
      is_archived: false,
      last_activity_at: new Date().toISOString(),
      created_at: new Date().toISOString(),
      messages: [],
    }

    // Messages count before
    expect(chatStore.currentSession.messages?.length).toBe(0)

    // Send message
    const exchange = chatStore.sendMessage('session-1', 'What is Smart Adama?')

    // Verify immediate state integrity
    expect(exchange).toBeDefined()
    expect(exchange.clientRequestId).toMatch(/^req_/)
    expect(exchange.userMessageId).toMatch(/^user_/)
    expect(exchange.assistantMessageId).toMatch(/^asst_/)

    const messages = chatStore.currentSession.messages!
    expect(messages.length).toBe(2)

    // User message checks
    expect(messages[0].id).toBe(exchange.userMessageId)
    expect(messages[0].role).toBe('user')
    expect(messages[0].content).toBe('What is Smart Adama?')
    expect(messages[0].clientRequestId).toBe(exchange.clientRequestId)

    // Assistant placeholder checks
    expect(messages[1].id).toBe(exchange.assistantMessageId)
    expect(messages[1].role).toBe('assistant')
    expect(messages[1].content).toBe('')
    expect(messages[1].clientRequestId).toBe(exchange.clientRequestId)
    expect(messages[1].isStreaming).toBe(true)
    expect(messages[1].isPending).toBe(true)

    // Wait for the mock stream to finish
    await exchange.streamPromise

    // After stream finishes: token received and processed
    expect(messages[1].content).toBe('Hello world')
    expect(messages[1].isStreaming).toBe(false)
    expect(messages[1].isPending).toBe(false)
  })

  it('supports sending 2-3 questions in rapid succession with independent stream targeting and chronological ordering', async () => {
    // We will simulate 3 streams with deferred resolvers to test concurrent streaming
    const streamResolvers: Array<{
      pushChunk: (chunk: string) => void
      finish: () => void
    }> = []

    setMockFetch(vi.fn().mockImplementation((url, options) => {
      let listener: ((res: { done: boolean; value?: Uint8Array }) => void) | null = null
      const queue: Array<(l: (res: any) => void) => void> = []

      const streamController = {
        pushChunk: (text: string) => {
          const fn = (l: (res: any) => void) => {
            const encoder = new TextEncoder()
            l({
              done: false,
              value: encoder.encode(`event: token\ndata: {"content": "${text}"}\n\n`),
            })
          }
          if (listener) {
            const l = listener
            listener = null
            fn(l)
          } else {
            queue.push(fn)
          }
        },
        finish: () => {
          const fn = (l: (res: any) => void) => {
            l({ done: true, value: undefined })
          }
          if (listener) {
            const l = listener
            listener = null
            fn(l)
          } else {
            queue.push(fn)
          }
        },
      }
      streamResolvers.push(streamController)

      return Promise.resolve({
        ok: true,
        body: {
          getReader() {
            return {
              read() {
                if (queue.length > 0) {
                  const next = queue.shift()!
                  return new Promise((resolve) => {
                    next(resolve)
                  })
                }
                return new Promise((resolve) => {
                  listener = resolve
                })
              },
            }
          },
        },
      })
    }))

    const chatStore = useChatStore()
    chatStore.currentSession = {
      id: 'session-multi',
      title: 'Multi-Question Session',
      is_pinned: false,
      is_archived: false,
      last_activity_at: new Date().toISOString(),
      created_at: new Date().toISOString(),
      messages: [],
    }

    // Rapidly submit 3 questions without waiting for previous to finish
    const ex1 = chatStore.sendMessage('session-multi', 'Question 1')
    const ex2 = chatStore.sendMessage('session-multi', 'Question 2')
    const ex3 = chatStore.sendMessage('session-multi', 'Question 3')

    const messages = chatStore.currentSession.messages!
    // 3 exchanges = 6 messages total in strict chronological order
    expect(messages.length).toBe(6)

    // Check chronological order
    expect(messages[0].id).toBe(ex1.userMessageId)
    expect(messages[0].content).toBe('Question 1')
    expect(messages[1].id).toBe(ex1.assistantMessageId)
    expect(messages[1].isStreaming).toBe(true)

    expect(messages[2].id).toBe(ex2.userMessageId)
    expect(messages[2].content).toBe('Question 2')
    expect(messages[3].id).toBe(ex2.assistantMessageId)
    expect(messages[3].isStreaming).toBe(true)

    expect(messages[4].id).toBe(ex3.userMessageId)
    expect(messages[4].content).toBe('Question 3')
    expect(messages[5].id).toBe(ex3.assistantMessageId)
    expect(messages[5].isStreaming).toBe(true)

    // All discrete IDs must be unique
    const allIds = messages.map(m => m.id)
    const uniqueIds = new Set(allIds)
    expect(uniqueIds.size).toBe(6)

    // Now emit tokens in non-sequential order to verify discrete ID targeting:
    // Stream 2 emits token first
    streamResolvers[1].pushChunk('Answer to Q2')
    await new Promise(resolve => setTimeout(resolve, 20))

    // Stream 1 emits token
    streamResolvers[0].pushChunk('Answer to Q1')
    await new Promise(resolve => setTimeout(resolve, 20))

    // Stream 3 emits token
    streamResolvers[2].pushChunk('Answer to Q3')
    await new Promise(resolve => setTimeout(resolve, 20))

    // Verify stream chunks targeted their specific assistant message!
    expect(messages[1].content).toBe('Answer to Q1')
    expect(messages[3].content).toBe('Answer to Q2')
    expect(messages[5].content).toBe('Answer to Q3')

    // Close streams
    streamResolvers[0].finish()
    streamResolvers[1].finish()
    streamResolvers[2].finish()

    await Promise.all([ex1.streamPromise, ex2.streamPromise, ex3.streamPromise])

    expect(messages[1].isStreaming).toBe(false)
    expect(messages[3].isStreaming).toBe(false)
    expect(messages[5].isStreaming).toBe(false)
  })

  it('correctly reports streaming status and cancels active streams', async () => {
    let aborted = false
    setMockFetch(vi.fn().mockImplementation((url, options) => {
      options.signal?.addEventListener('abort', () => {
        aborted = true
      })
      return new Promise(() => {}) // never resolves
    }))

    const chatStore = useChatStore()
    chatStore.currentSession = {
      id: 'session-cancel',
      title: 'Cancel Session',
      is_pinned: false,
      is_archived: false,
      last_activity_at: new Date().toISOString(),
      created_at: new Date().toISOString(),
      messages: [],
    }

    const ex = chatStore.sendMessage('session-cancel', 'Will be cancelled')
    expect(chatStore.streaming).toBe(true)

    chatStore.cancelAllStreams()
    expect(chatStore.streaming).toBe(false)
    expect(chatStore.currentSession.messages![1].isStreaming).toBe(false)
    expect(aborted).toBe(true)
  })
})
