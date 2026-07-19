<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Stored;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mail;
use Spatie\Permission\Models\Role;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view orders')->only(['index', 'show']);
        $this->middleware('permission:create orders')->only(['create', 'store']);
        // We handle edit/delete permissions manually in the methods to allow "own" variants
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

        $user = auth()->user();

        if (! $user->isPrivileged()) {
            // Check if user has the role
            if (! $user->hasRole(Role::findById($data['role_id'])->name)) {
                return back()->withErrors(['role_id' => 'Sie haben keine Berechtigung für diese Einheit zu bestellen.'])->withInput();
            }

            // Check if the selected user shares a role with the current user
            $targetUser = User::findOrFail($data['user_id']);
            $sharedRoles = $user->roles->intersect($targetUser->roles);
            if ($sharedRoles->isEmpty()) {
                return back()->withErrors(['user_id' => 'Die ausgewählte Person ist nicht in Ihrem Bereich.'])->withInput();
            }
        }

        if ($data['action'] === 'save') {
            $data['status'] = 'stored';
        }

        if ($data['action'] === 'order') {
            $data['status'] = 'committed';
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
                $item = Item::find($itemId);
                if ($item && $item->lot > 1) {
                    $qty = $qty * $item->lot;
                }

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

        $order->itemCollections()->detach();
        foreach ($data['collections'] ?? [] as $collectionId) {
            $order->itemCollections()->attach($collectionId);
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
                    if (! empty($user->email)) {
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
    public function edit(Order $order): Factory|View
    {
        $user = auth()->user();
        if ($user->cannot('edit orders')) {
            if ($user->can('edit own orders')) {
                if ($order->user_id !== $user->id) {
                    abort(403);
                }
            } else {
                abort(403);
            }
        }

        if (! $order->state->equals(Stored::class)) {
            abort(403, 'Nur Entwürfe können bearbeitet werden.');
        }

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
            if (! $roles->contains('id', $order->role_id)) {
                $roles->push($order->role);
            }

            if (! $users->contains('id', $order->user_id)) {
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
        $user = auth()->user();

        if ($user->cannot('edit orders')) {
            if ($user->can('edit own orders')) {
                if ($order->user_id !== $user->id) {
                    abort(403);
                }
            } else {
                abort(403);
            }
        }

        if (! $order->state->equals(Stored::class) && ! $user->isPrivileged()) {
            if (! $user->can('edit orders')) {
                return redirect()->route('orders.show', $order)->with('error', 'Nur Entwürfe können bearbeitet werden.');
            }
        }

        // Basic validation for role_id and user_id if they are in the request
        if ($request->has('role_id') || $request->has('user_id')) {
            if (! $user->isPrivileged()) {
                if ($request->has('role_id')) {
                    if (! $user->hasRole(Role::findById($request->role_id)->name)) {
                        return back()->withErrors(['role_id' => 'Sie haben keine Berechtigung für diese Einheit zu bestellen.'])->withInput();
                    }
                }
                if ($request->has('user_id')) {
                    $targetUser = User::findOrFail($request->user_id);
                    $sharedRoles = $user->roles->intersect($targetUser->roles);
                    if ($sharedRoles->isEmpty()) {
                        // Allow if it's the current user_id of the order (in case it was already out of scope)
                        if ($targetUser->id !== $order->user_id) {
                            return back()->withErrors(['user_id' => 'Die ausgewählte Person ist nicht in Ihrem Bereich.'])->withInput();
                        }
                    }
                }
            }
        }

        $data = $request->validated();

        if ($data['action'] === 'save') {
            $data['status'] = 'stored';
        }

        if ($data['action'] === 'order') {
            $data['status'] = 'committed';
        }

        $order->update([
            'event' => $data['event'],
            'event_start' => $data['event_start'],
            'event_end' => $data['event_end'],
            'status' => $data['status'],

            'role_id' => $data['role_id'],
            'user_id' => $data['user_id'],
        ]);

        // Update ordered items
        $order->orderedItems()->delete();
        $items = $data['items'] ?? [];
        foreach ($items as $itemId => $qty) {
            $qty = (int) $qty;
            if ($qty > 0) {
                $item = Item::find($itemId);

                if ($item && $item->lot > 1) {
                    $qty = $qty * $item->lot;
                }

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

        $order->itemCollections()->detach();
        foreach ($data['collections'] ?? [] as $collectionId) {
            $order->itemCollections()->attach($collectionId);
        }

        return redirect()->route('orders.index')->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $user = auth()->user();

        if ($user->cannot('delete orders')) {
            if ($user->can('delete own orders')) {
                if ($order->user_id !== $user->id) {
                    abort(403);
                }
            } else {
                abort(403);
            }
        }

        $order->delete();

        return redirect()->route('orders.index')->with('success', 'Bestellung wurde erfolgreich gelöscht.');
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $to = $request->input('to');

        if (! $order->state->canTransitionTo($to)) {
            return back()->with('error', 'Ungültiger Statusübergang.');
        }

        $order->state->transitionTo($to);

        return back()->with('success', 'Bestellstatus wurde aktualisiert.');
    }
}
