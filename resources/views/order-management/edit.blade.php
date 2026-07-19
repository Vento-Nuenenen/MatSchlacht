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

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Bestellung verwalten: <strong>{{ $order->event }}</strong></span>
                        <span class="badge bg-{{ $order->state->color() }}">{{ $order->state->display() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex">
                            <h5>Aktionen</h5>
                        </div>
                        <div class="">
                            @if($order->state->equals(\App\States\Order\Accepted::class))
                                <a href="{{ route('orders.manage.pdf', $order) }}" class="btn btn-warning me-3" target="_blank">Auftragsliste PDF</a> &nbsp;
                            @endif

                            @if($order->state->equals(\App\States\Order\Delivered::class))
                                <a href="{{ route('orders.manage.pdf', $order) }}" class="btn btn-warning me-3" target="_blank">Retourenliste PDF</a> &nbsp;
                            @endif

                            @if($order->state->canTransitionTo('stored'))
                                <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="to" value="stored" />
                                    <button type="submit" class="btn btn-outline-danger">Ablehnen (zu Entwurf)</button>
                                </form>
                            @endif

                            @if($order->state->canTransitionTo('accepted'))
                                @if($order->state->equals(\App\States\Order\Committed::class))
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="accepted">
                                        <button type="submit" class="btn btn-primary">Akzeptieren</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="accepted">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Ausgeben)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('committed'))
                                @if($order->state->equals(\App\States\Order\Stored::class))
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="committed">
                                        <button type="submit" class="btn btn-primary">Bestellen</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="committed">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Akzeptieren)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('delivered'))
                                @if($order->state->equals(\App\States\Order\Accepted::class))
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="delivered">
                                        <button type="submit" class="btn btn-info">Ausgeben</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="delivered">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Zurückerhalten)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('returned'))
                                <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                    @csrf
                                    <input type="hidden" name="to" value="returned">
                                    <button type="submit" class="btn btn-secondary">Zurückerhalten</button>
                                </form>
                            @endif

                            @if($order->state->canTransitionTo('archived'))
                                <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline me-3 transition-form">
                                    @csrf
                                    <input type="hidden" name="to" value="archived">
                                    <button type="submit" class="btn btn-danger">Archivieren/Stornieren</button>
                                </form>
                            @endif
                        </div>

                        <hr />

                        <div class="row">
                            <div class="col-md-6">
                                <h5>Details</h5>
                                <p>
                                    <b>Einheit:</b> {{ $order->role->name }} <br />
                                    <b>Bestellt von:</b> {{ $order->user->fullName() }} <br />
                                    <b>Von:</b> {{ $order->role->name }} <br />
                                </p>
                            </div>
                        </div>

                        <hr />

                        <h5>Details & Material bearbeiten</h5>
                        <form method="POST" action="{{ route('orders.manage.update', $order->id) }}" id="orderUpdateForm">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="event" class="form-label">Anlass</label>
                                    <input type="text" class="form-control" id="event" name="event" value="{{ old('event', $order->event) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="event_start" class="form-label">Start</label>
                                    <input type="date" class="form-control" id="event_start" name="event_start" value="{{ old('event_start', \Carbon\Carbon::parse($order->event_start)->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="event_end" class="form-label">Ende</label>
                                    <input type="date" class="form-control" id="event_end" name="event_end" value="{{ old('event_end', \Carbon\Carbon::parse($order->event_end)->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <input type="hidden" name="role_id" value="{{ $order->role_id }}">
                            <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                            <input type="hidden" name="action" value="save"> {{-- Dummy for validator if still required --}}

                            @include('components.material-grid', ['items' => $items, 'order' => $order])

                            <div class="d-flex justify-content-between mt-4">
                                <a href="{{ route('orders.manage') }}" class="btn btn-secondary">Zurück zur Übersicht</a>
                                <button type="submit" class="btn btn-primary">Änderungen speichern</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('orderUpdateForm');
            let isDirty = false;

            if(form) {
                form.addEventListener('input', function () {
                    isDirty = true;
                });

                form.addEventListener('submit', function () {
                    isDirty = false;
                });
            }

            const transitionForms = document.querySelectorAll('.transition-form');
            transitionForms.forEach(tForm => {
                tForm.addEventListener('submit', function (e) {
                    if (isDirty) {
                        if (!confirm('Sie haben ungespeicherte Änderungen am Material oder den Bestelldetails. Möchten Sie den Status trotzdem ändern, ohne zu speichern?')) {
                            e.preventDefault();
                        }
                    }
                })
            })
        })
    </script>
@endsection
