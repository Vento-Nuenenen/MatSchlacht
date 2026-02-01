@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Bestellung anpassen</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderUpdateForm">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="event" class="form-label">Anlass</label>
                    <input type="text" class="form-control" id="event" name="event" value="{{ old('event', $order->event) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="role_id" class="form-label">Einheit</label>
                    <select id="role_id" name="role_id" class="form-select" required>
                        <option value="">Bitte wählen</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id', $order->role_id) == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Bestellende Person</label>
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="">Bitte wählen</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id', $order->user_id) == $user->id)>{{ $user->scoutname . " / " ?? '' }} {{ $user->firstname }} {{ $user->lastname }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="event_start" class="form-label">Verwendungszeitraum von</label>
                    <input type="date" class="form-control" id="event_start" name="event_start" value="{{ old('event_start', \Carbon\Carbon::parse($order->event_start)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="event_end" class="form-label">Verwendungszeitraum bis</label>
                    <input type="date" class="form-control" id="event_end" name="event_end" value="{{ old('event_end', \Carbon\Carbon::parse($order->event_end)->format('Y-m-d')) }}" required>
                </div>
            </div>

            <hr class="my-4" />

            @include('components.material-grid', ['items' => $items, 'order' => $order])

            <div class="d-flex justify-content-between">
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Abbrechen</a>

                <button type="submit" class="btn btn-success" name="action" value="save">Bestellung speichern</button>
                <button type="submit" class="btn btn-primary" name="action" value="order" id="submitOrderButton">Bestellung senden</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('orderUpdateForm');
            let isDirty = false;

            if (form) {
                form.addEventListener('input', function() {
                    isDirty = true;
                });

                form.addEventListener('submit', function() {
                    isDirty = false;
                });
            }

            const submitOrderButton = document.getElementById('submitOrderButton');
            if (submitOrderButton) {
                submitOrderButton.addEventListener('click', function(e) {
                    if (isDirty) {
                        if (!confirm('Sie haben ungespeicherte Änderungen am Material oder den Bestelldetails. Möchten Sie die Bestellung trotzdem senden? (Die Änderungen werden beim Senden automatisch mit gespeichert)')) {
                            e.preventDefault();
                        }
                    }
                });
            }
        });
    </script>
@endsection
