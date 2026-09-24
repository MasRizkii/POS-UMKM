import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            buildBase: '/build/',
            injectRegister: false,
            registerType: 'prompt',
            includeManifestIcons: false,
            manifest: {
                id: '/',
                name: 'POS UMKM',
                short_name: 'POS UMKM',
                description: 'Kasir mobile-first untuk UMKM',
                lang: 'id',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                theme_color: '#EF5A35',
                background_color: '#FFF8F5',
                icons: [
                    { src: '/icons/pos-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
                    { src: '/icons/pos-512.png', sizes: '512x512', type: 'image/png', purpose: 'any maskable' },
                ],
            },
            workbox: {
                // Root-level SW controls Laravel routes without requiring a special server header.
                swDest: 'public/sw.js',
                inlineWorkboxRuntime: true,
                globPatterns: ['assets/**/*.{js,css}'],
                modifyURLPrefix: {
                    'assets/': '/build/assets/',
                    'manifest.webmanifest': '/build/manifest.webmanifest',
                },
                additionalManifestEntries: [
                    { url: '/icons/pos-192.png', revision: '1' },
                    { url: '/icons/pos-512.png', revision: '1' },
                ],
                // Never store Laravel HTML/Inertia props, API responses, or mutations.
                navigateFallback: null,
                runtimeCaching: [],
                cleanupOutdatedCaches: true,
                skipWaiting: false,
                clientsClaim: false,
            },
            integration: {
                beforeBuildServiceWorker(options) {
                    options.workbox.additionalManifestEntries = options.workbox.additionalManifestEntries.map((entry) => {
                        if (typeof entry === 'object' && entry.url === 'manifest.webmanifest') {
                            return { ...entry, url: '/build/manifest.webmanifest' };
                        }

                        return entry;
                    });
                },
            },
            devOptions: { enabled: false },
        }),
    ],
});
