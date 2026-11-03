{{-- Maintenance log of an item on the item edit page; expects $item and $maintenanceStatus --}}
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 text-primary"><i class="fa fa-screwdriver-wrench me-2"></i>Wartung</h5>
        @include('maintenance.status', ['item' => $item, 'status' => $maintenanceStatus])
    </div>
    <div class="card-body">
        @if($item->maintenances->isNotEmpty())
            <div class="table-responsive mb-3">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Datum</th>
                            <th>Art</th>
                            <th class="text-center">Menge</th>
                            <th>Durchgeführt von</th>
                            <th class="text-end">Kosten</th>
                            <th>Beschreibung / Belege</th>
                            @can('manage maintenance')
                                <th class="text-end">Aktion</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($item->maintenances as $maintenanceEntry)
                            <tr>
                                <td class="text-nowrap">{{ $maintenanceEntry->performed_on->format('d.m.Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $maintenanceEntry->type->label() }}</span></td>
                                <td class="text-center">{{ $maintenanceEntry->quantity }}</td>
                                <td>{{ $maintenanceEntry->performed_by ?? $maintenanceEntry->user?->fullName() }}</td>
                                <td class="text-end text-nowrap">{{ $maintenanceEntry->cost_cents !== null ? 'CHF '.number_format($maintenanceEntry->cost_cents / 100, 2, '.', "'") : '' }}</td>
                                <td class="small">
                                    <div style="white-space: pre-line;">{{ $maintenanceEntry->description }}</div>
                                    @include('components.attachment-list', ['attachments' => $maintenanceEntry->attachments])
                                </td>
                                @can('manage maintenance')
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('items.maintenances.edit', $maintenanceEntry) }}" class="btn btn-warning btn-sm" title="Bearbeiten"><i class="fa fa-pen"></i></a>
                                        @include('components.delete', ['route' => 'items.maintenances.destroy', 'id' => $maintenanceEntry->id])
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-3">Noch keine Wartungen erfasst.</p>
        @endif

        @can('manage maintenance')
            <div class="p-3 p-md-4 bg-light rounded-3 border">
                <h6 class="text-uppercase text-muted small fw-bold mb-3">Wartung erfassen</h6>
                <form action="{{ route('items.maintenances.store', $item) }}" method="POST" enctype="multipart/form-data">
                    @include('item.maintenance-form', ['maintenanceEntry' => null])
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" class="btn btn-success shadow-sm"><i class="fa fa-plus me-1"></i> Erfassen</button>
                    </div>
                </form>
            </div>
        @endcan
    </div>
</div>
