<?php

use App\Models\Attachment;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non-privileged user cannot trigger test emails', function () {
    Mail::fake();

    $user = User::factory()->create();
    $user->assignRole('Abteilungsleitung');

    $response = $this->actingAs($user)->get(route('test-mail', ['email' => 'victim@example.com']));

    $response->assertForbidden();
    Mail::assertNothingSent();
});

test('privileged user can trigger test emails', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('Materialwart');

    $response = $this->actingAs($admin)->get(route('test-mail', ['email' => 'test@example.com']));

    $response->assertOk();
});

test('user editing item A cannot delete attachments belonging to item B (IDOR prevention)', function () {
    Storage::fake('s3');

    $group = ItemGroup::factory()->create();

    $itemA = Item::factory()->create(['item_group_id' => $group->id, 'lot' => 1]);
    $itemB = Item::factory()->create(['item_group_id' => $group->id, 'lot' => 1]);

    $attachmentB = $itemB->attachments()->create([
        'path' => 'items/test-file-b.jpg',
    ]);
    Storage::disk('s3')->put('items/test-file-b.jpg', 'content');

    $user = User::factory()->create();
    $user->assignRole('Materialwart');

    // Attempt to update itemA while passing attachment of itemB in remove_attachments
    $response = $this->actingAs($user)->put(route('items.update', $itemA), [
        'name' => $itemA->name,
        'stock' => $itemA->stock,
        'lot' => $itemA->lot,
        'repair' => 0,
        'item_group_id' => $group->id,
        'remove_attachments' => [$attachmentB->id],
    ]);

    $response->assertRedirect(route('items.index'));

    // Attachment of Item B must NOT be deleted
    expect(Attachment::find($attachmentB->id))->not->toBeNull();
    Storage::disk('s3')->assertExists('items/test-file-b.jpg');
});
