import { describe, it, expect, beforeEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

const DummyComponent = { template: '<div />' }

function makeRouter() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: DummyComponent },
      { path: '/login', name: 'login', component: DummyComponent, meta: { guestOnly: true } },
      { path: '/dashboard', name: 'dashboard', component: DummyComponent, meta: { requiresAuth: true } },
    ],
  })

  router.beforeEach((to) => {
    const auth = useAuthStore()
    if (to.meta.requiresAuth && !auth.isLoggedIn) return { name: 'login' }
    if (to.meta.guestOnly && auth.isLoggedIn) return { name: 'dashboard' }
  })

  return router
}

describe('router guards', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('redirects unauthenticated user from /dashboard to /login', async () => {
    const router = makeRouter()
    await router.push('/dashboard')
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('allows authenticated user to reach /dashboard', async () => {
    const auth = useAuthStore()
    auth.setToken('tok')
    const router = makeRouter()
    await router.push('/dashboard')
    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('redirects authenticated user from /login to /dashboard', async () => {
    const auth = useAuthStore()
    auth.setToken('tok')
    const router = makeRouter()
    await router.push('/login')
    expect(router.currentRoute.value.name).toBe('dashboard')
  })

  it('allows unauthenticated user to reach /login', async () => {
    const router = makeRouter()
    await router.push('/login')
    expect(router.currentRoute.value.name).toBe('login')
  })

  it('allows anyone to reach home /', async () => {
    const router = makeRouter()
    await router.push('/')
    expect(router.currentRoute.value.name).toBe('home')
  })
})
