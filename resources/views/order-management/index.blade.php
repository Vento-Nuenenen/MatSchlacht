@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card shadow-sm">
                    <div class="card-header">
                        <span class="h5 mb-0">Bestellungen verwalten</span>
                    </div>
                    <div class="card-body p-2 p-sm-3">
                        @php
                            $groupedOrders = $orders->groupBy(fn($o) => get_class($o->state));
                        @endphp

                        @foreach($stateClasses as $stateClass)
                            @php
                                $filteredOrders = $groupedOrders->get($stateClass, collect());
                                // Get an instance of the state to call display() and color()
                                $stateInstance = new $stateClass(null);
                            @endphp

                            <h5 class="d-flex align-items-center gap-2 mt-{{ $loop->first ? '1' : '4' }} mb-3">
                                <span>{{ $stateInstance->display() }}</span>
                                <i class="color-{{ $stateInstance->color() }} d-inline-flex">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-circle-fill" viewBox="0 0 16 16">
                                        <circle cx="8" cy="8" r="8"/>
                                    </svg>
                                </i>
                                <span class="badge bg-secondary rounded-pill small ms-auto">{{ $filteredOrders->count() }}</span>
                            </h5>

                            @if($filteredOrders->isNotEmpty())
                                <div class="table-responsive mb-4">
                                    <table class="table table-hover table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="sortable-header" style="min-width: 140px;">Anlass</th>
                                                <th class="sortable-header">Einheit</th>
                                                <th class="sortable-header">Besteller</th>
                                                <th class="sortable-header">Kollektionen</th>
                                                <th class="sortable-header text-nowrap">Von</th>
                                                <th class="sortable-header text-nowrap">Bis</th>
                                                <th class="text-nowrap" style="min-width: 100px;">Optionen</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($filteredOrders as $order)
                                                <tr>
                                                    <td>
                                                        <span class="fw-semibold">{{ $order->event }}</span>
                                                        @php
                                                            $hasContents = app(\App\Services\ComponentManager::class)->isEnabled('item_contents')
                                                                && $order->orderedItems->contains(fn($oi) => $oi->item->contents->isNotEmpty());
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
                                                            <i class="fa fa-cog me-1"></i> Verwalten
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted mb-4 small ps-2">Keine Bestellungen in diesem Status.</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
