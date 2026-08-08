<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
        $this->middleware('auth');
        $this->authorizeResource(Order::class, 'order');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        if (auth()->user()->isPrivileged()) {
            $orders = Order::all()->sortBy('event_start');
        } else {
            $orders = Order::where('user_id', auth()->id())->orderBy('event_start')->get();
        }

        return view('order.index', ['orders' => $orders]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): Factory|View|RedirectResponse
    {
        if (auth()->user()->isPrivileged()) {
            return redirect()->route('orders.manage.edit', $order);
        }

        $order->load(['orderedItems.item.itemGroup', 'itemCollections']);

        return view('order.show', compact('order'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isPrivileged()) {
            $users = User::all();
            $roles = Role::all();
        } else {
            $roles = $user->roles;
            $roleIds = $roles->pluck('id');
            $users = User::whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })->get();
        }

        $items = Item::with('itemGroup')
            ->orderBy('name')->get();

        $collections = ItemCollection::with('items')->get();

        return view('order.create', [
            'users' => $users,
            'roles' => $roles,
            'items' => $items,
            'collections' => $collections,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($data['action'] === 'save') {
            $data['status'] = 'stored';
        }

        if ($data['action'] === 'order') {
            $data['status'] = 'committed';
        }

        $this->orderService->syncOrder(new Order, $data);

        return redirect()->route('orders.index')->with('success', 'Neue Bestellung wurde erfolgreich erstellt.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Factory|View
    {
        $user = auth()->user();

        if ($user->isPrivileged()) {
            $users = User::all();
            $roles = Role::all();
        } else {
            $roles = $user->roles;
            $roleIds = $roles->pluck('id');
            $users = User::whereHas('roles', function ($query) use ($roleIds) {
                $query->whereIn('roles.id', $roleIds);
            })->get();

            // Ensure the order's role and user are included even if they are not in current user's scope (should not happen normally but for safety)
            if ($order->role instanceof Role && ! $roles->contains('id', $order->role_id)) {
                $roles->push($order->role);
            }

            if ($order->user instanceof User && ! $users->contains('id', $order->user_id)) {
                $users->push($order->user);
            }
        }

        $items = Item::with('itemGroup')->orderBy('name')->get();
        $collections = ItemCollection::with('items')->get();

        return view('order.edit', compact('order', 'users', 'roles', 'items', 'collections'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        if ($data['action'] === 'save') {
            $data['status'] = 'stored';
        }

        if ($data['action'] === 'order') {
            $data['status'] = 'committed';
        }

        $this->orderService->syncOrder($order, $data);

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

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $to = $request->input('to');

        if (! $order->state->canTransitionTo($to)) {
            return back()->with('error', 'Ungültiger Statusübergang.');
        }

        $order->state->transitionTo($to);

        return back()->with('success', 'Bestellstatus wurde aktualisiert.');
    }
}
