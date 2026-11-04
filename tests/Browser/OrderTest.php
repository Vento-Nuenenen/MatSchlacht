<?php

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemContent;
use App\Models\Order;
use App\Models\OrderedItem;
use App\States\Order\Committed;
use App\States\Order\Stored;
use Spatie\Permission\Models\Role;

/**
 * Fill in the head of the order form.
 */
function e2eOrderForm(int $roleId, int $userId): mixed
{
    return visit('/orders/create')
        ->fill('event', 'Sommerlager')
        ->select('role_id', (string) $roleId)
        ->select('user_id', (string) $userId)
        ->fill('event_start', '2030-07-10')
        ->fill('event_end', '2030-07-20');
}

function e2eStepUp(Item $item): string
{
    return "tr:has(input[name=\"items[{$item->id}]\"]) [data-qty-step=\"1\"]";
}

function e2eStepDown(Item $item): string
{
    return "tr:has(input[name=\"items[{$item->id}]\"]) [data-qty-step=\"-1\"]";
}

beforeEach(function () {
    $this->role = Role::findByName('Wolfsstufe');
    $this->user = e2eLogin('Wolfsstufe');
    $this->item = e2eItem('Spatz-Zelt');
});

test('a draft is saved with the quantity steppers and listed', function () {
    e2eOrderForm($this->role->id, $this->user->id)
        ->click(e2eStepUp($this->item))
        ->click(e2eStepUp($this->item))
        ->click(e2eStepUp($this->item))
        ->click(e2eStepDown($this->item))
        ->assertValue("input[name=\"items[{$this->item->id}]\"]", '2')
        ->assertAttributeContains("tr:has(input[name=\"items[{$this->item->id}]\"])", 'class', 'table-primary')
        ->press('button[name=action][value=save]')
        ->assertPathIs('/orders')
        ->assertSee('Sommerlager')
        ->assertNoJavaScriptErrors();

    $order = Order::sole();
    expect($order->state)->toBeInstanceOf(Stored::class)
        ->and($order->orderedItems()->sole()->quantity)->toBe(2);
});

test('the stepper stops at the available quantity', function () {
    $item = e2eItem('Beil', 2);

    $page = visit('/orders/create');

    foreach (range(1, 3) as $click) {
        $page->click(e2eStepUp($item));
    }

    $page->assertValue("input[name=\"items[{$item->id}]\"]", '2');
});

test('a package item is ordered in packages and stored in units', function () {
    $item = e2eItem('Heringe', 40, ['lot' => 10]);

    e2eOrderForm($this->role->id, $this->user->id)
        ->click(e2eStepUp($item))
        ->click(e2eStepUp($item))
        ->press('button[name=action][value=order]')
        ->assertPathIs('/orders');

    expect(Order::sole()->orderedItems()->sole()->quantity)->toBe(20);
});

test('a collection fills in its items and can be removed again', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Kochset']);
    $collection->items()->attach($this->item->id, ['quantity' => 3]);
    $quantity = "input[name=\"items[{$this->item->id}]\"]";

    visit('/orders/create')
        ->select('collectionSelect', (string) $collection->id)
        ->click('[data-collection-add]')
        ->assertValue($quantity, '3')
        ->assertSeeIn('#addedCollections', 'Kochset')
        ->click(e2eStepDown($this->item))
        ->assertValue($quantity, '3')
        ->click('[data-collection-remove]')
        ->assertValue($quantity, '0')
        ->assertMissing('#addedCollections')
        ->assertNoJavaScriptErrors();
});

test('an order with more than half of the stock in a collection can be submitted', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Grosses Set']);
    $collection->items()->attach($this->item->id, ['quantity' => 6]);

    e2eOrderForm($this->role->id, $this->user->id)
        ->select('collectionSelect', (string) $collection->id)
        ->click('[data-collection-add]')
        ->press('button[name=action][value=order]')
        ->assertPathIs('/orders')
        ->assertNoJavaScriptErrors();

    $order = Order::sole();
    expect($order->state)->toBeInstanceOf(Committed::class)
        ->and($order->orderedItems()->sole()->quantity)->toBe(6)
        ->and($order->itemCollections)->toHaveCount(1);
});

test('editing an order keeps its collection and minimum', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Kochset']);
    $collection->items()->attach($this->item->id, ['quantity' => 2]);
    $order = Order::factory()->create(['user_id' => $this->user->id, 'role_id' => $this->role->id]);
    OrderedItem::factory()->create(['order_id' => $order->id, 'item_id' => $this->item->id, 'quantity' => 2]);
    $order->itemCollections()->attach($collection->id);

    visit("/orders/{$order->id}/edit")
        ->assertSeeIn('#addedCollections', 'Kochset')
        ->click(e2eStepDown($this->item))
        ->assertValue("input[name=\"items[{$this->item->id}]\"]", '2')
        ->press('button[name=action][value=save]')
        ->assertPathIs('/orders');

    expect($order->fresh()->itemCollections)->toHaveCount(1);
});

test('a second order for the same period is refused with a clear message', function () {
    $other = Order::factory()->create([
        'user_id' => $this->user->id, 'role_id' => $this->role->id, 'state' => Committed::class,
        'event_start' => '2030-07-15', 'event_end' => '2030-07-25',
    ]);
    OrderedItem::factory()->create(['order_id' => $other->id, 'item_id' => $this->item->id, 'quantity' => 9]);

    $page = e2eOrderForm($this->role->id, $this->user->id);

    foreach (range(1, 3) as $click) {
        $page->click(e2eStepUp($this->item));
    }

    $page->press('button[name=action][value=order]')
        ->assertPathIs('/orders/create')
        ->assertSee('nur noch 1 Stück verfügbar');

    expect(Order::count())->toBe(1);
});

test('the search filters the material of the order form', function () {
    $other = e2eItem('Gaskocher');

    visit('/orders/create')
        ->fill('itemSearch', 'gask')
        ->assertVisible("tr:has(input[name=\"items[{$other->id}]\"])")
        ->assertMissing("tr:has(input[name=\"items[{$this->item->id}]\"])");
});

test('the content dialog shows a content list as plain text', function () {
    ItemContent::factory()->create(['item_id' => $this->item->id, 'name' => '<img src=x onerror=window.hacked=1>', 'quantity' => '2x']);

    visit('/orders/create')
        ->click('[data-content-lines]')
        ->assertVisible('#contentModal')
        ->assertSeeIn('#contentModal .modal-body', '<img src=x onerror=window.hacked=1>')
        ->assertNotPresent('#contentModal .modal-body img')
        ->assertScript('window.hacked === undefined', true);
});

test('a leader deletes an own draft after confirming', function () {
    $order = Order::factory()->create(['user_id' => $this->user->id, 'role_id' => $this->role->id, 'event' => 'Fehlplanung']);

    $page = visit('/orders');
    $page->script('window.confirm = () => true');

    $page->click("form[action$=\"/orders/{$order->id}\"] button[type=submit]")
        ->assertDontSee('Fehlplanung');

    expect(Order::count())->toBe(0);
});

test('orders of another unit stay hidden from leaders', function () {
    $foreign = Order::factory()->create(['role_id' => Role::findByName('Pfadistufe')->id, 'event' => 'Fremdes Lager']);

    visit('/orders')->assertDontSee('Fremdes Lager');
    visit("/orders/{$foreign->id}")->assertDontSee('Fremdes Lager');
});

test('an order can be placed on a smartphone', function () {
    visit('/orders/create')->on()->mobile()
        ->fill('event', 'Sommerlager')
        ->select('role_id', (string) $this->role->id)
        ->select('user_id', (string) $this->user->id)
        ->fill('event_start', '2030-07-10')
        ->fill('event_end', '2030-07-20')
        ->click(e2eStepUp($this->item))
        ->press('button[name=action][value=order]')
        ->assertPathIs('/orders');

    expect(Order::sole()->orderedItems()->sole()->quantity)->toBe(1);
});
