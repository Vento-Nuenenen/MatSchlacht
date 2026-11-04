@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <h2 class="mb-3">{{ __('Bestellungen verwalten') }}</h2>

                <div class="d-flex flex-column gap-4">
                    @foreach($ordersByState as $stateClass => $filteredOrders)
                        @php
                            // Get an instance of the state to call display() and color()
                            $stateInstance = new $stateClass(null);
                        @endphp

                        <section class="card shadow-sm" aria-labelledby="state-{{ $stateInstance::getMorphClass() }}">
                            <div class="bg-{{ $stateInstance->color() }}" style="height: 4px;"></div>
                            <div class="card-header d-flex align-items-center gap-2">
                                <h3 class="h6 mb-0" id="state-{{ $stateInstance::getMorphClass() }}">{{ $stateInstance->display() }}</h3>
                                <span class="badge bg-secondary rounded-pill ms-auto">{{ $filteredOrders->total() }}</span>
                            </div>

                            @if($filteredOrders->isNotEmpty())
                                <div class="card-body p-0 table-responsive">
                                    <table class="table table-hover table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="sortable-header" style="min-width: 140px;">{{ __('Anlass') }}</th>
                                                <th class="sortable-header">{{ __('Einheit') }}</th>
                                                <th class="sortable-header">{{ __('Besteller') }}</th>
                                                <th class="sortable-header">{{ __('Kollektionen') }}</th>
                                                <th class="sortable-header text-nowrap">{{ __('Von') }}</th>
                                                <th class="sortable-header text-nowrap">{{ __('Bis') }}</th>
                                                <th class="text-nowrap" style="min-width: 100px;">{{ __('Optionen') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($filteredOrders as $order)
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold">{{ $order->event }}</span>
@include('components.content-info', ['title' => __('Bestellinhalte (:event)', ['event' => $order->event]), 'lines' => $order->contentLines()])
                                                    </td>
                                                    <td>{{ $order->role->name }}</td>
                                                    <td>{{ $order->user->fullName() }}</td>
                                                    <td>
                                                        <div class="d-flex flex-wrap gap-1">
                                                            @foreach($order->itemCollections->groupBy('id') as $collectionId => $group)
                                                                @php $count = $group->count(); @endphp
                                                                <span class="badge bg-info text-dark">
                                                                    {{ $group->first()->name }}
                                                                    @if($count > 1) ({{ $count }}x) @endif
                                                                </span>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="text-nowrap" data-sort-value="{{ \Carbon\Carbon::parse($order->event_start)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }}</td>
                                                    <td class="text-nowrap" data-sort-value="{{ \Carbon\Carbon::parse($order->event_end)->format('Ymd') }}">{{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }}</td>
                                                    <td class="text-nowrap">
                                                        <a href="{{ route('orders.manage.edit', $order->id) }}" class="btn btn-warning btn-sm shadow-sm">
                                                            <i class="fa fa-cog me-1"></i> {{ __('Verwalten') }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                {{ $filteredOrders->links() }}
                            @else
                                <div class="card-body py-2">
                                    <p class="text-muted small mb-0">{{ __('Keine Bestellungen in diesem Status.') }}</p>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
