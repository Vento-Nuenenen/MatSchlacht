@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Bestellen</h2>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
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
                    <input type="date" class="form-control" id="event_start" name="event_start" value="{{ old('event_start') }}" required>
                </div>
                <div class="col-md-3">
                    <label for="event_end" class="form-label">Verwendungszeitraum bis</label>
                    <input type="date" class="form-control" id="event_end" name="event_end" value="{{ old('event_end') }}" required>
                </div>
            </div>

            <hr class="my-4" />

            @if($collections->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header">Kollektionen</div>
                    <div class="card-body">
                        <div class="row align-items-end g-3">
                            <div class="col-md-8">
                                <label for="collectionSelect" class="form-label">Kollektion auswählen</label>
                                <select id="collectionSelect" class="form-select">
                                    <option value="">-- Kollektion wählen --</option>
                                    @foreach($collections as $collection)
                                        <option value="{{ $collection->id }}" data-items="{{ json_encode($collection->items->map(fn($item) => ['id' => $item->id, 'quantity' => ($item->pivot->quantity / ($item->lot > 1 ? $item->lot : 1))])) }}">
                                            {{ $collection->name }} ({{ $collection->items_count }} Artikel)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary w-100" onclick="addSelectedCollection()">
                                    Hinzufügen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @include('components.material-grid', ['items' => $items, 'order' => null])

            <div id="addedCollections" class="mb-3"></div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Abbrechen</a>
                <div>
                    <button type="submit" class="btn btn-success" name="action" value="save">Bestellung speichern</button>
                    <button type="submit" class="btn btn-primary" name="action" value="order">Bestellung senden</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function addSelectedCollection() {
            const select = document.getElementById('collectionSelect');
            const selectedOption = select.options[select.selectedIndex];

            if (!selectedOption.value) {
                alert('Bitte wählen Sie eine Kollektion aus.');
                return;
            }

            const items = JSON.parse(selectedOption.getAttribute('data-items'));
            const collectionId = selectedOption.value;
            const collectionName = selectedOption.text.split(' (')[0];

            addCollection(items);
            trackCollection(collectionId, collectionName, items);

            // Reset select
            select.selectedIndex = 0;
        }

        function trackCollection(id, name, items) {
            const container = document.getElementById('addedCollections');

            // Check if we need to add the header
            if (container.children.length === 0) {
                const header = document.createElement('div');
                header.className = 'mb-2';
                header.innerHTML = '<strong>Hinzugefügte Kollektionen:</strong>';
                container.appendChild(header);

                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex flex-wrap gap-2 mb-3';
                wrapper.id = 'collections-wrapper';
                container.appendChild(wrapper);
            }

            const wrapper = document.getElementById('collections-wrapper');
            const uniqueId = Date.now() + Math.random().toString(36).substr(2, 9);

            const span = document.createElement('span');
            span.className = 'badge bg-info text-dark p-2';
            span.id = `collection-badge-${uniqueId}`;
            span.dataset.items = JSON.stringify(items);
            span.innerHTML = `${name} <input type="hidden" name="collections[]" value="${id}"> <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem" onclick="removeCollection('${uniqueId}')"></button>`;
            wrapper.appendChild(span);
        }

        function removeCollection(uniqueId) {
            const badge = document.getElementById(`collection-badge-${uniqueId}`);
            if (badge) {
                const items = JSON.parse(badge.dataset.items || '[]');
                subtractCollection(items);

                badge.remove();

                // If no badges left, remove the header
                const container = document.getElementById('addedCollections');
                const wrapper = container.querySelector('.d-flex.flex-wrap');
                if (wrapper && wrapper.children.length === 0) {
                    container.innerHTML = '';
                }
            }
        }

        function subtractCollection(items) {
            items.forEach(item => {
                const input = document.querySelector(`input[name="items[${item.id}]"]`);
                if (input) {
                    const minQty = (parseInt(input.dataset.minQty) || 0) - item.quantity;
                    input.dataset.minQty = Math.max(0, minQty);

                    const currentVal = parseInt(input.value) || 0;
                    input.value = Math.max(0, currentVal - item.quantity);

                    // Update highlighting
                    const row = input.closest('.item-row');
                    if (row && input.value == 0) {
                        row.classList.remove('table-primary');
                    }
                }
            });
        }

        function addCollection(items) {
            items.forEach(item => {
                const input = document.querySelector(`input[name="items[${item.id}]"]`);
                if (input) {
                    const minQty = (parseInt(input.dataset.minQty) || 0) + item.quantity;
                    input.dataset.minQty = minQty;

                    const currentVal = parseInt(input.value) || 0;
                    input.value = currentVal + item.quantity;

                    // Trigger highlight or change event if needed
                    const row = input.closest('.item-row');
                    if (row) {
                        row.classList.add('table-primary');
                    }
                }
            });
        }
    </script>
@endsection
