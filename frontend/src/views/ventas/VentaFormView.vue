<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../../api/client'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'
import LookupModal from '../../components/LookupModal.vue'

const { logout } = useAuthLayout()
const route = useRoute()
const router = useRouter()

const isNew = computed(() => route.name === 'ventas-nuevo')
const cod = computed(() => Number(route.params.cod || 0))

const form = reactive({
  cod_fac: '',
  fecha_fac: new Date().toISOString().slice(0, 10),
  cod_cli: 0,
  nom_cli: '',
  dire_cli: '',
  traspaso: '',
})
const lines = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const showClientes = ref(false)
const showItems = ref(false)

const total = computed(() => lines.value.reduce((s, l) => s + Number(l.importe_fac || 0), 0))
const totBul = computed(() => lines.value.reduce((s, l) => s + Number(l.bultos || 0), 0))

function recalc(line) {
  const caja = Number(line.caja_item) || 0
  const cant = Number(line.cant_fac) || 0
  const precio = Number(line.precio_uni) || 0
  line.bultos = caja > 0 ? Math.round((cant / caja) * 100) / 100 : Number(line.bultos) || 0
  line.importe_fac = Math.round(cant * precio * 100) / 100
}

function addItem(item) {
  const precioRef = Number(item.precio_item) || 0
  const precio = Math.round(precioRef * 1.4 * 100) / 100
  const line = {
    cod_item: item.cod_item,
    nom_item: item.nom_item,
    unid_item: item.unid_item,
    caja_item: Number(item.caja_item) || 0,
    bultos: 0,
    cant_fac: 1,
    precio_uni: precio,
    importe_fac: precio,
  }
  recalc(line)
  lines.value.push(line)
  showItems.value = false
}

function selectCliente(cli) {
  form.cod_cli = cli.cod_cli
  form.nom_cli = cli.nom_cli
  form.dire_cli = cli.dire_cli || ''
  showClientes.value = false
}

function removeLine(idx) {
  lines.value.splice(idx, 1)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    if (isNew.value) {
      form.cod_fac = ''
      form.cod_cli = 0
      form.nom_cli = ''
      form.dire_cli = ''
      form.traspaso = ''
      lines.value = []
      const meta = await api.get('/api/ventas/meta/next-code')
      form.cod_fac = String(meta.next_cod_fac ?? '')
      if (!form.cod_fac) {
        throw new Error('No se pudo obtener el siguiente N° de venta.')
      }
      return
    }
    const data = await api.get(`/api/ventas/${cod.value}`)
    Object.assign(form, {
      cod_fac: String(data.venta.cod_fac),
      fecha_fac: data.venta.fecha_fac,
      cod_cli: data.venta.cod_cli,
      nom_cli: data.venta.nom_cli,
      dire_cli: data.venta.dire_cli,
      traspaso: data.venta.traspaso,
    })
    lines.value = (data.lines || []).map((l) => ({ ...l }))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      cod_fac: Number(form.cod_fac),
      fecha_fac: form.fecha_fac,
      cod_cli: Number(form.cod_cli),
      nom_cli: form.nom_cli,
      dire_cli: form.dire_cli,
      traspaso: form.traspaso,
      total_fac: total.value,
      tot_bul: totBul.value,
      lines: lines.value.map((l) => ({
        cod_item: l.cod_item,
        bultos: Number(l.bultos) || 0,
        cant_fac: Number(l.cant_fac) || 0,
        precio_uni: Number(l.precio_uni) || 0,
        importe_fac: Number(l.importe_fac) || 0,
      })),
    }
    if (isNew.value) await api.post('/api/ventas', payload)
    else await api.put(`/api/ventas/${cod.value}`, payload)
    await router.push(`/ventas/${payload.cod_fac}`)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

watch(
  () => route.fullPath,
  () => {
    load()
  },
  { immediate: true }
)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="isNew ? 'Nueva venta' : `Editar venta ${cod}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/ventas')">Volver</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>

    <form v-else @submit.prevent="save">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">N° venta</label>
              <input
                :value="form.cod_fac"
                class="form-control fw-semibold"
                required
                readonly
                inputmode="numeric"
                title="Se asigna automáticamente el siguiente número disponible"
              />
              <div class="form-text">Asignado automáticamente</div>
            </div>
            <div class="col-md-2">
              <label class="form-label">Fecha</label>
              <input v-model="form.fecha_fac" type="date" class="form-control" required />
            </div>
            <div class="col-md-5">
              <label class="form-label">Cliente</label>
              <div class="input-group">
                <input :value="form.nom_cli" class="form-control" readonly required placeholder="Seleccionar…" />
                <button class="btn btn-outline-secondary" type="button" @click="showClientes = true">Buscar</button>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label">Traspaso / ref.</label>
              <input v-model="form.traspaso" class="form-control" />
            </div>
            <div class="col-md-12">
              <label class="form-label">Dirección</label>
              <input v-model="form.dire_cli" class="form-control" />
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0 text-carioca">Líneas</h2>
        <button class="btn btn-sm btn-carioca" type="button" @click="showItems = true">Agregar artículo</button>
      </div>

      <div class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th class="text-end">Bultos</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">P.V.</th>
                <th class="text-end">Importe</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="lines.length === 0">
                <td colspan="7" class="text-center text-secondary py-3">Sin líneas</td>
              </tr>
              <tr v-for="(line, idx) in lines" :key="idx">
                <td><code>{{ line.cod_item }}</code></td>
                <td>{{ line.nom_item }}</td>
                <td style="width:7rem"><input v-model.number="line.bultos" type="number" step="0.01" class="form-control form-control-sm text-end" /></td>
                <td style="width:7rem"><input v-model.number="line.cant_fac" type="number" step="0.01" class="form-control form-control-sm text-end" @input="recalc(line)" /></td>
                <td style="width:7rem"><input v-model.number="line.precio_uni" type="number" step="0.01" class="form-control form-control-sm text-end" @input="recalc(line)" /></td>
                <td class="text-end">{{ Number(line.importe_fac).toFixed(2) }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" @click="removeLine(idx)">Quitar</button></td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-end fw-semibold">Totales</td>
                <td class="text-end fw-semibold">{{ totBul.toFixed(2) }}</td>
                <td></td>
                <td></td>
                <td class="text-end fw-semibold">{{ total.toFixed(2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/ventas')">Cancelar</button>
        <button class="btn btn-carioca" type="submit" :disabled="saving || !form.nom_cli">{{ saving ? 'Guardando…' : 'Guardar venta' }}</button>
      </div>
    </form>

    <LookupModal
      :show="showClientes"
      title="Buscar cliente"
      endpoint="/api/clientes"
      placeholder="Nombre o ciudad…"
      :per-page="50"
      :columns="[{ key: 'cod_cli', label: 'Código' }, { key: 'nom_cli', label: 'Nombre' }, { key: 'ciudad_cli', label: 'Ciudad' }]"
      @close="showClientes = false"
      @select="selectCliente"
    />
    <LookupModal
      :show="showItems"
      title="Buscar artículo"
      endpoint="/api/items"
      placeholder="Código o nombre…"
      :per-page="50"
      :columns="[{ key: 'cod_item', label: 'Código' }, { key: 'nom_item', label: 'Nombre' }, { key: 'existencia', label: 'Stock' }, { key: 'precio_item', label: 'Precio' }]"
      @close="showItems = false"
      @select="addItem"
    />
  </AppShell>
</template>
