@csrf

<div class="mb-3">
    <label for="name" class="form-label">Material</label>
    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name ?? '') }}" required autofocus>

    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="item_group_id" class="form-label">Materialgruppe</label>
    <select class="form-control" id="item_group_id" name="item_group_id" required>
        <option value="">Bitte wählen</option>
        @foreach($itemGroups as $group)
            <option value="{{ $group->id }}" {{ old('item_group_id', $item->item_group_id ?? '') == $group->id ? 'selected' : '' }}>
                {{ $group->name }}
            </option>
        @endforeach
    </select>
</div>
<div class="mb-3">
    <label for="stock" class="form-label">Bestand</label>
    <input type="number" class="form-control" id="stock" name="stock" value="{{ old('stock', $item->stock ?? 0) }}" required>

    @error('stock')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="lot" class="form-label">Packungsgrösse</label>
    <input type="number" class="form-control" id="lot" name="lot" value="{{ old('lot', $item->lot ?? 1) }}" min="1" required>

    @error('lot')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="repair" class="form-label">Reparatur</label>
    <input type="number" class="form-control" id="repair" name="repair"  value="{{ old('repair', $item->repair ?? 0) }}" required>

    @error('repair')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="files" class="form-label">Dateien hinzufügen</label>
    <input type="file" class="form-control" id="files" name="files[]" multiple>
    <div class="form-text text-muted">Maximale Dateigröße: {{ ini_get('upload_max_filesize') }}. Erlaubte Formate: Bilder (JPG, PNG, GIF, WEBP, SVG) und PDF.</div>
    @error('files')
        <div class="text-danger">{{ $message }}</div>
    @enderror
    @error('files.*')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

@if(isset($item) && $item->attachments->count() > 0)
    <div class="mb-3">
        <label class="form-label">Vorhandene Dateien (zum Löschen markieren)</label>
        <div class="row">
            @foreach($item->attachments as $attachment)
                <div class="col-md-3 mb-2">
                    <div class="card h-100">
                        @if(\Illuminate\Support\Str::endsWith(strtolower($attachment->path), ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg']))
                            @php
                                try {
                                    $url = Storage::disk('s3')->temporaryUrl($attachment->path, now()->addMinutes(30));
                                } catch (\Exception $e) {
                                    $url = null;
                                    \Illuminate\Support\Facades\Log::error("S3 URL error in form for {$attachment->path}: " . $e->getMessage());
                                }
                            @endphp
                            @if($url)
                                <img src="{{ $url }}" class="card-img-top" style="height: 100px; object-fit: cover;">
                            @else
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-danger text-white" style="height: 100px;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                            @endif
                        @else
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 100px;">
                                <i class="bi bi-file-earmark" style="font-size: 2rem;"></i>
                                <span class="ms-2">{{ basename($attachment->path) }}</span>
                            </div>
                        @endif
                        <div class="card-body p-2 text-center">
                            <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}"> Löschen
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<br />

<button type="submit" class="btn btn-primary">{{ $buttonText }}</button>
