import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { primary } from '../theme/colors';

// Selector de ubicación del formulario de localidades: clic en el mapa o arrastrar el pin
// rellena latitud/longitud; escribir las coordenadas mueve el pin.
const DEFAULT_CENTER = [18.7357, -70.1627]; // República Dominicana
const BRAND = primary[600];

function pinIcon() {
    return L.divIcon({
        className: 'location-picker-pin',
        html: `<svg viewBox="0 0 40 48" width="34" height="41" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20 0C11.16 0 4 7.16 4 16c0 11 16 32 16 32s16-21 16-32C36 7.16 28.84 0 20 0z" fill="${BRAND}"/><circle cx="20" cy="15" r="5.5" fill="white"/></svg>`,
        iconSize: [34, 41],
        iconAnchor: [17, 41],
    });
}

function init() {
    const el = document.getElementById('location-picker-map');
    const latInput = document.getElementById('f-latitude');
    const lngInput = document.getElementById('f-longitude');
    if (!el || !latInput || !lngInput) {
        return;
    }

    const num = (v) => {
        const n = parseFloat(v);

        return Number.isFinite(n) ? n : null;
    };
    const lat0 = num(latInput.value);
    const lng0 = num(lngInput.value);
    const hasPoint = lat0 !== null && lng0 !== null;

    const map = L.map(el, { scrollWheelZoom: false }).setView(hasPoint ? [lat0, lng0] : DEFAULT_CENTER, hasPoint ? 15 : 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    let marker = null;
    const status = document.getElementById('location-picker-status');
    const say = (t) => {
        if (status) {
            status.textContent = t;
        }
    };

    function writeInputs(latlng) {
        latInput.value = latlng.lat.toFixed(7);
        lngInput.value = latlng.lng.toFixed(7);
        say(`Ubicación fijada: ${latInput.value}, ${lngInput.value}`);
    }

    function place(latlng, { pan = false } = {}) {
        if (!marker) {
            marker = L.marker(latlng, { draggable: true, icon: pinIcon() }).addTo(map);
            marker.on('dragend', () => writeInputs(marker.getLatLng()));
        } else {
            marker.setLatLng(latlng);
        }
        if (pan) {
            map.setView(latlng, Math.max(map.getZoom(), 15));
        }
    }

    function clear() {
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        latInput.value = '';
        lngInput.value = '';
        say('Sin ubicación. Haz clic en el mapa para fijarla.');
    }

    function fromInputs() {
        const lat = num(latInput.value);
        const lng = num(lngInput.value);
        if (lat !== null && lng !== null && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
            place(L.latLng(lat, lng), { pan: true });
            say(`Ubicación fijada: ${lat}, ${lng}`);
        }
    }

    if (hasPoint) {
        place(L.latLng(lat0, lng0));
        say(`Ubicación fijada: ${lat0}, ${lng0}`);
    }

    map.on('click', (e) => {
        place(e.latlng);
        writeInputs(e.latlng);
    });
    latInput.addEventListener('change', fromInputs);
    lngInput.addEventListener('change', fromInputs);

    document.getElementById('location-picker-clear')?.addEventListener('click', clear);
    document.getElementById('location-picker-locate')?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            say('Tu navegador no permite obtener la ubicación.');

            return;
        }
        say('Buscando tu ubicación…');
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const ll = L.latLng(pos.coords.latitude, pos.coords.longitude);
                place(ll, { pan: true });
                writeInputs(ll);
            },
            () => say('No se pudo obtener tu ubicación. Haz clic en el mapa.'),
            { enableHighAccuracy: true, timeout: 10_000 },
        );
    });

    // El contenedor puede calcular su tamaño después de pintar
    setTimeout(() => map.invalidateSize(), 150);
}

document.addEventListener('DOMContentLoaded', init);
