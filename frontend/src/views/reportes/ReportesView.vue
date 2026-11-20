<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, qs } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()

const tipo = computed(() => (route.name === 'reportes-compras' ? 'compras' : 'ventas'))
const title = computed(() => (tipo.value === 'compras' ? 'Reporte de compras' : 'Reporte de ventas'))

function monthStart() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`
}
function today() {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const fechaMin = ref(monthStart())
const fechaMax = ref(today())
const rows = ref([])
const totalDocs = ref(0)
const totalMonto = ref(0)
const loading = ref(false)
const error = ref('')
let requestId = 0

async function load() {
  const id = ++requestId
  loading.value = true
  error.value = ''
  try {
    const data = await api.get(`/api/reportes/${tipo.value}${qs({ fecha_min: fechaMin.value, fecha_max: fechaMax.value })}`)
    if (id !== requestId) return
    rows.value = data.data || []
    totalDocs.value = data.total_docs || 0
    totalMonto.value = data.total_monto || 0
  } catch (e) {
    if (id !== requestId) return
    error.value = e.message
    rows.value = []
  } finally {
    if (id === requestId) loading.value = false
  }
}

function money(n) {
  return Number(n || 0).toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function printReport() {
  window.print()
}

watch(() => route.name, load)
onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="title" subtitle="Informe por rango de fechas">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/reportes')">Volver</button>
        <button class="btn btn-outline-primary" type="button" :disabled="loading" @click="printReport">Imprimir</button>
      </template>
    </PageHeader>

    <div class="card border-0 shadow-sm mb-3 no-print-form">
      <div class="card-body">
        <form class="row g-3 align-items-end" @submit.prevent="load">
          <div class="col-sm-4 col-md-3">
            <label class="form-label">Desde</label>
            <input v-model="fechaMin" type="date" class="form-control" required />
          </div>
          <div class="col-sm-4 col-md-3">
            <label class="form-label">Hasta</label>
            <input v-model="fechaMax" type="date" class="form-control" required />
          </div>
          <div class="col-sm-4 col-md-3">
            <button class="btn btn-carioca" type="submit" :disabled="loading">
              {{ loading ? 'Cargando…' : 'Generar' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>

    <div class="d-flex flex-wrap gap-3 mb-3">
      <div class="badge text-bg-light border px-3 py-2">
        Documentos: <strong>{{ totalDocs }}</strong>
      </div>
      <div class="badge text-bg-light border px-3 py-2">
        Total: <strong>{{ money(totalMonto) }}</strong>
      </div>
      <div class="badge text-bg-light border px-3 py-2">
        Período: <strong>{{ fechaMin }} → {{ fechaMax }}</strong>
      </div>
    </div>

    <div class="table-responsive bg-white shadow-sm rounded">
      <table class="table table-sm table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>N°</th>
            <th>Fecha</th>
            <th>{{ tipo === 'compras' ? 'Proveedor' : 'Cliente' }}</th>
            <th class="text-end">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="4" class="text-secondary">Cargando…</td>
          </tr>
          <tr v-else-if="rows.length === 0">
            <td colspan="4" class="text-secondary">Sin resultados en el período.</td>
          </tr>
          <tr v-for="row in rows" :key="row.cod_fac">
            <td>{{ row.cod_fac }}</td>
            <td>{{ row.fecha_fac }}</td>
            <td>{{ tipo === 'compras' ? row.nom_pro : row.nom_cli }}</td>
            <td class="text-end">{{ money(row.total_fac) }}</td>
          </tr>
        </tbody>
        <tfoot v-if="rows.length">
          <tr class="fw-semibold">
            <td colspan="3" class="text-end">Total período</td>
            <td class="text-end">{{ money(totalMonto) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </AppShell>
</template>

<style scoped>
@media print {
  :deep(.navbar),
  .no-print-form,
  :deep(.btn) {
    display: none !important;
  }
  :deep(main.container) {
    max-width: 100% !important;
    padding: 0 !important;
  }
}
</style>
