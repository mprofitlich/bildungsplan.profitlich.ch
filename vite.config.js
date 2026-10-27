import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

// Unter ddev läuft Vite im Container; der Browser holt die Module über den freigegebenen Port.
const ddev = process.env.DDEV_PRIMARY_URL;

// Die Tests brauchen das Laravel-Plugin nicht, und in CI bricht es ab, weil es Vitest für den Dev-Server hält.
const tests = process.env.VITEST;

export default defineConfig({
  plugins: [!tests && laravel({ input: 'src/main.js' }), svelte()],
  server: ddev && {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    origin: `${ddev.replace(/:\d+$/, '')}:5173`,
    cors: { origin: /https?:\/\/([a-z0-9-]+\.)*ddev\.site(:\d+)?$/ },
  },
  test: {
    include: ['src/**/*.test.js'],
  },
});
