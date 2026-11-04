{{-- Light / dark / automatic colour mode, handled by resources/js/ui/theme.js --}}
<div class="theme-switch btn-group w-100" role="group" aria-label="{{ __('Farbmodus') }}">
    <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-value="light" title="{{ __('Hell') }}"><i class="fa fa-sun"></i><span class="ms-1">{{ __('Hell') }}</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-value="dark" title="{{ __('Dunkel') }}"><i class="fa fa-moon"></i><span class="ms-1">{{ __('Dunkel') }}</span></button>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-value="auto" title="{{ __('Wie Gerät') }}"><i class="fa fa-circle-half-stroke"></i><span class="ms-1">{{ __('Auto') }}</span></button>
</div>
