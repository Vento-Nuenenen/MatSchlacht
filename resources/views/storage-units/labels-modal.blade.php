{{-- Print dialog for labels of a storage unit; expects $storageUnit --}}
<div class="modal fade" id="labelModal" tabindex="-1" aria-labelledby="labelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="GET" action="{{ route('labels.storage-unit', $storageUnit) }}" target="_blank">
            <div class="modal-header">
                <h5 class="modal-title" id="labelModalLabel">{{ __('Etiketten drucken') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">{{ __('A4-Bogen mit 3 × 8 Etiketten à 70 × 37 mm (z. B. Herma 4453, Avery 3475).') }}</p>
                @unless($storageUnit->level->holdsStock())
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="with_children" value="1" id="label-with-children" checked>
                        <label class="form-check-label" for="label-with-children">{{ __('Alle enthaltenen Lagereinheiten') }}</label>
                    </div>
                @endunless
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="with_items" value="1" id="label-with-items">
                    <label class="form-check-label" for="label-with-items">{{ __('Etiketten für das eingelagerte Material') }}</label>
                </div>
                <label for="label-skip" class="form-label fw-semibold">{{ __('Bereits verwendete Etiketten auf dem ersten Bogen') }}</label>
                <input type="number" class="form-control" id="label-skip" name="skip" value="0" min="0" max="{{ \App\Services\Pdf\LabelSheetPdf::LABELS_PER_SHEET - 1 }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Abbrechen') }}</button>
                <button type="submit" class="btn btn-primary"><i class="fa fa-print me-1"></i> {{ __('PDF erstellen') }}</button>
            </div>
        </form>
    </div>
</div>
