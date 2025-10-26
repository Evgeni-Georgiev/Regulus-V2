import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
// import axios from 'axios';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            // hotFile: 'storage/vite.hot',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    // The Vue plugin will re-write asset URLs, when referenced
                    // in Single File Components, to point to the Laravel web
                    // server. Setting this to `null` allows the Laravel plugin
                    // to instead re-write asset URLs to point to the Vite
                    // server instead.
                    base: null,

                    // The Vue plugin will parse absolute URLs and treat them
                    // as absolute paths to files on disk. Setting this to
                    // `false` will leave absolute URLs un-touched so they can
                    // reference assets in the public directory as expected.
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: process.env.VITE_PORT || 5180,
        strictPort: true,
        hmr: {
            host: 'localhost',
            port: process.env.VITE_HMR_PORT || 5180,
            protocol: 'ws',
        },
        cors: {
            origin: [
                'http://localhost',
                'http://localhost:80', 
                'http://localhost:89',
                'http://127.0.0.1', 
                'http://127.0.0.1:80',
                'http://127.0.0.1:89',
                'http://regulus.local',
                'http://regulus.local:89'
            ],
            credentials: true,
        },
        watch: {
            usePolling: true,
        },
    },
    build: {
        rollupOptions: {
            external: ['axios'], // Add axios here
        },
    },
    resolve: {
        alias: {
            '@comps': '/resources/js/components',
            '@css': '/resources/css',
            '@images': '/resources/images',
            '@': path.resolve(__dirname, 'resources/js'),
        }
    }
});
