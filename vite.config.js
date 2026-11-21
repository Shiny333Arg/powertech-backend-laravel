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
                'resources/js/app.js',

                // Cliente centralizado de la API del panel. Entra como entrada
                // propia porque se importa con Vite::asset() desde el layout de
                // gestion y desde el ingreso del panel, no desde app.js: tiene
                // que resolverse por el manifiesto.
                'resources/js/gestion/api.js',

                // Las pantallas de categorias, migradas a la API. Entran como
                // entradas propias por el mismo motivo que api.js: las importa
                // la vista con Vite::asset(), asi que tienen que resolverse por
                // el manifiesto. Los modulos que ellas importan —api.js y
                // avisos.js— quedan en un chunk compartido, que es lo que hace
                // que el token siga viviendo en UNA sola variable de modulo.
                'resources/js/gestion/categorias/index.js',
                'resources/js/gestion/categorias/formulario.js',

                // Las pantallas de productos, migradas a la API con el mismo
                // criterio que las de categorias.
                'resources/js/gestion/productos/index.js',
                'resources/js/gestion/productos/formulario.js'
            ],
            refresh: true,
        }),
    ],

    build: {
        rollupOptions: {
            // api.js es un modulo que se IMPORTA, no un script que se ejecuta
            // por su efecto colateral.
            //
            // Por defecto Vite compila las entradas con
            // preserveEntrySignatures: false, asumiendo que se cargan con
            // <script src> y que sus exports no le importan a nadie. Con eso,
            // api.js —que solo exporta funciones y no hace nada al cargarse— se
            // elimina entero por tree-shaking: el build avisa "Generated an
            // empty chunk: api" y deja un archivo de 0 kB. En produccion, el
            // import del layout fallaria.
            //
            // 'strict' conserva los exports de cada entrada. Para app.js y los
            // CSS no cambia nada, porque no exportan.
            preserveEntrySignatures: 'strict',
        },
    },
});
