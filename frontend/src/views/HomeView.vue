<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AppShell from '../components/AppShell.vue'

const auth = useAuthStore()
const router = useRouter()

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}

const modules = [
  { title: 'Artículos', desc: 'Catálogo e inventario', icon: 'bi-box-seam', admin: false, to: '/items', soon: false },
  { title: 'Ventas', desc: 'Salidas y notas', icon: 'bi-cart-check', admin: false, to: '/ventas', soon: false },
  { title: 'Clientes', desc: 'Fichas y movimientos', icon: 'bi-people', admin: false, to: '/clientes', soon: false },
  { title: 'Compras', desc: 'Ingresos de mercadería', icon: 'bi-truck', admin: true, to: '/compras', soon: false },
  { title: 'Proveedores', desc: 'Contactos de compra', icon: 'bi-building', admin: true, to: '/proveedores', soon: false },
  { title: 'Inventario físico', desc: 'Conteos y aplicación', icon: 'bi-clipboard-check', admin: true, to: '/inventarios', soon: false },
  { title: 'Reportes', desc: 'Compras y ventas', icon: 'bi-graph-up', admin: true, to: '/reportes', soon: false },
  { title: 'Usuarios / Respaldos', desc: 'Administración', icon: 'bi-shield-lock', admin: true, to: '/admin', soon: false },
]
</script>

<template>
  <AppShell @logout="logout">
    <div class="mb-4">
      <h1 class="h3 text-carioca mb-1">Panel principal</h1>
      <p class="text-secondary mb-0">
        Bienvenido, <strong>{{ auth.user?.usuario_nombre }}</strong>
        <span v-if="auth.isAdmin" class="badge text-bg-primary ms-2">Admin</span>
        <span v-else class="badge text-bg-secondary ms-2">Operador</span>
      </p>
    </div>

    <div class="row g-3">
      <div
        v-for="m in modules.filter((x) => !x.admin || auth.isAdmin)"
        :key="m.title"
        class="col-12 col-md-6 col-xl-3"
      >
        <button
          class="card h-100 border-0 shadow-sm w-100 text-start"
          type="button"
          :disabled="m.soon"
          @click="m.to && router.push(m.to)"
        >
          <div class="card-body">
            <div class="d-flex align-items-start gap-3">
              <div class="fs-3 text-carioca"><i :class="m.icon"></i></div>
              <div>
                <h2 class="h6 mb-1">{{ m.title }}</h2>
                <p class="small text-secondary mb-2">{{ m.desc }}</p>
                <span v-if="m.soon" class="badge text-bg-light text-secondary border">Próximamente</span>
                <span v-else class="badge text-bg-success-subtle text-success border border-success-subtle">Disponible</span>
              </div>
            </div>
          </div>
        </button>
      </div>
    </div>
  </AppShell>
</template>
