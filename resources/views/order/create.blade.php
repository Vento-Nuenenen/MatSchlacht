@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <h2 class="mb-3">Bestellen</h2>

        <form method="POST" action="{{ route('orders.store') }}">
            @csrf

            <div class="card mb-4 shadow-sm">
                <div class="card-header fw-bold bg-light">Bestelldetails</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6 col-lg-4">
                            <label for="event" class="form-label fw-semibold">Anlass</label>
                            <input type="text" class="form-control" id="event" name="event" value="{{ old('event', $order->event ?? '') }}" required>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label for="role_id" class="form-label fw-semibold">Einheit</label>
                            <select id="role_id" name="role_id" class="form-select" required>
                                <option value="">Bitte wählen</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-3 col-lg-2">
                            <label for="user_id" class="form-label fw-semibold">Bestellende Person</label>
                            <select id="user_id" name="user_id" class="form-select" required>
                                <option value="">Bitte wählen</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->scoutname . " / " ?? '' }} {{ $user->firstname }} {{ $user->lastname }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                            <label for="event_start" class="form-label fw-semibold">Von</label>
                            <input type="date" class="form-control" id="event_start" name="event_start" value="{{ old('event_start') }}" required>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-2">
                            <label for="event_end" class="form-label fw-semibold">Bis</label>
                            <input type="date" class="form-control" id="event_end" name="event_end" value="{{ old('event_end') }}" required>
                        </div>
                    </div>
                </div>
            </div>

            @include('components.collection-picker', ['collections' => $collections])

            @include('components.material-grid', ['items' => $items, 'order' => null])

            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4 mb-5">
                <a href="{{ route('orders.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
                <div class="d-flex flex-column flex-sm-row gap-2 order-1 order-sm-2">
                    <button type="submit" class="btn btn-success" name="action" value="save">Bestellung speichern</button>
                    <button type="submit" class="btn btn-primary" name="action" value="order">Bestellung senden</button>
                </div>
            </div>
        </form>
    </div>
@endsection
