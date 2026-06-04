import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// proxy: يمرّر نداءات الـ API للـ backend الحقيقي أثناء الاختبار المحلي فقط
const apiProxy = {
  target: 'https://operation.aqleeat.co/wp-json',
  changeOrigin: true,
  secure: false,
}

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'react-vendor': ['react', 'react-dom', 'react-router-dom'],
          'query-vendor': ['@tanstack/react-query'],
          'ui-vendor': ['recharts'],
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
  server: {
    proxy: {
      '/aqop-jwt': apiProxy,
      '/aqop': apiProxy,
    },
  },
  preview: {
    proxy: {
      '/aqop-jwt': apiProxy,
      '/aqop': apiProxy,
    },
  },
})