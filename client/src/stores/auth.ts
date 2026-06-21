import { ref, computed } from 'vue'
import { defineStore } from 'pinia'

export interface User {
  id: number
  name: string | null
  mobile: string
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<User | null>(null)

  const isLoggedIn = computed(() => !!token.value)

  function setToken(value: string) {
    token.value = value
    localStorage.setItem('token', value)
  }

  function setUser(value: User) {
    user.value = value
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  return { token, user, isLoggedIn, setToken, setUser, logout }
})
