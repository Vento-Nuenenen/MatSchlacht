@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Bestellungen</span>
                        @can('create orders')
                            <a href="{{ route('orders.create') }}" class="btn btn-info btn-sm">
                                <i class="fa fa-plus me-1"></i> Neue Bestellung
                            </a>
                        @endcan
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="sortable-header">Anlass</th>
                                        <th class="sortable-header">Einheit</th>
                                        <th class="sortable-header">Verantwortlich</th>
                                        <th class="sortable-header text-nowrap">Von</th>
                                        <th class="sortable-header text-nowrap">Bis</th>
                                        <th class="sortable-header">Status</th>
                                        <th class="text-nowrap" style="min-width: 140px;">Optionen</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $order->event }}</span>
                                            @php
                                                $hasContents = $order->orderedItems->contains(fn($oi) => $oi->item->contents->isNotEmpty());
                                            @endphp
                                            @if($hasContents)
                                                <i class="fa fa-info-circle text-info ms-1 cursor-pointer p-1"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#contentModal"
                                                   data-bs-title="Bestellinhalte ({{ $order->event }})"
                                                   data-bs-content="<ul class='list-unstyled mb-0'>@foreach($order->orderedItems as $oi) @if($oi->item->contents->isNotEmpty()) <li><strong>{{ $oi->item->name }}:</strong> <small>@foreach($oi->item->contents as $c){{ $c->quantity }} {{ $c->name }}@if(!$loop->last), @endif @endforeach</small></li> @endif @endforeach</ul>"></i>
                                            @endif
                                        </td>
                                        <td>{{ $order->role->name }}</td>
                                        <td>{{ $order->user->preferredName() }}</td>
                                        <td class="text-nowrap" data-sort-value="{{ \Carbon\Carbon::parse($order->event_start)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }}</td>
                                        <td class="text-nowrap" data-sort-value="{{ \Carbon\Carbon::parse($order->event_end)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $order->state->color() }}">
                                                {{ $order->state->display() }}
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="d-flex gap-1 flex-wrap align-items-center">
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm shadow-sm text-white">
                                                    <i class="fa fa-eye me-1"></i> Anzeigen
                                                </a>
                                                @if($order->state->equals(\App\States\Order\Stored::class))
                                                    @if(auth()->user()->can('edit orders') || (auth()->user()->can('edit own orders') && $order->user_id === auth()->id()))
                                                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    @endif
                                                    @if(auth()->user()->can('delete orders') || (auth()->user()->can('delete own orders') && $order->user_id === auth()->id()))
                                                        @include('components.delete', [
                                                            'route' => 'orders.destroy',
                                                            'id' => $order->id,
                                                        ])
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Keine Bestellungen vorhanden.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
