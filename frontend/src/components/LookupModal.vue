<script setup>
import { ref, watch } from 'vue'
import { api, qs } from '../api/client'
import PaginationBar from './PaginationBar.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Buscar' },
  endpoint: { type: String, required: true },
  columns: { type: Array, required: true }, // [{key,label}]
  placeholder: { type: String, default: 'Buscar…' },
  perPage: { type: Number, default: 50 },
})
const emit = defineEmits(['close', 'select'])

const q = ref('')
const rows = ref([])
const total = ref(0)
const page = ref(1)
const loading = ref(false)
let debounceTimer = 0
let requestId = 0

async function search() {
  const id = ++requestId
  loading.value = true
  try {
    const data = await api.get(
      `${props.endpoint}${qs({ q: q.value, page: page.value, per_page: props.perPage, sort: undefined })}`
    )
    if (id !== requestId) return
    rows.value = data.data || []
    total.value = data.total || 0
  } catch {
    if (id !== requestId) return
    rows.value = []
    total.value = 0
  } finally {
    if (id === requestId) loading.value = false
  }
}

function onQueryInput() {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    if (page.value !== 1) {
      page.value = 1 // watch(page) dispara search
    } else {
      search()
    }
  }, 250)
}

function clearQuery() {
  clearTimeout(debounceTimer)
  q.value = ''
  if (page.value !== 1) {
    page.value = 1
  } else {
    search()
  }
}

watch(
  () => props.show,
  (v) => {
    if (v) {
      q.value = ''
      page.value = 1
      clearTimeout(debounceTimer)
      search()
    }
  }
)

watch(page, (next, prev) => {
  if (props.show && next !== prev) search()
})
</script>

<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ title }}</h5>
          <button type="button" class="btn-close" @click="emit('close')"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <div class="input-group">
              <input
                v-model="q"
                class="form-control"
                :placeholder="placeholder"
                autofocus
                @input="onQueryInput"
              />
              <button
                class="btn btn-outline-secondary"
                type="button"
                title="Borrar búsqueda"
                aria-label="Borrar búsqueda"
                :disabled="!q"
                @click="clearQuery"
              >
                <i class="bi bi-x-lg" aria-hidden="true"></i>
              </button>
            </div>
            <div class="form-text">Los resultados se actualizan al escribir.</div>
          </div>
          <div v-if="loading" class="text-secondary mb-2">Buscando…</div>
          <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!loading && rows.length === 0">
                  <td :colspan="columns.length + 1" class="text-center text-secondary py-3">Sin resultados</td>
                </tr>
                <tr v-for="(row, idx) in rows" :key="idx">
                  <td v-for="c in columns" :key="c.key">{{ row[c.key] }}</td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" type="button" @click="emit('select', row)">Elegir</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            <PaginationBar v-model:page="page" :per-page="perPage" :total="total" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
