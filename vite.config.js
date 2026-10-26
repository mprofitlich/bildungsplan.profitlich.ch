import { svelte } from '@sveltejs/vite-plugin-svelte';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

// Unter ddev läuft Vite im Container; der Browser holt die Module über den freigegebenen Port.
const ddev = process.env.DDEV_PRIMARY_URL;

export default defineConfig({
  plugins: [laravel({ input: 'src/main.js' }), svelte()],
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
