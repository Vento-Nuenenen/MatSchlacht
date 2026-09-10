@extends('layouts.app')

@section('content')
    <div class="container-fluid px-3 px-sm-4 px-xxl-5 app-container">
        <div class="row justify-content-center">
            <div class="col-12">
                @include('components.status-alerts')

                <div class="card mb-4 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="h5 mb-0">Bestellung verwalten: <strong>{{ $order->event }}</strong></span>
                        <span class="badge bg-{{ $order->state->color() }} fs-6">{{ $order->state->display() }}</span>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted text-uppercase small fw-bold mb-2">Aktionen</h6>
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <a href="{{ route('orders.manage.contents-pdf', $order) }}" class="btn btn-info text-white"
                               target="_blank">
                                <i class="fa fa-file-pdf me-1"></i> Packliste PDF
                            </a>

                            @if($order->state->equals(\App\States\Order\Accepted::class))
                                <a href="{{ route('orders.manage.order-pdf', $order) }}" class="btn btn-warning" target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i> Auftragsliste PDF
                                </a>
                            @endif

                            @if($order->state->equals(\App\States\Order\Delivered::class))
                                <a href="{{ route('orders.manage.return-pdf', $order) }}" class="btn btn-warning" target="_blank">
                                    <i class="fa fa-file-pdf me-1"></i> Retourenliste PDF
                                </a>
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
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="accepted">
                                        <button type="submit" class="btn btn-primary">Akzeptieren</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="accepted">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Ausgeben)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('committed'))
                                @if($order->state->equals(\App\States\Order\Stored::class))
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="committed">
                                        <button type="submit" class="btn btn-primary">Bestellen</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="committed">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Akzeptieren)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('delivered'))
                                @if($order->state->equals(\App\States\Order\Accepted::class))
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="delivered">
                                        <button type="submit" class="btn btn-info text-white">Ausgeben</button>
                                    </form>
                                @else
                                    <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                        @csrf
                                        <input type="hidden" name="to" value="delivered">
                                        <button type="submit" class="btn btn-outline-danger">Rückgängig (Zurückerhalten)</button>
                                    </form>
                                @endif
                            @endif

                            @if($order->state->canTransitionTo('returned'))
                                <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                    @csrf
                                    <input type="hidden" name="to" value="returned">
                                    <button type="submit" class="btn btn-secondary">Zurückerhalten</button>
                                </form>
                            @endif

                            @if($order->state->canTransitionTo('archived'))
                                <form action="{{ route('orders.manage.transition', $order) }}" method="POST" class="d-inline transition-form">
                                    @csrf
                                    <input type="hidden" name="to" value="archived">
                                    <button type="submit" class="btn btn-danger">Archivieren/Stornieren</button>
                                </form>
                            @endif
                        </div>

                        <hr />

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <h6 class="text-muted text-uppercase small fw-bold">Details</h6>
                                <p class="mb-0">
                                    <b>Einheit:</b> {{ $order->role->name }} <br />
                                    <b>Bestellt von:</b> {{ $order->user->fullName() }} <br />
                                    <b>Von:</b> {{ \Carbon\Carbon::parse($order->event_start)->format('d.m.Y') }} <br />
                                    <b>Bis:</b> {{ \Carbon\Carbon::parse($order->event_end)->format('d.m.Y') }} <br />
                                </p>
                            </div>
                        </div>

                        <hr />

                        <h5 class="mb-3">Details & Material bearbeiten</h5>
                        <form method="POST" action="{{ route('orders.manage.update', $order->id) }}" id="orderUpdateForm">
                            @csrf
                            @method('PUT')

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <label for="event" class="form-label fw-semibold">Anlass</label>
                                    <input type="text" class="form-control" id="event" name="event" value="{{ old('event', $order->event) }}" required>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label for="event_start" class="form-label fw-semibold">Start</label>
                                    <input type="date" class="form-control" id="event_start" name="event_start" value="{{ old('event_start', \Carbon\Carbon::parse($order->event_start)->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <label for="event_end" class="form-label fw-semibold">Ende</label>
                                    <input type="date" class="form-control" id="event_end" name="event_end" value="{{ old('event_end', \Carbon\Carbon::parse($order->event_end)->format('Y-m-d')) }}" required>
                                </div>
                            </div>

                            <input type="hidden" name="role_id" value="{{ $order->role_id }}">
                            <input type="hidden" name="user_id" value="{{ $order->user_id }}">
                            <input type="hidden" name="action" value="save"> {{-- Dummy for validator if still required --}}

                            <div id="addedCollections" class="mb-3">
                                @if($order->itemCollections->isNotEmpty())
                                    <div class="mb-2"><strong>Verknüpfte Kollektionen:</strong></div>
                                    <div class="d-flex flex-wrap gap-2 mb-3" id="collections-wrapper">
                                        @foreach($order->itemCollections as $index => $collection)
                                            @php $uniqueId = 'existing-' . $index; @endphp
                                            <span class="badge bg-info text-dark p-2" id="collection-badge-{{ $uniqueId }}" data-items="{{ json_encode($collection->items->map(fn($item) => ['id' => $item->id, 'quantity' => ($item->pivot->quantity / ($item->lot > 1 ? $item->lot : 1))])) }}">
                                                {{ $collection->name }}
                                                <input type="hidden" name="collections[]" value="{{ $collection->id }}">
                                                <button type="button" class="btn-close btn-close-white" style="font-size: 0.5rem" onclick="removeCollection('{{ $uniqueId }}')"></button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @include('components.material-grid', ['items' => $items, 'order' => $order])

                            @if($collections->isNotEmpty())
                                <div class="card mt-4 shadow-sm">
                                    <div class="card-header fw-bold bg-light">Kollektionen hinzufügen</div>
                                    <div class="card-body">
                                        <div class="row align-items-end g-3">
                                            <div class="col-12 col-md-8 col-lg-9">
                                                <label for="collectionSelect" class="form-label fw-semibold">Kollektion auswählen</label>
                                                <select id="collectionSelect" class="form-select">
                                                    <option value="">-- Kollektion wählen --</option>
                                                    @foreach($collections as $collection)
                                                        <option value="{{ $collection->id }}" data-items="{{ json_encode($collection->items->map(fn($item) => ['id' => $item->id, 'quantity' => ($item->pivot->quantity / ($item->lot > 1 ? $item->lot : 1))])) }}">
                                                            {{ $collection->name }} ({{ $collection->items_count }} Artikel)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-4 col-lg-3">
                                                <button type="button" class="btn btn-outline-primary w-100" onclick="addSelectedCollection()">
                                                    <i class="fa fa-plus me-1"></i> Hinzufügen
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
                                <a href="{{ route('orders.manage') }}" class="btn btn-secondary order-2 order-sm-1">Zurück zur Übersicht</a>
                                <button type="submit" class="btn btn-primary order-1 order-sm-2">Änderungen speichern</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize minQty for existing collections
            const badges = document.querySelectorAll('[id^="collection-badge-existing-"]');
            badges.forEach(badge => {
                const items = JSON.parse(badge.dataset.items || '[]');
                items.forEach(item => {
                    const input = document.querySelector(`input[name="items[${item.id}]"]`);
                    if (input) {
                        const minQty = (parseInt(input.dataset.minQty) || 0) + item.quantity;
                        input.dataset.minQty = minQty;
                    }
                });
            });
        });

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

            // Mark form as dirty
            const form = document.getElementById('orderUpdateForm');
            if (form) {
                form.dispatchEvent(new Event('input'));
            }
        }

        function trackCollection(id, name, items) {
            const container = document.getElementById('addedCollections');

            // Check if we need to add the header
            if (container.children.length === 0) {
                const header = document.createElement('div');
                header.className = 'mb-2';
                header.innerHTML = '<strong>Verknüpfte Kollektionen:</strong>';
                container.appendChild(header);

                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex flex-wrap gap-2 mb-3';
                wrapper.id = 'collections-wrapper';
                container.appendChild(wrapper);
            }

            let wrapper = document.getElementById('collections-wrapper');
            if (!wrapper) {
                // If it was already there from server but we added more
                wrapper = container.querySelector('.d-flex.flex-wrap');
                if (!wrapper) {
                     wrapper = document.createElement('div');
                     wrapper.className = 'd-flex flex-wrap gap-2 mb-3';
                     wrapper.id = 'collections-wrapper';
                     container.appendChild(wrapper);
                }
            }

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

                    // Trigger highlight
                    const row = input.closest('.item-row');
                    if (row) {
                        row.classList.add('table-primary');
                        row.style.display = ''; // Ensure it's visible if it was filtered
                    }
                }
            });
        }

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
