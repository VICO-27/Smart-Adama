<template>
  <!-- =========================================================
       SMART ADAMA APP NAVIGATION

       PUBLIC:
         Brand | EN | Sign in | Get Started after scroll

       AUTHENTICATED:
         Brand | Home | Study | Game | EN | Profile

       NO AI / SEARCH
  ========================================================== -->

  <div
    class="smart-navbar fixed inset-x-0 top-3 md:top-4 z-40 flex flex-col items-center px-3 md:px-5 pointer-events-none"
  >

    <!-- =======================================================
         PUBLIC NAVBAR
    ======================================================== -->

    <nav
      v-if="!auth.isAuthenticated"
      class="pointer-events-auto w-full max-w-6xl"
    >

      <div
        class="public-navbar"
        :class="{
          'public-navbar-scrolled': isScrolled
        }"
      >

        <!-- BRAND -->

        <router-link
          to="/"
          class="brand-link"
          @click="closeMobileMenu"
        >

          <div class="brand-logo">
            <img
              src="/logo.png"
              alt="Smart Adama Logo"
              class="w-full h-full object-contain"
            />
          </div>

          <div class="brand-copy">

            <div class="brand-title">
              {{ $t('nav.brand') }}
            </div>

            <div class="brand-subtitle">
              Smart City Learning
            </div>

          </div>

        </router-link>


        <!-- PUBLIC ACTIONS -->

        <div class="public-actions">

          <!-- LANGUAGE -->

          <div
            class="language-wrapper"
            @mouseenter="openLangMenu"
            @mouseleave="closeLangMenu"
          >

            <button
              type="button"
              class="language-button"
              aria-haspopup="menu"
              :aria-expanded="langMenuOpen"
              @click="
                langMenuOpen = !langMenuOpen
              "
            >

              <span>
                {{ currentLangDisplay }}
              </span>

              <svg
                class="language-chevron"
                :class="{
                  'rotate-180':
                    langMenuOpen
                }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="m6 9 6 6 6-6"
                />
              </svg>

            </button>


            <!-- LANGUAGE DROPDOWN -->

            <transition name="language-menu">

              <div
                v-if="langMenuOpen"
                class="language-menu"
                role="menu"
              >

                <div class="language-menu-header">
                  Language
                </div>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'en'
                  }"
                  @click="changeLanguage('en')"
                >

                  <span>
                    {{ $t('nav.lang_en') }}
                  </span>

                  <svg
                    v-if="locale === 'en'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'om'
                  }"
                  @click="changeLanguage('om')"
                >

                  <span>
                    {{ $t('nav.lang_om') }}
                  </span>

                  <svg
                    v-if="locale === 'om'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'am'
                  }"
                  @click="changeLanguage('am')"
                >

                  <span>
                    አማርኛ
                  </span>

                  <svg
                    v-if="locale === 'am'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>

              </div>

            </transition>

          </div>


          <!-- SIGN IN -->

          <button
            type="button"
            class="signin-button"
            @click="
              auth.openAuthModal('login')
            "
          >
            {{ $t('nav.signin') }}
          </button>


          <!-- GET STARTED -->

          <transition name="get-started">

            <button
              v-if="isScrolled"
              type="button"
              class="get-started-button"
              @click="
                auth.openAuthModal('register')
              "
            >

              {{ $t('nav.get_started') }}

              <svg
                class="button-arrow"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  d="M5 12h14"
                  stroke-linecap="round"
                />

                <path
                  d="m13 6 6 6-6 6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>

            </button>

          </transition>

        </div>

      </div>

    </nav>


    <!-- =======================================================
         AUTHENTICATED NAVBAR
    ======================================================== -->

    <div
      v-else
      ref="navContainerRef"
      class="pointer-events-auto w-full max-w-[1800px]"
    >

      <div class="authenticated-navbar">


        <!-- BRAND -->

        <router-link
          to="/dashboard"
          class="authenticated-brand"
          @click="closeMobileMenu"
        >

          <div class="authenticated-logo">

            <img
              src="/logo.png"
              alt="Smart Adama Logo"
              class="w-full h-full object-contain"
            />

          </div>


          <div
            class="authenticated-brand-copy"
          >

            <div class="brand-title">
              {{ $t('nav.brand') }}
            </div>

            <div class="brand-subtitle">
              Learning Hub
            </div>

          </div>

        </router-link>


        <!-- DESKTOP NAVIGATION -->

        <nav
          class="authenticated-links"
          aria-label="Application navigation"
        >

          <!-- DASHBOARD -->

          <router-link
            to="/dashboard"
            class="app-nav-link"
            :class="{
              active:
                isRouteActive('/dashboard')
            }"
          >

            <svg
              class="app-nav-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path
                d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"
              />
            </svg>

            <span>
              {{ $t('nav.home') }}
            </span>

          </router-link>


          <!-- STUDY -->

          <router-link
            to="/study"
            class="app-nav-link"
            :class="{
              active:
                isRouteActive('/study')
            }"
          >

            <svg
              class="app-nav-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path
                d="M5 4h14v16H5V4Z"
              />

              <path
                d="M9 8h6M9 12h6M9 16h4"
              />
            </svg>

            <span>
              {{ $t('nav.study') }}
            </span>

          </router-link>


          <!-- GAME -->

          <router-link
            to="/game"
            class="app-nav-link"
            :class="{
              active:
                isRouteActive('/game')
            }"
          >

            <svg
              class="app-nav-icon"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.7"
            >
              <path
                d="M8 10h8"
              />

              <path
                d="M10 8v4"
              />

              <path
                d="M14 12h.01"
              />

              <path
                d="M8.5 19H6.8a2.8 2.8 0 0 1-2.7-3.55l1.4-5.1A5 5 0 0 1 10.32 6h3.36a5 5 0 0 1 4.82 4.35l1.4 5.1A2.8 2.8 0 0 1 17.2 19h-1.7l-2.1-3h-3.3l-1.6 3Z"
              />
            </svg>

            <span>
              {{ $t('nav.game') }}
            </span>

          </router-link>

        </nav>


        <!-- RIGHT ACTIONS -->

        <div class="authenticated-actions">


          <!-- LANGUAGE -->

          <div
            class="language-wrapper"
            @mouseenter="openLangMenu"
            @mouseleave="closeLangMenu"
          >

            <button
              type="button"
              class="language-button authenticated-language"
              aria-haspopup="menu"
              :aria-expanded="langMenuOpen"
              @click="
                langMenuOpen =
                  !langMenuOpen
              "
            >

              {{ currentLangDisplay }}

              <svg
                class="language-chevron"
                :class="{
                  'rotate-180':
                    langMenuOpen
                }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="m6 9 6 6 6-6"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>

            </button>


            <transition name="language-menu">

              <div
                v-if="langMenuOpen"
                class="language-menu"
                role="menu"
              >

                <div class="language-menu-header">
                  Language
                </div>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'en'
                  }"
                  @click="
                    changeLanguage('en')
                  "
                >

                  <span>
                    {{ $t('nav.lang_en') }}
                  </span>

                  <svg
                    v-if="locale === 'en'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'om'
                  }"
                  @click="
                    changeLanguage('om')
                  "
                >

                  <span>
                    {{ $t('nav.lang_om') }}
                  </span>

                  <svg
                    v-if="locale === 'om'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>


                <button
                  type="button"
                  class="language-item"
                  :class="{
                    active:
                      locale === 'am'
                  }"
                  @click="
                    changeLanguage('am')
                  "
                >

                  <span>
                    አማርኛ
                  </span>

                  <svg
                    v-if="locale === 'am'"
                    class="language-check"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      d="m5 12 4 4L19 6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>

                </button>

              </div>

            </transition>

          </div>


          <div class="action-divider"></div>


          <!-- PROFILE -->

          <router-link
            to="/profile"
            class="profile-link"
          >

            <img
              v-if="profileImage"
              :src="profileImage"
              :alt="
                `${auth.user?.name || 'User'} profile picture`
              "
              class="profile-avatar"
            />

            <div
              v-else
              class="profile-avatar profile-avatar-fallback"
            >
              {{ userInitial }}
            </div>


            <div class="profile-copy">

              <div class="profile-name">
                {{
                  auth.user?.name ||
                  $t('nav.account')
                }}
              </div>

              <div class="profile-subtitle">
                View profile
              </div>

            </div>


            <svg
              class="profile-chevron"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m9 18 6-6-6-6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </router-link>


          <!-- MOBILE -->

          <button
            type="button"
            class="mobile-menu-button authenticated-mobile-button"
            :aria-expanded="mobileMenuOpen"
            aria-label="Open navigation menu"
            @click.stop="toggleMobileMenu"
          >

            <svg
              v-if="!mobileMenuOpen"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M4 7h16M4 12h16M4 17h16"
                stroke-linecap="round"
              />
            </svg>

            <svg
              v-else
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <path
                d="M6 6l12 12M18 6 6 18"
                stroke-linecap="round"
              />
            </svg>

          </button>

        </div>

      </div>


      <!-- =====================================================
           AUTH MOBILE MENU
      ====================================================== -->

      <transition name="mobile-menu">

        <div
          v-if="mobileMenuOpen"
          class="mobile-menu-panel authenticated-mobile-panel"
        >

          <router-link
            to="/dashboard"
            class="mobile-app-link"
            :class="{
              active:
                isRouteActive('/dashboard')
            }"
            @click="
              closeMobileMenu
            "
          >

            <span>
              {{ $t('nav.dashboard') }}
            </span>

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m9 18 6-6-6-6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </router-link>


          <router-link
            to="/study"
            class="mobile-app-link"
            :class="{
              active:
                isRouteActive('/study')
            }"
            @click="
              closeMobileMenu
            "
          >

            <span>
              {{ $t('nav.study') }}
            </span>

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m9 18 6-6-6-6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </router-link>


          <router-link
            to="/game"
            class="mobile-app-link"
            :class="{
              active:
                isRouteActive('/game')
            }"
            @click="
              closeMobileMenu
            "
          >

            <span>
              {{ $t('nav.game') }}
            </span>

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m9 18 6-6-6-6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </router-link>


          <router-link
            to="/profile"
            class="mobile-app-link"
            @click="
              closeMobileMenu
            "
          >

            <span>
              {{ $t('nav.profile') }}
            </span>

            <svg
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.8"
            >
              <path
                d="m9 18 6-6-6-6"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>

          </router-link>


          <div class="mobile-divider"></div>


          <div class="mobile-section">

            <span class="mobile-section-label">
              Language
            </span>

            <div class="mobile-language-grid">

              <button
                type="button"
                class="mobile-language-button"
                :class="{
                  active:
                    locale === 'en'
                }"
                @click="
                  changeLanguage('en')
                "
              >
                EN
              </button>

              <button
                type="button"
                class="mobile-language-button"
                :class="{
                  active:
                    locale === 'om'
                }"
                @click="
                  changeLanguage('om')
                "
              >
                OM
              </button>

              <button
                type="button"
                class="mobile-language-button"
                :class="{
                  active:
                    locale === 'am'
                }"
                @click="
                  changeLanguage('am')
                "
              >
                AM
              </button>

            </div>

          </div>

        </div>

      </transition>

    </div>

    <!-- MOBILE MENU BACKDROP -->
    <teleport to="body">
      <transition name="nav-backdrop">
        <div
          v-if="mobileMenuOpen"
          class="fixed inset-0 z-30 bg-black/25 backdrop-blur-[2px] cursor-pointer lg:hidden"
          aria-hidden="true"
          @click="closeMobileMenu"
          @touchstart.passive="closeMobileMenu"
        ></div>
      </transition>
    </teleport>

  </div>
</template>


<script setup lang="ts">

import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
} from 'vue'

import {
  useRoute,
} from 'vue-router'

import {
  useAuthStore,
} from '@/stores/auth'

import {
  useI18n,
} from 'vue-i18n'

import {
  setLanguage,
} from '@/i18n'


/* ============================================================
   STORES
============================================================ */

const auth =
  useAuthStore()

const route =
  useRoute()

const {
  locale,
} = useI18n()


/* ============================================================
   STATE
============================================================ */

const navContainerRef =
  ref<HTMLElement | null>(null)

const isScrolled =
  ref(false)

const langMenuOpen =
  ref(false)

const mobileMenuOpen =
  ref(false)

let langTimeout:
  ReturnType<typeof setTimeout> | null =
    null


/* ============================================================
   PROFILE
============================================================ */

const profileImage =
  computed(() => {

    const user =
      auth.user as any

    if (!user) {
      return null
    }

    return (
      user.avatar_url ||
      user.profile_picture ||
      user.profile_image ||
      user.avatar ||
      user.photo_url ||
      user.image ||
      user.photo ||
      null
    )
  })


const userInitial =
  computed(() => {

    const name =
      auth.user?.name?.trim()

    if (!name) {
      return 'A'
    }

    return name
      .charAt(0)
      .toUpperCase()
  })


/* ============================================================
   LANGUAGE
============================================================ */

const currentLangDisplay =
  computed(() => {

    if (locale.value === 'am') {
      return 'AM'
    }

    if (locale.value === 'om') {
      return 'OM'
    }

    return 'EN'
  })


const changeLanguage =
  (
    language:
      'en' |
      'am' |
      'om',
  ) => {

    setLanguage(language)

    langMenuOpen.value =
      false

    closeMobileMenu()
  }


/* ============================================================
   LANGUAGE MENU
============================================================ */

const openLangMenu =
  () => {

    if (langTimeout) {
      clearTimeout(langTimeout)
    }

    langMenuOpen.value =
      true
  }


const closeLangMenu =
  () => {

    langTimeout =
      setTimeout(() => {

        langMenuOpen.value =
          false

      }, 220)
  }


/* ============================================================
   ROUTE
============================================================ */

const isRouteActive =
  (
    path: string,
  ) => {

    if (path === '/') {
      return route.path === '/'
    }

    return (
      route.path === path ||
      route.path.startsWith(
        `${path}/`,
      )
    )
  }


/* ============================================================
   MOBILE
============================================================ */

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value
}

const closeMobileMenu =
  () => {

    mobileMenuOpen.value =
      false

    langMenuOpen.value =
      false
  }

const handleOutsideInteraction = (event: MouseEvent | TouchEvent) => {
  if (!mobileMenuOpen.value) return
  const target = event.target as Node | null
  if (navContainerRef.value && target && !navContainerRef.value.contains(target)) {
    closeMobileMenu()
  }
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && mobileMenuOpen.value) {
    closeMobileMenu()
  }
}

const handleResize = () => {
  if (window.innerWidth >= 1024 && mobileMenuOpen.value) {
    closeMobileMenu()
  }
}

watch(
  () => route.fullPath,
  () => {
    closeMobileMenu()
  },
)


/* ============================================================
   SCROLL
============================================================ */

const handleScroll =
  () => {

    isScrolled.value =
      window.scrollY > 12

    if (mobileMenuOpen.value) {
      closeMobileMenu()
    }
  }


/* ============================================================
   LIFECYCLE
============================================================ */

onMounted(() => {

  window.addEventListener(
    'scroll',
    handleScroll,
    {
      passive: true,
    },
  )

  document.addEventListener('click', handleOutsideInteraction)
  document.addEventListener('touchstart', handleOutsideInteraction, { passive: true })
  document.addEventListener('keydown', handleKeyDown)
  window.addEventListener('resize', handleResize, { passive: true })

  handleScroll()
})


onUnmounted(() => {

  window.removeEventListener(
    'scroll',
    handleScroll,
  )

  document.removeEventListener('click', handleOutsideInteraction)
  document.removeEventListener('touchstart', handleOutsideInteraction)
  document.removeEventListener('keydown', handleKeyDown)
  window.removeEventListener('resize', handleResize)

  if (langTimeout) {
    clearTimeout(
      langTimeout,
    )
  }
})

</script>


<style scoped>

/* ============================================================
   NAVBAR
============================================================ */

.smart-navbar {
  font-family:
    var(--font-body);
}


/* ============================================================
   PUBLIC NAVBAR
============================================================ */

.public-navbar {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  width:
    100%;

  min-height:
    3.2rem;

  padding:
    0.3rem 0.48rem;

  border:
    1px solid
    var(--sa-nav-border);

  border-radius:
    1.05rem;

  background:
    var(--sa-nav-surface);

  backdrop-filter:
    blur(20px)
    saturate(160%);

  -webkit-backdrop-filter:
    blur(20px)
    saturate(160%);

  box-shadow:
    var(--sa-nav-shadow);

  transition:
    background 0.3s ease,
    border-color 0.3s ease,
    box-shadow 0.3s ease;
}


.public-navbar-scrolled {

  background:
    var(--sa-nav-surface-scrolled);
}


/* ============================================================
   BRAND
============================================================ */

.brand-link,
.authenticated-brand {

  display:
    flex;

  align-items:
    center;

  gap:
    0.58rem;

  flex-shrink:
    0;

  color:
    inherit;

  text-decoration:
    none;
}


.brand-logo {

  width:
    2.2rem;

  height:
    2.2rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    0.18rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.65rem;

  background:
    white;

  box-shadow:
    0 2px 8px
    rgba(15,23,42,0.05);
}


.brand-copy,
.authenticated-brand-copy {

  display:
    flex;

  flex-direction:
    column;

  line-height:
    1;
}


.brand-title {

  color:
    var(--sa-nav-text);

  font-size:
    0.86rem;

  font-weight:
    800;

  letter-spacing:
    -0.025em;
}


.brand-subtitle {

  margin-top:
    0.2rem;

  color:
    var(--sa-nav-muted);

  font-size:
    0.41rem;

  font-weight:
    700;

  text-transform:
    uppercase;

  letter-spacing:
    0.14em;
}


/* ============================================================
   PUBLIC ACTIONS
============================================================ */

.public-actions {

  display:
    flex;

  align-items:
    center;

  gap:
    0.12rem;
}


/* ============================================================
   LANGUAGE WRAPPER
============================================================ */

.language-wrapper {
  position:
    relative;
}


/* ============================================================
   LANGUAGE BUTTON
============================================================ */

.language-button {

  display:
    inline-flex;

  align-items:
    center;

  justify-content:
    center;

  gap:
    0.28rem;

  min-width:
    2.7rem;

  padding:
    0.5rem;

  border:
    0;

  border-radius:
    0.62rem;

  background:
    transparent;

  color:
    var(--sa-nav-text);

  font-size:
    0.63rem;

  font-weight:
    800;

  letter-spacing:
    0.06em;

  cursor:
    pointer;

  transition:
    background 0.2s ease,
    color 0.2s ease;
}


.language-button:hover {

  background:
    var(--sa-nav-hover);
}


.language-chevron {

  width:
    0.62rem;

  height:
    0.62rem;

  transition:
    transform 0.22s
    var(--ease-out);
}


/* ============================================================
   LANGUAGE DROPDOWN
============================================================ */

.language-menu {

  position:
    absolute;

  top:
    calc(100% + 0.4rem);

  right:
    0;

  z-index:
    60;

  width:
    11.4rem;

  padding:
    0.35rem;

  border:
    1px solid
    var(--sa-dropdown-border);

  border-radius:
    0.95rem;

  background:
    var(--sa-dropdown-bg);

  color:
    var(--sa-text);

  backdrop-filter:
    blur(20px)
    saturate(170%);

  -webkit-backdrop-filter:
    blur(20px)
    saturate(170%);

  box-shadow:
    var(--sa-dropdown-shadow);
}


.language-menu-header {

  padding:
    0.45rem
    0.65rem
    0.3rem;

  color:
    var(--sa-text-faint);

  font-size:
    0.49rem;

  font-weight:
    800;

  letter-spacing:
    0.14em;

  text-transform:
    uppercase;
}


.language-item {

  width:
    100%;

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  padding:
    0.58rem
    0.65rem;

  border:
    0;

  border-radius:
    0.62rem;

  background:
    transparent;

  color:
    var(--sa-text-secondary);

  font-size:
    0.71rem;

  font-weight:
    600;

  text-align:
    left;

  cursor:
    pointer;

  transition:
    background 0.17s ease,
    color 0.17s ease;
}


.language-item:hover {

  background:
    var(--sa-nav-hover);

  color:
    var(--sa-text);
}


.language-item.active {

  background:
    var(--sa-nav-active);

  color:
    #10b981;
}


.language-check {

  width:
    0.75rem;

  height:
    0.75rem;

  flex-shrink:
    0;
}


/* ============================================================
   SIGN IN
============================================================ */

.signin-button {

  padding:
    0.5rem
    0.7rem;

  border:
    0;

  border-radius:
    0.62rem;

  background:
    transparent;

  color:
    var(--sa-nav-text);

  font-size:
    0.68rem;

  font-weight:
    700;

  cursor:
    pointer;

  transition:
    background 0.2s ease,
    transform 0.2s ease;
}


.signin-button:hover {

  background:
    var(--sa-nav-hover);

  transform:
    translateY(-1px);
}


/* ============================================================
   GET STARTED
============================================================ */

.get-started-button {

  display:
    inline-flex;

  align-items:
    center;

  gap:
    0.32rem;

  padding:
    0.5rem
    0.7rem;

  border:
    0;

  border-radius:
    0.62rem;

  background:
    var(--sa-button-primary);

  color:
    var(--sa-button-primary-text);

  font-size:
    0.66rem;

  font-weight:
    800;

  cursor:
    pointer;

  box-shadow:
    0 4px 14px
    rgba(0,0,0,0.12);

  transition:
    transform 0.22s ease,
    box-shadow 0.22s ease;
}


.get-started-button:hover {

  transform:
    translateY(-1px);

  box-shadow:
    0 7px 18px
    rgba(0,0,0,0.18);
}


.button-arrow {

  width:
    0.72rem;

  height:
    0.72rem;
}


.get-started-enter-active,
.get-started-leave-active {

  transition:
    opacity 0.38s var(--ease-out),
    transform 0.38s var(--ease-out);
}


.get-started-enter-from,
.get-started-leave-to {

  opacity:
    0;

  transform:
    translateX(18px);
}


/* ============================================================
   AUTHENTICATED NAVBAR
============================================================ */

.authenticated-navbar {

  width:
    100%;

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  gap:
    0.55rem;
}


/* ============================================================
   AUTH BRAND
============================================================ */

.authenticated-brand {

  padding:
    0.3rem
    0.55rem;

  border:
    1px solid
    var(--sa-nav-border);

  border-radius:
    0.9rem;

  background:
    var(--sa-nav-surface);

  backdrop-filter:
    blur(20px)
    saturate(160%);

  -webkit-backdrop-filter:
    blur(20px)
    saturate(160%);

  box-shadow:
    var(--sa-nav-shadow);
}


.authenticated-logo {

  width:
    1.9rem;

  height:
    1.9rem;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  padding:
    0.15rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.58rem;

  background:
    white;
}


/* ============================================================
   APP NAVIGATION
============================================================ */

.authenticated-links {

  display:
    flex;

  align-items:
    center;

  gap:
    0.08rem;

  padding:
    0.22rem;

  border:
    1px solid
    var(--sa-nav-border);

  border-radius:
    0.9rem;

  background:
    var(--sa-nav-surface);

  backdrop-filter:
    blur(20px)
    saturate(160%);

  -webkit-backdrop-filter:
    blur(20px)
    saturate(160%);

  box-shadow:
    var(--sa-nav-shadow);
}


.app-nav-link {

  display:
    inline-flex;

  align-items:
    center;

  gap:
    0.33rem;

  padding:
    0.46rem
    0.68rem;

  border-radius:
    0.62rem;

  color:
    var(--sa-nav-text);

  font-size:
    0.67rem;

  font-weight:
    700;

  text-decoration:
    none;

  transition:
    background 0.18s ease,
    color 0.18s ease;
}


.app-nav-link:hover {

  background:
    var(--sa-nav-hover);
}


.app-nav-link.active {

  background:
    var(--sa-nav-active);

  color:
    #10b981;
}


.app-nav-icon {

  width:
    0.8rem;

  height:
    0.8rem;

  flex-shrink:
    0;
}


/* ============================================================
   AUTH ACTIONS
============================================================ */

.authenticated-actions {

  display:
    flex;

  align-items:
    center;

  gap:
    0.05rem;

  padding:
    0.2rem;

  border:
    1px solid
    var(--sa-nav-border);

  border-radius:
    0.9rem;

  background:
    var(--sa-nav-surface);

  backdrop-filter:
    blur(20px)
    saturate(160%);

  -webkit-backdrop-filter:
    blur(20px)
    saturate(160%);

  box-shadow:
    var(--sa-nav-shadow);
}


.action-divider {

  width:
    1px;

  height:
    1.05rem;

  margin:
    0
    0.12rem;

  background:
    var(--sa-border);
}


/* ============================================================
   PROFILE
============================================================ */

.profile-link {

  display:
    flex;

  align-items:
    center;

  gap:
    0.45rem;

  padding:
    0.18rem
    0.3rem;

  border-radius:
    0.68rem;

  color:
    var(--sa-nav-text);

  text-decoration:
    none;

  transition:
    background 0.2s ease;
}


.profile-link:hover {

  background:
    var(--sa-nav-hover);
}


.profile-avatar {

  width:
    1.8rem;

  height:
    1.8rem;

  flex-shrink:
    0;

  border-radius:
    50%;

  display:
    flex;

  align-items:
    center;

  justify-content:
    center;

  object-fit:
    cover;

  border:
    1px solid
    var(--sa-border-strong);
}


.profile-avatar-fallback {

  color:
    white;

  font-size:
    0.65rem;

  font-weight:
    800;

  border:
    none;

  background:
    linear-gradient(
      135deg,
      #10b981,
      #2563eb
    );
}


.profile-copy {

  min-width:
    0;

  max-width:
    7.4rem;

  display:
    flex;

  flex-direction:
    column;
}


.profile-name {

  overflow:
    hidden;

  color:
    var(--sa-nav-text);

  font-size:
    0.62rem;

  font-weight:
    800;

  line-height:
    1.1;

  text-overflow:
    ellipsis;

  white-space:
    nowrap;
}


.profile-subtitle {

  margin-top:
    0.1rem;

  color:
    var(--sa-nav-muted);

  font-size:
    0.45rem;

  line-height:
    1;
}


.profile-chevron {

  width:
    0.68rem;

  height:
    0.68rem;

  flex-shrink:
    0;

  color:
    var(--sa-nav-muted);
}


/* ============================================================
   MOBILE MENU BUTTON
============================================================ */

.mobile-menu-button {

  display:
    none;

  width:
    1.95rem;

  height:
    1.95rem;

  align-items:
    center;

  justify-content:
    center;

  padding:
    0;

  border:
    0;

  border-radius:
    0.62rem;

  background:
    var(--sa-nav-hover);

  color:
    var(--sa-nav-text);

  cursor:
    pointer;
}


.mobile-menu-button svg {

  width:
    0.9rem;

  height:
    0.9rem;
}


/* ============================================================
   MOBILE MENU
============================================================ */

.mobile-menu-panel {

  width:
    240px;

  margin-left:
    auto;

  transform-origin:
    top right;

  margin-top:
    0.55rem;

  padding:
    0.7rem;

  border:
    1px solid
    var(--sa-nav-border);

  border-radius:
    1rem;

  background:
    var(--sa-nav-surface-scrolled);

  backdrop-filter:
    blur(22px)
    saturate(160%);

  -webkit-backdrop-filter:
    blur(22px)
    saturate(160%);

  box-shadow:
    var(--sa-nav-shadow);
}


.mobile-section {

  padding:
    0.25rem;
}


.mobile-section-label {

  display:
    block;

  margin-bottom:
    0.45rem;

  color:
    var(--sa-nav-muted);

  font-size:
    0.5rem;

  font-weight:
    800;

  letter-spacing:
    0.14em;

  text-transform:
    uppercase;
}


.mobile-language-grid {

  display:
    grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap:
    0.4rem;
}


.mobile-language-button {

  padding:
    0.4rem 0.2rem;

  border:
    1px solid
    var(--sa-border);

  border-radius:
    0.65rem;

  background:
    var(--sa-surface-soft);

  color:
    var(--sa-nav-muted);

  font-size:
    0.67rem;

  font-weight:
    800;

  cursor:
    pointer;
}


.mobile-language-button.active {

  border-color:
    rgba(16,185,129,0.28);

  background:
    var(--sa-nav-active);

  color:
    #10b981;
}


.mobile-auth-grid {

  display:
    grid;

  grid-template-columns:
    repeat(2, 1fr);

  gap:
    0.45rem;
}


.mobile-signin-button,
.mobile-get-started-button {

  min-height:
    2.35rem;

  border-radius:
    0.7rem;

  font-size:
    0.68rem;

  font-weight:
    800;

  cursor:
    pointer;
}


.mobile-signin-button {

  border:
    1px solid
    var(--sa-border-strong);

  background:
    transparent;

  color:
    var(--sa-nav-text);
}


.mobile-get-started-button {

  border:
    0;

  background:
    var(--sa-button-primary);

  color:
    var(--sa-button-primary-text);
}


.mobile-divider {

  height:
    1px;

  margin:
    0.55rem 0;

  background:
    var(--sa-border);
}


/* ============================================================
   MOBILE APP LINKS
============================================================ */

.mobile-app-link {

  display:
    flex;

  align-items:
    center;

  justify-content:
    space-between;

  width:
    100%;

  margin-bottom:
    0.15rem;

  padding:
    0.72rem
    0.78rem;

  border-radius:
    0.7rem;

  color:
    var(--sa-nav-text);

  font-size:
    0.75rem;

  font-weight:
    700;

  text-decoration:
    none;

  transition:
    background 0.18s ease,
    color 0.18s ease;
}


.mobile-app-link:hover {

  background:
    var(--sa-nav-hover);
}


.mobile-app-link.active {

  background:
    var(--sa-nav-active);

  color:
    #10b981;
}


.mobile-app-link svg {

  width:
    0.8rem;

  height:
    0.8rem;
}


/* ============================================================
   TRANSITIONS
============================================================ */

.language-menu-enter-active,
.language-menu-leave-active {

  transition:
    opacity 0.18s ease,
    transform 0.2s
    var(--ease-out);
}


.language-menu-enter-from,
.language-menu-leave-to {

  opacity:
    0;

  transform:
    translateY(-6px)
    scale(0.97);
}


.mobile-menu-enter-active,
.mobile-menu-leave-active {

  transition:
    opacity 0.2s ease,
    transform 0.2s
    var(--ease-out);
}


.mobile-menu-enter-from,
.mobile-menu-leave-to {

  opacity:
    0;

  transform:
    translateY(-7px)
    scale(0.985);
}


.nav-backdrop-enter-active,
.nav-backdrop-leave-active {
  transition: opacity 0.2s ease;
}

.nav-backdrop-enter-from,
.nav-backdrop-leave-to {
  opacity: 0;
}


/* ============================================================
   RESPONSIVE
============================================================ */

@media (max-width: 1023px) {

  .authenticated-links {
    display:
      none;
  }

  .mobile-menu-button {
    display:
      flex;
  }
}


@media (max-width: 640px) {

  .smart-navbar {
    top:
      0.65rem;
  }

  .public-navbar {
    min-height:
      2.95rem;

    padding:
      0.25rem
      0.35rem;

    border-radius:
      0.95rem;
  }

  .brand-logo {
    width:
      2rem;

    height:
      2rem;
  }

  .brand-title {
    font-size:
      0.8rem;
  }

  .brand-subtitle {
    font-size:
      0.38rem;
  }

  .language-button {
    min-width:
      auto;

    padding:
      0.48rem
      0.42rem;
  }

  .signin-button {
    padding:
      0.48rem
      0.52rem;

    font-size:
      0.65rem;
  }

  .get-started-button {
    padding:
      0.48rem
      0.58rem;

    font-size:
      0.63rem;
  }

  .authenticated-brand {
    padding:
      0.28rem
      0.42rem;
  }

  .authenticated-actions {
    padding:
      0.16rem;
  }

  .authenticated-actions
  .language-wrapper,
  .authenticated-actions
  .action-divider,
  .authenticated-actions
  .profile-link {
    display:
      none;
  }

  .profile-copy {
    display:
      none;
  }
}

</style>