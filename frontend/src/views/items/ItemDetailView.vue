<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { itemsApi } from '../../api/items'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'
import PaginationBar from '../../components/PaginationBar.vue'
import ListPanel from '../../components/ListPanel.vue'

const { auth, logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()

const cod = computed(() => decodeURIComponent(String(route.params.cod || '')))
const tab = computed(() => (route.name === 'items-movimientos' ? 'movimientos' : 'datos'))

const item = ref(null)
const rows = ref([])
const page = ref(1)
const perPage = 50
const total = ref(0)
const sort = ref('fecha_mov')
const dir = ref('desc')
const loading = ref(false)
const error = ref('')
let requestId = 0

function editPath() {
  return `/items/${encodeURIComponent(cod.value)}`
}

function goTab(name) {
  if (name === 'movimientos') {
    router.push({ name: 'items-movimientos', params: { cod: cod.value } })
  } else {
    router.push({ name: 'items-ver', params: { cod: cod.value } })
  }
}

async function loadItem() {
  const data = await itemsApi.get(cod.value)
  item.value = data.item
}

async function loadMovements() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await itemsApi.movements(cod.value, {
      page: page.value,
      per_page: perPage,
      sort: sort.value,
      dir: dir.value,
    })
    if (id !== requestId) return
    item.value = data.item
    rows.value = data.data || []
    total.value = data.total || 0
  } catch (e) {
    if (id !== requestId) return
    error.value = e.message
  } finally {
    if (id === requestId) loading.value = false
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    if (tab.value === 'movimientos') {
      await loadMovements()
    } else {
      await loadItem()
      loading.value = false
    }
  } catch (e) {
    error.value = e.message
    loading.value = false
  }
}

function toggleSort(col) {
  if (sort.value === col) dir.value = dir.value === 'asc' ? 'desc' : 'asc'
  else {
    sort.value = col
    dir.value = col === 'fecha_mov' ? 'desc' : 'asc'
  }
  page.value = 1
  loadMovements()
}

function money(n) {
  return Number(n || 0).toFixed(2)
}

function openNota(row) {
  if (!row.nota_path) return
  if (row.tipo_mov === 'C' && !auth.isAdmin) {
    alert('Solo administradores pueden ver el detalle de compras.')
    return
  }
  router.push(row.nota_path)
}

watch(page, () => {
  if (tab.value === 'movimientos') loadMovements()
})
watch(
  () => [route.name, route.params.cod],
  () => {
    page.value = 1
    load()
  }
)
onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="item ? `[${item.cod_item}] ${item.nom_item}` : 'Artículo'" subtitle="Ficha y movimientos">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/items')">Volver</button>
        <button class="btn btn-outline-primary" type="button" @click="router.push(editPath())">Editar</button>
      </template>
    </PageHeader>

    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: tab === 'datos' }"
          type="button"
          @click="goTab('datos')"
        >
          Datos artículo
        </button>
      </li>
      <li class="nav-item">
        <button
          class="nav-link"
          :class="{ active: tab === 'movimientos' }"
          type="button"
          @click="goTab('movimientos')"
        >
          Movimiento artículo
        </button>
      </li>
    </ul>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading && !item" class="text-secondary">Cargando…</div>

    <template v-else-if="item && tab === 'datos'">
      <div class="card border-0 shadow-sm">
        <div class="card-body row g-3">
          <div class="col-md-3">
            <span class="text-secondary small">Código</span>
            <div><code>{{ item.cod_item }}</code></div>
          </div>
          <div class="col-md-9">
            <span class="text-secondary small">Nombre</span>
            <div>{{ item.nom_item }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Unidad</span>
            <div>{{ item.unid_item || '—' }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Precio</span>
            <div>{{ money(item.precio_item) }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Cant. por caja</span>
            <div>{{ money(item.caja_item) }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Existencia</span>
            <div :class="{ 'text-danger fw-semibold': item.existencia <= item.exi_min }">
              {{ money(item.existencia) }} {{ item.unid_item }}
            </div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Existencia máx.</span>
            <div>{{ money(item.exi_max) }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Existencia mín.</span>
            <div>{{ money(item.exi_min) }}</div>
          </div>
          <div class="col-12">
            <span class="text-secondary small">Detalle</span>
            <div>{{ item.deta_item || '—' }}</div>
          </div>
        </div>
      </div>
    </template>

    <template v-else-if="item && tab === 'movimientos'">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="text-carioca fw-semibold">
          Saldo actual del artículo:
          <span class="ms-1">{{ money(item.existencia) }} {{ item.unid_item }}</span>
        </div>
      </div>

      <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="7">
        <template #head>
          <tr>
            <th role="button" @click="toggleSort('tipo_mov')">Tipo</th>
            <th role="button" @click="toggleSort('fecha_mov')">Fecha</th>
            <th role="button" @click="toggleSort('cod_mov')">N° nota</th>
            <th role="button" @click="toggleSort('nom_cli_pro')">Cliente / Proveedor</th>
            <th role="button" class="text-end" @click="toggleSort('entrada')">Entrada</th>
            <th role="button" class="text-end" @click="toggleSort('salida')">Salida</th>
            <th></th>
          </tr>
        </template>
        <tr v-for="row in rows" :key="row.id_mov">
          <td>
            <span class="badge" :class="row.tipo_mov === 'C' ? 'text-bg-primary' : 'text-bg-success'">
              {{ row.tipo_mov }}
            </span>
          </td>
          <td>{{ row.fecha_mov }}</td>
          <td>{{ row.cod_mov }}</td>
          <td>{{ row.nom_cli_pro || '—' }}</td>
          <td class="text-end">{{ money(row.entrada) }}</td>
          <td class="text-end">{{ money(row.salida) }}</td>
          <td class="text-end">
            <button
              v-if="row.nota_path"
              class="btn btn-sm btn-outline-secondary"
              type="button"
              title="Ver detalle nota"
              @click="openNota(row)"
            >
              Ver nota
            </button>
          </td>
        </tr>
      </ListPanel>

      <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
    </template>
  </AppShell>
</template>
