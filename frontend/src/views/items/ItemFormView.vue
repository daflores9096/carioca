<script setup>
import { onMounted, reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { itemsApi } from '../../api/items'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { auth, logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()

const isNew = computed(() => route.name === 'items-nuevo')
const originalCod = computed(() => (isNew.value ? '' : decodeURIComponent(String(route.params.cod))))

const form = reactive({
  cod_item: '',
  nom_item: '',
  unid_item: '',
  precio_item: 0,
  caja_item: 0,
  existencia: 0,
  exi_max: 0,
  exi_min: 0,
  deta_item: '',
})
const loading = ref(false)
const saving = ref(false)
const error = ref('')

async function load() {
  if (isNew.value) return
  loading.value = true
  try {
    const data = await itemsApi.get(originalCod.value)
    Object.assign(form, data.item)
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!auth.isAdmin && isNew.value) {
    error.value = 'Solo administradores pueden crear artículos.'
    return
  }
  saving.value = true
  error.value = ''
  try {
    if (isNew.value) {
      await itemsApi.create({ ...form })
    } else {
      await itemsApi.update(originalCod.value, { ...form })
    }
    await router.push('/items')
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
    <PageHeader :title="isNew ? 'Nuevo artículo' : 'Editar artículo'" :subtitle="isNew ? '' : originalCod">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/items')">Volver</button>
        <button
          v-if="!isNew"
          class="btn btn-outline-dark"
          type="button"
          @click="router.push(`/items/${encodeURIComponent(originalCod)}/movimientos`)"
        >
          Movimiento artículo
        </button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>

    <form v-else class="card border-0 shadow-sm" @submit.prevent="save">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Código</label>
            <input v-model="form.cod_item" class="form-control" maxlength="10" required :readonly="!isNew && !auth.isAdmin" />
          </div>
          <div class="col-md-9">
            <label class="form-label">Nombre</label>
            <input v-model="form.nom_item" class="form-control" maxlength="50" required />
          </div>
          <div class="col-md-3">
            <label class="form-label">Unidad</label>
            <input v-model="form.unid_item" class="form-control" maxlength="5" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Precio</label>
            <input v-model.number="form.precio_item" type="number" step="0.01" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Cant. por caja</label>
            <input v-model.number="form.caja_item" type="number" step="0.01" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Existencia</label>
            <input v-model.number="form.existencia" type="number" step="0.01" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Existencia máx.</label>
            <input v-model.number="form.exi_max" type="number" step="0.01" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label">Existencia mín.</label>
            <input v-model.number="form.exi_min" type="number" step="0.01" class="form-control" />
          </div>
          <div class="col-12">
            <label class="form-label">Detalle</label>
            <input v-model="form.deta_item" class="form-control" maxlength="100" />
          </div>
        </div>
      </div>
      <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/items')">Cancelar</button>
        <button class="btn btn-carioca" type="submit" :disabled="saving">
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </div>
    </form>
  </AppShell>
</template>
