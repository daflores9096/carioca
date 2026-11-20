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
const id = Number(route.params.id)

const inventario = ref(null)
const lines = ref([])
const loading = ref(true)
const applying = ref(false)
const error = ref('')
const fechaAp = ref(new Date().toISOString().slice(0, 10))

async function load() {
  loading.value = true
  try {
    const data = await api.get(`/api/inventarios/${id}`)
    inventario.value = data.inventario
    lines.value = data.lines || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function aplicar() {
  if (!confirm('¿Aplicar este inventario? Las existencias del sistema se igualarán al conteo físico.')) return
  applying.value = true
  try {
    const data = await api.post(`/api/inventarios/${id}/aplicar`, { fecha_ap: fechaAp.value })
    inventario.value = data.inventario
    lines.value = data.lines || []
  } catch (e) {
    alert(e.message)
  } finally {
    applying.value = false
  }
}

async function anular() {
  const msg = inventario.value?.is_aplicado
    ? '¿Anular inventario aplicado? Se restaurará el stock al snapshot.'
    : '¿Eliminar inventario pendiente?'
  if (!confirm(msg)) return
  try {
    await api.delete(`/api/inventarios/${id}`)
    await router.push('/inventarios')
  } catch (e) {
    alert(e.message)
  }
}

onMounted(load)
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader :title="`Inventario #${id}`">
      <template #actions>
        <button class="btn btn-outline-secondary" type="button" @click="router.push('/inventarios')">Volver</button>
        <button
          v-if="inventario?.is_pendiente"
          class="btn btn-outline-primary"
          type="button"
          @click="router.push(`/inventarios/${id}/editar`)"
        >Editar</button>
        <button class="btn btn-outline-danger" type="button" @click="anular">Anular</button>
      </template>
    </PageHeader>

    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="loading" class="text-secondary">Cargando…</div>

    <template v-else-if="inventario">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
          <div class="col-md-3">
            <span class="text-secondary small">Fecha levantamiento</span>
            <div>{{ inventario.fecha_lev }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Estado</span>
            <div>
              <span class="badge" :class="inventario.is_pendiente ? 'text-bg-warning' : 'text-bg-success'">
                {{ inventario.estado }}
              </span>
            </div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Fecha aplicación</span>
            <div>{{ inventario.fecha_ap || '—' }}</div>
          </div>
          <div class="col-md-3">
            <span class="text-secondary small">Descripción</span>
            <div>{{ inventario.descripcion || '—' }}</div>
          </div>
        </div>
      </div>

      <div v-if="inventario.is_pendiente" class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-end gap-3">
          <div>
            <label class="form-label mb-1">Fecha de aplicación</label>
            <input v-model="fechaAp" type="date" class="form-control" />
          </div>
          <button class="btn btn-carioca" type="button" :disabled="applying" @click="aplicar">
            {{ applying ? 'Aplicando…' : 'Aplicar inventario' }}
          </button>
          <p class="small text-secondary mb-0">
            Al aplicar, <code>item.existencia</code> pasa a ser el conteo físico. No se registra en movimientos.
          </p>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="table-responsive">
          <table class="table mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th class="text-end">Sist.</th>
                <th class="text-end">Físico</th>
                <th class="text-end">Dif.</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="l in lines" :key="l.id">
                <td><code>{{ l.cod_item }}</code></td>
                <td>{{ l.nom_item }}</td>
                <td class="text-end">{{ Number(l.existencia_sis).toFixed(2) }}</td>
                <td class="text-end">{{ Number(l.existencia_inv).toFixed(2) }}</td>
                <td class="text-end" :class="{ 'text-danger fw-semibold': l.diferencia !== 0 }">
                  {{ Number(l.diferencia).toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </AppShell>
</template>
