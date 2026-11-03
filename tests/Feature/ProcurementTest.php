<?php

use App\Models\Item;
use App\Models\ItemProcurement;
use App\Models\ItemStock;
use App\Models\User;
use App\Services\ComponentManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(ComponentManager::class)->update(['procurement' => ['enabled' => true, 'settings' => []]]);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');

    $this->item = Item::factory()->create(['stock' => 5]);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function procurementPayload(array $overrides = []): array
{
    return array_merge([
        'procured_on' => now()->subMonth()->format('Y-m-d'),
        'quantity' => 3,
        'supplier' => 'Hajk',
        'unit_price' => '129.90',
        'invoice_number' => 'RE-4711',
        'warranty_until' => now()->addYear()->format('Y-m-d'),
        'notes' => 'Ersatz für beschädigte Zelte',
    ], $overrides);
}

test('procurements are not reachable while the component is disabled', function () {
    app(ComponentManager::class)->update(['procurement' => ['enabled' => false, 'settings' => []]]);

    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload())
        ->assertNotFound();
});

test('the materialwart can record a procurement', function () {
    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload())
        ->assertSessionHasNoErrors();

    $procurement = ItemProcurement::sole();
    expect($procurement->unit_price_cents)->toBe(12990)
        ->and($procurement->totalPriceCents())->toBe(38970)
        ->and($procurement->added_to_stock)->toBeFalse()
        ->and($this->item->fresh()->stock)->toBe(5);

    $this->actingAs($this->materialwart)
        ->get(route('items.edit', $this->item))
        ->assertSuccessful()
        ->assertSee('RE-4711')
        ->assertSee('CHF 389.70', false);
});

test('a procurement can add its quantity to the stock and takes it off again when deleted', function () {
    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload(['add_to_stock' => '1']))
        ->assertSessionHasNoErrors();

    expect($this->item->fresh()->stock)->toBe(8);

    $this->actingAs($this->materialwart)->delete(route('items.procurements.destroy', ItemProcurement::sole()));

    expect($this->item->fresh()->stock)->toBe(5)
        ->and(ItemProcurement::count())->toBe(0);
});

test('a procurement cannot add to the stock of an item stored in bins', function () {
    app(ComponentManager::class)->update(['storage' => ['enabled' => true, 'settings' => []]]);
    ItemStock::factory()->create(['item_id' => $this->item->id, 'quantity' => 5]);

    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload(['add_to_stock' => '1']))
        ->assertSessionHasErrors('add_to_stock');

    expect(ItemProcurement::count())->toBe(0);
});

test('the warranty cannot end before the procurement', function () {
    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload([
            'procured_on' => '2026-06-01',
            'warranty_until' => '2026-05-01',
        ]))
        ->assertSessionHasErrors('warranty_until');
});

test('users without the manage procurements permission neither see nor change procurements', function () {
    $procurement = ItemProcurement::factory()->create(['item_id' => $this->item->id, 'supplier' => 'Geheimlieferant']);

    $this->actingAs($this->leader)
        ->post(route('items.procurements.store', $this->item), procurementPayload())
        ->assertForbidden();
    $this->actingAs($this->leader)
        ->delete(route('items.procurements.destroy', $procurement))
        ->assertForbidden();
    $this->actingAs($this->leader)
        ->get(route('items.show', $this->item))
        ->assertSuccessful()
        ->assertDontSee('Geheimlieferant');

    expect(ItemProcurement::count())->toBe(1);
});

test('a procurement can be edited and a changed stock contribution is applied as difference', function () {
    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload(['quantity' => 3, 'add_to_stock' => '1']));
    $procurement = ItemProcurement::sole();
    expect($this->item->fresh()->stock)->toBe(8);

    $this->actingAs($this->materialwart)
        ->get(route('items.procurements.edit', $procurement))
        ->assertSuccessful()
        ->assertSee('RE-4711');

    $this->actingAs($this->materialwart)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['quantity' => 5, 'supplier' => 'Transa', 'add_to_stock' => '1']))
        ->assertRedirect(route('items.edit', $this->item));

    expect($procurement->fresh()->supplier)->toBe('Transa')
        ->and($this->item->fresh()->stock)->toBe(10);

    $this->actingAs($this->materialwart)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['quantity' => 5, 'add_to_stock' => '0']));

    expect($this->item->fresh()->stock)->toBe(5)
        ->and($procurement->fresh()->added_to_stock)->toBeFalse();
});

test('the stock contribution cannot change once the item is stored in bins', function () {
    $procurement = ItemProcurement::factory()->create(['item_id' => $this->item->id, 'quantity' => 2, 'added_to_stock' => false]);
    app(ComponentManager::class)->update(['storage' => ['enabled' => true, 'settings' => []]]);
    ItemStock::factory()->create(['item_id' => $this->item->id]);

    $this->actingAs($this->materialwart)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['quantity' => 2, 'add_to_stock' => '1']))
        ->assertSessionHasErrors('add_to_stock');

    $this->actingAs($this->materialwart)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['quantity' => 2, 'add_to_stock' => '0', 'supplier' => 'Transa']))
        ->assertSessionHasNoErrors();

    expect($procurement->fresh()->supplier)->toBe('Transa');
});

test('receipts can be attached to a procurement, removed and are deleted with it', function () {
    Storage::fake('s3');

    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload([
            'files' => [UploadedFile::fake()->create('rechnung.pdf', 100, 'application/pdf'), UploadedFile::fake()->image('garantie.jpg')],
        ]))
        ->assertSessionHasNoErrors();

    $procurement = ItemProcurement::sole();
    [$invoice, $warranty] = $procurement->attachments->all();
    Storage::disk('s3')->assertExists([$invoice->path, $warranty->path]);

    $this->actingAs($this->materialwart)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['remove_attachments' => [$invoice->id]]))
        ->assertSessionHasNoErrors();

    Storage::disk('s3')->assertMissing($invoice->path);
    expect($procurement->attachments()->pluck('id')->all())->toBe([$warranty->id]);

    $this->actingAs($this->materialwart)->delete(route('items.procurements.destroy', $procurement));

    Storage::disk('s3')->assertMissing($warranty->path);
});

test('receipts are ignored while attachments are disabled', function () {
    Storage::fake('s3');
    app(ComponentManager::class)->update(['attachments' => ['enabled' => false, 'settings' => []]]);

    $this->actingAs($this->materialwart)
        ->post(route('items.procurements.store', $this->item), procurementPayload(['files' => [UploadedFile::fake()->image('beleg.jpg')]]))
        ->assertSessionHasNoErrors();

    expect(ItemProcurement::sole()->attachments)->toBeEmpty();
});

test('users without the manage procurements permission cannot edit procurements', function () {
    $procurement = ItemProcurement::factory()->create(['item_id' => $this->item->id, 'supplier' => 'Hajk']);

    $this->actingAs($this->leader)->get(route('items.procurements.edit', $procurement))->assertForbidden();
    $this->actingAs($this->leader)
        ->put(route('items.procurements.update', $procurement), procurementPayload(['supplier' => 'Transa']))
        ->assertForbidden();

    expect($procurement->fresh()->supplier)->toBe('Hajk');
});
