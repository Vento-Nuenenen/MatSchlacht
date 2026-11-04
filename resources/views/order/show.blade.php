@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">{{ __('Bestellung Ansehen:') }} <strong>{{ $order->event }}</strong></span>
                        <span class="badge bg-{{ $order->state->color() }} fs-6">{{ $order->state->display() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6 col-lg-4">
                                <h6 class="text-muted text-uppercase small fw-bold">{{ __('Bestelldetails') }}</h6>
                                <p class="mb-0">
                                    <b>{{ __('Anlass:') }}</b> {{ $order->event }} <br />
                                    <b>{{ __('Einheit:') }}</b> {{ $order->role->name }} <br />
                                    <b>{{ __('Bestellt von:') }}</b> {{ $order->user->fullName() }} <br />
                                </p>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4">
                                <h6 class="text-muted text-uppercase small fw-bold">{{ __('Zeitraum') }}</h6>
                                <p class="mb-0">
                                    <b>{{ __('Von:') }}</b> {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} <br />
                                    <b>{{ __('Bis:') }}</b> {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }} <br />
                                </p>
                            </div>
                            @if($order->itemCollections->isNotEmpty())
                                <div class="col-12 col-md-12 col-lg-4">
                                    <h6 class="text-muted text-uppercase small fw-bold">{{ __('Kollektionen') }}</h6>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($order->itemCollections as $coll)
                                            <span class="badge bg-info text-dark">{{ $coll->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr />

                        <h5 class="mb-3">{{ __('Materialien') }}</h5>

                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 140px;">{{ __('Material') }}</th>
                                        <th class="text-center" style="width: 15%; min-width: 80px;">{{ __('Bestellt') }}</th>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <th class="text-center" style="width: 15%; min-width: 80px;">{{ __('Zurückgegeben') }}</th>
                                        @endif
                                        <th>{{ __('Materialgruppe') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($order->orderedItems as $orderedItem)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold">{{ $orderedItem->item->name }}</span>
                                            @include('components.content-info', ['title' => __('Inhaltsliste von :name', ['name' => $orderedItem->item->name]), 'lines' => $orderedItem->item->contentLines()])
                                        </td>
                                        <td class="text-center fw-bold">{{ $orderedItem->quantity }}</td>
                                        @if($order->state->equals(\App\States\Order\Delivered::class) || $order->state->equals(\App\States\Order\Returned::class))
                                            <td class="text-center fw-bold">{{ $orderedItem->returned }}</td>
                                        @endif
                                        <td>{{ $orderedItem->item->itemGroup?->name ?? __('Sonstiges') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
                            <a href="{{ route('orders.index') }}" class="btn btn-secondary">{{ __('Zurück zur Übersicht') }}</a>
                            @component_enabled('item_contents')
                                <a href="{{ route('orders.manage.contents-pdf', $order) }}" class="btn btn-info text-white"
                                   target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i> {{ __('Packliste PDF') }}
                                </a>
                            @endcomponent_enabled
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
