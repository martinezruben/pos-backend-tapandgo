/**
 * Colores del panel de administración Tap&Go — ÚNICO lugar donde se definen.
 * Los consumen tailwind.config.js (clases `bg-primary-600`, `text-slate-500`,
 * `bg-side-card`, …) y resources/js/dashboard.js (gráficos).
 * Cambia un valor aquí y ejecuta `npm run build` para ver el cambio en todo el panel.
 */

/** Marca: 600 es el color principal (botones, enlaces, estado activo). */
export const primary = {
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
};

/**
 * Neutros cálidos. Alimentan `slate-*` y `snow-*` (mismas escalas):
 * 25 superficies, 50-100 rellenos suaves, 200-300 bordes,
 * 400 placeholder, 500-600 texto secundario, 700-900 tinta.
 */
export const neutral = {
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
};

/** Fondo de página. */
export const canvas = '#fbf8f4';

/** Extras de la barra lateral oscura (el fondo y el activo salen de `neutral` 800 y 700). */
export const side = {
    title: '#f3ebe1', // texto destacado
    muted: '#a8998a', // etiquetas de grupo, iconos, pie
    card: '#241914', // tarjeta de usuario
};

/** Gráficos (ApexCharts). */
export const chart = {
    series: [primary[600], primary[400], neutral[600], '#22c55e', neutral[400], primary[300]],
    negative: '#ef4444', // anulaciones
    axis: neutral[500], // etiquetas de ejes
    grid: neutral[200], // líneas de cuadrícula
    surface: neutral[25], // borde de segmentos del donut
};
