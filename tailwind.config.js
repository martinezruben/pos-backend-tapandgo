import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import { canvas, neutral, primary, side } from './resources/theme/colors.js';

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
                canvas,
                snow: neutral,
                slate: neutral,
                primary,
                side,
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
                xs: '0 1px 2px 0 rgb(25 26 41 / 0.05)',
                snow: '0 1px 3px 0 rgb(25 26 41 / 0.1)',
                'snow-md': '0 4px 6px -1px rgb(25 26 41 / 0.06), 0 2px 4px -2px rgb(25 26 41 / 0.05)',
                'snow-lg': '0 10px 15px -3px rgb(25 26 41 / 0.06), 0 4px 6px -4px rgb(25 26 41 / 0.05)',
                hope: '0 1px 3px 0 rgb(84 102 142 / 0.08), 0 4px 12px -2px rgb(25 26 41 / 0.06)',
                'hope-card': '0 1px 2px 0 rgb(25 26 41 / 0.04), 0 8px 24px -4px rgb(25 26 41 / 0.08)',
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
