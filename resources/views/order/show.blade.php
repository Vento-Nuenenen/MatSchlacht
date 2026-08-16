@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if(session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

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
                                        <td>{{ $orderedItem->item->name }}</td>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
