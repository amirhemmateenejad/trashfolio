<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { RouterLink } from 'vue-router'

const auth = useAuthStore()
</script>

<template>
  <div class="dashboard-placeholder">
    <h2>Dashboard — Coming in Phase 3C</h2>
    <p v-if="auth.user">Welcome, {{ auth.user.name ?? auth.user.mobile }}</p>
    <div style="display: flex; gap: 1rem; margin-top: 1rem">
      <button @click="auth.logout()" style="cursor: pointer">Logout</button>
      <RouterLink to="/">Home</RouterLink>
    </div>
  </div>
</template>

<style scoped>
.dashboard-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 0.5rem;
  font-family: system-ui, sans-serif;
}
</style>
