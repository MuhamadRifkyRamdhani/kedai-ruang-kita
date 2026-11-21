<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard)
    {
    }

    /** GET /admin?periode=hari|minggu|bulan */
    public function index(Request $request): View
    {
        return view('admin.dashboard', $this->dashboard->summary((string) $request->query('periode', 'hari')));
    }
}
