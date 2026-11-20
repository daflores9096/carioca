<script setup>
import { reactive, ref } from 'vue'
import { api } from '../api/client'
import { useAuthLayout } from '../composables/useAuthLayout'
import AppShell from '../components/AppShell.vue'
import PageHeader from '../components/PageHeader.vue'

const { auth, logout, router } = useAuthLayout()

const pwd = reactive({
  clave_actual: '',
  usuario_clave: '',
  usuario_clave_conf: '',
})
const saving = ref(false)
const error = ref('')
const ok = ref('')

async function changePassword() {
  saving.value = true
  error.value = ''
  ok.value = ''
  try {
    await api.post('/api/auth/password', { ...pwd })
    ok.value = 'Contraseña actualizada.'
    pwd.clave_actual = ''
    pwd.usuario_clave = ''
    pwd.usuario_clave_conf = ''
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader title="Mi cuenta" :subtitle="auth.user?.usuario_nombre || ''">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/')">Volver</button>
      </template>
    </PageHeader>

    <div class="card border-0 shadow-sm" style="max-width: 420px">
      <div class="card-body">
        <h2 class="h6 mb-3">Cambiar contraseña</h2>
        <form class="vstack gap-3" @submit.prevent="changePassword">
          <div>
            <label class="form-label">Contraseña actual</label>
            <input v-model="pwd.clave_actual" type="password" class="form-control" required />
          </div>
          <div>
            <label class="form-label">Nueva contraseña</label>
            <input v-model="pwd.usuario_clave" type="password" class="form-control" required />
          </div>
          <div>
            <label class="form-label">Repetir nueva</label>
            <input v-model="pwd.usuario_clave_conf" type="password" class="form-control" required />
          </div>
          <button class="btn btn-carioca" type="submit" :disabled="saving">
            {{ saving ? 'Guardando…' : 'Guardar' }}
          </button>
        </form>
        <div v-if="error" class="alert alert-danger py-2 mt-3 mb-0">{{ error }}</div>
        <div v-if="ok" class="alert alert-success py-2 mt-3 mb-0">{{ ok }}</div>
      </div>
    </div>
  </AppShell>
</template>
