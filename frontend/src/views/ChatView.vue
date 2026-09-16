<template>
  <div v-if="fatalError" class="fatal-error-overlay">
    <div class="fatal-error-modal">
      <h2>{{ $t('chat.error') }}</h2>
      <p>{{ $t('chat.error_desc') }}</p>
      <pre>{{ fatalError }}</pre>
      <button class="btn btn--primary" @click="fatalError = null">{{ $t('chat.dismiss') }}</button>
      <button class="btn" @click="reloadPage">{{ $t('chat.reload') }}</button>
    </div>
  </div>

  <div
    v-else
    class="chat-page"
    :class="[
      'theme-' + readerTheme,
      {
        'dark': readerTheme === 'dark',
        'light': readerTheme !== 'dark',
        'is-resizing': isDraggingLeft || isDraggingRight || isDraggingReader,
        'is-fullscreen': isFullscreen,
      }
    ]"
    :data-theme="readerTheme"
    :style="[themeVars, { colorScheme: readerTheme === 'dark' ? 'dark' : 'light' }]"
  >
    <!-- Mobile overlays -->
    <Transition name="fade">
      <button
        v-if="isMobile && (isSidebarOpen || isAiSidebarOpen)"
        class="mobile-backdrop"
        type="button"
        aria-label="Close open panel"
        @click="closeMobilePanels"
      ></button>
    </Transition>

    <!-- =========================================================
         LEFT COLLAPSED RAIL
    ========================================================== -->
    <aside
      v-if="!isSidebarOpen && !isFullscreen && !isMobile"
      class="collapsed-rail collapsed-rail--left flex flex-col justify-between items-center cursor-pointer"
      aria-label="Sidebar controls"
      @click="isSidebarOpen = true"
    >
      <div class="flex flex-col gap-5 pt-5 w-full items-center">
        <!-- Open Sidebar -->
        <button class="rail-button" type="button" title="Open sidebar" @click="isSidebarOpen = true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-linecap="round" />
            <path d="M9 3v18" stroke-linecap="round" />
          </svg>
        </button>

        <!-- New Chat -->
        <button class="rail-button" type="button" title="New AI session" @click.stop="startNewChatAndOpen">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 4v16M4 12h16" stroke-linecap="round" />
          </svg>
        </button>

        <!-- Get Full PDF (Read Book) -->
        <button class="rail-button" type="button" title="Get Full PDF" @click="switchToPdf">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>

        <!-- Chapters -->
        <button class="rail-button" type="button" title="Chapters" data-tour="chapters" @click="activeSidebarTab = 'chapters'; isSidebarOpen = true;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <div class="pb-6 flex justify-center w-full">
        <button v-if="authStore?.user" class="w-7 h-7 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center text-xs shadow-sm hover:opacity-80 transition-opacity" title="Profile">
          {{ authStore?.user?.first_name?.charAt(0) || authStore?.user?.name?.charAt(0) || 'U' }}
        </button>
      </div>
    </aside>

    <!-- =========================================================
         LEFT CHAPTER / CHAT SIDEBAR
    ========================================================== -->
    <aside
      v-if="isSidebarOpen && !isFullscreen"
      class="side-panel side-panel--left"
      :class="{ 'side-panel--mobile': isMobile }"
      aria-label="Course navigation"
    >
      <div class="side-panel__header">
        <div class="side-panel__brand-row">
          <RouterLink
            to="/dashboard"
            class="brand-button"
            title="Go to Dashboard"
          >
            <img src="/logo.png" alt="Smart Adama" />
            <span>{{ $t('nav.brand') }}</span>
          </RouterLink>

          <div style="display: flex; gap: 4px;">
            <button
              type="button"
              class="icon-button"
              aria-label="Search"
              title="Search"
              @click="toggleSidebarSearch"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" stroke-linecap="round" />
              </svg>
            </button>
            <button
              type="button"
              class="icon-button"
              aria-label="Close sidebar"
              title="Close sidebar"
              @click="isSidebarOpen = false"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-linecap="round" />
                <path d="M9 3v18" stroke-linecap="round" />
              </svg>
            </button>
          </div>
        </div>

        <!-- ChatGPT-style New Chat Button -->
        <button type="button" class="new-chat-button" @click="startNewChat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ $t('chat.new_chat') }}
        </button>

        <button
          type="button"
          class="reader-toggle-button"
          @click="switchToPdf"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          Get Full PDF
        </button>

        <!-- Modern Tab Switcher -->
        <div class="sidebar-tab-switcher">
          <button
            type="button"
            class="tab-btn"
            :class="{ 'is-active': activeSidebarTab === 'chats' }"
            @click="activeSidebarTab = 'chats'; if (!isAiSidebarOpen) { isAiSidebarOpen = true; isReaderOpen = false; }"
          >
            {{ $t('chat.chats') }}
          </button>
          <button
            type="button"
            class="tab-btn"
            data-tour="chapters-tab"
            :class="{ 'is-active': activeSidebarTab === 'chapters' }"
            @click="activeSidebarTab = 'chapters'; if (!isReaderOpen) { isReaderOpen = true; isAiSidebarOpen = false; }"
          >
            {{ $t('chat.chapters') }}
          </button>
        </div>
      </div>

      <div class="side-panel__scroll">
        <!-- CHATS TAB -->
        <div v-if="activeSidebarTab === 'chats'" class="sidebar-tab-content">
          <section class="sidebar-section sidebar-section--sessions">
            <div
              class="sidebar-group-title"
              style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; user-select: none; padding: 4px 8px; font-size: 0.72rem; font-weight: 600; color: var(--reader-muted);"
              @click="isRecentChatsOpen = !isRecentChatsOpen"
            >
              <span>{{ $t('chat.recent_chats') }}</span>
              <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                style="width: 12px; height: 12px; transition: transform 0.2s;"
                :style="{ transform: isRecentChatsOpen ? 'rotate(90deg)' : 'rotate(0deg)' }"
              >
                <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>

            <div v-show="isRecentChatsOpen">
              <template v-for="group in sessionGroups" :key="group.id">
                <div class="session-list chatgpt-session-list" style="margin-bottom: 12px;">
                  <div
                    v-for="session in group.sessions"
                  :key="session.id"
                  class="session-item-wrapper"
                  style="position: relative; display: flex; align-items: center; gap: 4px; padding-right: 4px;"
                  @mouseleave="activeSessionMenu === session.id ? null : null"
                >
                  <button
                    v-if="editingSessionId !== session.id"
                    type="button"
                    class="session-item"
                    :class="{ 'session-item--active': chatStore.currentSession?.id === session.id }"
                    @click="switchSession(session.id)"
                    style="flex: 1; margin: 0; position: relative;"
                  >
                    <span class="session-item__title" style="padding-right: 28px;">{{ session.title || $t('chat.new_conversation') }}</span>
                  </button>

                  <input
                    v-else
                    type="text"
                    class="session-rename-input"
                    v-model="editSessionTitle"
                    @blur="submitRename"
                    @keyup.enter="submitRename"
                    @keyup.esc="editingSessionId = null"
                    v-focus
                  />

                  <button
                    v-if="editingSessionId !== session.id"
                    type="button"
                    class="session-options-btn"
                    :class="{ 'is-visible': activeSessionMenu === session.id || chatStore.currentSession?.id === session.id }"
                    @click.stop="activeSessionMenu = activeSessionMenu === session.id ? null : session.id"
                    title="Options"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                  </button>

                  <!-- Dropdown Menu -->
                  <div v-if="activeSessionMenu === session.id" class="session-dropdown-menu">
                    <button type="button" class="dropdown-item" @click.stop="copySessionTranscript(session)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
                      {{ $t('chat.share') }}
                    </button>
                    <button type="button" class="dropdown-item" @click.stop="startRenaming(session)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/></svg>
                      {{ $t('chat.rename') }}
                    </button>
                    <hr class="dropdown-divider" />
                    <button type="button" class="dropdown-item" @click.stop="chatStore.togglePinSession(session); activeSessionMenu = null">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                      {{ session.is_pinned ? 'Unpin chat' : 'Pin chat' }}
                    </button>
                    <button type="button" class="dropdown-item" @click.stop="chatStore.toggleArchiveSession(session); activeSessionMenu = null">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                      {{ session.is_archived ? 'Unarchive' : 'Archive' }}
                    </button>
                    <button type="button" class="dropdown-item text-danger" @click.stop="deleteChat(session.id); activeSessionMenu = null">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                      {{ $t('chat.delete') }}
                    </button>
                  </div>
                </div>
              </div>
            </template>

            <button
              v-if="hiddenChatsCount > 0"
              type="button"
              class="show-more-button"
              @click="showAllChats = !showAllChats"
            >
              {{ showAllChats ? 'Show less' : `Show ${hiddenChatsCount} more` }}
            </button>
            </div>
          </section>
        </div>

        <!-- CHAPTERS TAB -->
        <div v-if="activeSidebarTab === 'chapters'" class="sidebar-tab-content">
          <RouterLink to="/quizzes" class="sidebar-link sidebar-link--quiz">
            <span class="sidebar-link__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 12 2 2 4-4M7.8 4.7a3.4 3.4 0 0 0 1.9-.8 3.4 3.4 0 0 1 4.4 0 3.4 3.4 0 0 0 1.9.8 3.4 3.4 0 0 1 3.1 3.1 3.4 3.4 0 0 0 .8 1.9 3.4 3.4 0 0 1 0 4.4 3.4 3.4 0 0 0-.8 1.9 3.4 3.4 0 0 1-3.1 3.1 3.4 3.4 0 0 0-1.9.8 3.4 3.4 0 0 1-4.4 0 3.4 3.4 0 0 0-1.9-.8 3.4 3.4 0 0 1-3.1-3.1 3.4 3.4 0 0 0-.8-1.9 3.4 3.4 0 0 1 0-4.4 3.4 3.4 0 0 0 .8-1.9 3.4 3.4 0 0 1 3.1-3.1Z" />
              </svg>
            </span>
            <span>{{ $t('chapter.quizzes') }}</span>
          </RouterLink>

          <section class="sidebar-section">
            <div class="sidebar-section__title">{{ $t('chapter.course_content') }}</div>

            <div class="chapter-list modern-chapter-list">
              <article
                v-for="chapter in allVisibleChapters"
                :key="chapter.id"
                class="chapter-item"
                :class="{ 'chapter-item--active': booksStore.currentChapter?.id === chapter.id }"
              >
                <div class="chapter-item__row">
                  <button type="button" class="chapter-title" @click="loadBookChapter(chapter.id)">
                    {{ chapter.title }}
                  </button>

                  <button
                    type="button"
                    class="chapter-toggle"
                    :aria-expanded="!!expandedChapters[chapter.id]"
                    :aria-label="`Toggle ${chapter.title}`"
                    @click.stop="toggleChapterCollapse(chapter.id)"
                  >
                    <svg
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      :class="{ 'rotate-180': expandedChapters[chapter.id] }"
                    >
                      <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                  </button>
                </div>

                <div v-if="expandedChapters[chapter.id]" class="chapter-sections">
                  <button
                    v-if="chapter.title === 'Introduction & Preface'"
                    type="button"
                    class="section-link"
                    :class="{ 'section-link--active': booksStore.currentChapter?.id === chapter.id }"
                    @click="loadBookChapter(chapter.id)"
                  >
                    <span class="section-bullet">01</span>
                    <span>{{ $t('chat.overview') }}</span>
                  </button>

                  <template v-else>
                    <button
                      v-for="section in currentChapterPages"
                      :key="section.id"
                      type="button"
                      class="section-link"
                      :class="{
                        'section-link--active':
                          booksStore.currentChapter?.id === chapter.id &&
                          sectionToPageMap.get(section.id) === currentPage - 1,
                      }"
                      :style="{ paddingLeft: section.depth > 0 ? `${(section.depth * 1.5) + 0.75}rem` : '' }"
                      @click="jumpToSection(chapter.id, section.id)"
                    >
                      <span class="section-bullet" :style="{ opacity: section.depth > 0 ? 0.5 : 1 }">
                        <template v-if="section.depth > 0">-</template>
                        <template v-else>•</template>
                      </span>
                      <span>{{ section.title }}</span>
                    </button>

                    <RouterLink :to="`/chapters/${chapter.id}/quiz`" class="chapter-quiz-link" data-tour="quiz">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m9 12 2 2 4-4M7.8 4.7a3.4 3.4 0 0 0 1.9-.8 3.4 3.4 0 0 1 4.4 0 3.4 3.4 0 0 0 1.9.8 3.4 3.4 0 0 1 3.1 3.1 3.4 3.4 0 0 0 .8 1.9 3.4 3.4 0 0 1 0 4.4 3.4 3.4 0 0 0-.8 1.9 3.4 3.4 0 0 1-3.1 3.1 3.4 3.4 0 0 0-1.9.8 3.4 3.4 0 0 1-4.4 0 3.4 3.4 0 0 0-1.9-.8 3.4 3.4 0 0 1-3.1-3.1 3.4 3.4 0 0 0-.8-1.9 3.4 3.4 0 0 1 0-4.4 3.4 3.4 0 0 0 .8-1.9 3.4 3.4 0 0 1 3.1-3.1Z" />
                      </svg>
                      {{ $t('chapter.take_quiz') }}
                    </RouterLink>
                  </template>
                </div>
              </article>
            </div>

            <button
              v-if="hasHiddenChapters"
              type="button"
              class="show-more-button"
              @click="toggleShowAll"
            >
              {{ isShowingAll ? $t('chapter.show_less') : $t('chapter.show_more') }}
            </button>
          </section>
        </div>
      </div>

      <!-- Sidebar Footer (Profile & Settings - ChatGPT Style) -->
      <div class="sidebar-footer" ref="displaySettingsRef">
        <!-- ChatGPT-style User Pill Button -->
        <button
          type="button"
          class="chatgpt-user-pill"
          :class="{ 'is-active': isDisplaySettingsOpen }"
          @click.stop="toggleDisplaySettings"
          :aria-expanded="isDisplaySettingsOpen"
          aria-label="User menu and settings"
        >
          <div class="chatgpt-user-avatar">
            <template v-if="authStore?.user">
              {{ authStore?.user?.first_name?.charAt(0) || authStore?.user?.name?.charAt(0) || 'U' }}
            </template>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="3.5" />
              <path d="M5 20a7 7 0 0 1 14 0" />
            </svg>
          </div>

          <div class="chatgpt-user-info">
            <span class="chatgpt-user-name">
              {{ authStore?.user?.first_name || authStore?.user?.name || 'User' }}
            </span>
            <span class="chatgpt-user-role">
              {{ authStore?.user?.role || 'Reader' }}
            </span>
          </div>

          <div class="chatgpt-user-dots">
            <svg viewBox="0 0 24 24" fill="currentColor">
              <circle cx="5" cy="12" r="2" />
              <circle cx="12" cy="12" r="2" />
              <circle cx="19" cy="12" r="2" />
            </svg>
          </div>
        </button>

        <!-- ChatGPT-style Floating Popover Menu -->
        <Transition name="chatgpt-popover">
          <div v-if="isDisplaySettingsOpen" class="chatgpt-popover-menu" @click.stop>
            <!-- User preview header inside menu -->
            <div class="chatgpt-menu-user-row">
              <div class="chatgpt-user-avatar chatgpt-user-avatar--sm">
                {{ authStore?.user?.first_name?.charAt(0) || authStore?.user?.name?.charAt(0) || 'U' }}
              </div>
              <div class="chatgpt-menu-user-meta">
                <span class="chatgpt-menu-user-name">{{ authStore?.user?.name || authStore?.user?.first_name || 'User' }}</span>
                <span class="chatgpt-menu-user-email">{{ authStore?.user?.email || $t('chat.active_reader') }}</span>
              </div>
            </div>

            <div class="chatgpt-menu-divider"></div>

            <!-- Settings (Opens ChatGPT Settings Dialog) -->
            <button type="button" class="chatgpt-menu-item" @click="openSettingsModal('general')">
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.settings', 'Settings') }}</span>
            </button>

            <!-- Appearance (Inline expandable accordion) -->
            <button
              type="button"
              class="chatgpt-menu-item"
              :class="{ 'is-expanded': activeSettingsSubmenu === 'appearance' }"
              @click="activeSettingsSubmenu = activeSettingsSubmenu === 'appearance' ? null : 'appearance'"
            >
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="4"></circle>
                  <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.appearance', 'Appearance') }}</span>
              <span class="chatgpt-menu-pill-tag">{{ THEMES[readerTheme]?.label || 'Dark' }}</span>
              <svg class="chatgpt-menu-chevron" :class="{ 'is-rotated': activeSettingsSubmenu === 'appearance' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Inline Theme Selector Strip -->
            <div v-if="activeSettingsSubmenu === 'appearance'" class="chatgpt-inline-accordion">
              <div class="chatgpt-inline-themes-grid">
                <button
                  v-for="(item, key) in THEMES"
                  :key="key"
                  type="button"
                  class="chatgpt-inline-theme-chip"
                  :class="{ 'is-selected': readerTheme === key }"
                  @click="setTheme(key)"
                >
                  <span class="chatgpt-chip-swatch" :style="{ background: item.vars['--rt-surface'], borderColor: item.vars['--rt-border'] }"></span>
                  <span class="chatgpt-chip-label">{{ item.label }}</span>
                  <svg v-if="readerTheme === key" class="chatgpt-chip-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Font (Inline expandable) -->
            <button
              type="button"
              class="chatgpt-menu-item"
              :class="{ 'is-expanded': activeSettingsSubmenu === 'font' }"
              @click="activeSettingsSubmenu = activeSettingsSubmenu === 'font' ? null : 'font'"
            >
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="4 7 4 4 20 4 20 7"></polyline>
                  <line x1="9" y1="20" x2="15" y2="20"></line>
                  <line x1="12" y1="4" x2="12" y2="20"></line>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.font', 'Font') }}</span>
              <span class="chatgpt-menu-pill-tag">{{ readerFont === 'serif' ? 'Serif' : 'Sans' }}</span>
              <svg class="chatgpt-menu-chevron" :class="{ 'is-rotated': activeSettingsSubmenu === 'font' }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"></polyline>
              </svg>
            </button>

            <!-- Inline Font Segmented Switch -->
            <div v-if="activeSettingsSubmenu === 'font'" class="chatgpt-inline-accordion">
              <div class="chatgpt-inline-font-switch">
                <button
                  type="button"
                  class="chatgpt-font-switch-btn"
                  :class="{ 'is-selected': readerFont === 'sans' }"
                  @click="setFont('sans')"
                >
                  <span style="font-family: sans-serif; font-weight: 600;">Sans</span>
                  <span class="chatgpt-font-subtext">{{ $t('chat.sans', 'Modern') }}</span>
                </button>
                <button
                  type="button"
                  class="chatgpt-font-switch-btn"
                  :class="{ 'is-selected': readerFont === 'serif' }"
                  @click="setFont('serif')"
                >
                  <span style="font-family: serif; font-weight: 600;">Serif</span>
                  <span class="chatgpt-font-subtext">{{ $t('chat.serif', 'Book') }}</span>
                </button>
              </div>
            </div>

            <!-- Profile item -->
            <RouterLink to="/profile" class="chatgpt-menu-item" @click="isDisplaySettingsOpen = false">
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.profile', 'Profile') }}</span>
            </RouterLink>

            <div class="chatgpt-menu-divider"></div>

            <!-- Clear Chat History -->
            <button
              type="button"
              class="chatgpt-menu-item chatgpt-menu-item--danger"
              @click="handleClearChatFromSettings"
            >
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.clear_hist', 'Clear chat history') }}</span>
            </button>

            <!-- Sign Out -->
            <button type="button" class="chatgpt-menu-item" @click="signOut">
              <span class="chatgpt-menu-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                  <polyline points="16 17 21 12 16 7"></polyline>
                  <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
              </span>
              <span class="chatgpt-menu-label">{{ $t('chat.signout', 'Log out') }}</span>
            </button>
          </div>
        </Transition>
      </div>

    </aside>

    <!-- =========================================================
         WORKSPACE (Main Area + Right Sidebar)
    ========================================================== -->
    <div class="workspace" style="flex: 1; display: flex; flex-direction: column; min-width: 0; position: relative;">

      <div v-if="viewMode === 'reading'" class="reading-progress-track">
        <div class="reading-progress-value" :style="{ width: `${readingProgress}%` }"></div>
      </div>

      <!-- Floating Read Book with AI Button for Desktop -->
      <button
        v-if="!isMobile"
        type="button"
        class="btn-read-with-ai"
        style="position: absolute; top: 12px; left: 16px; z-index: 60;"
        :class="{ 'is-active': isReaderOpen && isAiSidebarOpen }"
        @click="toggleSplitScreen"
      >
        {{ $t('chat.read_ai') }}
      </button>

      <header v-if="isMobile" class="reader-header">
        <div class="reader-header__left">
          <div class="mobile-header-left">
            <div class="mobile-header-top-row">
              <RouterLink to="/dashboard" class="mobile-brand-row">
                <img src="/logo.png" alt="Smart Adama" class="mobile-brand-logo" />
                <span class="mobile-brand-text">{{ $t('chat.brand') }}</span>
              </RouterLink>
              <span class="mobile-chapter-title">
                {{ booksStore.currentChapter?.title || 'Select a chapter' }}
              </span>
            </div>
            <div class="mobile-header-bottom-row">
              <button
                type="button"
                class="mobile-sidebar-toggle"
                aria-label="Open sidebar"
                title="Open sidebar"
                @click="openSidebar"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="m11 17 5-5-5-5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="m6 17 5-5-5-5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div class="reader-header__right">
          <div class="mobile-header-right">
            <div class="mobile-header-top-row mobile-header-top-row--right">
              <!-- Mobile Hamburger Settings (Right Top) -->
              <div class="mobile-settings-wrapper" ref="mobileSettingsMenuRef">
                <button
                  type="button"
                  class="mobile-settings-toggle"
                  aria-label="Settings"
                  title="Settings"
                  @click="isMobileSettingsOpen = !isMobileSettingsOpen"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="5" r="1" />
                    <circle cx="12" cy="12" r="1" />
                    <circle cx="12" cy="19" r="1" />
                  </svg>
                </button>

                <Transition name="menu">
                  <div v-if="isMobileSettingsOpen" class="mobile-settings-dropdown">

                    <!-- Reader Mode removed from here -->

                    <template v-if="viewMode === 'reading'">
                      <!-- Page Control -->
                      <div class="mobile-settings-section">
                        <div class="page-control page-control--mobile">
                          <button type="button" :disabled="currentPage <= 1" aria-label="Previous page" @click="prevPage">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                              <path d="m15 19-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </button>
                          <label class="page-jump">
                            <input
                              v-model="jumpPageInput"
                              aria-label="Current page"
                              inputmode="numeric"
                              @blur="applyJumpPage"
                              @keydown.enter.prevent="applyJumpPage"
                            />
                          </label>
                          <span class="page-total">/ {{ totalPages }}</span>
                          <button
                            type="button"
                            :disabled="currentPage >= totalPages"
                            aria-label="Next page"
                            @click="nextPage"
                          >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                              <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </button>
                        </div>
                      </div>

                      <!-- Mode Switching Dropdown -->
                      <div class="mobile-settings-section">
                        <button
                          type="button"
                          class="mobile-menu-item"
                          @click="viewMode = 'quiz'; isMobileSettingsOpen = false"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mr-2">
                            <path d="m9 11 3 3L22 4" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                          <span>{{ $t('chat.take_quiz') }}</span>
                        </button>
                        <button
                          type="button"
                          class="mobile-menu-item"
                          @click="viewMode = 'mindmap'; isMobileSettingsOpen = false"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 mr-2">
                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                          </svg>
                          <span>{{ $t('chat.mind_map') }}</span>
                        </button>
                      </div>
                    </template>

                    <!-- Theme Switcher -->
                    <template v-if="viewMode === 'reading'">
                      <div class="mobile-settings-section theme-grid">
                        <button
                          v-for="(item, key) in themeDefinitions"
                          :key="key"
                          type="button"
                          class="theme-button"
                          :class="{ 'is-active': readerTheme === key }"
                          @click="setTheme(key)"
                        >
                          <span class="theme-swatch" :style="{ background: item.vars['--rt-surface'] }"></span>
                          {{ item.label }}
                        </button>
                      </div>
                    </template>

                  </div>
                </Transition>
              </div>
            </div>
            <div class="mobile-header-bottom-row mobile-header-bottom-row--right">
              <button
                type="button"
                class="mobile-sidebar-toggle"
                aria-label="Open AI assistant"
                title="Open AI assistant"
                @click="openAiSidebar"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <path d="m13 17-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="m18 17-5-5 5-5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </header>

      <div class="workspace-content" style="flex: 1; display: flex; min-height: 0;">
        <!-- =========================================================
             MAIN READER
        ========================================================== -->
        <main
          v-show="isReaderOpen"
          class="reader-main"
          data-tour="reader"
          style="flex: 1; min-width: 0; position: relative;"
          :style="{ borderRight: isReaderOpen && isAiSidebarOpen && !isMobile ? '1px solid var(--reader-border)' : 'none' }"
        >
          <div class="reading-canvas-controls" v-if="!isMobile && viewMode === 'reading'">
            <button @click="zoomOutReader" class="reading-canvas-btn" title="Zoom Out">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
            <button class="reading-canvas-zoom-label" @click="readerScale = 125" title="Reset to 125%">
              {{ readerScale }}%
            </button>
            <button @click="zoomInReader" class="reading-canvas-btn" title="Zoom In">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            </button>
          </div>

      <!-- =====================================================
           READING VIEW
      ====================================================== -->
      <div
        v-if="viewMode === 'reading'"
        ref="scrollAreaRef"
        class="reader-scroll"
        @wheel="handleReaderWheel"
      >
        <div
          ref="readerContainerRef"
          class="reader-column"
          :style="{
            width: '100%',
            transform: overscrollAccum ? `translateY(${overscrollDir === 'down' ? -overscrollAccum : overscrollAccum}px)` : 'none',
            transition: overscrollAccum === 0 ? 'transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1)' : 'none'
          }"
        >

          <Transition name="page-slide" mode="out-in">
            <article
              :key="currentPage"
              class="reader-paper"
              :class="[
                readerFont === 'serif' ? 'reader-paper--serif' : 'reader-paper--sans',
                { 'reader-paper--preface': booksStore.currentChapter?.title === 'Introduction & Preface' }
              ]"
              :style="isMobile ? { minHeight: 'calc(100dvh - 7rem)' } : {
                fontSize: `${readerScale}%`,
                minHeight: '800px',
              }"
            >
            <div class="reader-paper__inner">
              <template v-if="booksStore.currentChapter?.title === 'Introduction & Preface'">
                <IntroductionPreface
                  :current-page="currentPage"
                  @go-to-chapter="handleTocClick"
                />
              </template>

              <template v-else-if="(booksStore.loading || isChapterLoading) && !currentPageData">
                <div class="reader-skeleton" aria-busy="true" aria-live="polite">
                  <div class="skeleton-header">
                    <div class="skeleton-line skeleton-meta"></div>
                    <div class="skeleton-line skeleton-title"></div>
                    <div class="skeleton-divider"></div>
                  </div>
                  <div class="skeleton-body">
                    <div class="skeleton-line skeleton-h2"></div>
                    <div class="skeleton-line skeleton-p"></div>
                    <div class="skeleton-line skeleton-p"></div>
                    <div class="skeleton-line skeleton-p" style="width: 85%;"></div>
                    <div class="skeleton-line skeleton-p" style="width: 65%;"></div>
                    <div class="skeleton-line skeleton-h2" style="margin-top: 2rem;"></div>
                    <div class="skeleton-line skeleton-p"></div>
                    <div class="skeleton-line skeleton-p" style="width: 90%;"></div>
                    <div class="skeleton-line skeleton-p" style="width: 70%;"></div>
                  </div>
                  <div class="reader-loading-label">
                    <svg class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10" stroke-opacity="0.25" />
                      <path d="M12 2a10 10 0 0 1 10 10" />
                    </svg>
                    <span>{{ $t('chat.loading_chapter') || 'Loading chapter content...' }}</span>
                  </div>
                </div>
              </template>

              <template v-else-if="chapterLoadError && !currentPageData">
                <div class="reader-error">
                  <div class="reader-error__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                  </div>
                  <h2>{{ $t('chat.err_load_chapter') }}</h2>
                  <p>{{ chapterLoadError }}</p>
                  <button type="button" class="reader-retry-btn" @click="retryLoadChapter">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                      <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                      <path d="M3 3v5h5" />
                      <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                      <path d="M16 21h5v-5" />
                    </svg>
                    <span>{{ $t('chat.try_again') }}</span>
                  </button>
                </div>
              </template>

              <template v-else-if="!booksStore.currentChapter">
                <div class="reader-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                  </svg>
                  <h2>{{ booksStore.books.length === 0 ? $t('chat.err_load_course') : $t('chat.select_a_chapter') }}</h2>
                  <p>{{ booksStore.books.length === 0 ? $t('chat.conn_error') : $t('chat.select_chapter') }}</p>
                  <button type="button" class="reader-retry-btn" @click="retryLoadChapter">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                      <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                      <path d="M3 3v5h5" />
                      <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                      <path d="M16 21h5v-5" />
                    </svg>
                    <span>{{ booksStore.books.length === 0 ? $t('chat.reload_course') : $t('chat.open_first') }}</span>
                  </button>
                </div>
              </template>

              <template v-else>
                <div v-if="currentPageData && currentPageData.sections.length > 1" class="section-chips">
                  <button
                    v-for="section in currentPageData.sections"
                    :key="section.id"
                    type="button"
                    class="section-chip"
                    @click="scrollToSection(section.id)"
                  >
                    {{ section.title }}
                  </button>
                </div>

                <div v-if="currentPageData" class="reader-content">
                  <header class="reading-canvas-header" v-if="currentPage === 1 && booksStore.currentChapter?.title && booksStore.currentChapter.title !== 'Introduction & Preface'">
                    <div class="reading-canvas-metadata">{{ $t('chat.book_title') }}</div>
                    <div class="reading-canvas-chapter">
                      {{ booksStore.currentChapter.title.toUpperCase() }}
                    </div>
                    <hr class="reading-canvas-divider" />
                  </header>
                  <section
                    v-for="section in currentPageData.sections"
                    :key="section.id"
                    :id="`sec-${section.id}`"
                    class="reader-section"
                  >
                    <h2>{{ section.title }}</h2>

                    <div class="reader-markdown-content" v-html="renderMarkdown(section.raw_text || '')"></div>
                  </section>
                </div>

                <div v-else class="reader-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M5 4h14v16H5V4ZM9 8h6M9 12h6M9 16h4" />
                  </svg>
                  <h2>{{ $t('chat.no_content') }}</h2>
                  <p>{{ $t('chat.select_another') }}</p>
                </div>
              </template>

              <template v-if="booksStore.currentChapter && currentPageData && currentPage === totalPages && booksStore.currentChapter?.title !== 'Introduction & Preface'">
                <div class="mt-16 mb-8 p-8 bg-[var(--rt-surface-2)] border border-[var(--rt-border)] rounded-2xl flex flex-col items-center justify-center text-center shadow-sm">
                  <div class="w-16 h-16 bg-[var(--rt-surface)] border border-[var(--rt-border)] rounded-full flex items-center justify-center shadow-sm mb-4">
                    <span class="text-3xl">🎉</span>
                  </div>
                  <h3 class="text-xl font-bold text-[var(--rt-text)] mb-2">You've reached the end of {{ booksStore.currentChapter?.title }}</h3>
                  <p class="text-[var(--rt-muted)] mb-6 max-w-md">{{ $t('chat.complete_to_unlock') }}</p>
                  <div class="flex flex-wrap items-center justify-center gap-3">
                    <button
                      type="button"
                      class="px-6 py-3 bg-[#04AA6D] text-white font-bold rounded-xl hover:bg-[#039660] transition-colors shadow-sm shadow-emerald-500/20 flex items-center gap-2 cursor-pointer"
                      @click="markCompleteAndNextChapter"
                    >
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 7" stroke-linecap="round" stroke-linejoin="round" /></svg>
                      Finish Chapter
                    </button>
                    <button
                      type="button"
                      :disabled="isNavigatingToQuiz"
                      class="px-6 py-3 bg-[#395886] text-white font-bold rounded-xl hover:bg-[#2e476d] transition-colors shadow-sm shadow-blue-500/20 flex items-center gap-2 cursor-pointer disabled:opacity-50"
                      @click="takeChapterQuiz"
                    >
                      <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      {{ isNavigatingToQuiz ? $t('chat.loading_quiz') : $t('chat.take_quiz') }}
                    </button>
                  </div>
                </div>
              </template>

              <footer v-if="currentPageData || booksStore.currentChapter?.title === 'Introduction & Preface'" class="reader-footer">
                <RouterLink
                  v-if="currentPage === 1"
                  to="/dashboard"
                  class="page-action page-action--secondary"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="m15 19-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <span>{{ $t('dashboard.title') }}</span>
                </RouterLink>

                <button
                  v-else
                  type="button"
                  class="page-action page-action--secondary"
                  @click="prevPage"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="m15 19-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <span>{{ $t('chapter.prev') }}</span>
                </button>

                <button
                  v-if="currentPage < totalPages"
                  type="button"
                  class="page-action page-action--primary"
                  @click="nextPage"
                >
                  <span>{{ $t('chapter.next') }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
              </footer>
            </div>
          </article>
          </Transition>
        </div>
      </div>

      <!-- =====================================================
           PDF VIEW
      ====================================================== -->
      <div v-else class="pdf-view">
        <iframe
          v-if="pdfUrl"
          :src="pdfUrl"
          class="pdf-view__frame"
          title="Original Smart Adama PDF"
        ></iframe>

        <div v-else class="pdf-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
            <path d="M6 3h8l4 4v14H6V3ZM14 3v5h5M9 12h6M9 16h6" />
          </svg>
          <h2>{{ $t('chapter.no_pdf') }}</h2>
        </div>

        <!-- Floating AI FAB with spinning glow border + Smart AI label -->
        <div class="ai-fab-wrap" data-tour="study-ai" :class="{ 'ai-fab-wrap--label': aiLabelVisible }">
          <!-- Spinning conic-gradient glow ring -->
          <div class="ai-fab-ring" aria-hidden="true"></div>
          <!-- Animated "Smart AI" label pill -->
          <Transition name="ai-label">
            <span v-if="aiLabelVisible" class="ai-fab-label" aria-hidden="true">Smart AI</span>
          </Transition>
          <button type="button" class="pdf-ai-button" @click="toggleAiSidebar(true)" aria-label="Open Smart AI Assistant">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M13 10V3L4 14h7v7l9-11h-7Z" stroke-linejoin="round" />
            </svg>
          </button>
        </div>

      </div>
    </main>

    <!-- =========================================================
         RIGHT AI SIDEBAR
    ========================================================== -->
    <aside
      v-show="isAiSidebarOpen && !isFullscreen"
      class="side-panel side-panel--right"
      :style="isMobile ? undefined : { flex: isReaderOpen ? '0 0 auto' : 1, width: isReaderOpen ? '500px' : 'auto' }"
      :class="{ 'side-panel--mobile': isMobile, 'chat-full-screen': !isReaderOpen && !isMobile, 'chat-is-empty': !hasMessages }"
      aria-label="Smart Adama AI Assistant"
    >
      <!-- Mobile header for AI sidebar — ChatGPT style -->
      <div v-if="isMobile" class="mobile-ai-topbar">
        <div class="mobile-ai-topbar__left">
          <div class="mobile-ai-model-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path d="M13 10V3L4 14h7v7l9-11h-7Z" stroke-linejoin="round"/></svg>
            <span>Smart Adama AI</span>
          </div>
        </div>
        <div class="mobile-ai-topbar__right">
          <button
            type="button"
            class="mobile-ai-icon-btn"
            aria-label="New chat"
            title="New chat"
            @click="startNewChat"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <button
            type="button"
            class="mobile-ai-icon-btn"
            aria-label="Close AI panel"
            title="Close AI panel"
            @click="isAiSidebarOpen = false"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>

      <!-- EMPTY / WELCOME STATE: greeting + composer centered together -->
      <div v-if="!hasMessages" class="ai-empty-state">
        <div class="ai-empty-state__center">
          <Transition name="fade-greeting" mode="out-in">
            <h2
              :key="activeGreetingKey + (chatStore.currentSession?.id || 'new')"
              class="ai-empty-state__greeting"
            >
              {{ dynamicGreeting }}
            </h2>
          </Transition>
          <form class="ai-composer ai-composer--empty" @submit.prevent="sendMessage">
            <div class="ai-composer__field">
              <textarea
                ref="chatInputRef"
                v-model="chatInput"
                @input="autoResizeInput"
                @keydown="handleEnter"
                rows="1"
                class="ai-composer__textarea"
                :placeholder="$t('chapter.ask_placeholder')"
              ></textarea>
              <button
                v-if="!chatStore.streaming || chatInput.trim()"
                type="submit"
                class="send-button"
                :class="{ 'send-button--active': !!chatInput.trim() }"
                :disabled="!chatInput.trim() || isSubmitting"
                :aria-label="$t('chat.send')"
              >
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 11h11.586l-5.293-5.293 1.414-1.414L20.414 12l-8.707 8.707-1.414-1.414L15.586 13H4v-2z"/></svg>
              </button>
              <button
                v-else
                type="button"
                class="send-button stop-button"
                aria-label="Stop generating"
                @click.prevent="chatStore.cancelAllStreams()"
              >
                <svg viewBox="0 0 24 24" fill="currentColor"><rect x="7" y="7" width="10" height="10" rx="1.5"/></svg>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- CONVERSATION THREAD + COMPOSER (when messages exist) -->
      <template v-else>
        <div ref="messagesContainerRef" class="ai-messages" @scroll="handleChatScroll" @click="handleMessagesClick">
          <div v-for="msg in currentMessages" :key="msg.id" :id="'msg-' + msg.id" class="message-stack w-full flex mb-8">

            <!-- USER MESSAGE -->
            <div v-if="msg.role === 'user'" class="message-stack__user w-full flex justify-end group relative">
              <div class="message message--user bg-[var(--rt-surface-2)] text-[var(--rt-text)] px-5 py-3 rounded-[24px] rounded-br-[8px] max-w-[75%] inline-block text-base leading-relaxed break-words shadow-sm">
                {{ msg.content }}

                <div class="absolute -bottom-8 right-0 opacity-0 group-hover:opacity-100 transition-opacity flex flex-row gap-1 z-10">
                  <button @click="editMessage(msg)" class="p-1 text-[var(--rt-muted)] hover:text-[var(--rt-text)] bg-[var(--rt-surface)] rounded-md border border-[var(--rt-border)] shadow-sm" title="Edit">
                     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                  </button>
                  <button @click="copyText(msg.content, msg.id)" class="p-1 text-[var(--rt-muted)] hover:text-[var(--rt-text)] bg-[var(--rt-surface)] rounded-md border border-[var(--rt-border)] shadow-sm" title="Copy">
                     <svg v-if="copiedMessageId === msg.id" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                     <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- AI MESSAGE -->
            <div v-else class="message-stack__assistant flex gap-4 w-full">
              <div class="flex-1 min-w-0">
                <article class="message message--assistant text-[var(--rt-text)] w-full">
                  <!-- ERROR STATE (NO CONTENT GENERATED) -->
                  <div v-if="msg.error && !msg.content" class="error-message">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                      <path d="M12 9v3m0 4h.01M10.3 4.5 2.8 17.5A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.5L13.7 4.5a2 2 0 0 0-3.4 0Z" />
                    </svg>
                    <div>
                      <strong>{{ $t('chat.ai_down') }}</strong>
                      <p>{{ msg.error }}</p>
                    </div>
                  </div>

                  <!-- PENDING / THINKING STATE -->
                  <div v-else-if="msg.isStreaming && msg.isPending" class="flex items-center gap-3 py-1">
                    <div class="thinking-dots flex gap-1 items-center" aria-label="Thinking">
                      <span class="w-1.5 h-1.5 rounded-full bg-[var(--rt-muted)] animate-bounce"></span>
                      <span class="w-1.5 h-1.5 rounded-full bg-[var(--rt-muted)] animate-bounce" style="animation-delay: 0.2s"></span>
                      <span class="w-1.5 h-1.5 rounded-full bg-[var(--rt-muted)] animate-bounce" style="animation-delay: 0.4s"></span>
                    </div>
                    <span v-if="msg.activity?.message" class="text-[var(--rt-muted)] text-sm italic opacity-80">
                      {{ msg.activity.message }}
                    </span>
                  </div>

                  <!-- STREAMING CONTENT STATE -->
                  <div v-else-if="msg.isStreaming && !msg.isPending" class="streaming-text">
                    <span class="ai-response-content" v-html="renderMarkdown(msg.content)"></span>
                    <span class="streaming-cursor ml-1 inline-block">▋</span>
                  </div>

                  <!-- COMPLETED OR PARTIAL ERROR STATE -->
                  <template v-else>
                    <div v-html="renderMarkdown(msg.content)" class="ai-response-content"></div>

                    <!-- PARTIAL STREAM WARNING / RETRY NOTICE -->
                    <div v-if="msg.error" class="partial-error-notice mt-3 p-2.5 rounded-lg border border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs flex items-center gap-2">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 shrink-0">
                        <path d="M12 9v3m0 4h.01M10.3 4.5 2.8 17.5A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.5L13.7 4.5a2 2 0 0 0-3.4 0Z" />
                      </svg>
                      <span>{{ msg.error }} — Partial response preserved. You can send a follow-up to continue.</span>
                    </div>

                    <!-- AI Action Row -->
                    <div class="feedback-row flex items-center gap-1.5 mt-3">
                      <button
                        type="button"
                        class="feedback-button feedback-up p-1.5 text-[var(--rt-muted)] hover:text-[var(--rt-text)] hover:bg-[var(--rt-surface-2)] rounded-md transition-colors"
                        :class="{ 'text-blue-500': msg.feedback?.feedback === 'like' }"
                        @click="toggleFeedback(msg, 'like')"
                        title="Helpful"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                          <path d="M14 10h4.7a2 2 0 0 1 1.8 2.9l-3.5 7A2 2 0 0 1 15.2 21h-4a2 2 0 0 1-.5-.1L7 20m7-10V5a2 2 0 0 0-2-2h-.1a.9.9 0 0 0-.9.9 3.8 3.8 0 0 1-.6 2L7 11v9M7 20H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2" />
                        </svg>
                      </button>

                      <button
                        type="button"
                        class="feedback-button feedback-down p-1.5 text-[var(--rt-muted)] hover:text-[var(--rt-text)] hover:bg-[var(--rt-surface-2)] rounded-md transition-colors"
                        :class="{ 'text-red-500': msg.feedback?.feedback === 'dislike' }"
                        @click="toggleFeedback(msg, 'dislike')"
                        title="Not helpful"
                      >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                          <path d="M10 14H5.3a2 2 0 0 1-1.8-2.9l3.5-7A2 2 0 0 1 8.8 3h4a2 2 0 0 1 .5.1L17 4m-7 10v5a2 2 0 0 0 2 2h.1a.9.9 0 0 0 .9-.9 3.8 3.8 0 0 1 .6-2L17 13V4m0 0h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2" />
                        </svg>
                      </button>

                      <!-- Copy AI Response -->
                      <button
                        type="button"
                        class="feedback-button feedback-copy p-1.5 text-[var(--rt-muted)] hover:text-[var(--rt-text)] hover:bg-[var(--rt-surface-2)] rounded-md transition-colors"
                        @click="copyText(msg.content, msg.id)"
                        title="Copy text"
                      >
                        <svg v-if="copiedMessageId === msg.id" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-green-500">
                          <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                          <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                          <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                      </button>

                      <!-- Sources Tooltip -->
                      <div class="source-tooltip-container relative group inline-block ml-1">
                        <button type="button" class="feedback-button feedback-source p-1.5 px-2 flex items-center gap-1 text-[var(--rt-muted)] hover:text-[var(--rt-text)] hover:bg-[var(--rt-surface-2)] rounded-md transition-colors" title="View Sources">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                          </svg>
                          <span class="text-[11px] font-semibold tracking-wide">{{ $t('chat.sources') }}</span>
                        </button>
                        <!-- Tooltip popup -->
                        <div class="source-tooltip absolute bottom-full left-0 mb-2 hidden group-hover:block w-72 p-3 bg-[var(--rt-surface)] border border-[var(--rt-border)] rounded-lg shadow-xl text-xs text-[var(--rt-text-body)] z-50 text-left">
                          <div class="font-bold mb-2 text-[var(--rt-text)]">{{ $t('chat.sources') }}</div>
                          <ul v-if="msg.sources && msg.sources.length" class="space-y-1.5">
                            <li v-for="(src, idx) in msg.sources" :key="idx" class="truncate flex items-center gap-1.5">
                              <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                              {{ src.chapter_title || src.heading || 'Context' }}
                              <span v-if="src.vector_score || src.similarity_score" class="text-[10px] opacity-75 shrink-0">(Match: {{ Math.round((src.vector_score || src.similarity_score || 0) * 100) }}%)</span>
                            </li>
                          </ul>
                          <div v-else class="text-[var(--rt-muted)] italic mt-1">{{ $t('chat.no_sources') }}</div>
                        </div>
                      </div>
                    </div>
                  </template>
                </article>
              </div>
            </div>
          </div>
        </div>

        <!-- Scroll to bottom button -->
        <button
          v-show="showScrollButton"
          @click="scrollToBottom"
          class="absolute bottom-28 left-1/2 -translate-x-1/2 z-50 p-2 bg-(--rt-surface-2) border border-(--rt-border) rounded-full shadow-md text-(--rt-text-body) hover:text-(--rt-text) hover:bg-(--rt-surface) transition-all"
          title="Scroll to bottom"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
            <path d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </button>

        <!-- Feedback Toast -->
        <div
          v-if="feedbackToastMessage"
          class="absolute bottom-32 left-1/2 -translate-x-1/2 z-50 px-3 py-1.5 bg-[#10b981] text-white text-xs font-medium rounded-full shadow-lg transition-opacity duration-300"
        >
          {{ feedbackToastMessage }}
        </div>

        <form class="ai-composer" data-tour="ai-composer" @submit.prevent="sendMessage">
          <div class="ai-composer__field">
            <textarea
              ref="chatInputRef"
              v-model="chatInput"
              @input="autoResizeInput"
              @keydown="handleEnter"
              rows="1"
              class="ai-composer__textarea"
              :placeholder="$t('chapter.ask_placeholder')"
            ></textarea>

            <button
              v-if="!chatStore.streaming || chatInput.trim()"
              type="submit"
              class="send-button"
              :class="{ 'send-button--active': !!chatInput.trim() }"
              :disabled="!chatInput.trim() || isSubmitting"
              :aria-label="$t('chat.send')"
            >
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 11h11.586l-5.293-5.293 1.414-1.414L20.414 12l-8.707 8.707-1.414-1.414L15.586 13H4v-2z" />
              </svg>
            </button>

            <button
              v-else
              type="button"
              class="send-button stop-button"
              aria-label="Stop generating"
              @click.prevent="chatStore.cancelAllStreams()"
            >
              <svg viewBox="0 0 24 24" fill="currentColor">
                <rect x="7" y="7" width="10" height="10" rx="1.5" />
              </svg>
            </button>
          </div>
        </form>
      </template>
    </aside>

      </div> <!-- /workspace-content -->
    </div> <!-- /workspace -->


    <!-- =========================================================
         TEXT SELECTION ACTION
    ========================================================== -->
    <Transition name="selection">
      <div
        v-if="selectionPopup.visible"
        class="selection-popup"
        :style="{
          top: `${selectionPopup.y}px`,
          left: `${selectionPopup.x}px`,
        }"
      >
        <span>{{ $t('chat.ask_ai') }}</span>
        <button type="button" @click="askAiAboutSelection">
          {{ $t('chat.ask') }}
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
      </div>
    </Transition>



    <!-- Global Search Overlay (Command Palette Style) -->
    <Transition name="fade">
      <div v-if="isSidebarSearchActive" class="fixed inset-0 z-[9999] flex items-start justify-center pt-[20vh] bg-black/20" @click.self="toggleSidebarSearch">
        <div class="w-full max-w-2xl relative mx-4 ai-glow-wrap pointer-events-auto rounded-[24px] overflow-hidden p-[2px]" style="box-shadow: 0 8px 32px rgba(0, 0, 0, 0.14);" @dblclick.stop="toggleSidebarSearch">
          <!-- Circling Glowing Border -->
          <div class="absolute top-1/2 left-1/2 w-[200%] h-[500%] -translate-x-1/2 -translate-y-1/2 z-0" style="background: conic-gradient(from 0deg, #0A0A0A, #9A9A9A, #FFFFFF, #9A9A9A, #0A0A0A); animation: spinGlow 3.5s linear infinite;"></div>

          <!-- The inner white pill that blocks out the middle of the gradient -->
          <div class="ai-glow-inner relative z-10 flex flex-col bg-[var(--rt-surface)] rounded-[22px] overflow-hidden transition-all duration-300" :style="{ maxHeight: (searchResults || (!sidebarSearchQuery && globalSearchHistory.length)) ? '80vh' : 'auto' }">
            <div class="flex items-center gap-3 px-5 lg:px-6 py-2.5">
              <textarea
                v-model="sidebarSearchQuery"
                ref="sidebarSearchInputRef"
                class="ai-search-input flex-1 bg-transparent outline-none border-0 text-[var(--rt-text)] placeholder:text-[#a8a29e] text-sm md:text-base resize-none"
                style="outline: none !important; box-shadow: none !important; min-height: 24px; max-height: 120px;"
                rows="1"
                placeholder="Search..."
                @keydown.esc="toggleSidebarSearch"
                @keydown.enter="submitGlobalSearch"
                @input="handleSearchInput"
              ></textarea>
              <button
                class="w-7 h-7 flex items-center justify-center rounded-full text-[#a8a29e] hover:bg-[var(--rt-surface-2)] hover:text-[var(--rt-text)] transition shrink-0"
                aria-label="Close search"
                @click="toggleSidebarSearch"
                type="button"
              >
                ✕
              </button>
            </div>

            <!-- History List (Shown when empty) -->
            <div v-if="!sidebarSearchQuery && globalSearchHistory.length > 0" class="border-t border-[var(--rt-border)] overflow-y-auto p-2 bg-[var(--rt-surface-2)]">
              <div class="px-3 py-2 text-xs font-semibold text-[var(--rt-muted)] uppercase tracking-wider">{{ $t('chat.recent_searches') }}</div>
              <ul>
                <li v-for="hist in globalSearchHistory" :key="hist" class="px-3 py-2 text-sm text-[var(--rt-text)] hover:bg-[var(--rt-surface)] cursor-pointer rounded-lg flex items-center gap-3 transition-colors" @click="sidebarSearchQuery = hist; performHybridSearch()">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-[var(--rt-muted)]">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                  {{ hist }}
                </li>
              </ul>
            </div>

            <!-- Unified Search Results -->
            <div v-if="(searchResults || isSearchingGlobal || localSearchResults.messages.length || localSearchResults.sessions.length) && sidebarSearchQuery" class="border-t border-[var(--rt-border)] overflow-y-auto p-4 bg-[var(--rt-surface-2)]" style="max-height: calc(80vh - 60px);">

              <!-- 1. Local Sessions -->
              <div v-if="localSearchResults.sessions.length > 0" class="mb-6">
                <div class="px-2 pb-2 text-xs font-semibold text-[var(--rt-muted)] uppercase tracking-wider">{{ $t('chat.past_chats') }}</div>
                <div class="space-y-1">
                  <div v-for="session in localSearchResults.sessions" :key="session.id"
                       @click="goToSession(session.id)"
                       class="px-3 py-2 bg-[var(--rt-surface)] hover:bg-blue-50 border border-[var(--rt-border)] rounded-lg cursor-pointer transition-colors flex items-center gap-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 text-blue-500 shrink-0"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    <span class="text-sm font-medium text-[var(--rt-text)] truncate" v-html="highlightText(session.title, sidebarSearchQuery)"></span>
                  </div>
                </div>
              </div>

              <!-- 2. Local Chat Messages -->
              <div v-if="localSearchResults.messages.length > 0" class="mb-6">
                <div class="px-2 pb-2 text-xs font-semibold text-[var(--rt-muted)] uppercase tracking-wider">{{ $t('chat.current_msgs') }}</div>
                <div class="space-y-2">
                  <div v-for="msg in localSearchResults.messages" :key="msg.id"
                       @click="scrollToMessage(msg.id)"
                       class="px-3 py-2 bg-[var(--rt-surface)] hover:bg-blue-50 border border-[var(--rt-border)] rounded-lg cursor-pointer transition-colors">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="w-2 h-2 rounded-full" :class="msg.role === 'user' ? 'bg-gray-400' : 'bg-blue-500'"></span>
                      <span class="text-[10px] font-bold uppercase text-[var(--rt-muted)]">{{ msg.role === 'user' ? 'You' : 'AI' }}</span>
                    </div>
                    <div class="text-xs text-[var(--rt-text-body)] line-clamp-2" v-html="highlightText(msg.content, sidebarSearchQuery)"></div>
                  </div>
                </div>
              </div>

              <!-- 3. RAG Search -->
              <div class="px-2 pb-2 text-xs font-semibold text-[var(--rt-muted)] uppercase tracking-wider">{{ $t('chat.knowledge') }}</div>
              <div v-if="isSearchingGlobal" class="flex justify-center items-center py-6">
                <svg class="animate-spin h-6 w-6 text-[var(--rt-muted)]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </div>

              <div v-else-if="searchError" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ searchError }}
              </div>

              <div v-else-if="searchResults">
                <div class="flex items-center justify-between text-[10px] text-[var(--rt-muted)] mb-3 px-2">
                  <span>Found {{ filteredChunks.length }} chunks</span>
                  <span class="font-mono">{{ searchResults.duration_seconds }}s</span>
                </div>

                <div v-if="filteredChunks.length > 0" class="space-y-4">
                  <div v-for="(chunk, index) in filteredChunks" :key="chunk.id" class="bg-[var(--rt-surface)] rounded-xl border border-[var(--rt-border)] shadow-sm overflow-hidden hover:border-blue-300 transition-colors">
                    <div class="border-b border-[var(--rt-border)] bg-[var(--rt-surface-2)] px-4 py-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                      <div class="flex items-center gap-3">
                        <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded bg-blue-900 text-[10px] font-bold text-white">#{{ index + 1 }}</div>
                        <div class="flex flex-col">
                          <span class="text-[9px] font-semibold uppercase tracking-wider text-[var(--rt-muted)]">{{ $t('chat.rrf') }}</span>
                          <span class="text-xs font-bold text-blue-600 font-mono">{{ chunk.rrf_score.toFixed(4) }}</span>
                        </div>
                      </div>

                      <div class="flex flex-wrap items-center gap-3">
                        <div class="flex flex-col items-end gap-0.5">
                          <span class="text-[8px] uppercase tracking-wider text-[var(--rt-muted)] font-semibold">Semantic (#{{ chunk.vector_rank }})</span>
                          <span :class="['inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-mono font-semibold border', getScoreColor(chunk.vector_score)]">
                            {{ chunk.vector_score.toFixed(4) }}
                          </span>
                        </div>
                        <div class="w-px h-5 bg-[var(--rt-border)] hidden sm:block"></div>
                        <div class="flex flex-col items-end gap-0.5">
                          <span class="text-[8px] uppercase tracking-wider text-[var(--rt-muted)] font-semibold">Lexical (#{{ chunk.keyword_rank }})</span>
                          <span :class="['inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-mono font-semibold border', getScoreColor(chunk.keyword_score, true)]">
                            {{ chunk.keyword_score.toFixed(4) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <div class="px-4 py-3 space-y-3">
                      <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded bg-[var(--rt-surface-2)] px-2 py-0.5 text-[9px] font-medium text-[var(--rt-text)] border border-[var(--rt-border)]">Page {{ chunk.page_number || 'N/A' }}</span>
                        <span v-if="chunk.structural_context?.heading" class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-[9px] font-medium text-blue-700 border border-blue-200">{{ chunk.structural_context.heading }}</span>
                      </div>
                      <div class="text-[11px] leading-relaxed text-[var(--rt-text)] bg-[var(--rt-surface-2)] rounded-lg p-3">
                        <p v-html="highlightText(chunk.chunk_text, sidebarSearchQuery)"></p>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-6 text-[var(--rt-muted)] text-xs">
                  {{ $t('chat.no_chunks') }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- =========================================================
         CHATGPT-STYLE SETTINGS MODAL DIALOG
    ========================================================== -->
    <Teleport to="body">
      <Transition name="chatgpt-modal-fade">
        <div
          v-if="isSettingsModalOpen"
          class="chatgpt-modal-overlay"
          role="dialog"
          aria-modal="true"
          aria-labelledby="settings-dialog-title"
          @click.self="isSettingsModalOpen = false"
        >
          <div class="chatgpt-settings-dialog">
            <!-- Dialog Header -->
            <div class="chatgpt-dialog-header">
              <div class="chatgpt-dialog-header-left">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="chatgpt-dialog-header-icon">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <h2 id="settings-dialog-title" class="chatgpt-dialog-title">{{ $t('chat.settings', 'Settings') }}</h2>
              </div>
              <button
                type="button"
                class="chatgpt-dialog-close-btn"
                aria-label="Close settings"
                @click="isSettingsModalOpen = false"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>

            <!-- Dialog Body -->
            <div class="chatgpt-dialog-body">
              <!-- Left Navigation Tabs -->
              <nav class="chatgpt-dialog-sidebar">
                <button
                  type="button"
                  class="chatgpt-tab-btn"
                  :class="{ 'is-active': activeSettingsTab === 'general' }"
                  @click="activeSettingsTab = 'general'"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                  </svg>
                  <span>{{ $t('chat.general', 'General') }}</span>
                </button>

                <button
                  type="button"
                  class="chatgpt-tab-btn"
                  :class="{ 'is-active': activeSettingsTab === 'appearance' }"
                  @click="activeSettingsTab = 'appearance'"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                  </svg>
                  <span>{{ $t('chat.appearance', 'Appearance') }}</span>
                </button>

                <button
                  type="button"
                  class="chatgpt-tab-btn"
                  :class="{ 'is-active': activeSettingsTab === 'data' }"
                  @click="activeSettingsTab = 'data'"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                  </svg>
                  <span>{{ $t('chat.data_controls', 'Data controls') }}</span>
                </button>
              </nav>

              <!-- Right Content Area -->
              <div class="chatgpt-dialog-content">
                <!-- General Tab -->
                <div v-if="activeSettingsTab === 'general'" class="chatgpt-tab-panel">
                  <div class="chatgpt-setting-row">
                    <div class="chatgpt-setting-info">
                      <div class="chatgpt-setting-title">{{ $t('chat.reading_font', 'Reading font') }}</div>
                      <div class="chatgpt-setting-desc">{{ $t('chat.choose_font_desc', 'Choose your preferred typography for reading and chat.') }}</div>
                    </div>
                    <div class="chatgpt-segmented-control">
                      <button
                        type="button"
                        class="chatgpt-segmented-btn"
                        :class="{ 'is-active': readerFont === 'sans' }"
                        @click="setFont('sans')"
                      >
                        <span style="font-family: sans-serif;">{{ $t('chat.sans', 'Sans-serif') }}</span>
                      </button>
                      <button
                        type="button"
                        class="chatgpt-segmented-btn"
                        :class="{ 'is-active': readerFont === 'serif' }"
                        @click="setFont('serif')"
                      >
                        <span style="font-family: serif;">{{ $t('chat.serif', 'Serif') }}</span>
                      </button>
                    </div>
                  </div>

                  <div class="chatgpt-panel-divider"></div>

                  <div class="chatgpt-setting-row">
                    <div class="chatgpt-setting-info">
                      <div class="chatgpt-setting-title">{{ $t('chat.brand', 'Smart Adama') }}</div>
                      <div class="chatgpt-setting-desc">{{ $t('chat.book_title', 'SMART ADAMA BOOK') }} — Civic Innovation & Learning Platform</div>
                    </div>
                    <span class="chatgpt-system-pill">v2.4 Ready</span>
                  </div>
                </div>

                <!-- Appearance Tab -->
                <div v-if="activeSettingsTab === 'appearance'" class="chatgpt-tab-panel">
                  <div class="chatgpt-panel-intro">
                    <div class="chatgpt-setting-title">{{ $t('chat.theme', 'Theme') }}</div>
                    <div class="chatgpt-setting-desc">{{ $t('chat.choose_theme_desc', 'Customize the visual appearance of your study environment.') }}</div>
                  </div>

                  <div class="chatgpt-themes-gallery">
                    <button
                      v-for="(item, key) in THEMES"
                      :key="key"
                      type="button"
                      class="chatgpt-theme-card"
                      :class="{ 'is-selected': readerTheme === key }"
                      @click="setTheme(key)"
                    >
                      <div class="chatgpt-card-canvas" :style="{ background: item.vars['--rt-bg'] }">
                        <div class="chatgpt-card-inner" :style="{ background: item.vars['--rt-surface'], borderColor: item.vars['--rt-border'] }">
                          <div class="chatgpt-card-bar" :style="{ background: item.vars['--rt-accent'] }"></div>
                          <div class="chatgpt-card-line" :style="{ background: item.vars['--rt-text'] }"></div>
                          <div class="chatgpt-card-subline" :style="{ background: item.vars['--rt-muted'] }"></div>
                        </div>
                      </div>
                      <div class="chatgpt-card-meta">
                        <span class="chatgpt-card-name">{{ item.label }}</span>
                        <span v-if="readerTheme === key" class="chatgpt-card-checkmark">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <polyline points="20 6 9 17 4 12"></polyline>
                          </svg>
                        </span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Data Controls Tab -->
                <div v-if="activeSettingsTab === 'data'" class="chatgpt-tab-panel">
                  <div class="chatgpt-setting-row chatgpt-setting-row--stacked-sm">
                    <div class="chatgpt-setting-info">
                      <div class="chatgpt-setting-title">{{ $t('chat.clear_hist', 'Clear Chat History') }}</div>
                      <div class="chatgpt-setting-desc">{{ $t('chat.clear_all_chats_desc', 'Permanently delete all previous conversation history for this book.') }}</div>
                    </div>
                    <button
                      type="button"
                      class="chatgpt-modal-danger-btn"
                      @click="handleClearChatFromSettings"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                      <span>{{ $t('chat.delete', 'Delete') }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { useTour } from '@/composables/useTour'
import { studyTour } from '@/composables/tourRegistry'
import {
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  onErrorCaptured,
  ref,
  watch,
} from 'vue'
import {
  RouterLink,
  useRoute,
  useRouter,
} from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useChatStore } from '@/stores/chat'
import { useBooksStore } from '@/stores/books'
import { useProgressStore } from '@/stores/progress'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'
import apiClient, { serverRoot, warmUpBackend } from '@/api/client'
import IntroductionPreface from '@/components/IntroductionPreface.vue'
import { useI18n } from 'vue-i18n'
import { useConfirm } from '@/composables/useConfirm'
import { useTheme } from '@/composables/useTheme'

// Setup marked and syntax highlighting
const renderer = new marked.Renderer()
renderer.code = function(token) {
  const code = typeof token === 'string' ? token : (token as any).text
  const lang = (typeof token === 'string' ? '' : ((token as any).lang || '')).match(/\S*/)?.[0]

  const validLanguage = hljs.getLanguage(lang) ? lang : 'plaintext'
  const highlighted = hljs.highlight(code, { language: validLanguage }).value
  const encodedCode = encodeURIComponent(code)

  return `
    <div class="code-block-wrapper my-4 rounded-md overflow-hidden bg-[var(--rt-surface-2)] border border-[var(--rt-border)]">
      <div class="flex items-center justify-between px-3 py-1.5 bg-[var(--rt-surface)] text-xs text-[var(--rt-muted)] border-b border-[var(--rt-border)]">
        <span class="font-mono uppercase">${validLanguage}</span>
        <button type="button" class="copy-code-btn flex items-center gap-1.5 hover:text-[var(--rt-text)] transition-colors" data-code="${encodedCode}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
          <span class="copy-text">Copy code</span>
        </button>
      </div>
      <div class="overflow-x-auto p-4 bg-[var(--rt-surface-2)]">
        <pre class="!m-0 !p-0 !bg-transparent"><code class="hljs language-${validLanguage} text-sm">${highlighted}</code></pre>
      </div>
    </div>
  `
}
marked.setOptions({ renderer, breaks: true })

const { confirm } = useConfirm()
const chatStore = useChatStore()
const booksStore = useBooksStore()
const progressStore = useProgressStore()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

async function signOut() {
  await authStore.logout()
}

async function clearChatHistory() {
  const isConfirmed = await confirm({
    title: 'Delete All Chat History',
    message: 'Are you sure you want to delete all chat history? This cannot be undone.',
    confirmText: 'Delete All',
    confirmColor: 'red'
  })

  if (isConfirmed) {
    try {
      await chatStore.deleteAllSessions()
      startNewChat()
    } catch (e) {
      console.error('Failed to clear history:', e)
    }
  }
}

async function deleteChat(sessionId: string) {
  const isConfirmed = await confirm({
    title: 'Delete Chat',
    message: 'Are you sure you want to delete this chat?',
    confirmText: 'Delete',
    confirmColor: 'red'
  })

  if (isConfirmed) {
    try {
      await chatStore.deleteSession(sessionId)
      if (chatStore.currentSession?.id === sessionId) {
        startNewChat()
      }
    } catch (e) {
      console.error('Failed to delete chat:', e)
    }
  }
}

/* ============================================================
   ERROR BOUNDARY
============================================================ */

const fatalError = ref<string | null>(null)

const vFocus = {
  mounted: (el: HTMLElement) => el.focus()
}

onErrorCaptured((err: unknown) => {
  console.error('ChatView Error Boundary Caught:', err)
  fatalError.value = err instanceof Error ? err.message : String(err)
  return false // Prevent the error from propagating further and unmounting the tree
})

/* ============================================================
   RESPONSIVE LAYOUT STATE
============================================================ */

const isSidebarOpen = ref(false)
const isAiSidebarOpen = ref(false)
const storedReaderState = typeof sessionStorage !== 'undefined' ? sessionStorage.getItem('smart_adama_reader_open') : null
const isReaderOpen = ref(typeof window !== 'undefined' && window.innerWidth < 1024 ? true : (storedReaderState !== null ? storedReaderState === 'true' : true))

const saveReaderState = () => {
  if (typeof sessionStorage !== 'undefined') {
    sessionStorage.setItem('smart_adama_reader_open', isReaderOpen.value ? 'true' : 'false')
  }
}

const sidebarWidth = ref(300)
const activeSidebarTab = ref<'chats' | 'chapters'>('chats')
const isRecentChatsOpen = ref(true)
const activeSessionMenu = ref<string | null>(null)
const editingSessionId = ref<string | null>(null)
const editSessionTitle = ref<string>('')
const isDraggingLeft = ref(false)

const startRenaming = (session: any) => {
  editingSessionId.value = session.id
  editSessionTitle.value = session.title || 'New Conversation'
  activeSessionMenu.value = null
  // We need to focus the input on next tick, but we'll do it via auto-focus directive or ref
}

const submitRename = async () => {
  if (editingSessionId.value && editSessionTitle.value.trim()) {
    try {
      await chatStore.renameSession(editingSessionId.value, editSessionTitle.value.trim())
    } catch (e) {
      console.error('Failed to rename session:', e)
    }
  }
  editingSessionId.value = null
}

const copySessionTranscript = (session: any) => {
  activeSessionMenu.value = null
  if (!session.messages || session.messages.length === 0) {
    alert('This chat is empty.')
    return
  }
  const transcript = session.messages.map((m: any) => `${m.role === 'user' ? 'You' : 'Smart Adama'}:\n${m.content}`).join('\n\n')
  navigator.clipboard.writeText(transcript).then(() => {
    // Ideally a toast, but an alert is simple
    alert('Chat copied to clipboard!')
  }).catch(err => {
    console.error('Failed to copy text: ', err)
  })
}


const isDraggingRight = ref(false)

const windowWidth = ref(
  typeof window !== 'undefined' ? window.innerWidth : 1280,
)

const isMobile = computed(() => windowWidth.value < 1024)
let wasMobile = typeof window !== 'undefined' ? window.innerWidth < 1024 : false

const handleResize = () => {
  windowWidth.value = window.innerWidth

  const mobileNow = window.innerWidth < 1024
  if (mobileNow && !wasMobile) {
    isSidebarOpen.value = false
    isAiSidebarOpen.value = false
  }
  wasMobile = mobileNow

  if (!mobileNow) {
    sidebarWidth.value = clamp(sidebarWidth.value, 240, Math.min(480, Math.floor(window.innerWidth * 0.34)))
  }
}

const closeMobilePanels = () => {
  if (!isMobile.value) return
  isSidebarOpen.value = false
  isAiSidebarOpen.value = false
  isReaderOpen.value = true
}

const openSidebar = () => {
  isSidebarOpen.value = true
  if (isMobile.value) {
    isAiSidebarOpen.value = false
  }
}

const openAiSidebar = () => {
  isAiSidebarOpen.value = true
  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

const toggleAiSidebar = (open?: boolean) => {
  isAiSidebarOpen.value = typeof open === 'boolean' ? open : !isAiSidebarOpen.value
  if (isMobile.value && isAiSidebarOpen.value) {
    isSidebarOpen.value = false
  }
}

/* ============================================================
   MOBILE EDGE SWIPE GESTURES
============================================================ */
let touchStartX = 0
let touchStartY = 0
let eligibleGesture: 'open-left' | 'open-right' | 'close-left' | 'close-right' | null = null

const handleTouchStart = (e: TouchEvent) => {
  if (!isMobile.value || e.touches.length !== 1) {
    eligibleGesture = null
    return
  }

  const target = e.target as HTMLElement | null
  // Do not initiate gestures on interactive elements
  if (target?.closest('input, textarea, button, select, a, [contenteditable="true"], .session-dropdown-menu, .theme-switcher, .session-options-btn, .ai-composer')) {
    eligibleGesture = null
    return
  }

  const touch = e.touches[0]
  touchStartX = touch.clientX
  touchStartY = touch.clientY

  const EDGE_ZONE = 44 // px — wider edge zone for easier triggering
  const screenWidth = window.innerWidth

  if (isSidebarOpen.value) {
    // Left sidebar open: swipe left anywhere to close it
    eligibleGesture = 'close-left'
  } else if (isAiSidebarOpen.value) {
    // AI sidebar open: swipe right anywhere to close it
    // (but not from inside message thread — let that scroll normally)
    if (!target?.closest('.ai-messages')) {
      eligibleGesture = 'close-right'
    } else {
      eligibleGesture = null
    }
  } else {
    // Both sidebars closed:
    // — Swipe right from left edge (or broadly left side) → open left sidebar
    // — Swipe left from right edge (or broadly right side) → open AI sidebar
    if (touchStartX <= EDGE_ZONE) {
      eligibleGesture = 'open-left'
    } else if (touchStartX >= screenWidth - EDGE_ZONE) {
      eligibleGesture = 'open-right'
    } else if (touchStartX <= screenWidth * 0.35) {
      // Allow swipe right from the left 35% of screen to open left sidebar
      eligibleGesture = 'open-left'
    } else if (touchStartX >= screenWidth * 0.65) {
      // Allow swipe left from the right 35% of screen to open AI sidebar
      eligibleGesture = 'open-right'
    } else {
      eligibleGesture = null
    }
  }
}

const handleTouchMove = (e: TouchEvent) => {
  if (!eligibleGesture || e.touches.length !== 1) return

  const touch = e.touches[0]
  const deltaX = touch.clientX - touchStartX
  const deltaY = touch.clientY - touchStartY

  // If user is scrolling vertically, cancel swipe gesture so we don't interfere with normal scrolling
  if (Math.abs(deltaY) > 15 && Math.abs(deltaY) > Math.abs(deltaX)) {
    eligibleGesture = null
  }
}

const handleTouchEnd = (e: TouchEvent) => {
  if (!eligibleGesture || e.changedTouches.length === 0) {
    eligibleGesture = null
    return
  }

  const touch = e.changedTouches[0]
  const deltaX = touch.clientX - touchStartX
  const deltaY = touch.clientY - touchStartY
  const absX = Math.abs(deltaX)
  const absY = Math.abs(deltaY)

  const SWIPE_THRESHOLD = 50

  // Must be predominantly horizontal and exceed threshold distance
  if (absX >= SWIPE_THRESHOLD && absX > absY * 1.5) {
    if (eligibleGesture === 'open-left' && deltaX > 0) {
      openSidebar()
    } else if (eligibleGesture === 'open-right' && deltaX < 0) {
      openAiSidebar()
    } else if (eligibleGesture === 'close-left' && deltaX < 0) {
      isSidebarOpen.value = false
    } else if (eligibleGesture === 'close-right' && deltaX > 0) {
      isAiSidebarOpen.value = false
    }
  }

  eligibleGesture = null
}

const handleTouchCancel = () => {
  eligibleGesture = null
}

/* ============================================================
   READER STATE
============================================================ */

const isFullscreen = ref(false)
const readerScale = ref(125)
const currentPage = ref(1)
const jumpPageInput = ref('1')
const viewMode = ref<'reading' | 'pdf'>('reading')

const readerWidth = ref(1000)
const isDraggingReader = ref(false)
const readerContainerRef = ref<HTMLElement | null>(null)
const scrollAreaRef = ref<HTMLElement | null>(null)
let readerDragCenter = 0

const LOCAL_BOOK_FALLBACK_URL = 'https://giknfkibnyzrlegxraio.supabase.co/storage/v1/object/public/books/SA-Book.pdf'

const ZOOM_STEPS = [50, 60, 75, 80, 90, 100, 110, 125, 150, 175, 200, 234, 250, 300, 400]

const zoomInReader = () => {
  const current = readerScale.value
  const next = ZOOM_STEPS.find(s => s > current) || 400
  readerScale.value = next
}

const zoomOutReader = () => {
  const current = readerScale.value
  const prev = [...ZOOM_STEPS].reverse().find(s => s < current) || 50
  readerScale.value = prev
}

const pdfUrl = computed(() => {
  const chapter = booksStore.currentChapter as any
  const book = (booksStore as any).currentBook
  return (
    chapter?.pdf_url ||
    chapter?.file_url ||
    chapter?.source_pdf ||
    book?.pdf_url ||
    book?.file_url ||
    LOCAL_BOOK_FALLBACK_URL
  )
})

const preFullscreenState = ref({
  sidebar: true,
  aiSidebar: true,
})

/* ============================================================
   CHAPTER NAVIGATION
============================================================ */

const expandedChapters = ref<Record<string, boolean>>({})
const isShowingAll = ref(false)
const showAllChats = ref(false)

const toggleChapterCollapse = (chapterId: string) => {
  expandedChapters.value[chapterId] = !expandedChapters.value[chapterId]
}

const toggleShowAll = () => {
  isShowingAll.value = !isShowingAll.value
}

const getSortedChapters = (book: any) => {
  if (!book) return []

  const chapters = [
    ...(book.chapters?.data || book.chapters || []),
  ]

  return chapters.sort((a, b) => {
    const aIntro = a.title === 'Introduction & Preface'
    const bIntro = b.title === 'Introduction & Preface'
    const aSys = String(a.title || '').includes('System Context')
    const bSys = String(b.title || '').includes('System Context')

    if (aIntro) return -1
    if (bIntro) return 1
    if (aSys) return 1
    if (bSys) return -1

    return (a.order || 0) - (b.order || 0)
  })
}

const allSortedChapters = computed(() => {
  if (!booksStore.books?.length) return []

  const contentSize = (book: any) => {
    const chapters = book.chapters?.data || book.chapters || []
    let totalLength = 0
    let totalSections = 0

    chapters.forEach((chapter: any) => {
      const sections = chapter.sections?.data || chapter.sections || []
      totalSections += sections.length
      totalLength += sections.reduce(
        (sum: number, section: any) => sum + (section.raw_text?.length || 0),
        0,
      )
    })

    // Fallback: If text length is 0 (sections not eager loaded), use chapter count
    return totalLength > 0 ? totalLength : (totalSections > 0 ? totalSections : chapters.length)
  }

  const book = booksStore.books.reduce(
    (largest: any, candidate: any) =>
      contentSize(candidate) > contentSize(largest) ? candidate : largest,
    booksStore.books[0],
  )

  return getSortedChapters(book)
})

const isSidebarSearchActive = ref(false)
const sidebarSearchQuery = ref('')
const sidebarSearchInputRef = ref<HTMLInputElement | null>(null)

const toggleSidebarSearch = () => {
  isSidebarSearchActive.value = !isSidebarSearchActive.value
  if (isSidebarSearchActive.value) {
    nextTick(() => {
      sidebarSearchInputRef.value?.focus()
    })
  } else {
    sidebarSearchQuery.value = ''
  }
}

const allVisibleChapters = computed(() => {
  const chapters = allSortedChapters.value
  const query = sidebarSearchQuery.value.trim().toLowerCase()
  const filtered = query
    ? chapters.filter(c => c.title?.toLowerCase().includes(query) || c.chapter_number?.toString().includes(query))
    : chapters
  return isShowingAll.value || query ? filtered : filtered.slice(0, 5)
})

const hasHiddenChapters = computed(() => allSortedChapters.value.length > 5)

const visiblePinnedSessions = computed(() => {
  const sessions = chatStore.pinnedSessions || []
  const query = sidebarSearchQuery.value.trim().toLowerCase()
  return query ? sessions.filter(s => (s.title || '').toLowerCase().includes(query)) : sessions
})

const visibleActiveSessions = computed(() => {
  const sessions = chatStore.activeSessions || []
  const query = sidebarSearchQuery.value.trim().toLowerCase()
  const filtered = query ? sessions.filter(s => (s.title || '').toLowerCase().includes(query)) : sessions
  return showAllChats.value || query ? filtered : filtered.slice(0, 6)
})

const visibleArchivedSessions = computed(() => {
  const sessions = chatStore.archivedSessions || []
  const query = sidebarSearchQuery.value.trim().toLowerCase()
  return query ? sessions.filter(s => (s.title || '').toLowerCase().includes(query)) : sessions
})

const hiddenChatsCount = computed(() =>
  Math.max(0, (chatStore.activeSessions || []).length - 6),
)

const sessionGroups = computed(() => [
  { id: 'pinned', title: 'Pinned', sessions: visiblePinnedSessions.value },
  { id: 'active', title: 'Recent', sessions: visibleActiveSessions.value },
  { id: 'archived', title: 'Archived', sessions: visibleArchivedSessions.value }
].filter(g => g.sessions.length > 0))

/* ============================================================
   PAGINATION / CONTENT
============================================================ */

const currentChapterPages = computed(() =>
  getStructuredSections(
    booksStore.currentChapter?.sections?.data ||
    booksStore.currentChapter?.sections ||
    []
  )
)

const READING_PAGE_MIN_CHARS = 900

const mergedPages = computed(() => {
  const sections = currentChapterPages.value
  const pages: Array<{ sections: typeof sections }> = []
  let bucket: typeof sections = []
  let bucketLength = 0

  for (const section of sections) {
    bucket.push(section)
    bucketLength += (section.raw_text || '').length

    if (bucketLength >= READING_PAGE_MIN_CHARS) {
      pages.push({ sections: bucket })
      bucket = []
      bucketLength = 0
    }
  }

  if (bucket.length) {
    pages.push({ sections: bucket })
  }

  return pages
})

const totalPages = computed(() => {
  if (booksStore.currentChapter?.title === 'Introduction & Preface') {
    return 3
  }
  return Math.max(1, mergedPages.value.length)
})

const currentPageData = computed(() => {
  const pages = mergedPages.value
  if (!pages.length) return null

  const index = Math.min(
    Math.max(currentPage.value - 1, 0),
    pages.length - 1,
  )

  return pages[index]
})

const sectionToPageMap = computed(() => {
  const map = new Map<string, number>()
  mergedPages.value.forEach((page, pageIndex) => {
    page.sections.forEach((section: any) => map.set(section.id, pageIndex))
  })
  return map
})

const readingProgress = computed(() => {
  if (totalPages.value <= 1) return 100
  return Math.round((currentPage.value / totalPages.value) * 100)
})

type ContentBlock = {
  type: 'p' | 'bullet'
  text: string
  label?: string | null
}

function splitSentences(text: string): string[] {
  const matches = text.match(/[^.!?]+[.!?]+(\s+|$)/g)
  if (matches) return matches.map((value) => value.trim()).filter(Boolean)
  return text.trim() ? [text.trim()] : []
}

function paragraphize(text: string, perParagraph = 3) {
  const sentences = splitSentences(text)
  const paragraphs: string[] = []
  for (let i = 0; i < sentences.length; i += perParagraph) {
    paragraphs.push(sentences.slice(i, i + perParagraph).join(' ').trim())
  }
  return paragraphs.filter(Boolean)
}
function getStructuredSections(sections: any[]) {
  if (!sections || !Array.isArray(sections)) return []

  const childrenMap = new Map<string, any[]>()
  const rootSections: any[] = []

  sections.forEach(sec => {
    if (sec.parent_id) {
      if (!childrenMap.has(sec.parent_id)) childrenMap.set(sec.parent_id, [])
      childrenMap.get(sec.parent_id)!.push(sec)
    } else {
      rootSections.push(sec)
    }
  })

  const sortByOrder = (a: any, b: any) => (a.order || 0) - (b.order || 0)
  rootSections.sort(sortByOrder)
  childrenMap.forEach(arr => arr.sort(sortByOrder))

  const result: any[] = []
  function traverse(sec: any, depth: number) {
    result.push({ ...sec, depth })
    const children = childrenMap.get(sec.id)
    if (children) {
      children.forEach(child => traverse(child, depth + 1))
    }
  }

  rootSections.forEach(root => traverse(root, 0))
  return result
}


function formatContent(raw: string | undefined | null): ContentBlock[] {
  if (!raw) return []

  const cleaned = raw
    .replace(/\r\n/g, '\n')
    .replace(/\n+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()

  const blocks: ContentBlock[] = []

  if (cleaned.includes('•')) {
    const parts = cleaned
      .split('•')
      .map((part) => part.trim())
      .filter(Boolean)

    const intro = parts.shift()
    if (intro) {
      paragraphize(intro).forEach((paragraph) => {
        blocks.push({ type: 'p', text: paragraph })
      })
    }

    parts.forEach((item) => {
      const colonIndex = item.indexOf(':')
      if (colonIndex > 0 && colonIndex < 60) {
        blocks.push({
          type: 'bullet',
          label: item.slice(0, colonIndex).trim(),
          text: item.slice(colonIndex + 1).trim(),
        })
      } else {
        blocks.push({ type: 'bullet', label: null, text: item })
      }
    })
  } else {
    paragraphize(cleaned).forEach((paragraph) => {
      blocks.push({ type: 'p', text: paragraph })
    })
  }

  return blocks
}

const scrollToSection = (sectionId: string) => {
  nextTick(() => {
    document
      .getElementById(`sec-${sectionId}`)
      ?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

/* ============================================================
   READER POSITION
============================================================ */

const READ_POSITION_KEY = 'smart-adama-read-position'
const LAST_CHAPTER_KEY = 'smart-adama-last-chapter'

const isChapterLoading = ref(false)
const chapterLoadError = ref<string | null>(null)

const retryLoadChapter = async () => {
  chapterLoadError.value = null
  isChapterLoading.value = true
  try {
    if (!booksStore.books?.length) {
      await booksStore.loadBooks(true)
    }
    const chapters = allSortedChapters.value
    if (chapters.length) {
      const savedChapterId = localStorage.getItem(LAST_CHAPTER_KEY)
      const targetChapter =
        (savedChapterId && chapters.find((c: any) => c.id === savedChapterId)) ||
        chapters.find((c: any) => c.title?.includes('Ch-1') || c.title?.includes('Chapter 1')) ||
        chapters[1] ||
        chapters[0]
      if (targetChapter) {
        await loadBookChapter(targetChapter.id)
      }
    } else {
      chapterLoadError.value = 'No chapters found. Please check your connection and try again.'
    }
  } catch (err: any) {
    chapterLoadError.value = err?.message || 'Failed to reload chapter. Please check your network connection.'
  } finally {
    isChapterLoading.value = false
  }
}

const saveReadPosition = (chapterId: string, page: number) => {
  try {
    const raw = localStorage.getItem(READ_POSITION_KEY)
    const store = raw ? JSON.parse(raw) : {}
    store[chapterId] = page
    localStorage.setItem(READ_POSITION_KEY, JSON.stringify(store))
  } catch {
    // Local persistence is best-effort.
  }
}

const getReadPosition = (chapterId: string): number | null => {
  try {
    const raw = localStorage.getItem(READ_POSITION_KEY)
    if (!raw) return null
    const store = JSON.parse(raw)
    return typeof store[chapterId] === 'number' ? store[chapterId] : null
  } catch {
    return null
  }
}

const checkChapterCompletion = async (
  chapterId: string,
  page: number,
) => {
  // Do nothing. Chapters should only be completed when the user explicitly clicks "Finish Chapter"
  // as per the requirement: "Do not mark a chapter completed merely because ... the user jumped directly to the final page".
}
const renderMarkdown = (text: string | undefined): string => {
  if (!text) return ''

  let cleanText = text

  // Clean up PDF hard-wrapping artifacts and backend HTML formatting:
  // Convert HTML paragraph tags to Markdown newlines
  cleanText = cleanText.replace(/<\/p>\s*<p>/gi, '\n\n')
  cleanText = cleanText.replace(/<\/?p>/gi, '')

  // Ensure relative backend storage image URLs point to the backend server
  cleanText = cleanText.replace(/src="\/storage\//g, `src="${serverRoot}/storage/`)
  cleanText = cleanText.replace(/src='\/storage\//g, `src='${serverRoot}/storage/`)
  cleanText = cleanText.replace(/\(\/storage\//g, `(${serverRoot}/storage/`)

  const parsed = marked.parse(cleanText)
  return DOMPurify.sanitize(parsed as string)
}

const showScrollButton = ref(false)
const isNearBottom = ref(true)
const SCROLL_THRESHOLD = 120 // px

const handleChatScroll = (e: Event) => {
  const target = e.target as HTMLElement
  const distanceFromBottom = target.scrollHeight - target.scrollTop - target.clientHeight
  isNearBottom.value = distanceFromBottom <= SCROLL_THRESHOLD
  showScrollButton.value = distanceFromBottom > 150
}

const scrollToBottom = (force = true) => {
  if (!force && !isNearBottom.value) return
  nextTick(() => {
    if (messagesContainerRef.value) {
      messagesContainerRef.value.scrollTo({
        top: messagesContainerRef.value.scrollHeight,
        behavior: 'smooth'
      })
    }
  })
}

const handleMessagesClick = async (e: MouseEvent) => {
  const target = e.target as HTMLElement
  const copyBtn = target.closest('.copy-code-btn')
  if (copyBtn) {
    const code = decodeURIComponent(copyBtn.getAttribute('data-code') || '')
    if (code) {
      await navigator.clipboard.writeText(code)
      const textSpan = copyBtn.querySelector('.copy-text')
      if (textSpan) {
        const orig = textSpan.textContent
        textSpan.textContent = 'Copied!'
        setTimeout(() => { textSpan.textContent = orig }, 2000)
      }
    }
  }
}

const copiedMessageId = ref<string | null>(null)
const feedbackToastMessage = ref('')

const showFeedbackToast = (msg: string) => {
  feedbackToastMessage.value = msg
  setTimeout(() => feedbackToastMessage.value = '', 3000)
}

const copyText = async (text: string, msgId: string) => {
  try {
    await navigator.clipboard.writeText(text)
    copiedMessageId.value = msgId
    setTimeout(() => {
      copiedMessageId.value = null
    }, 2000)
  } catch (err) {
    console.error('Failed to copy text: ', err)
  }
}

const editMessage = (msg: any) => {
  chatInput.value = msg.content
  nextTick(() => {
    if (chatInputRef.value) {
      chatInputRef.value.focus()
      autoResizeInput()
    }
  })
}

const handleEnter = (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    void sendMessage()
  }
}

const autoResizeInput = () => {
  if (chatInputRef.value) {
    chatInputRef.value.style.height = 'auto';
    chatInputRef.value.style.height = Math.min(chatInputRef.value.scrollHeight, 120) + 'px';
  }
}

const autoResizeSearchInput = () => {
  if (sidebarSearchInputRef.value) {
    sidebarSearchInputRef.value.style.height = 'auto';
    sidebarSearchInputRef.value.style.height = Math.min(sidebarSearchInputRef.value.scrollHeight, 120) + 'px';
  }
}

// Global Hybrid Search Logic
const isSearchingGlobal = ref(false)
const searchResults = ref<any>(null)
const searchError = ref<string | null>(null)
const globalSearchHistory = ref<string[]>([])
let searchTimeout: ReturnType<typeof setTimeout> | null = null

const localSearchResults = ref<{ messages: any[], sessions: any[] }>({ messages: [], sessions: [] })

const filteredChunks = computed(() => {
  if (!searchResults.value?.chunks) return []
  // Filter out noise: require at least a decent semantic match or a direct keyword match
  return searchResults.value.chunks.filter((c: any) => c.vector_score > 0.25 || c.keyword_score > 0)
})

onMounted(() => {
  const history = localStorage.getItem('globalSearchHistory')
  if (history) {
    globalSearchHistory.value = JSON.parse(history)
  }
})

const performLocalSearch = () => {
  const q = sidebarSearchQuery.value.trim().toLowerCase()
  if (!q) {
    localSearchResults.value = { messages: [], sessions: [] }
    return
  }

  const matchedMessages = currentMessages.value.filter((m: any) => m.content && m.content.toLowerCase().includes(q))
  const matchedSessions = chatStore.sessions.filter((s: any) => s.title && s.title.toLowerCase().includes(q))

  localSearchResults.value = {
    messages: matchedMessages.slice(-5).reverse(), // Last 5 matches, newest first
    sessions: matchedSessions.slice(0, 5)
  }
}

const performHybridSearch = async () => {
  if (!sidebarSearchQuery.value.trim()) {
    searchResults.value = null
    isSearchingGlobal.value = false
    return
  }

  isSearchingGlobal.value = true
  searchError.value = null

  try {
    const { data } = await apiClient.get('/admin/rag/debug-search', {
      params: { query: sidebarSearchQuery.value, limit: 10 }
    })
    searchResults.value = data
  } catch (err: any) {
    searchError.value = err.response?.data?.message || 'Failed to execute search.'
  } finally {
    isSearchingGlobal.value = false
  }
}

const handleSearchInput = () => {
  autoResizeSearchInput()
  performLocalSearch() // Instant local search

  if (searchTimeout) clearTimeout(searchTimeout)
  if (!sidebarSearchQuery.value.trim()) {
    searchResults.value = null
    return
  }

  searchTimeout = setTimeout(() => {
    performHybridSearch()
  }, 400)
}

const highlightText = (text: string, searchTerm: string) => {
  if (!text || !searchTerm) return text
  const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const regex = new RegExp(`(${escapedTerm})`, 'gi')
  return text.replace(regex, '<mark class="bg-amber-200 text-amber-900 rounded-sm px-1 py-0.5 font-medium">$1</mark>')
}

const getScoreColor = (score: number, isKeyword = false) => {
  if (isKeyword && score === 0) return 'bg-slate-100 text-slate-400 border-slate-200'
  if (score > 0.8) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
  if (score > 0.5) return 'bg-blue-50 text-blue-700 border-blue-200'
  if (score > 0.0) return 'bg-amber-50 text-amber-700 border-amber-200'
  return 'bg-slate-50 text-slate-500 border-slate-200'
}

const scrollToMessage = (msgId: string) => {
  toggleSidebarSearch()
  if (!isAiSidebarOpen.value) {
    isAiSidebarOpen.value = true
  }
  setTimeout(() => {
    const el = document.getElementById(`msg-${msgId}`)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
      el.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2', 'transition-all', 'duration-1000')
      setTimeout(() => el.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2'), 2000)
    }
  }, 300)
}

const goToSession = (sessionId: string) => {
  toggleSidebarSearch()
  if (!isAiSidebarOpen.value) {
    isAiSidebarOpen.value = true
  }
  selectSession(sessionId)
}

const submitGlobalSearch = async (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    const queryToSubmit = sidebarSearchQuery.value.trim();
    if (!queryToSubmit) return;

    // Save to history
    if (!globalSearchHistory.value.includes(queryToSubmit)) {
      globalSearchHistory.value.unshift(queryToSubmit);
      if (globalSearchHistory.value.length > 5) globalSearchHistory.value.pop();
      localStorage.setItem('globalSearchHistory', JSON.stringify(globalSearchHistory.value));
    }

    chatInput.value = queryToSubmit;
    sidebarSearchQuery.value = '';
    searchResults.value = null;
    toggleSidebarSearch();

    if (!isAiSidebarOpen.value) {
      isAiSidebarOpen.value = true;
    }

    await sendMessage();
  }
}

watch(
  () => booksStore.currentChapter?.id,
  async (newId) => {
    if (!newId) return

    localStorage.setItem(LAST_CHAPTER_KEY, newId)
    expandedChapters.value[newId] = true

    const savedPage = getReadPosition(newId)
    currentPage.value = savedPage ?? 1
    jumpPageInput.value = String(currentPage.value)

    await nextTick()
    await checkChapterCompletion(newId, currentPage.value)

    const savedSessionId = localStorage.getItem(`smart_adama_chat_session_${newId}`)
    if (savedSessionId && chatStore.sessions.some((s: any) => s.id === savedSessionId)) {
      try {
        await chatStore.loadSession(savedSessionId)
        router.replace({ name: 'study', params: { sessionId: savedSessionId } })
      } catch (e) {
        localStorage.removeItem(`smart_adama_chat_session_${newId}`)
      }
    }
  },
)

let landAtBottomNext = false

watch(currentPage, (page) => {
  const chapterId = booksStore.currentChapter?.id
  if (chapterId) {
    saveReadPosition(chapterId, page)
    void checkChapterCompletion(chapterId, page)
  }

  nextTick(() => {
    if (!scrollAreaRef.value) return

    if (landAtBottomNext) {
      scrollAreaRef.value.scrollTop = scrollAreaRef.value.scrollHeight
      landAtBottomNext = false
    } else {
      scrollAreaRef.value.scrollTop = 0
    }
  })
})

const nextPage = () => {
  if (currentPage.value >= totalPages.value) return
  currentPage.value += 1
  jumpPageInput.value = String(currentPage.value)
}

const prevPage = () => {
  if (currentPage.value <= 1) return
  currentPage.value -= 1
  jumpPageInput.value = String(currentPage.value)
}

const jumpToPage = () => {
  const page = Number.parseInt(jumpPageInput.value, 10)
  if (Number.isFinite(page) && page >= 1 && page <= totalPages.value) {
    currentPage.value = page
  } else {
    jumpPageInput.value = String(currentPage.value)
  }
}

const markCompleteAndNextChapter = async () => {
  const currentId = booksStore.currentChapter?.id

  try {
    if (currentId && typeof booksStore.markChapterRead === 'function') {
      await booksStore.markChapterRead(currentId)
      await progressStore.loadAll()
    }
  } catch (error) {
    console.warn('Unable to mark chapter complete:', error)
  }

  const chapters = allSortedChapters.value
  if (!chapters.length) {
    router.push('/dashboard')
    return
  }

  const currentIndex = chapters.findIndex((chapter: any) => chapter.id === currentId)
  if (currentIndex !== -1 && currentIndex + 1 < chapters.length) {
    await loadBookChapter(chapters[currentIndex + 1].id)
    currentPage.value = 1
    jumpPageInput.value = '1'
    if (scrollAreaRef.value) scrollAreaRef.value.scrollTop = 0
  } else {
    router.push('/dashboard')
  }
}

const isNavigatingToQuiz = ref(false)

const takeChapterQuiz = async () => {
  const currentId = booksStore.currentChapter?.id
  if (!currentId || isNavigatingToQuiz.value) return

  isNavigatingToQuiz.value = true
  try {
    if (typeof booksStore.markChapterRead === 'function') {
      await booksStore.markChapterRead(currentId)
      await progressStore.loadAll()
    }
  } catch (error) {
    console.warn('Unable to mark chapter complete before quiz:', error)
  } finally {
    isNavigatingToQuiz.value = false
  }

  router.push(`/chapters/${currentId}/quiz`)
}

const jumpToSection = async (
  chapterId: string,
  sectionId: string,
) => {
  if (booksStore.currentChapter?.id !== chapterId) {
    await loadBookChapter(chapterId)
    await nextTick()
  }

  const pageIndex = sectionToPageMap.value.get(sectionId)
  if (pageIndex !== undefined) {
    currentPage.value = pageIndex + 1
    jumpPageInput.value = String(currentPage.value)
  }

  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

const handleTocClick = async (chapterNumber: string) => {
  const target = allSortedChapters.value.find((chapter: any) => {
    const regex = new RegExp(`^(chapter|ch)[\\s\\-]+${chapterNumber}\\b`, 'i')
    return regex.test(chapter.title)
  })

  if (!target) return

  await loadBookChapter(target.id)
  currentPage.value = 1
  jumpPageInput.value = '1'
  if (isMobile.value) isSidebarOpen.value = false
}

/* ============================================================
   RESIZABLE PANELS
============================================================ */

function clamp(value: number, min: number, max: number) {
  return Math.min(max, Math.max(min, value))
}

const setSidebarWidth = (value: number) => {
  const max = Math.min(480, Math.max(240, Math.floor(window.innerWidth * 0.34)))
  sidebarWidth.value = clamp(value, 240, max)
}

const nudgeSidebarWidth = (delta: number) => {
  setSidebarWidth(sidebarWidth.value + delta)
}

const startDragLeft = () => {
  if (isMobile.value) return
  isDraggingLeft.value = true
  document.body.style.cursor = 'col-resize'
  document.addEventListener('mousemove', onDragLeft)
  document.addEventListener('mouseup', stopDragLeft, { once: true })
}

const onDragLeft = (event: MouseEvent) => {
  if (!isDraggingLeft.value) return
  setSidebarWidth(event.clientX)
}

const stopDragLeft = () => {
  isDraggingLeft.value = false
  document.body.style.cursor = ''
  document.removeEventListener('mousemove', onDragLeft)
}




/* ============================================================
   OVERSCROLL PAGE TURNING
============================================================ */

const OVERSCROLL_THRESHOLD = 260
const OVERSCROLL_RESET_MS = 350
const PAGE_TURN_COOLDOWN_MS = 500

const overscrollAccum = ref(0)
const overscrollDir = ref<'up' | 'down' | null>(null)
let overscrollResetTimer: number | null = null
let isTurningPage = false

const clearOverscroll = () => {
  overscrollAccum.value = 0
  overscrollDir.value = null

  if (overscrollResetTimer) {
    clearTimeout(overscrollResetTimer)
    overscrollResetTimer = null
  }
}

const armOverscrollResetTimer = () => {
  if (overscrollResetTimer) clearTimeout(overscrollResetTimer)
  overscrollResetTimer = window.setTimeout(clearOverscroll, OVERSCROLL_RESET_MS)
}

const handleReaderWheel = (event: WheelEvent) => {
  if (isTurningPage || viewMode.value !== 'reading') return
  const element = scrollAreaRef.value
  if (!element) return

  const atTop = element.scrollTop <= 2
  const atBottom = element.scrollTop + element.clientHeight >= element.scrollHeight - 2
  const wantsUp = event.deltaY < 0 && atTop
  const wantsDown = event.deltaY > 0 && atBottom

  if (!wantsUp && !wantsDown) {
    clearOverscroll()
    return
  }

  const direction = wantsUp ? 'up' : 'down'
  if (overscrollDir.value !== direction) {
    overscrollAccum.value = 0
    overscrollDir.value = direction
  }

  overscrollAccum.value += Math.abs(event.deltaY)
  armOverscrollResetTimer()

  if (overscrollAccum.value >= OVERSCROLL_THRESHOLD) {
    isTurningPage = true

    if (direction === 'up' && currentPage.value > 1) {
      landAtBottomNext = true
      prevPage()
    } else if (direction === 'down' && currentPage.value < totalPages.value) {
      nextPage()
    }

    clearOverscroll()

    window.setTimeout(() => {
      isTurningPage = false
    }, PAGE_TURN_COOLDOWN_MS)
  }
}

/* ============================================================
   FULLSCREEN
============================================================ */

const toggleFullscreen = () => {
  if (!isFullscreen.value) {
    preFullscreenState.value = {
      sidebar: isSidebarOpen.value,
      aiSidebar: isAiSidebarOpen.value,
    }
    isSidebarOpen.value = false
    isAiSidebarOpen.value = false
    isFullscreen.value = true
  } else {
    isSidebarOpen.value = preFullscreenState.value.sidebar
    isAiSidebarOpen.value = preFullscreenState.value.aiSidebar
    isFullscreen.value = false
  }
}

const switchToPdf = () => {
  if (pdfUrl.value) {
    window.open(pdfUrl.value, '_blank')
  } else {
    alert('PDF not available for this chapter.')
  }
}

/* ============================================================
   THEME / FONT
============================================================ */

type ThemeKey = 'light' | 'sepia' | 'dark' | 'green' | 'brown'

const THEMES: Record<ThemeKey, { label: string; vars: Record<string, string> }> = {
  light: {
    label: 'Light',
    vars: {
      '--rt-bg': '#F0F3FA',
      '--rt-surface': '#FFFFFF',
      '--rt-surface-2': '#D5DEEF',
      '--rt-border': '#B1C9EF',
      '--rt-text': '#395886',
      '--rt-text-body': '#1E293B',
      '--rt-muted': '#638ECB',
      '--rt-accent': '#395886',
      '--rt-accent-hover': '#304B73',
      '--rt-accent-text': '#FFFFFF',
    },
  },
  sepia: {
    label: 'Sepia',
    vars: {
      '--rt-bg': '#EAE0C8',
      '--rt-surface': '#F4ECD8',
      '--rt-surface-2': '#EADBB8',
      '--rt-border': '#DCCBA8',
      '--rt-text': '#4A3728',
      '--rt-text-body': '#5B4636',
      '--rt-muted': '#9C876C',
      '--rt-accent': '#8A5A32',
      '--rt-accent-hover': '#734723',
      '--rt-accent-text': '#FFFFFF',
    },
  },
  dark: {
    label: 'Dark',
    vars: {
      '--rt-bg': '#212121',
      '--rt-surface': '#171717',
      '--rt-surface-2': '#0D0D0D',
      '--rt-border': '#3A3A3A',
      '--rt-text': '#ECECEC',
      '--rt-text-body': '#D1D5DB',
      '--rt-muted': '#9B9B9B',
      '--rt-accent': '#5B7FDB',
      '--rt-accent-hover': '#7093EE',
      '--rt-accent-text': '#FFFFFF',
    },
  },
  green: {
    label: 'Forest',
    vars: {
      '--rt-bg': '#E4EEE0',
      '--rt-surface': '#F2F8EF',
      '--rt-surface-2': '#DCE9D6',
      '--rt-border': '#C6DABF',
      '--rt-text': '#22381F',
      '--rt-text-body': '#33492F',
      '--rt-muted': '#748C6C',
      '--rt-accent': '#3F7D46',
      '--rt-accent-hover': '#356B3B',
      '--rt-accent-text': '#FFFFFF',
    },
  },
  brown: {
    label: 'Walnut',
    vars: {
      '--rt-bg': '#DFCBB2',
      '--rt-surface': '#EEDFC9',
      '--rt-surface-2': '#D6C2A6',
      '--rt-border': '#CDB48C',
      '--rt-text': '#3B2A1A',
      '--rt-text-body': '#4E3823',
      '--rt-muted': '#8A7256',
      '--rt-accent': '#6B4423',
      '--rt-accent-hover': '#59371B',
      '--rt-accent-text': '#FFFFFF',
    },
  },
}

const READER_THEME_KEY = 'smart-adama-reader-theme'
const READER_FONT_KEY = 'smart-adama-reader-font'

const storedTheme = typeof localStorage !== 'undefined'
  ? localStorage.getItem(READER_THEME_KEY) as ThemeKey | null
  : null

const storedFont = typeof localStorage !== 'undefined'
  ? localStorage.getItem(READER_FONT_KEY) as 'serif' | 'sans' | null
  : null

const readerTheme = ref<ThemeKey>(
  storedTheme && storedTheme in THEMES ? storedTheme : 'light',
)

const readerFont = ref<'serif' | 'sans'>(
  storedFont === 'sans' ? 'sans' : 'serif',
)

const themeVars = computed(() => THEMES[readerTheme.value].vars)

const { initializeTheme: restoreGlobalWebsiteTheme } = useTheme()

function applyStudyTheme(theme: ThemeKey) {
  if (typeof document === 'undefined') return
  const isDark = theme === 'dark'
  const root = document.documentElement
  const body = document.body
  const app = document.getElementById('app')

  if (isDark) {
    root.classList.add('dark')
    root.dataset.theme = 'dark'
    body.classList.add('dark-theme')
    body.dataset.theme = 'dark'
    if (app) {
      app.classList.add('dark')
      app.dataset.theme = 'dark'
    }
  } else {
    root.classList.remove('dark')
    root.dataset.theme = theme
    body.classList.remove('dark-theme')
    body.dataset.theme = theme
    if (app) {
      app.classList.remove('dark')
      app.dataset.theme = theme
    }
  }
}

function restoreGlobalTheme() {
  restoreGlobalWebsiteTheme()
}

watch(readerTheme, (newTheme) => {
  applyStudyTheme(newTheme)
})

const isDisplaySettingsOpen = ref(false)
const isMobileSettingsOpen = ref(false)
const isSettingsModalOpen = ref(false)
const activeSettingsTab = ref<'general' | 'appearance' | 'data'>('general')
const activeSettingsSubmenu = ref<string | null>(null)
const displaySettingsRef = ref<HTMLElement | null>(null)
const mobileSettingsMenuRef = ref<HTMLElement | null>(null)

const openSettingsModal = (tab: 'general' | 'appearance' | 'data' = 'general') => {
  activeSettingsTab.value = tab
  isDisplaySettingsOpen.value = false
  activeSettingsSubmenu.value = null
  isSettingsModalOpen.value = true
}

const handleClearChatFromSettings = async () => {
  isDisplaySettingsOpen.value = false
  isSettingsModalOpen.value = false
  await clearChatHistory()
}

const setTheme = (key: any) => {
  const newTheme = key as ThemeKey
  readerTheme.value = newTheme
  applyStudyTheme(newTheme)
  try {
    localStorage.setItem(READER_THEME_KEY, key as string)
  } catch {
    // best-effort persistence
  }
}

const setFont = (key: 'serif' | 'sans') => {
  readerFont.value = key
  try {
    localStorage.setItem(READER_FONT_KEY, key)
  } catch {
    // best-effort persistence
  }
}

const toggleDisplaySettings = () => {
  isDisplaySettingsOpen.value = !isDisplaySettingsOpen.value
  if (!isDisplaySettingsOpen.value) activeSettingsSubmenu.value = null
}

const handleClickOutsideMenus = (event: MouseEvent) => {
  const target = event.target as Node

  if (isDisplaySettingsOpen.value && displaySettingsRef.value && !displaySettingsRef.value.contains(target)) {
    isDisplaySettingsOpen.value = false
    activeSettingsSubmenu.value = null
  }

  if (isMobileSettingsOpen.value && mobileSettingsMenuRef.value && !mobileSettingsMenuRef.value.contains(target)) {
    isMobileSettingsOpen.value = false
  }

  if (activeSessionMenu.value) {
    if (!(target as HTMLElement).closest('.session-dropdown-menu') && !(target as HTMLElement).closest('.session-options-btn')) {
      activeSessionMenu.value = null
    }
  }
}

/* ============================================================
   KEYBOARD / SELECTION
============================================================ */

const chatInputRef = ref<HTMLInputElement | null>(null)

const handleKeydown = (event: KeyboardEvent) => {
  // Always handle Escape regardless of typing state
  if (event.key === 'Escape') {
    if (isSettingsModalOpen.value) {
      isSettingsModalOpen.value = false
      return
    }
    if (isDisplaySettingsOpen.value) {
      isDisplaySettingsOpen.value = false
      activeSettingsSubmenu.value = null
      return
    }
    if (isFullscreen.value) toggleFullscreen()
    if (isMobile.value) closeMobilePanels()
    return
  }

  const target = event.target as HTMLElement | null
  const isTyping =
    target?.tagName === 'INPUT' ||
    target?.tagName === 'TEXTAREA' ||
    target?.isContentEditable

  if (isTyping) return

  if (event.key === 'ArrowRight') {
    nextPage()
  } else if (event.key === 'ArrowLeft') {
    prevPage()
  } else if (event.key === 'f' && event.ctrlKey) {
    event.preventDefault()
    toggleFullscreen()
  } else if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
    // Auto-focus chat if user starts typing alphanumeric/punctuation characters
    if (event.key.match(/^[a-zA-Z0-9!"#$%&'()*+,-./:;<=>?@[\]^_`{|}~]$/)) {
      if (!isAiSidebarOpen.value) {
        isAiSidebarOpen.value = true
        isReaderOpen.value = false
      }
      // Use nextTick to ensure the DOM has updated if the sidebar was just opened
      nextTick(() => {
        chatInputRef.value?.focus()
      })
    }
  }
}

const selectionPopup = ref({
  visible: false,
  x: 0,
  y: 0,
  text: '',
})

const handleTextSelection = () => {
  if (viewMode.value !== 'reading') return

  const selection = window.getSelection()
  if (!selection || selection.isCollapsed) {
    selectionPopup.value.visible = false
    return
  }

  const anchor = selection.anchorNode?.parentElement
  if (!anchor || (!anchor.closest('.reader-paper') && !anchor.closest('.message'))) {
    selectionPopup.value.visible = false
    return
  }

  const text = selection.toString().trim()
  if (!text || text.length < 3) {
    selectionPopup.value.visible = false
    return
  }

  const range = selection.getRangeAt(0)
  const rect = range.getBoundingClientRect()
  if (!rect.width && !rect.height) return

  selectionPopup.value = {
    visible: true,
    x: Math.min(
      Math.max(rect.left + rect.width / 2, 80),
      window.innerWidth - 80,
    ),
    y: Math.max(rect.top - 12, 18),
    text,
  }
}

const askAiAboutSelection = () => {
  const snippet = selectionPopup.value.text
  selectionPopup.value.visible = false
  window.getSelection()?.removeAllRanges()

  const chapterName = booksStore.currentChapter?.title || 'the current chapter'
  chatInput.value = `Could you please explain this excerpt from "${chapterName}", page ${currentPage.value}?\n\n"${snippet}"`
  toggleAiSidebar(true)

  nextTick(() => {
    void sendMessage()
  })
}

/* ============================================================
   CHAT STATE / ACTIONS
============================================================ */

const chatInput = ref('')

watch(chatInput, () => {
  nextTick(autoResizeInput)
})
const messagesContainerRef = ref<HTMLElement | null>(null)
const currentMessages = computed(() => chatStore.currentSession?.messages || [])
const hasMessages = computed(() => (currentMessages.value?.length || 0) > 0)

/* ============================================================
   DYNAMIC SESSION GREETINGS
============================================================ */
const activeGreetingKey = ref<string>('help')

const userFirstName = computed(() => {
  const raw = authStore.user?.first_name || authStore.user?.name || ''
  if (!raw) return ''
  return raw.trim().split(/\s+/)[0] || ''
})

const displayChapterName = computed(() => {
  return booksStore.currentChapter?.title || progressStore.dashboard?.current_chapter?.title || ''
})

const pickDynamicGreeting = () => {
  const name = userFirstName.value
  const chapter = displayChapterName.value

  const candidatePool: string[] = [
    'help',
    'help_today',
    'explore_today',
    'where_start',
    'which_chapter',
    'dive_in',
  ]

  if (name) {
    candidatePool.push(
      'help_name',
      'explore_name',
      'where_start_name',
      'which_chapter_name',
      'what_learn_name',
    )
  }

  if (chapter) {
    candidatePool.push(
      'continue_reading',
      'pick_up_last',
      'questions_chapter',
    )
    if (name) {
      candidatePool.push('continue_reading_name')
    }
  }

  // Avoid repeating the immediately previous greeting
  const filtered = candidatePool.filter((k) => k !== activeGreetingKey.value)
  const pool = filtered.length > 0 ? filtered : candidatePool
  activeGreetingKey.value = pool[Math.floor(Math.random() * pool.length)]
}

const dynamicGreeting = computed(() => {
  const name = userFirstName.value
  const chapter = displayChapterName.value

  let key = activeGreetingKey.value

  // Safety fallbacks if key requires chapter or name but it's unavailable
  if ((key.includes('chapter') || key.includes('reading') || key.includes('pick_up')) && !chapter) {
    key = name ? 'explore_name' : 'explore_today'
  }
  if (key.includes('name') && !name) {
    key = key.replace('_name', '_today').replace('what_learn_today', 'where_start')
  }

  return t(`chat.greetings.${key}`, {
    name,
    chapter,
    page: currentPage.value,
  })
})

watch(currentMessages, () => {
  scrollToBottom(false)
}, { deep: true })

const sendPrompt = (prompt: string) => {
  chatInput.value = prompt
  void sendMessage()
}

const reloadPage = () => {
  window.location.reload()
}

const startNewChat = async () => {
  activeSidebarTab.value = 'chats'
  isReaderOpen.value = true
  isAiSidebarOpen.value = true
  pickDynamicGreeting()
  await chatStore.createSession()
  chatInput.value = ''

  if (chatStore.currentSession) {
    if (booksStore.currentChapter?.id) {
      localStorage.setItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`, chatStore.currentSession.id)
    }

    saveReaderState()

    router.replace({
      name: 'study',
      params: { sessionId: chatStore.currentSession.id },
    })
  }
}

const toggleSplitScreen = () => {
  if (isReaderOpen.value && isAiSidebarOpen.value) {
    if (activeSidebarTab.value === 'chapters') {
      isAiSidebarOpen.value = false
    } else {
      isReaderOpen.value = false
    }
  } else {
    isReaderOpen.value = true
    isAiSidebarOpen.value = true
  }
}

const startNewChatAndOpen = async () => {
  await startNewChat()
  isAiSidebarOpen.value = true
  if (!isMobile.value) {
    isReaderOpen.value = false
  } else {
    isReaderOpen.value = true
  }
}

const switchSession = async (sessionId: string) => {
  pickDynamicGreeting()
  await chatStore.loadSession(sessionId)

  if (booksStore.currentChapter?.id) {
    localStorage.setItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`, sessionId)
  }

  isAiSidebarOpen.value = true
  if (isMobile.value) {
    isSidebarOpen.value = false
    isReaderOpen.value = true
  } else {
    isReaderOpen.value = false
  }

  saveReaderState()

  router.replace({
    name: 'study',
    params: { sessionId },
  })
}

const loadBookChapter = async (chapterId: string) => {
  chapterLoadError.value = null
  isChapterLoading.value = true
  try {
    await booksStore.loadChapter(chapterId)
  } catch (err: any) {
    chapterLoadError.value = err?.message || 'Failed to load chapter content.'
    console.error('Failed to load chapter:', err)
  } finally {
    isChapterLoading.value = false
  }

  const savedPage = getReadPosition(chapterId)
  currentPage.value = savedPage ?? 1
  jumpPageInput.value = String(currentPage.value)

  isReaderOpen.value = true
  isAiSidebarOpen.value = false

  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

// Automatically load chapter as soon as chapters are available if none is currently selected
watch(
  allSortedChapters,
  async (chapters) => {
    if (chapters.length && !booksStore.currentChapter && !isChapterLoading.value) {
      const savedChapterId = localStorage.getItem(LAST_CHAPTER_KEY)
      const targetChapter =
        (savedChapterId && chapters.find((c: any) => c.id === savedChapterId)) ||
        chapters.find((c: any) => c.title?.includes('Ch-1') || c.title?.includes('Chapter 1')) ||
        chapters[1] ||
        chapters[0]
      if (targetChapter) {
        await loadBookChapter(targetChapter.id)
      }
    }
  },
  { immediate: true },
)

const toggleFeedback = async (
  message: any,
  feedbackType: 'like' | 'dislike',
) => {
  const newFeedback =
    message.feedback?.feedback === feedbackType
      ? undefined
      : { feedback: feedbackType }

  const currentSession = chatStore.currentSession
  const messages = currentSession?.messages
  if (!messages) return

  const index = messages.findIndex((item: any) => item.id === message.id)
  if (index === -1) return

  messages[index] = {
    ...messages[index],
    feedback: newFeedback,
  }

  try {
    if (newFeedback) {
      await apiClient.post(`/messages/${message.id}/feedback`, { feedback: newFeedback.feedback })
      showFeedbackToast('Thank you for your feedback!')
    } else {
      await apiClient.delete(`/messages/${message.id}/feedback`)
    }
  } catch (error) {
    console.error('Failed to save feedback:', error)
  }
}

// ── AI FAB label pulse (shows "Smart AI" on load then every 30s) ─────────────
const aiLabelVisible = ref(false)
let aiLabelInterval: ReturnType<typeof setInterval> | null = null

function startAiLabelPulse() {
  // Show immediately
  aiLabelVisible.value = true
  setTimeout(() => { aiLabelVisible.value = false }, 3000)

  // Then pulse every 10 seconds
  aiLabelInterval = setInterval(() => {
    aiLabelVisible.value = true
    setTimeout(() => { aiLabelVisible.value = false }, 3000)
  }, 10000)
}

const isSubmitting = ref(false)

const sendMessage = async () => {
  const text = chatInput.value.trim()
  if (!text || isSubmitting.value) return

  // 1. Immediately wipe composer and reset height so sent text NEVER reappears
  isSubmitting.value = true
  chatInput.value = ''
  nextTick(autoResizeInput)

  try {
    if (!chatStore.currentSession) {
      await startNewChat()
    }

    if (!chatStore.currentSession) return

    // Only pass chapter_id when it is a well-formed UUID.
    // Sending undefined / null / "undefined" causes PostgreSQL to throw
    // "invalid input syntax for type uuid" → HTTP 500 on the backend.
    const rawChapterId = booksStore.currentChapter?.id
    const isValidUuid = typeof rawChapterId === 'string' && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(rawChapterId)
    const context: Record<string, unknown> = {
      page: currentPage.value,
    }
    if (isValidUuid) {
      context.chapter_id = rawChapterId
    }

    // 2. Force scroll to bottom so user sees their new message immediately
    scrollToBottom(true)

    // 3. Dispatch message (optimistically appends both user message & assistant placeholder)
    chatStore.sendMessage(
      chatStore.currentSession.id,
      text,
      context,
    )
  } finally {
    // 4. Immediately release lock so user can draft and submit subsequent messages concurrently
    isSubmitting.value = false
  }
}

/* ============================================================
   LIFECYCLE
============================================================ */

onMounted(async () => {
  warmUpBackend()
  // Start the "Smart AI" label pulse on the floating AI button
  setTimeout(startAiLabelPulse, 1200)
  pickDynamicGreeting()
  applyStudyTheme(readerTheme.value)
  window.addEventListener('resize', handleResize)
  window.addEventListener('touchstart', handleTouchStart, { passive: true })
  window.addEventListener('touchmove', handleTouchMove, { passive: true })
  window.addEventListener('touchend', handleTouchEnd, { passive: true })
  window.addEventListener('touchcancel', handleTouchCancel, { passive: true })
  document.addEventListener('mouseup', handleTextSelection)
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('click', handleClickOutsideMenus)

  if (!progressStore.dashboard) {
    progressStore.loadDashboard().catch(() => {})
  }

  const savedChapterId = localStorage.getItem(LAST_CHAPTER_KEY)
  const initialPromises: Promise<any>[] = [
    chatStore.loadSessions(1),
    booksStore.loadBooks(),
  ]
  if (savedChapterId) {
    initialPromises.push(booksStore.loadChapter(savedChapterId))
  }

  try {
    await Promise.allSettled(initialPromises)
  } catch (err) {
    console.error('Initial study data loading error:', err)
  }

  if (!booksStore.currentChapter) {
    const chapters = allSortedChapters.value
    if (chapters.length) {
      const defaultChapter = chapters.find((c: any) => c.title?.includes('Ch-1') || c.title?.includes('Chapter 1')) || chapters[1] || chapters[0]
      await loadBookChapter(defaultChapter.id)
    }
  }

  const sessionId = route.params.sessionId as string | undefined
  if (sessionId) {
    try {
      await chatStore.loadSession(sessionId)
    } catch (e) {
      console.error('Failed to load session from URL:', e)
      router.replace({ name: 'study' })
    }
  } else {
    // Force start a new chat if no specific session is requested
    chatStore.currentSession = null
  }

  // Open the current chapter in the sidebar when it is available.
  if (booksStore.currentChapter?.id) {
    expandedChapters.value[booksStore.currentChapter.id] = true
  }
  // Prevent completely blank workspace on load
  if (!isReaderOpen.value && !isAiSidebarOpen.value) {
    isReaderOpen.value = true
  }

  // Start study tour if appropriate
  setTimeout(() => {
    const { registerTour, startTour } = useTour()
    registerTour(studyTour)
    startTour('study')
  }, 1200)
})

onUnmounted(() => {
  restoreGlobalTheme()
  window.removeEventListener('resize', handleResize)
  window.removeEventListener('touchstart', handleTouchStart)
  window.removeEventListener('touchmove', handleTouchMove)
  window.removeEventListener('touchend', handleTouchEnd)
  window.removeEventListener('touchcancel', handleTouchCancel)
  document.removeEventListener('mousemove', onDragLeft)
  document.removeEventListener('mouseup', handleTextSelection)
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('click', handleClickOutsideMenus)

  if (overscrollResetTimer) {
    clearTimeout(overscrollResetTimer)
  }

  if (aiLabelInterval) {
    clearInterval(aiLabelInterval)
    aiLabelInterval = null
  }

  document.body.style.cursor = ''
})

watch(
  () => route.params.sessionId,
  async (newSessionId, oldSessionId) => {
    if (newSessionId !== oldSessionId) {
      pickDynamicGreeting()
      if (newSessionId) {
        if (chatStore.currentSession?.id === newSessionId) {
          return
        }
        try {
          await chatStore.loadSession(newSessionId as string)
        } catch (e) {
          console.error('Failed to load session from URL:', e)
        }
      } else {
        chatStore.currentSession = null
      }
    }
  }
)

watch(
  () => chatStore.currentSession?.id,
  (newId, oldId) => {
    if (newId !== oldId) {
      pickDynamicGreeting()
    }
  }
)
</script>

<style scoped>
/* ============================================================
   GLOBAL DESIGN TOKENS
============================================================ */

.chat-page {
  --reader-bg: var(--rt-bg);
  --reader-surface: var(--rt-surface);
  --reader-surface-2: var(--rt-surface-2);
  --reader-border: var(--rt-border);
  --reader-text: var(--rt-text);
  --reader-body: var(--rt-text-body);
  --reader-muted: var(--rt-muted);
  --reader-accent: var(--rt-accent);
  --reader-accent-hover: var(--rt-accent-hover);
  --reader-accent-text: var(--rt-accent-text);

  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  min-width: 0;
  overflow: hidden;
  background: var(--reader-bg);
  color: var(--reader-body);
  font-family: var(--font-body, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
  transition: background-color 0.25s ease, color 0.25s ease;
}

.chat-page.is-resizing,
.chat-page.is-resizing * {
  user-select: none !important;
  cursor: ew-resize !important;
}

/* Ensure all form controls inside .chat-page strictly respect reader theme tokens */
.chat-page textarea,
.chat-page input:not([type="checkbox"]):not([type="radio"]),
.chat-page select {
  background-color: var(--reader-surface) !important;
  color: var(--reader-text) !important;
  border-color: var(--reader-border) !important;
}

.chat-page .ai-composer__textarea {
  background: transparent !important;
  color: var(--reader-text) !important;
}

.chat-page .ai-composer__textarea::placeholder {
  color: var(--reader-muted) !important;
}

.chat-page .sidebar-search-input {
  background: transparent !important;
  color: var(--reader-text) !important;
}

.chat-page .sidebar-search-input::placeholder {
  color: var(--reader-muted) !important;
}

.chat-page .ai-search-input {
  background: transparent !important;
  color: var(--reader-text) !important;
}

.chat-page .ai-search-input::placeholder {
  color: var(--reader-muted) !important;
}

/* ============================================================
   MOBILE BACKDROP
============================================================ */

.mobile-backdrop {
  position: fixed;
  inset: 0;
  z-index: 30;
  border: 0;
  background: rgba(2, 6, 23, 0.42);
  backdrop-filter: blur(5px);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ============================================================
   COLLAPSED RAIL
============================================================ */

.collapsed-rail {
  position: relative;
  z-index: 20;
  width: 48px;
  flex: 0 0 48px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 12px 0;
  background: color-mix(in srgb, var(--reader-surface-2) 92%, var(--reader-bg));
  border-color: var(--reader-border);
}

.collapsed-rail--left {
  border-right: 1px solid var(--reader-border);
}

.collapsed-rail--right {
  border-left: 1px solid var(--reader-border);
}

.rail-button {
  width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 8px;
  color: var(--reader-muted);
  background: transparent;
  cursor: pointer;
  transition: all 0.2s ease;
}

.rail-button:hover {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

.rail-button--accent {
  color: var(--reader-text);
}

.rail-button svg {
  width: 18px;
  height: 18px;
}

/* ============================================================
   SIDE PANELS
============================================================ */

.side-panel {
  position: relative;
  z-index: 40;
  flex: 0 0 auto;
  min-width: 0;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: visible;
  color: var(--reader-body);
  background: var(--reader-surface-2);
  border-color: var(--reader-border);
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.side-panel--left {
  width: 260px !important;
  flex: 0 0 260px !important;
  border-right: 1px solid var(--reader-border);
  z-index: 50;
}

.side-panel--right {
  border-left: 1px solid var(--reader-border);
}

.side-panel--mobile {
  position: fixed;
  top: 0;
  bottom: 0;
  width: min(88vw, 380px) !important;
  max-width: 380px;
  box-shadow: 0 20px 70px rgba(2, 6, 23, 0.22);
}

.side-panel--left.side-panel--mobile {
  left: 0;
  z-index: 50;
}

.side-panel--right.side-panel--mobile {
  right: 0;
  z-index: 50;
  height: 100dvh;
  display: flex;
  flex-direction: column;
}

.side-panel__header {
  flex: 0 0 auto;
  padding: 12px;
  border-bottom: none;
}

.side-panel__brand-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 16px;
}

.brand-button {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 12px;
  border: 0;
  color: var(--reader-text);
  background: transparent;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.brand-button img {
  width: 22px;
  height: 22px;
  object-fit: contain;
}

.icon-button {
  width: 32px;
  height: 32px;
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid transparent;
  border-radius: 9px;
  color: var(--reader-muted);
  background: transparent;
  cursor: pointer;
  transition: background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
}

.icon-button:hover {
  color: var(--reader-text);
  background: var(--reader-surface);
  border-color: var(--reader-border);
}

.icon-button svg {
  width: 17px;
  height: 17px;
}

.ai-glow-inner input::placeholder {
  color: #a8a29e; /* Subtle taupe */
}

/* Search Glow Animation */
@keyframes spinGlow {
  from { transform: translate(-50%, -50%) rotate(0deg); }
  to { transform: translate(-50%, -50%) rotate(360deg); }
}



.sidebar-search-wrapper:focus-within {
  border-color: var(--reader-accent, #395886);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--reader-accent, #395886) 15%, transparent);
}

.search-icon {
  width: 14px;
  height: 14px;
  color: var(--reader-muted, #94A3B8);
  flex-shrink: 0;
}

.sidebar-search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent !important;
  color: var(--reader-text) !important;
  font-size: 0.85rem;
  padding: 0;
  outline: none;
}

.sidebar-search-input::placeholder {
  color: var(--reader-muted) !important;
}

.search-clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: none;
  background: var(--reader-surface-2);
  color: var(--reader-muted);
  cursor: pointer;
  padding: 0;
  flex-shrink: 0;
  transition: background 0.15s ease, color 0.15s ease;
}

.search-clear:hover {
  background: var(--reader-surface);
  color: var(--reader-text);
}

.search-clear svg {
  width: 12px;
  height: 12px;
}

.new-chat-button, .reader-toggle-button {
  width: 100%;
  margin-top: 8px;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  padding: 6px 12px;
  border: none;
  border-radius: 8px;
  color: var(--reader-text);
  background: transparent;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s ease, transform 0.1s ease;
}

.new-chat-button:hover, .reader-toggle-button:hover {
  background: var(--reader-surface);
}

.new-chat-button:active, .reader-toggle-button:active {
  transform: scale(0.97);
  background: var(--reader-surface);
}

.new-chat-button svg, .reader-toggle-button svg {
  width: 16px;
  height: 16px;
  opacity: 0.7;
}

.sidebar-tab-switcher {
  display: flex;
  margin-top: 16px;
  background: var(--reader-surface);
  padding: 4px;
  border-radius: 10px;
  gap: 4px;
}

.tab-btn {
  flex: 1;
  border: none;
  background: transparent;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--reader-muted);
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn.is-active {
  background: var(--reader-bg);
  color: var(--reader-text);
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.sidebar-tab-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* ChatGPT style chat list */
.chatgpt-session-list .session-item {
  border: none;
  background: transparent;
  padding: 10px 14px;
  border-radius: 8px;
  font-weight: 500;
  color: var(--reader-body);
  transition: background 0.15s ease;
  justify-content: flex-start;
}
.chatgpt-session-list .session-item:hover {
  background: var(--reader-surface);
  transform: none;
  box-shadow: none;
}
.chatgpt-session-list .session-item--active {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

/* Minimalist Chapter List */
.modern-chapter-list .chapter-item {
  border: none;
  background: transparent;
  box-shadow: none;
  padding: 0;
  margin-bottom: 2px;
}
.modern-chapter-list .chapter-item__row {
  padding: 8px 12px;
  border-radius: 8px;
  background: transparent;
}
.modern-chapter-list .chapter-item__row:hover {
  background: var(--reader-surface);
}
.modern-chapter-list .chapter-item--active .chapter-item__row {
  background: var(--reader-surface-2);
}

/* Sidebar Footer (ChatGPT-Style User Pill) */
.sidebar-footer {
  padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
  border-top: 1px solid var(--reader-border);
  position: relative;
  z-index: 9999;
}

.chatgpt-user-pill {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 12px;
  background: transparent;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
  text-align: left;
  user-select: none;
}

.chatgpt-user-pill:hover,
.chatgpt-user-pill.is-active {
  background: var(--reader-surface-2);
  border-color: var(--reader-border);
}

.chatgpt-user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #10b981;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.chatgpt-user-avatar svg {
  width: 17px;
  height: 17px;
}

.chatgpt-user-avatar--sm {
  width: 28px;
  height: 28px;
  font-size: 0.76rem;
}

.chatgpt-user-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  flex: 1;
  overflow: hidden;
}

.chatgpt-user-name {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--reader-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.25;
}

.chatgpt-user-role {
  font-size: 0.68rem;
  color: var(--reader-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
}

.chatgpt-user-dots {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  color: var(--reader-muted);
  flex-shrink: 0;
  opacity: 0.7;
  transition: opacity 0.15s ease, color 0.15s ease;
}

.chatgpt-user-dots svg {
  width: 16px;
  height: 16px;
}

.chatgpt-user-pill:hover .chatgpt-user-dots,
.chatgpt-user-pill.is-active .chatgpt-user-dots {
  opacity: 1;
  color: var(--reader-text);
}

.side-panel__scroll {
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 12px;
}

.sidebar-link {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 9px 10px;
  border: 1px solid var(--reader-border);
  border-radius: 11px;
  color: var(--reader-text);
  background: var(--reader-surface);
  font-size: 0.72rem;
  font-weight: 800;
  text-decoration: none;
  transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
}

.sidebar-link:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
  border-color: color-mix(in srgb, var(--reader-border) 72%, var(--reader-accent));
}

.sidebar-link__icon {
  width: 28px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border-radius: 8px;
  color: var(--reader-accent-text);
  background: var(--reader-accent);
}

.sidebar-link__icon svg {
  width: 15px;
  height: 15px;
}

.sidebar-section {
  margin-top: 18px;
}

.sidebar-section--sessions {
  padding-top: 4px;
  border-top: none;
}

.sidebar-section__title {
  padding: 0 4px 8px;
  color: var(--reader-muted);
  font-size: 0.46rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.chapter-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.chapter-item {
  overflow: hidden;
  border: 1px solid var(--reader-border);
  border-radius: 11px;
  background: color-mix(in srgb, var(--reader-surface) 78%, transparent);
}

.chapter-item--active {
  border-color: color-mix(in srgb, var(--reader-accent) 32%, var(--reader-border));
  box-shadow: inset 3px 0 0 var(--reader-accent);
}

.chapter-item__row {
  min-height: 40px;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 5px 4px 10px;
}

.chapter-title {
  min-width: 0;
  flex: 1;
  padding: 6px 0;
  border: 0;
  color: var(--reader-text);
  background: transparent;
  text-align: left;
  font-size: 0.63rem;
  font-weight: 700;
  line-height: 1.35;
  cursor: pointer;
}

.chapter-toggle {
  width: 29px;
  height: 29px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 0;
  border-radius: 8px;
  color: var(--reader-muted);
  background: transparent;
  cursor: pointer;
}

.chapter-toggle:hover {
  color: var(--reader-text);
  background: var(--reader-bg);
}

.chapter-toggle svg {
  width: 14px;
  height: 14px;
  transition: transform 0.18s ease;
}

.rotate-180 {
  transform: rotate(180deg);
}

.chapter-sections {
  padding: 6px 8px 8px 10px;
  border-top: 1px solid var(--reader-border);
  background: color-mix(in srgb, var(--reader-bg) 62%, transparent);
}

.section-link,
.chapter-quiz-link {
  width: 100%;
  display: flex;
  align-items: flex-start;
  gap: 7px;
  padding: 6px 7px;
  border: 0;
  border-radius: 7px;
  color: var(--reader-muted);
  background: transparent;
  text-align: left;
  font-size: 0.56rem;
  line-height: 1.4;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease;
}

.section-link:hover,
.section-link--active,
.chapter-quiz-link:hover {
  color: var(--reader-text);
  background: color-mix(in srgb, var(--reader-border) 48%, transparent);
}

.section-bullet {
  width: 17px;
  flex: 0 0 17px;
  color: var(--reader-accent);
  font-size: 0.47rem;
  font-weight: 800;
}

.chapter-quiz-link {
  margin-top: 5px;
  padding-top: 7px;
  border-top: 1px solid color-mix(in srgb, var(--reader-border) 72%, transparent);
  color: var(--reader-accent);
  font-weight: 800;
}

.chapter-quiz-link svg {
  width: 13px;
  height: 13px;
  flex: 0 0 auto;
  margin-top: 1px;
}

.show-more-button {
  width: 100%;
  margin-top: 7px;
  min-height: 32px;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-muted);
  background: transparent;
  font-size: 0.56rem;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease;
}

.show-more-button:hover {
  color: var(--reader-text);
  background: var(--reader-surface);
}

.message-stack__assistant {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

:deep(.thinking-block) {
  margin: 8px 0;
  padding: 8px 12px;
  background-color: var(--color-surface-sunken);
  border-left: 2px solid var(--color-border);
  border-radius: 4px;
  font-size: 0.9em;
  color: var(--color-text-muted);
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

.sidebar-group-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--reader-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 8px 12px 4px;
  margin: 0;
}

.session-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.session-item-wrapper {
  position: relative;
}

.session-rename-input {
  width: 100%;
  display: block;
  padding: 7px 8px;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-text);
  background: var(--reader-surface-2);
  font-family: inherit;
  font-size: inherit;
  outline: none;
}
.session-rename-input:focus {
  border-color: var(--reader-accent);
  box-shadow: 0 0 0 2px rgba(var(--reader-accent-rgb, 16, 185, 129), 0.2);
}

.session-item {
  width: 100%;
  display: block;
  overflow: hidden;
  padding: 8px 9px;
  border: 1px solid transparent;
  border-radius: 9px;
  color: var(--reader-text);
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: background 0.16s ease, border-color 0.16s ease;
}

.session-item:hover,
.session-item--active,
.session-item-wrapper:hover .session-item {
  border-color: var(--reader-border);
  background: var(--reader-surface);
}

.session-options-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: transparent;
  border: none;
  padding: 4px;
  border-radius: 6px;
  color: var(--reader-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s ease, background 0.2s ease, color 0.2s ease;
}

.session-item-wrapper:hover .session-options-btn,
.session-options-btn.is-visible {
  opacity: 1;
}

.session-options-btn:hover,
.session-options-btn.is-visible {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

.session-dropdown-menu {
  position: absolute;
  top: 80%;
  right: 10px;
  background: var(--reader-surface);
  border: 1px solid var(--reader-border);
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  padding: 6px;
  min-width: 180px;
  z-index: 100;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.chat-page.dark .session-dropdown-menu,
:global(html.dark) .session-dropdown-menu {
  box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: var(--reader-text);
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease, color 0.15s ease;
}

.dropdown-item:not(:disabled):hover {
  background: var(--reader-surface-2);
}

.dropdown-item:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.dropdown-item.text-danger {
  color: var(--reader-error, #ef4444);
}

.dropdown-item.text-danger:hover {
  background: rgba(239, 68, 68, 0.1);
}

.dropdown-divider {
  margin: 4px 0;
  border: 0;
  border-top: 1px solid var(--reader-border);
}

.session-item__title {
  display: block;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  font-size: 0.59rem;
  font-weight: 700;
}

.resize-handle {
  position: absolute;
  top: 0;
  bottom: 0;
  z-index: 25;
  width: 12px;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: col-resize;
}

.resize-handle--right {
  right: -6px;
}

.resize-handle--left {
  left: -6px;
}

.resize-handle span {
  width: 3px;
  height: 44px;
  display: block;
  margin: auto;
  border-radius: 99px;
  background: transparent;
  transition: background 0.16s ease, height 0.16s ease;
}

.resize-handle:hover span,
.resize-handle:focus-visible span {
  height: 70px;
  background: var(--reader-accent);
}

/* ============================================================
   READER MAIN / HEADER
============================================================ */

.reader-main {
  position: relative;
  min-width: 0;
  flex: 1 1 auto;
  display: flex;
  flex-direction: column;
  background: var(--reader-bg);
}

.reading-progress-track {
  position: absolute;
  inset: 0 0 auto;
  z-index: 15;
  height: 3px;
  background: color-mix(in srgb, var(--reader-border) 50%, transparent);
}

.reading-progress-value {
  height: 100%;
  background: var(--reader-accent);
  transition: width 0.25s ease;
}

.reader-header {
  position: relative;
  z-index: 10;
  min-height: 56px;
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: none;
  background: transparent;
  pointer-events: none; /* Let clicks pass through empty header space */
}

.reader-header__left,
.reader-header__right {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 7px;
  pointer-events: auto; /* Re-enable clicks on buttons */
}

.btn-read-with-ai {
  padding: 4px 8px;
  font-weight: 600;
  font-size: 0.72rem;
  color: var(--reader-muted);
  background: transparent;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.2s;
}

.btn-read-with-ai:hover {
  color: var(--reader-text);
}

.btn-read-with-ai.is-active {
  color: #10b981;
}

.btn-read-with-ai.is-active:hover {
  color: #0d9668;
}

.reader-header__right {
  justify-content: flex-end;
}

.header-icon-button {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-text);
  background: var(--reader-surface);
  text-decoration: none;
  cursor: pointer;
  transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
}

.header-icon-button:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
  border-color: var(--reader-border);
}

.header-icon-button svg {
  width: 16px;
  height: 16px;
}

.breadcrumb {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 7px;
  color: var(--reader-muted);
  font-size: 0.62rem;
  font-weight: 700;
}

.breadcrumb__home {
  color: var(--reader-muted);
  text-decoration: none;
}

.breadcrumb__home:hover {
  color: var(--reader-text);
}

.breadcrumb svg {
  width: 13px;
  height: 13px;
  flex: 0 0 auto;
}

.breadcrumb__current {
  max-width: min(42vw, 430px);
  overflow: hidden;
  color: var(--reader-text);
  white-space: nowrap;
  text-overflow: ellipsis;
}

.segmented-control {
  display: inline-flex;
  gap: 2px;
  padding: 3px;
  border: 1px solid var(--reader-border);
  border-radius: 10px;
  background: var(--reader-surface);
}

.segmented-control button {
  min-height: 28px;
  padding: 5px 9px;
  border: 0;
  border-radius: 7px;
  color: var(--reader-muted);
  background: transparent;
  font-size: 0.52rem;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease;
}

.segmented-control button:hover {
  color: var(--reader-text);
}

.segmented-control button.is-active {
  color: var(--reader-accent-text);
  background: var(--reader-accent);
}

.page-control {
  display: inline-flex;
  align-items: center;
  gap: 3px;
  padding: 3px;
  border: 1px solid var(--reader-border);
  border-radius: 10px;
  background: var(--reader-surface);
}

.page-control > button {
  width: 30px;
  height: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 7px;
  color: var(--reader-muted);
  background: transparent;
  cursor: pointer;
}

.page-control > button:hover:not(:disabled) {
  color: var(--reader-text);
  background: var(--reader-bg);
}

.page-control > button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.page-control svg {
  width: 15px;
  height: 15px;
}

.page-jump {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 0 4px;
  color: var(--reader-muted);
  font-size: 0.53rem;
  font-weight: 700;
}

.page-jump input {
  width: 34px;
  height: 26px;
  border: 1px solid var(--reader-border);
  border-radius: 6px;
  outline: none;
  color: var(--reader-text);
  background: var(--reader-bg);
  text-align: center;
  font-size: 0.57rem;
  font-weight: 800;
}

.page-jump input:focus {
  border-color: var(--reader-accent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--reader-accent) 16%, transparent);
}

.reader-menu {
  position: relative;
}

.header-control-button {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 8px;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-text);
  background: var(--reader-surface);
  font-size: 0.52rem;
  font-weight: 800;
  cursor: pointer;
  transition: background 0.16s ease, transform 0.16s ease;
}

.header-control-button:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
}

.header-control-button svg {
  width: 11px;
  height: 11px;
  opacity: 0.6;
}

.font-aa {
  font-family: Georgia, serif;
  font-size: 0.9rem;
  line-height: 1;
}

.theme-swatch {
  width: 12px;
  height: 12px;
  flex: 0 0 12px;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 50%;
}

.theme-menu-label {
  max-width: 66px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* ============================================================
   CHATGPT-STYLE FLOATING POPOVER MENU
============================================================ */

.chatgpt-popover-menu {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 8px;
  right: 8px;
  z-index: 10000;
  background: var(--reader-surface);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border: 1px solid var(--reader-border);
  border-radius: 14px;
  box-shadow: 0 16px 36px -6px rgba(0, 0, 0, 0.28), 0 6px 16px -4px rgba(0, 0, 0, 0.12);
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  transform-origin: bottom center;
}

.chatgpt-menu-user-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
}

.chatgpt-menu-user-meta {
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}

.chatgpt-menu-user-name {
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--reader-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.25;
}

.chatgpt-menu-user-email {
  font-size: 0.7rem;
  color: var(--reader-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.2;
}

.chatgpt-menu-divider {
  height: 1px;
  background: var(--reader-border);
  margin: 4px 0;
}

.chatgpt-menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--reader-text);
  font-size: 0.82rem;
  font-weight: 450;
  transition: background 0.15s ease, color 0.15s ease;
  text-decoration: none;
}

.chatgpt-menu-item:hover,
.chatgpt-menu-item.is-expanded {
  background: var(--reader-surface-2);
}

.chatgpt-menu-item--danger {
  color: var(--reader-error, #ef4444);
}

.chatgpt-menu-item--danger:hover {
  background: color-mix(in srgb, var(--reader-error, #ef4444) 10%, transparent);
}

.chatgpt-menu-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  color: var(--reader-muted);
  flex-shrink: 0;
}

.chatgpt-menu-item--danger .chatgpt-menu-icon {
  color: var(--reader-error, #ef4444);
}

.chatgpt-menu-icon svg {
  width: 16px;
  height: 16px;
}

.chatgpt-menu-label {
  flex: 1;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chatgpt-menu-pill-tag {
  font-size: 0.68rem;
  padding: 2px 7px;
  border-radius: 9999px;
  background: var(--reader-surface-2);
  border: 1px solid var(--reader-border);
  color: var(--reader-muted);
  font-weight: 500;
  text-transform: capitalize;
}

.chatgpt-menu-chevron {
  width: 14px;
  height: 14px;
  color: var(--reader-muted);
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.chatgpt-menu-chevron.is-rotated {
  transform: rotate(180deg);
}

/* Inline Accordions */
.chatgpt-inline-accordion {
  padding: 4px 6px 8px;
}

.chatgpt-inline-themes-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 4px;
}

.chatgpt-inline-theme-chip {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 6px 8px;
  border-radius: 7px;
  border: 1px solid var(--reader-border);
  background: var(--reader-surface-2);
  color: var(--reader-text);
  font-size: 0.72rem;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 0.15s ease, background 0.15s ease;
  text-align: left;
}

.chatgpt-inline-theme-chip:hover {
  border-color: var(--reader-accent);
}

.chatgpt-inline-theme-chip.is-selected {
  border-color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 12%, var(--reader-surface-2));
  font-weight: 600;
}

.chatgpt-chip-swatch {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid;
  flex-shrink: 0;
}

.chatgpt-chip-label {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chatgpt-chip-check {
  width: 12px;
  height: 12px;
  margin-left: auto;
  color: var(--reader-accent);
}

.chatgpt-inline-font-switch {
  display: flex;
  gap: 6px;
}

.chatgpt-font-switch-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
  padding: 6px;
  border-radius: 8px;
  border: 1px solid var(--reader-border);
  background: var(--reader-surface-2);
  color: var(--reader-text);
  cursor: pointer;
  transition: all 0.15s ease;
}

.chatgpt-font-switch-btn:hover {
  border-color: var(--reader-accent);
}

.chatgpt-font-switch-btn.is-selected {
  border-color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 12%, var(--reader-surface-2));
}

.chatgpt-font-subtext {
  font-size: 0.62rem;
  color: var(--reader-muted);
}

/* Popover Transitions */
.chatgpt-popover-enter-active,
.chatgpt-popover-leave-active {
  transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}

.chatgpt-popover-enter-from,
.chatgpt-popover-leave-to {
  opacity: 0;
  transform: translateY(6px) scale(0.97);
}

/* ============================================================
   CHATGPT-STYLE SETTINGS MODAL DIALOG
============================================================ */

.chatgpt-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  z-index: 999999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.chatgpt-settings-dialog {
  width: min(680px, 96vw);
  max-height: min(580px, 88vh);
  background: var(--reader-surface);
  border: 1px solid var(--reader-border);
  border-radius: 16px;
  box-shadow: 0 24px 60px -12px rgba(0, 0, 0, 0.4), 0 8px 24px -6px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chatgpt-dialog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid var(--reader-border);
}

.chatgpt-dialog-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.chatgpt-dialog-header-icon {
  width: 18px;
  height: 18px;
  color: var(--reader-muted);
}

.chatgpt-dialog-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--reader-text);
  margin: 0;
}

.chatgpt-dialog-close-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--reader-muted);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.chatgpt-dialog-close-btn:hover {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

.chatgpt-dialog-close-btn svg {
  width: 18px;
  height: 18px;
}

.chatgpt-dialog-body {
  display: flex;
  flex: 1;
  min-height: 360px;
  overflow: hidden;
}

.chatgpt-dialog-sidebar {
  width: 170px;
  padding: 14px 10px;
  border-right: 1px solid var(--reader-border);
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex-shrink: 0;
  background: color-mix(in srgb, var(--reader-surface-2) 30%, var(--reader-surface));
}

.chatgpt-tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 12px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--reader-muted);
  font-size: 0.84rem;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}

.chatgpt-tab-btn svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.chatgpt-tab-btn:hover {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

.chatgpt-tab-btn.is-active {
  background: var(--reader-surface-2);
  color: var(--reader-text);
  font-weight: 600;
}

.chatgpt-dialog-content {
  flex: 1;
  padding: 22px 24px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.chatgpt-tab-panel {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.chatgpt-panel-intro {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.chatgpt-setting-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.chatgpt-setting-row--stacked-sm {
  align-items: flex-start;
}

.chatgpt-setting-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  max-width: 340px;
}

.chatgpt-setting-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--reader-text);
}

.chatgpt-setting-desc {
  font-size: 0.78rem;
  color: var(--reader-muted);
  line-height: 1.4;
}

.chatgpt-panel-divider {
  height: 1px;
  background: var(--reader-border);
  margin: 4px 0;
}

.chatgpt-segmented-control {
  display: inline-flex;
  padding: 3px;
  background: var(--reader-surface-2);
  border: 1px solid var(--reader-border);
  border-radius: 10px;
  gap: 3px;
}

.chatgpt-segmented-btn {
  padding: 6px 14px;
  border-radius: 7px;
  border: none;
  background: transparent;
  color: var(--reader-muted);
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;
}

.chatgpt-segmented-btn.is-active {
  background: var(--reader-surface);
  color: var(--reader-text);
  font-weight: 600;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chatgpt-system-pill {
  font-size: 0.72rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 9999px;
  background: color-mix(in srgb, var(--reader-accent) 15%, transparent);
  color: var(--reader-accent);
  border: 1px solid color-mix(in srgb, var(--reader-accent) 30%, transparent);
}

.chatgpt-themes-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 12px;
  margin-top: 4px;
}

.chatgpt-theme-card {
  display: flex;
  flex-direction: column;
  padding: 8px;
  border-radius: 12px;
  border: 1px solid var(--reader-border);
  background: var(--reader-surface-2);
  cursor: pointer;
  transition: all 0.18s ease;
  text-align: left;
}

.chatgpt-theme-card:hover {
  border-color: var(--reader-accent);
  transform: translateY(-1px);
}

.chatgpt-theme-card.is-selected {
  border-color: var(--reader-accent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--reader-accent) 35%, transparent);
  background: var(--reader-surface);
}

.chatgpt-card-canvas {
  height: 60px;
  border-radius: 8px;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.chatgpt-card-inner {
  width: 100%;
  height: 100%;
  border-radius: 6px;
  border: 1px solid;
  padding: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  justify-content: center;
}

.chatgpt-card-bar {
  height: 4px;
  width: 40%;
  border-radius: 2px;
}

.chatgpt-card-line {
  height: 3px;
  width: 75%;
  border-radius: 2px;
  opacity: 0.8;
}

.chatgpt-card-subline {
  height: 2px;
  width: 55%;
  border-radius: 2px;
  opacity: 0.5;
}

.chatgpt-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 2px 4px;
}

.chatgpt-card-name {
  font-size: 0.78rem;
  font-weight: 500;
  color: var(--reader-text);
}

.chatgpt-card-checkmark {
  width: 14px;
  height: 14px;
  color: var(--reader-accent);
  display: flex;
  align-items: center;
  justify-content: center;
}

.chatgpt-card-checkmark svg {
  width: 14px;
  height: 14px;
}

.chatgpt-modal-danger-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid var(--reader-error, #ef4444);
  background: color-mix(in srgb, var(--reader-error, #ef4444) 10%, transparent);
  color: var(--reader-error, #ef4444);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.chatgpt-modal-danger-btn svg {
  width: 15px;
  height: 15px;
}

.chatgpt-modal-danger-btn:hover {
  background: var(--reader-error, #ef4444);
  color: #ffffff;
}

/* Modal Transitions */
.chatgpt-modal-fade-enter-active,
.chatgpt-modal-fade-leave-active {
  transition: opacity 0.2s ease;
}

.chatgpt-modal-fade-enter-active .chatgpt-settings-dialog,
.chatgpt-modal-fade-leave-active .chatgpt-settings-dialog {
  transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}

.chatgpt-modal-fade-enter-from,
.chatgpt-modal-fade-leave-to {
  opacity: 0;
}

.chatgpt-modal-fade-enter-from .chatgpt-settings-dialog,
.chatgpt-modal-fade-leave-to .chatgpt-settings-dialog {
  opacity: 0;
  transform: scale(0.96) translateY(8px);
}

@media (max-width: 640px) {
  .chatgpt-dialog-body {
    flex-direction: column;
  }
  .chatgpt-dialog-sidebar {
    width: 100%;
    flex-direction: row;
    border-right: none;
    border-bottom: 1px solid var(--reader-border);
    overflow-x: auto;
    padding: 8px 12px;
  }
  .chatgpt-tab-btn {
    white-space: nowrap;
    padding: 6px 12px;
  }
  .chatgpt-dialog-content {
    padding: 16px;
  }
  .chatgpt-themes-gallery {
    grid-template-columns: repeat(2, 1fr);
  }
}

.assistant-header-button {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 10px;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-text);
  background: var(--reader-surface);
  font-size: 0.52rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.16s ease, background 0.16s ease, color 0.16s ease;
}

.assistant-header-button:hover,
.assistant-header-button.is-active {
  transform: translateY(-1px);
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  border-color: transparent;
}

.assistant-header-button svg {
  width: 14px;
  height: 14px;
}

/* ============================================================
   READER SCROLL / PAPER
============================================================ */

.reader-scroll {
  position: relative;
  flex: 1;
  min-height: 0;
  overflow: auto;
  padding: 0; /* padding moved to inner for fluid reading canvas */
  scroll-behavior: smooth;
  overscroll-behavior: contain;
  background: var(--reader-surface);
}

.reader-column {
  position: relative;
  width: 100%;
  margin: 0 auto;
}

.reader-paper {
  width: 100%;
  max-width: 53em;
  margin: 0 auto;
  color: var(--reader-body);
  background: transparent;
  border: none;
  border-radius: 0;
  box-shadow: none;
  transition: color 0.25s ease;
}

.reader-paper--preface {
  max-width: 66em;
}

.reader-paper--serif,
.reader-paper--serif * {
  font-family: Literata, Merriweather, Georgia, Cambria, "Times New Roman", serif;
}

.reader-paper--sans,
.reader-paper--sans * {
  font-family: var(--font-body, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
}

.reader-paper__inner {
  position: relative;
  min-height: inherit;
  display: flex;
  flex-direction: column;
  padding: 72px clamp(32px, 7vw, 120px) 120px;
}

.page-slide-enter-active,
.page-slide-leave-active {
  transition: opacity 0.4s ease, transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.page-slide-enter-from {
  opacity: 0;
  transform: translateY(40px);
}
.page-slide-leave-to {
  opacity: 0;
  transform: translateY(-40px);
}

.section-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
  margin-bottom: 26px;
  padding-bottom: 18px;
  border-bottom: 1px solid var(--reader-border);
}

.section-chip {
  max-width: 100%;
  padding: 6px 9px;
  overflow: hidden;
  border: 1px solid var(--reader-border);
  border-radius: 999px;
  color: var(--reader-body);
  background: transparent;
  font-size: 0.55rem;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
  cursor: pointer;
  transition: background 0.16s ease, color 0.16s ease;
}

.section-chip:hover {
  color: var(--reader-text);
  background: var(--reader-bg);
}

.reading-canvas-header {
  margin-bottom: 48px;
  text-align: center;
}

.reading-canvas-metadata {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: var(--reader-muted);
  margin-bottom: 8px;
}

.reading-canvas-chapter {
  font-size: clamp(1.2rem, 2vw, 1.5rem);
  font-weight: 600;
  color: var(--reader-text);
  letter-spacing: 0.02em;
  line-height: 1.3;
}

.reading-canvas-divider {
  border: 0;
  height: 1px;
  background: color-mix(in srgb, var(--reader-border) 50%, transparent);
  margin: 32px auto 0;
  width: 60px;
}

.reading-canvas-controls {
  position: absolute;
  top: 16px;
  right: 24px;
  display: flex;
  gap: 8px;
  z-index: 20;
}

.reading-canvas-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid color-mix(in srgb, var(--reader-border) 40%, transparent);
  background: color-mix(in srgb, var(--reader-surface) 40%, transparent);
  color: var(--reader-text);
  backdrop-filter: blur(8px);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
}

.reading-canvas-btn:hover {
  background: color-mix(in srgb, var(--reader-text) 10%, var(--reader-surface));
  border-color: color-mix(in srgb, var(--reader-text) 20%, transparent);
  transform: scale(1.1);
}

.reading-canvas-btn:hover svg {
  stroke-width: 2.5;
}

.reading-canvas-btn:active {
  transform: scale(0.95);
}

.reading-canvas-zoom-label {
  background: transparent;
  border: none;
  color: var(--reader-text);
  font-weight: 600;
  font-size: 0.85rem;
  width: 48px;
  text-align: center;
  cursor: pointer;
  transition: color 0.2s;
}

.reading-canvas-zoom-label:hover {
  color: var(--reader-heading);
}

.reader-content {
  width: 100%;
}

.reader-section + .reader-section {
  margin-top: 64px;
}

.reader-section h2 {
  margin: 0 0 1em;
  color: var(--reader-text);
  font-size: 2.2em;
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: -0.02em;
  scroll-margin-top: 24px;
}

.reader-section p {
  margin: 0 0 2em;
  color: var(--reader-body);
  font-size: 1.15em;
  line-height: 1.8;
  letter-spacing: 0.002em;
}

.reader-markdown-content {
  color: var(--reader-body);
  line-height: inherit;
  font-size: inherit;
}

.reader-markdown-content > p:first-of-type {
  font-size: 1.25em; /* Slight emphasis on first paragraph */
  line-height: 1.7;
}

.reader-markdown-content p {
  margin-bottom: 2em;
}

.reader-markdown-content ul, .reader-markdown-content ol {
  margin-left: 2rem;
  margin-bottom: 1.6em;
}

.reader-markdown-content li {
  margin-bottom: 0.6em;
  line-height: 1.7;
}

.reader-markdown-content h1, .reader-markdown-content h2, .reader-markdown-content h3 {
  margin-top: 2em;
  margin-bottom: 1em;
  color: var(--reader-heading);
}

.reader-markdown-content blockquote {
  margin: 2em 0;
  padding: 1.2em 2em;
  border-left: 3px solid var(--reader-border);
  background: color-mix(in srgb, var(--reader-bg) 50%, transparent);
  color: var(--reader-muted);
  font-style: italic;
  border-radius: 0 8px 8px 0;
}

.reader-bullet {
  display: flex;
  gap: 10px;
  margin: 0 0 14px;
  color: var(--reader-body);
  font-size: 1em;
  line-height: 1.78;
}

.reader-bullet__mark {
  width: 7px;
  height: 7px;
  margin-top: 0.74em;
  flex: 0 0 7px;
  border-radius: 50%;
  background: var(--reader-accent);
  opacity: 0.8;
}

/* ============================================================
   READER SKELETON & ERROR STATES
============================================================ */

.reader-skeleton {
  min-height: 480px;
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.skeleton-header {
  margin-bottom: 1.5rem;
}

.skeleton-line {
  background: linear-gradient(
    90deg,
    color-mix(in srgb, var(--reader-border) 40%, transparent) 0%,
    color-mix(in srgb, var(--reader-border) 85%, transparent) 50%,
    color-mix(in srgb, var(--reader-border) 40%, transparent) 100%
  );
  background-size: 200% 100%;
  animation: skeletonShimmer 1.8s infinite ease-in-out;
  border-radius: 6px;
}

@keyframes skeletonShimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

.skeleton-meta {
  width: 140px;
  height: 14px;
  margin-bottom: 0.75rem;
}

.skeleton-title {
  width: 65%;
  height: 28px;
  margin-bottom: 1rem;
}

.skeleton-divider {
  width: 100%;
  height: 1px;
  background: var(--reader-border);
  opacity: 0.5;
  margin-top: 1rem;
}

.skeleton-body {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.skeleton-h2 {
  width: 45%;
  height: 22px;
  margin-bottom: 0.5rem;
}

.skeleton-p {
  width: 100%;
  height: 16px;
}

.reader-loading-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1.5rem;
  color: var(--reader-muted);
  font-size: 0.85rem;
}

.reader-loading-label svg {
  width: 18px;
  height: 18px;
  color: var(--reader-accent);
}

.reader-error {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 2rem 1rem;
  color: var(--reader-muted);
}

.reader-error__icon svg {
  width: 44px;
  height: 44px;
  color: #ef4444;
  margin-bottom: 1rem;
}

.reader-error h2 {
  margin: 0;
  color: var(--reader-text);
  font-size: 1.15rem;
  font-weight: 700;
}

.reader-error p {
  max-width: 400px;
  margin: 0.5rem 0 1.25rem;
  font-size: 0.85rem;
  line-height: 1.5;
}

.reader-retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.25rem;
  border-radius: 10px;
  background: var(--reader-accent, #395886);
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(57, 88, 134, 0.25);
  transition: transform 0.15s ease, opacity 0.15s ease;
}

.reader-retry-btn:hover {
  opacity: 0.92;
  transform: translateY(-1px);
}

.reader-empty {
  min-height: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: var(--reader-muted);
}

.reader-empty svg {
  width: 38px;
  height: 38px;
  margin-bottom: 12px;
}

.reader-empty h2 {
  margin: 0;
  color: var(--reader-text);
  font-size: 1rem;
  font-weight: 800;
}

.reader-empty p {
  max-width: 300px;
  margin: 6px 0 0;
  font-size: 0.64rem;
  line-height: 1.55;
}

.reader-footer {
  margin-top: 50px;
  padding-top: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  border-top: 1px solid var(--reader-border);
}

.page-action {
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  width: max-content;
  max-width: 100%;
  padding: 8px 13px;
  border-radius: 10px;
  border: 1px solid transparent;
  font-size: 0.61rem;
  font-weight: 800;
  cursor: pointer;
  text-decoration: none;
  transition: transform 0.18s ease, background 0.18s ease, opacity 0.18s ease, box-shadow 0.18s ease;
}

.page-action:hover:not(:disabled),
.quiz-complete-link:hover {
  transform: translateY(-1px);
}

.page-action:disabled {
  opacity: 0.42;
  cursor: not-allowed;
}

.page-action svg,
.quiz-complete-link svg {
  width: 15px;
  height: 15px;
  flex: 0 0 auto;
}

.page-action--secondary {
  justify-self: start;
  color: var(--reader-text);
  background: var(--reader-surface-2);
}

.page-action--primary {
  justify-self: end;
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  box-shadow: 0 7px 16px color-mix(in srgb, var(--reader-accent) 20%, transparent);
}

.page-action--success {
  justify-self: end;
  color: #fff;
  background: #2f8f63;
  box-shadow: 0 7px 16px rgba(47, 143, 99, 0.18);
}

.quiz-complete-link {
  justify-self: center;
  min-height: 40px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  padding: 8px 13px;
  border: 1px solid rgba(99, 142, 203, 0.24);
  border-radius: 10px;
  color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 9%, var(--reader-surface));
  font-size: 0.6rem;
  font-weight: 800;
  text-decoration: none;
  transition: transform 0.18s ease, background 0.18s ease;
}

.reader-resize {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 14px;
  z-index: 10;
  padding: 0;
  border: 0;
  background: transparent;
  cursor: ew-resize;
}

.reader-resize--left {
  left: -14px;
}

.reader-resize--right {
  right: -14px;
}

.reader-resize span {
  width: 3px;
  height: 44px;
  display: block;
  margin: auto;
  border-radius: 999px;
  background: transparent;
  transition: background 0.16s ease, height 0.16s ease;
}

.reader-resize:hover span {
  height: 72px;
  background: var(--reader-accent);
}

/* ============================================================
   PDF
============================================================ */

.pdf-view {
  position: relative;
  flex: 1;
  min-height: 0;
  overflow: hidden;
  background: var(--reader-bg);
}

.pdf-view__frame {
  width: 100%;
  height: 100%;
  border: 0;
  background: var(--reader-surface);
}

.pdf-empty {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--reader-muted);
  text-align: center;
}

.pdf-empty svg {
  width: 42px;
  height: 42px;
  margin-bottom: 12px;
}

.pdf-empty h2 {
  color: var(--reader-text);
  font-size: 0.95rem;
  font-weight: 800;
}

/* ============================================================
   AI FLOATING ACTION BUTTON — spinning glow + label pulse
============================================================ */

/* Outer wrapper — positions everything relative */
.ai-fab-wrap {
  position: absolute;
  right: 18px;
  bottom: 18px;
  z-index: 20;
  display: flex;
  flex-direction: column;
  align-items: center;
  /* extra bottom space so label doesn't clip */
}

/* Spinning conic-gradient glow ring */
.ai-fab-ring {
  position: absolute;
  inset: -4px;
  border-radius: 999px;
  overflow: hidden;
  pointer-events: none;

  /* Use a CSS mask to punch a hole in the center, so only the border glows */
  padding: 3px; 
  -webkit-mask: 
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
}

.ai-fab-ring::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 200%;
  height: 200%;
  transform: translate(-50%, -50%);
  background: conic-gradient(
    from 0deg,
    transparent 0deg,
    transparent 240deg,
    #4285f4 360deg
  );
  animation: 
    ai-ring-spin 2.5s linear infinite,
    ai-color-cycle 10s linear infinite;
}

@keyframes ai-ring-spin {
  to { transform: translate(-50%, -50%) rotate(360deg); }
}

@keyframes ai-color-cycle {
  0% { filter: hue-rotate(0deg); }
  100% { filter: hue-rotate(360deg); }
}

/* The actual icon button — circle, no text */
.pdf-ai-button {
  position: relative;
  z-index: 1;
  width: 48px;
  height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 999px;
  color: var(--reader-accent-text, #fff);
  background: var(--reader-accent, #3b82f6);
  box-shadow: 0 4px 18px color-mix(in srgb, var(--reader-accent, #3b82f6) 40%, transparent);
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.pdf-ai-button:hover {
  transform: scale(1.08);
  box-shadow: 0 6px 24px color-mix(in srgb, var(--reader-accent, #3b82f6) 55%, transparent);
}

.pdf-ai-button:active {
  transform: scale(0.96);
}

.pdf-ai-button svg {
  width: 20px;
  height: 20px;
}

/* "Smart AI" label pill — floats left of the button */
.ai-fab-label {
  position: absolute;
  right: calc(100% + 14px);
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  white-space: nowrap;
  padding: 5px 12px;
  border-radius: 999px;
  background: var(--reader-brand, #395886);
  color: #fff;
  font-size: 0.7rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  box-shadow: 0 4px 16px rgba(57, 88, 134, 0.45);
  pointer-events: none;
  /* right-pointing arrow */
}

.ai-fab-label::after {
  content: '';
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-left-color: var(--reader-brand, #395886);
}

/* Label enter/leave transition — spring pop-up from button */
.ai-label-enter-active {
  transition: opacity 0.35s ease, transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.ai-label-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.ai-label-enter-from,
.ai-label-leave-to {
  opacity: 0;
  transform: translateY(-50%) translateX(10px) scale(0.9);
}



/* ============================================================
   AI PANEL
============================================================ */



.ai-messages {
  min-height: 0;
  flex: 1;
  overflow: auto;
  padding: 14px 24px;
  scroll-behavior: smooth;
  transition: padding 0.25s ease;
}

/* Chat Full-Screen Adjustments (ChatGPT/Claude style) */
.chat-full-screen {
  background: var(--reader-bg);
  border-left: none;
}

.chat-full-screen .ai-messages > *,
.chat-full-screen .ai-composer,
.chat-full-screen .quick-prompts {
  max-width: 800px;
  margin: 0 auto;
  width: 100%;
}

.chat-full-screen .ai-composer {
  padding: 0 14px 24px 14px;
}

.side-panel--right.chat-is-empty {
  display: flex;
  flex-direction: column;
}

.chat-is-empty .ai-messages {
  flex: 0 0 auto;
  margin-top: auto;
  padding: 0 20px 8px 20px;
  overflow: visible;
}

.chat-is-empty .chat-welcome-screen {
  padding-top: 0 !important;
  margin-bottom: 0;
}

.chat-is-empty .chat-welcome-screen h2 {
  margin-bottom: 24px;
}

.chat-is-empty .ai-composer {
  flex: 0 0 auto;
  margin-bottom: auto;
  margin-top: 0;
  width: 100%;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
  padding: 0 20px 24px 20px;
}

@media (max-width: 1023px) {
  .side-panel--mobile.chat-is-empty .ai-messages {
    flex: 1 1 auto;
    margin-top: 0;
    overflow-y: auto;
  }
  .side-panel--mobile.chat-is-empty .ai-composer {
    margin-bottom: 0;
    margin-top: auto;
    padding: 10px 12px calc(12px + env(safe-area-inset-bottom));
  }
}

/* ============================================================
   MOBILE AI TOPBAR (ChatGPT-style)
============================================================ */

.mobile-ai-topbar {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-bottom: 1px solid var(--reader-border);
  background: var(--reader-surface);
}

.mobile-ai-topbar__left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.mobile-ai-topbar__right {
  display: flex;
  align-items: center;
  gap: 4px;
}

.mobile-ai-model-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 10px;
  border-radius: 20px;
  background: color-mix(in srgb, var(--reader-accent) 10%, transparent);
  color: var(--reader-accent);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  cursor: default;
  user-select: none;
}

.mobile-ai-icon-btn {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--reader-muted);
  cursor: pointer;
  transition: background 0.15s ease, color 0.15s ease;
}

.mobile-ai-icon-btn svg {
  width: 16px;
  height: 16px;
}

.mobile-ai-icon-btn:hover {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

/* ============================================================
   AI EMPTY STATE (Welcome — greeting + composer centered)
============================================================ */

.ai-empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 16px calc(24px + env(safe-area-inset-bottom));
  min-height: 0;
}

.ai-empty-state__center {
  width: 100%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.ai-empty-state__greeting {
  font-size: clamp(1.4rem, 5vw, 2rem);
  font-weight: 700;
  color: var(--reader-text);
  text-align: center;
  line-height: 1.25;
  letter-spacing: -0.02em;
  margin: 0;
}

.ai-empty-state .ai-composer--empty {
  width: 100%;
  padding: 0;
  margin: 0;
  border-top: none;
  background: transparent;
}

.ai-empty-state .ai-composer--empty .ai-composer__field {
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
}

.fade-greeting-enter-active,
.fade-greeting-leave-active {
  transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.fade-greeting-enter-from,
.fade-greeting-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

.ai-welcome {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 12px;
  padding: 16px;
  border: 1px solid var(--reader-border);
  border-radius: 16px;
  background: var(--reader-surface);
}

.ai-welcome__icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 10px;
  border-radius: 12px;
  color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 9%, transparent);
}

.ai-welcome__icon svg {
  width: 21px;
  height: 21px;
}

.ai-welcome__eyebrow {
  color: var(--reader-accent);
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.ai-welcome h3 {
  margin-top: 6px;
  color: var(--reader-text);
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -0.025em;
}

.ai-welcome p {
  margin-top: 5px;
  color: var(--reader-muted);
  font-size: 1rem;
  line-height: 1.6;
}

.message-stack {
  margin-bottom: 12px;
}

.message-stack__assistant {
  width: 100%;
}

.message {
  width: fit-content;
  max-width: 85%;
  padding: 14px 18px;
  border-radius: 16px;
  font-size: 1rem;
  line-height: 1.65;
  word-break: break-word;
}

.message--user {
  margin-left: auto;
  color: var(--reader-text);
  background: var(--reader-surface-2);
  border-radius: 20px;
  margin-bottom: 24px;
}

.message--assistant {
  width: 100%;
  max-width: 100%;
  color: var(--reader-text);
  background: transparent;
  border: none;
  box-shadow: none;
  padding-left: 0;
  padding-right: 0;
}

/* Markdown AI Response Formatting */
:deep(.ai-response-content) {
  font-size: 1rem;
  line-height: 1.65;
  color: var(--reader-text);
  word-wrap: break-word;
}

:deep(.ai-response-content p) {
  margin-bottom: 1em;
}

:deep(.ai-response-content p:last-child) {
  margin-bottom: 0;
}

:deep(.ai-response-content strong),
:deep(.ai-response-content b) {
  font-weight: 700;
  color: var(--reader-text);
}

:deep(.ai-response-content em),
:deep(.ai-response-content i) {
  font-style: italic;
  color: var(--reader-text-body);
}

:deep(.ai-response-content h1),
:deep(.ai-response-content h2),
:deep(.ai-response-content h3),
:deep(.ai-response-content h4) {
  font-weight: 800;
  margin-top: 1.5em;
  margin-bottom: 0.75em;
  color: var(--reader-accent);
  line-height: 1.3;
  letter-spacing: -0.01em;
}

:deep(.ai-response-content h1) {
  font-size: 1.5em;
  padding-bottom: 0.3em;
  border-bottom: 1px solid color-mix(in srgb, var(--reader-accent) 25%, transparent);
}
:deep(.ai-response-content h2) {
  font-size: 1.3em;
  padding-bottom: 0.25em;
  border-bottom: 1px solid color-mix(in srgb, var(--reader-accent) 18%, transparent);
}
:deep(.ai-response-content h3) { font-size: 1.1em; }

:deep(.ai-response-content ul),
:deep(.ai-response-content ol) {
  margin-left: 0.3em;
  margin-bottom: 1em;
  padding-left: 1.2em;
}

:deep(.ai-response-content ul) {
  list-style-type: none;
}

:deep(.ai-response-content ul li) {
  position: relative;
}

:deep(.ai-response-content ul li)::before {
  content: '';
  position: absolute;
  left: -1.1em;
  top: 0.62em;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--reader-accent);
}

:deep(.ai-response-content ol) {
  list-style-type: decimal;
}

:deep(.ai-response-content ol li)::marker {
  color: var(--reader-accent);
  font-weight: 700;
}

:deep(.ai-response-content li) {
  margin-bottom: 0.5em;
  padding-left: 0.2em;
}

:deep(.ai-response-content li) strong {
  color: var(--reader-accent);
}

:deep(.ai-response-content blockquote) {
  border-left: 4px solid var(--reader-accent);
  padding-left: 1em;
  margin-left: 0;
  margin-bottom: 1em;
  color: var(--reader-muted);
  font-style: italic;
  background: color-mix(in srgb, var(--reader-accent) 5%, transparent);
  padding: 0.5em 1em;
  border-radius: 0 8px 8px 0;
}

.assistant-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 8px 2px;
  color: var(--reader-text);
  font-size: 0.85rem;
  font-weight: 700;
}

.assistant-label__icon {
  width: 24px;
  height: 24px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 8%, transparent);
}

.assistant-label__icon svg {
  width: 14px;
  height: 14px;
}

.feedback-row {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid var(--reader-border);
}

.feedback-button {
  min-height: 28px;
  width: 28px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border: none;
  border-radius: 8px;
  color: var(--reader-muted);
  background: transparent;
  cursor: pointer;
  transition: all 0.16s ease;
}

.feedback-button.feedback-source {
  width: auto;
  padding: 4px 8px;
  white-space: nowrap;
}

.feedback-button:hover {
  background: var(--reader-surface-2);
  color: var(--reader-text);
}

.feedback-button.feedback-up:hover,
.feedback-button.is-helpful {
  color: #2f8f63;
  background: rgba(47, 143, 99, 0.15);
}

.feedback-button.feedback-down:hover,
.feedback-button.is-unhelpful {
  color: #c43b3b;
  background: rgba(196, 59, 59, 0.15);
}

.feedback-button.feedback-source:hover {
  color: #3b82f6;
  background: rgba(59, 130, 246, 0.15);
}

.feedback-button svg {
  width: 14px;
  height: 14px;
}

.message-stack__assistant {
  min-height: 44px;
  overflow-wrap: break-word;
  word-break: break-word;
}

.streaming-text {
  display: block;
  width: 100%;
}

.streaming-cursor {
  display: inline-block;
  vertical-align: baseline;
  color: var(--reader-brand, #3b82f6);
  opacity: 0.8;
  animation: blink 0.9s steps(2, start) infinite;
}

@keyframes blink {
  50% {
    opacity: 0.1;
  }
}

.thinking-dots {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 3px 0;
}

.thinking-dots span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--reader-muted);
  animation: thinking 1.1s ease-in-out infinite;
}

.thinking-dots span:nth-child(2) {
  animation-delay: 0.12s;
}

.thinking-dots span:nth-child(3) {
  animation-delay: 0.24s;
}

@keyframes thinking {
  0%, 75%, 100% {
    transform: translateY(0);
    opacity: 0.4;
  }
  35% {
    transform: translateY(-3px);
    opacity: 1;
  }
}

.error-message {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  padding: 11px;
  border: 1px solid rgba(196, 59, 59, 0.20);
  border-radius: 12px;
  color: #a92f2f;
  background: rgba(196, 59, 59, 0.06);
}

.error-message svg {
  width: 16px;
  height: 16px;
  flex: 0 0 auto;
  margin-top: 1px;
}

.error-message strong {
  font-size: 0.95rem;
}

.error-message p {
  margin-top: 4px;
  font-size: 0.85rem;
  opacity: 0.85;
}

.quick-prompts {
  flex: 0 0 auto;
  display: flex;
  gap: 6px;
  padding: 0 12px 8px;
}

.quick-prompts button {
  min-width: 0;
  flex: 1;
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid var(--reader-border);
  border-radius: 12px;
  color: var(--reader-text);
  background: var(--reader-surface);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.16s ease, background 0.16s ease;
}

.quick-prompts button:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
}

.quick-prompts svg {
  width: 14px;
  height: 14px;
  flex: 0 0 auto;
}

.ai-composer {
  flex: 0 0 auto;
  padding: 10px 12px calc(12px + env(safe-area-inset-bottom));
  border-top: none;
  background: transparent;
  transition: padding 0.25s ease, margin 0.25s ease;
}



.ai-composer__field {
  display: flex;
  align-items: flex-end;
  gap: 7px;
  padding: 5px 5px 5px 12px;
  border: 1px solid var(--reader-border);
  border-radius: 24px;
  background: var(--reader-surface);
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.ai-composer__field:focus-within {
  border-color: var(--reader-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--reader-accent) 12%, transparent);
}

.ai-composer__textarea {
  min-width: 0;
  flex: 1;
  height: auto;
  min-height: 24px;
  max-height: 120px;
  border: 0;
  outline: 0;
  color: var(--reader-text);
  background: transparent;
  font-size: 1rem;
  resize: none;
  overflow-y: auto;
  font-family: inherit;
  line-height: 1.5;
  padding-top: 8px;
  padding-bottom: 8px;
  scrollbar-width: thin;
}

.ai-composer__textarea::placeholder {
  color: var(--reader-muted);
}

.send-button {
  width: 36px;
  height: 36px;
  flex: 0 0 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 50%;
  color: white;
  background: #3b82f6;
  cursor: pointer;
  transition: transform 0.18s ease, opacity 0.18s ease, background 0.18s ease;
}

.send-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #2563eb;
}

.send-button:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.send-button svg {
  width: 15px;
  height: 15px;
}



/* ============================================================
   TEXT SELECTION POPUP
============================================================ */

.selection-popup {
  position: fixed;
  z-index: 60;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  min-height: 34px;
  padding: 4px 5px 4px 9px;
  border: 1px solid color-mix(in srgb, var(--reader-border) 40%, transparent);
  border-radius: 10px;
  color: #fff;
  background: var(--reader-text);
  box-shadow: 0 14px 30px rgba(15, 23, 42, 0.18);
  transform: translate(-50%, -100%);
  font-size: 0.49rem;
  font-weight: 800;
}

.selection-popup button {
  min-height: 25px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 7px;
  border: 0;
  border-radius: 7px;
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  font-size: 0.47rem;
  font-weight: 800;
  cursor: pointer;
}

.selection-popup button:hover {
  background: var(--reader-accent-hover);
}

.selection-popup button svg {
  width: 11px;
  height: 11px;
}

.selection-enter-active,
.selection-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.selection-enter-from,
.selection-leave-to {
  opacity: 0;
  transform: translate(-50%, calc(-100% + 4px)) scale(0.97);
}

/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1280px) {
  .reader-header__right .theme-menu-label {
    display: none;
  }
}

@media (max-width: 1024px) {
  .reader-header {
    padding-left: 10px;
    padding-right: 10px;
  }

  .breadcrumb__home,
  .breadcrumb > svg {
    display: none;
  }

  .page-control--desktop {
    display: none;
  }
}

@media (max-width: 1024px) {
  .reader-header__left {
    flex: 1;
  }

  .reader-header__right {
    flex: 0 0 auto;
  }

  .reader-header__right .segmented-control {
    display: none;
  }

  .reader-scroll {
    padding: 0;
  }

  .reader-column {
    width: 100% !important;
  }

  .reader-resize {
    display: none;
  }
}

@media (max-width: 768px) {
  .reader-header {
    min-height: 54px;
  }

  .reader-header__right .assistant-header-button span {
    display: none;
  }

  .assistant-header-button {
    width: 34px;
    padding: 7px;
    justify-content: center;
  }

  .breadcrumb__current {
    max-width: 45vw;
    font-size: 0.55rem;
  }

  .reader-paper {
    border-radius: 0;
  }

  .reader-paper__inner {
    padding: 32px 20px 48px;
  }

  .reader-section h2 {
    font-size: 1.5em;
  }

  .reader-footer {
    grid-template-columns: 1fr auto;
  }

  .quiz-complete-link {
    display: none;
  }

  .page-action {
    min-height: 38px;
  }

  .selection-popup {
    max-width: calc(100vw - 20px);
  }

  .quick-prompts {
    flex-direction: column;
  }
}

@media (max-width: 640px) {
  .reader-header__left .header-icon-button {
    display: none;
  }

  .reader-header__right {
    gap: 4px;
  }

  .breadcrumb__current {
    max-width: 54vw;
  }

  .reader-paper__inner {
    padding: 24px 16px 36px;
  }

  .reader-section + .reader-section {
    margin-top: 30px;
  }

  .reader-footer {
    gap: 6px;
  }

  .page-action {
    padding: 8px 10px;
    font-size: 0.55rem;
  }

  .ai-welcome {
    padding: 13px;
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


@media (max-width: 768px) {
  .reader-header {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: transparent !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    border: none !important;
    padding: 12px !important;
    pointer-events: none;
    align-items: flex-start !important;
  }

  .reader-header__right {
    pointer-events: auto;
  }

  .reader-header__left {
    pointer-events: none;
  }

  .reader-paper {
    padding-top: 100px !important; /* Space so text isn't hidden under floating header initially */
    padding-bottom: 32px !important;
  }

  .side-panel--right.side-panel--mobile {
    width: 100vw !important;
    max-width: 100vw !important;
    left: 0 !important;
    right: 0 !important;
    top: 0 !important;
    bottom: 0 !important;
    height: 100% !important;
    max-height: 100dvh !important;
    z-index: 60 !important;
    background: var(--reader-surface-2) !important;
  }
}

/* ============================================================
   MOBILE HEADER OVERRIDES (FLOATING)
============================================================ */

.mobile-header-left {
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.mobile-header-top-row {
  display: flex;
  align-items: center;
  gap: 12px;
  pointer-events: none;
}

.mobile-brand-row {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  border-radius: 99px;
  padding: 6px 14px 6px 6px;
  pointer-events: auto;
}

.mobile-brand-logo {
  width: 22px;
  height: 22px;
  border-radius: 50%;
}

.mobile-brand-text {
  font-size: 0.85rem;
  font-weight: 800;
  color: var(--rt-text, var(--sa-text));
  letter-spacing: -0.01em;
}

.mobile-chapter-title {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--rt-text, var(--sa-text));
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  border-radius: 99px;
  padding: 8px 14px;
  pointer-events: auto;
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mobile-header-bottom-row {
  display: flex;
  pointer-events: none;
}

.mobile-header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 10px;
  pointer-events: none;
}

.mobile-header-top-row--right {
  display: flex;
  justify-content: flex-end;
  pointer-events: none;
}

.mobile-header-bottom-row--right {
  display: flex;
  justify-content: flex-end;
  pointer-events: none;
}

.mobile-ai-header {
  border-bottom: 1px solid var(--reader-border);
  padding: 12px 16px;
}

.mobile-sidebar-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--rt-muted);
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  border-radius: 12px;
  padding: 8px 12px;
  pointer-events: auto;
  font-size: 0.8rem;
  font-weight: 700;
}

.mobile-sidebar-toggle svg {
  width: 16px;
  height: 16px;
}

/* ============================================================
   MOBILE SETTINGS MENU (DROPDOWN)
============================================================ */

.mobile-settings-wrapper {
  position: relative;
  pointer-events: auto;
}

.mobile-settings-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--rt-text, var(--sa-text));
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  border-radius: 50%;
  width: 36px;
  height: 36px;
  pointer-events: auto;
}

.mobile-settings-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 200px;
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  border-radius: 16px;
  padding: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 999999;
}

.mobile-settings-section {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.page-control--mobile {
  width: 100%;
  justify-content: space-between;
}

.mobile-option-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: transparent;
  border: 1px solid var(--rt-border, var(--sa-border));
  border-radius: 8px;
  color: var(--rt-text, var(--sa-text));
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.mobile-option-btn:hover,
.mobile-option-btn.is-selected {
  background: color-mix(in srgb, var(--rt-accent) 12%, transparent);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.25);
}

.mobile-theme-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

</style>

<style>
/* ============================================================
   AI MARKDOWN
   Unscoped because the content is injected through v-html.
============================================================ */

.ai-response-content {
  color: var(--rt-text-body);
  line-height: 1.7;
}

.ai-response-content h1,
.ai-response-content h2,
.ai-response-content h3,
.ai-response-content h4 {
  margin: 1.15em 0 0.45em !important;
  color: var(--rt-accent, var(--rt-text)) !important;
  line-height: 1.3 !important;
  font-weight: 800 !important;
  letter-spacing: -0.01em !important;
}

.ai-response-content h1:first-child,
.ai-response-content h2:first-child,
.ai-response-content h3:first-child,
.ai-response-content h4:first-child {
  margin-top: 0 !important;
}

.ai-response-content h1 {
  font-size: 1.45em !important;
  padding-bottom: 0.3em !important;
  border-bottom: 1px solid color-mix(in srgb, var(--rt-accent, var(--rt-border)) 25%, transparent) !important;
}
.ai-response-content h2 {
  font-size: 1.27em !important;
  padding-bottom: 0.25em !important;
  border-bottom: 1px solid color-mix(in srgb, var(--rt-accent, var(--rt-border)) 18%, transparent) !important;
}
.ai-response-content h3 { font-size: 1.08em !important; }
.ai-response-content h4 { font-size: 1em !important; }

.ai-response-content p {
  margin: 0.7em 0 !important;
}

.ai-response-content p:first-child {
  margin-top: 0 !important;
}

.ai-response-content strong,
.ai-response-content b {
  color: var(--rt-accent, var(--rt-text)) !important;
  font-weight: 800 !important;
}

.ai-response-content em,
.ai-response-content i {
  font-style: italic !important;
  color: var(--rt-text-body) !important;
}

.ai-response-content ul,
.ai-response-content ol {
  margin: 0.7em 0 !important;
  padding-left: 1.4em !important;
  display: block !important;
}

.ai-response-content ul {
  list-style-type: none !important;
}

.ai-response-content ul li {
  position: relative !important;
}

.ai-response-content ul li::before {
  content: '' !important;
  position: absolute !important;
  left: -1.1em !important;
  top: 0.62em !important;
  width: 6px !important;
  height: 6px !important;
  border-radius: 50% !important;
  background: var(--rt-accent) !important;
}

.ai-response-content ol {
  list-style-type: decimal !important;
}

.ai-response-content ol li::marker {
  color: var(--rt-accent) !important;
  font-weight: 700 !important;
}

.ai-response-content li {
  margin: 0.3em 0 !important;
  display: list-item !important;
}

.ai-response-content li strong {
  color: var(--rt-accent, var(--rt-text)) !important;
}

.ai-response-content li > p {
  margin: 0.2em 0 !important;
  display: inline-block !important;
}

.ai-response-content code {
  padding: 0.15em 0.35em !important;
  border: 1px solid var(--rt-border) !important;
  border-radius: 5px !important;
  color: var(--rt-text) !important;
  background: var(--rt-surface) !important;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
  font-size: 0.9em !important;
}

.ai-response-content pre {
  margin: 1em 0 !important;
  padding: 0.9rem !important;
  overflow-x: auto !important;
  border: 1px solid var(--rt-border) !important;
  border-radius: 8px !important;
  color: var(--rt-text-body) !important;
  background: var(--rt-surface) !important;
}

.ai-response-content pre code {
  padding: 0 !important;
  border: 0 !important;
  background: transparent !important;
}

.ai-response-content a {
  color: var(--rt-accent) !important;
  text-decoration: underline !important;
  text-underline-offset: 2px !important;
}

.ai-response-content blockquote {
  margin: 1em 0 !important;
  padding: 0.6em 1em !important;
  border-left: 3px solid var(--rt-accent) !important;
  color: var(--rt-muted) !important;
  font-style: italic !important;
  background: color-mix(in srgb, var(--rt-accent) 6%, transparent) !important;
  border-radius: 0 8px 8px 0 !important;
}

.ai-response-content blockquote p {
  margin: 0.3em 0 !important;
}

.ai-response-content hr {
  margin: 1.25em 0 !important;
  border: 0 !important;
  border-top: 1px solid var(--rt-border) !important;
}

.ai-response-content table {
  width: 100% !important;
  margin: 1em 0 !important;
  border-collapse: collapse !important;
}

.ai-response-content th,
.ai-response-content td {
  padding: 0.55em 0.65em !important;
  border: 1px solid var(--rt-border) !important;
  text-align: left !important;
  vertical-align: top !important;
}

.ai-response-content th {
  color: var(--rt-text) !important;
  background: var(--rt-surface-2) !important;
  font-weight: 800 !important;
}

/* Scrollbars */
.chat-page ::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.chat-page ::-webkit-scrollbar-track {
  background: transparent;
}

.chat-page ::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: var(--rt-border, #B1C9EF);
}

.chat-page ::-webkit-scrollbar-thumb:hover {
  background: var(--rt-accent, #8AAEE0);
}
.fatal-error-overlay {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  z-index: 9999;
}

.fatal-error-modal {
  background: var(--rt-surface, #fff);
  padding: 2rem;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  color: var(--rt-text, #000);
  box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

.fatal-error-modal pre {
  background: #ffebe9;
  color: #cf222e;
  padding: 1rem;
  border-radius: 6px;
  overflow-x: auto;
  font-size: 0.85rem;
  margin: 1rem 0;
}

.fatal-error-modal .btn {
  margin-right: 1rem;
  padding: 0.5rem 1rem;
  border: 1px solid var(--rt-border, #ccc);
  border-radius: 6px;
  cursor: pointer;
}
</style>
