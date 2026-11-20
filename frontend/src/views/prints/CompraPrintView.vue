<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import './print.css'

const route = useRoute()
const router = useRouter()
const cod = Number(route.params.cod)
const isMod = computed(() => route.name === 'compras-print-mod')

const compra = ref(null)
const lines = ref([])
const loading = ref(true)
const error = ref('')

function money(n) {
  return Number(n || 0).toFixed(2)
}

async function load() {
  loading.value = true
  error.value = ''
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

function doPrint() {
  window.print()
}

onMounted(load)
</script>

<template>
  <div class="print-page">
    <div class="print-toolbar">
      <button class="btn btn-carioca btn-sm" type="button" :disabled="loading || !!error" @click="doPrint">Imprimir</button>
      <button class="btn btn-outline-secondary btn-sm" type="button" @click="router.push(`/compras/${cod}`)">Volver</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-else-if="loading" class="text-secondary">Cargando…</div>

    <template v-else-if="compra">
      <div class="print-box">
        <p class="print-company-title">CASA CARIOCA Ltda.</p>
        <p class="print-company-sub">Manzana 4, Galpón 28</p>
        <p class="print-center print-company-sub" style="margin-top: 6px">EN EFECTIVO</p>
        <p class="print-center"><b>(En dólares)</b></p>
      </div>

      <div class="print-box">
        <table class="print-meta">
          <tr>
            <td>N. COMPRA:</td>
            <td>{{ compra.cod_fac }}</td>
          </tr>
          <tr>
            <td>FECHA:</td>
            <td>{{ compra.fecha_fac }}</td>
          </tr>
          <tr v-if="!isMod">
            <td>PROVEEDOR:</td>
            <td>{{ compra.nom_pro }}</td>
          </tr>
        </table>

        <div class="print-count">{{ lines.length }} ARTICULOS</div>

        <table class="print-table">
          <thead>
            <tr>
              <th class="text-start">CODIGO</th>
              <th class="text-start">ARTICULO</th>
              <th class="text-start">UNIDAD</th>
              <th v-if="!isMod" class="text-end">CANTIDAD</th>
              <th class="text-end">P.VENTA</th>
              <th v-if="!isMod" class="text-end">IMPORTE</th>
            </tr>
          </thead>
          <tbody>
            <tr class="spacer"><td :colspan="isMod ? 4 : 6"></td></tr>
            <tr v-for="l in lines" :key="l.id">
              <td>{{ l.cod_item }}</td>
              <td>{{ l.nom_item }}</td>
              <td>{{ l.unid_item || '—' }}</td>
              <td v-if="!isMod" class="text-end">{{ money(l.cant_fac) }}</td>
              <td class="text-end">{{ money(l.precio_ven) }}</td>
              <td v-if="!isMod" class="text-end">{{ money(l.importe_fac) }}</td>
            </tr>
            <tr class="spacer"><td :colspan="isMod ? 4 : 6"></td></tr>
          </tbody>
        </table>

        <table v-if="!isMod" class="print-totals">
          <tr>
            <td><b>Importe Total:</b></td>
            <td class="text-end">{{ '$ US' }} <b>{{ money(compra.total_fac) }}</b></td>
          </tr>
        </table>
      </div>
    </template>
  </div>
</template>
