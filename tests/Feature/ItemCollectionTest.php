<?php

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    // Setup roles and permissions
    Permission::create(['name' => 'edit orders']);
    $role = Role::create(['name' => 'Materialwart']);
    $role->givePermissionTo('edit orders');

    $this->user = User::factory()->create();
    $this->user->assignRole($role);
});

test('can list item collections', function () {
    ItemCollection::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get(route('item-collections.index'))
        ->assertSuccessful()
        ->assertSee(ItemCollection::first()->name);
});

test('can create an item collection', function () {
    $items = Item::factory()->count(2)->create();

    $response = $this->actingAs($this->user)
        ->post(route('item-collections.store'), [
            'name' => 'Test Collection',
            'description' => 'Test Description',
            'items' => [
                $items[0]->id => 5,
                $items[1]->id => 10,
            ],
        ]);

    $response->assertRedirect(route('item-collections.index'));
    assertDatabaseHas('item_collections', ['name' => 'Test Collection']);
    assertDatabaseHas('item_collection_items', [
        'item_id' => $items[0]->id,
        'quantity' => 5,
    ]);
});

test('collections are visible on order create page', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Orderable Collection']);

    $this->actingAs($this->user)
        ->get(route('orders.create'))
        ->assertSuccessful()
        ->assertSee('Orderable Collection');
});
