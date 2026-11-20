<script setup>
defineProps({
  loading: { type: Boolean, default: false },
  empty: { type: Boolean, default: false },
  emptyText: { type: String, default: 'Sin resultados' },
  colSpan: { type: Number, default: 5 },
})
</script>

<template>
  <div class="list-panel card border-0 shadow-sm position-relative">
    <div
      v-if="loading"
      class="list-panel__overlay"
      aria-live="polite"
      aria-busy="true"
    >
      <div class="spinner-border text-carioca spinner-border-sm" role="status"></div>
      <span class="small ms-2">Actualizando…</span>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" :class="{ 'opacity-50': loading }">
        <thead class="table-light">
          <slot name="head" />
        </thead>
        <tbody>
          <tr v-if="empty && !loading">
            <td :colspan="colSpan" class="text-center text-secondary py-4">{{ emptyText }}</td>
          </tr>
          <slot />
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.list-panel__overlay {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.55);
  pointer-events: none;
}
</style>
