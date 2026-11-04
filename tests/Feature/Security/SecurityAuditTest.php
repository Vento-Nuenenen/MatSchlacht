<?php

use App\Mail\TestMail;
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

test('test emails cannot be triggered over the web', function () {
    Mail::fake();

    $admin = User::factory()->create();
    $admin->assignRole('Materialwart');

    $this->actingAs($admin)->get('/test-mail?email=victim@example.com')->assertNotFound();

    Mail::assertNothingSent();
});

test('user editing item A cannot delete attachments belonging to item B (IDOR prevention)', function () {
    Storage::fake('attachments');

    $group = ItemGroup::factory()->create();

    $itemA = Item::factory()->create(['item_group_id' => $group->id, 'lot' => 1]);
    $itemB = Item::factory()->create(['item_group_id' => $group->id, 'lot' => 1]);

    $attachmentB = $itemB->attachments()->create([
        'path' => 'items/test-file-b.jpg',
    ]);
    Storage::disk('attachments')->put('items/test-file-b.jpg', 'content');

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
    Storage::disk('attachments')->assertExists('items/test-file-b.jpg');
});

test('the test mail command sends a test mail to the given recipient', function () {
    Mail::fake();

    $this->artisan('mail:test', ['email' => 'test@example.com'])->assertSuccessful();

    Mail::assertSent(TestMail::class, fn (TestMail $mail) => $mail->hasTo('test@example.com'));
});
