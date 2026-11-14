<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Subfamily;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExcelController extends Controller
{
    private const HEADERS = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'description',
        'image_url',
        'subfamily_id',
        'price',
        'cost',
        'tax_rate',
        'is_active',
        'is_favorite',
        'subfamily',
    ];

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeExcel($request);

        $filename = 'productos-'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function (): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Productos');
            $subfamilyLabels = Subfamily::query()->with('family')->get()->mapWithKeys(fn (Subfamily $s) => [$s->id => $s->admin_label]);
            $cell = static function (Worksheet $sheet, int $col, int $row, mixed $value): void {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col).$row, $value);
            };
            foreach (self::HEADERS as $col => $header) {
                $cell($sheet, $col + 1, 1, $header);
            }

            $row = 2;
            Product::query()
                ->withTrashed()
                ->orderBy('id')
                ->chunk(500, function ($products) use ($sheet, &$row, $cell, $subfamilyLabels): void {
                    foreach ($products as $p) {
                        $cell($sheet, 1, $row, $p->id);
                        $cell($sheet, 2, $row, $p->sku);
                        $cell($sheet, 3, $row, $p->barcode);
                        $cell($sheet, 4, $row, $p->name);
                        $cell($sheet, 5, $row, $p->description);
                        $cell($sheet, 6, $row, $p->image_url);
                        $cell($sheet, 7, $row, $p->subfamily_id);
                        $cell($sheet, 8, $row, $p->price);
                        $cell($sheet, 9, $row, $p->cost);
                        $cell($sheet, 10, $row, $p->tax_rate);
                        $cell($sheet, 11, $row, $p->is_active ? 1 : 0);
                        $cell($sheet, 12, $row, $p->is_favorite ? 1 : 0);
                        $cell($sheet, 13, $row, $subfamilyLabels[$p->subfamily_id] ?? null);
                        $row++;
                    }
                });

            $sheet->freezePane('A2');
            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);

            $this->addInstructionsSheet($spreadsheet);
            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorizeExcel($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        if ($rows === []) {
            return back()->with('status', 'El archivo está vacío.');
        }

        $headerRow = array_shift($rows);
        $index = $this->headerIndexMap($headerRow);

        foreach (['sku', 'name'] as $h) {
            if (! isset($index[$h])) {
                return back()->withErrors(['file' => 'Falta la columna obligatoria: '.$h.'. Usa el archivo exportado como base.']);
            }
        }

        $updated = 0;
        $created = 0;
        $errors = [];
        $subfamiliesByName = Subfamily::query()->with('family')->get()->flatMap(fn (Subfamily $s) => [
            mb_strtolower($s->admin_label) => $s->id,
            mb_strtolower((string) $s->name) => $s->id,
        ]);

        DB::transaction(function () use ($rows, $index, &$updated, &$created, &$errors, $subfamiliesByName): void {
            foreach ($rows as $n => $row) {
                $line = $n + 2;
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $productId = $this->nullableString($this->cell($row, $index, 'product_id'));
                if ($productId !== null && ! Str::isUuid($productId)) {
                    $errors[] = "Fila {$line}: product_id no es un UUID válido (déjalo vacío para un producto nuevo).";

                    continue;
                }

                $sku = $this->nullableString($this->cell($row, $index, 'sku'));
                $name = $this->nullableString($this->cell($row, $index, 'name'));
                if ($sku === null || $name === null) {
                    $errors[] = "Fila {$line}: sku y name son obligatorios.";

                    continue;
                }

                // Subfamilia: por id; si no viene, por el nombre de la columna informativa «subfamily»
                $subfamilyId = $this->nullableUuid($this->cell($row, $index, 'subfamily_id'));
                if ($subfamilyId === null) {
                    $label = $this->nullableString($this->cell($row, $index, 'subfamily'));
                    if ($label !== null) {
                        $subfamilyId = $subfamiliesByName[mb_strtolower($label)] ?? null;
                        if ($subfamilyId === null) {
                            $errors[] = "Fila {$line}: la subfamilia «{$label}» no existe.";

                            continue;
                        }
                    }
                } elseif (! DB::table('subfamilies')->where('id', $subfamilyId)->exists()) {
                    $errors[] = "Fila {$line}: subfamily_id no existe.";

                    continue;
                }

                $data = [
                    'sku' => $sku,
                    'barcode' => $this->nullableString($this->cell($row, $index, 'barcode')),
                    'name' => $name,
                    'description' => $this->nullableString($this->cell($row, $index, 'description')),
                    'image_url' => $this->nullableString($this->cell($row, $index, 'image_url')),
                    'subfamily_id' => $subfamilyId,
                    'price' => $this->decimalOrFail($this->cell($row, $index, 'price'), "Fila {$line}: price", $errors),
                    'tax_rate' => $this->decimalOrFail($this->cell($row, $index, 'tax_rate'), "Fila {$line}: tax_rate", $errors, true),
                    'is_active' => isset($index['is_active']) ? $this->parseBool($this->cell($row, $index, 'is_active')) : true,
                    'is_favorite' => isset($index['is_favorite']) ? $this->parseBool($this->cell($row, $index, 'is_favorite')) : false,
                ];

                if ($data['price'] === null || $data['tax_rate'] === null) {
                    continue;
                }

                // `cost` es opcional: si el archivo no trae la columna no se toca el costo existente
                if (isset($index['cost'])) {
                    $cost = $this->cell($row, $index, 'cost');
                    if ($cost === null || $cost === '') {
                        $data['cost'] = null;
                    } elseif (is_numeric($cost) && (float) $cost >= 0) {
                        $data['cost'] = round((float) $cost, 2);
                    } else {
                        $errors[] = "Fila {$line}: cost no es un número válido.";

                        continue;
                    }
                }

                // Coincidencia: product_id; si no hay, el SKU existente (modifica); si tampoco, producto nuevo
                $product = $productId !== null ? Product::withTrashed()->whereKey($productId)->first() : null;
                if ($product === null) {
                    $product = Product::withTrashed()->where('sku', $sku)->first();
                }

                $skuTaken = Product::withTrashed()->where('sku', $sku)->when($product, fn ($q) => $q->whereKeyNot($product->getKey()))->exists();
                if ($skuTaken) {
                    $errors[] = "Fila {$line}: el SKU «{$sku}» ya pertenece a otro producto.";

                    continue;
                }
                if ($data['barcode'] !== null) {
                    $barcodeTaken = Product::withTrashed()->where('barcode', $data['barcode'])->when($product, fn ($q) => $q->whereKeyNot($product->getKey()))->exists();
                    if ($barcodeTaken) {
                        $errors[] = "Fila {$line}: el código de barras «{$data['barcode']}» ya pertenece a otro producto.";

                        continue;
                    }
                }

                if ($product !== null) {
                    if ($product->trashed()) {
                        $product->restore();
                    }
                    $product->update($data);
                    $updated++;
                } else {
                    $newId = $productId ?? (string) Str::uuid();
                    Product::unguarded(function () use ($newId, $data): void {
                        Product::query()->create(array_merge(['id' => $newId], $data));
                    });
                    $created++;
                }
            }
        });

        $msg = "Importación: {$created} creados, {$updated} actualizados";
        $msg .= $errors === [] ? '.' : ', '.count($errors).' fila(s) omitida(s).';

        $back = back()->with('status', $msg);
        if ($errors !== []) {
            $back->with('import_errors', array_slice($errors, 0, 20));
        }

        return $back;
    }

    /** @param  list<mixed>  $row */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    private function addInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Instrucciones');
        $lines = [
            ['Cómo usar este archivo'],
            [''],
            ['1. Modificar productos existentes: edita las celdas de la hoja «Productos» y no cambies product_id (ni el SKU, que identifica al producto).'],
            ['2. Agregar productos nuevos: añade filas al final con sku, name y price; deja product_id vacío (se genera solo).'],
            ['3. Sube el archivo en Productos › Excel › Importar. Se crean los nuevos y se actualizan los existentes; no se borra ningún producto.'],
            [''],
            ['Columnas'],
            ['product_id: identificador interno. Vacío = producto nuevo.'],
            ['sku (obligatorio) y name (obligatorio): el SKU es único.'],
            ['barcode: código de barras, único; vacío si no tiene.'],
            ['subfamily_id o subfamily: la subfamilia por id o por su nombre (como aparece en la columna subfamily).'],
            ['price (obligatorio), cost (opcional, nunca viaja al POS), tax_rate (% de IVA).'],
            ['is_active, is_favorite: 1 = sí, 0 = no.'],
            ['La columna subfamily es informativa cuando subfamily_id está presente.'],
        ];
        foreach ($lines as $i => $l) {
            $sheet->setCellValue('A'.($i + 1), $l[0]);
        }
        $sheet->getColumnDimension('A')->setWidth(130);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A7')->getFont()->setBold(true);
    }

    private function authorizeExcel(Request $request): void
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);
        $p = AdminRbac::permissionsForScreen('products');
        abort_unless($user->can($p['edit']), 403);
    }

    /**
     * @param  list<mixed>  $headerRow
     * @return array<string, int>
     */
    private function headerIndexMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $i => $h) {
            if (! is_string($h) && ! is_numeric($h)) {
                continue;
            }
            $key = strtolower(trim((string) $h));
            if ($key === 'id') {
                $key = 'product_id';
            }
            if ($key === 'codbar') {
                $key = 'barcode';
            }
            $map[$key] = $i;
        }

        return $map;
    }

    /**
     * @param  list<mixed>  $row
     */
    private function cell(array $row, array $index, string $key): mixed
    {
        if (! isset($index[$key])) {
            return null;
        }

        return $row[$index[$key]] ?? null;
    }

    private function nullableString(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return trim((string) $v);
    }

    private function nullableUuid(mixed $v): ?string
    {
        $s = $this->nullableString($v);
        if ($s === null) {
            return null;
        }

        return Str::isUuid($s) ? $s : null;
    }

    /**
     * @param  list<string>  $errors
     */
    private function decimalOrFail(mixed $v, string $context, array &$errors, bool $defaultZero = false): ?string
    {
        if ($v === null || $v === '') {
            if ($defaultZero) {
                return '0';
            }
            $errors[] = "{$context}: valor numérico obligatorio.";

            return null;
        }
        if (is_numeric($v)) {
            return (string) $v;
        }
        $errors[] = "{$context}: número no válido.";

        return null;
    }

    private function parseBool(mixed $v): bool
    {
        if ($v === null || $v === '') {
            return false;
        }
        if (is_bool($v)) {
            return $v;
        }
        $s = strtolower(trim((string) $v));

        return in_array($s, ['1', 'true', 'yes', 'sí', 'si', 'x', 'on'], true);
    }
}
