<template>
  <div class="global-assistant" :class="{ 'assistant-open': isOpen, 'global-assistant--dark': activeTheme === 'dark' }" :data-theme="activeTheme">
    <!-- DEDICATED BACKDROP OVERLAY -->
    <Transition name="assistant-backdrop">
      <div
        v-if="isOpen"
        class="assistant-backdrop"
        @click="closeAssistant"
        aria-hidden="true"
      ></div>
    </Transition>

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
                  {{ mode === 'hub' ? $t('assistant.title_hub') : mode === 'help' ? $t('assistant.title_help') : $t('assistant.title_chat') }}
                </h2>

                <span v-if="mode === 'ai'" class="context-badge">
                  {{ contextLabel }}
                </span>
              </div>

              <p v-if="mode === 'hub'">{{ $t('assistant.desc_hub') }}</p>
              <p v-else-if="mode === 'help'">{{ $t('assistant.desc_help') }}</p>
              <p v-else>{{ $t('assistant.desc_chat') }}</p>
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
              {{ $t('assistant.btn_new') }}
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
              <span class="section-kicker">{{ $t('assistant.kicker_hub') }}</span>
              <h3>{{ $t('assistant.question_hub') }}</h3>
              <p>
                {{ $t('assistant.sub_hub') }}
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
                <strong>{{ $t('assistant.opt_devs') }}</strong>
                <span>{{ $t('assistant.opt_devs_desc') }}</span>
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
                <strong>{{ $t('assistant.opt_help') }}</strong>
                <span>{{ $t('assistant.opt_help_desc') }}</span>
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
                <strong>{{ $t('assistant.opt_ai') }}</strong>
                <span>{{ $t('assistant.opt_ai_desc') }}</span>
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
            >{{ $t('assistant.theme_light') }}</button>
            <button
              @click="setTheme('dark')"
              class="theme-btn"
              :class="{ 'theme-btn--active': themePreference === 'dark' }"
            >{{ $t('assistant.theme_dark') }}</button>
            <button
              @click="setTheme('system')"
              class="theme-btn"
              :class="{ 'theme-btn--active': themePreference === 'system' }"
            >{{ $t('assistant.theme_system') }}</button>
          </div>
        </div>

        <!-- ===================================================
             HELP
        ==================================================== -->
        <div v-else-if="mode === 'help'" class="assistant-body help-body">
          <div class="help-intro">
            <span class="section-kicker">{{ $t('assistant.kicker_help') }}</span>
            <h3>{{ $t('assistant.title_help_page') }}</h3>
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
            <span>{{ $t('assistant.help_footer') }}</span>
            <button type="button" class="help-ai-button" @click="openAi">
              {{ $t('assistant.help_btn') }}
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

              <span class="section-kicker">{{ $t('assistant.kicker_chat') }}</span>
              <h3>{{ $t('assistant.title_chat_page') }}</h3>
              <p>
                {{ $t('assistant.chat_desc') }}
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
                    {{ copiedMessageIndex === index ? $t('assistant.btn_copied') : $t('assistant.btn_copy') }}
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
                <em>{{ $t('assistant.thinking') }}</em>
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
                :placeholder="isMobile ? $t('assistant.placeholder_mobile') : $t('assistant.placeholder_desktop')"
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
                  <span>{{ $t('assistant.hint') }}</span>
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
    <Transition name="assistant-toggle-fade">
      <button
        v-if="!isOpen"
        ref="toggleButtonRef"
        type="button"
        class="assistant-toggle"
        :class="{
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
        <span class="toggle-halo" aria-hidden="true"></span>

        <span class="toggle-icon" aria-hidden="true">
          <svg
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
        </span>

        <span
          v-if="!isDragging && !isMobile"
          class="toggle-tooltip"
          :class="pos.x > windowWidth / 2 ? 'toggle-tooltip--left' : 'toggle-tooltip--right'"
        >
          {{ $t('assistant.tooltip') }}
        </span>
      </button>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import { v4 as uuidv4 } from 'uuid'
import { aiApi } from '@/api/ai'
import { apiBase } from '@/api/client'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'
import { useI18n } from 'vue-i18n'

// DOMPurify Hook to add target="_blank" to parsed links
DOMPurify.addHook('afterSanitizeAttributes', (node) => {
  if (node.tagName === 'A') {
    node.setAttribute('target', '_blank')
    node.setAttribute('rel', 'noopener noreferrer')
  }
})

const auth = useAuthStore()
const { themePreference, setTheme } = useTheme()
const { t } = useI18n()

const activeTheme = computed(() => {
  if (themePreference.value === 'system') {
    return typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  return themePreference.value
})

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
  if (!rawText) return ''

  // Defensive client-side stripping of reasoning tags before rendering
  let processed = rawText
    .replace(/<think>[\s\S]*?<\/think>/gi, '')
    .replace(/<think>[\s\S]*$/gi, '')
    .replace(/(?:^|\n)(?:[*#_`\s]*)(?:Thinking [Pp]rocess|Thinking|Chain of [Tt]hought|Chain-of-[Tt]hought|Analysis)(?:[*#_`\s]*):?[\s\S]*?\n+(?:[*#_`\s]*)(?:Final Answer|Answer|Response|Reply|Solution):?\s*/gi, '\n')
    .replace(/^(?:[*#_`\s]*)(?:Thinking [Pp]rocess|Thinking|Chain of [Tt]hought|Chain-of-[Tt]hought|Analysis)(?:[*#_`\s]*):?[\s\S]*?(?=\n\n|\Z)/gi, '')
    .replace(/^(?:[*#_`\s]*)(?:Final Answer|Answer|Response|Reply)\s*:\s*[*#_`\s]*/gi, '')
    .trim()

  const html = marked.parse(processed) as string
  return DOMPurify.sanitize(html)
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
        q: t('assistant.faqs.study_1_q'),
        a: t('assistant.faqs.study_1_a'),
      },
      {
        q: t('assistant.faqs.study_2_q'),
        a: t('assistant.faqs.study_2_a'),
      },
      {
        q: t('assistant.faqs.study_3_q'),
        a: t('assistant.faqs.study_3_a'),
      },
    ]
  }

  if (path.includes('/dashboard')) {
    return [
      {
        q: t('assistant.faqs.dash_1_q'),
        a: t('assistant.faqs.dash_1_a'),
      },
      {
        q: t('assistant.faqs.dash_2_q'),
        a: t('assistant.faqs.dash_2_a'),
      },
      {
        q: t('assistant.faqs.dash_3_q'),
        a: t('assistant.faqs.dash_3_a'),
      },
    ]
  }

  if (path.includes('/profile')) {
    return [
      {
        q: t('assistant.faqs.prof_1_q'),
        a: t('assistant.faqs.prof_1_a'),
      },
      {
        q: t('assistant.faqs.prof_2_q'),
        a: t('assistant.faqs.prof_2_a'),
      },
      {
        q: t('assistant.faqs.prof_3_q'),
        a: t('assistant.faqs.prof_3_a'),
      },
    ]
  }

  return [
    {
      q: t('assistant.faqs.gen_1_q'),
      a: t('assistant.faqs.gen_1_a'),
    },
    {
      q: t('assistant.faqs.gen_2_q'),
      a: t('assistant.faqs.gen_2_a'),
    },
    {
      q: t('assistant.faqs.gen_3_q'),
      a: t('assistant.faqs.gen_3_a'),
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
      t('assistant.prompts.study_1'),
      t('assistant.prompts.study_2'),
      t('assistant.prompts.study_3'),
    ]
  }

  if (path.includes('/dashboard')) {
    return [
      t('assistant.prompts.dash_1'),
      t('assistant.prompts.dash_2'),
      t('assistant.prompts.dash_3'),
    ]
  }

  if (path.includes('/profile')) {
    return [
      t('assistant.prompts.prof_1'),
      t('assistant.prompts.prof_2'),
      t('assistant.prompts.prof_3'),
    ]
  }

  return [
    t('assistant.prompts.gen_1'),
    t('assistant.prompts.gen_2'),
    t('assistant.prompts.gen_3'),
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
    const response =
      await fetch(
        `${apiBase}/global-chat`,
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
   COMPONENT ROOT & THEME TOKENS INHERITANCE
============================================================ */

.global-assistant {
  /* Inherit from application active theme tokens (style.css) */
  --assistant-brand: var(--color-brand-500, #395886);
  --assistant-brand-dark: #2A4365;
  --assistant-brand-light: var(--color-brand-400, #638ECB);
  --assistant-brand-soft: var(--color-brand-300, #8AAEE0);
  --assistant-brand-pale: var(--color-brand-200, #B1C9EF);
  --assistant-brand-faint: rgba(99, 142, 203, 0.12);

  --assistant-page: var(--sa-bg, #F0F3FA);
  --assistant-surface: var(--sa-surface, #FFFFFF);
  --assistant-surface-soft: var(--sa-surface-soft, #F8FAFC);
  --assistant-surface-muted: var(--sa-surface-muted, #F1F5F9);
  --assistant-surface-opaque: rgba(255, 255, 255, 0.95);
  --assistant-surface-bg: var(--sa-surface-soft, #F8FAFC);
  --assistant-code-bg: var(--sa-surface-muted, #F1F5F9);

  --assistant-border: var(--sa-border, #E2E8F0);
  --assistant-border-strong: var(--sa-border-strong, #CBD5E1);

  --assistant-text: var(--sa-text, #0F172A);
  --assistant-text-secondary: var(--sa-text-secondary, #475569);
  --assistant-muted: var(--sa-text-muted, #64748B);
  --assistant-faint: var(--sa-text-faint, #94A3B8);

  --assistant-input-bg: var(--sa-input-bg, #FFFFFF);
  --assistant-input-border: var(--sa-input-border, #E2E8F0);
  --assistant-input-placeholder: var(--sa-input-placeholder, #94A3B8);

  /* User Bubble Contrast: Navy #395886 with #FFFFFF text = 7.45:1 (WCAG AAA) */
  --assistant-user-bubble-bg: #395886;
  --assistant-user-bubble-text: #FFFFFF;

  /* Assistant Bubble Contrast */
  --assistant-ai-bubble-bg: var(--sa-surface, #FFFFFF);
  --assistant-ai-bubble-text: var(--sa-text, #0F172A);
  --assistant-ai-bubble-border: var(--sa-border, #E2E8F0);

  --assistant-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
  --assistant-shadow-soft: 0 4px 16px rgba(15, 23, 42, 0.08);

  position: relative;
  z-index: 1000;
}

/* Dark theme synchronization */
.global-assistant[data-theme="dark"],
.global-assistant.global-assistant--dark,
:global(.dark) .global-assistant,
:global([data-theme="dark"]) .global-assistant {
  --assistant-brand: var(--color-brand-400, #638ECB);
  --assistant-brand-dark: #1E3A8A;
  --assistant-brand-light: var(--color-brand-300, #8AAEE0);
  --assistant-brand-soft: var(--color-brand-200, #B1C9EF);
  --assistant-brand-faint: rgba(99, 142, 203, 0.18);

  --assistant-page: var(--sa-bg, #020617);
  --assistant-surface: var(--sa-surface, #0F172A);
  --assistant-surface-soft: var(--sa-surface-soft, #111827);
  --assistant-surface-muted: var(--sa-surface-muted, #1E293B);
  --assistant-surface-opaque: rgba(15, 23, 42, 0.95);
  --assistant-surface-bg: #0B1220;
  --assistant-code-bg: #1E293B;

  --assistant-border: var(--sa-border, rgba(255, 255, 255, 0.08));
  --assistant-border-strong: var(--sa-border-strong, rgba(255, 255, 255, 0.16));

  --assistant-text: var(--sa-text, #F8FAFC);
  --assistant-text-secondary: var(--sa-text-secondary, #CBD5E1);
  --assistant-muted: var(--sa-text-muted, #94A3B8);
  --assistant-faint: var(--sa-text-faint, #64748B);

  --assistant-input-bg: var(--sa-input-bg, #0B1220);
  --assistant-input-border: var(--sa-input-border, #334155);
  --assistant-input-placeholder: var(--sa-input-placeholder, #64748B);

  /* User Bubble Contrast: Vibrant Blue #2563EB with #FFFFFF text = 4.56:1 (WCAG AA) */
  --assistant-user-bubble-bg: #2563EB;
  --assistant-user-bubble-text: #FFFFFF;

  /* Assistant Bubble Contrast */
  --assistant-ai-bubble-bg: var(--sa-surface, #0F172A);
  --assistant-ai-bubble-text: var(--sa-text, #F8FAFC);
  --assistant-ai-bubble-border: var(--sa-border, rgba(255, 255, 255, 0.1));

  --assistant-shadow: 0 24px 80px rgba(0, 0, 0, 0.52);
  --assistant-shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.32);
}


/* ============================================================
   BACKDROP
============================================================ */

.assistant-backdrop {
  position: fixed;
  inset: 0;
  z-index: 90;
  background: rgba(15, 23, 42, 0.42);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  cursor: pointer;
}

.assistant-backdrop-enter-active,
.assistant-backdrop-leave-active {
  transition: opacity 0.24s ease;
}

.assistant-backdrop-enter-from,
.assistant-backdrop-leave-to {
  opacity: 0;
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
  max-height: calc(100dvh - 120px);
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
  max-height: calc(100dvh - 20px);
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
  padding: 14px 16px;
  border-bottom: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}

.assistant-header-main {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.assistant-brand-mark {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 1px solid var(--assistant-border);
  border-radius: 12px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.assistant-brand-mark svg {
  width: 20px;
  height: 20px;
}

.assistant-title-wrap {
  min-width: 0;
}

.assistant-title-row {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.assistant-title-wrap h2 {
  overflow: hidden;
  margin: 0;
  color: var(--assistant-text);
  font-size: 0.95rem;
  line-height: 1.25;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.assistant-title-wrap p {
  margin: 2px 0 0;
  color: var(--assistant-muted);
  font-size: 0.75rem;
  line-height: 1.4;
}

.context-badge {
  flex: 0 0 auto;
  padding: 3px 8px;
  border: 1px solid var(--assistant-border);
  border-radius: 999px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.assistant-header-actions {
  display: flex;
  align-items: center;
  gap: 6px;
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
  width: 32px;
  height: 32px;
  border-radius: 10px;
}

.icon-button:hover,
.header-action:hover {
  color: var(--assistant-text);
  background: var(--assistant-surface-muted);
}

.icon-button svg {
  width: 17px;
  height: 17px;
}

.header-action {
  gap: 5px;
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;
}

.header-action svg {
  width: 14px;
  height: 14px;
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
  padding: 16px;
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
  background: linear-gradient(135deg, var(--color-brand-500, #395886), var(--color-brand-400, #638ECB));
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
  border: 1px solid rgba(255, 255, 255, 0.25);
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
  border-color: rgba(255, 255, 255, 0.35);
}

.hub-core {
  position: relative;
  z-index: 2;
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 13px;
  color: #D5DEEF;
  background: rgba(255, 255, 255, 0.12);
}

.hub-core svg {
  width: 22px;
  height: 22px;
}

.hub-copy {
  position: relative;
  z-index: 2;
}

.section-kicker {
  display: block;
  color: var(--assistant-brand-soft);
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.hub-copy .section-kicker {
  color: #D5DEEF;
}

.hub-copy h3 {
  margin: 6px 0 0;
  color: white;
  font-size: 1.25rem;
  line-height: 1.2;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.hub-copy p {
  margin: 6px 0 0;
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.85rem;
  line-height: 1.5;
}

.hub-actions {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-top: 14px;
}

.hub-option,
.ai-entry {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  padding: 13px 14px;
  border-radius: 14px;
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
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 11px;
}

.option-icon svg,
.ai-entry-icon svg {
  width: 19px;
  height: 19px;
}

.option-icon--blue {
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.option-icon--soft {
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.option-copy,
.ai-entry-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.option-copy strong {
  overflow: hidden;
  color: var(--assistant-text);
  font-size: 0.875rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.option-copy span {
  color: var(--assistant-muted);
  font-size: 0.775rem;
  line-height: 1.45;
}

.option-arrow {
  width: 14px;
  height: 14px;
  flex: 0 0 auto;
  color: var(--assistant-faint);
}

.ai-entry {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 14px 18px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  background: var(--color-brand-500, #395886);
  box-shadow: 0 8px 24px rgba(57, 88, 134, 0.22);
  color: white;
  transition: 0.2s ease;
}

.ai-entry:hover {
  transform: translateY(-1px);
  background: var(--assistant-brand-dark, #2A4365);
  box-shadow: 0 12px 30px rgba(57, 88, 134, 0.28);
}

.ai-entry-icon {
  color: #D5DEEF;
  background: rgba(255, 255, 255, 0.12);
}

.ai-entry-copy strong {
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
}

.ai-entry-copy span {
  display: block;
  font-size: 0.8rem;
  font-weight: 500;
  color: #D5DEEF;
  margin-top: 1px;
}

.ai-entry-key {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 7px;
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.75rem;
  font-weight: 600;
}


/* ============================================================
   HELP
============================================================ */

.help-body {
  min-height: 0;
}

.help-intro {
  padding: 16px 16px 10px;
}

.help-intro h3 {
  margin: 5px 0 0;
  color: var(--assistant-text);
  font-size: 1.05rem;
  line-height: 1.3;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.faq-list {
  min-height: 0;
  flex: 1;
  overflow-y: auto;
  padding: 6px 16px 16px;
}

.faq-item {
  margin-bottom: 8px;
  overflow: hidden;
  border: 1px solid var(--assistant-border);
  border-radius: 12px;
  background: var(--assistant-surface);
  transition: border-color 0.16s ease;
}

.faq-item[open] {
  border-color: var(--assistant-border-strong);
}

.faq-item summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px;
  color: var(--assistant-text);
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.4;
  cursor: pointer;
  list-style: none;
  user-select: none;
}

.faq-item summary::-webkit-details-marker {
  display: none;
}

.faq-plus {
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 6px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  font-size: 0.9rem;
  line-height: 1;
  transition: transform 0.18s ease;
}

.faq-item[open] .faq-plus {
  transform: rotate(45deg);
}

.faq-answer {
  padding: 10px 14px 14px;
  border-top: 1px solid var(--assistant-border);
  color: var(--assistant-muted);
  font-size: 0.825rem;
  line-height: 1.6;
}

.help-footer {
  flex: 0 0 auto;
  padding: 12px 16px 16px;
  border-top: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
}

.help-footer > span {
  display: block;
  margin-bottom: 8px;
  color: var(--assistant-muted);
  font-size: 0.775rem;
  text-align: center;
}

.help-ai-button {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 11px 12px;
  border: 1px solid var(--assistant-border);
  border-radius: 11px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
  font-size: 0.825rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.18s ease, transform 0.18s ease;
}

.help-ai-button:hover {
  transform: translateY(-1px);
  background: var(--assistant-brand-faint);
  border-color: var(--assistant-border-strong);
}

.help-ai-button svg {
  width: 15px;
  height: 15px;
}


/* ============================================================
   CHAT & BUBBLES
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
  padding: 24px 14px 18px;
  text-align: center;
}

.chat-empty-mark {
  width: 56px;
  height: 56px;
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
  width: 26px;
  height: 26px;
}

.chat-empty h3 {
  max-width: 300px;
  margin: 6px 0 0;
  color: var(--assistant-text);
  font-size: 1.15rem;
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.chat-empty p {
  max-width: 320px;
  margin: 8px auto 16px;
  color: var(--assistant-muted);
  font-size: 0.875rem;
  line-height: 1.6;
}

.suggestion-grid {
  width: min(100%, 380px);
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.suggestion-chip {
  min-width: 0;
  padding: 10px 12px;
  border: 1px solid var(--assistant-border);
  border-radius: 11px;
  color: var(--assistant-text);
  background: var(--assistant-surface);
  font-size: 0.8125rem;
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
  gap: 8px;
  margin-bottom: 14px;
}

.message-row--user {
  justify-content: flex-end;
}

.message-row--assistant {
  justify-content: flex-start;
}

.message-avatar {
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 1px solid var(--assistant-border);
  border-radius: 10px;
  color: var(--assistant-brand);
  background: var(--assistant-brand-faint);
}

.message-avatar svg {
  width: 15px;
  height: 15px;
}

.message-bubble {
  position: relative;
  max-width: min(85%, 480px);
  min-width: 0;
  padding: 11px 15px;
  border-radius: 16px;
}

.message-bubble--user {
  border-bottom-right-radius: 4px;
  color: var(--assistant-user-bubble-text);
  background: var(--assistant-user-bubble-bg);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
}

.message-bubble--assistant {
  border: 1px solid var(--assistant-ai-bubble-border);
  border-bottom-left-radius: 4px;
  color: var(--assistant-ai-bubble-text);
  background: var(--assistant-ai-bubble-bg);
  box-shadow: var(--assistant-shadow-soft);
}

.message-content {
  font-size: 0.9375rem;
  line-height: 1.6;
  word-break: break-word;
}

.message-content--user {
  white-space: pre-wrap;
  color: var(--assistant-user-bubble-text);
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
  padding: 4px 6px;
  border: 0;
  border-radius: 6px;
  color: var(--assistant-faint);
  background: transparent;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.message-actions button:hover {
  color: var(--assistant-text);
  background: var(--assistant-surface-muted);
}

.message-actions svg {
  width: 12px;
  height: 12px;
}

.thinking-bubble {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 11px 14px;
  border: 1px solid var(--assistant-border);
  border-radius: 16px;
  border-bottom-left-radius: 4px;
  background: var(--assistant-surface);
  box-shadow: var(--assistant-shadow-soft);
}

.thinking-bubble span {
  width: 6px;
  height: 6px;
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
  margin-left: 6px;
  color: var(--assistant-muted);
  font-size: 0.8125rem;
  font-style: normal;
  font-weight: 600;
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
  padding: 12px 14px calc(14px + env(safe-area-inset-bottom));
  border-top: 1px solid var(--assistant-border);
  background: var(--assistant-surface);
}

.composer-shell {
  position: relative;
  padding: 10px 12px;
  border-radius: 16px;
  border: 1px solid var(--assistant-input-border);
  background: var(--assistant-surface-soft);
  z-index: 1;
  overflow: hidden;
  transition: border-color 0.2s ease;
}

.composer-shell:focus-within {
  border-color: var(--assistant-brand);
}

.composer-shell textarea {
  position: relative;
  z-index: 1;
  width: 100%;
  min-height: 42px;
  max-height: 140px;
  display: block;
  resize: none;
  overflow-y: auto;
  border: 0;
  outline: 0;
  background: transparent;
  color: var(--assistant-text);
  font-family: inherit;
  font-size: 0.9375rem;
  line-height: 1.5;
}

.composer-shell textarea::placeholder {
  color: var(--assistant-input-placeholder);
  font-size: 0.9375rem;
}

.composer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-top: 6px;
}

.composer-hint {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--assistant-faint);
  font-size: 0.75rem;
}

.composer-context-icon {
  display: inline-flex;
  flex: 0 0 auto;
}

.composer-context-icon svg {
  width: 13px;
  height: 13px;
}

.send-button {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 10px;
  color: var(--assistant-muted);
  background: var(--assistant-surface-muted);
  cursor: pointer;
  transition:
    transform 0.18s ease,
    background 0.18s ease,
    color 0.18s ease;
}

.send-button:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.send-button--active {
  color: #FFFFFF;
  background: var(--assistant-brand);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.send-button--active:hover {
  transform: translateY(-1px);
  background: var(--assistant-brand-dark);
}

.send-button svg {
  width: 17px;
  height: 17px;
}


/* ============================================================
   FLOATING TOGGLE
============================================================ */

.assistant-toggle {
  position: fixed;
  z-index: 95;
  width: 62px;
  height: 62px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--assistant-border);
  border-radius: 18px;
  color: var(--assistant-brand);
  background: var(--assistant-surface-opaque);
  box-shadow: var(--assistant-shadow);
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

@media (hover: none) and (pointer: coarse) {
  .assistant-toggle:hover {
    transform: scale(1);
  }
}

.assistant-toggle:hover {
  transform: translateY(-2px);
  box-shadow: var(--assistant-shadow);
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
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toggle-icon svg {
  width: 24px;
  height: 24px;
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
  padding: 8px 12px;
  border-radius: 8px;
  color: white;
  background: var(--assistant-brand);
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.16s ease;
  box-shadow: var(--assistant-shadow-soft);
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

.assistant-toggle-fade-enter-active,
.assistant-toggle-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.assistant-toggle-fade-enter-from,
.assistant-toggle-fade-leave-to {
  opacity: 0;
  transform: scale(0.85);
}


/* ============================================================
   MARKDOWN
============================================================ */

.ai-formatted-response :deep(p) {
  margin: 0 0 0.75rem;
  line-height: 1.6;
}

.ai-formatted-response :deep(p:last-child) {
  margin-bottom: 0;
}

.ai-formatted-response :deep(strong) {
  color: var(--assistant-brand);
  font-weight: 700;
}

.ai-formatted-response :deep(ul),
.ai-formatted-response :deep(ol) {
  margin: 0.5rem 0 0.75rem;
  padding-left: 1.4rem;
}

.ai-formatted-response :deep(ul) {
  list-style: disc;
}

.ai-formatted-response :deep(ol) {
  list-style: decimal;
}

.ai-formatted-response :deep(li) {
  margin-bottom: 0.35rem;
  line-height: 1.55;
}

.ai-formatted-response :deep(li::marker) {
  color: var(--assistant-brand);
  font-weight: 700;
}

.ai-formatted-response :deep(code) {
  padding: 0.15rem 0.35rem;
  border-radius: 5px;
  background: var(--assistant-brand-faint);
  color: var(--assistant-text);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.85em;
  border: 1px solid var(--assistant-border);
}

.ai-formatted-response :deep(pre) {
  margin: 0.75rem 0;
  overflow-x: auto;
  padding: 0.85rem 1rem;
  border-radius: 10px;
  background: var(--assistant-code-bg);
  border: 1px solid var(--assistant-border);
  color: var(--assistant-text);
  font-size: 0.85rem;
  line-height: 1.5;
}

.ai-formatted-response :deep(pre code) {
  padding: 0;
  border: none;
  background: transparent;
  color: inherit;
  font-size: inherit;
}

.ai-formatted-response :deep(h1),
.ai-formatted-response :deep(h2),
.ai-formatted-response :deep(h3) {
  margin: 0.85rem 0 0.45rem;
  color: var(--assistant-text);
  font-weight: 700;
}

.ai-formatted-response :deep(h1) {
  font-size: 1.15rem;
}

.ai-formatted-response :deep(h2) {
  font-size: 1.05rem;
}

.ai-formatted-response :deep(h3) {
  font-size: 0.95rem;
}

.ai-formatted-response :deep(a) {
  color: var(--assistant-brand);
  text-decoration: underline;
  text-underline-offset: 3px;
  font-weight: 600;
}

.ai-formatted-response :deep(a:hover) {
  opacity: 0.85;
}


/* ============================================================
   SCROLLBARS
============================================================ */

.chat-scroll,
.faq-list,
.hub-body {
  scrollbar-width: thin;
  scrollbar-color: var(--assistant-border-strong) transparent;
}

.chat-scroll::-webkit-scrollbar,
.faq-list::-webkit-scrollbar,
.hub-body::-webkit-scrollbar {
  width: 6px;
}

.chat-scroll::-webkit-scrollbar-thumb,
.faq-list::-webkit-scrollbar-thumb,
.hub-body::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: var(--assistant-border-strong);
}

.chat-scroll::-webkit-scrollbar-thumb:hover,
.faq-list::-webkit-scrollbar-thumb:hover,
.hub-body::-webkit-scrollbar-thumb:hover {
  background: var(--assistant-muted);
}


/* ============================================================
   THEME SWITCHER
============================================================ */

.theme-switcher {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--assistant-border);
}

.theme-btn {
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.4rem 0.85rem;
  border-radius: 999px;
  color: var(--assistant-muted);
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: 0.2s ease;
}

.theme-btn:hover {
  background: var(--assistant-brand-faint);
  color: var(--assistant-text);
}

.theme-btn--active {
  background: var(--assistant-brand-faint);
  color: var(--assistant-brand);
  border-color: var(--assistant-border);
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
    width: 18px;
    height: 18px;
  }

  .hub-copy h3 {
    font-size: 1.05rem;
  }

  .suggestion-grid {
    grid-template-columns: 1fr;
  }

  .message-bubble {
    max-width: 90%;
    padding: 10px 13px;
  }

  .message-content {
    font-size: 0.9375rem;
  }

  .composer-shell textarea {
    font-size: 0.9375rem;
  }

  .composer-shell textarea::placeholder {
    font-size: 0.875rem;
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
</style>