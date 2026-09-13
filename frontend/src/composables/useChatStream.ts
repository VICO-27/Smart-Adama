import { ref, computed } from 'vue'

/**
 * SSE Event types
 */
export type SSEEvent = 'token' | 'complete' | 'activity' | 'error'

/**
 * SSE Event payloads
 */
export interface TokenEvent {
  content: string
}

export interface ActivityEvent {
  stage: string
  message: string
}

export interface CompleteEvent {
  message_id: string
  grounded: boolean
  citations: Citation[]
  html_content?: string
}

export interface ErrorEvent {
  error: {
    code: string
    message: string
  }
}

export interface Citation {
  chunk_id: string
  chapter_title: string
  section_title: string
  excerpt: string
  similarity: number
}

/**
 * Combined payload types
 */
export type SSEPayload = TokenEvent | CompleteEvent | ActivityEvent | ErrorEvent

export interface StreamRequestOptions {
  url: string
  token: string
  payload: any
  clientRequestId?: string
  onToken?: (token: string) => void
  onActivity?: (activity: ActivityEvent) => void
  onDone?: (payload: CompleteEvent) => void
  onError?: (error: ErrorEvent) => void
}

export function useChatStream() {
  const activeStreams = ref<Map<string, AbortController>>(new Map())
  const streaming = computed(() => activeStreams.value.size > 0)

  // Legacy state for backward compatibility
  const accumulatedText = ref('')
  const streamError = ref<string | null>(null)
  const activityPayload = ref<ActivityEvent | null>(null)
  const donePayload = ref<CompleteEvent | null>(null)

  async function streamRequest(options: StreamRequestOptions): Promise<void> {
    const {
      url,
      token,
      payload,
      clientRequestId = crypto.randomUUID(),
      onToken,
      onActivity,
      onDone,
      onError,
    } = options

    const controller = new AbortController()
    // Trigger reactivity on map mutation
    const newMap = new Map(activeStreams.value)
    newMap.set(clientRequestId, controller)
    activeStreams.value = newMap

    let streamAccumulated = ''
    let isComplete = false

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'text/event-stream',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
        signal: controller.signal,
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
          events.push(events.pop() || '')
          buffer = ''
        } else {
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
              streamAccumulated += eventData.content
              accumulatedText.value = streamAccumulated
              onToken?.(eventData.content)
            } else if (eventType === 'activity') {
              activityPayload.value = eventData
              onActivity?.(eventData)
            } else if (eventType === 'complete') {
              isComplete = true
              donePayload.value = eventData
              onDone?.(eventData)
            } else if (eventType === 'error') {
              streamError.value = eventData.error?.message || 'Stream error'
              onError?.(eventData)
            }
          }
        }

        if (done) {
          // If the stream finished without an explicit complete event, synthesize one to preserve content
          if (!isComplete && streamAccumulated) {
            const synthesizedComplete: CompleteEvent = {
              message_id: crypto.randomUUID(),
              grounded: false,
              citations: [],
              html_content: streamAccumulated,
            }
            isComplete = true
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

      const errorMessage = (error.message === 'Failed to fetch' || error.name === 'TypeError')
        ? 'Connection to AI timed out.'
        : (error.message || 'Unknown error')

      streamError.value = errorMessage

      // If we already received partial text before the error, synthesize a complete event to preserve it
      if (streamAccumulated && !isComplete) {
        const synthesizedComplete: CompleteEvent = {
          message_id: crypto.randomUUID(),
          grounded: false,
          citations: [],
          html_content: streamAccumulated,
        }
        isComplete = true
        donePayload.value = synthesizedComplete
        onDone?.(synthesizedComplete)
      }

      onError?.({ error: { code: 'STREAM_ERROR', message: errorMessage } })
    } finally {
      const updatedMap = new Map(activeStreams.value)
      updatedMap.delete(clientRequestId)
      activeStreams.value = updatedMap
    }
  }

  // Legacy stream wrapper for single-stream usage
  async function stream(
    url: string,
    token: string,
    payload: any,
    onToken?: (token: string) => void,
    onDone?: (payload: CompleteEvent) => void,
    onError?: (error: ErrorEvent) => void
  ): Promise<void> {
    const defaultRequestId = 'legacy-default'
    cancelStream(defaultRequestId)

    accumulatedText.value = ''
    streamError.value = null
    activityPayload.value = null
    donePayload.value = null

    return streamRequest({
      url,
      token,
      payload,
      clientRequestId: defaultRequestId,
      onToken,
      onDone,
      onError,
    })
  }

  function cancelStream(clientRequestId?: string) {
    if (clientRequestId) {
      const controller = activeStreams.value.get(clientRequestId)
      if (controller) {
        controller.abort()
        const updatedMap = new Map(activeStreams.value)
        updatedMap.delete(clientRequestId)
        activeStreams.value = updatedMap
      }
    } else {
      for (const controller of activeStreams.value.values()) {
        controller.abort()
      }
      activeStreams.value = new Map()
    }
  }

  function cancel() {
    cancelStream()
  }

  return {
    streaming,
    activeStreams,
    accumulatedText,
    streamError,
    activityPayload,
    donePayload,
    stream,
    streamRequest,
    cancelStream,
    cancel,
  }
}

// ChatMessageStreamDone is an alias for CompleteEvent — exported so stores can import it
export type ChatMessageStreamDone = CompleteEvent
export type { TokenEvent as DeltaEvent, CompleteEvent as DoneEvent }