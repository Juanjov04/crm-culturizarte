import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

// Rojo corporativo del prototipo (Figuras 9-11 del documento Capstone).
const brand = {
    50: '#fef2f2',
    100: '#fee2e2',
    200: '#fecaca',
    300: '#fca5a5',
    400: '#f87171',
    500: '#e53e3e',
    600: '#d32f2f',
    700: '#b91c1c',
    800: '#991b1b',
    900: '#7f1d1d',
    950: '#450a0a',
};

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand,
                // Las vistas heredadas de Breeze usan "indigo"; se redirige al rojo de la marca.
                indigo: brand,
                gray: colors.slate,
                navy: {
                    800: '#141c2f',
                    900: '#0e1629',
                    950: '#0a1020',
                },
            },
        },
    },

    plugins: [forms],
};
