<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\AdminRbac;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function productsBestSellers(Request $request): View
    {
        $this->authorizeView('products-best-sellers');

        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : now();
        $locationId = $request->input('location_id');

        $products = $this->reportService->getProductsBestSellers($dateFrom, $dateTo, $locationId);

        return view('admin.reports.products-best-sellers', [
            'products' => $products,
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'locationId' => $locationId,
        ]);
    }

    public function paymentMethodsReport(Request $request): View
    {
        $this->authorizeView('payment-methods-report');

        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : now();

        $methods = $this->reportService->getPaymentMethodsReport($dateFrom, $dateTo);

        return view('admin.reports.payment-methods', [
            'methods' => $methods,
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
        ]);
    }

    public function usersPerformanceReport(Request $request): View
    {
        $this->authorizeView('users-performance-report');

        $dateFrom = $request->input('date_from') ? Carbon::parse($request->input('date_from')) : now()->subMonth();
        $dateTo = $request->input('date_to') ? Carbon::parse($request->input('date_to')) : now();
        $locationId = $request->input('location_id');

        $users = $this->reportService->getUsersPerformanceReport($dateFrom, $dateTo, $locationId);

        return view('admin.reports.users-performance', [
            'users' => $users,
            'dateFrom' => $dateFrom->format('Y-m-d'),
            'dateTo' => $dateTo->format('Y-m-d'),
            'locationId' => $locationId,
        ]);
    }

    private function authorizeView(string $screen): void
    {
        $user = auth('admin')->user();
        abort_unless($user && $user->can(AdminRbac::permissionsForScreen($screen)['view']), 403);
    }
}
