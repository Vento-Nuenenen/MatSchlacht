@csrf

<div class="card mb-4 shadow-sm">
    <div class="card-header fw-bold bg-light">{{ __('Gruppendetails') }}</div>
    <div class="card-body">
        <div class="mb-3">
            <label for="name" class="form-label fw-semibold">{{ __('Bezeichnung') }}</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $itemGroup->name ?? '') }}" required autofocus>

        </div>

        <input type="hidden" name="position" value="{{ $itemGroup->position ?? 0 }}" />
    </div>
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('item-groups.index') }}" class="btn btn-secondary order-2 order-sm-1">{{ __('Abbrechen') }}</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>
