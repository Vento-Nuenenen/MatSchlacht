{{-- Links to attached files; expects $attachments, optional $removable to show delete checkboxes --}}
@php($attachmentService = app(\App\Services\AttachmentService::class))
@if($attachmentService->isEnabled() && $attachments->isNotEmpty())
    <div class="d-flex flex-wrap gap-2 {{ ($removable ?? false) ? 'mt-1' : '' }}">
        @foreach($attachments as $attachment)
            @php($url = $attachmentService->url($attachment))
            @php($isPdf = \Illuminate\Support\Str::endsWith(strtolower($attachment->path), '.pdf'))
            <span class="d-inline-flex align-items-center gap-1 border rounded px-2 py-1 bg-white small">
                <i class="fa {{ $isPdf ? 'fa-file-pdf text-danger' : 'fa-file-image text-primary' }}"></i>
                @if($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="text-decoration-none text-truncate" style="max-width: 160px;" title="{{ basename($attachment->path) }}">{{ basename($attachment->path) }}</a>
                @else
                    <span class="text-truncate" style="max-width: 160px;" title="{{ basename($attachment->path) }}">{{ basename($attachment->path) }}</span>
                @endif
                @if($removable ?? false)
                    <span class="form-check mb-0 ms-1">
                        <input class="form-check-input" type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" id="remove-attachment-{{ $attachment->id }}">
                        <label class="form-check-label text-danger" for="remove-attachment-{{ $attachment->id }}">löschen</label>
                    </span>
                @endif
            </span>
        @endforeach
    </div>
@endif
