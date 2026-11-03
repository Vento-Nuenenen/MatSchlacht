{{-- Procurements of an item on the item edit page; expects $item --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-primary"><i class="fa fa-cart-shopping me-2"></i>Beschaffungen</h5>
    </div>
    <div class="card-body">
        @if($item->procurements->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Datum</th>
                            <th class="text-center">Menge</th>
                            <th>Lieferant</th>
                            <th class="text-end text-nowrap">Stückpreis</th>
                            <th class="text-end">Total</th>
                            <th class="text-nowrap">Beleg-Nr.</th>
                            <th class="text-nowrap">Garantie bis</th>
                            <th>Notiz / Belege</th>
                            <th class="text-end">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->procurements as $procurement)
                            <tr>
                                <td class="text-nowrap">{{ $procurement->procured_on->format('d.m.Y') }}</td>
                                <td class="text-center">
                                    {{ $procurement->quantity }}
                                    @if($procurement->added_to_stock)
                                        <i class="fa fa-plus-circle text-success ms-1" title="Zum Bestand addiert"></i>
                                    @endif
                                </td>
                                <td>{{ $procurement->supplier }}</td>
                                <td class="text-end text-nowrap">{{ $procurement->unit_price_cents !== null ? 'CHF '.number_format($procurement->unit_price_cents / 100, 2, '.', "'") : '' }}</td>
                                <td class="text-end text-nowrap">{{ $procurement->totalPriceCents() !== null ? 'CHF '.number_format($procurement->totalPriceCents() / 100, 2, '.', "'") : '' }}</td>
                                <td>{{ $procurement->invoice_number }}</td>
                                <td class="text-nowrap">
                                    @if($procurement->warranty_until)
                                        <span class="badge {{ $procurement->isUnderWarranty() ? 'bg-success' : 'bg-secondary' }}">{{ $procurement->warranty_until->format('d.m.Y') }}</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div style="white-space: pre-line;">{{ $procurement->notes }}</div>
                                    @include('components.attachment-list', ['attachments' => $procurement->attachments])
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('items.procurements.edit', $procurement) }}" class="btn btn-warning btn-sm" title="Bearbeiten"><i class="fa fa-pen"></i></a>
                                    @include('components.delete', ['route' => 'items.procurements.destroy', 'id' => $procurement->id])
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-3">Noch keine Beschaffungen erfasst.</p>
        @endif

        <div class="p-3 p-md-4 bg-light rounded-3 border">
            <h6 class="text-uppercase text-muted small fw-bold mb-3">Beschaffung erfassen</h6>
            <form action="{{ route('items.procurements.store', $item) }}" method="POST" enctype="multipart/form-data">
                @include('item.procurement-form', ['procurement' => null])
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-success shadow-sm"><i class="fa fa-plus me-1"></i> Erfassen</button>
                </div>
            </form>
        </div>
    </div>
</div>
