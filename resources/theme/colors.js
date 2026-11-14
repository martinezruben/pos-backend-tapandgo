/**
 * Colores del panel de administración Tap&Go — ÚNICO lugar donde se definen.
 * Los consumen tailwind.config.js (clases `bg-primary-600`, `text-slate-500`,
 * `bg-side-card`, …) y resources/js/dashboard.js (gráficos).
 * Cambia un valor aquí y ejecuta `npm run build` para ver el cambio en todo el panel.
 *
 * Paleta base: naranja #ee6c23 · pizarra #2e3f50 · azul acero #567a99 · azul hielo #d6e6f3 · azul cielo #29a5e1
 */

/**
 * Marca (naranja). 500 es el naranja de la paleta; 600 es el color de botones y enlaces
 * (el naranja oscurecido para que el texto blanco cumpla contraste AA 4.5:1).
 */
export const primary = {
    25: '#fff8f3',
    50: '#fef0e7',
    100: '#fddcc6',
    200: '#fbbf96',
    300: '#f79f66',
    400: '#f28645',
    500: '#ee6c23', // Naranja de la paleta
    600: '#bf520f', // Botones, enlaces, estado activo
    700: '#9a420c',
    800: '#78340f',
    900: '#4d220a',
};

/**
 * Neutros pizarra/azulados. Alimentan `slate-*` y `snow-*` (mismas escalas):
 * 25 superficies, 50-100 rellenos suaves, 200-300 bordes,
 * 400 placeholder, 500-600 texto secundario, 700-900 tinta.
 */
export const neutral = {
    25: '#ffffff',
    50: '#f4f8fc', // Fondos suaves (azul hielo muy claro)
    100: '#e9f0f7',
    200: '#d6e6f3', // Azul hielo de la paleta (bordes)
    300: '#b4c8da',
    400: '#8499ad', // Placeholder
    500: '#566d83', // Texto secundario
    600: '#3f5368', // Texto
    700: '#2e3f50', // Pizarra de la paleta (activo en sidebar / texto fuerte)
    800: '#1f2b38', // Fondo de la barra lateral
    900: '#141d27',
};

/** Fondo de página. */
export const canvas = '#f4f8fc';

/** Extras de la barra lateral oscura (el fondo y el activo salen de `neutral` 800 y 700). */
export const side = {
    title: '#eaf2f9', // texto destacado
    muted: '#9fb3c6', // etiquetas de grupo, iconos, pie
    card: '#26343f', // tarjeta de usuario
};

/** Gráficos (ApexCharts). */
export const chart = {
    series: [
        primary[500], // naranja
        '#567a99', // azul acero
        '#29a5e1', // azul cielo
        neutral[700], // pizarra
        '#22c55e', // verde (métricas positivas)
        neutral[400], // gris
    ],
    negative: '#ef4444', // anulaciones
    axis: neutral[500], // etiquetas de ejes
    grid: neutral[200], // líneas de cuadrícula
    surface: neutral[25], // borde de segmentos del donut
};
