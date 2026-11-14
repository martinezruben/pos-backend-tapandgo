/**
 * Colores del panel de administración Tap&Go — ÚNICO lugar donde se definen.
 * Los consumen tailwind.config.js (clases `bg-primary-600`, `text-slate-500`,
 * `bg-side-bg`, …) y resources/js/dashboard.js (gráficos).
 * Cambia un valor aquí y ejecuta `npm run build` para ver el cambio en todo el panel.
 *
 * Paleta de Tap&Go (tapandgo.noxutech.com). Tema oscuro: fondo casi negro azulado,
 * acento azul y un verde menta de apoyo.
 */

export const colors = {
    // Base (variables CSS :root del sitio)
    bg: '#080b12', // --bg, también meta theme-color
    text: '#ecf2ff', // --text, texto principal
    muted: '#8795ad', // --muted, texto secundario
    border: '#ffffff10', // --border, bordes sutiles (blanco ~6%)

    // Superficies (tarjetas y paneles)
    surface: '#101927',
    surfaceAlt: '#1b293c',
    card: '#102037', // usar con opacidad ~0.27
    cardGradient: ['#192334', '#141d2a'],

    // Azul de marca
    primary: '#619cff',
    primaryLight: '#74b2ff',
    primaryGradient: ['#439eff', '#1557d5'], // 145deg, logo y botones
    primaryGradientAlt: ['#4d94ff', '#2c61ce'], // 135deg, botón "Pagar ahora"

    // Acento secundario
    mint: '#7de0c0',
    mintSoft: '#80c4a2',

    // Titular "Otra dimensión." (linear-gradient 110deg)
    headlineGradient: [
        { color: '#a2b9df', stop: '5%' },
        { color: '#5d9fff', stop: '45%' },
        { color: '#b6a1ff', stop: '90%' },
    ],

    // Textos de apoyo (de más claro a más tenue)
    textStrong: '#ffffff',
    textSoft: '#d0dbec',
    textBlue: '#94acce',
    textDim: '#5a87bf',

    // Botón claro de la sección principal
    buttonLight: '#e7efff',
    buttonOutlineBorder: 'rgba(255, 255, 255, 0.125)',

    // Bordes
    borderStrong: '#344157',
    borderMid: '#243750',
    borderSoft: '#1e2e46',

    // Efectos de fondo
    glow: 'rgba(22, 57, 104, 0.314)', // radial-gradient del hero
    gridLine: 'rgba(104, 141, 206, 0.04)', // cuadrícula de fondo
};

/* ------------------------------------------------------------------ *
 * Asignación a las escalas que usa el panel (clases de Tailwind).
 * ------------------------------------------------------------------ */

/** Fondo de página. */
export const canvas = colors.bg;

/**
 * Marca (azul). 600 es el color de botones (texto blanco, contraste AA); 700 su hover.
 * 300-400 se usan como texto/enlace sobre fondo oscuro; 50-200 son fondos suaves de acento.
 */
export const primary = {
    25: '#0c1626',
    50: '#10203a',
    100: '#162f55',
    200: '#1d4380',
    300: colors.primary, // foco, texto de acento
    400: colors.primaryLight, // enlaces, iconos activos
    500: colors.primaryGradientAlt[0], // borde seleccionado
    600: colors.primaryGradientAlt[1], // botones
    700: colors.primaryGradient[1], // hover de botones
    800: '#0f46b0',
    900: '#0a2f78',
};

/**
 * Neutros del tema oscuro. Alimentan `slate-*` y `snow-*` (mismas escalas) con la
 * escala invertida respecto a un tema claro:
 * 25 superficie · 50-100 rellenos suaves · 200-300 bordes · 400 placeholder ·
 * 500-600 texto secundario · 700-900 texto principal.
 */
export const neutral = {
    25: colors.surface,
    50: '#141f30',
    100: colors.surfaceAlt,
    200: colors.borderMid,
    300: colors.borderStrong,
    400: '#6b7d99',
    500: colors.muted,
    600: colors.textBlue,
    700: colors.textSoft,
    800: colors.text,
    900: colors.textStrong,
};

/** Barra lateral. */
export const side = {
    bg: colors.surface,
    active: colors.surfaceAlt,
    border: colors.borderSoft,
    text: colors.textSoft, // enlaces
    title: colors.text, // texto destacado
    muted: colors.muted, // etiquetas de grupo, iconos, pie
    card: colors.cardGradient[0], // tarjeta de usuario
};

/** Degradados de marca (se exponen como `bg-brand-gradient`, `bg-brand-gradient-alt`, `bg-headline-gradient`). */
export const gradients = {
    brand: `linear-gradient(145deg, ${colors.primaryGradient[0]}, ${colors.primaryGradient[1]})`,
    brandAlt: `linear-gradient(135deg, ${colors.primaryGradientAlt[0]}, ${colors.primaryGradientAlt[1]})`,
    headline: `linear-gradient(110deg, ${colors.headlineGradient.map((s) => `${s.color} ${s.stop}`).join(', ')})`,
    card: `linear-gradient(180deg, ${colors.cardGradient[0]}, ${colors.cardGradient[1]})`,
    glow: `radial-gradient(ellipse 80% 60% at 50% -10%, ${colors.glow}, transparent 70%)`,
    grid: `linear-gradient(${colors.gridLine} 1px, transparent 1px), linear-gradient(90deg, ${colors.gridLine} 1px, transparent 1px)`,
};

/**
 * Colores de estado (alertas, etiquetas): sobre fondo oscuro los tonos 50-200 pasan a ser
 * fondos translúcidos y 600-900 a textos claros. Los 500 (puntos, iconos) no cambian.
 */
const tone = (rgb, light) => ({
    50: `rgba(${rgb}, 0.12)`,
    100: `rgba(${rgb}, 0.18)`,
    200: `rgba(${rgb}, 0.3)`,
    600: light,
    700: light,
    800: light,
    900: light,
});
export const status = {
    red: tone('239, 68, 68', '#fca5a5'),
    rose: tone('244, 63, 94', '#fda4af'),
    amber: tone('245, 158, 11', '#fcd34d'),
    yellow: tone('234, 179, 8', '#fde047'),
    emerald: tone('16, 185, 129', colors.mint),
    green: tone('34, 197, 94', '#86efac'),
    sky: tone('14, 165, 233', '#7dd3fc'),
    violet: tone('139, 92, 246', '#c4b5fd'),
    indigo: tone('99, 102, 241', '#a5b4fc'),
    blue: tone('59, 130, 246', '#93c5fd'),
};

/** Gráficos (ApexCharts). */
export const chart = {
    series: [
        colors.primary, // azul de marca
        colors.mint, // menta
        colors.headlineGradient[2].color, // violeta
        colors.primaryGradient[0], // azul claro
        '#22c55e', // verde (métricas positivas)
        colors.textBlue, // azul grisáceo
    ],
    negative: '#f87171', // anulaciones
    axis: colors.muted, // etiquetas de ejes
    grid: colors.borderMid, // líneas de cuadrícula
    surface: colors.surface, // borde de segmentos del donut
    tooltip: 'dark',
};

// Sin `export default`: Tailwind carga este archivo con interop de módulos y un default haría que
// `import { primary }` resolviera a `colors.primary` (un string) en lugar de la escala.
