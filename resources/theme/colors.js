/**
 * Colores del panel de administración Tap&Go — ÚNICO lugar donde se definen.
 * Los consumen tailwind.config.js (clases `bg-primary-600`, `text-slate-500`,
 * `bg-side-card`, …) y resources/js/dashboard.js (gráficos).
 * Cambia un valor aquí y ejecuta `npm run build` para ver el cambio en todo el panel.
 */

/** Marca: 600 es el color principal (botones, enlaces, estado activo). */
export const primary = {
    25: '#f4f6fa',
    50: '#eaeff6',
    100: '#d4dfec',
    200: '#b3c4dc',
    300: '#879ec6', // Azul claro de la paleta
    400: '#6c81ac',
    500: '#5e729b',
    600: '#54668e', // Color principal (azul corporativo medio)
    700: '#455376',
    800: '#363955', // Azul marino profundo / fondo de la imagen
    900: '#232539',
};

/**
 * Neutros fríos/corporativos. Alimentan `slate-*` y `snow-*` (mismas escalas):
 * 25 superficies, 50-100 rellenos suaves, 200-300 bordes,
 * 400 placeholder, 500-600 texto secundario, 700-900 tinta.
 */
export const neutral = {
    25: '#ffffff',
    50: '#f5f6e6', // Blanco hueso / crema suave de la paleta
    100: '#e8e8e8', // Gris claro de la paleta
    200: '#d6d7dc',
    300: '#b8bac4',
    400: '#8e919f',
    500: '#646778',
    600: '#494c63',
    700: '#363955', // Azul oscuro pizarra (activo en sidebar / texto fuerte)
    800: '#27293e', // Fondo oscuro de la barra lateral
    900: '#191a29',
};

/** Fondo de página. */
export const canvas = '#f5f6e6';

/** Extras de la barra lateral oscura (el fondo y el activo salen de `neutral` 800 y 700). */
export const side = {
    title: '#f5f6e6', // texto destacado
    muted: '#9ea6bd', // etiquetas de grupo, iconos, pie
    card: '#363955', // tarjeta de usuario
};

/** Gráficos (ApexCharts). */
export const chart = {
    series: [
        primary[600], // #54668e (Azul principal)
        primary[300], // #879ec6 (Azul cielo)
        '#59598e', // Violeta/índigo corporativo de la paleta
        primary[800], // #363955 (Azul marino oscuro)
        '#22c55e', // Verde para métricas positivas
        neutral[400], // Gris neutro
    ],
    negative: '#ef4444', // anulaciones
    axis: neutral[500], // etiquetas de ejes
    grid: neutral[200], // líneas de cuadrícula
    surface: neutral[25], // borde de segmentos del donut
};
