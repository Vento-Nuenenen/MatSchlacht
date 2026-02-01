<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\Order;
use App\States\Order\Accepted;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:edit orders']);
    }

    public function index(): Application|Factory|View
    {
        $stateClasses = [
            Stored::class,
            Committed::class,
            Accepted::class,
            Delivered::class,
            Returned::class,
            Archived::class,
        ];

        return view('manage.index', [
            'orders' => Order::all(),
            'stateClasses' => $stateClasses,
        ]);
    }

    public function edit(Order $order): Application|Factory|View
    {
        $items = Item::with('itemGroup')->orderBy('name')->get();

        return view('manage.edit', [
            'order' => $order->load('orderedItems.item', 'role', 'user'),
            'items' => $items,
        ]);
    }

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

        // Update ordered items
        $order->orderedItems()->delete();
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

        return redirect()->route('manage.edit', $order)->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $to = $request->input('to');

        if (!$order->status->canTransitionTo($to)) {
            return back()->with('error', 'Ungültiger Statusübergang.');
        }

        $order->status->transitionTo($to);

        return back()->with('success', 'Bestellstatus wurde aktualisiert.');
    }

    public function exportPdf(Order $order)
    {
        if (!$order->status->equals(Accepted::class)) {
            return back()->with('error', 'PDF Export ist nur für bestätigte Bestellungen möglich.');
        }

        $order->load(['orderedItems.item', 'role', 'user']);

        $view = view('manage.pdf', compact('order'));
        $html = $view->render();

        TCPDF::SetTitle('Auftragsliste - ' . $order->event);
        TCPDF::AddPage();
        TCPDF::writeHTML($html, true, false, true, false, '');

        return response(TCPDF::Output($order->id . '_auftragsliste.pdf', 'S'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
