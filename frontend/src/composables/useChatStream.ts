import { ref, type Ref } from 'vue'

/**
 * SSE Event types
 */
type SSEEvent = 'delta' | 'done' | 'error'

/**
 * SSE Event payloads
 */
interface DeltaEvent {
  token: string
}

interface DoneEvent {
  message_id: string
  grounded: boolean
  citations: Citation[]
  html_content?: string
}

interface ErrorEvent {
  error: {
    code: string
    message: string
  }
}

interface Citation {
  chunk_id: string
  chapter_title: string
  section_title: string
  excerpt: string
  similarity: number
}

/**
 * Combined payload types
 */
type SSEPayload = DeltaEvent | DoneEvent | ErrorEvent

export function useChatStream() {
  const streaming = ref(false)
  const accumulatedText = ref('')
  const streamError = ref<string | null>(null)
  const donePayload = ref<DoneEvent | null>(null)
  const abortController = ref<AbortController | null>(null)

  async function stream(
    url: string,
    token: string,
    content: string,
    onToken?: (token: string) => void,
    onDone?: (payload: DoneEvent) => void,
    onError?: (error: ErrorEvent) => void
  ): Promise<void> {
    if (abortController.value) {
      abortController.value.abort()
    }

    abortController.value = new AbortController()

    streaming.value = true
    accumulatedText.value = ''
    streamError.value = null
    donePayload.value = null

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({ content }),
        signal: abortController.value.signal,
      })

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        throw new Error(errorData.message || `HTTP ${response.status}`)
      }

      if (!response.body) {
        throw new Error('Response body is null')
      }

      const reader = response.body.getReader()
      const decoder = new TextDecoder('utf-8')
      let buffer = ''

      while (true) {
        const { done, value } = await reader.read()

        if (done) {
          break
        }

        buffer += decoder.decode(value, { stream: true })

        // Process blocks split by double newline (SSE standard)
        const events = buffer.split(/\n\n/)
        // Keep incomplete block in buffer
        buffer = events.pop() || '' 

        for (const eventBlock of events) {
          if (!eventBlock.trim()) continue

          let eventType: SSEEvent = 'delta'
          let eventData: any = null

          const lines = eventBlock.split('\n')
          for (const line of lines) {
            if (line.startsWith('event:')) {
              eventType = line.substring(6).trim() as SSEEvent
            } else if (line.startsWith('data:')) {
              const dataStr = line.substring(5).trim()
              if (dataStr) {
                try {
                  eventData = JSON.parse(dataStr)
                } catch {
                  // Ignore JSON parse errors on partial streams
                }
              }
            }
          }

          if (eventData) {
            if (eventType === 'delta') {
              accumulatedText.value += eventData.token
              onToken?.(eventData.token)
            } else if (eventType === 'done') {
              donePayload.value = eventData
              onDone?.(eventData)
            } else if (eventType === 'error') {
              streamError.value = eventData.error?.message
              onError?.(eventData)
            }
          }
        }
      }
    } catch (error: any) {
      if (error.name === 'AbortError') {
        return
      }
      
      streamError.value = error.message || 'Unknown error'
      onError?.({ error: { code: 'STREAM_ERROR', message: streamError.value } })
    } finally {
      streaming.value = false
    }
  }

  function cancel() {
    if (abortController.value) {
      abortController.value.abort()
    }
  }

  return {
    streaming,
    accumulatedText,
    streamError,
    donePayload,
    stream,
    cancel,
  }
}

// ChatMessageStreamDone is an alias for DoneEvent — exported so stores can import it
export type ChatMessageStreamDone = DoneEvent
export type { SSEEvent, DeltaEvent, DoneEvent, ErrorEvent, Citation }