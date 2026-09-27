import { globSync } from 'glob';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// jeffersongoncalves/laravel-favicon and laravel-pwa-favicon resolve these
// through Vite::asset(), which requires each file to be its own manifest
// entry — plain <img> references wouldn't need this, but PHP-side asset()
// calls do.
const faviconAssets = globSync('resources/favicon/**/*');

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/app/theme.css',
                'resources/css/filament/guest/theme.css',
                'resources/css/app.css',
                'resources/js/app.js',
                ...faviconAssets,
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
