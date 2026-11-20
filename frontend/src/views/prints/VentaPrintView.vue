<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import logoUrl from '../../assets/carioca_logo_bn.jpg'
import './print.css'

const route = useRoute()
const router = useRouter()
const cod = Number(route.params.cod)
const isMod = computed(() => route.name === 'ventas-print-mod')
const factor = computed(() => (isMod.value ? 0.6 : 1))

const venta = ref(null)
const lines = ref([])
const loading = ref(true)
const error = ref('')

function money(n) {
  return Number(n || 0).toFixed(2)
}

function linePrice(l) {
  return Number(l.precio_uni || 0) * factor.value
}

function lineImporte(l) {
  return Number(l.importe_fac || 0) * factor.value
}

const totalFac = computed(() => Number(venta.value?.total_fac || 0) * factor.value)

async function load() {
  loading.value = true
  error.value = ''
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

function doPrint() {
  window.print()
}

onMounted(load)
</script>

<template>
  <div class="print-page">
    <div class="print-toolbar">
      <button class="btn btn-carioca btn-sm" type="button" :disabled="loading || !!error" @click="doPrint">Imprimir</button>
      <button class="btn btn-outline-secondary btn-sm" type="button" @click="router.push(`/ventas/${cod}`)">Volver</button>
    </div>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-else-if="loading" class="text-secondary">Cargando…</div>

    <template v-else-if="venta">
      <div class="print-venta-header">
        <img :src="logoUrl" alt="CARIOCA" />
        <div class="print-venta-addr">
          <div><strong>CASA CARIOCA Ltda.</strong></div>
          <div>Manzana 6, Galpón 30</div>
          <div>Zona Franca - Iquique</div>
          <div>{{ isMod ? 'Tel: 00 - 56 - 57 - 2473515' : 'Tel/Fax: 00 - 56 - 57 - 2473515' }}</div>
        </div>
      </div>

      <div class="print-box">
        <table class="print-meta">
          <tr>
            <td>{{ isMod ? 'Nº NOTA:' : 'N. NOTA:' }}</td>
            <td>{{ venta.cod_fac }}</td>
          </tr>
          <tr>
            <td>FECHA:</td>
            <td>{{ venta.fecha_fac }}</td>
          </tr>
          <tr>
            <td>CLIENTE:</td>
            <td>{{ venta.nom_cli }}</td>
          </tr>
          <tr>
            <td>DIRECCION:</td>
            <td>{{ venta.dire_cli || '' }}</td>
          </tr>
          <tr>
            <td>TRASPASO:</td>
            <td>{{ venta.traspaso || '' }}</td>
          </tr>
        </table>

        <div class="print-count">{{ lines.length }} ARTICULOS</div>

        <table class="print-table">
          <thead>
            <tr>
              <th class="text-start">CODIGO</th>
              <th class="text-start">ARTICULO</th>
              <th class="text-end">BULTOS</th>
              <th class="text-end">CANTIDAD</th>
              <th class="text-end">PRECIO U.</th>
              <th class="text-end">IMPORTE</th>
            </tr>
          </thead>
          <tbody>
            <tr class="spacer"><td colspan="6"></td></tr>
            <tr v-for="l in lines" :key="l.id">
              <td>{{ l.cod_item }}</td>
              <td>{{ l.nom_item }}</td>
              <td class="text-end">{{ money(l.bultos) }}</td>
              <td class="text-end">{{ money(l.cant_fac) }}</td>
              <td class="text-end">{{ money(linePrice(l)) }}</td>
              <td class="text-end">{{ money(lineImporte(l)) }}</td>
            </tr>
            <tr class="spacer"><td colspan="6"></td></tr>
          </tbody>
        </table>

        <table class="print-totals">
          <tr>
            <td class="text-end">
              <b>Total bultos:</b>&nbsp;&nbsp;{{ money(venta.tot_bul) }}
              &nbsp;&nbsp;&nbsp;&nbsp;
              <b>Importe total {{ '$ US' }}:</b>&nbsp;&nbsp;{{ money(totalFac) }}
            </td>
          </tr>
        </table>
      </div>
    </template>
  </div>
</template>
