@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="card">
                    <div class="card-header">Bestellungen verwalten</div>
                    <div class="card-body">
                        @php
                            $groupedOrders = $orders->groupBy(fn($o) => get_class($o->status));
                        @endphp

                        @foreach($stateClasses as $stateClass)
                            @php
                                $filteredOrders = $groupedOrders->get($stateClass, collect());
                                // Get an instance of the state to call display() and color()
                                $stateInstance = new $stateClass(null);
                            @endphp

                            <h4>
                                {{ $stateInstance->display() }}

                                <i class="color-{{ $stateInstance->color() }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                        <circle cx="8" cy="8" r="8"/>
                                    </svg>
                                </i>
                            </h4>

                            @if($filteredOrders->isNotEmpty())
                                <table class="table table-hover table-bordered table-striped mb-4">
                                    <thead>
                                    <tr>
                                        <th>Anlass</th>
                                        <th>Einheit</th>
                                        <th>Vom</th>
                                        <th>Bis</th>
                                        <th>Optionen</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($filteredOrders as $order)
                                        <tr>
                                            <td>{{ $order->event }}</td>
                                            <td>{{ $order->role->name }}</td>
                                            <td>{{ $order->event_start }}</td>
                                            <td>{{ $order->event_end }}</td>
                                            <td>
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-info btn-sm">Anzeigen</a> &nbsp;

                                                @if($order->status->equals(\App\States\Order\Stored::class))
                                                    @can('edit orders')
                                                        <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-warning btn-sm">Bearbeiten</a>
                                                    @endcan
                                                @endif

                                                @if($order->status->equals(\App\States\Order\Committed::class))
                                                    <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="to" value="accepted">
                                                        <button type="submit" class="btn btn-primary btn-sm">Akzeptieren</button>
                                                    </form>
                                                @endif

                                                @if($order->status->equals(\App\States\Order\Accepted::class))
                                                    <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="to" value="delivered">
                                                        <button type="submit" class="btn btn-info btn-sm">Ausgeben</button>
                                                    </form>
                                                @endif

                                                @if($order->status->equals(\App\States\Order\Delivered::class))
                                                    <form action="{{ route('orders.transition', $order) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="to" value="returned">
                                                        <button type="submit" class="btn btn-secondary btn-sm">Zurückerhalten</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr class="collapse" id="orderItems{{ $order->id }}">
                                            <td colspan="6">
                                                <div class="p-3 bg-light border">
                                                    <h5>Material für {{ $order->event }}</h5>
                                                    <form method="POST" action="{{ route('orders.update', $order->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        {{-- Keep existing metadata --}}
                                                        <input type="hidden" name="event" value="{{ $order->event }}">
                                                        <input type="hidden" name="role_id" value="{{ $order->role_id }}">
                                                        <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                                                        <input type="hidden" name="event_start" value="{{ \Carbon\Carbon::parse($order->event_start)->format('Y-m-d\TH:i') }}">
                                                        <input type="hidden" name="event_end" value="{{ \Carbon\Carbon::parse($order->event_end)->format('Y-m-d\TH:i') }}">

                                                        @include('components.material-grid', ['items' => $items, 'order' => $order])

                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-primary btn-sm">Material-Änderungen speichern</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted mb-4">Keine Bestellungen in diesem Status.</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
