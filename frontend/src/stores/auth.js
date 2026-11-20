import { defineStore } from 'pinia'
import { api } from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loaded: false,
  }),
  getters: {
    isAuthenticated: (s) => !!s.user,
    isAdmin: (s) => s.user?.is_admin === true,
  },
  actions: {
    async fetchMe() {
      try {
        const data = await api.get('/api/auth/me')
        this.user = data.user
      } catch {
        this.user = null
      } finally {
        this.loaded = true
      }
    },
    async login(usuario_nombre, usuario_clave) {
      const data = await api.post('/api/auth/login', { usuario_nombre, usuario_clave })
      this.user = data.user
      this.loaded = true
      return data.user
    },
    async logout() {
      try {
        await api.post('/api/auth/logout')
      } finally {
        this.user = null
      }
    },
  },
})
