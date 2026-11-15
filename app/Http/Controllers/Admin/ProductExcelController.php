<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CatalogExcelService;
use App\Support\AdminGridQuery;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Catálogo en Excel (productos, subfamilias y familias en un solo libro; sin promociones).
 */
class ProductExcelController extends Controller
{
    private const DIR = 'catalog-imports';

    public function __construct(private readonly CatalogExcelService $catalog) {}

    /** scope=all (por defecto) exporta todo; scope=filtered respeta los filtros/búsqueda del listado. */
    public function export(Request $request): StreamedResponse
    {
        $this->authorizeExcel();

        $query = null;
        $suffix = '';
        if ($request->query('scope') === 'filtered') {
            $query = Product::query();
            AdminGridQuery::apply($query, $request, 'products', config('admin_screens.products'));
            $suffix = '-filtrado';
        }

        $filename = 'catalogo'.$suffix.'-'.now()->format('Y-m-d_His').'.xlsx';

        return response()->streamDownload(function () use ($query): void {
            (new Xlsx($this->catalog->export($query)))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Aplica el archivo directamente (sin vista previa). */
    public function import(Request $request): RedirectResponse
    {
        $this->authorizeExcel();
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        $result = $this->catalog->import($request->file('file')->getRealPath(), $this->options($request, false));

        return $this->finish($result);
    }

    /** Valida el archivo sin guardar nada y muestra el resumen para confirmar. */
    public function preview(Request $request)
    {
        $this->authorizeExcel();
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240']]);

        $this->pruneOldUploads();
        $token = (string) Str::uuid();
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        Storage::disk('local')->putFileAs(self::DIR, $file, $this->storedName($token, $ext));

        try {
            $result = $this->catalog->import(Storage::disk('local')->path(self::DIR.'/'.$this->storedName($token, $ext)), $this->options($request, true));
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(self::DIR.'/'.$this->storedName($token, $ext));

            return back()->withErrors(['file' => 'No se pudo leer el archivo: '.$e->getMessage()]);
        }

        return view('admin.products.import-preview', [
            'result' => $result,
            'token' => $token,
            'ext' => $ext,
            'createMissing' => $request->boolean('create_missing'),
            'fileName' => $file->getClientOriginalName(),
        ]);
    }

    /** Aplica el archivo ya revisado en la vista previa. */
    public function apply(Request $request): RedirectResponse
    {
        $this->authorizeExcel();
        $data = $request->validate([
            'token' => ['required', 'uuid'],
            'ext' => ['required', 'in:xlsx,xls,csv'],
        ]);

        $relative = self::DIR.'/'.$this->storedName($data['token'], $data['ext']);
        if (! Storage::disk('local')->exists($relative)) {
            return redirect()->route('admin.screens.index', 'products')->withErrors(['file' => 'La vista previa expiró. Sube el archivo de nuevo.']);
        }

        try {
            $result = $this->catalog->import(Storage::disk('local')->path($relative), $this->options($request, false));
        } finally {
            Storage::disk('local')->delete($relative);
        }

        return $this->finish($result);
    }

    /** @param array<string, mixed> $result */
    private function finish(array $result): RedirectResponse
    {
        $parts = [];
        foreach ($result['sheets'] as $sheet => $s) {
            if ($s['created'] || $s['updated']) {
                $parts[] = "{$sheet}: {$s['created']} nuevos, {$s['updated']} actualizados";
            }
        }
        $errors = count($result['errors']);
        $msg = 'Importación: '.($parts === [] ? 'sin cambios' : implode(' · ', $parts)).($errors ? " · {$errors} fila(s) omitida(s)" : '').'.';

        $back = redirect()->route('admin.screens.index', 'products')->with('status', $msg);
        $notes = array_merge($result['ignored'], array_slice($result['errors'], 0, 20));
        if ($notes !== []) {
            $back->with('import_errors', $notes);
        }

        return $back;
    }

    /** @return array{create_missing: bool, dry_run: bool, can_families: bool, can_subfamilies: bool} */
    private function options(Request $request, bool $dryRun): array
    {
        $user = auth('admin')->user();

        return [
            'create_missing' => $request->boolean('create_missing'),
            'dry_run' => $dryRun,
            'can_families' => $user->can(AdminRbac::permissionsForScreen('families')['edit']),
            'can_subfamilies' => $user->can(AdminRbac::permissionsForScreen('subfamilies')['edit']),
        ];
    }

    /** Borra archivos de vistas previas que nadie confirmó en más de un día. */
    private function pruneOldUploads(): void
    {
        $disk = Storage::disk('local');
        foreach ($disk->files(self::DIR) as $f) {
            if ($disk->lastModified($f) < now()->subDay()->getTimestamp()) {
                $disk->delete($f);
            }
        }
    }

    private function storedName(string $token, string $ext): string
    {
        return auth('admin')->id().'_'.$token.'.'.$ext;
    }

    private function authorizeExcel(): void
    {
        $user = auth('admin')->user();
        abort_unless($user, 403);
        abort_unless($user->can(AdminRbac::permissionsForScreen('products')['edit']), 403);
    }
}
