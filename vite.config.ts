import { defineConfig } from 'vite';
import { resolve } from 'node:path';
export default defineConfig({
  root: 'web',
  build: { outDir: '../dist', emptyOutDir: true, rollupOptions: { input: { home: resolve('web/index.html'), portal: resolve('web/portal/index.html') } } },
  server: { port: 5173 }
});
