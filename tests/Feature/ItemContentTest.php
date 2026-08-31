<?php

use App\Models\Item;
use App\Models\ItemContent;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('edit items');
    $this->user = User::factory()->create();
    $this->user->givePermissionTo('edit items');
});

test('a user with permission can add content to an item', function () {
    $item = Item::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('items.contents.store', $item), [
            'name' => 'Test Inhalt',
            'quantity' => '5x',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('item_contents', [
        'item_id' => $item->id,
        'name' => 'Test Inhalt',
        'quantity' => '5x',
    ]);
});

test('a user with permission can delete content from an item', function () {
    $item = Item::factory()->create();
    $content = ItemContent::factory()->create(['item_id' => $item->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('items.contents.destroy', $content));

    $response->assertRedirect();
    $this->assertDatabaseMissing('item_contents', [
        'id' => $content->id,
    ]);
});

test('unauthorized users cannot add content', function () {
    $item = Item::factory()->create();
    $unprivilegedUser = User::factory()->create();

    $response = $this->actingAs($unprivilegedUser)
        ->post(route('items.contents.store', $item), [
            'name' => 'Test Inhalt',
        ]);

    $response->assertForbidden();
});
