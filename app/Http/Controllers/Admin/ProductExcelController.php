<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogImport;
use App\Models\Product;
use App\Services\CatalogExcelService;
use App\Services\CatalogImportReport;
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

        return $this->finish($result, $request->file('file')->getClientOriginalName(), $request->boolean('create_missing'));
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
            'summary' => CatalogImportReport::summarize($result),
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
            'file_name' => ['nullable', 'string', 'max:255'],
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

        return $this->finish($result, $data['file_name'] ?? 'archivo.'.$data['ext'], $request->boolean('create_missing'));
    }

    /** Resultado de una carga (resumen y descarga del informe). */
    public function show(CatalogImport $import)
    {
        $this->authorizeExcel();

        return view('admin.products.import-result', ['import' => $import]);
    }

    /** Historial de cargas. */
    public function history()
    {
        $this->authorizeExcel();

        return view('admin.products.import-history', ['imports' => CatalogImport::query()->latest()->limit(50)->get()]);
    }

    public function report(CatalogImport $import)
    {
        $this->authorizeExcel();
        abort_unless($import->report_path && Storage::disk('local')->exists($import->report_path), 404);

        return Storage::disk('local')->download($import->report_path, 'informe-carga-'.$import->created_at->format('Y-m-d_His').'.xlsx');
    }

    /** @param array<string, mixed> $result */
    private function finish(array $result, string $fileName, bool $createMissing): RedirectResponse
    {
        $user = auth('admin')->user();
        $summary = CatalogImportReport::summarize($result);
        $import = CatalogImport::create([
            'admin_user_id' => $user->getKey(),
            'admin_name' => $user->name,
            'file_name' => $fileName,
            'create_missing' => $createMissing,
            'summary' => $summary,
        ]);

        $path = 'catalog-reports/'.$import->id.'.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'rep');
        (new Xlsx(CatalogImportReport::workbook($result, $summary, [
            'file_name' => $fileName, 'admin_name' => $user->name, 'create_missing' => $createMissing, 'at' => $import->created_at,
        ])))->save($tmp);
        Storage::disk('local')->put($path, file_get_contents($tmp));
        @unlink($tmp);
        $import->update(['report_path' => $path]);

        $t = $summary['totals'];
        $back = redirect()->route('admin.products.excel.show', $import)->with('status', "Carga aplicada: {$t['created']} creados, {$t['updated']} modificados, {$t['unchanged']} sin cambios, {$t['errors']} con error.");
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
