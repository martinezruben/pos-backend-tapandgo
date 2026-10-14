<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\AdminRbac;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exporta el reporte «Productos sin movimiento» a Excel y CSV con los mismos filtros de la pantalla.
 */
class ProductsNoMovementExportController extends Controller
{
    private const HEADERS = ['Producto', 'SKU', 'Familia', 'Subfamilia', 'Precio', 'Última venta', 'Días sin vender'];

    public function __construct(private readonly ReportService $reportService) {}

    public function excel(Request $request): StreamedResponse|JsonResponse
    {
        [$rows, $from, $to, $error] = $this->rows($request);
        if ($error) {
            return $error;
        }

        return response()->streamDownload(function () use ($rows, $from, $to): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Sin movimiento');
            $sheet->setCellValue('A1', 'PRODUCTOS SIN MOVIMIENTO');
            $sheet->setCellValue('A2', 'Sin ventas entre '.$from->format('Y-m-d').' y '.$to->format('Y-m-d'));

            foreach (self::HEADERS as $i => $header) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).'4', $header);
            }
            foreach ($rows as $n => $values) {
                foreach ($values as $i => $value) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 1).($n + 5), $value);
                }
            }

            (new Xlsx($spreadsheet))->save('php://output');
        }, 'productos-sin-movimiento-'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function csv(Request $request): StreamedResponse|JsonResponse
    {
        [$rows, $from, $to, $error] = $this->rows($request);
        if ($error) {
            return $error;
        }

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, self::HEADERS);
            foreach ($rows as $values) {
                fputcsv($output, $values);
            }
            fclose($output);
        }, 'productos-sin-movimiento-'.$from->format('Y-m-d').'_'.$to->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array{0: list<list<mixed>>, 1: ?Carbon, 2: ?Carbon, 3: ?JsonResponse}
     */
    private function rows(Request $request): array
    {
        $user = auth('admin')->user();
        abort_unless($user && $user->can(AdminRbac::permissionsForScreen('products-no-movement')['view']), 403);

        $validator = Validator::make($request->all(), [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date'],
            'location_id' => ['nullable', 'uuid', 'exists:locations,id'],
        ]);
        if ($validator->fails()) {
            return [[], null, null, response()->json(['message' => 'Datos no válidos.', 'errors' => $validator->errors()], 422)];
        }

        $data = $validator->validated();
        $from = Carbon::parse($data['date_from'])->startOfDay();
        $to = Carbon::parse($data['date_to'])->endOfDay();
        if ($from->gt($to)) {
            return [[], null, null, response()->json(['message' => 'La fecha inicial no puede ser posterior a la final.'], 422)];
        }

        $rows = $this->reportService->getProductsWithoutMovement($from, $to, $data['location_id'] ?? null)
            ->map(function ($product) {
                $last = $product->last_sold_at ? Carbon::parse($product->last_sold_at) : null;

                return [
                    $product->name,
                    $product->sku,
                    $product->subfamily?->family?->name,
                    $product->subfamily?->name,
                    $product->price,
                    $last?->format('Y-m-d H:i'),
                    $last ? (int) $last->diffInDays(now()) : 'Nunca vendido',
                ];
            })
            ->all();

        return [$rows, $from, $to, null];
    }
}
