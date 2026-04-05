import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/admin-locations-map.css';

const FILL_SYNC = '#22c55e';
const FILL_NONE = '#94a3b8';

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;

    return d.innerHTML;
}

function pinSvg(fill) {
    return `<svg viewBox="0 0 40 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <path d="M20 0C11.16 0 4 7.16 4 16c0 11 16 32 16 32s16-21 16-32C36 7.16 28.84 0 20 0z" fill="${fill}"/>
  <circle cx="20" cy="15" r="5.5" fill="white" fill-opacity="0.95"/>
</svg>`;
}

function fillForPeriod(pin, periodKey) {
    const sp = pin.syncInPeriod;
    if (sp == null || typeof sp !== 'object') {
        return FILL_NONE;
    }

    return sp[periodKey] === true ? FILL_SYNC : FILL_NONE;
}

function makeMarkerIcon(p, periodKey) {
    const fill = fillForPeriod(p, periodKey);
    const n = Math.max(0, Math.min(999, parseInt(p.activeDevices, 10) || 0));
    const label = n > 99 ? '99+' : String(n);

    return L.divIcon({
        className: 'admin-loc-marker',
        html: `<div class="admin-loc-marker__pin" title="${escapeHtml(p.name ?? '')}">${pinSvg(fill)}<span class="admin-loc-marker__count">${label}</span></div>`,
        iconSize: [40, 44],
        iconAnchor: [20, 44],
        popupAnchor: [0, -42],
    });
}

function initLocationsMap() {
    const mount = document.getElementById('admin-locations-map');
    const dataEl = document.getElementById('admin-locations-map-data');
    const periodSelect = document.getElementById('admin-locations-map-period');
    if (!mount || !dataEl?.textContent) {
        return;
    }

    let pins;
    try {
        pins = JSON.parse(dataEl.textContent);
    } catch {
        return;
    }

    if (!Array.isArray(pins)) {
        return;
    }

    const map = L.map(mount, {
        zoomControl: true,
        scrollWheelZoom: true,
        attributionControl: true,
    });

    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
    }).addTo(map);

    const valid = pins.filter(
        (p) =>
            p.lat != null &&
            p.lng != null &&
            !Number.isNaN(Number(p.lat)) &&
            !Number.isNaN(Number(p.lng)),
    );

    const latlngs = [];
    /** @type {{ marker: L.Marker; pin: object }[]} */
    const markerEntries = [];

    const applyPeriod = (periodKey) => {
        markerEntries.forEach(({ marker, pin }) => {
            marker.setIcon(makeMarkerIcon(pin, periodKey));
        });
    };

    valid.forEach((p) => {
        const lat = Number(p.lat);
        const lng = Number(p.lng);
        const n = Math.max(0, Math.min(999, parseInt(p.activeDevices, 10) || 0));
        const periodKey = periodSelect?.value || 'today';
        const icon = makeMarkerIcon(p, periodKey);
        const marker = L.marker([lat, lng], { icon }).addTo(map);
        latlngs.push([lat, lng]);
        markerEntries.push({ marker, pin: p });

        const name = escapeHtml(p.name ?? '—');
        const edit =
            p.editUrl != null && p.editUrl !== ''
                ? `<p class="mt-1.5"><a class="font-semibold text-primary-600 hover:text-primary-700" href="${escapeHtml(p.editUrl)}">Editar localidad</a></p>`
                : '';

        marker.bindPopup(
            `<div class="text-[11px] text-slate-800"><p class="font-semibold">${name}</p><p class="mt-0.5 text-slate-500">Dispositivos activos: <span class="font-semibold text-slate-700">${n}</span></p>${edit}</div>`,
        );
    });

    if (periodSelect) {
        periodSelect.addEventListener('change', () => {
            applyPeriod(periodSelect.value);
        });
    }

    if (latlngs.length > 0) {
        map.fitBounds(latlngs, { padding: [36, 36], maxZoom: 14 });
    } else {
        map.setView([4.65, -74.05], 5);
    }

    setTimeout(() => {
        map.invalidateSize();
    }, 0);
    window.addEventListener('resize', () => {
        map.invalidateSize();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLocationsMap);
} else {
    initLocationsMap();
}
