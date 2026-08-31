@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @include('components.status-alerts')

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Bestellung Ansehen: <strong>{{ $order->event }}</strong></span>
                        <span class="badge bg-{{ $order->state->color() }}">{{ $order->state->display() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Details</h5>
                                <p>
                                    <b>Anlass:</b> {{ $order->event }} <br />
                                    <b>Einheit:</b> {{ $order->role->name }} <br />
                                    <b>Bestellt von:</b> {{ $order->user->fullName() }} <br />
                                    <b>Von:</b> {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} <br />
                                    <b>Bis:</b> {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }} <br />
                                    @if($order->itemCollections->isNotEmpty())
                                        <b>Kollektionen:</b> {{ $order->itemCollections->pluck('name')->implode(', ') }} <br />
                                    @endif
                                </p>
                            </div>
                        </div>

                        <hr />

                        <h5>Details & Material</h5>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Material</th>
                                        <th>Bestellt</th>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <th>Zurückgegeben</th>
                                        @endif
                                        <th>Materialgruppe</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($order->orderedItems as $orderedItem)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $orderedItem->item->name }}</span>
                                            @if($orderedItem->item->contents->isNotEmpty())
                                                <i class="fa fa-info-circle text-info ms-1 cursor-pointer"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#contentModal"
                                                   data-bs-title="Inhaltsliste von {{ $orderedItem->item->name }}"
                                                   data-bs-content="<ul class='list-unstyled mb-0'>@foreach($orderedItem->item->contents as $c)<li><i class='fa fa-check-square-o me-1 small text-muted'></i><strong>{{ $c->quantity }}</strong> {{ $c->name }}</li>@endforeach</ul>"></i>
                                            @endif
                                        </td>
                                        <td>{{ $orderedItem->quantity }}</td>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <td>{{ $orderedItem->returned }}</td>
                                        @endif
                                        <td>{{ $orderedItem->item->itemGroup?->name ?? 'Sonstiges' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Zurück zur Übersicht</a>
                            <a href="{{ route('orders.manage.contents-pdf', $order) }}" class="btn btn-info text-white"
                               target="_blank">
                                <i class="fa fa-file-pdf me-1"></i> Packliste PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
