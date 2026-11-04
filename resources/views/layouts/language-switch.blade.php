{{-- The language of the account, only shown once the app offers several languages --}}
@if(count(config('app.available_locales')) > 1)
    <form method="POST" action="{{ route('profile.locale.update') }}" class="px-3 py-1">
        @csrf
        @method('PUT')
        <label class="small text-body-secondary mb-1 d-block">
            {{ __('Sprache') }}
            <select name="locale" class="form-select form-select-sm mt-1" onchange="this.form.requestSubmit()">
                @foreach(config('app.available_locales') as $code => $name)
                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
    </form>
@endif
