<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Resumen estadístico y libro de Excel con el detalle de una carga de catálogo.
 */
class CatalogImportReport
{
    private const ACTIONS = ['created' => 'Creado', 'updated' => 'Modificado', 'unchanged' => 'Sin cambios'];

    /**
     * Totales de la carga y variaciones de precio, listos para guardar y mostrar.
     *
     * @param  array<string, mixed>  $result  Salida de CatalogExcelService::import()
     * @return array<string, mixed>
     */
    public static function summarize(array $result): array
    {
        $totals = ['read' => 0, 'matched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0];
        foreach ($result['sheets'] as $s) {
            foreach ($totals as $k => $_) {
                $totals[$k] += $s[$k] ?? 0;
            }
        }

        $prices = [];
        foreach ($result['rows'] as $r) {
            if (($r['sheet'] ?? '') !== CatalogExcelService::SHEET_PRODUCTS || ! isset($r['changes']['price']) || $r['action'] !== 'updated') {
                continue;
            }
            [$old, $new] = $r['changes']['price'];
            if ($old === null || $new === null) {
                continue;
            }
            $prices[] = [
                'sku' => $r['sku'] ?? '', 'name' => $r['name'] ?? '', 'old' => $old, 'new' => $new,
                'diff' => round($new - $old, 2), 'pct' => $old > 0 ? round((($new - $old) / $old) * 100, 2) : null,
            ];
        }
        usort($prices, fn ($a, $b) => abs($b['pct'] ?? 0) <=> abs($a['pct'] ?? 0));
        $pcts = array_filter(array_column($prices, 'pct'), fn ($v) => $v !== null);

        return [
            'sheets' => $result['sheets'],
            'totals' => $totals,
            'moved' => $result['moved'],
            'ignored' => $result['ignored'],
            'errors' => array_slice($result['errors'], 0, 200),
            'price' => [
                'changed' => count($prices),
                'up' => count(array_filter($prices, fn ($p) => $p['diff'] > 0)),
                'down' => count(array_filter($prices, fn ($p) => $p['diff'] < 0)),
                'avg_pct' => $pcts === [] ? null : round(array_sum($pcts) / count($pcts), 2),
                'max_up' => $prices === [] ? null : collect($prices)->sortByDesc('pct')->first(),
                'max_down' => $prices === [] ? null : collect($prices)->sortBy('pct')->first(),
                'top' => array_slice($prices, 0, 10),
            ],
        ];
    }

    /** @param array<string, mixed> $result @param array<string, mixed> $meta file_name, admin_name, create_missing, at */
    public static function workbook(array $result, array $summary, array $meta): Spreadsheet
    {
        $book = new Spreadsheet;
        $t = $summary['totals'];

        // Resumen
        $sh = $book->getActiveSheet();
        $sh->setTitle('Resumen');
        $rows = [
            ['Informe de carga de catálogo'],
            [''],
            ['Archivo', $meta['file_name']],
            ['Cargado por', $meta['admin_name'] ?? ''],
            ['Fecha', Carbon::parse($meta['at'])->format('d/m/Y H:i')],
            ['Crear familias/subfamilias inexistentes', $meta['create_missing'] ? 'Sí' : 'No'],
            [''],
            ['Resultado', 'Filas leídas', 'Cruzaron con existentes', 'Creados', 'Modificados', 'Sin cambios', 'Con error (omitidas)'],
        ];
        foreach ($summary['sheets'] as $name => $s) {
            $rows[] = [$name, $s['read'], $s['matched'], $s['created'], $s['updated'], $s['unchanged'], $s['errors']];
        }
        $rows[] = ['Total', $t['read'], $t['matched'], $t['created'], $t['updated'], $t['unchanged'], $t['errors']];
        $p = $summary['price'];
        $rows = array_merge($rows, [
            [''],
            ['Variaciones de precio'],
            ['Productos con precio modificado', $p['changed']],
            ['Subieron', $p['up']],
            ['Bajaron', $p['down']],
            ['Variación promedio', $p['avg_pct'] === null ? '—' : $p['avg_pct'].' %'],
            ['Mayor subida', $p['max_up'] ? "{$p['max_up']['sku']} · {$p['max_up']['name']} ({$p['max_up']['pct']} %)" : '—'],
            ['Mayor bajada', $p['max_down'] ? "{$p['max_down']['sku']} · {$p['max_down']['name']} ({$p['max_down']['pct']} %)" : '—'],
            [''],
            ['Productos que cambiaron de subfamilia', $summary['moved']],
        ]);
        foreach ($summary['ignored'] as $note) {
            $rows[] = [$note];
        }
        foreach ($rows as $i => $r) {
            foreach ($r as $c => $v) {
                self::put($sh, $c + 1, $i + 1, $v);
            }
        }
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        self::header($sh, 'A8:G8');
        $sh->getStyle('A'.(9 + count($summary['sheets'])).':G'.(9 + count($summary['sheets'])))->getFont()->setBold(true);
        $sh->getStyle('A'.(11 + count($summary['sheets'])))->getFont()->setBold(true);
        $sh->getColumnDimension('A')->setWidth(42);
        foreach (['B', 'C', 'D', 'E', 'F', 'G'] as $c) {
            $sh->getColumnDimension($c)->setWidth(22);
        }

        // Productos
        $prod = $book->createSheet();
        $prod->setTitle('Productos');
        self::table($prod, ['Fila', 'SKU', 'Producto', 'Acción', 'Familia', 'Subfamilia', 'Precio anterior', 'Precio nuevo', 'Variación', 'Variación %', 'Costo anterior', 'Costo nuevo', 'Otros cambios', 'Observación'],
            array_map(function ($r) {
                $c = $r['changes'] ?? [];
                [$po, $pn] = $c['price'] ?? [null, null];
                [$co, $cn] = $c['cost'] ?? [null, null];
                $other = collect($c)->except(['price', 'cost'])->map(fn ($v, $k) => self::fieldName($k).': '.self::fmt($v[0]).' → '.self::fmt($v[1]))->implode('; ');
                $diff = ($po !== null && $pn !== null) ? round($pn - $po, 2) : null;

                return [$r['line'], $r['sku'] ?? '', $r['name'] ?? '', self::ACTIONS[$r['action']] ?? $r['action'], $r['family'] ?? '', $r['subfamily'] ?? '',
                    $po, $pn, $diff, ($diff !== null && $po > 0) ? round($diff / $po * 100, 2) : null, $co, $cn, $other,
                    trim(($r['note'] ?? '').(! empty($r['moved']) ? ' Cambió de subfamilia.' : ''))];
            }, array_values(array_filter($result['rows'], fn ($r) => $r['sheet'] === CatalogExcelService::SHEET_PRODUCTS))));

        // Variaciones de precio
        $var = $book->createSheet();
        $var->setTitle('Variaciones de precio');
        $allPrices = [];
        foreach ($result['rows'] as $r) {
            if ($r['sheet'] === CatalogExcelService::SHEET_PRODUCTS && $r['action'] === 'updated' && isset($r['changes']['price'])) {
                [$o, $n] = $r['changes']['price'];
                if ($o !== null && $n !== null) {
                    $allPrices[] = [$r['sku'] ?? '', $r['name'] ?? '', $o, $n, round($n - $o, 2), $o > 0 ? round(($n - $o) / $o * 100, 2) : null, $n > $o ? 'Subió' : 'Bajó'];
                }
            }
        }
        usort($allPrices, fn ($a, $b) => abs($b[5] ?? 0) <=> abs($a[5] ?? 0));
        self::table($var, ['SKU', 'Producto', 'Precio anterior', 'Precio nuevo', 'Variación', 'Variación %', 'Tendencia'], $allPrices);

        // Subfamilias / Familias
        foreach ([CatalogExcelService::SHEET_SUBFAMILIES => ['Fila', 'Subfamilia', 'Familia', 'Acción', 'Cambios'], CatalogExcelService::SHEET_FAMILIES => ['Fila', 'Familia', 'Acción', 'Cambios']] as $title => $headers) {
            $ws = $book->createSheet();
            $ws->setTitle($title);
            $data = [];
            foreach ($result['rows'] as $r) {
                if ($r['sheet'] !== $title) {
                    continue;
                }
                $ch = collect($r['changes'] ?? [])->map(fn ($v, $k) => self::fieldName($k).': '.self::fmt($v[0]).' → '.self::fmt($v[1]))->implode('; ');
                $data[] = $title === CatalogExcelService::SHEET_SUBFAMILIES
                    ? [$r['line'], $r['name'] ?? '', $r['family'] ?? '', self::ACTIONS[$r['action']] ?? $r['action'], trim($ch.' '.($r['note'] ?? ''))]
                    : [$r['line'], $r['name'] ?? '', self::ACTIONS[$r['action']] ?? $r['action'], trim($ch.' '.($r['note'] ?? ''))];
            }
            self::table($ws, $headers, $data);
        }

        // Errores
        $err = $book->createSheet();
        $err->setTitle('Errores');
        self::table($err, ['Detalle (hoja, fila y motivo)'], array_map(fn ($e) => [$e], $result['errors']));
        $err->getColumnDimension('A')->setWidth(140);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** @param list<string> $headers @param list<list<mixed>> $rows */
    private static function table(Worksheet $ws, array $headers, array $rows): void
    {
        foreach ($headers as $i => $h) {
            self::put($ws, $i + 1, 1, $h);
        }
        foreach ($rows as $r => $row) {
            foreach ($row as $c => $v) {
                self::put($ws, $c + 1, $r + 2, $v);
            }
        }
        $last = Coordinate::stringFromColumnIndex(count($headers));
        self::header($ws, "A1:{$last}1");
        $ws->freezePane('A2');
        for ($c = 1; $c <= count($headers); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
    }

    private static function header(Worksheet $ws, string $range): void
    {
        $ws->getStyle($range)->getFont()->setBold(true);
        $ws->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9F0F7');
    }

    private static function put(Worksheet $ws, int $col, int $row, mixed $v): void
    {
        $coord = Coordinate::stringFromColumnIndex($col).$row;
        if ($v === null || $v === '') {
            return;
        }
        $ws->setCellValueExplicit($coord, $v, is_int($v) || is_float($v) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
    }

    private static function fmt(mixed $v): string
    {
        return $v === null ? '—' : (is_bool($v) ? ($v ? 'Sí' : 'No') : (is_array($v) ? implode(' · ', array_filter($v)) : (string) $v));
    }

    private static function fieldName(string $k): string
    {
        return ['name' => 'Nombre', 'barcode' => 'Código de barras', 'description' => 'Descripción', 'image_url' => 'Imagen', 'subfamily_id' => 'Subfamilia', 'family_id' => 'Familia',
            'tax_rate' => 'IVA', 'is_active' => 'Activo', 'is_favorite' => 'Favorito', 'sku' => 'SKU', 'price' => 'Precio', 'cost' => 'Costo'][$k] ?? $k;
    }
}
