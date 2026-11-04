{{-- Messages of the app. Errors stay until they are closed, see resources/js/ui/messages.js --}}
<div class="app-toasts" id="app-toasts">
    @foreach(['status' => 'success', 'success' => 'success', 'error' => 'danger'] as $key => $type)
        @if(session($key))
            @include('components.toast', ['type' => $type, 'message' => session($key)])
        @endif
    @endforeach
</div>

<template id="app-toast-template">
    @include('components.toast', ['type' => 'danger', 'message' => ''])
</template>

{{-- Input errors, shown at their fields by resources/js/ui/messages.js --}}
@if($errors->any())
    <script type="application/json" id="form-errors" data-form="{{ old('_form') }}">@json($errors->getMessages())</script>
@endif
