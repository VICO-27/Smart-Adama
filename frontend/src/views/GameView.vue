<script setup lang="ts">
import AppFooter from '@/components/layout/AppFooter.vue'
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProgressStore } from '@/stores/progress'
import AppNav from '@/components/layout/AppNav.vue'
import { useI18n } from 'vue-i18n'

const auth = useAuthStore()
const progress = useProgressStore()
const { t } = useI18n()

const d = computed(() => progress.dashboard)
const badges = computed(() => progress.badges || [])
const firstName = computed(() => auth.user?.name?.trim().split(/\s+/)[0] || 'Scholar')

/* Derived progression until the backend exposes a native XP service. */
const currentXP = computed(() => {
  if (!d.value) return 0
  return ((d.value.completed_chapters || 0) * 150)
    + ((d.value.quizzes_passed || 0) * 100)
    + ((d.value.current_streak || 0) * 20)
})
const currentLevel = computed(() => Math.floor(currentXP.value / 1000) + 1)
const currentLevelXP = computed(() => currentXP.value % 1000)
const xpProgressPct = computed(() => Math.min(100, currentLevelXP.value / 10))
const nextLevelRemainingXP = computed(() => 1000 - currentLevelXP.value)

interface GameMode {
  id: string
  title: string
  description: string
  difficulty: 'Easy' | 'Medium' | 'Hard' | 'Expert'
  xpReward: number
  duration: string
  available: boolean
  route: string
}

const gameModes = ref<GameMode[]>([
  {
    id: 'quick-quiz',
    title: 'Quick Quiz',
    description: 'Test your knowledge.',
    difficulty: 'Medium',
    xpReward: 100,
    duration: '5 min',
    available: true,
    route: '/quizzes',
  },
  {
    id: 'chapter-challenge',
    title: 'Chapter Challenge',
    description: 'Master core concepts.',
    difficulty: 'Hard',
    xpReward: 150,
    duration: '15 min',
    available: true,
    route: '/study',
  },
  {
    id: 'speed-round',
    title: 'Speed Round',
    description: 'Race against the clock.',
    difficulty: 'Expert',
    xpReward: 250,
    duration: '3 min',
    available: false,
    route: '#',
  },
  {
    id: 'memory-match',
    title: 'Concept Memory',
    description: 'Match civic ideas.',
    difficulty: 'Easy',
    xpReward: 50,
    duration: '5 min',
    available: false,
    route: '#',
  },
])

function badgeIcon(name: string) {
  const value = name.toLowerCase()
  if (value.includes('step')) return 'book'
  if (value.includes('learner')) return 'user'
  if (value.includes('perfect')) return 'check'
  if (value.includes('roll') || value.includes('week')) return 'flame'
  return 'bolt'
}

onMounted(() => {
  if (!progress.dashboard) progress.loadAll()
})
</script>

<template>
  <div class="game-page-wrapper">
    <AppNav />
    <main class="game-page">


      <section class="game-hero">
        <div class="hero-grid">
          <div class="hero-copy">
            <span class="eyebrow">{{ $t('game.header') }}</span>
            <h1>
              {{ $t('game.learn') }}
              <span>{{ $t('game.challenge') }}</span>
              {{ $t('game.master') }}
            </h1>
            <p>
              {{ $t('game.interactive') }}
            </p>
            <div class="hero-actions">
              <RouterLink to="/quizzes" class="hero-primary">
                {{ $t('game.start') }}
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 12h14" stroke-linecap="round"/>
                  <path d="m13 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </RouterLink>
              <RouterLink to="/study" class="hero-secondary">{{ $t('game.continue_study') }}</RouterLink>
            </div>
          </div>

          <div class="hero-visual" aria-hidden="true">
            <div class="hero-orbit orbit-one"></div>
            <div class="hero-orbit orbit-two"></div>
            <div class="hero-orbit orbit-three"></div>
            <div class="hero-core" style="overflow: hidden; padding: 0;">
              <video
                autoplay
                loop
                muted
                playsinline
                poster="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1 1'%3E%3C/svg%3E"
                src="/videos/smart-adama-book.mp4"
                style="width: 100%; height: 100%; object-fit: cover; border-radius: 2rem;"
              ></video>
            </div>
            <span class="hero-orbit-label label-one">{{ $t('game.xp') }}</span>
            <span class="hero-orbit-label label-two">{{ $t('game.quiz') }}</span>
            <span class="hero-orbit-label label-three">{{ $t('game.streak') }}</span>
          </div>
        </div>
      </section>

      <section class="progress-ribbon">
        <div class="progress-inner">
          <div class="level-block">
            <div class="level-badge">{{ currentLevel }}</div>
            <div class="level-copy">
              <div class="level-title-row">
                <span>Level {{ currentLevel }}</span>
                <strong>{{ currentXP.toLocaleString() }} XP</strong>
              </div>
              <div class="xp-track">
                <div class="xp-value" :style="{ width: `${xpProgressPct}%` }"></div>
              </div>
              <p>{{ nextLevelRemainingXP.toLocaleString() }} XP to Level {{ currentLevel + 1 }}</p>
            </div>
          </div>

          <div class="stat-group">
            <div class="game-stat"><span>{{ $t('game.streak_val') }}</span><strong>{{ d?.current_streak || 0 }}</strong><small>{{ $t('game.days') }}</small></div>
            <div class="stat-separator"></div>
            <div class="game-stat"><span>{{ $t('game.badges') }}</span><strong>{{ d?.earned_badge_count || 0 }}</strong><small>{{ $t('game.earned') }}</small></div>
            <div class="stat-separator"></div>
            <div class="game-stat"><span>{{ $t('game.quizzes') }}</span><strong>{{ d?.quizzes_passed || 0 }}</strong><small>{{ $t('game.passed') }}</small></div>
          </div>
        </div>
      </section>

      <div class="game-container">
        <div class="game-main-column">
          <section class="content-section">
            <div class="section-heading">
              <div>
                <h2>{{ $t('game.daily') }}</h2>
              </div>
              <span class="reward-pill">{{ $t('game.xp_gain') }}</span>
            </div>

            <article class="daily-challenge">
              <div class="daily-content">
                <span class="challenge-kicker">{{ $t('game.sa_challenge') }}</span>
                <h3>{{ $t('game.quiz_title') }}</h3>
                <p>
                  {{ $t('game.quiz_desc') }}
                </p>
                <div class="challenge-meta">
                  <span>{{ $t('game.q10') }}</span>
                  <span>{{ $t('game.min5') }}</span>
                  <span>{{ $t('game.mixed') }}</span>
                </div>
              </div>
              <div class="daily-action">
                <div class="challenge-ring"><div class="challenge-ring-inner">5m</div></div>
                <RouterLink to="/quizzes" class="challenge-button">
                  {{ $t('game.start_challenge') }}
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14" stroke-linecap="round"/>
                    <path d="m13 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </RouterLink>
              </div>
            </article>
          </section>

          <section class="content-section">
            <div class="section-heading">
              <div>
                <h2>{{ $t('game.choose') }}</h2>
              </div>
              <span class="section-note">{{ $t('game.avail') }}</span>
            </div>

            <div class="game-mode-grid">
              <template v-for="mode in gameModes" :key="mode.id">
                <RouterLink v-if="mode.available" :to="mode.route" class="mode-card available">
                  <div class="mode-top">
                    <div class="mode-icon">
                      <svg v-if="mode.id === 'quick-quiz'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z" stroke-linejoin="round"/>
                      </svg>
                      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="M5 4h14v16H5V4Z"/>
                        <path d="M9 8h6M9 12h6M9 16h4"/>
                      </svg>
                    </div>
                    <span class="xp-pill">+{{ mode.xpReward }} XP</span>
                  </div>

                  <div class="mode-content">
                    <h3>{{ mode.title }}</h3>
                    <p>{{ mode.description }}</p>
                  </div>

                  <div class="mode-footer">
                    <div class="mode-meta">
                      <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                          <circle cx="12" cy="12" r="9"/>
                          <path d="M12 7v5l3 2" stroke-linecap="round"/>
                        </svg>
                        {{ mode.duration }}
                      </span>
                      <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                          <path d="M7 20V10M12 20V5M17 20v-7" stroke-linecap="round"/>
                        </svg>
                        {{ mode.difficulty }}
                      </span>
                    </div>
                    <span class="mode-arrow">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14" stroke-linecap="round"/>
                        <path d="m13 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </span>
                  </div>
                </RouterLink>

                <article v-else class="mode-card disabled">
                  <div class="mode-top">
                    <div class="mode-icon">
                      <svg v-if="mode.id === 'speed-round'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 7v5l3 2" stroke-linecap="round"/>
                      </svg>
                      <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="4" y="7" width="16" height="12" rx="2"/>
                        <path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      </svg>
                    </div>
                    <span class="coming-soon">{{ $t('game.coming') }}</span>
                  </div>
                  <div class="mode-content">
                    <h3>{{ mode.title }}</h3>
                    <p>{{ mode.description }}</p>
                  </div>
                  <div class="mode-footer"><span class="development-note">{{ $t('game.in_dev') }}</span></div>
                </article>
              </template>
            </div>
          </section>
        </div>

        <aside class="game-side-column">
          <section class="side-section">
            <div class="section-heading compact">
              <div><h2>{{ $t('game.leader') }}</h2></div>
            </div>

            <article class="leaderboard-card">
              <div class="leaderboard-header">
                <span>{{ $t('game.global') }}</span>
                <span class="status-label">{{ $t('game.coming') }}</span>
              </div>

              <div class="leaderboard-empty">
                <div class="sync-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <path d="M20 11a8.1 8.1 0 0 0-14.9-4M4 5v4h4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4 13a8.1 8.1 0 0 0 14.9 4M20 19v-4h-4" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <strong>{{ $t('game.ranks_coming') }}</strong>
                <p>{{ $t('game.track_prog') }}</p>
              </div>

              <div class="current-player">
                <div class="player-identity">
                  <span class="player-rank">—</span>
                  <span class="player-avatar">{{ firstName.charAt(0) }}</span>
                  <div><strong>{{ firstName }}</strong><span>Level {{ currentLevel }}</span></div>
                </div>
                <strong class="player-xp">{{ currentXP.toLocaleString() }} XP</strong>
              </div>
            </article>
          </section>

          <section class="side-section">
            <div class="section-heading compact">
              <div><h2>{{ $t('game.achieve') }}</h2></div>
              <RouterLink to="/dashboard" class="view-all">{{ $t('game.view_all') }}</RouterLink>
            </div>

            <article class="achievement-card">
              <div v-if="badges.length" class="achievement-grid">
                <div v-for="badge in badges.slice(0, 8)" :key="badge.id || badge.name" class="achievement" :class="{ earned: badge.earned }">
                  <span class="achievement-icon">
                    <svg v-if="badgeIcon(badge.name) === 'book'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <path d="M5 4h14v16H5V4Z"/><path d="M9 8h6M9 12h6M9 16h4"/>
                    </svg>
                    <svg v-else-if="badgeIcon(badge.name) === 'user'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>
                    </svg>
                    <svg v-else-if="badgeIcon(badge.name) === 'check'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <circle cx="12" cy="12" r="8.5"/><path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <svg v-else-if="badgeIcon(badge.name) === 'flame'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <path d="M13.5 2.5c.4 3.5-1.4 5.5-3.3 7.1C8.7 10.9 8 12.2 8 14a4 4 0 0 0 4 4c1.6 0 2.9-.9 3.6-2.2.4 1.2.2 3-1.4 4.7 3.5-.9 5.8-3.6 5.8-7.2 0-4.2-2.7-8-6.5-10.8Z"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                      <path d="M13 3 4 14h7l-1 7 9-11h-7l1-7Z" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  <span class="achievement-tooltip">
                    <strong>{{ badge.name }}</strong>
                    <small>{{ badge.description || 'Learning milestone' }}</small>
                  </span>
                </div>
              </div>

              <div v-else class="achievement-empty">
                <div class="achievement-empty-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <circle cx="12" cy="8" r="4"/><path d="m9 12-2 8 5-3 5 3-2-8"/>
                  </svg>
                </div>
                <strong>{{ $t('game.unlock') }}</strong>
                <p>{{ $t('game.complete_miles') }}</p>
              </div>
            </article>
          </section>
        </aside>
      </div>

      <!-- ======================================================
           CITY FOOTER
      ======================================================= -->
      <AppFooter />
    </main>
  </div>
</template>

<style scoped>
.game-page {
  --page-bg: var(--sa-page-bg, #F0F3FA);
  --surface: var(--sa-surface, #FFFFFF);
  --surface-soft: var(--sa-surface-soft, #F8FAFC);
  --surface-muted: var(--sa-surface-muted, #EEF2F8);
  --border: var(--sa-border, #D5DEEF);
  --border-strong: var(--sa-border-strong, #B1C9EF);
  --text: var(--sa-text, #0F172A);
  --text-muted: var(--sa-text-muted, #64748B);
  --track: var(--sa-track, #D5DEEF);
  --brand: #395886;
  --brand-mid: #638ECB;
  --brand-soft: #8AAEE0;
  min-height: 100dvh;
  background: var(--page-bg);
  color: var(--text);
}

:global(html.dark) .game-page {
  --page-bg: #030712;
  --surface: #0B1220;
  --surface-soft: #111827;
  --surface-muted: #162033;
  --border: rgba(177, 201, 239, 0.13);
  --border-strong: rgba(177, 201, 239, 0.25);
  --text: #F8FAFC;
  --text-muted: #94A3B8;
  --track: #24344F;
}

.game-hero {
  position: relative;
  overflow: hidden;
  padding: 7rem 1rem 4rem;
  background: var(--page-bg);
}

.hero-grid {
  width: min(100%, 1160px);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.15fr .85fr;
  align-items: center;
  gap: 4rem;
}

.eyebrow {
  display: inline-flex;
  padding: .38rem .62rem;
  border: 1px solid rgba(99,142,203,.22);
  border-radius: 999px;
  background: rgba(99,142,203,.06);
  color: var(--brand-mid);
  font-size: .5rem;
  font-weight: 800;
  letter-spacing: .14em;
  text-transform: uppercase;
}

.hero-copy h1 {
  margin-top: .8rem;
  color: var(--text);
  font-size: clamp(3rem,7vw,5.5rem);
  line-height: .98;
  font-weight: 800;
  letter-spacing: -.06em;
}

.hero-copy h1 span { color: var(--brand-mid); }

.hero-copy p {
  max-width: 650px;
  margin-top: 1rem;
  color: var(--text-muted);
  font-size: .95rem;
  line-height: 1.7;
}

.hero-actions { display:flex; gap:.65rem; margin-top:1.5rem; flex-wrap:wrap; }
.hero-primary,.hero-secondary {
  display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
  min-height:3rem; padding:.75rem 1rem; border-radius:.8rem; font-size:.85rem; font-weight:800;
  text-decoration:none; transition:.25s ease;
}
.hero-primary { color:#fff; background:var(--brand); border:1px solid var(--brand); box-shadow:0 12px 28px rgba(57,88,134,.22); }
.hero-primary:hover { transform:translateY(-2px); background:var(--brand-mid); }
.hero-secondary { color:var(--text); background:var(--surface); border:1px solid var(--border-strong); }
.hero-secondary:hover { transform:translateY(-2px); border-color:var(--brand-mid); color:var(--brand); }
.hero-primary svg { width:1.1rem; height:1.1rem; }

.hero-visual { position:relative; width:min(100%,390px); aspect-ratio:1; margin:0 auto; }
.hero-orbit { position:absolute; left:50%; top:50%; border:1px solid rgba(99,142,203,.24); border-radius:50%; transform:translate(-50%,-50%); }
.orbit-one { width:96%; height:96%; }
.orbit-two { width:70%; height:70%; }
.orbit-three { width:44%; height:44%; border-color:rgba(177,201,239,.34); }
.hero-core {
  position:absolute; left:50%; top:50%; width:11rem; height:11rem; display:flex; align-items:center; justify-content:center;
  border:1px solid rgba(99,142,203,.27); border-radius:2rem;
  background:linear-gradient(135deg,rgba(255,255,255,.9),rgba(213,222,239,.55));
  color:var(--brand); box-shadow:0 25px 70px rgba(57,88,134,.14); transform:translate(-50%,-50%) rotate(12deg);
  transition:transform .5s cubic-bezier(.16,1,.3,1);
}
:global(html.dark) .hero-core { background:linear-gradient(135deg,rgba(15,23,42,.95),rgba(30,41,59,.78)); color:#B1C9EF; box-shadow:0 25px 70px rgba(0,0,0,.32); }
.hero-visual:hover .hero-core { transform:translate(-50%,-50%) rotate(0) scale(1.04); }
.hero-core svg { width:4.1rem; height:4.1rem; }
.hero-orbit-label {
  position:absolute; display:inline-flex; align-items:center; justify-content:center; min-width:3.2rem; min-height:1.55rem;
  padding:.25rem .45rem; border:1px solid rgba(99,142,203,.2); border-radius:999px; background:var(--surface); color:var(--brand);
  box-shadow:0 10px 22px rgba(15,23,42,.08); font-size:.44rem; font-weight:800; letter-spacing:.1em;
}
.label-one{left:7%;top:28%}.label-two{right:3%;top:48%}.label-three{left:23%;bottom:9%}

.progress-ribbon { border-top:1px solid var(--border); border-bottom:1px solid var(--border); background:var(--surface); }
.progress-inner { width:min(100%,1160px); margin:0 auto; padding:1.15rem 1rem; display:flex; align-items:center; justify-content:space-between; gap:2rem; }
.level-block { display:flex; align-items:center; gap:.75rem; min-width:0; flex:1; }
.level-badge { width:3.7rem; height:3.7rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; border:2px solid rgba(99,142,203,.25); border-radius:1rem; background:rgba(99,142,203,.08); color:var(--brand); font-size:1.35rem; font-weight:800; }
:global(html.dark) .level-badge { color:#B1C9EF; background:rgba(99,142,203,.11); }
.level-copy { width:min(100%,360px); min-width:0; }
.level-title-row { display:flex; justify-content:space-between; gap:1rem; margin-bottom:.38rem; color:var(--text-muted); font-size:.5rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
.level-title-row strong { color:var(--text); font-size:.55rem; letter-spacing:0; }
.xp-track { height:.45rem; overflow:hidden; border-radius:999px; background:var(--track); }
.xp-value { height:100%; border-radius:inherit; background:linear-gradient(90deg,var(--brand),var(--brand-mid)); transition:width .7s cubic-bezier(.16,1,.3,1); }
.level-copy p { margin-top:.3rem; color:var(--text-muted); font-size:.45rem; }
.stat-group { display:flex; align-items:center; gap:1.4rem; }
.game-stat { min-width:4.1rem; }
.game-stat span { display:block; color:var(--text-muted); font-size:.65rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
.game-stat strong { color:var(--text); font-size:1.6rem; line-height:1; font-weight:800; }
.game-stat small { color:var(--text-muted); font-size:.65rem; }
.stat-separator { width:1px; height:2rem; background:var(--border); }

.game-container { width:min(100%,1160px); margin:0 auto; padding:3rem 1rem 7rem; display:grid; grid-template-columns:minmax(0,1.65fr) minmax(300px,.85fr); gap:3rem; }
.game-main-column,.game-side-column { min-width:0; }
.game-main-column,.game-side-column { display:flex; flex-direction:column; gap:3rem; }
.content-section,.side-section{min-width:0}
.section-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:1rem; margin-bottom:.85rem; }
.section-heading.compact{margin-bottom:.7rem}
.section-label { display:inline-block; color:var(--sa-text-secondary); background:var(--sa-surface-muted); font-size:0.75rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; padding:6px 14px; border-radius:9999px; border:1px solid var(--sa-border); margin-bottom:8px; }
.section-heading h2 { margin-top:.2rem; color:var(--text); font-size:1.5rem; line-height:1.1; font-weight:800; letter-spacing:-.03em; }
.section-note { color:var(--text-muted); font-size:.65rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; }
.reward-pill,.xp-pill { display:inline-flex; align-items:center; padding:.34rem .52rem; border:1px solid rgba(99,142,203,.2); border-radius:999px; background:rgba(99,142,203,.07); color:var(--brand-mid); font-size:.7rem; font-weight:800; }

.daily-challenge { position:relative; overflow:hidden; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1.5rem; padding:1.35rem; border:1px solid var(--border); border-radius:1.35rem; background:var(--surface); box-shadow:0 12px 30px rgba(15,23,42,.08); transition:.25s ease; opacity: 1; z-index: 2; }
.daily-challenge:hover { transform:translateY(-2px); box-shadow:0 18px 40px rgba(15,23,42,.12); border-color:var(--border-strong); }
.daily-challenge::after { content:''; position:absolute; width:16rem; height:16rem; right:-7rem; top:-8rem; border-radius:50%; background:rgba(99,142,203,.1); filter:blur(4rem); pointer-events:none; }
.daily-content{position:relative;z-index:2}.challenge-kicker{display:inline-flex;color:var(--brand-mid);font-size:.65rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
.daily-content h3{margin-top:.5rem;color:var(--text);font-size:1.6rem;font-weight:800;letter-spacing:-.03em}.daily-content p{max-width:620px;margin-top:.45rem;color:var(--text-muted);font-size:.85rem;line-height:1.65}
.challenge-meta{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.8rem}.challenge-meta span{padding:.3rem .48rem;border:1px solid var(--border);border-radius:999px;color:var(--text-muted);font-size:.65rem;font-weight:700;background:var(--surface)}
.daily-action{position:relative;z-index:2;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.8rem;min-width:8.5rem}
.challenge-ring{width:6.3rem;height:6.3rem;display:flex;align-items:center;justify-content:center;border:5px solid rgba(99,142,203,.12);border-top-color:var(--brand-mid);border-right-color:var(--brand);border-radius:50%;transform:rotate(-25deg)}
.challenge-ring-inner{width:4.7rem;height:4.7rem;display:flex;align-items:center;justify-content:center;border-radius:50%;background:var(--surface);color:var(--brand);font-size:.9rem;font-weight:800;transform:rotate(25deg)}
.challenge-button{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;width:100%;min-height:2.7rem;padding:.6rem .8rem;border-radius:.7rem;background:var(--brand);color:#fff;font-size:.85rem;font-weight:800;text-decoration:none;transition:.2s ease}.challenge-button:hover{transform:translateY(-2px);background:var(--brand-mid)}.challenge-button svg{width:1rem;height:1rem}

.game-mode-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.mode-card{position:relative;min-width:0;min-height:15.5rem;display:flex;flex-direction:column;padding:1rem;border:1px solid var(--border);border-radius:1.1rem;background:var(--surface);color:var(--text);text-decoration:none;transition:.25s ease}.mode-card.available:hover{transform:translateY(-4px);border-color:var(--border-strong);box-shadow:0 16px 34px rgba(15,23,42,.06)}.mode-card.disabled{opacity:.62;cursor:not-allowed}
.mode-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.6rem}.mode-icon{width:2.7rem;height:2.7rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(99,142,203,.13);border-radius:.75rem;background:rgba(99,142,203,.07);color:var(--brand-mid);transition:.25s cubic-bezier(.34,1.56,.64,1)}.mode-card.available:hover .mode-icon{transform:scale(1.08) rotate(3deg)}.mode-icon svg{width:1.25rem;height:1.25rem}.mode-card.disabled .mode-icon{color:var(--text-muted);background:var(--surface-muted)}
.mode-content{flex:1;margin-top:1.3rem}.mode-content h3{color:var(--text);font-size:1.2rem;font-weight:800}.mode-content p{margin-top:.42rem;color:var(--text-muted);font-size:.8rem;line-height:1.65}.mode-footer{display:flex;align-items:center;justify-content:space-between;gap:.6rem;margin-top:1rem;padding-top:.7rem;border-top:1px solid var(--border)}.mode-meta{display:flex;align-items:center;flex-wrap:wrap;gap:.65rem}.mode-meta span{display:inline-flex;align-items:center;gap:.25rem;color:var(--text-muted);font-size:.65rem;font-weight:700}.mode-meta svg{width:.8rem;height:.8rem}.mode-arrow{display:flex;color:var(--brand-mid);opacity:0;transform:translateX(-3px);transition:.2s ease}.mode-card.available:hover .mode-arrow{opacity:1;transform:translateX(0)}.mode-arrow svg{width:1.2rem;height:1.2rem}.coming-soon{padding:.3rem .45rem;border:1px solid var(--border);border-radius:999px;background:var(--surface-muted);color:var(--text-muted);font-size:.55rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.development-note{color:var(--text-muted);font-size:.6rem;font-weight:700}

.leaderboard-card{overflow:hidden;border:1px solid var(--border);border-radius:1.1rem;background:var(--surface);box-shadow:0 8px 26px rgba(15,23,42,.035)}
.achievement-card{border:1px solid var(--border);border-radius:1.1rem;background:var(--surface);box-shadow:0 8px 26px rgba(15,23,42,.035)}
.leaderboard-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem;border-bottom:1px solid var(--border);color:var(--text-muted);font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.status-label{color:var(--brand-mid);font-size:.65rem;letter-spacing:.07em}
.leaderboard-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem 1rem;text-align:center}.sync-icon{width:2.6rem;height:2.6rem;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:.8rem;background:var(--surface-soft);color:var(--brand-mid)}.sync-icon svg{width:1.3rem;height:1.3rem}.leaderboard-empty strong{margin-top:.7rem;color:var(--text);font-size:.9rem;font-weight:800}.leaderboard-empty p{max-width:250px;margin-top:.35rem;color:var(--text-muted);font-size:.75rem;line-height:1.6}
.current-player{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.75rem .9rem;border-top:1px solid var(--border);background:var(--surface-soft)}.player-identity{display:flex;align-items:center;gap:.55rem}.player-rank{color:var(--text-muted);font-size:.7rem;font-weight:800}.player-avatar{width:2.5rem;height:2.5rem;display:flex;align-items:center;justify-content:center;border:1px solid rgba(99,142,203,.18);border-radius:50%;background:var(--brand);color:#fff;font-size:.9rem;font-weight:800}.player-identity strong{display:block;color:var(--text);font-size:.8rem;font-weight:800}.player-identity span{display:block;margin-top:.1rem;color:var(--text-muted);font-size:.65rem}.player-xp{color:var(--brand-mid);font-size:.8rem}

.achievement-card{padding:.85rem}.achievement-grid{display:flex;flex-wrap:nowrap;overflow-x:auto;gap:.45rem;padding-bottom:.5rem}.achievement{flex:0 0 auto;width:4.5rem;position:relative;aspect-ratio:1;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:.75rem;background:var(--surface-muted);color:var(--text-muted);opacity:.55;transition:.22s ease}.achievement.earned{opacity:1;color:var(--brand);background:rgba(99,142,203,.08);border-color:rgba(99,142,203,.2)}:global(html.dark) .achievement.earned{color:#B1C9EF;background:rgba(99,142,203,.12)}.achievement:hover{transform:translateY(-2px);border-color:var(--border-strong);opacity:1}.achievement-icon svg{width:1.25rem;height:1.25rem}
.achievement-tooltip{position:absolute;left:50%;bottom:calc(100% + .5rem);width:14rem;padding:.65rem;border:1px solid var(--border-strong);border-radius:.7rem;background:var(--surface);color:var(--text);box-shadow:0 15px 35px rgba(15,23,42,.12);opacity:0;visibility:hidden;pointer-events:none;transform:translate(-50%,4px);transition:.18s ease}.achievement:hover .achievement-tooltip{opacity:1;visibility:visible;transform:translate(-50%,0)}.achievement-tooltip strong{display:block;color:var(--text);font-size:.8rem;font-weight:800}.achievement-tooltip small{display:block;margin-top:.22rem;color:var(--text-muted);font-size:.65rem;line-height:1.45}
.view-all{color:var(--text-muted);font-size:.65rem;font-weight:800;text-decoration:none;letter-spacing:.07em;text-transform:uppercase}.view-all:hover{color:var(--brand-mid)}
.achievement-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1.6rem 1rem;text-align:center}.achievement-empty-icon{width:2.6rem;height:2.6rem;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:.8rem;background:var(--surface-soft);color:var(--brand-mid)}.achievement-empty-icon svg{width:1.3rem;height:1.3rem}.achievement-empty strong{margin-top:.6rem;color:var(--text);font-size:.9rem;font-weight:800}.achievement-empty p{max-width:250px;margin-top:.35rem;color:var(--text-muted);font-size:.75rem;line-height:1.55}

.city-footer{position:relative;z-index:10;overflow:hidden;background-color:#243A5A !important;opacity:1 !important;color:#D5DEEF;border-top:1px solid rgba(255,255,255,.08)}.city-footer-main{width:min(100%,1160px);margin:0 auto;padding:2.5rem 1rem 2rem;display:grid;grid-template-columns:1.45fr 1.3fr .9fr .9fr .9fr;gap:1.5rem}.footer-brand{display:flex;align-items:flex-start;gap:.65rem}.footer-logo{width:2.4rem;height:2.4rem;display:flex;align-items:center;justify-content:center;padding:.25rem;border-radius:.62rem;background:#fff}.footer-logo img{width:100%;height:100%;object-fit:contain}.footer-brand strong{display:block;color:#fff;font-size:.82rem;font-weight:800}.footer-brand span{display:block;margin-top:.12rem;color:#B1C9EF;font-size:.42rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}.footer-description{max-width:270px;color:#B1C9EF;font-size:.49rem;line-height:1.7}.footer-column{display:flex;flex-direction:column;align-items:flex-start;gap:.45rem}.footer-column h3{margin-bottom:.15rem;color:#fff;font-size:.45rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.footer-column a,.footer-column span{color:#B1C9EF;font-size:.47rem;font-weight:600;text-decoration:none;transition:color .18s ease}.footer-column a:hover{color:#fff}.city-government-bar{border-top:1px solid rgba(255,255,255,.09);border-bottom:1px solid rgba(255,255,255,.09);background:#1F334F}.city-government-inner{width:min(100%,1160px);margin:0 auto;padding:.7rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}.government-identity{display:flex;align-items:center;gap:.4rem;color:#D5DEEF;font-size:.43rem;font-weight:700}.government-line{width:3px;height:1rem;border-radius:2px;background:#638ECB}.government-separator{color:#638ECB}.government-message{color:#8AAEE0;font-size:.41rem;font-weight:600}.city-footer-bottom{width:min(100%,1160px);margin:0 auto;padding:.72rem 1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;color:#8AAEE0;font-size:.4rem}

@media (max-width:1024px){.hero-grid{grid-template-columns:1fr;gap:2rem}.hero-visual{max-width:320px}.game-container{grid-template-columns:1fr}.game-side-column{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));align-items:start;gap:2rem}.city-footer-main{grid-template-columns:1.4fr 1.2fr .8fr}}
@media (max-width:768px){
  .game-hero{padding:5.5rem 1rem 3rem}
  .hero-copy h1{font-size:3.2rem}
  .hero-actions{flex-direction:row;justify-content:center;gap:.5rem}
  .hero-primary,.hero-secondary{width:auto;flex:1;min-height:2.4rem;padding:.5rem .65rem;font-size:.78rem}
  .hero-visual{max-width:280px}
  .progress-inner{align-items:stretch;flex-direction:column}
  .stat-group{justify-content:space-between}
  .game-container{padding:2rem 0.5rem 3rem}
  .daily-challenge{grid-template-columns:minmax(0, 1.4fr) minmax(0, 1fr);gap:.65rem;padding:.85rem;border-radius:.9rem}
  .daily-content h3{font-size:1.05rem}
  .daily-content p{font-size:.7rem;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .challenge-meta{gap:.3rem;margin-top:.5rem}
  .challenge-meta span{padding:.2rem .35rem;font-size:.55rem}
  .daily-action{min-width:auto;gap:.4rem}
  .challenge-ring{width:3.8rem;height:3.8rem;border-width:3px}
  .challenge-ring-inner{width:2.8rem;height:2.8rem;font-size:.75rem}
  .challenge-button{min-height:2.1rem;padding:.35rem .5rem;font-size:.7rem;border-radius:.55rem}
  .game-mode-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.45rem}
  .mode-card{min-height:auto;padding:.65rem;border-radius:.85rem}
  .mode-icon{width:2.2rem;height:2.2rem;border-radius:.6rem}
  .mode-icon svg{width:1.05rem;height:1.05rem}
  .mode-content{margin-top:.6rem}
  .mode-content h3{font-size:.85rem}
  .mode-content p{font-size:.68rem;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .mode-footer{margin-top:.5rem;padding-top:.4rem}
  .mode-meta{gap:.35rem}
  .mode-meta span{font-size:.55rem}
  .city-footer-main{grid-template-columns:1.2fr 0.9fr 0.9fr 0.9fr;gap:.45rem;padding:1.5rem .6rem 1rem}
  .footer-brand{grid-column:auto}
  .city-government-inner{align-items:center;flex-direction:row;justify-content:space-between}
  .city-footer-bottom{align-items:center;flex-direction:row;justify-content:space-between}
}
@media (max-width:640px){
  .hero-copy h1{font-size:2.65rem}
  .hero-orbit-label{display:none}
  .game-stat{min-width:auto}
  .stat-group{gap:.6rem}
  .stat-separator{height:1.5rem}
  .city-footer-main{grid-template-columns:1.2fr 0.9fr 0.9fr 0.9fr;gap:.45rem}
}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important}}
</style>
