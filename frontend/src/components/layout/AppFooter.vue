<template>
  <footer class="app-footer">
    <div class="footer-container">
      <div class="footer-grid">
        <!-- BRAND -->
        <div class="footer-brand">
          <div class="footer-brand-row">
            <div class="footer-logo">
              <img src="/logo.png" alt="Smart Adama Logo" />
            </div>
            <div>
              <div class="footer-brand-title">{{ $t('nav.brand') }}</div>
              <div class="footer-brand-subtitle">{{ $t('landing.mayor.slogan') }}</div>
            </div>
          </div>
          <p>{{ $t('landing.footer.tagline') }}</p>
        </div>

        <!-- ECOSYSTEM -->
        <div class="footer-column">
          <h4>{{ $t('landing.footer.ecosystem') }}</h4>
          <router-link to="/study">{{ $t('landing.footer.study') }}</router-link>
          <router-link to="/game">{{ $t('landing.footer.game') }}</router-link>
          <router-link to="/language">{{ $t('landing.footer.language') }}</router-link>
          <a href="https://giknfkibnyzrlegxraio.supabase.co/storage/v1/object/public/books/SA-Book.pdf" target="_blank" rel="noopener noreferrer">
            {{ $t('landing.footer.download') }}
          </a>
        </div>

        <!-- LEGAL -->
        <div class="footer-column">
          <h4>{{ $t('landing.footer.legal') }}</h4>
          <router-link to="/about">{{ $t('landing.footer.about') }}</router-link>
          <a href="#">{{ $t('landing.footer.privacy') }}</a>
          <a href="#">{{ $t('landing.footer.terms') }}</a>
          <a href="#">{{ $t('landing.footer.contact') }}</a>
        </div>

        <!-- CITY -->
        <div class="footer-column">
          <h4>{{ $t('landing.mayor.city') }}</h4>
          <span>{{ $t('prof.tag_learn') }}</span>
          <span>{{ $t('prof.tag_tech') }}</span>
          <span>{{ $t('landing.mayor.fw_inno') }}</span>
          <span>{{ $t('prof.tag_comm') }}</span>
        </div>
      </div>

      <div class="footer-bottom">
        <p>{{ $t('landing.footer.copyright') }}</p>
        <span>{{ $t('prof.langs') }}</span>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
const { t } = useI18n()
</script>

<style scoped>
.app-footer {
  position: relative;
  z-index: 50;
  width: 100%;
  background-color: #243A5A !important;
  opacity: 1 !important;
  color: #D5DEEF;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.footer-container {
  width: min(100%, 1180px);
  margin: 0 auto;
  padding: 4rem 1.5rem 1.5rem;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.7fr 0.9fr 0.9fr 0.9fr;
  gap: 3rem;
}

.footer-brand {
  display: flex;
  flex-direction: column;
}

.footer-brand-row {
  display: flex;
  align-items: center;
  gap: 0.7rem;
}

.footer-logo {
  width: 2.3rem;
  height: 2.3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.65rem;
  background: white;
}

.footer-logo img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.footer-brand-title {
  color: white;
  font-size: 1.1rem;
  font-weight: 800;
}

.footer-brand-subtitle {
  margin-top: 0.15rem;
  color: #B1C9EF;
  font-size: 0.43rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.footer-brand p {
  max-width: 360px;
  margin-top: 1rem;
  color: #B1C9EF;
  font-size: 0.6rem;
  line-height: 1.7;
}

.footer-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.6rem;
}

.footer-column h4 {
  margin-bottom: 0.2rem;
  color: white;
  font-size: 0.48rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.footer-column a,
.footer-column span {
  color: #B1C9EF;
  font-size: 0.5rem;
  font-weight: 600;
  text-decoration: none;
  transition: color 0.2s ease;
  width: fit-content;
}

.footer-column a:hover {
  color: white;
}

.footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 3rem;
  padding-top: 1.2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.09);
}

.footer-bottom p,
.footer-bottom span {
  color: #8AAEE0;
  font-size: 0.46rem;
}

@media (max-width: 1024px) {
  .footer-grid {
    grid-template-columns: 1.5fr 1fr 1fr;
  }
}

@media (max-width: 768px) {
  .footer-container {
    padding: 1.8rem 0.75rem 0.8rem;
  }
  .footer-grid {
    grid-template-columns: 1.3fr 0.9fr 0.9fr 0.9fr;
    gap: 0.75rem;
  }
  .footer-brand {
    grid-column: auto;
  }
  .footer-brand p {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.45rem;
    margin-top: 0.4rem;
    line-height: 1.4;
  }
  .footer-logo {
    width: 1.8rem;
    height: 1.8rem;
  }
  .footer-brand-title {
    font-size: 0.8rem;
  }
  .footer-brand-subtitle {
    font-size: 0.35rem;
  }
  .footer-column {
    gap: 0.35rem;
  }
  .footer-column h4 {
    font-size: 0.42rem;
    margin-bottom: 0.15rem;
  }
  .footer-column a,
  .footer-column span {
    font-size: 0.42rem;
  }
  .footer-bottom {
    align-items: center;
    flex-direction: row;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 1.2rem;
    padding-top: 0.6rem;
  }
  .footer-bottom p,
  .footer-bottom span {
    font-size: 0.4rem;
  }
}

@media (max-width: 640px) {
  .footer-container {
    padding: 1.4rem 0.5rem 0.7rem;
  }
  .footer-grid {
    grid-template-columns: 1.2fr 0.9fr 0.9fr 0.9fr;
    gap: 0.45rem;
  }
  .footer-brand-title {
    font-size: 0.7rem;
  }
  .footer-logo {
    width: 1.5rem;
    height: 1.5rem;
    padding: 0.15rem;
  }
  .footer-brand-subtitle {
    display: none;
  }
  .footer-brand p {
    font-size: 0.38rem;
    line-height: 1.3;
    margin-top: 0.25rem;
  }
  .footer-column h4 {
    font-size: 0.38rem;
    letter-spacing: 0.05em;
  }
  .footer-column a,
  .footer-column span {
    font-size: 0.38rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
  }
  .footer-bottom {
    flex-direction: row;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 0.5rem;
  }
  .footer-bottom p,
  .footer-bottom span {
    font-size: 0.36rem;
  }
}
</style>
