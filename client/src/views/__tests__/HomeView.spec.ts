import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { createRouter, createMemoryHistory } from 'vue-router'
import HomeView from '../HomeView.vue'
import { useAuthStore } from '@/stores/auth'

function makeRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', component: HomeView },
      { path: '/login', component: { template: '<div>login</div>' } },
      { path: '/dashboard', component: { template: '<div>dashboard</div>' } },
    ],
  })
}

async function mountHome(loggedIn = false) {
  const pinia = createPinia()
  setActivePinia(pinia)

  if (loggedIn) {
    const auth = useAuthStore()
    auth.setToken('fake-token')
    auth.setUser({ id: 1, name: 'Ali', mobile: '09123456789' })
  }

  const router = makeRouter()
  await router.push('/')
  await router.isReady()

  return mount(HomeView, {
    global: { plugins: [pinia, router] },
  })
}

describe('HomeView', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('renders the brand name', async () => {
    const wrapper = await mountHome()
    expect(wrapper.text()).toContain('Trashfolio')
  })

  it('renders the hero headline', async () => {
    const wrapper = await mountHome()
    expect(wrapper.text()).toContain('snippet manager')
  })

  it('renders all 6 feature cards', async () => {
    const wrapper = await mountHome()
    const cards = wrapper.findAll('.feature-card')
    expect(cards).toHaveLength(6)
  })

  it('shows Login link when user is NOT logged in', async () => {
    const wrapper = await mountHome(false)
    const links = wrapper.findAll('a')
    const texts = links.map((l) => l.text())
    expect(texts.some((t) => t.toLowerCase().includes('login') || t.toLowerCase().includes('get started'))).toBe(true)
  })

  it('shows Dashboard link instead of Login when user IS logged in', async () => {
    const wrapper = await mountHome(true)
    const links = wrapper.findAll('a')
    const texts = links.map((l) => l.text())
    expect(texts.some((t) => t.toLowerCase().includes('dashboard'))).toBe(true)
    expect(texts.some((t) => t.toLowerCase() === 'login')).toBe(false)
  })

  it('nav has the brand name', async () => {
    const wrapper = await mountHome()
    expect(wrapper.find('.nav-brand').text()).toContain('Trashfolio')
  })

  it('has a footer with API docs link', async () => {
    const wrapper = await mountHome()
    const footer = wrapper.find('.footer')
    expect(footer.exists()).toBe(true)
    expect(footer.text()).toContain('API Docs')
  })

  it('has a CTA section', async () => {
    const wrapper = await mountHome()
    expect(wrapper.find('.cta').exists()).toBe(true)
  })
})
