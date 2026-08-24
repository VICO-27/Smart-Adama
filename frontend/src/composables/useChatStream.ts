import { ref, type Ref } from 'vue'

/**
 * SSE Event types
 */
type SSEEvent = 'token' | 'complete' | 'activity' | 'error'

/**
 * SSE Event payloads
 */
interface TokenEvent {
  content: string
}

interface ActivityEvent {
  stage: string
  message: string
}

interface CompleteEvent {
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
type SSEPayload = TokenEvent | CompleteEvent | ActivityEvent | ErrorEvent

export function useChatStream() {
  const streaming = ref(false)
  const accumulatedText = ref('')
  const streamError = ref<string | null>(null)
  const activityPayload = ref<ActivityEvent | null>(null)
  const donePayload = ref<CompleteEvent | null>(null)
  const abortController = ref<AbortController | null>(null)

  async function stream(
    url: string,
    token: string,
    content: string,
    onToken?: (token: string) => void,
    onDone?: (payload: CompleteEvent) => void,
    onError?: (error: ErrorEvent) => void
  ): Promise<void> {
    if (abortController.value) {
      abortController.value.abort()
    }

    abortController.value = new AbortController()

    streaming.value = true
    accumulatedText.value = ''
    streamError.value = null
    activityPayload.value = null
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

        if (value) {
          buffer += decoder.decode(value, { stream: !done })
        }

        // Process blocks split by double newline (SSE standard)
        const events = buffer.split(/\n\n/)
        
        if (done) {
          // If the stream is done, treat whatever is left in the buffer as the final event
          events.push(events.pop() || '')
          buffer = ''
        } else {
          // Keep incomplete block in buffer
          buffer = events.pop() || '' 
        }

        for (const eventBlock of events) {
          if (!eventBlock.trim()) continue

          let eventType: SSEEvent = 'token'
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
            if (eventType === 'token') {
              accumulatedText.value += eventData.content
              onToken?.(eventData.content)
            } else if (eventType === 'activity') {
              activityPayload.value = eventData
            } else if (eventType === 'complete') {
              donePayload.value = eventData
              onDone?.(eventData)
            } else if (eventType === 'error') {
              streamError.value = eventData.error?.message
              onError?.(eventData)
            }
          }
        }
        
        if (done) {
          // If the stream finished without a complete event, synthesize one to prevent the text from vanishing
          if (!donePayload.value && accumulatedText.value) {
            const synthesizedComplete: CompleteEvent = {
              message_id: crypto.randomUUID(),
              grounded: false,
              citations: [],
              html_content: accumulatedText.value
            }
            donePayload.value = synthesizedComplete
            onDone?.(synthesizedComplete)
          }
          break
        }
      }
    } catch (error: any) {
      if (error.name === 'AbortError') {
        return
      }
      
      if (error.message === 'Failed to fetch' || error.name === 'TypeError') {
          streamError.value = 'Connection to AI timed out.'
      } else {
          streamError.value = error.message || 'Unknown error'
      }
      // If we already received partial text before the error, synthesize a complete event to preserve it
      if (accumulatedText.value && !donePayload.value) {
        const synthesizedComplete: CompleteEvent = {
          message_id: crypto.randomUUID(),
          grounded: false,
          citations: [],
          html_content: accumulatedText.value
        }
        donePayload.value = synthesizedComplete
        onDone?.(synthesizedComplete)
      }

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
    activityPayload,
    donePayload,
    stream,
    cancel,
  }
}

// ChatMessageStreamDone is an alias for CompleteEvent — exported so stores can import it
export type ChatMessageStreamDone = CompleteEvent
export type { SSEEvent, TokenEvent as DeltaEvent, CompleteEvent as DoneEvent, ErrorEvent, Citation }