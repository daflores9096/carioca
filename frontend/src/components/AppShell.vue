<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

defineEmits(['logout'])
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const links = computed(() => {
  const all = [
    { to: '/', label: 'Inicio', admin: false },
    { to: '/items', label: 'Artículos', admin: false },
    { to: '/ventas', label: 'Ventas', admin: false },
    { to: '/compras', label: 'Compras', admin: true },
    { to: '/clientes', label: 'Clientes', admin: false },
    { to: '/proveedores', label: 'Proveedores', admin: true },
    { to: '/inventarios', label: 'Inventario', admin: true },
    { to: '/reportes', label: 'Reportes', admin: true },
    { to: '/admin', label: 'Admin', admin: true },
  ]
  return all.filter((l) => !l.admin || auth.isAdmin)
})

function go(to) {
  router.push(to)
}
</script>

<template>
  <div>
    <nav class="navbar navbar-expand-lg navbar-dark bg-carioca">
      <div class="container">
        <a class="navbar-brand fw-bold" href="#" @click.prevent="go('/')">CARIOCA</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mainNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div id="mainNav" class="collapse navbar-collapse">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li v-for="l in links" :key="l.to" class="nav-item">
              <a
                class="nav-link"
                :class="{ active: route.path === l.to || (l.to !== '/' && route.path.startsWith(l.to)) }"
                href="#"
                @click.prevent="go(l.to)"
              >{{ l.label }}</a>
            </li>
          </ul>
          <div class="d-flex align-items-center gap-3 text-white">
            <a
              class="small d-none d-sm-inline text-white text-decoration-none"
              href="#"
              @click.prevent="go('/cuenta')"
            >{{ auth.user?.usuario_nombre }}</a>
            <button class="btn btn-sm btn-outline-light" type="button" @click="$emit('logout')">
              Cerrar sesión
            </button>
          </div>
        </div>
      </div>
    </nav>
    <main class="container py-4">
      <slot />
    </main>
  </div>
</template>
