<template>
  <div v-if="fatalError" class="fatal-error-overlay">
    <div class="fatal-error-modal">
      <h2>Something went wrong</h2>
      <p>We encountered an unexpected error while rendering this page.</p>
      <pre>{{ fatalError }}</pre>
      <button class="btn btn--primary" @click="fatalError = null">Dismiss</button>
      <button class="btn" @click="reloadPage">Reload Page</button>
    </div>
  </div>

  <div
    v-else
    class="chat-page"
    :style="themeVars"
    :class="{
      'is-resizing': isDraggingLeft || isDraggingRight || isDraggingReader,
      'is-fullscreen': isFullscreen,
    }"
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
      class="collapsed-rail collapsed-rail--left"
      aria-label="Chapter controls"
    >
      <button
        class="rail-button"
        type="button"
        title="Open chapters"
        aria-label="Open chapters"
        @click="isSidebarOpen = true"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke-linecap="round" />
          <path d="M9 3v18" stroke-linecap="round" />
        </svg>
      </button>

      <button
        class="rail-button rail-button--accent"
        type="button"
        title="New AI session"
        aria-label="New AI session"
        @click="startNewChatAndOpen"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 4v16M4 12h16" stroke-linecap="round" />
        </svg>
      </button>
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

        <!-- Sidebar Search -->
        <div v-if="isSidebarSearchActive" class="sidebar-search-container">
          <div class="sidebar-search-wrapper">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" stroke-linecap="round" />
            </svg>
            <input 
              type="text" 
              v-model="sidebarSearchQuery" 
              ref="sidebarSearchInputRef"
              class="sidebar-search-input" 
              :placeholder="activeSidebarTab === 'chats' ? 'Search chats...' : 'Search chapters...'"
              @keyup.esc="toggleSidebarSearch"
            />
            <button v-if="sidebarSearchQuery" @click="sidebarSearchQuery = ''" class="search-clear" type="button">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- ChatGPT-style New Chat Button -->
        <button type="button" class="new-chat-button" @click="startNewChat">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          New chat
        </button>

        <button 
          type="button" 
          class="reader-toggle-button" 
          @click="viewMode === 'reading' ? switchToPdf() : viewMode = 'reading'"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ viewMode === 'reading' ? 'Get Full PDF' : 'Back to Reading' }}
        </button>

        <!-- Modern Tab Switcher -->
        <div class="sidebar-tab-switcher">
          <button 
            type="button" 
            class="tab-btn" 
            :class="{ 'is-active': activeSidebarTab === 'chats' }"
            @click="activeSidebarTab = 'chats'; if (!isAiSidebarOpen) { isAiSidebarOpen = true; isReaderOpen = false; }"
          >
            Chats
          </button>
          <button 
            type="button" 
            class="tab-btn" 
            :class="{ 'is-active': activeSidebarTab === 'chapters' }"
            @click="activeSidebarTab = 'chapters'; if (!isReaderOpen) { isReaderOpen = true; isAiSidebarOpen = false; }"
          >
            Chapters
          </button>
        </div>
      </div>

      <div class="side-panel__scroll">
        <!-- CHATS TAB -->
        <div v-if="activeSidebarTab === 'chats'" class="sidebar-tab-content">
          <section class="sidebar-section sidebar-section--sessions">
            <div class="session-list chatgpt-session-list">
              <div
                v-for="session in visibleSessions"
                :key="session.id"
                style="display: flex; align-items: center; gap: 4px; padding-right: 4px;"
              >
                <button
                  type="button"
                  class="session-item"
                  :class="{ 'session-item--active': chatStore.currentSession?.id === session.id }"
                  @click="switchSession(session.id)"
                  style="flex: 1; margin: 0;"
                >
                  <span class="session-item__title">{{ session.title || 'New Conversation' }}</span>
                </button>
                <button 
                  type="button" 
                  @click.stop="deleteChat(session.id)" 
                  title="Delete chat"
                  style="background: transparent; border: none; padding: 6px; cursor: pointer; color: var(--reader-muted); border-radius: 6px; display: flex; align-items: center;" 
                  onmouseover="this.style.color='var(--reader-error, #ef4444)'; this.style.background='var(--reader-surface-2)';" 
                  onmouseout="this.style.color='var(--reader-muted)'; this.style.background='transparent';"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
              </div>
            </div>
            
            <button
              v-if="hiddenChatsCount > 0"
              type="button"
              class="show-more-button"
              @click="showAllChats = !showAllChats"
            >
              {{ showAllChats ? 'Show less' : `Show ${hiddenChatsCount} more` }}
            </button>
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
                    <span>Overview</span>
                  </button>

                  <template v-else>
                    <button
                      v-for="section in (chapter.sections?.data || chapter.sections || [])"
                      :key="section.id"
                      type="button"
                      class="section-link"
                      :class="{
                        'section-link--active':
                          booksStore.currentChapter?.id === chapter.id &&
                          sectionToPageMap.get(section.id) === currentPage - 1,
                      }"
                      @click="jumpToSection(chapter.id, section.id)"
                    >
                      <span class="section-bullet">•</span>
                      <span>{{ section.title }}</span>
                    </button>

                    <RouterLink :to="`/chapters/${chapter.id}/quiz`" class="chapter-quiz-link">
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

      <!-- Sidebar Footer (Profile & Settings) -->
      <div class="sidebar-footer" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
        <RouterLink to="/profile" class="sidebar-profile-link" style="flex: 1; padding-right: 4px;">
          <div class="sidebar-profile-avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="8" r="3.5" />
              <path d="M5 20a7 7 0 0 1 14 0" />
            </svg>
          </div>
          <div class="sidebar-profile-info">
            <span class="sidebar-profile-name">Profile</span>
            <span class="sidebar-profile-plan">Manage Account</span>
          </div>
        </RouterLink>

        <!-- Display Settings Toggle (Moved from header) -->
        <div class="reader-menu" ref="displaySettingsRef">
          <button
            type="button"
            class="header-control-button"
            :aria-expanded="isDisplaySettingsOpen"
            title="Display Settings"
            @click.stop="toggleDisplaySettings"
            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; background: transparent; border: none; cursor: pointer; color: var(--reader-muted); transition: all 0.2s ease;"
            onmouseover="this.style.background='var(--reader-surface-2)'; this.style.color='var(--reader-text)';"
            onmouseout="this.style.background='transparent'; this.style.color='var(--reader-muted)';"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 20px; height: 20px;">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
          </button>

          <Transition name="menu">
            <div v-if="isDisplaySettingsOpen" class="popover-menu popover-menu--display-settings" style="bottom: 100%; top: auto; right: auto; left: 0; margin-bottom: 12px; transform-origin: bottom left; width: 200px; overflow: visible;">
              
              <div class="display-settings-group" style="position: relative;">
                <button 
                  class="display-settings-label" 
                  style="display: flex; justify-content: space-between; align-items: center; width: 100%; border: none; background: transparent; cursor: pointer; padding: 8px 4px;"
                  @click="activeSettingsSubmenu = activeSettingsSubmenu === 'font' ? null : 'font'"
                >
                  Font
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; transform: rotate(-90deg);"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                
                <Transition name="fade">
                  <div v-if="activeSettingsSubmenu === 'font'" class="popover-menu flyout-menu" style="left: 100%; right: auto; top: -10px; margin-left: 8px; width: 220px;">
                    <div style="display: flex; gap: 4px;">
                      <button type="button" :class="{ 'is-selected': readerFont === 'sans' }" @click="setFont('sans')" style="flex:1; justify-content:center;">Sans-serif</button>
                      <button type="button" :class="{ 'is-selected': readerFont === 'serif' }" @click="setFont('serif')" style="flex:1; justify-content:center;">Serif</button>
                    </div>
                  </div>
                </Transition>
              </div>

              <div class="display-settings-group" style="position: relative;">
                <button 
                  class="display-settings-label" 
                  style="display: flex; justify-content: space-between; align-items: center; width: 100%; border: none; background: transparent; cursor: pointer; padding: 8px 4px;"
                  @click="activeSettingsSubmenu = activeSettingsSubmenu === 'appearance' ? null : 'appearance'"
                >
                  Appearance
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; transform: rotate(-90deg);"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                
                <Transition name="fade">
                  <div v-if="activeSettingsSubmenu === 'appearance'" class="popover-menu flyout-menu" style="left: 100%; right: auto; top: -10px; margin-left: 8px; width: 220px;">
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                      <button
                        v-for="(item, key) in THEMES"
                        :key="key"
                        type="button"
                        :class="{ 'is-selected': readerTheme === key }"
                        @click="setTheme(key)"
                      >
                        <span class="theme-swatch" :style="{ background: item.vars['--rt-surface'] }"></span>
                        {{ item.label }}
                        <svg v-if="readerTheme === key" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left: auto;">
                          <path d="m5 12 4 4L19 6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </Transition>
              </div>

              <!-- New items: Clear Chat History & Sign Out -->
              <hr style="margin: 8px 0; border: 0; border-top: 1px solid var(--reader-border);" />
              
              <button 
                class="display-settings-label" 
                style="display: flex; justify-content: flex-start; align-items: center; gap: 8px; width: 100%; border: none; background: transparent; cursor: pointer; padding: 8px 4px; color: var(--reader-error, #ef4444);"
                @click="clearChatHistory"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                Clear Chat History
              </button>
              
              <button 
                class="display-settings-label" 
                style="display: flex; justify-content: flex-start; align-items: center; gap: 8px; width: 100%; border: none; background: transparent; cursor: pointer; padding: 8px 4px;"
                @click="signOut"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px;"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"></path></svg>
                Sign Out
              </button>

            </div>
          </Transition>
        </div>
      </div>

    </aside>

    <!-- =========================================================
         WORKSPACE (Main Area + Right Sidebar)
    ========================================================== -->
    <div class="workspace" style="flex: 1; display: flex; flex-direction: column; min-width: 0;">
      
      <div v-if="viewMode === 'reading'" class="reading-progress-track">
        <div class="reading-progress-value" :style="{ width: `${readingProgress}%` }"></div>
      </div>

      <header class="reader-header">
        <div class="reader-header__left">
          <template v-if="!isMobile">
            <button
              type="button"
              class="btn-read-with-ai"
              :class="{ 'is-active': isReaderOpen && isAiSidebarOpen }"
              @click="toggleSplitScreen"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 14px; height: 14px; color: #10b981;">
                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
              </svg>
              Read with AI
            </button>
          </template>
          
          <template v-else>
            <div class="mobile-header-left">
              <div class="mobile-header-top-row">
                <RouterLink to="/dashboard" class="mobile-brand-row">
                  <img src="/logo.png" alt="Smart Adama" class="mobile-brand-logo" />
                  <span class="mobile-brand-text">Smart Adama</span>
                </RouterLink>
                <span class="mobile-chapter-title">
                  {{ booksStore.currentChapter?.title || 'Select a chapter' }}
                </span>
              </div>
              <div class="mobile-header-bottom-row">
                <button type="button" class="mobile-sidebar-toggle" @click="isSidebarOpen = true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="m11 17 5-5-5-5" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="m6 17 5-5-5-5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
              </div>
            </div>
          </template>
        </div>

        <div class="reader-header__right">
          <template v-if="isMobile">
            <!-- Mobile Hamburger Settings (Right Top) -->
            <div class="mobile-settings-wrapper" ref="mobileSettingsMenuRef">
              <button 
                type="button" 
                class="mobile-settings-toggle" 
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
                            @keyup.enter="jumpToPage"
                            @blur="jumpToPage"
                          />
                          <span>/ {{ totalPages }}</span>
                        </label>
                        <button type="button" :disabled="currentPage >= totalPages" aria-label="Next page" @click="nextPage">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                            <path d="m9 5 7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </button>
                      </div>
                    </div>

                    <!-- Font Menu -->
                    <div class="mobile-settings-section" style="display: flex; gap: 8px;">
                      <button 
                        type="button" 
                        class="mobile-option-btn" 
                        :class="{ 'is-selected': readerFont === 'sans' }" 
                        @click="setFont('sans')"
                      >
                        Sans-serif
                      </button>
                      <button 
                        type="button" 
                        class="mobile-option-btn" 
                        :class="{ 'is-selected': readerFont === 'serif' }" 
                        @click="setFont('serif')"
                      >
                        Serif
                      </button>
                    </div>

                    <!-- Theme Menu -->
                    <div class="mobile-settings-section mobile-theme-grid">
                      <button
                        v-for="(item, key) in THEMES"
                        :key="key"
                        type="button"
                        class="mobile-option-btn"
                        :class="{ 'is-selected': readerTheme === key }"
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
          </template>

          <template v-else>
            <!-- segmented control removed -->

          </template>
        </div>
      </header>

      <div class="workspace-content" style="flex: 1; display: flex; min-height: 0;">
        <!-- =========================================================
             MAIN READER
        ========================================================== -->
        <main
          v-show="isReaderOpen"
          class="reader-main"
          style="flex: 1; min-width: 0;"
          :style="{ borderRight: isReaderOpen && isAiSidebarOpen && !isMobile ? '1px solid var(--reader-border)' : 'none' }"
        >

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
            width: isMobile ? '100%' : `${readerWidth}px`,
            minWidth: isMobile ? undefined : '320px',
            maxWidth: '100%',
          }"
        >


          <article
            class="reader-paper"
            :class="readerFont === 'serif' ? 'reader-paper--serif' : 'reader-paper--sans'"
            :style="{
              fontSize: `${readerScale}%`,
              minHeight: isMobile ? 'calc(100vh - 7rem)' : '800px',
            }"
          >
            <div class="reader-paper__inner">
              <template v-if="booksStore.currentChapter?.title === 'Introduction & Preface'">
                <IntroductionPreface
                  :current-page="currentPage"
                  @go-to-chapter="handleTocClick"
                />
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
                  <section
                    v-for="section in currentPageData.sections"
                    :key="section.id"
                    :id="`sec-${section.id}`"
                    class="reader-section"
                  >
                    <h2>{{ section.title }}</h2>

                    <template v-for="(block, blockIndex) in formatContent(section.raw_text)" :key="blockIndex">
                      <p v-if="block.type === 'p'">{{ block.text }}</p>

                      <div v-else class="reader-bullet">
                        <span class="reader-bullet__mark"></span>
                        <span>
                          <strong v-if="block.label">{{ block.label }}: </strong>
                          {{ block.text }}
                        </span>
                      </div>
                    </template>
                  </section>
                </div>

                <div v-else class="reader-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <path d="M5 4h14v16H5V4ZM9 8h6M9 12h6M9 16h4" />
                  </svg>
                  <h2>No readable content</h2>
                  <p>Select another chapter or open the original PDF.</p>
                </div>
              </template>

              <footer v-if="currentPageData || booksStore.currentChapter?.title === 'Introduction & Preface'" class="reader-footer">
                <button
                  type="button"
                  class="page-action page-action--secondary"
                  :disabled="currentPage <= 1"
                  @click="prevPage"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="m15 19-7-7 7-7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                  <span>{{ $t('chapter.prev') }}</span>
                </button>

                <RouterLink
                  v-if="currentPage >= totalPages && booksStore.currentChapter?.title !== 'Introduction & Preface' && booksStore.currentChapter?.id"
                  :to="`/chapters/${booksStore.currentChapter.id}/quiz`"
                  class="quiz-complete-link"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m9 12 2 2 4-4M7.8 4.7a3.4 3.4 0 0 0 1.9-.8 3.4 3.4 0 0 1 4.4 0 3.4 3.4 0 0 0 1.9.8 3.4 3.4 0 0 1 3.1 3.1 3.4 3.4 0 0 0 .8 1.9 3.4 3.4 0 0 1 0 4.4 3.4 3.4 0 0 0-.8 1.9 3.4 3.4 0 0 1-3.1 3.1 3.4 3.4 0 0 0-1.9.8 3.4 3.4 0 0 1-4.4 0 3.4 3.4 0 0 0-1.9-.8 3.4 3.4 0 0 1-3.1-3.1 3.4 3.4 0 0 0-.8-1.9 3.4 3.4 0 0 1 0-4.4 3.4 3.4 0 0 0 .8-1.9 3.4 3.4 0 0 1 3.1-3.1Z" />
                  </svg>
                  {{ $t('chapter.take_quiz') }}
                </RouterLink>

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

                <button
                  v-else
                  type="button"
                  class="page-action page-action--success"
                  @click="markCompleteAndNextChapter"
                >
                  <span>Finish</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="m5 12 4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </button>
              </footer>
            </div>
          </article>


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

        <button type="button" class="pdf-ai-button" @click="toggleAiSidebar(true)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M13 10V3L4 14h7v7l9-11h-7Z" stroke-linejoin="round" />
          </svg>
          {{ $t('chapter.ask_ai') }}
        </button>
      </div>
    </main>

    <!-- =========================================================
         RIGHT AI SIDEBAR
    ========================================================== -->
    <aside
      v-show="isAiSidebarOpen && !isFullscreen"
      class="side-panel side-panel--right"
      :style="isMobile ? undefined : { flex: isReaderOpen ? '0 0 auto' : 1, width: isReaderOpen ? '400px' : 'auto' }"
      :class="{ 'side-panel--mobile': isMobile, 'chat-full-screen': !isReaderOpen && !isMobile, 'chat-is-empty': currentMessages.length === 0 }"
      aria-label="Smart Adama AI Assistant"
    >

      <div ref="messagesContainerRef" class="ai-messages">
        <div v-if="currentMessages.length === 0" class="ai-welcome">
          <h3>What can I help with?</h3>
        </div>

        <div v-for="msg in currentMessages" :key="msg.id" class="message-stack">
          <div v-if="msg.role === 'user'" class="message message--user">
            {{ msg.content }}
          </div>

          <div v-else class="message-stack__assistant">
            <div class="assistant-label">
              <span class="assistant-label__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                  <circle cx="12" cy="12" r="3" />
                  <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
                </svg>
              </span>
              <span>Smart Adama</span>
            </div>

            <article class="message message--assistant">
              <div v-html="renderMarkdown(msg.content)" class="ai-response-content"></div>

              <div class="feedback-row">
                <button
                  type="button"
                  class="feedback-button"
                  :class="{ 'is-helpful': msg.feedback === 'helpful' }"
                  @click="toggleFeedback(msg, 'helpful')"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M14 10h4.7a2 2 0 0 1 1.8 2.9l-3.5 7A2 2 0 0 1 15.2 21h-4a2 2 0 0 1-.5-.1L7 20m7-10V5a2 2 0 0 0-2-2h-.1a.9.9 0 0 0-.9.9 3.8 3.8 0 0 1-.6 2L7 11v9M7 20H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h2" />
                  </svg>
                  {{ msg.feedback === 'helpful' ? 'Helpful' : 'Helpful?' }}
                </button>

                <button
                  type="button"
                  class="feedback-button"
                  :class="{ 'is-unhelpful': msg.feedback === 'not_helpful' }"
                  @click="toggleFeedback(msg, 'not_helpful')"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M10 14H5.3a2 2 0 0 1-1.8-2.9l3.5-7A2 2 0 0 1 8.8 3h4a2 2 0 0 1 .5.1L17 4m-7 10v5a2 2 0 0 0 2 2h.1a.9.9 0 0 0 .9-.9 3.8 3.8 0 0 1 .6-2L17 13V4m0 0h2a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-2" />
                  </svg>
                  {{ msg.feedback === 'not_helpful' ? 'Not helpful' : 'Not helpful?' }}
                </button>
              </div>
            </article>
          </div>
        </div>

        <div v-if="chatStore.streaming" class="message-stack__assistant">
          <div class="assistant-label">
            <span class="assistant-label__icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <circle cx="12" cy="12" r="3" />
                <path d="M12 3v4M12 17v4M3 12h4M17 12h4" />
              </svg>
            </span>
            <span>Smart Adama</span>
          </div>

          <article class="message message--assistant">
            <template v-if="chatStore.streamingContent">
              <div class="streaming-text">
                <span v-html="renderMarkdown(chatStore.streamingContent)"></span>
                <span class="streaming-cursor">▋</span>
              </div>
            </template>

            <template v-else>
              <div style="display: flex; align-items: center; gap: 12px;">
                <div class="thinking-dots" aria-label="Thinking" style="margin: 0;">
                  <span></span><span></span><span></span>
                </div>
                <span v-if="chatStore.activityPayload" style="color: var(--color-text-muted); font-size: 0.9em; font-style: italic; opacity: 0.8;">
                  {{ chatStore.activityPayload.message }}
                </span>
              </div>
            </template>
          </article>
        </div>

        <div v-if="chatStore.streamError && !chatStore.streaming" class="message-stack__assistant">
          <article class="error-message">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path d="M12 9v3m0 4h.01M10.3 4.5 2.8 17.5A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.5L13.7 4.5a2 2 0 0 0-3.4 0Z" />
            </svg>
            <div>
              <strong>AI is temporarily unavailable</strong>
              <p>Please try again in a moment.</p>
            </div>
          </article>
        </div>
      </div>



      <form class="ai-composer" @submit.prevent="sendMessage">
        <div class="ai-composer__field">
          <input
            ref="chatInputRef"
            v-model="chatInput"
            :disabled="chatStore.streaming"
            type="text"
            autocomplete="off"
            :placeholder="$t('chapter.ask_placeholder')"
          />

          <button
            v-if="!chatStore.streaming"
            type="submit"
            class="send-button"
            :disabled="!chatInput.trim()"
            aria-label="Send message"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
              <path d="m4 5 16 7-16 7 2.3-7L4 5Z" stroke-linejoin="round" />
              <path d="M6.3 12H20" stroke-linecap="round" />
            </svg>
          </button>

          <button
            v-else
            type="button"
            class="send-button stop-button"
            aria-label="Stop generating"
            @click.prevent="chatStore.cancelStream()"
          >
            <svg viewBox="0 0 24 24" fill="currentColor">
              <rect x="7" y="7" width="10" height="10" rx="1.5" />
            </svg>
          </button>
        </div>
      </form>
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
        <span>Ask Smart AI</span>
        <button type="button" @click="askAiAboutSelection">
          Ask
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M5 12h14m-6-6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
      </div>
    </Transition>

    <!-- =========================================================
         MOBILE BOTTOM NAV
    ========================================================== -->
    <nav v-if="isMobile && !isFullscreen" class="mobile-bottom-nav">
      <RouterLink to="/dashboard" class="mobile-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
          <polyline points="9 22 9 12 15 12 15 22" />
        </svg>
        <span>Home</span>
      </RouterLink>
      
      <div class="mobile-nav-item is-active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20" />
        </svg>
        <span>Study</span>
      </div>

      <button type="button" class="mobile-nav-item" @click="isAiSidebarOpen = true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M13 10V3L4 14h7v7l9-11h-7Z" stroke-linejoin="round" />
        </svg>
        <span>AI</span>
      </button>

      <RouterLink to="/profile" class="mobile-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="8" r="4" />
          <path d="M4 20c0-4 4-7 8-7s8 3 8 7" />
        </svg>
        <span>Profile</span>
      </RouterLink>
    </nav>
  </div>
</template>

<script setup lang="ts">
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
import apiClient from '@/api/client'
import IntroductionPreface from '@/components/IntroductionPreface.vue'
import { useI18n } from 'vue-i18n'

const chatStore = useChatStore()
const booksStore = useBooksStore()
const progressStore = useProgressStore()
const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { t } = useI18n()

async function signOut() {
  await authStore.logout()
  router.push('/login')
}

async function clearChatHistory() {
  if (confirm('Are you sure you want to delete all chat history?')) {
    try {
      for (const session of chatStore.sessions) {
        await chatStore.deleteSession(session.id)
      }
      await chatStore.fetchSessions()
      startNewChat()
    } catch (e) {
      console.error('Failed to clear history:', e)
    }
  }
}

async function deleteChat(sessionId: string) {
  if (confirm('Are you sure you want to delete this chat?')) {
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

onErrorCaptured((err: unknown) => {
  console.error('ChatView Error Boundary Caught:', err)
  fatalError.value = err instanceof Error ? err.message : String(err)
  return false // Prevent the error from propagating further and unmounting the tree
})

/* ============================================================
   RESPONSIVE LAYOUT STATE
============================================================ */

const isSidebarOpen = ref(typeof window !== 'undefined' ? window.innerWidth > 768 : true)
const isAiSidebarOpen = ref(true)
const isReaderOpen = ref(false)
const sidebarWidth = ref(300)
const activeSidebarTab = ref<'chats' | 'chapters'>('chats')
const isDraggingLeft = ref(false)


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
    aiSidebarWidth.value = clamp(aiSidebarWidth.value, 300, Math.min(560, Math.floor(window.innerWidth * 0.42)))
  }
}

const closeMobilePanels = () => {
  if (!isMobile.value) return
  isSidebarOpen.value = false
  isAiSidebarOpen.value = false
}

const toggleAiSidebar = (open?: boolean) => {
  isAiSidebarOpen.value = typeof open === 'boolean' ? open : !isAiSidebarOpen.value
  if (isMobile.value && isAiSidebarOpen.value) {
    isSidebarOpen.value = false
  }
}

/* ============================================================
   READER STATE
============================================================ */

const isFullscreen = ref(false)
const readerScale = ref(100)
const currentPage = ref(1)
const jumpPageInput = ref('1')
const viewMode = ref<'reading' | 'pdf'>('reading')

const readerWidth = ref(1000)
const isDraggingReader = ref(false)
const readerContainerRef = ref<HTMLElement | null>(null)
const scrollAreaRef = ref<HTMLElement | null>(null)
let readerDragCenter = 0

const LOCAL_BOOK_FALLBACK_URL = '/books/SA-Book.pdf'

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
    return chapters.reduce((total: number, chapter: any) => {
      const sections = chapter.sections?.data || chapter.sections || []
      return total + sections.reduce(
        (sum: number, section: any) => sum + (section.raw_text?.length || 0),
        0,
      )
    }, 0)
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

const visibleSessions = computed(() => {
  const sessions = chatStore.sessions || []
  const query = sidebarSearchQuery.value.trim().toLowerCase()
  const filtered = query
    ? sessions.filter(s => (s.title || 'New Conversation').toLowerCase().includes(query))
    : sessions
  return showAllChats.value || query ? filtered : filtered.slice(0, 6)
})

const hiddenChatsCount = computed(() =>
  Math.max(0, (chatStore.sessions || []).length - 6),
)

/* ============================================================
   PAGINATION / CONTENT
============================================================ */

const currentChapterPages = computed(() =>
  booksStore.currentChapter?.sections?.data ||
  booksStore.currentChapter?.sections ||
  [],
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
  if (page < totalPages.value) return

  try {
    if (typeof booksStore.markChapterRead === 'function') {
      await booksStore.markChapterRead(chapterId)
    } else {
      try {
        await apiClient.post(`/chapters/${chapterId}/read`)
      } catch {
        await apiClient.post(`/progress/chapters/${chapterId}`)
      }
    }

    await progressStore.loadAll()
  } catch (error) {
    console.warn('Chapter completion sync failed:', error)
  }
}
const renderMarkdown = (text: string | undefined): string => {
  if (!text) return ''
  const parsed = marked.parse(text)
  return DOMPurify.sanitize(parsed as string)
}

watch(
  () => booksStore.currentChapter?.id,
  async (newId) => {
    if (!newId) return

    localStorage.setItem(LAST_CHAPTER_KEY, newId)

    const savedPage = getReadPosition(newId)
    currentPage.value = savedPage ?? 1
    jumpPageInput.value = String(currentPage.value)

    await nextTick()
    await checkChapterCompletion(newId, currentPage.value)

    const savedSessionId = localStorage.getItem(`smart_adama_chat_session_${newId}`)
    if (savedSessionId && route.params.sessionId !== savedSessionId) {
      try {
        await chatStore.loadSession(savedSessionId)
        router.replace({ name: 'study-session', params: { sessionId: savedSessionId } })
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
    const regex = new RegExp(`^chapter\\s+${chapterNumber}\\b`, 'i')
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
  viewMode.value = 'pdf'
  if (!isFullscreen.value) {
    toggleFullscreen()
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
      '--rt-bg': '#0B0D10',
      '--rt-surface': '#1E2128',
      '--rt-surface-2': '#15171B',
      '--rt-border': '#2C303A',
      '--rt-text': '#EDEFF3',
      '--rt-text-body': '#D8DEE9',
      '--rt-muted': '#8890A0',
      '--rt-accent': '#5B7FDB',
      '--rt-accent-hover': '#7093EE',
      '--rt-accent-text': '#0B0D10',
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

const isDisplaySettingsOpen = ref(false)
const isMobileSettingsOpen = ref(false)
const activeSettingsSubmenu = ref<string | null>(null)
const displaySettingsRef = ref<HTMLElement | null>(null)
const mobileSettingsMenuRef = ref<HTMLElement | null>(null)

const setTheme = (key: any) => {
  readerTheme.value = key as ThemeKey
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
}

/* ============================================================
   KEYBOARD / SELECTION
============================================================ */

const chatInputRef = ref<HTMLInputElement | null>(null)

const handleKeydown = (event: KeyboardEvent) => {
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
  } else if (event.key === 'Escape') {
    if (isFullscreen.value) toggleFullscreen()
    if (isMobile.value) closeMobilePanels()
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

  chatInput.value = `Can you explain this excerpt from chapter page ${currentPage.value}: "${snippet}"`
  toggleAiSidebar(true)

  nextTick(() => {
    void sendMessage()
  })
}

/* ============================================================
   CHAT STATE / ACTIONS
============================================================ */

const chatInput = ref('')
const messagesContainerRef = ref<HTMLElement | null>(null)
const currentMessages = computed(() => chatStore.currentSession?.messages || [])

const scrollChatToBottom = () => {
  nextTick(() => {
    const container = messagesContainerRef.value
    if (!container) return
    container.scrollTo({
      top: container.scrollHeight,
      behavior: 'smooth',
    })
  })
}

watch(currentMessages, scrollChatToBottom, { deep: true })
watch(() => chatStore.streamingContent, scrollChatToBottom)
watch(() => chatStore.streaming, scrollChatToBottom)

const sendPrompt = (prompt: string) => {
  chatInput.value = prompt
  void sendMessage()
}

const reloadPage = () => {
  window.location.reload()
}

const startNewChat = async () => {
  await chatStore.createSession()
  chatInput.value = ''

  if (chatStore.currentSession) {
    if (booksStore.currentChapter?.id) {
      localStorage.setItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`, chatStore.currentSession.id)
    }
    
    router.push({
      name: 'study-session',
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
  isReaderOpen.value = false
}

const switchSession = async (sessionId: string) => {
  await chatStore.loadSession(sessionId)

  if (booksStore.currentChapter?.id) {
    localStorage.setItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`, sessionId)
  }

  router.push({
    name: 'study-session',
    params: { sessionId },
  })

  isAiSidebarOpen.value = true
  isReaderOpen.value = false

  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

const loadBookChapter = async (chapterId: string) => {
  await booksStore.loadChapter(chapterId)
  currentPage.value = 1
  jumpPageInput.value = '1'

  isReaderOpen.value = true
  isAiSidebarOpen.value = false

  if (isMobile.value) {
    isSidebarOpen.value = false
  }
}

const toggleFeedback = async (
  message: any,
  feedbackType: 'helpful' | 'not_helpful',
) => {
  const newFeedback =
    message.feedback === feedbackType
      ? null
      : feedbackType

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
    // Keep the backend integration optional until the feedback endpoint exists.
    // await apiClient.post(`/chat/messages/${message.id}/feedback`, { feedback: newFeedback })
  } catch (error) {
    console.error('Failed to save feedback:', error)
  }
}

const isSubmitting = ref(false)

const sendMessage = async () => {
  const text = chatInput.value.trim()
  if (!text || chatStore.streaming || isSubmitting.value) return

  isSubmitting.value = true

  try {
    if (!chatStore.currentSession) {
      await startNewChat()
    }

    if (!chatStore.currentSession) return

    chatInput.value = ''

    await chatStore.sendMessage(
      chatStore.currentSession.id,
      text,
    )
  } finally {
    isSubmitting.value = false
  }
}

/* ============================================================
   LIFECYCLE
============================================================ */

onMounted(async () => {
  window.addEventListener('resize', handleResize)
  document.addEventListener('mouseup', handleTextSelection)
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('click', handleClickOutsideMenus)

  await Promise.all([
    chatStore.loadSessions(1),
    booksStore.loadBooks(),
  ])

  const chapters = allSortedChapters.value
  if (chapters.length) {
    const savedChapterId = localStorage.getItem(LAST_CHAPTER_KEY)
    const target =
      chapters.find((chapter: any) => chapter.id === savedChapterId) ||
      chapters[0]

    await booksStore.loadChapter(target.id)
  }

  const sessionId = route.params.sessionId as string | undefined
  if (sessionId) {
    await chatStore.loadSession(sessionId)
    if (booksStore.currentChapter?.id) {
      localStorage.setItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`, sessionId)
    }
  } else if (booksStore.currentChapter?.id) {
    const savedId = localStorage.getItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`)
    if (savedId) {
      try {
        await chatStore.loadSession(savedId)
        router.replace({ name: 'study-session', params: { sessionId: savedId } })
      } catch (e) {
        localStorage.removeItem(`smart_adama_chat_session_${booksStore.currentChapter.id}`)
      }
    }
  }

  // Open the current chapter in the sidebar when it is available.
  if (booksStore.currentChapter?.id) {
    expandedChapters.value[booksStore.currentChapter.id] = true
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
  document.removeEventListener('mousemove', onDragLeft)
  document.removeEventListener('mouseup', handleTextSelection)
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('click', handleClickOutsideMenus)

  if (overscrollResetTimer) {
    clearTimeout(overscrollResetTimer)
  }

  document.body.style.cursor = ''
})
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
  width: 58px;
  flex: 0 0 58px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  padding: 12px 8px;
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
  width: 38px;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--reader-border);
  border-radius: 11px;
  color: var(--reader-text);
  background: var(--reader-surface);
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
}

.rail-button:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
  box-shadow: 0 7px 18px rgba(15, 23, 42, 0.08);
}

.rail-button--accent {
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  border-color: transparent;
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
}

.side-panel--right.side-panel--mobile {
  right: 0;
}

.side-panel__header {
  flex: 0 0 auto;
  padding: 12px;
  border-bottom: 1px solid var(--reader-border);
}

.side-panel__brand-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.brand-button {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 0;
  color: var(--reader-text);
  background: transparent;
  font-size: 0.86rem;
  font-weight: 800;
  cursor: pointer;
}

.brand-button img {
  width: 28px;
  height: 28px;
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

/* Sidebar Search */
.sidebar-search-container {
  margin-top: 12px;
  margin-bottom: 4px;
}

.sidebar-search-wrapper {
  display: flex;
  align-items: center;
  background: var(--rt-input-bg, #FFFFFF);
  border: 1px solid var(--rt-border, #E2E8F0);
  border-radius: 8px;
  padding: 6px 10px;
  gap: 8px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

html.dark .sidebar-search-wrapper {
  background: var(--rt-input-bg, #0B1220);
  border-color: var(--rt-border, #334155);
}

.sidebar-search-wrapper:focus-within {
  border-color: #395886;
  box-shadow: 0 0 0 2px rgba(57, 88, 134, 0.15);
}

html.dark .sidebar-search-wrapper:focus-within {
  border-color: #638ECB;
  box-shadow: 0 0 0 2px rgba(99, 142, 203, 0.15);
}

.search-icon {
  width: 14px;
  height: 14px;
  color: var(--rt-text-muted, #94A3B8);
  flex-shrink: 0;
}

.sidebar-search-input {
  flex: 1;
  min-width: 0;
  border: none;
  background: transparent !important;
  color: var(--rt-text, #0F172A);
  font-size: 0.85rem;
  padding: 0;
  outline: none;
}

html.dark .sidebar-search-input {
  color: var(--rt-text, #F8FAFC);
}

.sidebar-search-input::placeholder {
  color: var(--rt-text-muted, #94A3B8);
}

.search-clear {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: none;
  background: var(--rt-hover, #F1F5F9);
  color: var(--rt-text-secondary, #64748B);
  cursor: pointer;
  padding: 0;
  flex-shrink: 0;
  transition: background 0.15s ease, color 0.15s ease;
}

html.dark .search-clear {
  background: var(--rt-hover, #1E293B);
  color: var(--rt-text-secondary, #94A3B8);
}

.search-clear:hover {
  background: #E2E8F0;
  color: #0F172A;
}

html.dark .search-clear:hover {
  background: #334155;
  color: #F8FAFC;
}

.search-clear svg {
  width: 12px;
  height: 12px;
}

.new-chat-button {
  width: 100%;
  margin-top: 10px;
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  padding: 8px 14px;
  border: 1px solid var(--reader-border);
  border-radius: 12px;
  color: var(--reader-text);
  background: var(--reader-bg);
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.new-chat-button:hover {
  background: var(--reader-surface-2);
}

.new-chat-button svg {
  width: 16px;
  height: 16px;
  opacity: 0.8;
}

.reader-toggle-button {
  width: 100%;
  margin-top: 8px;
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  padding: 8px 14px;
  border: 1px solid transparent;
  border-radius: 12px;
  color: var(--reader-muted);
  background: transparent;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reader-toggle-button:hover {
  background: var(--reader-surface);
  color: var(--reader-text);
}

.reader-toggle-button svg {
  width: 16px;
  height: 16px;
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

/* Sidebar Footer (Profile) */
.sidebar-footer {
  padding: 12px;
  border-top: 1px solid var(--reader-border);
  position: relative;
  z-index: 9999;
}

.sidebar-profile-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px;
  border-radius: 8px;
  text-decoration: none;
  transition: background 0.15s ease;
}
.sidebar-profile-link:hover {
  background: var(--reader-surface);
}

.sidebar-profile-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--reader-surface-2);
  color: var(--reader-text);
}
.sidebar-profile-avatar svg {
  width: 18px;
  height: 18px;
}

.sidebar-profile-info {
  display: flex;
  flex-direction: column;
}
.sidebar-profile-name {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--reader-text);
}
.sidebar-profile-plan {
  font-size: 0.65rem;
  color: var(--reader-muted);
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
  padding-top: 15px;
  border-top: 1px solid var(--reader-border);
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

.session-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
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
.session-item--active {
  border-color: var(--reader-border);
  background: var(--reader-surface);
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
  padding: 6px 12px;
  border-radius: 9999px;
  font-weight: 500;
  font-size: 0.75rem;
  color: var(--reader-text);
  background: transparent;
  border: 1px solid var(--reader-border);
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
}

.btn-read-with-ai:hover {
  background: var(--reader-surface-2);
}

.btn-read-with-ai.is-active {
  color: #10b981;
  background: color-mix(in srgb, #10b981 12%, transparent);
  border-color: color-mix(in srgb, #10b981 20%, transparent);
}

.btn-read-with-ai.is-active:hover {
  background: color-mix(in srgb, #10b981 16%, transparent);
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

.popover-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 7px);
  z-index: 9999;
  width: 150px;
  padding: 5px;
  border: 1px solid var(--reader-border);
  border-radius: 11px;
  background: var(--reader-surface);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
  overflow: hidden;
}

.popover-menu--theme {
  width: 160px;
}

.popover-menu--display-settings {
  width: 260px;
  padding: 12px;
}

.display-settings-group {
  margin-bottom: 16px;
}

.display-settings-group:last-child {
  margin-bottom: 0;
}

.display-settings-label {
  font-size: 0.6rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--reader-muted);
  margin-bottom: 8px;
}

.popover-menu button {
  width: 100%;
  min-height: 33px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 8px;
  border: 0;
  border-radius: 7px;
  color: var(--reader-text);
  background: transparent;
  font-size: 0.52rem;
  font-weight: 700;
  text-align: left;
  cursor: pointer;
}

.popover-menu button:hover,
.popover-menu button.is-selected {
  background: var(--reader-bg);
}

.popover-menu button svg {
  width: 12px;
  height: 12px;
  margin-left: auto;
  color: var(--reader-accent);
}

.menu-enter-active,
.menu-leave-active {
  transition: opacity 0.16s ease, transform 0.16s ease;
}

.menu-enter-from,
.menu-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
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
  padding: 28px 20px 44px;
  scroll-behavior: smooth;
  overscroll-behavior: contain;
}

.reader-column {
  position: relative;
  margin: 0 auto;
}

.reader-paper {
  width: 100%;
  color: var(--reader-body);
  background: var(--reader-surface);
  border: 1px solid color-mix(in srgb, var(--reader-border) 62%, transparent);
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05), 0 20px 48px rgba(15, 23, 42, 0.06);
  transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
}

.reader-paper--serif,
.reader-paper--serif * {
  font-family: Georgia, Cambria, "Times New Roman", serif;
}

.reader-paper--sans,
.reader-paper--sans * {
  font-family: var(--font-body, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif);
}

.reader-paper__inner {
  min-height: inherit;
  display: flex;
  flex-direction: column;
  padding: 52px clamp(24px, 5vw, 68px) 42px;
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

.reader-content {
  width: 100%;
}

.reader-section + .reader-section {
  margin-top: 38px;
}

.reader-section h2 {
  margin: 0 0 16px;
  color: var(--reader-text);
  font-size: 1.75em;
  line-height: 1.14;
  font-weight: 800;
  letter-spacing: -0.02em;
  scroll-margin-top: 24px;
}

.reader-section p {
  margin: 0 0 14px;
  color: var(--reader-body);
  font-size: 1em;
  line-height: 1.88;
  letter-spacing: 0.002em;
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
  display: grid;
  grid-template-columns: 1fr auto 1fr;
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

.pdf-ai-button {
  position: absolute;
  right: 18px;
  bottom: 18px;
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border: 0;
  border-radius: 999px;
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  box-shadow: 0 14px 30px color-mix(in srgb, var(--reader-accent) 24%, transparent);
  font-size: 0.62rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.18s ease, background 0.18s ease;
}

.pdf-ai-button:hover {
  transform: translateY(-2px);
  background: var(--reader-accent-hover);
}

.pdf-ai-button svg {
  width: 16px;
  height: 16px;
}

/* ============================================================
   AI PANEL
============================================================ */



.ai-messages {
  min-height: 0;
  flex: 1;
  overflow: auto;
  padding: 14px;
  scroll-behavior: smooth;
}

/* Chat Full-Screen Adjustments (ChatGPT/Claude style) */
.chat-full-screen {
  background: var(--reader-bg);
  border-left: none;
}



.chat-full-screen .ai-messages,
.chat-full-screen .ai-composer,
.chat-full-screen .quick-prompts {
  max-width: 800px;
  margin: 0 auto;
  width: 100%;
}

.chat-full-screen .ai-composer {
  padding: 0 14px 24px 14px;
}

.chat-is-empty .ai-messages {
  flex: 0 0 auto;
  margin-top: auto;
}

.chat-is-empty .ai-composer {
  margin-bottom: auto;
}

.chat-is-empty .ai-welcome {
  align-items: center;
  text-align: center;
  border: none;
  background: transparent;
  padding: 0;
  margin-bottom: 32px;
}

.chat-is-empty .ai-welcome__icon,
.chat-is-empty .ai-welcome p,
.chat-is-empty .ai-welcome__eyebrow {
  display: none;
}

.chat-is-empty .ai-welcome h3 {
  font-size: clamp(1.4rem, 4vw, 2.2rem);
  font-weight: 600;
  color: var(--reader-text);
  letter-spacing: -0.5px;
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
  font-size: 0.43rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.ai-welcome h3 {
  margin-top: 6px;
  color: var(--reader-text);
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: -0.025em;
}

.ai-welcome p {
  margin-top: 5px;
  color: var(--reader-muted);
  font-size: 0.55rem;
  line-height: 1.6;
}

.message-stack {
  margin-bottom: 13px;
}

.message-stack__assistant {
  width: 100%;
}

.message {
  width: fit-content;
  max-width: 92%;
  padding: 10px 11px;
  border-radius: 13px;
  font-size: 0.57rem;
  line-height: 1.65;
  word-break: break-word;
}

.message--user {
  margin-left: auto;
  color: var(--reader-text);
  background: var(--reader-surface-2);
  border-radius: 20px;
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

.assistant-label {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  margin: 0 0 5px 2px;
  color: var(--reader-text);
  font-size: 0.45rem;
  font-weight: 800;
}

.assistant-label__icon {
  width: 18px;
  height: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  color: var(--reader-accent);
  background: color-mix(in srgb, var(--reader-accent) 8%, transparent);
}

.assistant-label__icon svg {
  width: 11px;
  height: 11px;
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
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 7px;
  border: 1px solid var(--reader-border);
  border-radius: 8px;
  color: var(--reader-muted);
  background: var(--reader-bg);
  font-size: 0.44rem;
  font-weight: 700;
  cursor: pointer;
  transition: color 0.16s ease, background 0.16s ease, border-color 0.16s ease;
}

.feedback-button:hover {
  color: var(--reader-text);
  border-color: var(--reader-border);
}

.feedback-button.is-helpful {
  color: #2f8f63;
  background: rgba(47, 143, 99, 0.08);
  border-color: rgba(47, 143, 99, 0.22);
}

.feedback-button.is-unhelpful {
  color: #c43b3b;
  background: rgba(196, 59, 59, 0.08);
  border-color: rgba(196, 59, 59, 0.22);
}

.feedback-button svg {
  width: 11px;
  height: 11px;
}

.streaming-text {
  white-space: pre-wrap;
}

.streaming-cursor {
  opacity: 0.7;
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
  font-size: 0.53rem;
}

.error-message p {
  margin-top: 2px;
  font-size: 0.46rem;
  opacity: 0.82;
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
  min-height: 35px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 7px 8px;
  border: 1px solid var(--reader-border);
  border-radius: 9px;
  color: var(--reader-text);
  background: var(--reader-surface);
  font-size: 0.48rem;
  font-weight: 800;
  cursor: pointer;
  transition: transform 0.16s ease, background 0.16s ease;
}

.quick-prompts button:hover {
  transform: translateY(-1px);
  background: var(--reader-bg);
}

.quick-prompts svg {
  width: 13px;
  height: 13px;
  flex: 0 0 auto;
}

.ai-composer {
  flex: 0 0 auto;
  padding: 10px 12px calc(12px + env(safe-area-inset-bottom));
  border-top: none;
  background: transparent;
}

@media (max-width: 1023px) {
  .ai-composer {
    padding-bottom: calc(85px + env(safe-area-inset-bottom));
  }
}

.ai-composer__field {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 5px 5px 5px 12px;
  border: 1px solid var(--reader-border);
  border-radius: 999px;
  background: var(--reader-surface);
  transition: border-color 0.18s ease, box-shadow 0.18s ease;
}

.ai-composer__field:focus-within {
  border-color: var(--reader-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--reader-accent) 12%, transparent);
}

.ai-composer__field input {
  min-width: 0;
  flex: 1;
  height: 31px;
  border: 0;
  outline: 0;
  color: var(--reader-text);
  background: transparent;
  font-size: 0.55rem;
}

.ai-composer__field input::placeholder {
  color: var(--reader-muted);
}

.send-button {
  width: 32px;
  height: 32px;
  flex: 0 0 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 50%;
  color: var(--reader-accent-text);
  background: var(--reader-accent);
  cursor: pointer;
  transition: transform 0.18s ease, opacity 0.18s ease, background 0.18s ease;
}

.send-button:hover:not(:disabled) {
  transform: translateY(-1px);
  background: var(--reader-accent-hover);
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

  .reader-paper__inner {
    padding-left: 42px;
    padding-right: 42px;
  }
}

@media (max-width: 1100px) {
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

  .reader-paper__inner {
    padding: 38px 28px 34px;
  }
}

@media (max-width: 1023px) {
  .reader-header__left {
    flex: 1;
  }

  .reader-header__right {
    flex: 0 0 auto;
  }

  .reader-header__right .reader-menu,
  .reader-header__right .segmented-control {
    display: none;
  }

  .reader-scroll {
    padding: 18px 10px 30px;
  }

  .reader-column {
    width: 100% !important;
  }

  .reader-resize {
    display: none;
  }
}

@media (max-width: 720px) {
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
    border-radius: 8px;
  }

  .reader-paper__inner {
    padding: 28px 18px 26px;
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

@media (max-width: 480px) {
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
    padding: 24px 14px 22px;
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
.chat-page {
  /* No global padding needed anymore as bottom nav floats over the bottom */
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
    padding-bottom: 100px !important; /* Space so text isn't hidden under bottom nav */
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

.mobile-sidebar-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--sa-taupe);
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  border-radius: 12px;
  padding: 8px 12px;
  pointer-events: auto;
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
  z-index: 100;
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
  background: var(--sa-spotlight);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.25);
}

.mobile-theme-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

/* ============================================================
   MOBILE BOTTOM NAVIGATION (FLOATING)
============================================================ */

.mobile-bottom-nav {
  position: fixed;
  bottom: 24px;
  left: 16px;
  right: 16px;
  height: auto;
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0;
  z-index: 100;
  pointer-events: none;
}

.mobile-nav-item {
  pointer-events: auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: var(--rt-muted, var(--sa-text-muted));
  text-decoration: none;
  font-size: 0.65rem;
  font-weight: 700;
  background: var(--rt-surface, var(--sa-surface));
  border: 1px solid var(--rt-border, var(--sa-border));
  border-radius: 16px;
  padding: 8px 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  transition: all 0.2s ease;
  min-width: 60px;
}

.mobile-nav-item svg {
  width: 18px;
  height: 18px;
}

.mobile-nav-item:hover,
.mobile-nav-item:active {
  background: var(--sa-spotlight);
  transform: translateY(-2px);
}

.mobile-nav-item.is-active {
  color: #10b981;
  background: var(--sa-spotlight);
  border-color: rgba(16, 185, 129, 0.25);
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
  margin: 1.15em 0 0.45em;
  color: var(--rt-text);
  line-height: 1.3;
  font-weight: 800;
}

.ai-response-content h1:first-child,
.ai-response-content h2:first-child,
.ai-response-content h3:first-child,
.ai-response-content h4:first-child {
  margin-top: 0;
}

.ai-response-content h1 { font-size: 1.45em; }
.ai-response-content h2 { font-size: 1.27em; }
.ai-response-content h3 { font-size: 1.08em; }
.ai-response-content h4 { font-size: 1em; }

.ai-response-content p {
  margin: 0.7em 0;
}

.ai-response-content p:first-child {
  margin-top: 0;
}

.ai-response-content strong {
  color: var(--rt-text);
  font-weight: 800;
}

.ai-response-content em {
  font-style: italic;
}

.ai-response-content ul,
.ai-response-content ol {
  margin: 0.7em 0;
  padding-left: 1.5em;
}

.ai-response-content ul {
  list-style: disc;
}

.ai-response-content ol {
  list-style: decimal;
}

.ai-response-content li {
  margin: 0.3em 0;
}

.ai-response-content li > p {
  margin: 0.2em 0;
}

.ai-response-content code {
  padding: 0.15em 0.35em;
  border: 1px solid var(--rt-border);
  border-radius: 5px;
  color: var(--rt-text);
  background: var(--rt-bg);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.9em;
}

.ai-response-content pre {
  margin: 1em 0;
  padding: 0.9rem;
  overflow-x: auto;
  border: 1px solid var(--rt-border);
  border-radius: 8px;
  color: var(--rt-text-body);
  background: var(--rt-bg);
}

.ai-response-content pre code {
  padding: 0;
  border: 0;
  background: transparent;
}

.ai-response-content a {
  color: var(--rt-accent);
  text-decoration: underline;
  text-underline-offset: 2px;
}

.ai-response-content blockquote {
  margin: 1em 0;
  padding-left: 0.9em;
  border-left: 3px solid var(--rt-accent);
  color: var(--rt-muted);
}

.ai-response-content hr {
  margin: 1.25em 0;
  border: 0;
  border-top: 1px solid var(--rt-border);
}

.ai-response-content table {
  width: 100%;
  margin: 1em 0;
  border-collapse: collapse;
}

.ai-response-content th,
.ai-response-content td {
  padding: 0.55em 0.65em;
  border: 1px solid var(--rt-border);
  text-align: left;
  vertical-align: top;
}

.ai-response-content th {
  color: var(--rt-text);
  background: var(--rt-bg);
  font-weight: 800;
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