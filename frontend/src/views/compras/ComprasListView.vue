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
const sort = ref('fecha_fac')
const dir = ref('desc')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await api.get(`/api/compras${qs({ q: q.value, page: page.value, per_page: perPage, sort: sort.value, dir: dir.value })}`)
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
    dir.value = col === 'fecha_fac' ? 'desc' : 'asc'
  }
  page.value = 1
  load()
}

async function anular(row) {
  if (!confirm(`¿Anular compra N° ${row.cod_fac}? Se revertirá el stock.`)) return
  try {
    await api.delete(`/api/compras/${row.cod_fac}`)
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
    <PageHeader title="Compras" subtitle="Ingresos de mercadería">
      <template #actions>
        <button class="btn btn-carioca" type="button" @click="router.push('/compras/nuevo')">Nueva compra</button>
      </template>
    </PageHeader>

    <DataToolbar v-model="q" placeholder="N° compra o proveedor…" @search="page = 1; load()" />
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="5">
      <template #head>
        <tr>
          <th role="button" @click="toggleSort('cod_fac')">N°</th>
          <th role="button" @click="toggleSort('fecha_fac')">Fecha</th>
          <th role="button" @click="toggleSort('nom_pro')">Proveedor</th>
          <th role="button" class="text-end" @click="toggleSort('total_fac')">Total</th>
          <th></th>
        </tr>
      </template>
      <tr v-for="row in rows" :key="row.cod_fac">
        <td>{{ row.cod_fac }}</td>
        <td>{{ row.fecha_fac }}</td>
        <td>{{ row.nom_pro }}</td>
        <td class="text-end">{{ Number(row.total_fac).toFixed(2) }}</td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="router.push(`/compras/${row.cod_fac}`)">Ver</button>
          <button class="btn btn-sm btn-outline-secondary me-1" type="button" @click="router.push(`/compras/${row.cod_fac}/editar`)">Editar</button>
          <button class="btn btn-sm btn-outline-danger" type="button" @click="anular(row)">Anular</button>
        </td>
      </tr>
    </ListPanel>
    <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
  </AppShell>
</template>
