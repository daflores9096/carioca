<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { logout } = useAuthLayout()
const router = useRouter()

const nombre = ref('')
const downloading = ref(false)
const backupError = ref('')
const backupOk = ref('')

const pwd = reactive({
  clave_actual: '',
  usuario_clave: '',
  usuario_clave_conf: '',
})
const pwdSaving = ref(false)
const pwdError = ref('')
const pwdOk = ref('')

async function downloadBackup() {
  downloading.value = true
  backupError.value = ''
  backupOk.value = ''
  try {
    const q = nombre.value ? `?nombre=${encodeURIComponent(nombre.value)}` : ''
    const res = await fetch(`/api/respaldos/download${q}`, {
      credentials: 'include',
    })
    if (!res.ok) {
      let msg = `Error HTTP ${res.status}`
      try {
        const data = await res.json()
        msg = data.error || msg
      } catch {
        /* ignore */
      }
      throw new Error(msg)
    }
    const blob = await res.blob()
    const cd = res.headers.get('Content-Disposition') || ''
    const match = /filename="?([^"]+)"?/i.exec(cd)
    const filename = match?.[1] || `carioca_${new Date().toISOString().slice(0, 10)}.sql`
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    a.remove()
    URL.revokeObjectURL(url)
    backupOk.value = `Respaldo descargado: ${filename}`
  } catch (e) {
    const msg = e?.message || 'Error de red'
    backupError.value =
      msg === 'Failed to fetch'
        ? 'No se pudo completar la descarga (tiempo de espera o red). Probá de nuevo.'
        : msg
  } finally {
    downloading.value = false
  }
}

async function changePassword() {
  pwdSaving.value = true
  pwdError.value = ''
  pwdOk.value = ''
  try {
    await api.post('/api/auth/password', { ...pwd })
    pwdOk.value = 'Contraseña actualizada.'
    pwd.clave_actual = ''
    pwd.usuario_clave = ''
    pwd.usuario_clave_conf = ''
  } catch (e) {
    pwdError.value = e.message
  } finally {
    pwdSaving.value = false
  }
}
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader title="Administración" subtitle="Usuarios, contraseña y respaldos" />

    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h2 class="h6">Usuarios</h2>
            <p class="small text-secondary">Alta, roles y restablecer claves.</p>
            <button class="btn btn-carioca" type="button" @click="router.push('/admin/usuarios')">
              Gestionar usuarios
            </button>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h2 class="h6">Mi contraseña</h2>
            <form class="vstack gap-2" @submit.prevent="changePassword">
              <input v-model="pwd.clave_actual" type="password" class="form-control form-control-sm" placeholder="Actual" required />
              <input v-model="pwd.usuario_clave" type="password" class="form-control form-control-sm" placeholder="Nueva" required />
              <input v-model="pwd.usuario_clave_conf" type="password" class="form-control form-control-sm" placeholder="Repetir nueva" required />
              <button class="btn btn-sm btn-carioca" type="submit" :disabled="pwdSaving">
                {{ pwdSaving ? 'Guardando…' : 'Cambiar' }}
              </button>
            </form>
            <div v-if="pwdError" class="alert alert-danger py-2 mt-2 mb-0 small">{{ pwdError }}</div>
            <div v-if="pwdOk" class="alert alert-success py-2 mt-2 mb-0 small">{{ pwdOk }}</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <h2 class="h6">Respaldo SQL</h2>
            <p class="small text-secondary mb-2">
              Descarga un dump lógico de la base. La restauración destructiva queda fuera de esta versión.
            </p>
            <label class="form-label small">Nombre opcional</label>
            <input v-model="nombre" class="form-control form-control-sm mb-2" maxlength="40" placeholder="ej. mensual" />
            <button class="btn btn-sm btn-outline-primary" type="button" :disabled="downloading" @click="downloadBackup">
              {{ downloading ? 'Generando…' : 'Descargar respaldo' }}
            </button>
            <div v-if="backupError" class="alert alert-danger py-2 mt-2 mb-0 small">{{ backupError }}</div>
            <div v-if="backupOk" class="alert alert-success py-2 mt-2 mb-0 small">{{ backupOk }}</div>
          </div>
        </div>
      </div>
    </div>
  </AppShell>
</template>
