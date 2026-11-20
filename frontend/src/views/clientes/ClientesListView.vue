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
const sort = ref('nom_cli')
const dir = ref('asc')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await api.get(`/api/clientes${qs({ q: q.value, page: page.value, per_page: perPage, sort: sort.value, dir: dir.value })}`)
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

async function remove(row) {
  if (!confirm(`¿Eliminar cliente ${row.nom_cli}?`)) return
  try {
    await api.delete(`/api/clientes/${row.cod_cli}`)
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
    <PageHeader title="Clientes" subtitle="Contactos de venta">
      <template #actions>
        <button class="btn btn-carioca" type="button" @click="router.push('/clientes/nuevo')">Nuevo cliente</button>
      </template>
    </PageHeader>

    <DataToolbar v-model="q" placeholder="Nombre, ciudad, teléfono…" @search="page = 1; load()" />
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="5">
      <template #head>
        <tr>
          <th role="button" @click="toggleSort('cod_cli')">Código</th>
          <th role="button" @click="toggleSort('nom_cli')">Nombre</th>
          <th role="button" @click="toggleSort('ciudad_cli')">Ciudad</th>
          <th>Teléfono</th>
          <th></th>
        </tr>
      </template>
      <tr v-for="row in rows" :key="row.cod_cli">
        <td>{{ row.cod_cli }}</td>
        <td>{{ row.nom_cli }}</td>
        <td>{{ row.ciudad_cli }}</td>
        <td>{{ row.tel_cli }}</td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="router.push(`/clientes/${row.cod_cli}`)">Editar</button>
          <button class="btn btn-sm btn-outline-danger" type="button" @click="remove(row)">Eliminar</button>
        </td>
      </tr>
    </ListPanel>
    <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
  </AppShell>
</template>
