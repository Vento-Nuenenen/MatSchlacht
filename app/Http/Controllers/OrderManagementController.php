<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PdfService;
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
    public function __construct(
        protected OrderService $orderService,
        protected PdfService $pdfService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Factory|View
    {
        $this->authorize('manage', Order::class);

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
        $this->authorize('update', $order);

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
        $user = auth()->user();

        if ($order->state->equals(Delivered::class)) {
            $this->orderService->syncReturnedItems($order, $data['returned'] ?? []);
            // Also update basic order info if provided
            $order->update([
                'event' => $data['event'],
                'event_start' => $data['event_start'],
                'event_end' => $data['event_end'],
                'role_id' => $data['role_id'],
                'user_id' => $data['user_id'],
            ]);
        } else {
            $this->orderService->syncOrder($order, $data);
        }

        if ($user->isPrivileged()) {
            return redirect()->route('orders.manage')->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
        }

        return redirect()->route('orders.manage.edit', $order)->with('success', 'Bestellung wurde erfolgreich aktualisiert.');
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

    public function exportOrderPdf(Order $order)
    {
        $this->authorize('export', $order);

        if (! $order->state->equals(Accepted::class)) {
            return back()->with('error', 'PDF Export ist nur für bestätigte Bestellungen möglich.');
        }

        $order->load(['orderedItems.item', 'role', 'user', 'itemCollections']);

        $this->pdfService->init('Auftragsliste - '.$order->event);
        $this->pdfService->renderHeader('Auftragsliste', $order->event);
        $this->pdfService->renderOrderInfo($order);

        $this->pdfService->renderSectionTitle('Materialkollektionen');
        TCPDF::SetFont('helvetica', '', 10);

        if ($order->itemCollections->isNotEmpty()) {
            $collections = $order->itemCollections->groupBy('id')->map(function ($group) {
                $count = $group->count();

                return $count > 1 ? $group->first()->name.' ('.$count.'x)' : $group->first()->name;
            })->implode(', ');
            TCPDF::MultiCell(0, 7, $collections, 0, 'L');
        } else {
            TCPDF::Cell(0, 7, 'Es wurden keine Materialkollektionen angefordert.', 0, 1, 'L');
        }
        TCPDF::Ln(5);

        $this->pdfService->renderSectionTitle('Ausgegebene Materialien');

        $columns = [
            ['width' => 15, 'label' => 'Pos.', 'align' => 'C'],
            ['width' => 105, 'label' => 'Bezeichnung', 'align' => 'L'],
            ['width' => 30, 'label' => 'Bestellt', 'align' => 'C'],
            ['width' => 30, 'label' => 'Ausgegeben', 'align' => 'C'],
        ];

        $this->pdfService->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;
        foreach ($order->orderedItems as $index => $orderedItem) {
            $this->pdfService->checkPageBreak(250, $columns);

            TCPDF::SetFillColor(250, 250, 250);
            TCPDF::Cell(15, 7, $index + 1, 1, 0, 'C', $fill);
            TCPDF::Cell(105, 7, ' '.$orderedItem->item->name, 1, 0, 'L', $fill);
            TCPDF::Cell(30, 7, $orderedItem->quantity, 1, 0, 'C', $fill);
            TCPDF::Cell(30, 7, '', 1, 1, 'C', $fill);
            $fill = ! $fill;
        }

        $this->pdfService->renderSignatures($order->user->fullName());

        return response($this->pdfService->output($order->id.'_auftragsliste.pdf'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function exportReturnPdf(Order $order)
    {
        $this->authorize('exportReturn', $order);

        if (! $order->state->equals(Delivered::class)) {
            return back()->with('error', 'Retourenliste ist nur für ausgegebene Bestellungen möglich.');
        }

        $order->load(['orderedItems.item', 'role', 'user', 'itemCollections']);

        $this->pdfService->init('Retourenliste - '.$order->event);
        $this->pdfService->renderHeader('Retourenliste', $order->event);
        $this->pdfService->renderOrderInfo($order);

        $this->pdfService->renderSectionTitle('Retoure');

        $columns = [
            ['width' => 12, 'label' => 'Pos.', 'align' => 'C'],
            ['width' => 88, 'label' => 'Bezeichnung', 'align' => 'L'],
            ['width' => 25, 'label' => 'Geliefert', 'align' => 'C'],
            ['width' => 25, 'label' => 'Retourniert', 'align' => 'C'],
            ['width' => 30, 'label' => 'Defekt', 'align' => 'C'],
        ];

        $this->pdfService->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;
        foreach ($order->orderedItems as $index => $orderedItem) {
            $this->pdfService->checkPageBreak(250, $columns);

            TCPDF::SetFillColor(250, 250, 250);
            TCPDF::Cell(12, 7, $index + 1, 1, 0, 'C', $fill);
            TCPDF::Cell(88, 7, ' '.$orderedItem->item->name, 1, 0, 'L', $fill);
            TCPDF::Cell(25, 7, $orderedItem->delivered, 1, 0, 'C', $fill);
            TCPDF::Cell(25, 7, '', 1, 0, 'C', $fill);
            TCPDF::Cell(30, 7, '', 1, 1, 'C', $fill);
            $fill = ! $fill;
        }

        $this->pdfService->renderSignatures($order->user->fullName());

        return response($this->pdfService->output($order->id.'_rueckgabe.pdf'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
