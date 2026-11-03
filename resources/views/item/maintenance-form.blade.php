{{-- Fields of a maintenance entry; expects optionally $maintenanceEntry (when editing) --}}
@php($maintenanceEntry ??= null)
@csrf
<div class="row g-2">
    <div class="col-6 col-md-3">
        <label for="performed_on" class="form-label small fw-semibold">Datum</label>
        <input type="date" id="performed_on" name="performed_on" class="form-control" required max="{{ now()->format('Y-m-d') }}"
               value="{{ old('performed_on', $maintenanceEntry?->performed_on->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="maintenance_type" class="form-label small fw-semibold">Art</label>
        <select id="maintenance_type" name="type" class="form-select" required>
            @foreach(\App\Enums\MaintenanceType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', $maintenanceEntry?->type->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-md-2">
        <label for="maintenance_quantity" class="form-label small fw-semibold">Menge</label>
        <input type="number" id="maintenance_quantity" name="quantity" class="form-control" min="1" max="999999" placeholder="alle"
               value="{{ old('quantity', $maintenanceEntry?->quantity) }}">
    </div>
    <div class="col-6 col-md-4">
        <label for="performed_by" class="form-label small fw-semibold">Durchgeführt von</label>
        <input type="text" id="performed_by" name="performed_by" class="form-control" maxlength="255"
               value="{{ old('performed_by', $maintenanceEntry?->performed_by) }}" placeholder="{{ $maintenanceEntry?->user?->fullName() ?? auth()->user()->fullName() }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="cost" class="form-label small fw-semibold">Kosten (CHF)</label>
        <input type="number" id="cost" name="cost" class="form-control" min="0" step="0.05"
               value="{{ old('cost', $maintenanceEntry?->cost_cents !== null ? number_format($maintenanceEntry->cost_cents / 100, 2, '.', '') : null) }}">
    </div>
    <div class="col-12 col-md-9">
        <label for="maintenance_description" class="form-label small fw-semibold">Beschreibung</label>
        <textarea id="maintenance_description" name="description" class="form-control" rows="2" maxlength="2000">{{ old('description', $maintenanceEntry?->description) }}</textarea>
    </div>
    <div class="col-12">
        @include('components.attachment-upload', ['id' => 'maintenance_files', 'label' => 'Fotos / Belege'])
        @if($maintenanceEntry)
            @include('components.attachment-list', ['attachments' => $maintenanceEntry->attachments, 'removable' => true])
        @endif
    </div>
</div>
