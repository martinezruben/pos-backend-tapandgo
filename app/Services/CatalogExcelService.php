<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Product;
use App\Models\Subfamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Importación/exportación del catálogo en un solo libro de Excel:
 * Productos, Subfamilias, Familias (más Listas e Instrucciones de apoyo).
 * Las promociones no forman parte del archivo.
 */
class CatalogExcelService
{
    /** @var array<string, array{family: ?string, subfamily: ?string}> */
    private array $labelCache = [];

    public const SHEET_PRODUCTS = 'Productos';

    public const SHEET_SUBFAMILIES = 'Subfamilias';

    public const SHEET_FAMILIES = 'Familias';

    public const SHEET_LISTS = 'Listas';

    public const SHEET_HELP = 'Instrucciones';

    private const PRODUCT_HEADERS = ['product_id', 'sku', 'barcode', 'name', 'description', 'family', 'subfamily', 'price', 'cost', 'tax_rate', 'is_active', 'is_favorite', 'image_url', 'subfamily_id'];

    private const SUBFAMILY_HEADERS = ['subfamily_id', 'family', 'name'];

    private const FAMILY_HEADERS = ['family_id', 'name', 'description', 'image_url'];

    /* ------------------------------------------------------------------ *
     * Exportar
     * ------------------------------------------------------------------ */

    /**
     * @param  Builder<Product>|null  $productQuery  Productos a exportar (null = todos). Con filtro solo salen sus familias/subfamilias.
     */
    public function export(?Builder $productQuery = null): Spreadsheet
    {
        $filtered = $productQuery !== null;
        $products = ($productQuery ?? Product::query())->with('subfamily.family')->get();

        $subfamilies = $filtered
            ? $products->pluck('subfamily')->filter()->unique('id')->values()
            : Subfamily::query()->with('family')->get();
        $families = $filtered
            ? $subfamilies->pluck('family')->filter()->unique('id')->values()
            : Family::query()->get();

        $sort = fn (Collection $c, callable $key) => $c->sortBy($key, SORT_NATURAL | SORT_FLAG_CASE)->values();
        $families = $sort($families, fn (Family $f) => $f->name);
        $subfamilies = $sort($subfamilies, fn (Subfamily $s) => ($s->family?->name ?? '').'|'.$s->name);
        $products = $sort($products, fn (Product $p) => ($p->subfamily?->family?->name ?? '~').'|'.($p->subfamily?->name ?? '~').'|'.$p->name);

        $book = new Spreadsheet;

        $sheet = $book->getActiveSheet();
        $sheet->setTitle(self::SHEET_PRODUCTS);
        $this->writeTable($sheet, self::PRODUCT_HEADERS, $products->map(fn (Product $p) => [
            $p->id, $p->sku, $p->barcode, $p->name, $p->description,
            $p->subfamily?->family?->name, $p->subfamily?->name,
            $p->price, $p->cost, $p->tax_rate, $p->is_active ? 1 : 0, $p->is_favorite ? 1 : 0,
            $p->image_url, $p->subfamily_id,
        ])->all());

        $sub = $book->createSheet();
        $sub->setTitle(self::SHEET_SUBFAMILIES);
        $this->writeTable($sub, self::SUBFAMILY_HEADERS, $subfamilies->map(fn (Subfamily $s) => [$s->id, $s->family?->name, $s->name])->all());

        $fam = $book->createSheet();
        $fam->setTitle(self::SHEET_FAMILIES);
        $this->writeTable($fam, self::FAMILY_HEADERS, $families->map(fn (Family $f) => [$f->id, $f->name, $f->description, $f->image_url])->all());

        $lists = $book->createSheet();
        $lists->setTitle(self::SHEET_LISTS);
        $this->writeLists($lists, $families, $subfamilies);
        $this->addDropdowns($sheet, $families->count(), $subfamilies->pluck('name')->unique()->count(), max(count($products) + 500, 600));

        $help = $book->createSheet();
        $help->setTitle(self::SHEET_HELP);
        $this->writeHelp($help);

        $book->setActiveSheetIndex(0);

        return $book;
    }

    /** @param list<string> $headers @param list<list<mixed>> $rows */
    private function writeTable(Worksheet $sheet, array $headers, array $rows): void
    {
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'1', $h);
        }
        $r = 2;
        foreach ($rows as $row) {
            foreach ($row as $i => $v) {
                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($i + 1).$r,
                    $v === null ? '' : (is_bool($v) ? (int) $v : $v),
                    is_numeric($v) && ! in_array($headers[$i], ['sku', 'barcode', 'name'], true)
                        ? DataType::TYPE_NUMERIC
                        : DataType::TYPE_STRING
                );
            }
            $r++;
        }
        $last = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->freezePane('A2');
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true);
        for ($c = 1; $c <= count($headers); $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
    }

    private function writeLists(Worksheet $sheet, Collection $families, Collection $subfamilies): void
    {
        $sheet->setCellValue('A1', 'Familias válidas');
        foreach ($families->values() as $i => $f) {
            $sheet->setCellValue('A'.($i + 2), $f->name);
        }
        $sheet->setCellValue('C1', 'Subfamilias válidas');
        foreach ($subfamilies->pluck('name')->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values() as $i => $n) {
            $sheet->setCellValue('C'.($i + 2), $n);
        }
        $sheet->setCellValue('E1', 'Familia · Subfamilia');
        foreach ($subfamilies->values() as $i => $s) {
            $sheet->setCellValue('E'.($i + 2), ($s->family?->name ?? '').' · '.$s->name);
        }
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        foreach (['A', 'C', 'E'] as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
        $sheet->setSheetState(Worksheet::SHEETSTATE_VISIBLE);
    }

    /** Desplegables de familia y subfamilia en la hoja Productos (los valores nuevos siguen permitidos: advertencia, no bloqueo). */
    private function addDropdowns(Worksheet $sheet, int $familyCount, int $subfamilyNameCount, int $lastRow): void
    {
        $apply = function (string $col, string $range, int $count) use ($sheet, $lastRow): void {
            if ($count === 0) {
                return;
            }
            for ($r = 2; $r <= $lastRow; $r++) {
                $v = $sheet->getCell($col.$r)->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $v->setAllowBlank(true);
                $v->setShowDropDown(true);
                $v->setShowErrorMessage(true);
                $v->setErrorTitle('Valor nuevo');
                $v->setError('Ese valor no está en la lista. Solo se creará si activas «Crear las que no existan» al importar.');
                $v->setFormula1($range);
            }
        };
        $apply('F', '=Listas!$A$2:$A$'.($familyCount + 1), $familyCount);
        $apply('G', '=Listas!$C$2:$C$'.($subfamilyNameCount + 1), $subfamilyNameCount);
    }

    private function writeHelp(Worksheet $sheet): void
    {
        $lines = [
            ['Cómo usar este archivo'],
            [''],
            ['El libro tiene tres hojas editables: Productos, Subfamilias y Familias. Las hojas Listas e Instrucciones son de apoyo y no se importan.'],
            ['Las promociones no forman parte de este archivo.'],
            [''],
            ['Modificar'],
            ['Cambia las celdas de las filas existentes. Para renombrar una familia o subfamilia, conserva su id (family_id / subfamily_id); sin id se identifican por nombre.'],
            ['Un producto se identifica por product_id o, si falta, por su SKU. Cambiar la familia/subfamilia de un producto lo mueve: puede cambiar las promociones que le aplican.'],
            [''],
            ['Agregar'],
            ['Añade filas al final. En Productos: sku, name y price son obligatorios; deja product_id vacío.'],
            ['Familia y subfamilia se escriben por nombre (hay desplegables con las existentes). Si el nombre de subfamilia se repite en varias familias, indica también la familia.'],
            ['Las familias y subfamilias que no existan se rechazan, salvo que marques «Crear las que no existan» al importar (apagado por defecto).'],
            [''],
            ['Importar'],
            ['Se procesa en este orden: Familias → Subfamilias → Productos. Antes de aplicar verás un resumen con lo que se crea, se actualiza y los errores.'],
            ['Las filas con errores se omiten; el resto se aplica. Nunca se borra nada: quitar una fila del Excel no elimina el registro.'],
            ['Cada hoja exige el permiso de edición de su pantalla (Productos, Subfamilias, Familias); sin él, esa hoja se ignora.'],
            [''],
            ['Columnas de Productos'],
            ['sku, name, price obligatorios · barcode único · tax_rate en % · cost opcional (nunca viaja al POS) · is_active / is_favorite: 1 = sí, 0 = no · image_url: URL (las imágenes se suben desde el formulario).'],
        ];
        foreach ($lines as $i => $l) {
            $sheet->setCellValue('A'.($i + 1), $l[0]);
        }
        $sheet->getColumnDimension('A')->setWidth(140);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        foreach ([6, 10, 15, 20] as $r) {
            $sheet->getStyle('A'.$r)->getFont()->setBold(true);
        }
    }

    /* ------------------------------------------------------------------ *
     * Importar
     * ------------------------------------------------------------------ */

    /**
     * @param  array{create_missing?: bool, dry_run?: bool, can_families?: bool, can_subfamilies?: bool}  $options
     * @return array{sheets: array<string, array<string, int>>, rows: list<array<string, mixed>>, errors: list<string>, warnings: list<string>, moved: int, ignored: list<string>}
     */
    public function import(string $path, array $options = []): array
    {
        $createMissing = (bool) ($options['create_missing'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);
        $canFamilies = (bool) ($options['can_families'] ?? true);
        $canSubfamilies = (bool) ($options['can_subfamilies'] ?? true);

        $book = IOFactory::load($path);
        $tables = $this->readTables($book);

        $result = [
            'sheets' => [
                self::SHEET_FAMILIES => ['read' => 0, 'matched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0],
                self::SHEET_SUBFAMILIES => ['read' => 0, 'matched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0],
                self::SHEET_PRODUCTS => ['read' => 0, 'matched' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'errors' => 0],
            ],
            'rows' => [],
            'errors' => [],
            'warnings' => [],
            'moved' => 0,
            'ignored' => [],
        ];

        if (! isset($tables[self::SHEET_PRODUCTS]) && ! isset($tables[self::SHEET_FAMILIES]) && ! isset($tables[self::SHEET_SUBFAMILIES])) {
            $result['errors'][] = 'El archivo no tiene hojas reconocibles (Productos, Subfamilias o Familias).';

            return $result;
        }

        DB::beginTransaction();
        try {
            $ctx = new class
            {
                /** @var array<string, string> nombre en minúsculas => id de familia */
                public array $families = [];

                /** @var array<string, string> «idFamilia|nombre» => id de subfamilia */
                public array $subfamilies = [];
            };
            foreach (Family::query()->get(['id', 'name']) as $f) {
                $ctx->families[$this->key($f->name)] = $f->id;
            }
            foreach (Subfamily::query()->get(['id', 'family_id', 'name']) as $s) {
                $ctx->subfamilies[$s->family_id.'|'.$this->key($s->name)] = $s->id;
            }

            if (isset($tables[self::SHEET_FAMILIES])) {
                if ($canFamilies) {
                    $this->importFamilies($tables[self::SHEET_FAMILIES], $ctx, $result);
                } else {
                    $result['ignored'][] = 'Hoja «Familias» ignorada: no tienes permiso para editar familias.';
                }
            }
            if (isset($tables[self::SHEET_SUBFAMILIES])) {
                if ($canSubfamilies) {
                    $this->importSubfamilies($tables[self::SHEET_SUBFAMILIES], $ctx, $result, $createMissing && $canFamilies);
                } else {
                    $result['ignored'][] = 'Hoja «Subfamilias» ignorada: no tienes permiso para editar subfamilias.';
                }
            }
            if (isset($tables[self::SHEET_PRODUCTS])) {
                $this->importProducts($tables[self::SHEET_PRODUCTS], $ctx, $result, $createMissing, $canFamilies, $canSubfamilies);
            }

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * @return array<string, array{map: array<string, int>, rows: list<array{line: int, row: list<mixed>}>}>
     */
    private function readTables(Spreadsheet $book): array
    {
        $tables = [];
        foreach ($book->getWorksheetIterator() as $i => $sheet) {
            $title = $this->canonicalSheet($sheet->getTitle());
            // Compatibilidad: un archivo de una sola hoja sin nombre conocido se trata como Productos
            if ($title === null) {
                if ($i !== 0 || $book->getSheetCount() > 1) {
                    continue;
                }
                $title = self::SHEET_PRODUCTS;
            }
            $raw = $sheet->toArray(null, true, false, false);
            if ($raw === []) {
                continue;
            }
            $header = array_shift($raw);
            $map = [];
            foreach ($header as $col => $h) {
                if (is_string($h) || is_numeric($h)) {
                    $key = strtolower(trim((string) $h));
                    $key = ['id' => 'product_id', 'codbar' => 'barcode'][$key] ?? $key;
                    $map[$key] = $col;
                }
            }
            $rows = [];
            foreach ($raw as $n => $row) {
                $rows[] = ['line' => $n + 2, 'row' => $row];
            }
            $tables[$title] = ['map' => $map, 'rows' => $rows];
        }

        return $tables;
    }

    private function canonicalSheet(string $title): ?string
    {
        return match (Str::lower(Str::ascii(trim($title)))) {
            'productos', 'products' => self::SHEET_PRODUCTS,
            'subfamilias', 'subfamilies' => self::SHEET_SUBFAMILIES,
            'familias', 'families' => self::SHEET_FAMILIES,
            default => null,
        };
    }

    /** @param array{map: array<string, int>, rows: list<array{line: int, row: list<mixed>}>} $t */
    private function importFamilies(array $t, object $ctx, array &$result): void
    {
        $S = self::SHEET_FAMILIES;
        if (! isset($t['map']['name'])) {
            $result['errors'][] = "{$S}: falta la columna obligatoria «name».";

            return;
        }
        foreach ($t['rows'] as ['line' => $line, 'row' => $row]) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $result['sheets'][$S]['read']++;
            $id = $this->str($row, $t['map'], 'family_id');
            $name = $this->str($row, $t['map'], 'name');
            if ($id !== null && ! Str::isUuid($id)) {
                $this->fail($result, $S, "{$S} fila {$line}: family_id no es un UUID válido.");

                continue;
            }
            if ($name === null || mb_strlen($name) > 120) {
                $this->fail($result, $S, "{$S} fila {$line}: el nombre es obligatorio (máx. 120).");

                continue;
            }

            $family = $id !== null ? Family::withTrashed()->find($id) : null;
            if ($family === null && $id === null) {
                $existingId = $ctx->families[$this->key($name)] ?? null;
                $family = $existingId ? Family::withTrashed()->find($existingId) : null;
            }
            $otherId = $ctx->families[$this->key($name)] ?? null;
            if ($otherId !== null && $family !== null && $otherId !== $family->id) {
                $this->fail($result, $S, "{$S} fila {$line}: ya existe otra familia llamada «{$name}».");

                continue;
            }

            $data = ['name' => $name];
            foreach (['description', 'image_url'] as $col) {
                if (isset($t['map'][$col])) {
                    $data[$col] = $this->str($row, $t['map'], $col);
                }
            }

            if ($family !== null) {
                $result['sheets'][$S]['matched']++;
                $changes = $this->diff($family, $data);
                $restored = $family->trashed();
                $restored && $family->restore();
                unset($ctx->families[$this->key($family->name)]);
                if ($changes === [] && ! $restored) {
                    $result['sheets'][$S]['unchanged']++;
                    $this->row($result, $S, $line, 'unchanged', ['name' => $name]);
                } else {
                    $family->update($data);
                    $result['sheets'][$S]['updated']++;
                    $this->row($result, $S, $line, 'updated', ['name' => $name, 'changes' => $changes, 'note' => $restored ? 'Restaurada' : null]);
                }
            } else {
                $family = Family::unguarded(fn () => Family::query()->create(array_merge(['id' => $id ?? (string) Str::uuid()], $data)));
                $result['sheets'][$S]['created']++;
                $this->row($result, $S, $line, 'created', ['name' => $name]);
            }
            $ctx->families[$this->key($family->name)] = $family->id;
        }
    }

    /** @param array{map: array<string, int>, rows: list<array{line: int, row: list<mixed>}>} $t */
    private function importSubfamilies(array $t, object $ctx, array &$result, bool $createFamilies): void
    {
        $S = self::SHEET_SUBFAMILIES;
        foreach (['family', 'name'] as $required) {
            if (! isset($t['map'][$required])) {
                $result['errors'][] = "{$S}: falta la columna obligatoria «{$required}».";

                return;
            }
        }
        foreach ($t['rows'] as ['line' => $line, 'row' => $row]) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $result['sheets'][$S]['read']++;
            $id = $this->str($row, $t['map'], 'subfamily_id');
            $familyName = $this->str($row, $t['map'], 'family');
            $name = $this->str($row, $t['map'], 'name');
            if ($id !== null && ! Str::isUuid($id)) {
                $this->fail($result, $S, "{$S} fila {$line}: subfamily_id no es un UUID válido.");

                continue;
            }
            if ($familyName === null || $name === null || mb_strlen($name) > 120) {
                $this->fail($result, $S, "{$S} fila {$line}: familia y nombre son obligatorios.");

                continue;
            }

            $familyId = $this->resolveFamily($familyName, $ctx, $result, $createFamilies, "{$S} fila {$line}", $S);
            if ($familyId === null) {
                continue;
            }

            $sub = $id !== null ? Subfamily::withTrashed()->find($id) : null;
            if ($sub === null && $id === null) {
                $existingId = $ctx->subfamilies[$familyId.'|'.$this->key($name)] ?? null;
                $sub = $existingId ? Subfamily::withTrashed()->find($existingId) : null;
            }
            $clashId = $ctx->subfamilies[$familyId.'|'.$this->key($name)] ?? null;
            if ($clashId !== null && $sub !== null && $clashId !== $sub->id) {
                $this->fail($result, $S, "{$S} fila {$line}: «{$familyName} · {$name}» ya existe.");

                continue;
            }

            if ($sub !== null) {
                $result['sheets'][$S]['matched']++;
                $changes = $this->diff($sub, ['family_id' => $familyId, 'name' => $name]);
                $restored = $sub->trashed();
                $restored && $sub->restore();
                unset($ctx->subfamilies[$sub->family_id.'|'.$this->key($sub->name)]);
                if ($changes === [] && ! $restored) {
                    $result['sheets'][$S]['unchanged']++;
                    $this->row($result, $S, $line, 'unchanged', ['name' => $name, 'family' => $familyName]);
                } else {
                    $sub->update(['family_id' => $familyId, 'name' => $name]);
                    $result['sheets'][$S]['updated']++;
                    $this->row($result, $S, $line, 'updated', ['name' => $name, 'family' => $familyName, 'changes' => $changes, 'note' => $restored ? 'Restaurada' : null]);
                }
            } else {
                $sub = Subfamily::unguarded(fn () => Subfamily::query()->create(['id' => $id ?? (string) Str::uuid(), 'family_id' => $familyId, 'name' => $name]));
                $result['sheets'][$S]['created']++;
                $this->row($result, $S, $line, 'created', ['name' => $name, 'family' => $familyName]);
            }
            $ctx->subfamilies[$familyId.'|'.$this->key($name)] = $sub->id;
        }
    }

    /** @param array{map: array<string, int>, rows: list<array{line: int, row: list<mixed>}>} $t */
    private function importProducts(array $t, object $ctx, array &$result, bool $createMissing, bool $canFamilies, bool $canSubfamilies): void
    {
        $S = self::SHEET_PRODUCTS;
        foreach (['sku', 'name'] as $required) {
            if (! isset($t['map'][$required])) {
                $result['errors'][] = "{$S}: falta la columna obligatoria «{$required}». Usa el archivo exportado como base.";

                return;
            }
        }
        $map = $t['map'];

        foreach ($t['rows'] as ['line' => $line, 'row' => $row]) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $result['sheets'][$S]['read']++;
            $where = "{$S} fila {$line}";

            $productId = $this->str($row, $map, 'product_id');
            if ($productId !== null && ! Str::isUuid($productId)) {
                $this->fail($result, $S, "{$where}: product_id no es un UUID válido (déjalo vacío para un producto nuevo).");

                continue;
            }
            $sku = $this->str($row, $map, 'sku');
            $name = $this->str($row, $map, 'name');
            if ($sku === null || $name === null) {
                $this->fail($result, $S, "{$where}: sku y name son obligatorios.");

                continue;
            }

            $product = $productId !== null ? Product::withTrashed()->whereKey($productId)->first() : null;
            $product ??= Product::withTrashed()->where('sku', $sku)->first();
            $creating = $product === null;

            $data = ['sku' => $sku, 'name' => $name];
            foreach (['barcode', 'description', 'image_url'] as $col) {
                if (isset($map[$col])) {
                    $data[$col] = $this->str($row, $map, $col);
                }
            }

            // Clasificación: subfamily_id, o familia + subfamilia por nombre
            $hasClassification = isset($map['subfamily_id']) || isset($map['subfamily']) || isset($map['family']);
            if ($hasClassification) {
                $resolved = $this->resolveProductSubfamily($row, $map, $ctx, $result, $createMissing, $canFamilies, $canSubfamilies, $where);
                if ($resolved === false) {
                    continue;
                }
                $data['subfamily_id'] = $resolved;
            }

            // Precio / IVA / costo
            if (isset($map['price']) || $creating) {
                $price = $this->number($row, $map, 'price');
                if ($price === null) {
                    $this->fail($result, $S, "{$where}: price es obligatorio y debe ser un número.");

                    continue;
                }
                $data['price'] = $price;
            }
            if (isset($map['tax_rate'])) {
                $tax = $this->number($row, $map, 'tax_rate', true);
                if ($tax === null) {
                    $this->fail($result, $S, "{$where}: tax_rate no es un número válido.");

                    continue;
                }
                $data['tax_rate'] = $tax;
            } elseif ($creating) {
                $data['tax_rate'] = '0';
            }
            if (isset($map['cost'])) {
                $raw = $row[$map['cost']] ?? null;
                if ($raw === null || trim((string) $raw) === '') {
                    $data['cost'] = null;
                } elseif (is_numeric($raw) && (float) $raw >= 0) {
                    $data['cost'] = round((float) $raw, 2);
                } else {
                    $this->fail($result, $S, "{$where}: cost no es un número válido.");

                    continue;
                }
            }
            foreach (['is_active' => true, 'is_favorite' => false] as $col => $default) {
                if (isset($map[$col])) {
                    $data[$col] = $this->parseBool($row[$map[$col]] ?? null);
                } elseif ($creating) {
                    $data[$col] = $default;
                }
            }

            // Unicidad de SKU y código de barras frente a otros productos
            $others = fn () => Product::withTrashed()->when($product, fn ($q) => $q->whereKeyNot($product->getKey()));
            if ($others()->where('sku', $sku)->exists()) {
                $this->fail($result, $S, "{$where}: el SKU «{$sku}» ya pertenece a otro producto.");

                continue;
            }
            if (! empty($data['barcode']) && $others()->where('barcode', $data['barcode'])->exists()) {
                $this->fail($result, $S, "{$where}: el código de barras «{$data['barcode']}» ya pertenece a otro producto.");

                continue;
            }

            $label = $this->classification($data['subfamily_id'] ?? $product?->subfamily_id);
            if ($product !== null) {
                $result['sheets'][$S]['matched']++;
                $changes = $this->diff($product, $data);
                $restored = $product->trashed();
                $restored && $product->restore();
                $moved = array_key_exists('subfamily_id', $changes);
                if ($moved) {
                    $result['moved']++;
                    $result['warnings'][] = "{$where}: «{$product->name}» cambia de subfamilia (puede cambiar las promociones que le aplican).";
                    $changes['subfamily_id'] = [$this->classification($changes['subfamily_id'][0]), $this->classification($changes['subfamily_id'][1])];
                }
                if ($changes === [] && ! $restored) {
                    $result['sheets'][$S]['unchanged']++;
                    $this->row($result, $S, $line, 'unchanged', ['sku' => $sku, 'name' => $name] + $label);
                } else {
                    $product->update($data);
                    $result['sheets'][$S]['updated']++;
                    $this->row($result, $S, $line, 'updated', ['sku' => $sku, 'name' => $name, 'changes' => $changes, 'moved' => $moved, 'note' => $restored ? 'Restaurado' : null] + $label);
                }
            } else {
                Product::unguarded(fn () => Product::query()->create(array_merge(['id' => $productId ?? (string) Str::uuid()], $data)));
                $result['sheets'][$S]['created']++;
                $this->row($result, $S, $line, 'created', ['sku' => $sku, 'name' => $name, 'changes' => array_intersect_key(
                    ['price' => [null, $data['price'] ?? null], 'cost' => [null, $data['cost'] ?? null]],
                    ['price' => 1, 'cost' => 1]
                )] + $label);
            }
        }
    }

    /** @return string|false|null id de subfamilia, null = sin clasificar, false = error ya registrado */
    private function resolveProductSubfamily(array $row, array $map, object $ctx, array &$result, bool $createMissing, bool $canFamilies, bool $canSubfamilies, string $where): string|false|null
    {
        $S = self::SHEET_PRODUCTS;
        $subId = $this->str($row, $map, 'subfamily_id');
        $familyName = $this->str($row, $map, 'family');
        $subName = $this->str($row, $map, 'subfamily');

        // Etiqueta heredada «Familia · Subfamilia»
        if ($subName !== null && $familyName === null && str_contains($subName, ' · ')) {
            [$familyName, $subName] = array_map('trim', explode(' · ', $subName, 2));
        }

        if ($subName === null && $familyName === null) {
            if ($subId === null) {
                return null;
            }
            if (! Str::isUuid($subId) || ! Subfamily::query()->whereKey($subId)->exists()) {
                $this->fail($result, $S, "{$where}: subfamily_id no existe.");

                return false;
            }

            return $subId;
        }

        if ($subName === null) {
            $this->fail($result, $S, "{$where}: indica también la subfamilia de «{$familyName}».");

            return false;
        }

        if ($familyName !== null) {
            $familyId = $this->resolveFamily($familyName, $ctx, $result, $createMissing && $canFamilies, $where, $S);
            if ($familyId === null) {
                return false;
            }
            $key = $familyId.'|'.$this->key($subName);
            if (isset($ctx->subfamilies[$key])) {
                return $ctx->subfamilies[$key];
            }
            if (! $createMissing) {
                $this->fail($result, $S, "{$where}: la subfamilia «{$familyName} · {$subName}» no existe (activa «Crear las que no existan» o agrégala en la hoja Subfamilias).");

                return false;
            }
            if (! $canSubfamilies) {
                $this->fail($result, $S, "{$where}: la subfamilia «{$subName}» no existe y no tienes permiso para crear subfamilias.");

                return false;
            }
            $sub = Subfamily::unguarded(fn () => Subfamily::query()->create(['id' => (string) Str::uuid(), 'family_id' => $familyId, 'name' => $subName]));
            $ctx->subfamilies[$key] = $sub->id;
            $result['sheets'][self::SHEET_SUBFAMILIES]['created']++;

            return $sub->id;
        }

        // Solo nombre de subfamilia: debe ser único entre todas las familias
        $matches = [];
        foreach ($ctx->subfamilies as $k => $id) {
            if (str_ends_with($k, '|'.$this->key($subName))) {
                $matches[] = $id;
            }
        }
        if (count($matches) === 1) {
            return $matches[0];
        }
        $this->fail($result, $S, count($matches) === 0
            ? "{$where}: la subfamilia «{$subName}» no existe (indica también la familia para poder crearla)."
            : "{$where}: «{$subName}» existe en varias familias; indica la familia.");

        return false;
    }

    private function resolveFamily(string $name, object $ctx, array &$result, bool $create, string $where, string $sheet): ?string
    {
        $key = $this->key($name);
        if (isset($ctx->families[$key])) {
            return $ctx->families[$key];
        }
        if (! $create) {
            $this->fail($result, $sheet, "{$where}: la familia «{$name}» no existe (activa «Crear las que no existan» o agrégala en la hoja Familias).");

            return null;
        }
        if (mb_strlen($name) > 120) {
            $this->fail($result, $sheet, "{$where}: el nombre de familia supera 120 caracteres.");

            return null;
        }
        $family = Family::unguarded(fn () => Family::query()->create(['id' => (string) Str::uuid(), 'name' => $name]));
        $ctx->families[$key] = $family->id;
        $result['sheets'][self::SHEET_FAMILIES]['created']++;

        return $family->id;
    }

    /* ------------------------------------------------------------------ *
     * Utilidades
     * ------------------------------------------------------------------ */

    /** @param array<string, mixed> $extra */
    private function row(array &$result, string $sheet, int $line, string $action, array $extra = []): void
    {
        $result['rows'][] = ['sheet' => $sheet, 'line' => $line, 'action' => $action] + $extra;
    }

    /** @return array{family: ?string, subfamily: ?string} */
    private function classification(?string $subfamilyId): array
    {
        if ($subfamilyId === null) {
            return ['family' => null, 'subfamily' => null];
        }

        return $this->labelCache[$subfamilyId] ??= (function () use ($subfamilyId): array {
            $s = Subfamily::withTrashed()->with('family')->find($subfamilyId);

            return ['family' => $s?->family?->name, 'subfamily' => $s?->name];
        })();
    }

    /**
     * Cambios reales entre el registro y los datos a aplicar (importes comparados a 2 decimales).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function diff(Model $model, array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $old = $model->getAttribute($k);
            if (in_array($k, ['price', 'cost', 'tax_rate'], true)) {
                $a = $old === null ? null : round((float) $old, 2);
                $b = $v === null ? null : round((float) $v, 2);
            } elseif (in_array($k, ['is_active', 'is_favorite'], true)) {
                $a = (bool) $old;
                $b = (bool) $v;
            } else {
                $a = $old === null ? null : (string) $old;
                $b = $v === null ? null : (string) $v;
            }
            if ($a !== $b) {
                $out[$k] = [$a, $b];
            }
        }

        return $out;
    }

    private function fail(array &$result, string $sheet, string $message): void
    {
        $result['errors'][] = $message;
        $result['sheets'][$sheet]['errors']++;
    }

    private function key(string $v): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v) ?? $v));
    }

    /** @param list<mixed> $row */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    /** @param list<mixed> $row @param array<string, int> $map */
    private function str(array $row, array $map, string $key): ?string
    {
        if (! isset($map[$key])) {
            return null;
        }
        $v = $row[$map[$key]] ?? null;
        if ($v === null) {
            return null;
        }
        $s = is_float($v) && floor($v) == $v ? (string) (int) $v : trim((string) $v);

        return $s === '' ? null : $s;
    }

    /** @param list<mixed> $row @param array<string, int> $map */
    private function number(array $row, array $map, string $key, bool $zeroWhenEmpty = false): ?string
    {
        $v = isset($map[$key]) ? ($row[$map[$key]] ?? null) : null;
        if ($v === null || trim((string) $v) === '') {
            return $zeroWhenEmpty ? '0' : null;
        }

        return is_numeric($v) && (float) $v >= 0 ? (string) $v : null;
    }

    private function parseBool(mixed $v): bool
    {
        if ($v === null || $v === '') {
            return false;
        }
        if (is_bool($v)) {
            return $v;
        }

        return in_array(strtolower(trim((string) $v)), ['1', 'true', 'yes', 'sí', 'si', 'x', 'on'], true);
    }
}
