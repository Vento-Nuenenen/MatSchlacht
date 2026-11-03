<?php

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StorageUnit;
use App\Models\User;
use App\Services\ComponentManager;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(ComponentManager::class)->update(['storage' => ['enabled' => true, 'settings' => ['label_caption' => 'Pfadi Nünenen']]]);

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');
});

test('labels for a storage unit and its contents are printed as pdf', function () {
    $stock = ItemStock::factory()->create();
    $location = $stock->storageUnit->ancestors()[0];

    $this->actingAs($this->leader)
        ->get(route('labels.storage-unit', ['storageUnit' => $location, 'with_children' => 1, 'with_items' => 1, 'skip' => 5]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('labels for selected items are printed as pdf', function () {
    $items = Item::factory()->count(30)->create();

    $this->actingAs($this->leader)
        ->get(route('labels.items', ['items' => $items->pluck('id')->all()]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('printing item labels requires a selection', function () {
    $this->actingAs($this->leader)
        ->get(route('labels.items'))
        ->assertStatus(422);
});

test('the number of skipped labels has to fit on one sheet', function () {
    $this->actingAs($this->leader)
        ->get(route('labels.storage-unit', ['storageUnit' => StorageUnit::factory()->create(), 'skip' => 24]))
        ->assertSessionHasErrors('skip');
});

test('labels are not available while the storage component is disabled', function () {
    app(ComponentManager::class)->update(['storage' => ['enabled' => false, 'settings' => []]]);

    $this->actingAs($this->leader)
        ->get(route('labels.items', ['items' => [Item::factory()->create()->id]]))
        ->assertNotFound();
});

test('users without view permission cannot print labels', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('labels.storage-unit', StorageUnit::factory()->create()))
        ->assertForbidden();
});
