<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'
import LookupModal from '../../components/LookupModal.vue'

const { logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()

const isNew = computed(() => route.name === 'inventarios-nuevo')
const id = computed(() => Number(route.params.id || 0))

const form = reactive({
  fecha_lev: new Date().toISOString().slice(0, 10),
  descripcion: '',
})
const lines = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const showItems = ref(false)

function addItem(item) {
  if (lines.value.some((l) => l.cod_item === item.cod_item)) {
    alert('El artículo ya está en la lista.')
    showItems.value = false
    return
  }
  const sis = Number(item.existencia) || 0
  lines.value.push({
    cod_item: item.cod_item,
    nom_item: item.nom_item,
    unid_item: item.unid_item,
    existencia_sis: sis,
    existencia_inv: sis,
    diferencia: 0,
  })
  showItems.value = false
}

function recalc(line) {
  const sis = Number(line.existencia_sis) || 0
  const inv = Number(line.existencia_inv) || 0
  line.diferencia = Math.round((sis - inv) * 100) / 100
}

function removeLine(idx) {
  lines.value.splice(idx, 1)
}

async function load() {
  if (isNew.value) return
  loading.value = true
  try {
    const data = await api.get(`/api/inventarios/${id.value}`)
    if (!data.inventario.is_pendiente) {
      error.value = 'Solo se pueden editar inventarios pendientes.'
      return
    }
    form.fecha_lev = data.inventario.fecha_lev
    form.descripcion = data.inventario.descripcion
    lines.value = (data.lines || []).map((l) => ({ ...l }))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (lines.value.length === 0) {
    error.value = 'Agrega al menos un artículo.'
    return
  }
  saving.value = true
  error.value = ''
  try {
    const payload = {
      fecha_lev: form.fecha_lev,
      descripcion: form.descripcion,
      lines: lines.value.map((l) => ({
        cod_item: l.cod_item,
        existencia_inv: Number(l.existencia_inv) || 0,
      })),
    }
    const data = isNew.value
      ? await api.post('/api/inventarios', payload)
      : await api.put(`/api/inventarios/${id.value}`, payload)
    await router.push(`/inventarios/${data.inventario.id_inv}`)
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
    <PageHeader :title="isNew ? 'Nuevo inventario físico' : `Editar inventario #${id}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/inventarios')">Volver</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>

    <form v-else @submit.prevent="save">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
          <div class="col-md-3">
            <label class="form-label">Fecha levantamiento</label>
            <input v-model="form.fecha_lev" type="date" class="form-control" required />
          </div>
          <div class="col-md-9">
            <label class="form-label">Descripción</label>
            <input v-model="form.descripcion" class="form-control" maxlength="60" placeholder="Ej. Inventario bodega" />
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0 text-carioca">Artículos a contar</h2>
        <button class="btn btn-sm btn-carioca" type="button" @click="showItems = true">Agregar artículo</button>
      </div>

      <div class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th class="text-end">Sist.</th>
                <th class="text-end">Físico</th>
                <th class="text-end">Dif. (sis−fís)</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="lines.length === 0">
                <td colspan="6" class="text-center text-secondary py-3">Sin artículos</td>
              </tr>
              <tr v-for="(line, idx) in lines" :key="line.cod_item">
                <td><code>{{ line.cod_item }}</code></td>
                <td>{{ line.nom_item }}</td>
                <td class="text-end">{{ Number(line.existencia_sis).toFixed(2) }}</td>
                <td style="width:8rem">
                  <input
                    v-model.number="line.existencia_inv"
                    type="number"
                    step="0.01"
                    class="form-control form-control-sm text-end"
                    @input="recalc(line)"
                  />
                </td>
                <td class="text-end" :class="{ 'text-danger': line.diferencia !== 0 }">
                  {{ Number(line.diferencia).toFixed(2) }}
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-outline-danger" type="button" @click="removeLine(idx)">Quitar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/inventarios')">Cancelar</button>
        <button class="btn btn-carioca" type="submit" :disabled="saving">{{ saving ? 'Guardando…' : 'Guardar' }}</button>
      </div>
    </form>

    <LookupModal
      :show="showItems"
      title="Agregar artículo al conteo"
      endpoint="/api/items"
      placeholder="Código o nombre…"
      :columns="[
        { key: 'cod_item', label: 'Código' },
        { key: 'nom_item', label: 'Nombre' },
        { key: 'existencia', label: 'Stock' },
      ]"
      @close="showItems = false"
      @select="addItem"
    />
  </AppShell>
</template>
