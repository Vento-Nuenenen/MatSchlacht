<?php

use App\Models\Order;
use App\Models\OrderedItem;
use App\States\Order\Accepted;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Spatie\Permission\Models\Role;

function e2eTransition(string $state): string
{
    return "form:has(input[name=\"to\"][value=\"{$state}\"]) button[type=submit]";
}

beforeEach(function () {
    $this->materialwart = e2eLogin();
    $this->item = e2eItem('Spatz-Zelt');
    $this->order = Order::factory()->create([
        'user_id' => $this->materialwart->id,
        'role_id' => Role::findByName('Wolfsstufe')->id,
        'state' => Committed::class,
        'event' => 'Sommerlager',
        'event_start' => '2030-07-10',
        'event_end' => '2030-07-20',
    ]);
    $this->position = OrderedItem::factory()->create(['order_id' => $this->order->id, 'item_id' => $this->item->id, 'quantity' => 4]);
});

test('an order goes through all states up to the archive', function () {
    $page = visit("/manage/orders/{$this->order->id}/edit")
        ->click(e2eTransition('accepted'))
        ->assertSee('Bestellstatus wurde aktualisiert');
    expect($this->order->fresh()->state)->toBeInstanceOf(Accepted::class);

    $page->click(e2eTransition('delivered'))->assertSee('Bestellstatus wurde aktualisiert');
    expect($this->order->fresh()->state)->toBeInstanceOf(Delivered::class)
        ->and($this->position->fresh()->delivered)->toBe(4);

    $page->fill("input[name=\"returned[{$this->item->id}]\"]", '3')
        ->press('#orderUpdateForm button[type=submit]')
        ->assertNoJavaScriptErrors();
    expect($this->position->fresh()->returned)->toBe(3);

    visit("/manage/orders/{$this->order->id}/edit")->click(e2eTransition('returned'));
    expect($this->order->fresh()->state)->toBeInstanceOf(Returned::class);

    $archive = visit("/manage/orders/{$this->order->id}/edit");
    $archive->script('window.confirm = () => true');
    $archive->click(e2eTransition('archived'));
    expect($this->order->fresh()->state)->toBeInstanceOf(Archived::class);
});

test('a step can be undone', function () {
    visit("/manage/orders/{$this->order->id}/edit")
        ->click(e2eTransition('accepted'))
        ->click(e2eTransition('delivered'))
        ->click(e2eTransition('accepted'))
        ->assertSee('Bestellstatus wurde aktualisiert');

    expect($this->order->fresh()->state)->toBeInstanceOf(Accepted::class)
        ->and($this->position->fresh()->delivered)->toBe(0);
});

test('a rejected order goes back to the drafts', function () {
    visit("/manage/orders/{$this->order->id}/edit")->click(e2eTransition('stored'));

    expect($this->order->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('a draft cannot be submitted when its material is taken', function () {
    $draft = Order::factory()->create([
        'user_id' => $this->materialwart->id, 'role_id' => $this->order->role_id, 'state' => Stored::class,
        'event_start' => '2030-07-15', 'event_end' => '2030-07-18',
    ]);
    OrderedItem::factory()->create(['order_id' => $draft->id, 'item_id' => $this->item->id, 'quantity' => 8]);

    visit("/manage/orders/{$draft->id}/edit")
        ->click(e2eTransition('committed'))
        ->assertSee('nur noch 6 Stück verfügbar');

    expect($draft->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('the quantities of a submitted order can be changed', function () {
    visit("/manage/orders/{$this->order->id}/edit")
        ->click("tr:has(input[name=\"items[{$this->item->id}]\"]) [data-qty-step=\"1\"]")
        ->press('#orderUpdateForm button[type=submit]')
        ->assertPathIs('/manage/orders')
        ->assertNoJavaScriptErrors();

    expect($this->position->fresh()->quantity)->toBe(5);
});

test('unsaved changes are not lost by a status change without asking', function () {
    $page = visit("/manage/orders/{$this->order->id}/edit");
    $page->script('window.confirm = () => { window.asked = true; return false; }');

    $page->click("tr:has(input[name=\"items[{$this->item->id}]\"]) [data-qty-step=\"1\"]")
        ->click(e2eTransition('accepted'))
        ->assertScript('window.asked', true);

    expect($this->order->fresh()->state)->toBeInstanceOf(Committed::class);
});

test('the overview lists the orders by state', function () {
    visit('/manage/orders')
        ->assertSeeIn('section[aria-labelledby="state-committed"]', 'Sommerlager')
        ->assertNoJavaScriptErrors();
});
