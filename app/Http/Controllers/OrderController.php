<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\OrderService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
        $this->authorizeResource(Order::class, 'order');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        $orders = Order::visibleTo(auth()->user())
            ->with('role', 'user', 'orderedItems.item.contents')
            ->orderBy('event_start')
            ->orderBy('id')
            ->paginate($this->perPage());

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

        $order->load(['orderedItems.item.itemGroup', 'orderedItems.item.contents', 'itemCollections']);

        return view('order.show', compact('order'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Factory|View
    {
        $user = auth()->user();

        return view('order.create', [
            'users' => $user->scopedUsersQuery()->get(),
            'roles' => $user->scopedRoles(),
            ...$this->catalog(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->orderService->createOrder($request->validated());

        return redirect()->route('orders.index')->with('success', __('Neue Bestellung wurde erfolgreich erstellt.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Factory|View
    {
        $user = auth()->user();
        $roles = $user->scopedRoles();
        $users = $user->scopedUsersQuery()->get();

        // Keep the order's current unit and orderer selectable even if they are out of the user's scope.
        if ($order->role instanceof Role && ! $roles->contains('id', $order->role_id)) {
            $roles->push($order->role);
        }

        if ($order->user instanceof User && ! $users->contains('id', $order->user_id)) {
            $users->push($order->user);
        }

        return view('order.edit', [
            'order' => $order,
            'users' => $users,
            'roles' => $roles,
            ...$this->catalog(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->updateOrder($order, $request->validated());

        return redirect()->route('orders.index')->with('success', __('Bestellung wurde erfolgreich aktualisiert.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return redirect()->route('orders.index')->with('success', __('Bestellung wurde erfolgreich gelöscht.'));
    }

    /**
     * The orderable items and collections shown in the order form.
     *
     * @return array{items: Collection<int, Item>, collections: Collection<int, ItemCollection>}
     */
    protected function catalog(): array
    {
        return [
            'items' => Item::with('itemGroup', 'attachments', 'contents')->orderBy('name')->get(),
            'collections' => app(ComponentManager::class)->isEnabled('item_collections')
                ? ItemCollection::with('items')->get()
                : new Collection,
        ];
    }
}
