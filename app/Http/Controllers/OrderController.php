<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Mail\OrderCreated;
use App\Models\Item;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view orders')->only(['index', 'show']);
        $this->middleware('permission:create orders')->only(['create', 'store']);
        $this->middleware('permission:edit orders')->only(['edit', 'update']);
        $this->middleware('permission:delete orders')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Application|Factory|View
    {
        return view('order.index', ['orders' => Order::all()]);
    }

    public function show(Order $order): Application|Factory|View
    {
        return view('order.show', compact('order'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Application|Factory|View
    {
        $users = User::all();
        $roles = Role::all();

        $items = Item::with('itemGroup')
            ->orderBy('name')->get();

        return view('order.create', [
            'users' => $users,
            'roles' => $roles,
            'items' => $items,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['status'])) {
            $data['status'] = 'stored';
        }

        $order = Order::create([
            'event' => $data['event'],
            'event_start' => $data['event_start'],
            'event_end' => $data['event_end'],
            'status' => $data['status'],
            'role_id' => $data['role_id'],
            'user_id' => $data['user_id'],
        ]);

        // Create ordered items from submitted quantities
        $items = $data['items'] ?? [];
        foreach ($items as $itemId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $order->orderedItems()->create([
                    'item_id' => (int) $itemId,
                    'quantity' => $qty,
                    'confirmed' => false,
                    'delivered' => false,
                    'returned' => 0,
                    'repair' => 0,
                    'broken' => 0,
                ]);
            }
        }

        // Send notification email to users in the selected role (best-effort)
        try {
            $role = Role::find($data['role_id']);
            // Prefer users having the selected role (Spatie Permission)
            $recipients = $role ? User::role($role->name)->get() : collect();
            // Fallback: at least email the ordering user
            if ($recipients->isEmpty()) {
                $orderingUser = User::find($data['user_id']);
                if ($orderingUser) {
                    $recipients = collect([$orderingUser]);
                }
            }

            if ($recipients->isNotEmpty()) {
                foreach ($recipients as $user) {
                    if (!empty($user->email)) {
                        Mail::to($user->email)->send(new OrderCreated($order));
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error($e);
        }

        return redirect()->route('orders.index')->with('success', 'Neue Bestellung wurde erfolgreich erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Application|Factory|View
    {
        return view('order.edit', compact('order'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

        return redirect()->route('orders.index')->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Bestellung wurde erfolgreich gelöscht.');
    }
}
