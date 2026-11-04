<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class MaintenanceOverviewController extends Controller
{
    /**
     * List all items with a maintenance interval and their due dates.
     */
    public function index(Request $request, MaintenanceService $maintenance): View
    {
        $this->authorize('view items');

        $isDueOnly = $request->boolean('due');
        $items = $isDueOnly ? $maintenance->dueItems() : $maintenance->scheduledItems();
        $page = Paginator::resolveCurrentPage();

        return view('maintenance.index', [
            'items' => (new LengthAwarePaginator($items->forPage($page, $this->perPage()), $items->count(), $this->perPage(), $page, ['path' => $request->url()]))->withQueryString(),
            'isDueOnly' => $isDueOnly,
            'maintenance' => $maintenance,
        ]);
    }
}
