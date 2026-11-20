<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const usuario = ref('')
const clave = ref('')
const error = ref('')
const loading = ref(false)

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(usuario.value, clave.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
    await router.replace(redirect)
  } catch (e) {
    error.value = e.message || 'No se pudo iniciar sesión'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-shell">
    <div class="card login-card overflow-hidden">
      <div class="brand-banner">CARIOCA</div>
      <div class="card-body p-4">
        <h1 class="h4 mb-1 text-carioca">Iniciar sesión</h1>
        <p class="text-secondary small mb-4">Inventario y movimientos</p>

        <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>

        <form @submit.prevent="onSubmit">
          <div class="mb-3">
            <label class="form-label" for="usuario">Nombre de usuario</label>
            <input
              id="usuario"
              v-model="usuario"
              type="text"
              class="form-control form-control-lg"
              autocomplete="username"
              required
            />
          </div>
          <div class="mb-4">
            <label class="form-label" for="clave">Contraseña</label>
            <input
              id="clave"
              v-model="clave"
              type="password"
              class="form-control form-control-lg"
              autocomplete="current-password"
              required
            />
          </div>
          <button class="btn btn-carioca btn-lg w-100" type="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Entrar
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
