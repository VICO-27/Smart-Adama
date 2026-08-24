<template>
  <div class="global-assistant" :class="{ 'assistant-open': isOpen }">
    <!-- =====================================================
         ASSISTANT PANEL
    ====================================================== -->
    <Transition name="assistant-panel">
      <section
        v-if="isOpen"
        ref="panelRef"
        class="assistant-panel"
        :class="[
          `assistant-panel--${panelPlacement}`,
          { 'assistant-panel--mobile': isMobile }
        ]"
        role="dialog"
        aria-modal="false"
        aria-label="Smart Adama Assistant"
      >
        <!-- HEADER -->
        <header class="assistant-header">
          <div class="assistant-header-main">
            <button
              v-if="mode !== 'hub'"
              type="button"
              class="icon-button"
              aria-label="Go back"
              @click="goBack"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>

            <div class="assistant-brand-mark" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65">
                <circle cx="12" cy="12" r="3" />
                <circle cx="5.5" cy="7.5" r="1.15" />
                <circle cx="18.5" cy="7.5" r="1.15" />
                <circle cx="5.5" cy="16.5" r="1.15" />
                <circle cx="18.5" cy="16.5" r="1.15" />
                <path d="M9.5 10 6.5 8.35M14.5 10l3-1.65M9.5 14l-3 1.65M14.5 14l3 1.65" />
              </svg>
            </div>

            <div class="assistant-title-wrap">
              <div class="assistant-title-row">
                <h2>
                  {{ mode === 'hub' ? 'Smart Adama' : mode === 'help' ? 'Quick Help' : 'Learning Assistant' }}
                </h2>

                <span v-if="mode === 'ai'" class="context-badge">
                  {{ contextLabel }}
                </span>
              </div>

              <p v-if="mode === 'hub'">Your guide across the Smart Adama platform.</p>
              <p v-else-if="mode === 'help'">Useful answers for the page you are viewing.</p>
              <p v-else>Ask, explore, and continue the conversation.</p>
            </div>
          </div>

          <div class="assistant-header-actions">
            <button
              v-if="mode === 'ai' && messages.length"
              type="button"
              class="header-action"
              @click="resetConversation"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 7h16M9 7v-2h6v2M7 7l1 13h8l1-13M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              New
            </button>

            <button
              type="button"
              class="icon-button"
              aria-label="Close assistant"
              @click="closeAssistant"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
              </svg>
            </button>
          </div>
        </header>

        <!-- ===================================================
             HUB
        ==================================================== -->
        <div v-if="mode === 'hub'" class="assistant-body hub-body">
          <div class="hub-hero">
            <div class="hub-visual" aria-hidden="true">
              <span class="hub-orbit hub-orbit--one"></span>
              <span class="hub-orbit hub-orbit--two"></span>
              <span class="hub-core">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <circle cx="12" cy="12" r="3" />
                  <path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.64 5.64l2.83 2.83M15.53 15.53l2.83 2.83M18.36 5.64l-2.83 2.83M8.47 15.53l-2.83 2.83" />
                </svg>
              </span>
            </div>

            <div class="hub-copy">
              <span class="section-kicker">Smart Adama Assistant</span>
              <h3>What do you need help with?</h3>
              <p>
                Move between guidance, help, and a full AI conversation without leaving the page.
              </p>
            </div>
          </div>

          <div class="hub-actions">
            <button type="button" class="hub-option" @click="navigateToAbout">
              <span class="option-icon option-icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                  <circle cx="12" cy="8" r="3.25" />
                  <path d="M5.5 20a6.5 6.5 0 0 1 13 0" />
                </svg>
              </span>
              <span class="option-copy">
                <strong>Meet the developers</strong>
                <span>See the story behind Smart Adama.</span>
              </span>
              <svg class="option-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>

            <button type="button" class="hub-option" @click="mode = 'help'">
              <span class="option-icon option-icon--soft">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                  <circle cx="12" cy="12" r="8.75" />
                  <path d="M9.8 9a2.35 2.35 0 1 1 4.2 1.45c-.7.9-2 1.1-2 2.55" stroke-linecap="round" />
                  <path d="M12 16.5h.01" stroke-linecap="round" />
                </svg>
              </span>
              <span class="option-copy">
                <strong>Quick help</strong>
                <span>Get answers based on this page.</span>
              </span>
              <svg class="option-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>

            <button type="button" class="ai-entry" @click="openAi">
              <span class="ai-entry-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                  <circle cx="12" cy="12" r="3" />
                  <circle cx="6" cy="7" r="1.1" />
                  <circle cx="18" cy="7" r="1.1" />
                  <circle cx="6" cy="17" r="1.1" />
                  <circle cx="18" cy="17" r="1.1" />
                  <path d="M9.5 10 7 8.7M14.5 10 17 8.7M9.5 14 7 15.3M14.5 14l2.5 1.3" />
                </svg>
              </span>
              <span class="ai-entry-copy">
                <strong>Open learning assistant</strong>
                <span>Ask follow-up questions and keep the conversation going.</span>
              </span>
              <span class="ai-entry-key">⌘</span>
            </button>
          </div>

          <!-- THEME TOGGLES -->
          <div class="theme-switcher" aria-label="Theme switcher">
            <button 
              @click="setTheme('light')" 
              class="theme-btn" 
              :class="{ 'theme-btn--active': themePreference === 'light' }"
            >Light</button>
            <button 
              @click="setTheme('dark')" 
              class="theme-btn" 
              :class="{ 'theme-btn--active': themePreference === 'dark' }"
            >Dark</button>
            <button 
              @click="setTheme('system')" 
              class="theme-btn" 
              :class="{ 'theme-btn--active': themePreference === 'system' }"
            >System</button>
          </div>
        </div>

        <!-- ===================================================
             HELP
        ==================================================== -->
        <div v-else-if="mode === 'help'" class="assistant-body help-body">
          <div class="help-intro">
            <span class="section-kicker">Relevant to this page</span>
            <h3>Quick answers before you ask AI.</h3>
          </div>

          <div class="faq-list">
            <details
              v-for="(item, idx) in contextualFaqs"
              :key="idx"
              class="faq-item"
            >
              <summary>
                <span>{{ item.q }}</span>
                <span class="faq-plus">+</span>
              </summary>
              <div class="faq-answer">
                {{ item.a }}
              </div>
            </details>
          </div>

          <div class="help-footer">
            <span>Still need a deeper answer?</span>
            <button type="button" class="help-ai-button" @click="openAi">
              Ask the learning assistant
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M5 12h14M13 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </div>
        </div>

        <!-- ===================================================
             AI CHAT
        ==================================================== -->
        <div v-else class="assistant-body chat-body">
          <div ref="chatContainer" class="chat-scroll">
            <div v-if="messages.length === 0" class="chat-empty">
              <div class="chat-empty-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <circle cx="12" cy="12" r="3" />
                  <circle cx="5.5" cy="7.5" r="1.1" />
                  <circle cx="18.5" cy="7.5" r="1.1" />
                  <circle cx="5.5" cy="16.5" r="1.1" />
                  <circle cx="18.5" cy="16.5" r="1.1" />
                  <path d="m9.5 10-3-1.6M14.5 10l3-1.6M9.5 14l-3 1.6M14.5 14l3 1.6" />
                </svg>
              </div>

              <span class="section-kicker">Ready when you are</span>
              <h3>What would you like to understand?</h3>
              <p>
                I can explain concepts, summarize content, guide you through the platform, or answer a follow-up question.
              </p>

              <div class="suggestion-grid">
                <button
                  v-for="prompt in suggestedPrompts"
                  :key="prompt"
                  type="button"
                  class="suggestion-chip"
                  @click="sendSuggested(prompt)"
                >
                  {{ prompt }}
                </button>
              </div>
            </div>

            <div
              v-for="(msg, index) in messages"
              :key="index"
              class="message-row"
              :class="msg.role === 'user' ? 'message-row--user' : 'message-row--assistant'"
            >
              <div
                v-if="msg.role === 'ai'"
                class="message-avatar"
                aria-hidden="true"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55">
                  <circle cx="12" cy="12" r="3" />
                  <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                </svg>
              </div>

              <article
                class="message-bubble"
                :class="msg.role === 'user' ? 'message-bubble--user' : 'message-bubble--assistant'"
              >
                <div
                  v-if="msg.role === 'ai'"
                  class="message-content ai-formatted-response"
                  v-html="parseMarkdown(msg.content)"
                ></div>
                <div
                  v-else
                  class="message-content message-content--user"
                >
                  {{ msg.content }}
                </div>

                <div
                  v-if="msg.role === 'ai'"
                  class="message-actions"
                >
                  <button type="button" @click="copyMessage(msg.content)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <rect x="9" y="9" width="11" height="11" rx="2" />
                      <path d="M6 15H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1" />
                    </svg>
                    {{ copiedMessageIndex === index ? 'Copied' : 'Copy' }}
                  </button>
                </div>
              </article>
            </div>

            <div v-if="isThinking" class="message-row message-row--assistant">
              <div class="message-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.55">
                  <circle cx="12" cy="12" r="3" />
                  <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                </svg>
              </div>

              <div class="thinking-bubble">
                <span></span>
                <span></span>
                <span></span>
                <em>Thinking</em>
              </div>
            </div>
          </div>

          <div class="composer-wrap">
            <div class="composer-shell">
              <textarea
                ref="inputRef"
                v-model="userInput"
                rows="1"
                autocomplete="off"
                :placeholder="isMobile ? 'Ask Smart Adama…' : 'Ask Smart Adama anything…'"
                @keydown="handleKeydown"
                @input="autoResizeTextarea"
              ></textarea>

              <div class="composer-bottom">
                <div class="composer-hint">
                  <span class="composer-context-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                      <circle cx="12" cy="12" r="8.5" />
                      <path d="M12 8v4l2.5 2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </span>
                  <span>Enter to send · Shift+Enter for a new line</span>
                </div>

                <button
                  type="button"
                  class="send-button"
                  :class="{
                    'send-button--active': userInput.trim() && !isThinking,
                  }"
                  :disabled="!userInput.trim() || isThinking"
                  aria-label="Send message"
                  @click="sendMessage"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <path d="M4 5.5 20 12 4 18.5l2.2-6.5L4 5.5Z" stroke-linejoin="round" />
                    <path d="M6.2 12H20" stroke-linecap="round" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </Transition>

    <!-- =====================================================
         FLOATING ASSISTANT BUTTON
    ====================================================== -->
    <button
      ref="toggleButtonRef"
      type="button"
      class="assistant-toggle"
      :class="{
        'assistant-toggle--open': isOpen,
        'assistant-toggle--dragging': isDragging,
      }"
      :style="toggleStyle"
      aria-label="Toggle Smart Adama Assistant"
      :aria-expanded="isOpen"
      draggable="false"
      @pointerdown.stop.prevent="onPointerDown"
      @pointermove.stop.prevent="onPointerMove"
      @pointerup.stop.prevent="onPointerUp"
      @pointercancel.stop.prevent="onPointerUp"
      @keydown.enter.prevent="toggleAssistant"
      @keydown.space.prevent="toggleAssistant"
    >
      <span v-if="!isOpen" class="toggle-halo" aria-hidden="true"></span>

      <span class="toggle-icon" aria-hidden="true">
        <svg
          v-if="!isOpen"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
        >
          <circle cx="12" cy="12" r="3" />
          <circle cx="5.5" cy="7.5" r="1.05" />
          <circle cx="18.5" cy="7.5" r="1.05" />
          <circle cx="5.5" cy="16.5" r="1.05" />
          <circle cx="18.5" cy="16.5" r="1.05" />
          <path d="m9.5 10-3-1.6M14.5 10l3-1.6M9.5 14l-3 1.6M14.5 14l3 1.6" />
        </svg>

        <svg
          v-else
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.9"
        >
          <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
        </svg>
      </span>

      <span
        v-if="!isOpen && !isDragging && !isMobile"
        class="toggle-tooltip"
        :class="pos.x > windowWidth / 2 ? 'toggle-tooltip--left' : 'toggle-tooltip--right'"
      >
        Ask Smart Adama
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import { v4 as uuidv4 } from 'uuid'
import { aiApi } from '@/api/ai'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'

// DOMPurify Hook to add target="_blank" to parsed links
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if (node.tagName === 'A') {
    node.setAttribute('target', '_blank')
    node.setAttribute('rel', 'noopener noreferrer')
  }
})

const auth = useAuthStore()
const { themePreference, setTheme } = useTheme()


type AssistantMode = 'hub' | 'help' | 'ai'
type Message = {
  role: 'user' | 'ai'
  content: string
}


/* ============================================================
   ROUTING / AUTH
============================================================ */

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()


/* ============================================================
   STATE
============================================================ */

const isOpen = ref(false)
const mode = ref<AssistantMode>('hub')
const isThinking = ref(false)
const userInput = ref('')
const messages = ref<Message[]>([])
const copiedMessageIndex = ref<number | null>(null)

const panelRef = ref<HTMLElement | null>(null)
const inputRef = ref<HTMLTextAreaElement | null>(null)
const chatContainer = ref<HTMLElement | null>(null)
const toggleButtonRef = ref<HTMLElement | null>(null)


/* ============================================================
   MARKDOWN
============================================================ */

marked.setOptions({
  breaks: true,
  gfm: true,
})


const parseMarkdown = (rawText: string) => {
  let processed = rawText
  
  processed = processed.replace(/<think>([\s\S]*?)<\/think>/g, '<details class="thinking-block"><summary>Thinking Process...</summary>\n\n$1\n\n</details>')
  
  if (processed.includes('<think>') && !processed.includes('</think>')) {
      processed = processed.replace(/<think>([\s\S]*)$/g, '<details class="thinking-block" open><summary>Thinking...</summary>\n\n$1\n\n</details>')
  }
  
  return marked.parse(processed) as string
}


/* ============================================================
   RESPONSIVE / POSITION
============================================================ */

const windowWidth = ref(
  typeof window !== 'undefined'
    ? window.innerWidth
    : 1280,
)

const windowHeight = ref(
  typeof window !== 'undefined'
    ? window.innerHeight
    : 800,
)

const isMobile = computed(() => {
  return windowWidth.value < 768
})

const pos = ref({
  x: Math.max(
    16,
    windowWidth.value - 80,
  ),
  y: Math.max(
    16,
    windowHeight.value - 88,
  ),
})

const isDragging = ref(false)

let hasMoved = false
let startPoint = {
  x: 0,
  y: 0,
}
let startPos = {
  x: 0,
  y: 0,
}

const toggleStyle = computed(() => ({
  left: `${pos.value.x}px`,
  top: `${pos.value.y}px`,
}))

const panelPlacement = computed(() => {
  if (isMobile.value) {
    return 'mobile'
  }

  const horizontal =
    pos.value.x <
    windowWidth.value / 2
      ? 'left'
      : 'right'

  const vertical =
    pos.value.y <
    windowHeight.value / 2
      ? 'top'
      : 'bottom'

  return `${vertical}-${horizontal}`
})


/* ============================================================
   CONTEXT
============================================================ */

const contextLabel = computed(() => {
  const path = route.path

  if (path.includes('/study') || path.includes('/chapter')) {
    return 'Study'
  }

  if (path.includes('/dashboard')) {
    return 'Dashboard'
  }

  if (path.includes('/profile')) {
    return 'Profile'
  }

  if (path.includes('/game')) {
    return 'Game'
  }

  return 'Smart Adama'
})


const contextualFaqs = computed(() => {
  const path = route.path

  if (
    path.includes('/study') ||
    path.includes('/chapter')
  ) {
    return [
      {
        q: 'How does Study Mode work?',
        a: 'Study Mode lets you read the Smart Adama content, track your progress, and ask the assistant about the material you are viewing.',
      },
      {
        q: 'How do chapter quizzes work?',
        a: 'Chapter quizzes are used to assess your understanding. Your progress and achievement state are updated from the learning system.',
      },
      {
        q: 'Why might my progress not appear immediately?',
        a: 'Progress is tied to the learning actions recorded by the application. If a change does not appear, refresh the page and confirm that the action was completed successfully.',
      },
    ]
  }

  if (path.includes('/dashboard')) {
    return [
      {
        q: 'What does my learning progress show?',
        a: 'The dashboard summarizes your completed chapters, overall progress, quiz performance, learning streak, and achievements.',
      },
      {
        q: 'How do I earn badges?',
        a: 'Badges are connected to learning milestones such as completing chapters, maintaining consistency, and performing well in assessments.',
      },
      {
        q: 'What should I study next?',
        a: 'Use the Continue Learning section on the dashboard to return to the next chapter in your learning journey.',
      },
    ]
  }

  if (path.includes('/profile')) {
    return [
      {
        q: 'How do I change my profile picture?',
        a: 'Open your profile settings and use the profile picture control to upload a new image.',
      },
      {
        q: 'How does dark mode work?',
        a: 'Appearance is controlled through the application-wide theme system, so the selected theme is shared across Smart Adama pages.',
      },
      {
        q: 'Where can I update my account information?',
        a: 'Use the Personal Information section of your profile page to update your account details.',
      },
    ]
  }

  return [
    {
      q: 'How do I get started?',
      a: 'Open the Dashboard to see your progress, or go to Study to begin learning from the Smart Adama content.',
    },
    {
      q: 'How does the assistant work?',
      a: 'The assistant receives the current route and your conversation history so it can provide more relevant answers within the application.',
    },
    {
      q: 'Where can I change my account settings?',
      a: 'Open your Profile page from the navigation bar to manage your profile, security, preferences, and appearance.',
    },
  ]
})


const suggestedPrompts = computed(() => {
  const path = route.path

  if (
    path.includes('/study') ||
    path.includes('/chapter')
  ) {
    return [
      'Summarize this chapter',
      'Explain the key concepts',
      'Quiz me on this topic',
    ]
  }

  if (path.includes('/dashboard')) {
    return [
      'How can I improve my score?',
      'What should I study next?',
      'Explain my progress',
    ]
  }

  if (path.includes('/profile')) {
    return [
      'How do I improve my profile?',
      'How does dark mode work?',
      'Help me manage my account',
    ]
  }

  return [
    'What is Smart Adama?',
    'How does the platform work?',
    'What should I explore first?',
  ]
})


/* ============================================================
   OPEN / CLOSE
============================================================ */

const openAi = async () => {
  mode.value = 'ai'
  await nextTick()
  inputRef.value?.focus()
  scrollToBottom()
}


const closeAssistant = () => {
  isOpen.value = false
}


const goBack = () => {
  if (mode.value === 'ai') {
    mode.value = 'hub'
    return
  }

  if (mode.value === 'help') {
    mode.value = 'hub'
  }
}


const toggleAssistant = () => {
  if (isDragging.value) {
    return
  }

  isOpen.value = !isOpen.value

  if (!isOpen.value) {
    mode.value = 'hub'
  }
}


/* ============================================================
   DRAGGING
============================================================ */

const clampTogglePosition = (
  x: number,
  y: number,
) => {
  const size = isMobile.value
    ? 56
    : 64

  return {
    x: Math.max(
      12,
      Math.min(
        windowWidth.value - size - 12,
        x,
      ),
    ),
    y: Math.max(
      12,
      Math.min(
        windowHeight.value - size - 12,
        y,
      ),
    ),
  }
}


const onPointerDown = (
  event: PointerEvent,
) => {
  hasMoved = false

  startPoint = {
    x: event.clientX,
    y: event.clientY,
  }

  startPos = {
    x: pos.value.x,
    y: pos.value.y,
  }

  const target =
    event.currentTarget as HTMLElement

  target.setPointerCapture(
    event.pointerId,
  )
}


const onPointerMove = (
  event: PointerEvent,
) => {
  if (!event.buttons) {
    return
  }

  const dx =
    event.clientX - startPoint.x

  const dy =
    event.clientY - startPoint.y

  if (
    Math.abs(dx) > 3 ||
    Math.abs(dy) > 3
  ) {
    hasMoved = true
    isDragging.value = true
  }

  if (!hasMoved) {
    return
  }

  const next = clampTogglePosition(
    startPos.x + dx,
    startPos.y + dy,
  )

  pos.value = next
}


const onPointerUp = (
  event: PointerEvent,
) => {
  const target =
    event.currentTarget as HTMLElement

  if (
    target.hasPointerCapture(
      event.pointerId,
    )
  ) {
    target.releasePointerCapture(
      event.pointerId,
    )
  }

  isDragging.value = false

  if (hasMoved) {
    if (!isMobile.value) {
      pos.value.x =
        pos.value.x <
        windowWidth.value / 2
          ? 16
          : windowWidth.value - 80
    }
  } else {
    toggleAssistant()
  }

  hasMoved = false
}


/* ============================================================
   RESIZE / ESCAPE
============================================================ */

const handleResize = () => {
  windowWidth.value =
    window.innerWidth

  windowHeight.value =
    window.innerHeight

  pos.value = clampTogglePosition(
    pos.value.x,
    pos.value.y,
  )
}


const handleEscape = (
  event: KeyboardEvent,
) => {
  if (event.key !== 'Escape') {
    return
  }

  if (isOpen.value) {
    closeAssistant()
  }
}


/* ============================================================
   ABOUT
============================================================ */

const navigateToAbout = () => {
  closeAssistant()
  router.push('/about')
}


/* ============================================================
   CHAT
============================================================ */

const sendSuggested = async (
  prompt: string,
) => {
  userInput.value = prompt
  await nextTick()
  await sendMessage()
}


const handleKeydown = (
  event: KeyboardEvent,
) => {
  if (
    event.key === 'Enter' &&
    !event.shiftKey
  ) {
    event.preventDefault()

    if (
      userInput.value.trim() &&
      !isThinking.value
    ) {
      sendMessage()
    }
  }
}


const autoResizeTextarea = () => {
  const textarea =
    inputRef.value

  if (!textarea) {
    return
  }

  textarea.style.height = 'auto'
  textarea.style.height = `${Math.min(textarea.scrollHeight, 132)}px`
}


const scrollToBottom = async () => {
  await nextTick()

  if (!chatContainer.value) {
    return
  }

  chatContainer.value.scrollTo({
    top:
      chatContainer.value.scrollHeight,
    behavior: 'smooth',
  })
}


const sendMessage = async () => {
  const text =
    userInput.value.trim()

  if (
    !text ||
    isThinking.value
  ) {
    return
  }

  const currentHistory =
    [...messages.value]

  messages.value.push({
    role: 'user',
    content: text,
  })

  userInput.value = ''
  isThinking.value = true

  autoResizeTextarea()
  await scrollToBottom()

  try {
    const apiBase =
      import.meta.env.VITE_API_BASE_URL ??
      'http://localhost:8000'

    const response =
      await fetch(
        `${apiBase}/api/v1/global-chat`,
        {
          method: 'POST',
          headers: {
            'Content-Type':
              'application/json',
            Accept:
              'application/json',
            ...(authStore.token
              ? {
                  Authorization:
                    `Bearer ${authStore.token}`,
                }
              : {}),
          },
          body: JSON.stringify({
            message: text,
            route: route.path,
            history:
              currentHistory,
          }),
        },
      )

    if (!response.ok) {
      throw new Error(
        `Assistant request failed: ${response.status}`,
      )
    }

    const data =
      await response.json()

    messages.value.push({
      role: 'ai',
      content:
        data.reply ||
        'I could not generate a response. Please try again.',
    })
  } catch (error) {
    console.error(
      'Global assistant error:',
      error,
    )

    messages.value.push({
      role: 'ai',
      content:
        'I could not reach the Smart Adama assistant right now. Please try again in a moment.',
    })
  } finally {
    isThinking.value = false
    await scrollToBottom()
  }
}


const resetConversation = async () => {
  if (isThinking.value) {
    return
  }

  messages.value = []
  copiedMessageIndex.value = null
  userInput.value = ''

  await nextTick()

  autoResizeTextarea()
  inputRef.value?.focus()
}


const copyMessage = async (
  content: string,
) => {
  try {
    await navigator.clipboard.writeText(
      content,
    )

    const index =
      messages.value.findIndex(
        (item) =>
          item.role === 'ai' &&
          item.content === content,
      )

    copiedMessageIndex.value =
      index >= 0 ? index : null

    window.setTimeout(() => {
      copiedMessageIndex.value = null
    }, 1600)
  } catch (error) {
    console.warn(
      'Clipboard access unavailable',
      error,
    )
  }
}

const handleClickOutside = (event: MouseEvent) => {
  if (!isOpen.value || isDragging.value) return
  
  const path = event.composedPath()
  const clickedInsidePanel = panelRef.value && path.includes(panelRef.value)
  const clickedToggleButton = toggleButtonRef.value && path.includes(toggleButtonRef.value)
  
  if (!clickedInsidePanel && !clickedToggleButton) {
    isOpen.value = false
  }
}


/* ============================================================
   WATCHERS / LIFECYCLE
============================================================ */

watch(
  isOpen,
  async (open) => {
    if (!open) {
      window.setTimeout(() => {
        mode.value = 'hub'
      }, 180)
      return
    }

    await nextTick()

    if (isOpen.value && mode.value === 'ai') {
      inputRef.value?.focus()
    }
  },
)

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})


watch(
  () => route.path,
  () => {
    if (mode.value === 'help') {
      // Keep the assistant open, but update the contextual content automatically.
    }
  },
)


onMounted(() => {
  window.addEventListener(
    'resize',
    handleResize,
  )

  window.addEventListener(
    'keydown',
    handleEscape,
  )
})


onUnmounted(() => {
  window.removeEventListener(
    'resize',
    handleResize,
  )

  window.removeEventListener(
    'keydown',
    handleEscape,
  )
})
</script>


<style scoped>
/* ============================================================
   COMPONENT ROOT
============================================================ */

.global-assistant {
  --assistant-brand: #395886;
  --assistant-brand-dark: #304B73;
  --assistant-brand-light: #638ECB;
  --assistant-brand-soft: #8AAEE0;
  --assistant-brand-pale: #B1C9EF;
  --assistant-brand-faint: rgba(99, 142, 203, 0.12);
  --assistant-page: #F0F3FA;
  --assistant-border: rgba(57, 88, 134, 0.14);
  --assistant-border-strong: rgba(57, 88, 134, 0.26);
  --assistant-surface: rgba(255, 255, 255, 0.85);
  --assistant-surface-soft: rgba(240, 243, 250, 0.70);
  --assistant-surface-opaque: rgba(255, 255, 255, 0.92);
  --assistant-surface-bg: linear-gradient(180deg, rgba(240,243,250,0.40), rgba(240,243,250,0.72));
  --assistant-code-bg: rgba(99,142,203,0.08);
  --assistant-text: #14243B;
  --assistant-muted: #64748B;
  --assistant-faint: #94A3B8;
  --assistant-shadow: 0 24px 70px rgba(15, 23, 42, 0.17);
  --assistant-shadow-soft: 0 10px 35px rgba(15, 23, 42, 0.10);
  position: relative;
  z-index: 1000;
}

:global(html.dark) {
  --assistant-brand: #8AAEE0;
  --assistant-brand-dark: #638ECB;
  --assistant-brand-faint: rgba(177, 201, 239, 0.15);
  --assistant-page: #08101C;
  --assistant-border: rgba(255, 255, 255, 0.09);
  --assistant-border-strong: rgba(177, 201, 239, 0.24);
  --assistant-surface: rgba(11, 15, 25, 0.85);
  --assistant-surface-soft: rgba(15, 23, 42, 0.70);
  --assistant-surface-opaque: rgba(11, 15, 25, 0.92);
  --assistant-surface-bg: linear-gradient(180deg, rgba(3,7,18,0.54), rgba(3,7,18,0.82));
  --assistant-code-bg: #172337;
  --assistant-text: #F8FAFC;
  --assistant-muted: #94A3B8;
  --assistant-faint: #64748B;
  --assistant-shadow: 0 24px 80px rgba(0, 0, 0, 0.42);
  --assistant-shadow-soft: 0 10px 35px rgba(0, 0, 0, 0.28);
}


/* ============================================================
   PANEL
============================================================ */

.assistant-panel {
  position: fixed;
  z-index: 100;
  left: 50%;
  bottom: 24px;
  transform: translateX(-50%);
  width: min(840px, calc(100vw - 32px));
  max-height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid var(--assistant-border);
  border-radius: 24px;
  background: var(--assistant-surface);
  box-shadow: var(--assistant-shadow);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
}

.assistant-panel--mobile {
  left: 10px;
  right: 10px;
  bottom: 10px;
  transform: none;
  width: auto;
  max-height: calc(100vh - 20px);
  border-radius: 24px;
}

.assistant-panel--mobile .assistant-header {
  padding-top: max(12px, env(safe-area-inset-top));
}


/* ============================================================
   PANEL TRANSITION
============================================================ */

.assistant-panel-enter-active,
.assistant-panel-leave-active {
  transition:
    opacity 0.24s ease,
    transform 0.24s cubic-bezier(0.16, 1, 0.3, 1);
}

.assistant-panel-enter-from,
.assistant-panel-leave-to {
  opacity: 0;
  transform: translate(-50%, 15px) scale(0.97);
}

.assistant-panel--mobile.assistant-panel-enter-from,
.assistant-panel--mobile.assistant-panel-leave-to {
  transform: translateY(15px) scale(0.97);
}


/* ============================================================
   HEADER
============================================================ */

.assistant-header {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 13px 14px;
  border-bottom: 1px solid var(--assistant-border);
  background: color-mix(in srgb, var(--assistant-surface) 92%, transparent);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

.assistant-header-main {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 9px;
}

.assistant-brand-mark {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 1px solid rgba(99, 142, 203, 0.22);
  border-radius: 11px;
  color: #638ECB;
  background: rgba(99, 142, 203, 0.08);
}

.assistant-brand-mark svg {
  width: 18px;
  height: 18px;
}

.assistant-title-wrap {
  min-width: 0;
}

.assistant-title-row {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 7px;
}

.assistant-title-wrap h2 {
  overflow: hidden;
  margin: 0;
  color: var(--assistant-text);
  font-size: 0.80rem;
  line-height: 1.1;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.assistant-title-wrap p {
  margin: 3px 0 0;
  color: var(--assistant-muted);
  font-size: 0.48rem;
  line-height: 1.35;
}

.context-badge {
  flex: 0 0 auto;
  padding: 3px 6px;
  border: 1px solid rgba(99, 142, 203, 0.20);
  border-radius: 999px;
  color: #638ECB;
  background: rgba(99, 142, 203, 0.07);
  font-size: 0.39rem;
  font-weight: 800;
  letter-spacing: 0.07em;
  text-transform: uppercase;
}

.assistant-header-actions {
  display: flex;
  align-items: center;
  gap: 3px;
  flex: 0 0 auto;
}

.icon-button,
.header-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  background: transparent;
  color: var(--assistant-muted);
  cursor: pointer;
  transition:
    background 0.16s ease,
    color 0.16s ease;
}

.icon-button {
  width: 31px;
  height: 31px;
  border-radius: 9px;
}

.icon-button:hover,
.header-action:hover {
  color: var(--assistant-text);
  background: var(--assistant-surface-soft);
}

.icon-button svg {
  width: 15px;
  height: 15px;
}

.header-action {
  gap: 4px;
  padding: 7px 8px;
  border-radius: 8px;
  font-size: 0.45rem;
  font-weight: 800;
}

.header-action svg {
  width: 12px;
  height: 12px;
}


/* ============================================================
   BODY
============================================================ */

.assistant-body {
  min-height: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}


/* ============================================================
   HUB
============================================================ */

.hub-body {
  padding: 14px;
  overflow-y: auto;
}

.hub-hero {
  position: relative;
  display: grid;
  grid-template-columns: 112px minmax(0, 1fr);
  gap: 16px;
  align-items: center;
  min-height: 154px;
  overflow: hidden;
  padding: 18px;
  border: 1px solid var(--assistant-border);
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(57, 88, 134, 0.97), rgba(99, 142, 203, 0.96));
  color: white;
}

.hub-visual {
  position: relative;
  width: 104px;
  height: 104px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hub-orbit {
  position: absolute;
  left: 50%;
  top: 50%;
  border: 1px solid rgba(255, 255, 255, 0.21);
  border-radius: 50%;
  transform: translate(-50%, -50%);
}

.hub-orbit--one {
  width: 92px;
  height: 92px;
}

.hub-orbit--two {
  width: 68px;
  height: 68px;
  border-color: rgba(255, 255, 255, 0.28);
}

.hub-core {
  position: relative;
  z-index: 2;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 13px;
  color: #D5DEEF;
  background: rgba(255, 255, 255, 0.10);
}

.hub-core svg {
  width: 20px;
  height: 20px;
}

.hub-copy {
  position: relative;
  z-index: 2;
}

.section-kicker {
  display: block;
  color: #8AAEE0;
  font-size: 0.43rem;
  font-weight: 800;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}

.hub-copy .section-kicker {
  color: #B1C9EF;
}

.hub-copy h3 {
  margin: 6px 0 0;
  color: white;
  font-size: 1.2rem;
  line-height: 1.08;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.hub-copy p {
  margin: 6px 0 0;
  color: rgba(255,255,255,0.74);
  font-size: 0.52rem;
  line-height: 1.55;
}

.hub-actions {
  display: flex;
  flex-direction: column;
  gap: 7px;
  margin-top: 12px;
}

.hub-option,
.ai-entry {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 11px;
  border-radius: 13px;
  text-align: left;
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    border-color 0.18s ease,
    box-shadow 0.18s ease;
}

.hub-option {
  border: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
}

.hub-option:hover {
  transform: translateY(-1px);
  border-color: var(--assistant-border-strong);
  background: var(--assistant-surface-soft);
}

.option-icon,
.ai-entry-icon {
  width: 34px;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 10px;
}

.option-icon svg,
.ai-entry-icon svg {
  width: 17px;
  height: 17px;
}

.option-icon--blue {
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.option-icon--soft {
  color: var(--assistant-brand-soft);
  background: var(--assistant-brand-faint);
}

:global(html.dark) .option-icon--soft {
  color: var(--assistant-brand);
}

.option-copy,
.ai-entry-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.option-copy strong,
.ai-entry-copy strong {
  overflow: hidden;
  color: var(--assistant-text);
  font-size: 0.56rem;
  font-weight: 800;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.option-copy span,
.ai-entry-copy span {
  color: var(--assistant-muted);
  font-size: 0.45rem;
  line-height: 1.45;
}

.option-arrow {
  width: 12px;
  height: 12px;
  flex: 0 0 auto;
  color: var(--assistant-faint);
}

.ai-entry {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 14px 18px;
  border: none;
  border-radius: 16px;
  background: #395886;
  box-shadow: 0 10px 24px rgba(57, 88, 134, 0.18);
  color: white;
  transition: 0.2s ease;
}

.ai-entry:hover {
  transform: translateY(-1px);
  background: #304B73;
  box-shadow: 0 14px 30px rgba(57, 88, 134, 0.22);
}

.ai-entry-icon {
  color: #D5DEEF;
  background: rgba(255,255,255,0.10);
}

.ai-entry-copy strong {
  color: white;
}

.ai-entry-copy span {
  display: block;
  font-size: 0.52rem;
  font-weight: 500;
  color: #D5DEEF;
  margin-top: 1px;
}

.ai-entry-key {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255,255,255,0.13);
  border-radius: 6px;
  color: rgba(255,255,255,0.72);
  font-size: 0.47rem;
}


/* ============================================================
   HELP
============================================================ */

.help-body {
  min-height: 0;
}

.help-intro {
  padding: 16px 16px 9px;
}

.help-intro h3 {
  margin: 5px 0 0;
  color: var(--assistant-text);
  font-size: 0.95rem;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.faq-list {
  min-height: 0;
  flex: 1;
  overflow-y: auto;
  padding: 4px 16px 16px;
}

.faq-item {
  margin-bottom: 7px;
  overflow: hidden;
  border: 1px solid var(--assistant-border);
  border-radius: 12px;
  background: var(--assistant-surface);
}

.faq-item[open] {
  border-color: var(--assistant-border-strong);
}

.faq-item summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 11px 12px;
  color: var(--assistant-text);
  font-size: 0.56rem;
  font-weight: 800;
  line-height: 1.4;
  cursor: pointer;
  list-style: none;
  user-select: none;
}

.faq-item summary::-webkit-details-marker {
  display: none;
}

.faq-plus {
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 6px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  font-size: 0.75rem;
  line-height: 1;
  transition: transform 0.18s ease;
}

.faq-item[open] .faq-plus {
  transform: rotate(45deg);
}

.faq-answer {
  padding: 0 12px 12px;
  border-top: 1px solid var(--assistant-border);
  color: var(--assistant-muted);
  font-size: 0.53rem;
  line-height: 1.65;
}

.help-footer {
  flex: 0 0 auto;
  padding: 11px 16px 14px;
  border-top: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
}

.help-footer > span {
  display: block;
  margin-bottom: 7px;
  color: var(--assistant-muted);
  font-size: 0.46rem;
  text-align: center;
}

.help-ai-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 10px 11px;
  border: 1px solid var(--assistant-brand-faint);
  border-radius: 10px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  font-size: 0.52rem;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.18s ease, transform 0.18s ease;
}

:global(html.dark) .help-ai-button {
  color: var(--assistant-brand);
}

.help-ai-button:hover {
  transform: translateY(-1px);
  background: var(--assistant-brand-faint);
}

.help-ai-button svg {
  width: 12px;
  height: 12px;
}


/* ============================================================
   CHAT
============================================================ */

.chat-body {
  min-height: 0;
}

.chat-scroll {
  min-height: 0;
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: var(--assistant-surface-bg);
  scroll-behavior: smooth;
}

.chat-empty {
  min-height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 22px 12px 16px;
  text-align: center;
}

.chat-empty-mark {
  width: 54px;
  height: 54px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
  border: 1px solid var(--assistant-border);
  border-radius: 16px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.chat-empty-mark svg {
  width: 24px;
  height: 24px;
}

.chat-empty h3 {
  max-width: 250px;
  margin: 6px 0 0;
  color: var(--assistant-text);
  font-size: 1rem;
  line-height: 1.2;
  font-weight: 800;
  letter-spacing: -0.025em;
}

.chat-empty p {
  max-width: 280px;
  margin: 7px auto 13px;
  color: var(--assistant-muted);
  font-size: 0.52rem;
  line-height: 1.65;
}

.suggestion-grid {
  width: min(100%, 330px);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
}

.suggestion-chip {
  min-width: 0;
  padding: 9px 10px;
  border: 1px solid var(--assistant-border);
  border-radius: 10px;
  color: var(--assistant-text);
  background: var(--assistant-surface);
  font-size: 0.47rem;
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
  transition:
    transform 0.16s ease,
    border-color 0.16s ease,
    background 0.16s ease;
}

.suggestion-chip:hover {
  transform: translateY(-1px);
  border-color: var(--assistant-border-strong);
  background: var(--assistant-surface-soft);
}

.message-row {
  display: flex;
  align-items: flex-end;
  gap: 7px;
  margin-bottom: 13px;
}

.message-row--user {
  justify-content: flex-end;
}

.message-row--assistant {
  justify-content: flex-start;
}

.message-avatar {
  width: 27px;
  height: 27px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 1px solid var(--assistant-border);
  border-radius: 9px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.message-avatar svg {
  width: 13px;
  height: 13px;
}

.message-bubble {
  position: relative;
  max-width: min(84%, 330px);
  min-width: 0;
  padding: 10px 11px;
  border-radius: 14px;
}

.message-bubble--user {
  border-bottom-right-radius: 4px;
  color: white;
  background: var(--assistant-brand);
  box-shadow: 0 7px 18px rgba(0,0,0,0.1);
}

.message-bubble--assistant {
  border: 1px solid var(--assistant-border);
  border-bottom-left-radius: 4px;
  color: var(--assistant-text);
  background: var(--assistant-surface);
  box-shadow: var(--assistant-shadow-soft);
}

.message-content {
  font-size: 0.56rem;
  line-height: 1.65;
  word-break: break-word;
}

.message-content--user {
  white-space: pre-wrap;
}

.message-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 6px;
  opacity: 0;
  transition: opacity 0.16s ease;
}

.message-bubble--assistant:hover .message-actions,
.message-actions:focus-within {
  opacity: 1;
}

.message-actions button {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 5px;
  border: 0;
  border-radius: 6px;
  color: var(--assistant-faint);
  background: transparent;
  font-size: 0.41rem;
  font-weight: 700;
  cursor: pointer;
}

.message-actions button:hover {
  color: var(--assistant-text);
  background: var(--assistant-surface-soft);
}

.message-actions svg {
  width: 10px;
  height: 10px;
}

.thinking-bubble {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 10px 11px;
  border: 1px solid var(--assistant-border);
  border-radius: 14px;
  border-bottom-left-radius: 4px;
  background: var(--assistant-surface);
  box-shadow: var(--assistant-shadow-soft);
}

.thinking-bubble span {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--assistant-brand);
  animation: assistant-bounce 1.1s ease-in-out infinite;
}

.thinking-bubble span:nth-child(2) {
  animation-delay: 0.12s;
}

.thinking-bubble span:nth-child(3) {
  animation-delay: 0.24s;
}

.thinking-bubble em {
  margin-left: 4px;
  color: var(--assistant-muted);
  font-size: 0.43rem;
  font-style: normal;
  font-weight: 700;
}

@keyframes assistant-bounce {
  0%, 70%, 100% {
    transform: translateY(0);
    opacity: 0.45;
  }
  35% {
    transform: translateY(-3px);
    opacity: 1;
  }
}


/* ============================================================
   COMPOSER
============================================================ */

.composer-wrap {
  flex: 0 0 auto;
  padding: 10px 12px calc(12px + env(safe-area-inset-bottom));
  border-top: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
}

.composer-shell {
  position: relative;
  padding: 8px;
  border-radius: 16px;
  background: var(--assistant-surface-soft);
  z-index: 1;
  overflow: hidden;
}

.composer-shell::before {
  content: '';
  position: absolute;
  top: -100%;
  left: -100%;
  width: 300%;
  height: 300%;
  z-index: -2;
  background: conic-gradient(from 0deg, transparent 65%, var(--assistant-brand) 85%, var(--assistant-brand-dark) 100%);
  animation: composer-spin 2.5s linear infinite;
  opacity: 0.2;
  transition: opacity 0.25s ease;
}

.composer-shell::after {
  content: '';
  position: absolute;
  inset: 1.5px;
  border-radius: 14.5px;
  background: var(--assistant-surface);
  z-index: -1;
}

.composer-shell:focus-within::before {
  opacity: 0.9;
}

@keyframes composer-spin {
  100% { transform: rotate(360deg); }
}

.composer-shell textarea {
  position: relative;
  z-index: 1;
  width: 100%;
  min-height: 39px;
  max-height: 132px;
  display: block;
  resize: none;
  overflow-y: auto;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--assistant-text);
  font-family: inherit;
  font-size: 0.57rem;
  line-height: 1.55;
}

.composer-shell textarea::placeholder {
  color: var(--assistant-faint);
}

.composer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 4px;
}

.composer-hint {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 5px;
  color: var(--assistant-faint);
  font-size: 0.39rem;
}

.composer-context-icon {
  display: inline-flex;
  flex: 0 0 auto;
}

.composer-context-icon svg {
  width: 11px;
  height: 11px;
}

.send-button {
  width: 31px;
  height: 31px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 9px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    color 0.18s ease;
}

.send-button:disabled {
  cursor: not-allowed;
  opacity: 0.76;
}

.send-button--active {
  color: white;
  background: var(--assistant-brand);
  box-shadow: 0 7px 18px rgba(0,0,0,0.1);
}

.send-button--active:hover {
  transform: translateY(-1px);
  background: var(--assistant-brand-dark);
}

.send-button svg {
  width: 15px;
  height: 15px;
}


/* ============================================================
   FLOATING TOGGLE
============================================================ */

.assistant-toggle {
  position: fixed;
  z-index: 110;
  width: 62px;
  height: 62px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--assistant-border);
  border-radius: 18px;
  color: var(--assistant-brand);
  background: var(--assistant-surface-opaque);
  box-shadow: 0 14px 35px rgba(0,0,0,0.1);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  cursor: grab;
  user-select: none;
  touch-action: none;
  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease,
    background 0.22s ease,
    color 0.22s ease;
}

:global(html.dark) .assistant-toggle {
  color: var(--assistant-brand);
  background: var(--assistant-surface-opaque);
  border-color: var(--assistant-border);
}

@media (hover: none) and (pointer: coarse) {
  .assistant-toggle:hover {
    transform: scale(1);
  }
}

.assistant-toggle:hover {
  transform: translateY(-2px);
  box-shadow: 0 18px 42px rgba(0,0,0,0.15);
}

.assistant-toggle--open {
  color: white;
  background: var(--assistant-brand);
}

.assistant-toggle--dragging {
  cursor: grabbing;
  transform: scale(0.96);
  transition: none;
}

.toggle-icon {
  position: relative;
  z-index: 2;
  width: 23px;
  height: 23px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toggle-icon svg {
  width: 23px;
  height: 23px;
}

.toggle-halo {
  position: absolute;
  inset: -1px;
  border: 1px solid var(--assistant-brand);
  border-radius: 18px;
  animation: assistant-pulse 2.8s ease-out infinite;
  pointer-events: none;
}

@keyframes assistant-pulse {
  0% {
    transform: scale(1);
    opacity: 0.42;
  }
  70%, 100% {
    transform: scale(1.16);
    opacity: 0;
  }
}

.toggle-tooltip {
  position: absolute;
  top: 50%;
  z-index: 10;
  transform: translateY(-50%);
  padding: 7px 9px;
  border-radius: 8px;
  color: white;
  background: var(--assistant-brand-dark);
  font-size: 0.48rem;
  font-weight: 800;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.16s ease;
  box-shadow: 0 8px 22px rgba(0,0,0,0.15);
}

.assistant-toggle:hover .toggle-tooltip {
  opacity: 1;
}

.toggle-tooltip--left {
  right: calc(100% + 10px);
}

.toggle-tooltip--right {
  left: calc(100% + 10px);
}


/* ============================================================
   MARKDOWN
============================================================ */

.ai-formatted-response :deep(p) {
  margin: 0 0 0.62rem;
}

.ai-formatted-response :deep(p:last-child) {
  margin-bottom: 0;
}

.ai-formatted-response :deep(strong) {
  color: var(--assistant-brand);
  font-weight: 800;
}

:global(html.dark) .ai-formatted-response :deep(strong) {
  color: var(--assistant-brand);
}

.ai-formatted-response :deep(ul),
.ai-formatted-response :deep(ol) {
  margin: 0.45rem 0 0.65rem;
  padding-left: 1.1rem;
}

.ai-formatted-response :deep(ul) {
  list-style: disc;
}

.ai-formatted-response :deep(ol) {
  list-style: decimal;
}

.ai-formatted-response :deep(li) {
  margin-bottom: 0.22rem;
}

.ai-formatted-response :deep(li::marker) {
  color: var(--assistant-brand);
}

.ai-formatted-response :deep(code) {
  padding: 0.12rem 0.28rem;
  border-radius: 4px;
  background: var(--assistant-brand-faint);
  color: var(--assistant-brand);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.88em;
}

:global(html.dark) .ai-formatted-response :deep(code) {
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.ai-formatted-response :deep(pre) {
  margin: 0.65rem 0;
  overflow-x: auto;
  padding: 0.8rem;
  border-radius: 9px;
  background: var(--assistant-code-bg);
  color: #E2E8F0;
}

.ai-formatted-response :deep(pre code) {
  padding: 0;
  background: transparent;
  color: inherit;
}

.ai-formatted-response :deep(h1),
.ai-formatted-response :deep(h2),
.ai-formatted-response :deep(h3) {
  margin: 0.75rem 0 0.4rem;
  color: var(--assistant-text);
  font-weight: 800;
}

.ai-formatted-response :deep(h3) {
  font-size: 0.7rem;
}


/* ============================================================
   SCROLLBARS
============================================================ */

.chat-scroll,
.faq-list,
.hub-body {
  scrollbar-width: thin;
  scrollbar-color: var(--assistant-border) transparent;
}

.chat-scroll::-webkit-scrollbar,
.faq-list::-webkit-scrollbar,
.hub-body::-webkit-scrollbar {
  width: 5px;
}

.chat-scroll::-webkit-scrollbar-thumb,
.faq-list::-webkit-scrollbar-thumb,
.hub-body::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: var(--assistant-border);
}

.theme-switcher {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--assistant-border);
}

.theme-btn {
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  color: var(--assistant-muted);
  background: transparent;
  transition: 0.2s ease;
}

.theme-btn:hover {
  background: var(--assistant-brand-faint);
  color: var(--assistant-text);
}

.theme-btn--active {
  background: var(--assistant-brand-faint);
  color: var(--assistant-text);
}


/* ============================================================
   MOBILE
============================================================ */

@media (max-width: 767px) {
  .assistant-toggle {
    width: 56px;
    height: 56px;
    border-radius: 16px;
  }

  .toggle-halo {
    border-radius: 16px;
  }

  .assistant-panel--mobile {
    width: calc(100vw - 20px);
  }

  .hub-hero {
    grid-template-columns: 84px minmax(0, 1fr);
    min-height: 134px;
    padding: 14px;
  }

  .hub-visual {
    width: 76px;
    height: 76px;
  }

  .hub-orbit--one {
    width: 70px;
    height: 70px;
  }

  .hub-orbit--two {
    width: 53px;
    height: 53px;
  }

  .hub-core {
    width: 35px;
    height: 35px;
    border-radius: 11px;
  }

  .hub-core svg {
    width: 17px;
    height: 17px;
  }

  .hub-copy h3 {
    font-size: 1.03rem;
  }

  .suggestion-grid {
    grid-template-columns: 1fr;
  }

  .message-bubble {
    max-width: 88%;
  }

  .message-actions {
    opacity: 1;
  }
}


/* ============================================================
   REDUCED MOTION
============================================================ */

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}

:deep(.thinking-block) {
  margin: 8px 0;
  padding: 8px 12px;
  background-color: rgba(0,0,0,0.03);
  border-left: 2px solid rgba(0,0,0,0.1);
  border-radius: 4px;
  font-size: 0.9em;
  color: #64748b;
}
:global(html.dark) :deep(.thinking-block) {
  background-color: rgba(255,255,255,0.05);
  border-left-color: rgba(255,255,255,0.1);
  color: #94a3b8;
}
:deep(.thinking-block summary) {
  cursor: pointer;
  font-weight: 500;
  user-select: none;
  opacity: 0.8;
}
:deep(.thinking-block summary:hover) {
  opacity: 1;
}
</style>