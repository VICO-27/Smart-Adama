import fs from 'fs'

let content = fs.readFileSync('/tmp/ChatView.vue.bak', 'utf8')

// 1. New Conversation
content = content.replace(
  /<span class="session-item__title" style="padding-right: 28px;">\{\{ session\.title \|\| 'New Conversation' \}\}<\/span>/g,
  '<span class="session-item__title" style="padding-right: 28px;">{{ session.title || $t(\'chat.new_conversation\') }}</span>'
)

// 2. Active Reader
content = content.replace(
  /<span class="chatgpt-menu-user-email">\{\{ authStore\?\.user\?\.email \|\| 'Active Reader' \}\}<\/span>/g,
  '<span class="chatgpt-menu-user-email">{{ authStore?.user?.email || $t(\'chat.active_reader\') }}</span>'
)

// 3. Take Quiz (mobile menu)
content = content.replace(
  /<span>Take Quiz<\/span>/g,
  '<span>{{ $t(\'chat.take_quiz\') }}</span>'
)

// 4. Mind Map
content = content.replace(
  /<span>Mind Map<\/span>/g,
  '<span>{{ $t(\'chat.mind_map\') }}</span>'
)

// 5. Try Again
content = content.replace(
  /<span>Try Again<\/span>/g,
  '<span>{{ $t(\'chat.try_again\') }}</span>'
)

// 6. Unable to load chapter content
content = content.replace(
  /<h2>Unable to load chapter content<\/h2>/g,
  '<h2>{{ $t(\'chat.err_load_chapter\') }}</h2>'
)

// 7. Select a chapter / Unable to load course materials
content = content.replace(
  /<h2>\{\{ booksStore\.books\.length === 0 \? 'Unable to load course materials' : 'Select a chapter' \}\}<\/h2>/g,
  '<h2>{{ booksStore.books.length === 0 ? $t(\'chat.err_load_course\') : $t(\'chat.select_a_chapter\') }}</h2>'
)

// 8. Could not connect
content = content.replace(
  /<p>\{\{ booksStore\.books\.length === 0 \? 'Could not connect to the course server\. Please check your connection and tap below to retry\.' : 'Please select a chapter from the course menu to start reading\.' \}\}<\/p>/g,
  '<p>{{ booksStore.books.length === 0 ? $t(\'chat.conn_error\') : $t(\'chat.select_chapter\') }}</p>'
)

// 9. Reload Course / Open First Chapter
content = content.replace(
  /<span>\{\{ booksStore\.books\.length === 0 \? 'Reload Course' : 'Open First Chapter' \}\}<\/span>/g,
  '<span>{{ booksStore.books.length === 0 ? $t(\'chat.reload_course\') : $t(\'chat.open_first\') }}</span>'
)

// 10. Complete this chapter to unlock its quiz.
content = content.replace(
  /<p class="text-\[var\(--rt-muted\)\] mb-6 max-w-md">Complete this chapter to unlock its quiz\.<\/p>/g,
  '<p class="text-[var(--rt-muted)] mb-6 max-w-md">{{ $t(\'chat.complete_to_unlock\') }}</p>'
)

// 11. Loading Quiz... : Take Quiz (in footer)
content = content.replace(
  /\{\{ isNavigatingToQuiz \? 'Loading Quiz\.\.\.' : 'Take Quiz' \}\}/g,
  '{{ isNavigatingToQuiz ? $t(\'chat.loading_quiz\') : $t(\'chat.take_quiz\') }}'
)

// 12. Imports
content = content.replace(
  /<script setup lang="ts">/g,
  `<script setup lang="ts">\nimport { useTour } from '@/composables/useTour'\nimport { studyTour } from '@/composables/tourRegistry'`
)

// 13. onMounted tour start
content = content.replace(
  `  if (!isReaderOpen.value && !isAiSidebarOpen.value) {
    isReaderOpen.value = true
  }
})`,
  `  if (!isReaderOpen.value && !isAiSidebarOpen.value) {
    isReaderOpen.value = true
  }

  // Start study tour if appropriate
  setTimeout(() => {
    const { registerTour, startTour } = useTour()
    registerTour(studyTour)
    startTour('study')
  }, 1200)
})`
)

fs.writeFileSync('src/views/ChatView.vue', content)
console.log('Fixed ChatView.vue')
