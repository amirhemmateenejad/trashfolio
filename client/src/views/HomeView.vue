<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { RouterLink } from 'vue-router'

const auth = useAuthStore()

const features = [
  {
    icon: '📁',
    title: 'Organized Projects',
    description: 'Group your snippets into projects with nested folder trees as deep as you need.',
  },
  {
    icon: '⚡',
    title: 'Full-Text Search',
    description: 'Find any snippet instantly with Meilisearch-powered full-text search across title, content, and tags.',
  },
  {
    icon: '🏷️',
    title: 'Tags & Filters',
    description: 'Annotate snippets with color-coded tags and filter by language, folder, or tag in one click.',
  },
  {
    icon: '🔒',
    title: 'Private by Default',
    description: 'OTP-only login — no passwords to leak. Every snippet is strictly yours.',
  },
  {
    icon: '🗑️',
    title: 'Trash & Restore',
    description: "Soft-delete anything and restore it later. Permanently purge when you're sure.",
  },
  {
    icon: '🌐',
    title: 'Open REST API',
    description: 'Every feature is available via a documented REST API with Swagger UI at /api/docs.',
  },
]
</script>

<template>
  <div class="landing">
    <!-- Nav -->
    <nav class="nav">
      <span class="nav-brand">🗑️ Trashfolio</span>
      <div class="nav-actions">
        <RouterLink v-if="auth.isLoggedIn" to="/dashboard" class="btn btn-primary" title="Go to dashboard">
          ⚡ Dashboard
        </RouterLink>
        <RouterLink v-else to="/login" class="btn btn-primary">
          Login
        </RouterLink>
      </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
      <h1 class="hero-title">
        Your personal<br />
        <span class="accent">snippet manager</span>
      </h1>
      <p class="hero-subtitle">
        Stop scattering code snippets across notes apps, Gists, and sticky notes.
        Trashfolio keeps every snippet organized, searchable, and always yours.
      </p>
      <div class="hero-actions">
        <RouterLink v-if="auth.isLoggedIn" to="/dashboard" class="btn btn-primary btn-lg">
          Open Dashboard
        </RouterLink>
        <RouterLink v-else to="/login" class="btn btn-primary btn-lg">
          Get Started — It's Free
        </RouterLink>
      </div>
    </section>

    <!-- Features -->
    <section class="features">
      <h2 class="section-title">Everything you need, nothing you don't</h2>
      <div class="features-grid">
        <div v-for="f in features" :key="f.title" class="feature-card">
          <span class="feature-icon">{{ f.icon }}</span>
          <h3 class="feature-title">{{ f.title }}</h3>
          <p class="feature-desc">{{ f.description }}</p>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="cta">
      <h2>Ready to organize your code?</h2>
      <RouterLink v-if="auth.isLoggedIn" to="/dashboard" class="btn btn-cta btn-lg">
        Go to Dashboard
      </RouterLink>
      <RouterLink v-else to="/login" class="btn btn-cta btn-lg">
        Sign in with your mobile number
      </RouterLink>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <p>
        Trashfolio — Personal Snippet Manager ·
        <a href="/api/documentation" target="_blank" rel="noopener">API Docs</a>
      </p>
    </footer>
  </div>
</template>

<style scoped>
.landing {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: system-ui, sans-serif;
  color: #1a1a2e;
  background: #f8f9ff;
}

/* Nav */
.nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  border-bottom: 1px solid #e2e6ff;
  background: #fff;
  position: sticky;
  top: 0;
  z-index: 10;
}

.nav-brand {
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: #3d5afe;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  text-decoration: none;
  transition:
    background 0.15s,
    transform 0.1s;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: #3d5afe;
  color: #fff;
}

.btn-primary:hover {
  background: #2a3fc7;
  transform: translateY(-1px);
}

.btn-cta {
  background: #fff;
  color: #3d5afe;
  font-weight: 700;
}

.btn-cta:hover {
  background: #f0f3ff;
}

.btn-lg {
  padding: 0.75rem 2rem;
  font-size: 1.05rem;
  border-radius: 10px;
}

/* Hero */
.hero {
  text-align: center;
  padding: 6rem 2rem 4rem;
  max-width: 700px;
  margin: 0 auto;
}

.hero-title {
  font-size: clamp(2.4rem, 6vw, 3.8rem);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.03em;
  margin: 0 0 1.25rem;
}

.accent {
  color: #3d5afe;
}

.hero-subtitle {
  font-size: 1.15rem;
  color: #555;
  line-height: 1.7;
  margin: 0 0 2.5rem;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Features */
.features {
  padding: 4rem 2rem;
  max-width: 1000px;
  margin: 0 auto;
  width: 100%;
}

.section-title {
  text-align: center;
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0 0 2.5rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}

.feature-card {
  background: #fff;
  border: 1px solid #e2e6ff;
  border-radius: 12px;
  padding: 1.75rem;
  transition:
    box-shadow 0.15s,
    transform 0.15s;
}

.feature-card:hover {
  box-shadow: 0 8px 24px rgba(61, 90, 254, 0.1);
  transform: translateY(-2px);
}

.feature-icon {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.75rem;
}

.feature-title {
  font-size: 1.05rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.feature-desc {
  font-size: 0.92rem;
  color: #666;
  line-height: 1.65;
  margin: 0;
}

/* CTA */
.cta {
  text-align: center;
  padding: 5rem 2rem;
  background: linear-gradient(135deg, #3d5afe 0%, #2a3fc7 100%);
  color: #fff;
  margin-top: auto;
}

.cta h2 {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 2rem;
}

/* Footer */
.footer {
  text-align: center;
  padding: 1.5rem;
  font-size: 0.85rem;
  background: #1a1a2e;
  color: #aaa;
}

.footer a {
  color: #7b96ff;
  text-decoration: none;
}

.footer a:hover {
  text-decoration: underline;
}

@media (prefers-color-scheme: dark) {
  .landing {
    background: #0d0d1a;
    color: #e8eaff;
  }

  .nav {
    background: #12122a;
    border-color: #2a2a4a;
  }

  .nav-brand {
    color: #7b96ff;
  }

  .hero-subtitle {
    color: #aab;
  }

  .feature-card {
    background: #12122a;
    border-color: #2a2a4a;
  }

  .feature-desc {
    color: #99a;
  }

  .footer {
    background: #080812;
  }
}
</style>
