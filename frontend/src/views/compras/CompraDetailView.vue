<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()
const cod = Number(route.params.cod)

const compra = ref(null)
const lines = ref([])
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  try {
    const data = await api.get(`/api/compras/${cod}`)
    compra.value = data.compra
    lines.value = data.lines || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function anular() {
  if (!confirm(`¿Anular compra N° ${cod}?`)) return
  try {
    await api.delete(`/api/compras/${cod}`)
    await router.push('/compras')
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="`Compra N° ${cod}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/compras')">Volver</button>
        <button class="btn btn-outline-dark" type="button" @click="router.push(`/compras/${cod}/imprimir`)">Imprimir nota</button>
        <button class="btn btn-outline-dark" type="button" @click="router.push(`/compras/${cod}/imprimir-mod`)">Imprimir nota (MOD)</button>
        <button class="btn btn-outline-primary" type="button" @click="router.push(`/compras/${cod}/editar`)">Editar</button>
        <button class="btn btn-outline-danger" type="button" @click="anular">Anular</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>
    <template v-else-if="compra">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2">
          <div class="col-md-3"><span class="text-secondary small">Fecha</span><div>{{ compra.fecha_fac }}</div></div>
          <div class="col-md-9"><span class="text-secondary small">Proveedor</span><div>{{ compra.nom_pro }} ({{ compra.cod_pro }})</div></div>
        </div>
      </div>
      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Código</th><th>Nombre</th><th class="text-end">Cant.</th><th class="text-end">Costo</th><th class="text-end">P.Venta</th><th class="text-end">Importe</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in lines" :key="l.id">
                <td><code>{{ l.cod_item }}</code></td>
                <td>{{ l.nom_item }}</td>
                <td class="text-end">{{ Number(l.cant_fac).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.precio_uni).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.precio_ven).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.importe_fac).toFixed(2) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="5" class="text-end fw-semibold">Total</td>
                <td class="text-end fw-semibold">{{ Number(compra.total_fac).toFixed(2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </AppShell>
</template>
