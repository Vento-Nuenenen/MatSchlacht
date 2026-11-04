<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrderRequest;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Pdf\OrderSheetPdf;
use App\Services\Pdf\PackingListPdf;
use App\Services\Pdf\ReturnSheetPdf;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\OrderState;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class OrderManagementController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    /**
     * Display all active orders grouped by state.
     */
    public function index(): View
    {
        $this->authorize('manage', Order::class);

        $ordersByState = [];

        foreach ([Stored::class, Committed::class, Accepted::class, Delivered::class, Returned::class] as $state) {
            $ordersByState[$state] = $this->ordersInState($state);
        }

        return view('order-management.index', ['ordersByState' => $ordersByState]);
    }

    /**
     * Every state has its own page, so that the sections can be browsed independently.
     *
     * @param  class-string<OrderState>  $state
     * @return LengthAwarePaginator<int, Order>
     */
    protected function ordersInState(string $state): LengthAwarePaginator
    {
        return Order::with('itemCollections', 'role', 'user', 'orderedItems.item.contents')
            ->whereState('state', $state)
            ->orderBy('event_start')
            ->orderBy('id')
            ->paginate($this->perPage(), pageName: $state::getMorphClass().'_page')
            ->withQueryString();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): View
    {
        $this->authorize('update', $order);

        return view('order-management.edit', [
            'order' => $order->load('orderedItems.item', 'role', 'user', 'itemCollections.items'),
            'items' => Item::with('itemGroup', 'attachments', 'contents')->orderBy('name')->get(),
            'collections' => ItemCollection::with('items')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $this->orderService->updateManagedOrder($order, $request->validated());

        if (auth()->user()->isPrivileged()) {
            return redirect()->route('orders.manage')->with('success', __('Bestellung wurde erfolgreich aktualisiert.'));
        }

        return redirect()->route('orders.manage.edit', $order)->with('success', __('Bestellung wurde erfolgreich aktualisiert.'));
    }

    public function transition(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        try {
            $this->orderService->transition($order, $request->input('to'));
        } catch (ValidationException $exception) {
            return back()->with('error', implode(' ', Arr::flatten($exception->errors())));
        }

        return back()->with('success', __('Bestellstatus wurde aktualisiert.'));
    }

    public function exportOrderPdf(Order $order, OrderSheetPdf $document): Response|RedirectResponse
    {
        $this->authorize('export', $order);

        if (! $order->state->equals(Accepted::class)) {
            return back()->with('error', __('PDF Export ist nur für bestätigte Bestellungen möglich.'));
        }

        return $document->download($order);
    }

    public function exportReturnPdf(Order $order, ReturnSheetPdf $document): Response|RedirectResponse
    {
        $this->authorize('exportReturn', $order);

        if (! $order->state->equals(Delivered::class)) {
            return back()->with('error', __('Retourenliste ist nur für ausgegebene Bestellungen möglich.'));
        }

        return $document->download($order);
    }

    public function exportContentsPdf(Order $order, PackingListPdf $document): Response
    {
        $this->authorize('view', $order);

        return $document->download($order);
    }
}
