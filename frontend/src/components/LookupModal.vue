<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { api, qs } from '../api/client'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Buscar' },
  endpoint: { type: String, required: true },
  columns: { type: Array, required: true }, // [{key,label}]
  placeholder: { type: String, default: 'Buscar…' },
  perPage: { type: Number, default: 100 },
})
const emit = defineEmits(['close', 'select'])

const q = ref('')
const rows = ref([])
const total = ref(0)
const page = ref(0)
const loading = ref(false)
const loadingMore = ref(false)
const listEl = ref(null)
let debounceTimer = 0
let requestId = 0

const pageSize = computed(() => Number(props.perPage) || 100)
const hasMore = computed(() => rows.value.length < total.value)
const statusText = computed(() => {
  if (total.value === 0 && !loading.value) return 'Sin resultados'
  if (rows.value.length === 0) return ''
  return `Mostrando ${rows.value.length} de ${total.value}`
})

async function fetchPage(nextPage, { append }) {
  const id = ++requestId
  if (append) loadingMore.value = true
  else loading.value = true

  try {
    const data = await api.get(
      `${props.endpoint}${qs({
        q: q.value,
        page: nextPage,
        per_page: pageSize.value,
        sort: undefined,
      })}`
    )
    if (id !== requestId) return

    const batch = data.data || []
    total.value = Number(data.total) || 0
    page.value = nextPage
    rows.value = append ? rows.value.concat(batch) : batch
  } catch {
    if (id !== requestId) return
    if (!append) {
      rows.value = []
      total.value = 0
      page.value = 0
    }
  } finally {
    if (id === requestId) {
      loading.value = false
      loadingMore.value = false
    }
  }
}

function resetAndSearch() {
  clearTimeout(debounceTimer)
  page.value = 0
  rows.value = []
  total.value = 0
  fetchPage(1, { append: false }).then(() => {
    nextTick(() => {
      if (listEl.value) listEl.value.scrollTop = 0
    })
  })
}

function onQueryInput() {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    resetAndSearch()
  }, 250)
}

function clearQuery() {
  clearTimeout(debounceTimer)
  q.value = ''
  resetAndSearch()
}

function onScroll() {
  const el = listEl.value
  if (!el || loading.value || loadingMore.value || !hasMore.value) return
  const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 80
  if (nearBottom) {
    fetchPage(page.value + 1, { append: true })
  }
}

watch(
  () => props.show,
  (v) => {
    if (v) {
      q.value = ''
      clearTimeout(debounceTimer)
      resetAndSearch()
    }
  }
)
</script>

<template>
  <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45)">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ title }}</h5>
          <button type="button" class="btn-close" @click="emit('close')"></button>
        </div>
        <div class="modal-body d-flex flex-column" style="max-height: min(75vh, 640px)">
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
            <div class="form-text">Escribí para filtrar. Desplazate al final para cargar más.</div>
          </div>

          <div
            ref="listEl"
            class="table-responsive flex-grow-1 border rounded"
            style="overflow-y: auto; min-height: 240px"
            @scroll="onScroll"
          >
            <table class="table table-hover table-sm align-middle mb-0">
              <thead class="table-light sticky-top">
                <tr>
                  <th v-for="c in columns" :key="c.key">{{ c.label }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading && rows.length === 0">
                  <td :colspan="columns.length + 1" class="text-center text-secondary py-4">Buscando…</td>
                </tr>
                <tr v-else-if="!loading && rows.length === 0">
                  <td :colspan="columns.length + 1" class="text-center text-secondary py-4">Sin resultados</td>
                </tr>
                <tr v-for="(row, idx) in rows" :key="`${row.cod_item ?? row.cod_cli ?? row.cod_pro ?? idx}-${idx}`">
                  <td v-for="c in columns" :key="c.key">{{ row[c.key] }}</td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" type="button" @click="emit('select', row)">Elegir</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="loadingMore" class="text-center text-secondary small py-2">Cargando más…</div>
            <div v-else-if="rows.length > 0 && !hasMore" class="text-center text-secondary small py-2">No hay más resultados</div>
          </div>

          <div v-if="statusText" class="small text-secondary mt-2 mb-0">{{ statusText }}</div>
        </div>
      </div>
    </div>
  </div>
</template>
