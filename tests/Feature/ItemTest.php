<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $role = Role::create(['name' => 'Materialwart']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);
    $this->itemGroup = ItemGroup::factory()->create();
});

test('can upload multiple files for an item', function () {
    Storage::fake('attachments');

    $response = $this->actingAs($this->user)
        ->post(route('items.store'), [
            'name' => 'Test Item',
            'stock' => 10,
            'lot' => 1,
            'repair' => 0,
            'item_group_id' => $this->itemGroup->id,
            'files' => [
                UploadedFile::fake()->image('image1.jpg'),
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ],
        ]);

    $response->assertRedirect(route('items.index'));

    $item = Item::where('name', 'Test Item')->first();
    expect($item->attachments)->toHaveCount(2);

    Storage::disk('attachments')->assertExists($item->attachments[0]->path);
    Storage::disk('attachments')->assertExists($item->attachments[1]->path);
});

test('can upload any file type', function () {
    Storage::fake('attachments');

    $response = $this->actingAs($this->user)
        ->post(route('items.store'), [
            'name' => 'Test Item PDF',
            'stock' => 10,
            'lot' => 1,
            'repair' => 0,
            'item_group_id' => $this->itemGroup->id,
            'files' => [
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ],
        ]);

    $response->assertRedirect(route('items.index'));
    $item = Item::where('name', 'Test Item PDF')->first();
    expect($item->attachments)->toHaveCount(1);
    expect($item->attachments[0]->path)->toEndWith('.pdf');
});
