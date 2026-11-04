{{-- Fields of a procurement; expects $item and optionally $procurement (when editing) --}}
@php
    $procurement ??= null;
    $isStoredInBins = app(\App\Services\ComponentManager::class)->isEnabled('storage') && $item->stocks->isNotEmpty();
@endphp
@csrf
<div class="row g-2">
    <div class="col-6 col-md-3">
        <label for="procured_on" class="form-label small fw-semibold">{{ __('Datum') }}</label>
        <input type="date" id="procured_on" name="procured_on" class="form-control" required max="{{ now()->format('Y-m-d') }}"
               value="{{ old('procured_on', $procurement?->procured_on->format('Y-m-d') ?? now()->format('Y-m-d')) }}">
    </div>
    <div class="col-6 col-md-2">
        <label for="procurement_quantity" class="form-label small fw-semibold">{{ __('Menge') }}</label>
        <input type="number" id="procurement_quantity" name="quantity" class="form-control" min="1" max="999999" required
               value="{{ old('quantity', $procurement?->quantity) }}" @readonly($isStoredInBins && $procurement?->added_to_stock)>
    </div>
    <div class="col-12 col-md-4">
        <label for="supplier" class="form-label small fw-semibold">{{ __('Lieferant') }}</label>
        <input type="text" id="supplier" name="supplier" class="form-control" maxlength="255" value="{{ old('supplier', $procurement?->supplier) }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="unit_price" class="form-label small fw-semibold">{{ __('Stückpreis (CHF)') }}</label>
        <input type="number" id="unit_price" name="unit_price" class="form-control" min="0" step="0.05"
               value="{{ old('unit_price', $procurement?->unit_price_cents !== null ? number_format($procurement->unit_price_cents / 100, 2, '.', '') : null) }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="invoice_number" class="form-label small fw-semibold">{{ __('Beleg-Nr.') }}</label>
        <input type="text" id="invoice_number" name="invoice_number" class="form-control" maxlength="255" value="{{ old('invoice_number', $procurement?->invoice_number) }}">
    </div>
    <div class="col-6 col-md-3">
        <label for="warranty_until" class="form-label small fw-semibold">{{ __('Garantie bis') }}</label>
        <input type="date" id="warranty_until" name="warranty_until" class="form-control" value="{{ old('warranty_until', $procurement?->warranty_until?->format('Y-m-d')) }}">
    </div>
    <div class="col-12 col-md-6">
        <label for="procurement_notes" class="form-label small fw-semibold">{{ __('Notiz') }}</label>
        <input type="text" id="procurement_notes" name="notes" class="form-control" maxlength="2000" value="{{ old('notes', $procurement?->notes) }}">
    </div>
    <div class="col-12 col-md-6">
        @include('components.attachment-upload', ['id' => 'procurement_files', 'label' => __('Belege (Rechnung, Garantieschein, …)')])
        @if($procurement)
            @include('components.attachment-list', ['attachments' => $procurement->attachments, 'removable' => true])
        @endif
    </div>
    <div class="col-12 col-md-6 d-flex align-items-end">
        <div class="form-check">
            {{-- A disabled checkbox is not submitted, so the hidden field keeps the current state --}}
            <input type="hidden" name="add_to_stock" value="{{ $isStoredInBins && $procurement?->added_to_stock ? 1 : 0 }}">
            <input class="form-check-input" type="checkbox" name="add_to_stock" value="1" id="add_to_stock"
                   @disabled($isStoredInBins) @checked(old('add_to_stock', $procurement?->added_to_stock))>
            <label class="form-check-label" for="add_to_stock">
                {{ __('Menge zum Bestand addieren') }}
                @if($isStoredInBins)
                    <span class="text-muted small">{{ __('(gesperrt: Bestand ergibt sich aus den Lagerplätzen)') }}</span>
                @endif
            </label>
        </div>
    </div>
</div>
