<script setup>
import { useRouter } from 'vue-router'
import { useAuthLayout } from '../../composables/useAuthLayout'
import AppShell from '../../components/AppShell.vue'
import PageHeader from '../../components/PageHeader.vue'

const { logout } = useAuthLayout()
const router = useRouter()

const reports = [
  {
    title: 'Compras',
    desc: 'Ingresos de mercadería por rango de fechas',
    to: '/reportes/compras',
    icon: 'bi-truck',
  },
  {
    title: 'Ventas',
    desc: 'Salidas y notas por rango de fechas',
    to: '/reportes/ventas',
    icon: 'bi-cart-check',
  },
]
</script>

<template>
  <AppShell @logout="logout">
    <PageHeader title="Reportes" subtitle="Informes de compras y ventas" />

    <div class="row g-3">
      <div v-for="r in reports" :key="r.to" class="col-12 col-md-6">
        <button
          class="card h-100 border-0 shadow-sm w-100 text-start"
          type="button"
          @click="router.push(r.to)"
        >
          <div class="card-body">
            <div class="d-flex align-items-start gap-3">
              <div class="fs-3 text-carioca"><i :class="r.icon"></i></div>
              <div>
                <h2 class="h6 mb-1">{{ r.title }}</h2>
                <p class="small text-secondary mb-0">{{ r.desc }}</p>
              </div>
            </div>
          </div>
        </button>
      </div>
    </div>
  </AppShell>
</template>
