<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api, qs } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'
import DataToolbar from '../../components/DataToolbar.vue'
import PaginationBar from '../../components/PaginationBar.vue'
import ListPanel from '../../components/ListPanel.vue'

const { logout } = useAuthLayout()
const router = useRouter()

const q = ref('')
const page = ref(1)
const perPage = 25
const total = ref(0)
const rows = ref([])
const loading = ref(false)
const error = ref('')
const sort = ref('id_inv')
const dir = ref('desc')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await api.get(`/api/inventarios${qs({ q: q.value, page: page.value, per_page: perPage, sort: sort.value, dir: dir.value })}`)
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
    dir.value = 'desc'
  }
  page.value = 1
  load()
}

async function anular(row) {
  const msg = row.is_aplicado
    ? `¿Anular inventario #${row.id_inv}? Se restaurará el stock al snapshot del sistema.`
    : `¿Eliminar inventario pendiente #${row.id_inv}?`
  if (!confirm(msg)) return
  try {
    await api.delete(`/api/inventarios/${row.id_inv}`)
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
    <PageHeader title="Inventario físico" subtitle="Conteos y aplicación de existencias">
      <template #actions>
        <button class="btn btn-carioca" type="button" @click="router.push('/inventarios/nuevo')">Nuevo inventario</button>
      </template>
    </PageHeader>

    <DataToolbar v-model="q" placeholder="N°, descripción o estado…" @search="page = 1; load()" />
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="6">
      <template #head>
        <tr>
          <th role="button" @click="toggleSort('id_inv')">N°</th>
          <th role="button" @click="toggleSort('fecha_lev')">Fecha lev.</th>
          <th role="button" @click="toggleSort('descripcion')">Descripción</th>
          <th role="button" @click="toggleSort('estado')">Estado</th>
          <th role="button" @click="toggleSort('fecha_ap')">Fecha apl.</th>
          <th></th>
        </tr>
      </template>
      <tr v-for="row in rows" :key="row.id_inv">
        <td>{{ row.id_inv }}</td>
        <td>{{ row.fecha_lev }}</td>
        <td>{{ row.descripcion || '—' }}</td>
        <td>
          <span class="badge" :class="row.is_pendiente ? 'text-bg-warning' : 'text-bg-success'">{{ row.estado }}</span>
        </td>
        <td>{{ row.fecha_ap || '—' }}</td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="router.push(`/inventarios/${row.id_inv}`)">Ver</button>
          <button
            v-if="row.is_pendiente"
            class="btn btn-sm btn-outline-secondary me-1"
            type="button"
            @click="router.push(`/inventarios/${row.id_inv}/editar`)"
          >Editar</button>
          <button class="btn btn-sm btn-outline-danger" type="button" @click="anular(row)">Anular</button>
        </td>
      </tr>
    </ListPanel>
    <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
  </AppShell>
</template>
