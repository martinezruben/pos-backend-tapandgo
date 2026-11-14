import './bootstrap';
import './table-sort';

import Alpine from 'alpinejs';
import { money } from './format';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    // Cascada de selects: los selects con data-depends solo muestran opciones
    // cuyo data-parent coincida con el valor del select padre; al cambiar el
    // padre se limpia la selección del hijo. Funciona en filtros de grid y form.
    Alpine.data('selectCascade', () => ({
        init() {
            const root = this.$el;
            const byId = (id) => root.querySelector('#' + CSS.escape(id));

            root.querySelectorAll('select[data-depends]').forEach((child) => {
                const parent = byId(child.dataset.depends);
                if (!parent || parent.tagName !== 'SELECT') {
                    return;
                }
                const apply = () => {
                    const val = parent.value;
                    [...child.options].forEach((opt) => {
                        if (opt.dataset.parent === undefined) {
                            return;
                        }
                        const show = val === '' || opt.dataset.parent === val;
                        opt.hidden = !show;
                        if (!show && opt.selected) {
                            opt.selected = false;
                        }
                    });
                };
                parent.addEventListener('change', apply);
                apply();
            });
        },
    }));

    // Asistente de promociones: tipo -> alcance (familia > subfamilia > producto) -> parámetros -> resumen.
    // Los datos se envían en inputs ocultos para que viajen aunque el paso no esté visible.
    Alpine.data('promotionWizard', (cfg) => ({
        step: cfg.startStep || 1,
        catalog: cfg.catalog,
        serverErrors: cfg.errors || {},
        type: cfg.type || '',
        scopeType: cfg.scopeType || 'GLOBAL',
        familyId: cfg.familyId || '',
        subfamilyId: cfg.subfamilyId || '',
        productId: cfg.productId || '',
        value: cfg.value ?? '',
        buyQty: cfg.buyQty ?? '',
        payQty: cfg.payQty ?? '',
        name: cfg.name || '',
        nameTouched: !!cfg.name,
        simQty: 3,
        simPrice: 50,
        types: [
            { key: 'BUNDLE', title: 'Lleva N, paga M', desc: 'Ej. 2x1 o 3x2: se llevan varias unidades y se paga menos.', example: 'Llevas 2, pagas 1' },
            { key: 'PERCENT', title: '% de descuento', desc: 'Un porcentaje menos sobre el precio de lista.', example: '15 % menos en cada unidad' },
            { key: 'AMOUNT', title: 'Monto fijo de descuento', desc: 'Una cantidad en pesos que se resta a cada unidad.', example: 'RD$10 menos por unidad' },
            { key: 'PRICE', title: 'Precio de oferta', desc: 'Cada unidad pasa a costar un precio fijo menor.', example: 'Cada unidad a RD$40' },
        ],
        steps: ['Tipo', 'Alcance', 'Parámetros', 'Resumen'],

        init() {
            const p = this.product;
            if (p) {
                this.subfamilyId = p.subfamily_id || this.subfamilyId;
            }
            const sf = this.subfamilies.find((x) => x.id === this.subfamilyId);
            if (sf) {
                this.familyId = sf.family_id || this.familyId;
            }
            if (this.priceRef !== null) {
                this.simPrice = this.priceRef;
            }
            const firstError = Object.keys(this.serverErrors)[0];
            if (firstError) {
                this.step = this.stepOfField(firstError);
            }
        },

        stepOfField(f) {
            if (f === 'type') return 1;
            if (['product_id', 'subfamily_id', 'family_id'].includes(f)) return 2;
            if (['value', 'buy_qty', 'pay_qty'].includes(f)) return 3;
            return 4;
        },

        get hasBeenReviewed() { return this.step === 4; },
        get isBundle() { return this.type === 'BUNDLE'; },
        get scopes() {
            const noGlobal = ['PRICE', 'BUNDLE'].includes(this.type);
            return [
                { key: 'GLOBAL', label: 'Todos los productos', disabled: noGlobal, hint: noGlobal ? 'Este tipo requiere un objetivo concreto.' : '' },
                { key: 'FAMILY', label: 'Una familia', disabled: false, hint: '' },
                { key: 'SUBFAMILY', label: 'Una subfamilia', disabled: false, hint: '' },
                { key: 'PRODUCT', label: 'Un producto', disabled: false, hint: '' },
            ];
        },
        get families() { return this.catalog.families; },
        get subfamilies() {
            return this.catalog.subfamilies.filter((s) => !this.familyId || s.family_id === this.familyId);
        },
        get products() {
            const subIds = this.subfamilyId
                ? [this.subfamilyId]
                : this.familyId ? this.catalog.subfamilies.filter((s) => s.family_id === this.familyId).map((s) => s.id) : null;
            return this.catalog.products.filter((p) => !subIds || subIds.includes(p.subfamily_id));
        },
        get product() { return this.catalog.products.find((p) => p.id === this.productId) || null; },
        get priceRef() { return this.scopeType === 'PRODUCT' && this.product ? this.product.price : null; },

        pickType(key) {
            this.type = key;
            if (['PRICE', 'BUNDLE'].includes(key) && this.scopeType === 'GLOBAL') {
                this.scopeType = 'FAMILY';
            }
        },
        pickScope(key) {
            this.scopeType = key;
            if (key === 'GLOBAL') { this.familyId = ''; this.subfamilyId = ''; this.productId = ''; }
            if (key === 'FAMILY') { this.subfamilyId = ''; this.productId = ''; }
            if (key === 'SUBFAMILY') { this.productId = ''; }
        },
        onFamilyChange() {
            if (this.subfamilyId && !this.subfamilies.some((s) => s.id === this.subfamilyId)) { this.subfamilyId = ''; }
            if (this.productId && !this.products.some((p) => p.id === this.productId)) { this.productId = ''; }
        },
        onSubfamilyChange() {
            const sf = this.catalog.subfamilies.find((s) => s.id === this.subfamilyId);
            if (sf) { this.familyId = sf.family_id; }
            if (this.productId && !this.products.some((p) => p.id === this.productId)) { this.productId = ''; }
        },
        onProductChange() {
            // La familia y la subfamilia se deducen del producto: no pueden contradecirlo
            const p = this.product;
            if (p) {
                this.subfamilyId = p.subfamily_id || '';
                const sf = this.catalog.subfamilies.find((s) => s.id === this.subfamilyId);
                this.familyId = sf ? sf.family_id : '';
                this.simPrice = p.price;
            }
        },

        get scopeName() {
            if (this.scopeType === 'PRODUCT') return this.product?.name || '';
            if (this.scopeType === 'SUBFAMILY') return this.catalog.subfamilies.find((s) => s.id === this.subfamilyId)?.name || '';
            if (this.scopeType === 'FAMILY') return this.families.find((f) => f.id === this.familyId)?.name || '';
            return 'todos los productos';
        },
        get scopePhrase() {
            const n = this.scopeName;
            return { GLOBAL: 'todos los productos', FAMILY: `la familia ${n}`, SUBFAMILY: `la subfamilia ${n}`, PRODUCT: n }[this.scopeType];
        },

        get scopeOk() {
            return this.scopeType === 'GLOBAL'
                ? !['PRICE', 'BUNDLE'].includes(this.type)
                : this.scopeType === 'FAMILY' ? !!this.familyId
                : this.scopeType === 'SUBFAMILY' ? !!this.subfamilyId
                : !!this.productId;
        },
        get paramError() {
            const v = Number(this.value);
            if (this.isBundle) {
                const b = Number(this.buyQty); const p = Number(this.payQty);
                if (!Number.isInteger(b) || !Number.isInteger(p) || b < 2 || p < 1) return 'Indica «lleva» y «paga» como números enteros (ej. 2 y 1).';
                if (p >= b) return '«Paga» debe ser menor que «lleva».';
                return '';
            }
            if (!(v > 0)) return 'Indica un valor mayor que cero.';
            if (this.type === 'PERCENT' && v > 100) return 'El porcentaje no puede superar 100.';
            if (this.priceRef !== null && ['AMOUNT', 'PRICE'].includes(this.type) && v >= this.priceRef) {
                return `Debe ser menor que el precio de lista (${money(this.priceRef)}).`;
            }
            return '';
        },
        get stepOk() {
            return [null, !!this.type, this.scopeOk, this.paramError === '', this.name.trim() !== ''];
        },
        go(n) {
            // Solo se puede avanzar si los pasos previos están completos
            for (let i = 1; i < n; i++) { if (!this.stepOk[i]) { this.step = i; return; } }
            this.step = n;
            if (n === 4 && !this.nameTouched) { this.name = this.suggestedName; }
        },

        get suggestedName() {
            const n = this.scopeType === 'GLOBAL' ? 'Todo' : this.scopeName;
            const v = Number(this.value);
            return {
                BUNDLE: `${n} ${this.buyQty}x${this.payQty}`,
                PERCENT: `${n} ${v}% off`,
                AMOUNT: `${n} -${money(v)}`,
                PRICE: `${n} a ${money(v)}`,
            }[this.type] || '';
        },
        get summary() {
            const v = Number(this.value);
            const t = this.scopePhrase;
            return {
                BUNDLE: `Lleva ${this.buyQty} y paga ${this.payQty} en ${t}.`,
                PERCENT: `${v} % de descuento por unidad en ${t}.`,
                AMOUNT: `${money(v)} de descuento por unidad en ${t}.`,
                PRICE: `Precio de oferta de ${money(v)} por unidad en ${t}.`,
            }[this.type] || '';
        },
        get sim() {
            const q = Math.max(1, Math.floor(Number(this.simQty) || 1));
            const price = Number(this.simPrice) || 0;
            const v = Number(this.value);
            let pay = price * q;
            let note = '';
            if (this.type === 'BUNDLE') {
                const b = Number(this.buyQty); const m = Number(this.payQty);
                const groups = Math.floor(q / b);
                const rest = q - groups * b;
                pay = (groups * m + rest) * price;
                note = `${groups} grupo(s) de ${b} (pagas ${m} c/u) + ${rest} suelta(s) a precio normal.`;
            } else if (this.type === 'PERCENT') {
                pay = price * (1 - v / 100) * q;
            } else if (this.type === 'AMOUNT') {
                pay = Math.max(price - v, 0) * q;
            } else if (this.type === 'PRICE') {
                pay = v * q;
            }
            return { q, normal: price * q, pay, saved: price * q - pay, note };
        },
        money,
    }));

    Alpine.data('locationPairingToken', () => ({
        open: false,
        loading: false,
        code: '',
        expiresAt: null,
        locationId: null,
        locationName: '',
        remainingLabel: '15:00',
        _interval: null,

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        pairingUrl() {
            if (!this.locationId) {
                return '';
            }

            return `/admin/locations/${encodeURIComponent(this.locationId)}/pairing-token`;
        },

        openFor(detail) {
            if (!detail?.locationId) {
                return;
            }
            this.locationId = detail.locationId;
            this.locationName = typeof detail.locationName === 'string' ? detail.locationName : '';
            this.openModal();
        },

        async openModal() {
            this.open = true;
            this.loading = true;
            try {
                await this.fetchStatus();
                if (!this.code) {
                    await this.postAction('ensure');
                }
                this.startTimer();
            } finally {
                this.loading = false;
            }
        },

        closeModal() {
            this.open = false;
            this.stopTimer();
        },

        async fetchStatus() {
            const res = await fetch(this.pairingUrl(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await res.json();
            this.applyPayload(data);
        },

        applyPayload(data) {
            this.code = data.code ? String(data.code) : '';
            this.expiresAt = data.expires_at != null ? Number(data.expires_at) : null;
            this.tick();
        },

        async postAction(action) {
            const res = await fetch(this.pairingUrl(), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ action }),
            });
            if (!res.ok) {
                throw new Error('Pairing request failed');
            }
            const data = await res.json();
            this.applyPayload(data);
        },

        async regenerate() {
            this.loading = true;
            try {
                await this.postAction('regenerate');
                this.startTimer();
            } finally {
                this.loading = false;
            }
        },

        startTimer() {
            this.stopTimer();
            this._interval = window.setInterval(() => this.tick(), 1000);
            this.tick();
        },

        stopTimer() {
            if (this._interval != null) {
                window.clearInterval(this._interval);
                this._interval = null;
            }
        },

        tick() {
            if (!this.expiresAt) {
                this.remainingLabel = '—';
                return;
            }
            const endMs = this.expiresAt * 1000;
            const sec = Math.max(0, Math.floor((endMs - Date.now()) / 1000));
            if (sec === 0) {
                this.code = '';
                this.expiresAt = null;
                this.remainingLabel = 'Caducado';
                this.stopTimer();
                return;
            }
            const m = Math.floor(sec / 60);
            const s = sec % 60;
            this.remainingLabel = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },
    }));

    Alpine.data('auditLogDetail', (detailBaseUrl = '') => ({
        detailBaseUrl: typeof detailBaseUrl === 'string' ? detailBaseUrl : '',
        open: false,
        loading: false,
        error: '',
        row: null,

        actionLabel(action) {
            const labels = {
                created: 'Creación',
                updated: 'Edición',
                deleted: 'Eliminación',
                login: 'Login',
                logout: 'Logout',
            };

            return labels[action] ?? action;
        },

        changeEntries(changes) {
            if (changes == null || typeof changes !== 'object') {
                return [];
            }

            return Object.entries(changes).map(([field, pair]) => ({
                field,
                before: Array.isArray(pair) ? String(pair[0] ?? '—') : '—',
                after: Array.isArray(pair) ? String(pair[1] ?? '—') : '—',
            }));
        },

        async openDetail(id) {
            this.open = true;
            this.loading = true;
            this.error = '';
            this.row = null;
            const base = this.detailBaseUrl.replace(/\/$/, '');
            try {
                const res = await fetch(`${base}/${encodeURIComponent(id)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.error = 'No se pudo cargar el detalle.';

                    return;
                }
                this.row = await res.json();
            } catch {
                this.error = 'Error de red.';
            } finally {
                this.loading = false;
            }
        },

        closeDetail() {
            this.open = false;
            this.row = null;
            this.error = '';
        },
    }));

    Alpine.data('apiRequestLogDetail', (detailBaseUrl = '') => ({
        detailBaseUrl: typeof detailBaseUrl === 'string' ? detailBaseUrl : '',
        open: false,
        loading: false,
        error: '',
        row: null,
        copied: '',

        async copy(kind, text) {
            try {
                await navigator.clipboard.writeText(String(text ?? ''));
            } catch {
                // Sin permiso del portapapeles (p. ej. HTTP): se selecciona el texto en un área temporal
                const area = document.createElement('textarea');
                area.value = String(text ?? '');
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
            this.copied = kind;
            setTimeout(() => {
                if (this.copied === kind) {
                    this.copied = '';
                }
            }, 1500);
        },

        jsonBlock(formatted, raw) {
            const hasFormatted =
                formatted !== null &&
                formatted !== undefined &&
                String(formatted).length > 0;
            const s = hasFormatted ? formatted : raw ?? '';
            const t = s === null || s === undefined ? '' : String(s);

            return t === '' ? '(vacío)' : t;
        },

        async openDetail(id) {
            this.open = true;
            this.loading = true;
            this.error = '';
            this.row = null;
            const base = this.detailBaseUrl.replace(/\/$/, '');
            try {
                const res = await fetch(`${base}/${encodeURIComponent(id)}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.error =
                        res.status === 403
                            ? 'No autorizado.'
                            : res.status === 404
                              ? 'Registro no encontrado.'
                              : `Error ${res.status}`;

                    return;
                }
                this.row = await res.json();
            } catch {
                this.error = 'No se pudo cargar el detalle.';
            } finally {
                this.loading = false;
            }
        },

        closeDetail() {
            this.open = false;
            this.row = null;
            this.error = '';
        },
    }));

    Alpine.data('transactionLineItemsDetail', (baseUrl = '') => ({
        baseUrl: typeof baseUrl === 'string' ? baseUrl : '',
        open: false,
        loading: false,
        error: '',
        detail: null,

        async openDetail(id) {
            this.open = true;
            this.loading = true;
            this.error = '';
            this.detail = null;
            const base = this.baseUrl.replace(/\/$/, '');
            try {
                const res = await fetch(`${base}/${encodeURIComponent(id)}/line-items`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.error =
                        res.status === 403
                            ? 'No autorizado.'
                            : res.status === 404
                              ? 'Registro no encontrado.'
                              : `Error ${res.status}`;

                    return;
                }
                this.detail = await res.json();
            } catch {
                this.error = 'No se pudo cargar el detalle.';
            } finally {
                this.loading = false;
            }
        },

        closeDetail() {
            this.open = false;
            this.detail = null;
            this.error = '';
        },
    }));

    Alpine.data('transactionExcelModal', (locations = []) => {
        const localYmd = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');

            return `${y}-${m}-${day}`;
        };

        return {
            open: false,
            dateFrom: '',
            dateTo: '',
            locationId: '',
            error: '',
            loading: false,
            locations: Array.isArray(locations) ? locations : [],

            init() {
                const t = new Date();
                this.dateTo = localYmd(t);
                const f = new Date(t);
                f.setDate(f.getDate() - 7);
                this.dateFrom = localYmd(f);
            },

            csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            },

            async submit() {
                this.error = '';
                this.loading = true;
                try {
                    const res = await fetch('/admin/transactions/excel/export', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            date_from: this.dateFrom,
                            date_to: this.dateTo,
                            location_id: this.locationId || null,
                        }),
                    });

                    if (res.status === 422) {
                        const data = await res.json();
                        const fromErrors = data.errors ? Object.values(data.errors).flat() : [];
                        this.error =
                            (typeof data.message === 'string' && data.message && data.message !== 'The given data was invalid.')
                                ? data.message
                                : fromErrors[0] ?? 'Datos no válidos.';

                        return;
                    }

                    if (!res.ok) {
                        this.error = 'No se pudo generar el archivo.';

                        return;
                    }

                    const blob = await res.blob();
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const cd = res.headers.get('Content-Disposition');
                    const m = cd && /filename="?([^";\n]+)"?/i.exec(cd);
                    a.download = m ? m[1].trim() : 'transacciones.xlsx';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(url);
                    this.open = false;
                } catch {
                    this.error = 'Error de red.';
                } finally {
                    this.loading = false;
                }
            },
        };
    });

    Alpine.data('transactionsReportModal', (locations = []) => ({
        open: false,
        dateFrom: '',
        dateTo: '',
        locationId: '',
        includeDetail: false,
        error: '',
        loading: false,
        locations: Array.isArray(locations) ? locations : [],

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        async submit() {
            this.error = '';
            this.loading = true;
            try {
                const res = await fetch('/admin/transactions/report/export', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': this.csrfToken(),
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        date_from: this.dateFrom || null,
                        date_to: this.dateTo || null,
                        location_id: this.locationId || null,
                        include_detail: this.includeDetail,
                    }),
                });

                if (res.status === 422) {
                    const data = await res.json();
                    const fromErrors = data.errors ? Object.values(data.errors).flat() : [];
                    this.error =
                        (typeof data.message === 'string' && data.message && data.message !== 'The given data was invalid.')
                            ? data.message
                            : fromErrors[0] ?? 'Datos no válidos.';

                    return;
                }

                if (!res.ok) {
                    this.error = 'No se pudo generar el archivo.';

                    return;
                }

                const blob = await res.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                const cd = res.headers.get('Content-Disposition');
                const m = cd && /filename="?([^";\n]+)"?/i.exec(cd);
                a.download = m ? m[1].trim() : 'reporte-transacciones.xlsx';
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
                this.open = false;
            } catch {
                this.error = 'Error de red.';
            } finally {
                this.loading = false;
            }
        },
    }));
});

Alpine.start();
