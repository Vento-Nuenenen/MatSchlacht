<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['path', 'attachable_id', 'attachable_type'])]
class Attachment extends Model
{
    /**
     * The link to the file. Files are always served through the app, so that every provider
     * works the same way and the permissions of the attached record are checked.
     */
    public function url(): string
    {
        return route('attachments.show', $this);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
