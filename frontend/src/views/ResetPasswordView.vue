<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { api } from '../api/client'

const route = useRoute()
const router = useRouter()

const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''))
const clave = ref('')
const claveConf = ref('')
const showPassword = ref(false)
const error = ref('')
const success = ref('')
const loading = ref(false)

async function onSubmit() {
  error.value = ''
  success.value = ''
  if (!token.value) {
    error.value = 'Enlace inválido o incompleto.'
    return
  }
  if (clave.value !== claveConf.value) {
    error.value = 'Las contraseñas no coinciden.'
    return
  }
  loading.value = true
  try {
    await api.post('/api/auth/reset-password', {
      token: token.value,
      usuario_clave: clave.value,
      usuario_clave_conf: claveConf.value,
    })
    success.value = 'Contraseña actualizada. Ya podés iniciar sesión.'
    setTimeout(() => router.replace('/login'), 1500)
  } catch (e) {
    error.value = e.message || 'No se pudo restablecer la contraseña'
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
        <h1 class="h4 mb-1 text-carioca">Nueva contraseña</h1>
        <p class="text-secondary small mb-4">Elegí una contraseña nueva para tu cuenta.</p>

        <div v-if="!token" class="alert alert-warning py-2" role="alert">
          El enlace no incluye un token válido. Solicitá uno nuevo desde
          <RouterLink to="/forgot-password">recuperar contraseña</RouterLink>.
        </div>

        <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>
        <div v-if="success" class="alert alert-success py-2" role="alert">{{ success }}</div>

        <form v-if="token && !success" @submit.prevent="onSubmit">
          <div class="mb-3">
            <label class="form-label" for="clave">Nueva contraseña</label>
            <div class="input-group input-group-lg">
              <input
                id="clave"
                v-model="clave"
                :type="showPassword ? 'text' : 'password'"
                class="form-control"
                autocomplete="new-password"
                minlength="6"
                required
              />
              <button
                type="button"
                class="btn btn-outline-secondary"
                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label" for="claveConf">Confirmar contraseña</label>
            <input
              id="claveConf"
              v-model="claveConf"
              :type="showPassword ? 'text' : 'password'"
              class="form-control form-control-lg"
              autocomplete="new-password"
              minlength="6"
              required
            />
          </div>
          <button class="btn btn-carioca btn-lg w-100" type="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Guardar contraseña
          </button>
        </form>

        <p class="text-center small mt-3 mb-0">
          <RouterLink to="/login">Volver al inicio de sesión</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
