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
import { sortIconClass, sortThClass } from '../../utils/sortUi'

const { logout } = useAuthLayout()
const router = useRouter()

const q = ref('')
const page = ref(1)
const perPage = 25
const total = ref(0)
const rows = ref([])
const loading = ref(false)
const error = ref('')
const sort = ref('nom_pro')
const dir = ref('asc')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await api.get(`/api/proveedores${qs({ q: q.value, page: page.value, per_page: perPage, sort: sort.value, dir: dir.value })}`)
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
  if (!confirm(`¿Eliminar proveedor ${row.nom_pro}?`)) return
  try {
    await api.delete(`/api/proveedores/${row.cod_pro}`)
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
    <PageHeader title="Proveedores" subtitle="Contactos de compra">
      <template #actions>
        <button class="btn btn-carioca" type="button" @click="router.push('/proveedores/nuevo')">Nuevo proveedor</button>
      </template>
    </PageHeader>

    <DataToolbar v-model="q" placeholder="Nombre, ciudad, teléfono…" @search="page = 1; load()" />
    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <ListPanel :loading="loading" :empty="rows.length === 0" :col-span="5">
      <template #head>
        <tr>
          <th :class="sortThClass(sort, 'cod_pro')" role="button" @click="toggleSort('cod_pro')">
            <span class="sort-th-inner">Código <i :class="sortIconClass(sort, dir, 'cod_pro')" aria-hidden="true"></i></span>
          </th>
          <th :class="sortThClass(sort, 'nom_pro')" role="button" @click="toggleSort('nom_pro')">
            <span class="sort-th-inner">Nombre <i :class="sortIconClass(sort, dir, 'nom_pro')" aria-hidden="true"></i></span>
          </th>
          <th :class="sortThClass(sort, 'ciudad_pro')" role="button" @click="toggleSort('ciudad_pro')">
            <span class="sort-th-inner">Ciudad <i :class="sortIconClass(sort, dir, 'ciudad_pro')" aria-hidden="true"></i></span>
          </th>
          <th>Teléfono</th>
          <th></th>
        </tr>
      </template>
      <tr v-for="row in rows" :key="row.cod_pro">
        <td>{{ row.cod_pro }}</td>
        <td>{{ row.nom_pro }}</td>
        <td>{{ row.ciudad_pro }}</td>
        <td>{{ row.tel_pro }}</td>
        <td class="text-end text-nowrap">
          <button class="btn btn-sm btn-outline-primary me-1" type="button" @click="router.push(`/proveedores/${row.cod_pro}`)">Editar</button>
          <button class="btn btn-sm btn-outline-danger" type="button" @click="remove(row)">Eliminar</button>
        </td>
      </tr>
    </ListPanel>
    <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
  </AppShell>
</template>
