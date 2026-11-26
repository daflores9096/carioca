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

const isNew = computed(() => route.name === 'compras-nuevo')
const cod = computed(() => Number(route.params.cod || 0))

const form = reactive({
  cod_fac: '',
  fecha_fac: new Date().toISOString().slice(0, 10),
  cod_pro: 0,
  nom_pro: '',
})
const lines = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const showProveedores = ref(false)
const showItems = ref(false)

const total = computed(() => lines.value.reduce((s, l) => s + Number(l.importe_fac || 0), 0))

function normalizeCant(line) {
  const n = Number(line.cant_fac)
  line.cant_fac = Number.isFinite(n) ? Math.max(0, Math.round(n)) : 0
}

function recalc(line) {
  normalizeCant(line)
  const cant = Number(line.cant_fac) || 0
  const costo = Number(line.precio_uni) || 0
  line.precio_ven = Math.round(costo * 1.4 * 100) / 100
  line.importe_fac = Math.round(cant * line.precio_ven * 100) / 100
}

function addItem(item) {
  const costo = Number(item.precio_item) || 0
  const line = {
    cod_item: item.cod_item,
    nom_item: item.nom_item,
    unid_item: item.unid_item || '',
    cant_fac: 0,
    precio_uni: costo,
    precio_ven: Math.round(costo * 1.4 * 100) / 100,
    importe_fac: 0,
  }
  recalc(line)
  lines.value.push(line)
  showItems.value = false
}

function selectProveedor(pro) {
  form.cod_pro = pro.cod_pro
  form.nom_pro = pro.nom_pro
  showProveedores.value = false
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
      form.cod_pro = 0
      form.nom_pro = ''
      lines.value = []
      const meta = await api.get('/api/compras/meta/next-code')
      form.cod_fac = String(meta.next_cod_fac ?? '')
      if (!form.cod_fac) {
        throw new Error('No se pudo obtener el siguiente N° de compra.')
      }
      return
    }
    const data = await api.get(`/api/compras/${cod.value}`)
    Object.assign(form, {
      cod_fac: String(data.compra.cod_fac),
      fecha_fac: data.compra.fecha_fac,
      cod_pro: data.compra.cod_pro,
      nom_pro: data.compra.nom_pro,
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
      cod_pro: Number(form.cod_pro),
      nom_pro: form.nom_pro,
      total_fac: total.value,
      lines: lines.value.map((l) => ({
        cod_item: l.cod_item,
        cant_fac: Number(l.cant_fac) || 0,
        precio_uni: Number(l.precio_uni) || 0,
        precio_ven: Number(l.precio_ven) || 0,
        importe_fac: Number(l.importe_fac) || 0,
      })),
    }
    if (isNew.value) await api.post('/api/compras', payload)
    else await api.put(`/api/compras/${cod.value}`, payload)
    await router.push(`/compras/${payload.cod_fac}`)
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
    <PageHeader :title="isNew ? 'Nueva compra' : `Editar compra ${cod}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/compras')">Volver</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>

    <form v-else @submit.prevent="save">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">N° compra</label>
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
            <div class="col-md-8">
              <label class="form-label">Proveedor</label>
              <div class="input-group">
                <input :value="form.nom_pro" class="form-control" readonly required placeholder="Seleccionar…" />
                <button class="btn btn-outline-secondary" type="button" @click="showProveedores = true">Buscar</button>
              </div>
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
                <th>Unidad</th>
                <th class="text-end">Cant.</th>
                <th class="text-end">P.Costo</th>
                <th class="text-end">P.Venta</th>
                <th class="text-end">Importe</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="lines.length === 0">
                <td colspan="8" class="text-center text-secondary py-3">Sin líneas</td>
              </tr>
              <tr v-for="(line, idx) in lines" :key="idx">
                <td><code>{{ line.cod_item }}</code></td>
                <td>{{ line.nom_item }}</td>
                <td>{{ line.unid_item || '—' }}</td>
                <td style="width:7rem">
                  <input
                    v-model.number="line.cant_fac"
                    type="number"
                    step="1"
                    min="0"
                    inputmode="numeric"
                    class="form-control form-control-sm text-end"
                    @input="recalc(line)"
                    @change="recalc(line)"
                  />
                </td>
                <td style="width:7rem"><input v-model.number="line.precio_uni" type="number" step="0.01" class="form-control form-control-sm text-end" @input="recalc(line)" /></td>
                <td class="text-end">{{ Number(line.precio_ven).toFixed(2) }}</td>
                <td class="text-end">{{ Number(line.importe_fac).toFixed(2) }}</td>
                <td class="text-end"><button class="btn btn-sm btn-outline-danger" type="button" @click="removeLine(idx)">Quitar</button></td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="6" class="text-end fw-semibold">Total</td>
                <td class="text-end fw-semibold">{{ total.toFixed(2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/compras')">Cancelar</button>
        <button class="btn btn-carioca" type="submit" :disabled="saving || !form.nom_pro">{{ saving ? 'Guardando…' : 'Guardar compra' }}</button>
      </div>
    </form>

    <LookupModal
      :show="showProveedores"
      title="Buscar proveedor"
      endpoint="/api/proveedores"
      placeholder="Nombre o ciudad…"
      :per-page="100"
      :columns="[{ key: 'cod_pro', label: 'Código' }, { key: 'nom_pro', label: 'Nombre' }, { key: 'ciudad_pro', label: 'Ciudad' }]"
      @close="showProveedores = false"
      @select="selectProveedor"
    />
    <LookupModal
      :show="showItems"
      title="Buscar artículo"
      endpoint="/api/items"
      placeholder="Código o nombre…"
      :per-page="100"
      :columns="[{ key: 'cod_item', label: 'Código' }, { key: 'nom_item', label: 'Nombre' }, { key: 'precio_item', label: 'Precio' }]"
      @close="showItems = false"
      @select="addItem"
    />
  </AppShell>
</template>
