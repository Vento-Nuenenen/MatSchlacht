{{-- File input for receipts and photos; only shown while the attachments component is enabled --}}
@component_enabled('attachments')
    <label for="{{ $id ?? 'files' }}" class="form-label small fw-semibold">{{ $label ?? 'Belege / Fotos' }}</label>
    <input type="file" class="form-control" id="{{ $id ?? 'files' }}" name="files[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.svg,.pdf">
    <div class="form-text">{{ __('Bilder oder PDF, max. :ini_get pro Datei.', ['ini_get' => ini_get('upload_max_filesize')]) }}</div>
@endcomponent_enabled
