@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                @include('components.status-alerts')

                <div class="card">
                    <div class="card-header">Bestellung verwalten</div>
                    <div class="card-body">
                        @php
                            $groupedOrders = $orders->groupBy(fn($o) => get_class($o->state));
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
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered table-striped mb-4">
                                        <thead>
                                            <tr>
                                                <th class="sortable-header">Anlass</th>
                                                <th class="sortable-header">Einheit</th>
                                                <th class="sortable-header">Besteller</th>
                                                <th class="sortable-header">Kollektionen</th>
                                                <th class="sortable-header">Von</th>
                                                <th class="sortable-header">Bis</th>
                                                <th>Optionen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($filteredOrders as $order)
                                                <tr>
                                                    <td>{{ $order->event }}</td>
                                                    <td>{{ $order->role->name }}</td>
                                                    <td>{{ $order->user->fullName() }}</td>
                                                    <td>
                                                        @foreach($order->itemCollections->groupBy('id') as $collectionId => $group)
                                                            @php $count = $group->count(); @endphp
                                                            <span class="badge bg-info text-dark">
                                                                {{ $group->first()->name }}
                                                                @if($count > 1) ({{ $count }}x) @endif
                                                            </span>
                                                        @endforeach
                                                    </td>
                                                    <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_start)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }}</td>
                                                    <td data-sort-value="{{ \Carbon\Carbon::parse($order->event_end)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="{{ route('orders.manage.edit', $order->id) }}" class="btn btn-warning btn-sm">Verwalten</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
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
