<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use App\Services\FileStorage\FileStorageManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Stores, serves and removes files attached to items, procurements and maintenance entries.
 * The files are stored on the provider chosen under Einstellungen › Dateispeicher.
 * Uploads are ignored while the attachments component is disabled.
 */
class AttachmentService
{
    public const DISK = FileStorageManager::DISK;

    public function __construct(protected ComponentManager $components) {}

    public function isEnabled(): bool
    {
        return $this->components->isEnabled('attachments');
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function store(Item|ItemProcurement|ItemMaintenance $attachable, array $files, string $directory): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        foreach ($files as $file) {
            $path = $file->store($directory, self::DISK);

            if ($path) {
                $attachable->attachments()->create(['path' => $path]);
            }
        }
    }

    /**
     * Remove the given attachments of the model from the disk and the database.
     *
     * @param  array<int, int|string>  $attachmentIds
     */
    public function remove(Item|ItemProcurement|ItemMaintenance $attachable, array $attachmentIds): void
    {
        if (! $this->isEnabled() || $attachmentIds === []) {
            return;
        }

        $attachable->attachments()->whereIn('id', $attachmentIds)->get()->each(fn (Attachment $attachment) => $this->delete($attachment));
    }

    /**
     * Remove all attachments of a model that is about to be deleted, regardless of the component state.
     */
    public function removeAll(Item|ItemProcurement|ItemMaintenance $attachable): void
    {
        $attachable->attachments()->get()->each(fn (Attachment $attachment) => $this->delete($attachment));
    }

    /**
     * The link to a file. Files are always served through the app, so that every provider works
     * the same way and the permissions of the attached record are checked.
     */
    public function url(Attachment $attachment): string
    {
        return route('attachments.show', $attachment);
    }

    /**
     * The file as inline response, e.g. to show an image or open a PDF in the browser.
     */
    public function response(Attachment $attachment): StreamedResponse
    {
        $disk = Storage::disk(self::DISK);

        try {
            $exists = $disk->exists($attachment->path);
        } catch (Throwable $exception) {
            Log::error("File storage error for {$attachment->path}: ".$exception->getMessage());
            abort(503, 'Der Dateispeicher ist nicht erreichbar.');
        }

        abort_unless($exists, 404);

        return $disk->response($attachment->path, basename($attachment->path));
    }

    protected function delete(Attachment $attachment): void
    {
        Storage::disk(self::DISK)->delete($attachment->path);
        $attachment->delete();
    }
}
