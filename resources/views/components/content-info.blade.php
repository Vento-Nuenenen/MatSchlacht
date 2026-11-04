{{-- Opens the content dialog; expects $title and $lines ([['label' => …, 'text' => …], …]), shown as plain text --}}
@component_enabled('item_contents')
    @if($lines !== [])
        <i class="fa fa-info-circle text-info ms-1 cursor-pointer p-1"
           data-bs-toggle="modal"
           data-bs-target="#contentModal"
           data-content-title="{{ $title }}"
           data-content-lines="{{ json_encode($lines) }}"></i>
    @endif
@endcomponent_enabled
