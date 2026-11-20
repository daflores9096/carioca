<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, required: true },
  perPage: { type: Number, required: true },
  total: { type: Number, required: true },
})
const emit = defineEmits(['update:page'])

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.perPage)))
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1))
const to = computed(() => Math.min(props.total, props.page * props.perPage))
</script>

<template>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
    <div class="small text-secondary">
      {{ from }}–{{ to }} de {{ total }}
    </div>
    <div class="btn-group">
      <button
        class="btn btn-outline-secondary btn-sm"
        type="button"
        :disabled="page <= 1"
        @click="emit('update:page', page - 1)"
      >Anterior</button>
      <span class="btn btn-outline-secondary btn-sm disabled">{{ page }} / {{ totalPages }}</span>
      <button
        class="btn btn-outline-secondary btn-sm"
        type="button"
        :disabled="page >= totalPages"
        @click="emit('update:page', page + 1)"
      >Siguiente</button>
    </div>
  </div>
</template>
