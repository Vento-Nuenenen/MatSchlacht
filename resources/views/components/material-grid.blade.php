<div class="mb-3">
    <label class="form-label">Materialien</label>
    <input type="text" id="itemSearch" class="form-control" placeholder="Suchen…" oninput="filterItems()" />
</div>

<div id="itemsContainer">
    @php
        $grouped = $items->groupBy(fn($i) => optional($i->itemGroup)->position . '|' . optional($i->itemGroup)->name);
        $orderItemQuantities = isset($order) ? $order->orderedItems->pluck('quantity', 'item_id') : collect();
    @endphp

    @foreach($grouped as $groupKey => $groupItems)
        @php [$pos, $groupName] = array_pad(explode('|', $groupKey, 2), 2, 'Sonstiges') @endphp
        <div class="card mb-3 item-group">
            <div class="card-header">{{ $groupName }}</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th scope="col" class="col-6">Name</th>
                        <th scope="col" class="col-2">Verfügbar</th>
                        <th scope="col" class="col-{{ isset($order) && ($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class)) ? '1' : '2' }}">Packungsgrösse</th>
                        <th scope="col" class="col-{{ isset($order) && ($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class)) ? '1' : '2' }}">Bestellen</th>
                        @if(isset($order) && ($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class)))
                            <th scope="col" class="col-2">Zurückgegeben</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupItems as $item)
                        @php
                            $actualQty = old('items.'.$item->id, $orderItemQuantities->get($item->id, 0));
                            $displayQty = ($item->lot > 1) ? $actualQty / $item->lot : $actualQty;

                            $orderedItem = isset($order) ? $order->orderedItems->where('item_id', $item->id)->first() : null;
                            $returnedQty = old('returned.'.$item->id, $orderedItem?->returned ?? ($actualQty > 0 ? $actualQty : 0));
                            $displayReturnedQty = ($item->lot > 1) ? $returnedQty / $item->lot : $returnedQty;
                        @endphp
                        <tr class="item-row {{ $actualQty > 0 ? 'table-primary' : '' }}">
                            <td class="item-name">{{ $item->name }}</td>
                            <td>{{ $item->stock }}</td>
                            <td>{{ $item->lot }}</td>
                            <td>
                                <input
                                    type="number"
                                    min="0"
                                    class="form-control form-control-sm"
                                    name="items[{{ $item->id }}]"
                                    value="{{ $displayQty }}"
                                    {{ isset($order) && ($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class)) ? 'readonly' : '' }}>
                            </td>
                            @if(isset($order) && ($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class)))
                                <td>
                                    @if($actualQty > 0)
                                        <input
                                            type="number"
                                            min="0"
                                            class="form-control form-control-sm"
                                            name="returned[{{ $item->id }}]"
                                            value="{{ $displayReturnedQty }}"
                                            {{ $order->state->equals(\App\States\Order\Returned::class) || $order->state->equals(\App\States\Order\Archived::class) ? 'readonly' : '' }}>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<script>
    function filterItems() {
        const q = (document.getElementById('itemSearch').value || '').toLowerCase();
        const groups = document.querySelectorAll('#itemsContainer .item-group');
        groups.forEach(group => {
            let visible = 0;
            group.querySelectorAll('.item-row').forEach(row => {
                const name = row.querySelector('.item-name').textContent.toLowerCase();
                const match = !q || name.includes(q);
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            group.style.display = visible ? '' : 'none';
        });
    }
</script>
