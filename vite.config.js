import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/components/header.css',
                'resources/css/gestion/category/index.css',
                'resources/css/gestion/item/index.css',
                'resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});
