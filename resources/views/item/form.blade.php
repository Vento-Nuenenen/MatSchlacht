@csrf

<div id="form-fields">
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold bg-light">Basisdaten</div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-8">
                    <label for="name" class="form-label fw-semibold">Material</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $item->name ?? '') }}" required autofocus>
                    @error('name')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-4">
                    <label for="item_group_id" class="form-label fw-semibold">Materialgruppe</label>
                    <select class="form-select" id="item_group_id" name="item_group_id" required>
                        <option value="">Bitte wählen</option>
                        @foreach($itemGroups as $group)
                            <option value="{{ $group->id }}" {{ old('item_group_id', $item->item_group_id ?? '') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-4">
                    <label for="stock" class="form-label fw-semibold">Bestand</label>
                    @php
                        $isStockFromStorage = isset($item) && $item->exists
                            && app(\App\Services\ComponentManager::class)->isEnabled('storage')
                            && $item->stocks->isNotEmpty();
                    @endphp
                    <input type="number" class="form-control" id="stock" name="stock" value="{{ old('stock', $item->stock ?? 0) }}" min="0" max="999999" required @readonly($isStockFromStorage)>
                    @if($isStockFromStorage)
                        <div class="form-text">Summe der Lagerplätze</div>
                    @endif
                    @error('stock')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm-4">
                    <label for="lot" class="form-label fw-semibold">Packungsgrösse</label>
                    <input type="number" class="form-control" id="lot" name="lot" value="{{ old('lot', $item->lot ?? 1) }}" min="1" max="999999" required>
                    @error('lot')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-sm-4">
                    <label for="repair" class="form-label fw-semibold">Reparatur</label>
                    <input type="number" class="form-control" id="repair" name="repair" value="{{ old('repair', $item->repair ?? 0) }}" min="0" max="999999" required>
                    @error('repair')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    @component_enabled('attachments')
    <div class="card mb-4 shadow-sm">
        <div class="card-header fw-bold bg-light">Dateianhänge</div>
        <div class="card-body">
            <div class="mb-3">
                <label for="files" class="form-label fw-semibold">Dateien hinzufügen</label>
                <input type="file" class="form-control" id="files" name="files[]" multiple>
                <div class="form-text text-muted">Maximale Dateigröße: {{ ini_get('upload_max_filesize') }}. Erlaubte Formate: Bilder (JPG, PNG, GIF, WEBP, SVG) und PDF.</div>
                @error('files')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
                @error('files.*')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            @if(isset($item) && $item->attachments->count() > 0)
                <div class="mt-4">
                    <label class="form-label fw-semibold">Vorhandene Dateien (zum Löschen markieren)</label>
                    <div class="row g-2">
                        @foreach($item->attachments as $attachment)
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <div class="card h-100 shadow-sm">
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
                                        <div class="card-img-top d-flex flex-column align-items-center justify-content-center bg-light p-2 text-center" style="height: 100px;">
                                            <i class="fa fa-file-pdf text-danger" style="font-size: 2rem;"></i>
                                            <span class="small text-truncate w-100 mt-1" title="{{ basename($attachment->path) }}">{{ basename($attachment->path) }}</span>
                                        </div>
                                    @endif
                                    <div class="card-body p-2 text-center bg-light">
                                        <div class="form-check d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" id="del-att-{{ $attachment->id }}">
                                            <label class="form-check-label small text-danger fw-bold" for="del-att-{{ $attachment->id }}">Löschen</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endcomponent_enabled
</div>

<div class="d-flex flex-column flex-sm-row justify-content-between gap-2 mt-4">
    <a href="{{ route('items.index') }}" class="btn btn-secondary order-2 order-sm-1">Abbrechen</a>
    <button type="submit" class="btn btn-primary order-1 order-sm-2">{{ $buttonText }}</button>
</div>

