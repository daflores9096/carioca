import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    // Acceso público vía gateway en localhost:48080
    hmr: {
      clientPort: 48080,
    },
    proxy: {
      '/api': {
        target: 'http://api:80',
        changeOrigin: true,
        timeout: 300000,
        proxyTimeout: 300000,
      },
    },
  },
})
