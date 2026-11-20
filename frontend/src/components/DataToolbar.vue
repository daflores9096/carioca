<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Buscar…' },
  debounceMs: { type: Number, default: 250 },
})
const emit = defineEmits(['update:modelValue', 'search'])

const local = ref(props.modelValue)
let timer = 0

watch(
  () => props.modelValue,
  (v) => {
    if (v !== local.value) local.value = v
  }
)

function onInput(e) {
  const value = e.target.value
  local.value = value
  emit('update:modelValue', value)
  clearTimeout(timer)
  timer = window.setTimeout(() => {
    emit('search')
  }, props.debounceMs)
}

function onSubmit() {
  clearTimeout(timer)
  emit('search')
}

function clear() {
  if (!local.value) return
  clearTimeout(timer)
  local.value = ''
  emit('update:modelValue', '')
  emit('search')
}
</script>

<template>
  <form class="row g-2 align-items-center mb-3" @submit.prevent="onSubmit">
    <div class="col-sm-8 col-md-6 col-lg-4">
      <div class="input-group">
        <input
          class="form-control"
          :value="local"
          :placeholder="placeholder"
          @input="onInput"
        />
        <button
          class="btn btn-outline-secondary"
          type="button"
          title="Borrar búsqueda"
          aria-label="Borrar búsqueda"
          :disabled="!local"
          @click="clear"
        >
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </div>
    </div>
    <div class="col-auto">
      <button class="btn btn-carioca" type="submit">Buscar</button>
    </div>
    <div class="col-auto">
      <slot />
    </div>
  </form>
</template>
