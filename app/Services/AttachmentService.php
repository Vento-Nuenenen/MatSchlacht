<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Stores and removes files attached to items, procurements and maintenance entries on the S3 disk.
 * Does nothing while the attachments component is disabled.
 */
class AttachmentService
{
    public const DISK = 's3';

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
     * A temporary download link, null if the disk cannot create one.
     */
    public function temporaryUrl(Attachment $attachment): ?string
    {
        try {
            return Storage::disk(self::DISK)->temporaryUrl($attachment->path, now()->addMinutes(30));
        } catch (Throwable $exception) {
            Log::error("S3 URL error for {$attachment->path}: ".$exception->getMessage());

            return null;
        }
    }

    protected function delete(Attachment $attachment): void
    {
        Storage::disk(self::DISK)->delete($attachment->path);
        $attachment->delete();
    }
}
