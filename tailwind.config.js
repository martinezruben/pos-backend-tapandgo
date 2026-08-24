import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                '2xs': ['0.6875rem', { lineHeight: '1rem' }],
            },
            colors: {
                /** Fondo página estilo Hope UI */
                canvas: '#F4F7FC',
                snow: {
                    25: '#fcfdfe',
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                },
                primary: {
                    25: '#f0f7ff',
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
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
                snow: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                'snow-md': '0 4px 6px -1px rgb(15 23 42 / 0.06), 0 2px 4px -2px rgb(15 23 42 / 0.05)',
                'snow-lg': '0 10px 15px -3px rgb(15 23 42 / 0.06), 0 4px 6px -4px rgb(15 23 42 / 0.05)',
                hope: '0 1px 3px 0 rgb(37 99 235 / 0.08), 0 4px 12px -2px rgb(15 23 42 / 0.06)',
                'hope-card': '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 8px 24px -4px rgb(15 23 42 / 0.08)',
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