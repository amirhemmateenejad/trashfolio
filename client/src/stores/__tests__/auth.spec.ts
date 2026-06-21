import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../auth'

// Stub localStorage
const localStorageMock = (() => {
  let store: Record<string, string> = {}
  return {
    getItem: (key: string) => store[key] ?? null,
    setItem: (key: string, value: string) => { store[key] = value },
    removeItem: (key: string) => { delete store[key] },
    clear: () => { store = {} },
  }
})()

Object.defineProperty(globalThis, 'localStorage', { value: localStorageMock })

describe('useAuthStore', () => {
  beforeEach(() => {
    localStorageMock.clear()
    setActivePinia(createPinia())
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('isLoggedIn is false when no token', () => {
    const auth = useAuthStore()
    expect(auth.isLoggedIn).toBe(false)
    expect(auth.token).toBeNull()
  })

  it('setToken persists token and marks isLoggedIn true', () => {
    const auth = useAuthStore()
    auth.setToken('my-test-token')
    expect(auth.token).toBe('my-test-token')
    expect(auth.isLoggedIn).toBe(true)
    expect(localStorageMock.getItem('token')).toBe('my-test-token')
  })

  it('setUser stores user data', () => {
    const auth = useAuthStore()
    auth.setUser({ id: 1, name: 'Ali', mobile: '09123456789' })
    expect(auth.user).toEqual({ id: 1, name: 'Ali', mobile: '09123456789' })
  })

  it('logout clears token, user, and localStorage', () => {
    const auth = useAuthStore()
    auth.setToken('abc')
    auth.setUser({ id: 1, name: 'Ali', mobile: '09123456789' })

    auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(auth.isLoggedIn).toBe(false)
    expect(localStorageMock.getItem('token')).toBeNull()
  })

  it('reads existing token from localStorage on store init', () => {
    localStorageMock.setItem('token', 'persisted-token')
    // Re-create pinia so store re-initialises from localStorage
    setActivePinia(createPinia())
    const auth = useAuthStore()
    expect(auth.token).toBe('persisted-token')
    expect(auth.isLoggedIn).toBe(true)
  })

  it('user is null by default', () => {
    const auth = useAuthStore()
    expect(auth.user).toBeNull()
  })
})
