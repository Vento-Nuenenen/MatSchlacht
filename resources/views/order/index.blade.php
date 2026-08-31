@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @include('components.status-alerts')

                <div class="card">
                    <div class="card-header">Bestellungen</div>
                    <div class="card-body">
                        @can('create orders')
                            <a href="{{ route('orders.create') }}" class="btn btn-info btn-sm col-12">Neue Bestellung</a>
                            <br />
                            <br />
                        @endcan

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered table-striped">
                                <thead>
                                    <th class="sortable-header">Anlass</th>
                                    <th class="sortable-header">Einheit</th>
                                    <th class="sortable-header">Verantwortlich</th>
                                    <th class="sortable-header">Von</th>
                                    <th class="sortable-header">Bis</th>
                                    <th class="sortable-header">Status</th>
                                    <th>Optionen</th>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td>
                                            {{ $order->event }}
                                            @php
                                                $hasContents = $order->orderedItems->contains(fn($oi) => $oi->item->contents->isNotEmpty());
                                            @endphp
                                            @if($hasContents)
                                                <i class="fa fa-info-circle text-info ms-1 cursor-pointer"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#contentModal"
                                                   data-bs-title="Bestellinhalte ({{ $order->event }})"
                                                   data-bs-content="<ul class='list-unstyled mb-0'>@foreach($order->orderedItems as $oi) @if($oi->item->contents->isNotEmpty()) <li><strong>{{ $oi->item->name }}:</strong> <small>@foreach($oi->item->contents as $c){{ $c->quantity }} {{ $c->name }}@if(!$loop->last), @endif @endforeach</small></li> @endif @endforeach</ul>"></i>
                                            @endif
                                        </td>
                                        <td>{{ $order->role->name }}</td>
                                        <td>{{ $order->user->preferredName() }}</td>
                                        <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_start)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }}</td>
                                        <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_end)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $order->state->color() }}">
                                                {{ $order->state->display() }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info shadow-sm">
                                                <i class="fa fa-eye me-1"></i> Anzeigen
                                            </a>
                                            &nbsp;
                                            @if($order->state->equals(\App\States\Order\Stored::class))
                                                @if(auth()->user()->can('edit orders') || (auth()->user()->can('edit own orders') && $order->user_id === auth()->id()))
                                                    <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning">Bearbeiten</a>
                                                    &nbsp;
                                                @endif
                                                @if(auth()->user()->can('delete orders') || (auth()->user()->can('delete own orders') && $order->user_id === auth()->id()))
                                                    @include('components.delete', [
                                                        'route' => 'orders.destroy',
                                                        'id' => $order->id,
                                                    ])
                                                @endif

                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
