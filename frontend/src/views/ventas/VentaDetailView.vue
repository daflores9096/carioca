<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { auth, logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()
const cod = Number(route.params.cod)

const venta = ref(null)
const lines = ref([])
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  try {
    const data = await api.get(`/api/ventas/${cod}`)
    venta.value = data.venta
    lines.value = data.lines || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function anular() {
  if (!confirm(`¿Anular venta N° ${cod}?`)) return
  try {
    await api.delete(`/api/ventas/${cod}`)
    await router.push('/ventas')
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="`Venta N° ${cod}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/ventas')">Volver</button>
        <button class="btn btn-outline-dark" type="button" @click="router.push(`/ventas/${cod}/imprimir`)">Imprimir nota</button>
        <button class="btn btn-outline-dark" type="button" @click="router.push(`/ventas/${cod}/imprimir-mod`)">Imprimir nota (MOD)</button>
        <button v-if="auth.isAdmin" class="btn btn-outline-primary" type="button" @click="router.push(`/ventas/${cod}/editar`)">Editar</button>
        <button v-if="auth.isAdmin" class="btn btn-outline-danger" type="button" @click="anular">Anular</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>
    <template v-else-if="venta">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
          <div class="col-md-3"><span class="text-secondary small">Fecha</span><div>{{ venta.fecha_fac }}</div></div>
          <div class="col-md-5"><span class="text-secondary small">Cliente</span><div>{{ venta.nom_cli }} ({{ venta.cod_cli }})</div></div>
          <div class="col-md-4"><span class="text-secondary small">Traspaso</span><div>{{ venta.traspaso || '—' }}</div></div>
          <div class="col-12"><span class="text-secondary small">Dirección</span><div>{{ venta.dire_cli || '—' }}</div></div>
        </div>
      </div>
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Código</th><th>Nombre</th><th class="text-end">Bultos</th><th class="text-end">Cant.</th><th class="text-end">P.V.</th><th class="text-end">Importe</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in lines" :key="l.id">
                <td><code>{{ l.cod_item }}</code></td>
                <td>{{ l.nom_item }}</td>
                <td class="text-end">{{ Number(l.bultos).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.cant_fac).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.precio_uni).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.importe_fac).toFixed(2) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-end fw-semibold">Totales</td>
                <td class="text-end fw-semibold">{{ Number(venta.tot_bul).toFixed(2) }}</td>
                <td colspan="2"></td>
                <td class="text-end fw-semibold">{{ Number(venta.total_fac).toFixed(2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </AppShell>
</template>
