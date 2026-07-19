<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Elibyy\TCPDF\Facades\TCPDF;
use Faker\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:edit orders']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        $stateClasses = [
            Stored::class,
            Committed::class,
            Accepted::class,
            Delivered::class,
            Returned::class,
        ];

        return view('order-management.index', [
            'orders' => Order::with('itemCollections', 'role', 'user')->get(),
            'stateClasses' => $stateClasses,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Factory|View
    {
        $items = Item::with('itemGroup')->orderBy('name')->get();
        $collections = ItemCollection::with('items')->get();

        return view('order-management.edit', [
            'order' => $order->load('orderedItems.item', 'role', 'user'),
            'items' => $items,
            'collections' => $collections,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $data = $request->validated();

        $order->update([
            'event' => $data['event'],
            'event_start' => $data['event_start'],
            'event_end' => $data['event_end'],
            'role_id' => $data['role_id'],
            'user_id' => $data['user_id'],
        ]);

        if ($order->state->equals(Delivered::class)) {
            // In Delivered state, we update 'returned' and keep 'quantity' as is
            $returned = $data['returned'] ?? [];
            foreach ($returned as $itemId => $qty) {
                $qty = (int) $qty;
                $item = Item::find($itemId);
                if ($item && $item->lot > 1) {
                    $qty = $qty * $item->lot;
                }

                $order->orderedItems()->where('item_id', $itemId)->update(['returned' => $qty]);
            }
        } else {
            // Update ordered items (quantities)
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
        }

        $order->itemCollections()->detach();
        foreach ($data['collections'] ?? [] as $collectionId) {
            $order->itemCollections()->attach($collectionId);
        }

        return redirect()->route('orders.manage.edit', $order)->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
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

    public function exportPdf(Order $order)
    {
        if (! $order->state->equals(Accepted::class)) {
            return back()->with('error', 'PDF Export ist nur für bestätigte Bestellungen möglich.');
        }

        $order->load(['orderedItems.item', 'role', 'user', 'itemCollections']);

        $view = view('order-management.pdf', compact('order'));
        $html = $view->render();

        TCPDF::SetTitle('Auftragsliste - '.$order->event);
        TCPDF::AddPage();
        TCPDF::writeHTML($html, true, false, true, false, '');

        return response(TCPDF::Output($order->id.'_auftragsliste.pdf', 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
