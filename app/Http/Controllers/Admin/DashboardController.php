<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Location;
use App\Models\Transaction;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'locations' => Location::count(),
            'devices' => Device::count(),
            'salesToday' => Transaction::whereDate('occurred_at', now()->toDateString())->sum('total'),
            'ticketsToday' => Transaction::whereDate('occurred_at', now()->toDateString())->count(),
        ]);
    }
}
