<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\MaintenanceService;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Illuminate\View\View;

class OverwatchController extends Controller
{
    public function index(ComponentManager $components, MaintenanceService $maintenance): View
    {
        $user = auth()->user();
        $stats = [];

        if ($user->isPrivileged()) {
            $stats['total_users'] = User::count();
            $stats['total_items'] = Item::count();
            $stats['pending_orders'] = Order::whereState('state', Committed::class)->count();
            $stats['draft_orders'] = Order::whereState('state', Stored::class)->count();
            $stats['total_orders'] = Order::count();
        }

        $user_stats = [
            'total_orders' => $user->orders()->count(),
            'active_orders' => $user->orders()->whereNotState('state', [Archived::class, Returned::class])->count(),
        ];

        $dueMaintenanceCount = $components->isEnabled('maintenance') && $user->can('manage maintenance')
            ? $maintenance->dueItems()->count()
            : 0;

        return view('overwatch.index', compact('stats', 'user_stats', 'dueMaintenanceCount'));
    }
}
