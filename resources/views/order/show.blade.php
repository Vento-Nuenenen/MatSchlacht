@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Bestellung Ansehen: <strong>{{ $order->event }}</strong></span>
                        <span class="badge bg-{{ $order->state->color() }} fs-6">{{ $order->state->display() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <h6 class="text-muted text-uppercase small fw-bold">Bestelldetails</h6>
                                <p class="mb-0">
                                    <b>Anlass:</b> {{ $order->event }} <br />
                                    <b>Einheit:</b> {{ $order->role->name }} <br />
                                    <b>Bestellt von:</b> {{ $order->user->fullName() }} <br />
                                </p>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <h6 class="text-muted text-uppercase small fw-bold">Zeitraum</h6>
                                <p class="mb-0">
                                    <b>Von:</b> {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} <br />
                                    <b>Bis:</b> {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }} <br />
                                </p>
                            </div>
                            @if($order->itemCollections->isNotEmpty())
                                <div class="col-12 col-md-12 col-lg-4">
                                    <h6 class="text-muted text-uppercase small fw-bold">Kollektionen</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($order->itemCollections as $coll)
                                            <span class="badge bg-info text-dark">{{ $coll->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr />

                        <h5 class="mb-3">Materialien</h5>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 140px;">Material</th>
                                        <th class="text-center" style="width: 15%; min-width: 80px;">Bestellt</th>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <th class="text-center" style="width: 15%; min-width: 80px;">Zurückgegeben</th>
                                        @endif
                                        <th>Materialgruppe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($order->orderedItems as $orderedItem)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $orderedItem->item->name }}</span>
                                            @component_enabled('item_contents')
                                            @if($orderedItem->item->contents->isNotEmpty())
                                                <i class="fa fa-info-circle text-info ms-1 cursor-pointer p-1"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#contentModal"
                                                   data-bs-title="Inhaltsliste von {{ $orderedItem->item->name }}"
                                                   data-bs-content="<ul class='list-unstyled mb-0'>@foreach($orderedItem->item->contents as $c)<li><i class='fa fa-check-square-o me-1 small text-muted'></i><strong>{{ $c->quantity }}</strong> {{ $c->name }}</li>@endforeach</ul>"></i>
                                            @endif
                                            @endcomponent_enabled
                                        </td>
                                        <td class="text-center fw-bold">{{ $orderedItem->quantity }}</td>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <td class="text-center fw-bold">{{ $orderedItem->returned }}</td>
                                        @endif
                                        <td>{{ $orderedItem->item->itemGroup?->name ?? 'Sonstiges' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Zurück zur Übersicht</a>
                            @component_enabled('item_contents')
                                <a href="{{ route('orders.manage.contents-pdf', $order) }}" class="btn btn-info text-white"
                                   target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i> Packliste PDF
                                </a>
                            @endcomponent_enabled
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
