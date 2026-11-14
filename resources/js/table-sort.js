// Ordenamiento por encabezado para tablas pequeñas sin paginar (reportes, dashboards, roles):
// <table data-sort-table>. Los grids paginados ordenan en el servidor.
const ARROWS = `<svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="sort-up" d="m4.5 6.5 3.5-3.5 3.5 3.5"/><path class="sort-down" d="m4.5 9.5 3.5 3.5 3.5-3.5"/></svg>`;

function valueOf(cell) {
    const raw = (cell?.dataset.sort ?? cell?.textContent ?? '').trim();
    if (raw === '' || raw === '—' || raw === '-') {
        return { empty: true };
    }
    const numeric = raw.replace(/[$%\s,]|u\.$/gi, '');
    if (/^[-+]?\d+(\.\d+)?$/.test(numeric)) {
        return { num: parseFloat(numeric) };
    }

    return { text: raw.toLocaleLowerCase('es') };
}

function compare(a, b) {
    if (a.empty || b.empty) {
        return a.empty === b.empty ? 0 : a.empty ? 1 : -1; // vacíos siempre al final
    }
    if (a.num !== undefined && b.num !== undefined) {
        return a.num - b.num;
    }

    return String(a.num ?? a.text).localeCompare(String(b.num ?? b.text), 'es', { numeric: true });
}

function enhance(table) {
    const headRow = table.tHead?.rows[0];
    const body = table.tBodies[0];
    if (!headRow || !body) {
        return;
    }
    const headers = [...headRow.cells];
    const state = { col: -1, dir: 'asc' };

    headers.forEach((th, col) => {
        if (th.hasAttribute('data-nosort') || th.textContent.trim() === '') {
            return;
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'group inline-flex items-center gap-1.5 font-inherit text-inherit hover:text-slate-800 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-600/25 rounded';
        if (th.classList.contains('text-right')) {
            button.classList.add('w-full', 'justify-end');
        }
        button.title = 'Ordenar por ' + th.textContent.trim();
        button.innerHTML = `<span>${th.innerHTML}</span>${ARROWS}`;
        th.textContent = '';
        th.appendChild(button);
        th.setAttribute('aria-sort', 'none');
        th.querySelectorAll('.sort-up, .sort-down').forEach((p) => p.setAttribute('opacity', '.3'));

        button.addEventListener('click', () => {
            state.dir = state.col === col && state.dir === 'asc' ? 'desc' : 'asc';
            state.col = col;
            const rows = [...body.rows].filter((r) => !(r.cells.length === 1 && r.cells[0].colSpan > 1));
            const factor = state.dir === 'asc' ? 1 : -1;
            rows
                .map((row, i) => ({ row, i, v: valueOf(row.cells[col]) }))
                .sort((x, y) => (x.v.empty || y.v.empty ? compare(x.v, y.v) : factor * compare(x.v, y.v)) || x.i - y.i)
                .forEach(({ row }) => body.appendChild(row));

            headers.forEach((h, c) => {
                if (!h.hasAttribute('aria-sort')) {
                    return;
                }
                const on = c === col;
                h.setAttribute('aria-sort', on ? (state.dir === 'asc' ? 'ascending' : 'descending') : 'none');
                h.querySelector('.sort-up')?.setAttribute('opacity', on && state.dir === 'asc' ? '1' : '.3');
                h.querySelector('.sort-down')?.setAttribute('opacity', on && state.dir === 'desc' ? '1' : '.3');
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('table[data-sort-table]').forEach(enhance);
});
