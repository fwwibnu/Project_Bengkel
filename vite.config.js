import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',                  // CSS halaman Blade buatan sendiri (nota, laporan cetak)
                'resources/js/app.js',
                'resources/css/filament/app/theme.css',   // tema panel Filament
            ],
            refresh: true,
        }),
        // Plugin Tailwind v4 dihapus; Tailwind v3 berjalan lewat postcss.config.js
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});