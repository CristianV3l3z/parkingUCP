import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/styles.css',
                'resources/css/datos.css', 'resources/css/login.css',
                'resources/css/register.css',
                'resources/js/app.js', 'resources/js/register.js',],
            refresh: true,
        }),
    ],
});
