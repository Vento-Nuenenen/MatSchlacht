<?php

namespace App\Http\Controllers;

use App\Services\MaintenanceService;
use Illuminate\Http\Request;
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

        return view('maintenance.index', [
            'items' => $items,
            'isDueOnly' => $isDueOnly,
            'maintenance' => $maintenance,
        ]);
    }
}
