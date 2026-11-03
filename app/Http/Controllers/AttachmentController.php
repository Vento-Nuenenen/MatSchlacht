<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Serve an attached file from the configured file storage.
     */
    public function show(Attachment $attachment, AttachmentService $attachments): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return $attachments->response($attachment);
    }
}
