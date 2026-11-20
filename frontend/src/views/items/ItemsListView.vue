<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { itemsApi } from '../../api/items'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'
import DataToolbar from '../../components/DataToolbar.vue'
import PaginationBar from '../../components/PaginationBar.vue'
import ListPanel from '../../components/ListPanel.vue'

const { auth, logout } = useAuthLayout()
const router = useRouter()

const q = ref('')
const page = ref(1)
const perPage = 25
const total = ref(0)
const rows = ref([])
const loading = ref(false)
const error = ref('')
const sort = ref('nom_item')
const dir = ref('asc')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await itemsApi.list({ q: q.value, page: page.value, per_page: perPage, sort: sort.value, dir: dir.value })
    if (id !== requestId) return
    rows.value = data.data || []
    total.value = data.total || 0
  } catch (e) {
    if (id !== requestId) return
    error.value = e.message
  } finally {
    if (id === requestId) loading.value = false
  }
}

function toggleSort(col) {
  if (sort.value === col) dir.value = dir.value === 'asc' ? 'desc' : 'asc'
  else {
    sort.value = col
    dir.value = 'asc'
  }
  page.value = 1
  load()
}

function editPath(cod) {
  return `/items/${encodeURIComponent(cod)}`
}

function viewPath(cod) {
  return `/items/${encodeURIComponent(cod)}/ver`
}

async function remove(row) {
  if (!confirm(`¿Eliminar artículo ${row.cod_item}?`)) return
  try {
    await itemsApi.remove(row.cod_item)
    await load()
  } catch (e) {
    alert(e.message)
  }
}

watch(page, load)
onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader title="Artículos" subtitle="Catálogo e existencias">
      <template #actions>
        <button v-if="auth.isAdmin" class="btn btn-carioca" type="button" @click="router.push('/items/nuevo')">
          Nuevo artículo
        </button>
      </template>
    </PageHeader>

    <DataToolbar v-model="q" placeholder="Código, nombre o detalle…" @search="page = 1; load()" />
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="6">
      <template #head>
        <tr>
          <th role="button" @click="toggleSort('cod_item')">Código</th>
          <th role="button" @click="toggleSort('nom_item')">Nombre</th>
          <th>Unidad</th>
          <th role="button" class="text-end" @click="toggleSort('precio_item')">Precio</th>
          <th role="button" class="text-end" @click="toggleSort('existencia')">Existencia</th>
          <th></th>
        </tr>
      </template>
      <tr v-for="row in rows" :key="row.cod_item">
        <td><code>{{ row.cod_item }}</code></td>
        <td>{{ row.nom_item }}</td>
        <td>{{ row.unid_item }}</td>
        <td class="text-end">{{ Number(row.precio_item).toFixed(2) }}</td>
        <td class="text-end" :class="{ 'text-danger fw-semibold': row.existencia <= row.exi_min }">
          {{ Number(row.existencia).toFixed(2) }}
        </td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-dark me-1" type="button" title="Ver ficha y movimientos" @click="router.push(viewPath(row.cod_item))">
            Ver
          </button>
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="router.push(editPath(row.cod_item))">
            Editar
          </button>
          <button v-if="auth.isAdmin" class="btn btn-sm btn-outline-danger" type="button" @click="remove(row)">
            Eliminar
          </button>
        </td>
      </tr>
    </ListPanel>

    <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
  </AppShell>
</template>
