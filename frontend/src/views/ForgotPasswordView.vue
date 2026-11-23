<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { api } from '../api/client'

const email = ref('')
const error = ref('')
const success = ref('')
const devResetUrl = ref('')
const loading = ref(false)

async function onSubmit() {
  error.value = ''
  success.value = ''
  devResetUrl.value = ''
  loading.value = true
  try {
    const data = await api.post('/api/auth/forgot-password', {
      usuario_email: email.value.trim(),
    })
    success.value = data.message || 'Revisá tu correo para continuar.'
    if (data.dev_reset_url) {
      devResetUrl.value = data.dev_reset_url
    }
  } catch (e) {
    error.value = e.message || 'No se pudo enviar la solicitud'
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
        <h1 class="h4 mb-1 text-carioca">Recuperar contraseña</h1>
        <p class="text-secondary small mb-4">
          Ingresá el email de tu cuenta y te enviaremos un enlace para restablecerla.
        </p>

        <div v-if="error" class="alert alert-danger py-2" role="alert">{{ error }}</div>
        <div v-if="success" class="alert alert-success py-2" role="alert">
          {{ success }}
          <div v-if="devResetUrl" class="mt-2 small">
            <a :href="devResetUrl">Abrir enlace de desarrollo</a>
          </div>
        </div>

        <form v-if="!success || devResetUrl" @submit.prevent="onSubmit">
          <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              class="form-control form-control-lg"
              autocomplete="email"
              required
            />
          </div>
          <button class="btn btn-carioca btn-lg w-100" type="submit" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Enviar enlace
          </button>
        </form>

        <p class="text-center small mt-3 mb-0">
          <RouterLink to="/login">Volver al inicio de sesión</RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>
