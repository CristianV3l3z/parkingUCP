import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/styles.css',
                'resources/css/datos.css', 'resources/css/login.css',
                'resources/css/register.css',
                'resources/js/app.js', 'resources/js/register.js',
                'resources/js/login.js', 'resources/js/datos.js', 
                'resources/js/dashboard_readonly.js',
                'resources/js/dashboard_vehiculos.js', 'resources/js/tarifas.js',
                'resources/js/tiquetes.js', 'resources/js/ui-utils.js', 
            'resources/js/checkout_mp.js'],
            refresh: true,
        }),
    ],
});
