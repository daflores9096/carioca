import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import LoginView from '../views/LoginView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'
import HomeView from '../views/HomeView.vue'
import CuentaView from '../views/CuentaView.vue'
import ItemsListView from '../views/items/ItemsListView.vue'
import ItemFormView from '../views/items/ItemFormView.vue'
import ItemDetailView from '../views/items/ItemDetailView.vue'
import ClientesListView from '../views/clientes/ClientesListView.vue'
import ClienteFormView from '../views/clientes/ClienteFormView.vue'
import ProveedoresListView from '../views/proveedores/ProveedoresListView.vue'
import ProveedorFormView from '../views/proveedores/ProveedorFormView.vue'
import VentasListView from '../views/ventas/VentasListView.vue'
import VentaFormView from '../views/ventas/VentaFormView.vue'
import VentaDetailView from '../views/ventas/VentaDetailView.vue'
import ComprasListView from '../views/compras/ComprasListView.vue'
import CompraFormView from '../views/compras/CompraFormView.vue'
import CompraDetailView from '../views/compras/CompraDetailView.vue'
import CompraPrintView from '../views/prints/CompraPrintView.vue'
import VentaPrintView from '../views/prints/VentaPrintView.vue'
import InventariosListView from '../views/inventarios/InventariosListView.vue'
import InventarioFormView from '../views/inventarios/InventarioFormView.vue'
import InventarioDetailView from '../views/inventarios/InventarioDetailView.vue'
import ReportesHubView from '../views/reportes/ReportesHubView.vue'
import ReportesView from '../views/reportes/ReportesView.vue'
import AdminHubView from '../views/admin/AdminHubView.vue'
import UsuariosListView from '../views/admin/UsuariosListView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
    { path: '/forgot-password', name: 'forgot-password', component: ForgotPasswordView, meta: { guest: true } },
    { path: '/reset-password', name: 'reset-password', component: ResetPasswordView, meta: { guest: true } },
    { path: '/', name: 'home', component: HomeView, meta: { requiresAuth: true } },
    { path: '/cuenta', name: 'cuenta', component: CuentaView, meta: { requiresAuth: true } },
    { path: '/items', name: 'items', component: ItemsListView, meta: { requiresAuth: true } },
    { path: '/items/nuevo', name: 'items-nuevo', component: ItemFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/items/:cod/ver', name: 'items-ver', component: ItemDetailView, meta: { requiresAuth: true } },
    { path: '/items/:cod/movimientos', name: 'items-movimientos', component: ItemDetailView, meta: { requiresAuth: true } },
    { path: '/items/:cod', name: 'items-edit', component: ItemFormView, meta: { requiresAuth: true } },
    { path: '/clientes', name: 'clientes', component: ClientesListView, meta: { requiresAuth: true } },
    { path: '/clientes/nuevo', name: 'clientes-nuevo', component: ClienteFormView, meta: { requiresAuth: true } },
    { path: '/clientes/:cod', name: 'clientes-edit', component: ClienteFormView, meta: { requiresAuth: true } },
    { path: '/proveedores', name: 'proveedores', component: ProveedoresListView, meta: { requiresAuth: true, admin: true } },
    { path: '/proveedores/nuevo', name: 'proveedores-nuevo', component: ProveedorFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/proveedores/:cod', name: 'proveedores-edit', component: ProveedorFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/ventas', name: 'ventas', component: VentasListView, meta: { requiresAuth: true } },
    { path: '/ventas/nuevo', name: 'ventas-nuevo', component: VentaFormView, meta: { requiresAuth: true } },
    { path: '/ventas/:cod/imprimir', name: 'ventas-print', component: VentaPrintView, meta: { requiresAuth: true } },
    { path: '/ventas/:cod/imprimir-mod', name: 'ventas-print-mod', component: VentaPrintView, meta: { requiresAuth: true } },
    { path: '/ventas/:cod', name: 'ventas-detail', component: VentaDetailView, meta: { requiresAuth: true } },
    { path: '/ventas/:cod/editar', name: 'ventas-edit', component: VentaFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras', name: 'compras', component: ComprasListView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras/nuevo', name: 'compras-nuevo', component: CompraFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras/:cod/imprimir', name: 'compras-print', component: CompraPrintView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras/:cod/imprimir-mod', name: 'compras-print-mod', component: CompraPrintView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras/:cod', name: 'compras-detail', component: CompraDetailView, meta: { requiresAuth: true, admin: true } },
    { path: '/compras/:cod/editar', name: 'compras-edit', component: CompraFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/inventarios', name: 'inventarios', component: InventariosListView, meta: { requiresAuth: true, admin: true } },
    { path: '/inventarios/nuevo', name: 'inventarios-nuevo', component: InventarioFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/inventarios/:id', name: 'inventarios-detail', component: InventarioDetailView, meta: { requiresAuth: true, admin: true } },
    { path: '/inventarios/:id/editar', name: 'inventarios-edit', component: InventarioFormView, meta: { requiresAuth: true, admin: true } },
    { path: '/reportes', name: 'reportes', component: ReportesHubView, meta: { requiresAuth: true, admin: true } },
    { path: '/reportes/compras', name: 'reportes-compras', component: ReportesView, meta: { requiresAuth: true, admin: true } },
    { path: '/reportes/ventas', name: 'reportes-ventas', component: ReportesView, meta: { requiresAuth: true, admin: true } },
    { path: '/admin', name: 'admin', component: AdminHubView, meta: { requiresAuth: true, admin: true } },
    { path: '/admin/usuarios', name: 'admin-usuarios', component: UsuariosListView, meta: { requiresAuth: true, admin: true } },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.loaded) {
    await auth.fetchMe()
  }
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.guest && auth.isAuthenticated) {
    return { name: 'home' }
  }
  if (to.meta.admin && !auth.isAdmin) {
    return { name: 'home' }
  }
  return true
})

export default router
