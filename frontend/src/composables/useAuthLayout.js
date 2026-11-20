import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

export function useAuthLayout() {
  const auth = useAuthStore()
  const router = useRouter()

  async function logout() {
    await auth.logout()
    await router.push({ name: 'login' })
  }

  return { auth, router, logout }
}
