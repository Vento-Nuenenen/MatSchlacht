@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ $item->name }}</span>
                        <div class="d-flex flex-wrap gap-1">
                            @component_enabled('storage')
                                <a href="{{ route('labels.items', ['items' => [$item->id]]) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                                    <i class="fa fa-print me-1"></i> Etikett drucken
                                </a>
                            @endcomponent_enabled
                            @can('update', $item)
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4 col-lg-3">Materialgruppe</dt>
                            <dd class="col-sm-8 col-lg-9">{{ $item->itemGroup?->name }}</dd>
                            <dt class="col-sm-4 col-lg-3">Bestand</dt>
                            <dd class="col-sm-8 col-lg-9 fw-bold">{{ $item->stock }}</dd>
                            <dt class="col-sm-4 col-lg-3">Packungsgrösse</dt>
                            <dd class="col-sm-8 col-lg-9">{{ $item->lot }}</dd>
                            <dt class="col-sm-4 col-lg-3">Reparatur</dt>
                            <dd class="col-sm-8 col-lg-9 mb-0">{{ $item->repair }}</dd>
                        </dl>
                    </div>
                </div>

                @component_enabled('storage')
                    <div class="card shadow-sm mb-4">
                        <div class="card-header h6 mb-0 py-3"><i class="fa fa-warehouse me-2"></i>Lagerplätze</div>
                        <ul class="list-group list-group-flush">
                            @forelse($item->stocks as $stock)
                                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                                    @can('view', $stock->storageUnit)
                                        <a href="{{ route('storage-units.show', $stock->storageUnit) }}" class="text-decoration-none">{{ $stock->storageUnit->path() }}</a>
                                    @else
                                        <span>{{ $stock->storageUnit->path() }}</span>
                                    @endcan
                                    <span class="badge bg-secondary">{{ $stock->quantity }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Keinem Lagerplatz zugeordnet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcomponent_enabled

                @component_enabled('maintenance')
                    <div class="card shadow-sm mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="h6 mb-0"><i class="fa fa-screwdriver-wrench me-2"></i>Wartung</span>
                            @include('maintenance.status', ['item' => $item, 'status' => $maintenanceStatus])
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse($item->maintenances as $maintenanceEntry)
                                <li class="list-group-item">
                                    <span class="text-nowrap fw-semibold">{{ $maintenanceEntry->performed_on->format('d.m.Y') }}</span>
                                    <span class="badge bg-secondary ms-1">{{ $maintenanceEntry->type->label() }}</span>
                                    @if($maintenanceEntry->description)
                                        <span class="ms-1">{{ $maintenanceEntry->description }}</span>
                                    @endif
                                    @include('components.attachment-list', ['attachments' => $maintenanceEntry->attachments])
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Noch keine Wartungen erfasst.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcomponent_enabled

                @component_enabled('procurement')
                    @can('manage procurements')
                        <div class="card shadow-sm mb-4">
                            <div class="card-header h6 mb-0 py-3"><i class="fa fa-cart-shopping me-2"></i>Beschaffungen</div>
                            <ul class="list-group list-group-flush">
                                @forelse($item->procurements as $procurement)
                                    <li class="list-group-item d-flex justify-content-between flex-wrap gap-2">
                                        <span>
                                            <span class="fw-semibold">{{ $procurement->procured_on->format('d.m.Y') }}</span>:
                                            {{ $procurement->quantity }} Stk.{{ $procurement->supplier ? ' bei '.$procurement->supplier : '' }}
                                        </span>
                                        @if($procurement->warranty_until)
                                            <span class="badge {{ $procurement->isUnderWarranty() ? 'bg-success' : 'bg-secondary' }}">Garantie bis {{ $procurement->warranty_until->format('d.m.Y') }}</span>
                                        @endif
                                        <div class="w-100">@include('components.attachment-list', ['attachments' => $procurement->attachments])</div>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">Noch keine Beschaffungen erfasst.</li>
                                @endforelse
                            </ul>
                        </div>
                    @endcan
                @endcomponent_enabled

                @component_enabled('item_contents')
                    @if($item->contents->isNotEmpty())
                        <div class="card shadow-sm mb-4">
                            <div class="card-header h6 mb-0 py-3"><i class="fa fa-list-ul me-2"></i>Inhaltsliste</div>
                            <ul class="list-group list-group-flush">
                                @foreach($item->contents as $content)
                                    <li class="list-group-item"><strong>{{ $content->quantity ?: '1x' }}</strong> {{ $content->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endcomponent_enabled

                <a href="{{ route('items.index') }}" class="btn btn-secondary">Zurück zur Übersicht</a>
            </div>
        </div>
    </div>
@endsection
