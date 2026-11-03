<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use App\Models\User;

class AttachmentPolicy
{
    /**
     * A file may be viewed by everyone who may see the record it is attached to.
     * Procurement receipts contain prices and are limited to users managing procurements.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        return match (true) {
            $attachment->attachable instanceof ItemProcurement => $user->can('manage procurements'),
            $attachment->attachable instanceof Item, $attachment->attachable instanceof ItemMaintenance => $user->can('view items'),
            default => false,
        };
    }
}
