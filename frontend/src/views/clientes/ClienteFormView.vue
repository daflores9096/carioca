<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'clientes-nuevo')
const cod = computed(() => Number(route.params.cod))

const form = reactive({
  nom_cli: '',
  contacto_sec: '',
  dire_cli: '',
  dire_sec: '',
  ciudad_cli: '',
  tel_cli: '',
  tel_sec: '',
  email_cli: '',
  desc_cli: '',
})
const previewCode = ref(null)
const loading = ref(false)
const saving = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    if (isNew.value) {
      const meta = await api.get('/api/clientes/meta/next-code')
      previewCode.value = meta.next_cod_cli
    } else {
      const data = await api.get(`/api/clientes/${cod.value}`)
      Object.assign(form, data.cliente)
      previewCode.value = data.cliente.cod_cli
    }
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (isNew.value) await api.post('/api/clientes', { ...form })
    else await api.put(`/api/clientes/${cod.value}`, { ...form })
    await router.push('/clientes')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="isNew ? 'Nuevo cliente' : 'Editar cliente'">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/clientes')">Volver</button>
      </template>
    </PageHeader>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>
    <form v-else class="card border-0 shadow-sm" @submit.prevent="save">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-2">
            <label class="form-label">Código</label>
            <input class="form-control" :value="previewCode ?? ''" readonly />
          </div>
          <div class="col-md-10">
            <label class="form-label">Nombre</label>
            <input v-model="form.nom_cli" class="form-control" maxlength="40" required />
          </div>
          <div class="col-md-6">
            <label class="form-label">Contacto secundario</label>
            <input v-model="form.contacto_sec" class="form-control" maxlength="40" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Ciudad</label>
            <input v-model="form.ciudad_cli" class="form-control" maxlength="30" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Dirección</label>
            <input v-model="form.dire_cli" class="form-control" maxlength="50" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Dirección sec.</label>
            <input v-model="form.dire_sec" class="form-control" maxlength="50" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Teléfono</label>
            <input v-model="form.tel_cli" class="form-control" maxlength="15" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Teléfono sec.</label>
            <input v-model="form.tel_sec" class="form-control" maxlength="15" />
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input v-model="form.email_cli" type="email" class="form-control" maxlength="30" />
          </div>
          <div class="col-12">
            <label class="form-label">Descripción / notas</label>
            <input v-model="form.desc_cli" class="form-control" maxlength="80" />
          </div>
        </div>
      </div>
      <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/clientes')">Cancelar</button>
        <button class="btn btn-carioca" type="submit" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
      </div>
    </form>
  </AppShell>
</template>
