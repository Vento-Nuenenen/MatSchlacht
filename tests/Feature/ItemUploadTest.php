<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

test('authenticated user with permission can upload files to an item', function () {
    seed(RolesAndPermissionsSeeder::class);
    Storage::fake('attachments');
    $user = User::factory()->create();
    $user->assignRole('Materialwart');

    $group = ItemGroup::create(['name' => 'Test Group', 'position' => 1]);

    $file = UploadedFile::fake()->image('test.jpg');

    actingAs($user)
        ->post(route('items.store'), [
            'name' => 'Test Item',
            'stock' => 10,
            'lot' => 1,
            'item_group_id' => $group->id,
            'files' => [$file],
        ])
        ->assertRedirect(route('items.index'));

    $item = Item::where('name', 'Test Item')->first();
    expect($item->attachments)->toHaveCount(1);
    Storage::disk('attachments')->assertExists($item->attachments->first()->path);
});

test('authenticated user with permission can upload files when updating an item', function () {
    seed(RolesAndPermissionsSeeder::class);
    Storage::fake('attachments');
    $user = User::factory()->create();
    $user->assignRole('Materialwart');

    $group = ItemGroup::create(['name' => 'Test Group', 'position' => 1]);
    $item = Item::create([
        'name' => 'Initial Item',
        'stock' => 5,
        'lot' => 1,
        'item_group_id' => $group->id,
    ]);

    $file = UploadedFile::fake()->image('update.png');

    actingAs($user)
        ->put(route('items.update', $item), [
            'name' => 'Updated Item',
            'stock' => 5,
            'lot' => 1,
            'item_group_id' => $group->id,
            'files' => [$file],
        ])
        ->assertRedirect(route('items.index'));

    $item->refresh();
    expect($item->name)->toBe('Updated Item');
    expect($item->attachments)->toHaveCount(1);
    Storage::disk('attachments')->assertExists($item->attachments->first()->path);
});

test('it validates file size against server limits', function () {
    seed(RolesAndPermissionsSeeder::class);
    Storage::fake('attachments');
    $user = User::factory()->create();
    $user->assignRole('Materialwart');

    $group = ItemGroup::create(['name' => 'Test Group', 'position' => 1]);

    // PHP limit is 2M in this environment
    $file = UploadedFile::fake()->create('large.jpg', 3000); // 3MB

    actingAs($user)
        ->post(route('items.store'), [
            'name' => 'Large Item',
            'stock' => 10,
            'lot' => 1,
            'item_group_id' => $group->id,
            'files' => [$file],
        ])
        ->assertSessionHasErrors(['files.0']);
});
