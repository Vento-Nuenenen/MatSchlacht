<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;

class OverwatchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View|Factory|Application
    {
        $user = auth()->user();
        $stats = [];

        if ($user->hasRole('Materialwart')) {
            $stats['total_users'] = User::count();
            $stats['total_items'] = Item::count();
            $stats['pending_orders'] = Order::whereState('status', Committed::class)->count();
            $stats['draft_orders'] = Order::whereState('status', Stored::class)->count();
            $stats['total_orders'] = Order::count();
        }

        $user_stats = [
            'total_orders' => $user->orders()->count(),
            'active_orders' => $user->orders()->whereNotState('status', [Archived::class, Returned::class])->count(),
        ];

        return view('overwatch.index', compact('stats', 'user_stats'));
    }
}
