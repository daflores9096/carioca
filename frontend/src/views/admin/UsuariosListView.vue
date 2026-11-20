<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { auth, logout, router } = useAuthLayout()

const rows = ref([])
const loading = ref(false)
const error = ref('')
const saving = ref(false)
const showForm = ref(false)
const editingId = ref(null)
const pwdForId = ref(null)

const form = reactive({
  usuario_nombre: '',
  usuario_email: '',
  usuario_clave: '',
  usuario_clave_conf: '',
  is_admin: false,
})

const pwdForm = reactive({
  usuario_clave: '',
  usuario_clave_conf: '',
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.get('/api/usuarios')
    rows.value = data.data || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function openCreate() {
  editingId.value = null
  pwdForId.value = null
  form.usuario_nombre = ''
  form.usuario_email = ''
  form.usuario_clave = ''
  form.usuario_clave_conf = ''
  form.is_admin = false
  showForm.value = true
}

function openEdit(row) {
  editingId.value = row.usuario_id
  pwdForId.value = null
  form.usuario_nombre = row.usuario_nombre
  form.usuario_email = row.usuario_email
  form.usuario_clave = ''
  form.usuario_clave_conf = ''
  form.is_admin = !!row.is_admin
  showForm.value = true
}

function openPwd(row) {
  showForm.value = false
  editingId.value = null
  pwdForId.value = row.usuario_id
  pwdForm.usuario_clave = ''
  pwdForm.usuario_clave_conf = ''
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await api.put(`/api/usuarios/${editingId.value}`, {
        usuario_email: form.usuario_email,
        nivel_acceso: form.is_admin ? '1' : null,
      })
    } else {
      await api.post('/api/usuarios', {
        usuario_nombre: form.usuario_nombre,
        usuario_email: form.usuario_email,
        usuario_clave: form.usuario_clave,
        usuario_clave_conf: form.usuario_clave_conf,
        nivel_acceso: form.is_admin ? '1' : null,
      })
    }
    showForm.value = false
    await load()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function savePwd() {
  saving.value = true
  error.value = ''
  try {
    await api.post(`/api/usuarios/${pwdForId.value}/password`, {
      usuario_clave: pwdForm.usuario_clave,
      usuario_clave_conf: pwdForm.usuario_clave_conf,
    })
    pwdForId.value = null
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function remove(row) {
  if (!confirm(`¿Eliminar usuario ${row.usuario_nombre}?`)) return
  try {
    await api.delete(`/api/usuarios/${row.usuario_id}`)
    await load()
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader title="Usuarios" subtitle="Administración de accesos">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/admin')">Volver</button>
        <button class="btn btn-carioca" type="button" @click="openCreate">Nuevo usuario</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div v-if="showForm" class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 mb-3">{{ editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
        <form class="row g-3" @submit.prevent="save">
          <div class="col-md-4">
            <label class="form-label">Usuario</label>
            <input
              v-model="form.usuario_nombre"
              class="form-control"
              maxlength="15"
              :disabled="!!editingId"
              required
            />
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input v-model="form.usuario_email" type="email" class="form-control" required />
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input id="isAdmin" v-model="form.is_admin" class="form-check-input" type="checkbox" />
              <label class="form-check-label" for="isAdmin">Administrador</label>
            </div>
          </div>
          <template v-if="!editingId">
            <div class="col-md-4">
              <label class="form-label">Contraseña</label>
              <input v-model="form.usuario_clave" type="password" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">Repetir contraseña</label>
              <input v-model="form.usuario_clave_conf" type="password" class="form-control" required />
            </div>
          </template>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-carioca" type="submit" :disabled="saving">
              {{ saving ? 'Guardando…' : 'Guardar' }}
            </button>
            <button class="btn btn-outline-secondary" type="button" @click="showForm = false">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="pwdForId" class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <h2 class="h6 mb-3">Cambiar contraseña</h2>
        <form class="row g-3" @submit.prevent="savePwd">
          <div class="col-md-4">
            <label class="form-label">Nueva contraseña</label>
            <input v-model="pwdForm.usuario_clave" type="password" class="form-control" required />
          </div>
          <div class="col-md-4">
            <label class="form-label">Repetir</label>
            <input v-model="pwdForm.usuario_clave_conf" type="password" class="form-control" required />
          </div>
          <div class="col-12 d-flex gap-2">
            <button class="btn btn-carioca" type="submit" :disabled="saving">Guardar</button>
            <button class="btn btn-outline-secondary" type="button" @click="pwdForId = null">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <div class="table-responsive bg-white shadow-sm rounded">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Usuario</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Alta</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="text-secondary">Cargando…</td>
          </tr>
          <tr v-for="row in rows" :key="row.usuario_id">
            <td>{{ row.usuario_nombre }}</td>
            <td>{{ row.usuario_email }}</td>
            <td>
              <span class="badge" :class="row.is_admin ? 'text-bg-primary' : 'text-bg-secondary'">
                {{ row.is_admin ? 'Admin' : 'Operador' }}
              </span>
            </td>
            <td>{{ row.usuario_freg }}</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="openEdit(row)">Editar</button>
              <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="openPwd(row)">Clave</button>
              <button
                v-if="row.usuario_id !== auth.user?.usuario_id"
                class="btn btn-sm btn-outline-danger"
                type="button"
                @click="remove(row)"
              >
                Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppShell>
</template>
