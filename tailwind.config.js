import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import { canvas, gradients, neutral, primary, side, status } from './resources/theme/colors.js';

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
                ...status,
            },
            backgroundImage: {
                'brand-gradient': gradients.brand,
                'brand-gradient-alt': gradients.brandAlt,
                'headline-gradient': gradients.headline,
                'card-gradient': gradients.card,
                'hero-glow': gradients.glow,
                'grid-lines': gradients.grid,
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
                xs: '0 1px 2px 0 rgb(0 0 0 / 0.3)',
                snow: '0 1px 3px 0 rgb(0 0 0 / 0.4)',
                'snow-md': '0 4px 6px -1px rgb(0 0 0 / 0.35), 0 2px 4px -2px rgb(0 0 0 / 0.3)',
                'snow-lg': '0 10px 15px -3px rgb(0 0 0 / 0.5), 0 4px 6px -4px rgb(0 0 0 / 0.4)',
                hope: '0 1px 3px 0 rgb(97 156 255 / 0.15), 0 4px 12px -2px rgb(0 0 0 / 0.4)',
                'hope-card': '0 1px 2px 0 rgb(0 0 0 / 0.3), 0 8px 24px -4px rgb(0 0 0 / 0.5)',
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
