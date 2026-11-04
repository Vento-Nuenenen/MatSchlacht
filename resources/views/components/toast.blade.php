<div class="toast app-toast app-toast-{{ $type }}" role="{{ $type === 'danger' ? 'alert' : 'status' }}" aria-live="{{ $type === 'danger' ? 'assertive' : 'polite' }}" aria-atomic="true">
    <div class="d-flex align-items-start gap-2">
        <i class="fa {{ $type === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' }} app-toast-icon"></i>
        <div class="toast-body p-0 flex-grow-1">{{ $message }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="{{ __('Schliessen') }}"></button>
    </div>
</div>
