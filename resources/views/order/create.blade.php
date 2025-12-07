@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Bestellen</h2>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('orders.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="event" class="form-label">Anlass</label>
                    <input type="text" class="form-control" id="event" name="event" value="{{ old('event', $order->event ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label for="role_id" class="form-label">Einheit</label>
                    <select id="role_id" name="role_id" class="form-select" required>
                        <option value="">Bitte wählen</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="user_id" class="form-label">Bestellende Person</label>
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="">Bitte wählen</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->scoutname . " / " ?? '' }} {{ $user->firstname }} {{ $user->lastname }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="event_start" class="form-label">Verwendungszeitraum von</label>
                    <input type="datetime-local" class="form-control" id="event_start" name="event_start" value="{{ old('event_start') }}" required>
                </div>
                <div class="col-md-3">
                    <label for="event_end" class="form-label">Verwendungszeitraum bis</label>
                    <input type="datetime-local" class="form-control" id="event_end" name="event_end" value="{{ old('event_end') }}" required>
                </div>
            </div>

            <hr class="my-4" />

            <div class="mb-3">
                <label class="form-label">Materialien</label>
                <input type="text" id="itemSearch" class="form-control" placeholder="Suchen…" oninput="filterItems()">
            </div>

            <div id="itemsContainer">
                @php
                    $grouped = $items->groupBy(fn($i) => optional($i->itemGroup)->position . '|' . optional($i->itemGroup)->name);
                @endphp

                @foreach($grouped as $groupKey => $groupItems)
                    @php [$pos,$groupName] = array_pad(explode('|',$groupKey,2),2,'Sonstiges'); @endphp
                    <div class="card mb-3 item-group">
                        <div class="card-header fw-bold">{{ $groupName }}</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th width="60%">Name</th>
                                    <th width="20%">Verfügbar</th>
                                    <th width="20%">Bestellen</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groupItems as $item)
                                    <tr class="item-row">
                                        <td class="item-name">{{ $item->name }}</td>
                                        <td>{{ $item->stock }}</td>
                                        <td>
                                            <input type="number" min="0" class="form-control form-control-sm" name="items[{{ $item->id }}]" value="{{ old('items.'.$item->id, 0) }}">
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Bestellung abschließen</button>
            </div>
        </form>
    </div>

    <script>
        function filterItems() {
            const q = (document.getElementById('itemSearch').value || '').toLowerCase();
            const groups = document.querySelectorAll('#itemsContainer .item-group');
            groups.forEach(group => {
                let visible = 0;
                group.querySelectorAll('.item-row').forEach(row => {
                    const name = row.querySelector('.item-name').textContent.toLowerCase();
                    const match = !q || name.includes(q);
                    row.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                group.style.display = visible ? '' : 'none';
            });
        }
    </script>
@endsection
