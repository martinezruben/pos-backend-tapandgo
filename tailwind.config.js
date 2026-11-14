import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Http/Controllers/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['DM Sans', 'Public Sans', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            fontSize: {
                '2xs': ['0.6875rem', { lineHeight: '1rem' }],
            },
            colors: {
                /**
                 * Sistema de diseño Tap&Go (rediseño Tailwind UI):
                 * fondo crema, tinta café, una sola tinta de acento,
                 * el color se reserva para estado/acción, no para el cromo.
                 */
                canvas: '#fbf8f4',
                snow: {
                    25: '#ffffff',
                    50: '#f5efe8',
                    100: '#efe7dd',
                    200: '#e6dacb',
                    300: '#d5c4ae',
                    400: '#a39d94',
                    500: '#66635c',
                    600: '#4a4a46',
                    700: '#3a2418',
                    800: '#1a120e',
                    900: '#110b08',
                },
                slate: {
                    50: '#f5efe8',
                    100: '#efe7dd',
                    200: '#e6dacb',
                    300: '#d5c4ae',
                    400: '#a39d94',
                    500: '#66635c',
                    600: '#4a4a46',
                    700: '#3a2418',
                    800: '#1a120e',
                    900: '#110b08',
                },
                /** Marca Tap&Go: café (#6b4a2e) sobre crema; 600 = color de marca */
                primary: {
                    25: '#fbf8f4',
                    50: '#f7f2ec',
                    100: '#ede3d7',
                    200: '#d5c4ae',
                    300: '#c2a689',
                    400: '#a88462',
                    500: '#8a6a48',
                    600: '#6b4a2e',
                    700: '#573b23',
                    800: '#3a2418',
                    900: '#1a120e',
                },
                /** Acento secundario (botones alternativos, charts) */
                hope: {
                    teal: '#0d9488',
                    cyan: '#06b6d4',
                },
            },
            keyframes: {
                actIn: {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'act-in': 'actIn 0.38s ease-out both',
            },
            boxShadow: {
                xs: '0 1px 2px 0 rgb(26 18 14 / 0.05)',
                snow: '0 1px 3px 0 rgb(26 18 14 / 0.1)',
                'snow-md': '0 4px 6px -1px rgb(26 18 14 / 0.06), 0 2px 4px -2px rgb(26 18 14 / 0.05)',
                'snow-lg': '0 10px 15px -3px rgb(26 18 14 / 0.06), 0 4px 6px -4px rgb(26 18 14 / 0.05)',
                hope: '0 1px 3px 0 rgb(107 74 46 / 0.08), 0 4px 12px -2px rgb(26 18 14 / 0.06)',
                'hope-card': '0 1px 2px 0 rgb(26 18 14 / 0.04), 0 8px 24px -4px rgb(26 18 14 / 0.08)',
            },
            scale: {
                '125': '1.25',
                '150': '1.5',
            },
        },
    },

    plugins: [
        forms({
            strategy: 'class',
        }),
    ],
};
