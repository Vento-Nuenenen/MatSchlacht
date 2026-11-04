<?php

use App\Enums\StorageLevel;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use App\Models\ItemStock;
use App\Models\StorageUnit;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    e2eEnable('storage', 'procurement', 'maintenance');
    e2eLogin();
    $this->item = e2eItem('Spatz-Zelt', 5);
});

test('the storage is built level by level and stock is stored in a bin', function () {
    $page = visit('/storage-units/create')
        ->fill('name', 'Pfadiheim')
        ->press('form[action$="/storage-units"] button[type=submit]');

    foreach (['Keller', 'Regal A', 'Ebene 1'] as $name) {
        $page->click('a[href*="create?parent"]')
            ->fill('name', $name)
            ->press('form[action$="/storage-units"] button[type=submit]');

        $page->navigate('/storage-units/'.StorageUnit::where('name', $name)->sole()->id);
    }

    $page->click('a[href*="create?parent"]')
        ->press('form[action$="/storage-units"] button[type=submit]')
        ->assertNoJavaScriptErrors();

    $bin = StorageUnit::ofLevel(StorageLevel::Bin)->sole();

    visit("/storage-units/{$bin->id}")
        ->select('item_id', (string) $this->item->id)
        ->fill('form[action$="/item-stocks"] input[name=quantity]', '7')
        ->press('form[action$="/item-stocks"] button[type=submit]')
        ->assertSee('Spatz-Zelt');

    expect($this->item->fresh()->stock)->toBe(7);

    visit('/storage-units')->assertSee('Pfadiheim')->assertSee('Regal A');

    visit("/items/{$this->item->id}")->assertSee('Pfadiheim');
});

test('the stock of an item in bins cannot be typed in', function () {
    ItemStock::factory()->create(['item_id' => $this->item->id, 'quantity' => 4]);

    visit("/items/{$this->item->id}/edit")
        ->assertPresent('input#stock[readonly]')
        ->fill('.table input[name=quantity]', '6')
        ->press('form[action*="/item-stocks/"] button[title="Speichern"]')
        ->assertSee('Lagerbestand wurde aktualisiert');

    expect($this->item->fresh()->stock)->toBe(6);
});

test('the label dialog opens on a storage unit', function () {
    $location = StorageUnit::factory()->create(['name' => 'Pfadiheim']);

    visit("/storage-units/{$location->id}")
        ->click('button[data-bs-target="#labelModal"]')
        ->assertVisible('#labelModal')
        ->assertPresent('#label-with-children')
        ->assertNoJavaScriptErrors();
});

test('a storage unit with content is not deleted', function () {
    $stock = ItemStock::factory()->create();

    $page = visit("/storage-units/{$stock->storage_unit_id}");
    $page->script('window.confirm = () => true');
    $page->click("form[action$=\"/storage-units/{$stock->storage_unit_id}\"] button[type=submit]")
        ->assertSee('nicht leer');

    expect(StorageUnit::find($stock->storage_unit_id))->not->toBeNull();
});

test('a procurement raises the stock and can be edited', function () {
    visit("/items/{$this->item->id}/edit")
        ->fill('procured_on', '2026-06-01')
        ->fill('procurement_quantity', '3')
        ->fill('supplier', 'Hajk')
        ->fill('unit_price', '129.90')
        ->fill('invoice_number', 'RE-4711')
        ->check('add_to_stock')
        ->press('form[action$="/procurements"] button[type=submit]')
        ->assertSee('Hajk')
        ->assertSee('389.70');

    expect($this->item->fresh()->stock)->toBe(8);

    $procurement = ItemProcurement::sole();

    visit("/items/{$this->item->id}/edit")
        ->click("a[href$=\"/item-procurements/{$procurement->id}/edit\"]")
        ->fill('procurement_quantity', '5')
        ->press('form[action*="/item-procurements/"] button[type=submit]')
        ->assertPathIs("/items/{$this->item->id}/edit");

    expect($this->item->fresh()->stock)->toBe(10);
});

test('a receipt of a procurement can be opened', function () {
    Storage::fake('attachments');
    Storage::disk('attachments')->put('procurements/rechnung.png', base64_decode(E2E_PNG));
    $receipt = ItemProcurement::factory()->create(['item_id' => $this->item->id])->attachments()->create(['path' => 'procurements/rechnung.png']);

    visit("/items/{$this->item->id}/edit")
        ->assertSee('rechnung.png')
        ->assertAttributeContains('a[title="rechnung.png"]', 'href', "/attachments/{$receipt->id}")
        ->navigate("/attachments/{$receipt->id}")
        ->assertNoJavaScriptErrors();
});

test('maintenance due shows up and disappears once it is done', function () {
    $page = visit("/items/{$this->item->id}/edit")
        ->fill('maintenance_interval_months', '12')
        ->press('#item-update-form button[type=submit]');

    visit('/overwatch')->assertSee('Wartung fällig');

    visit('/maintenance?due=1')->assertSee('Spatz-Zelt');

    visit("/items/{$this->item->id}/edit")
        ->select('maintenance_type', 'repair')
        ->fill('maintenance_description', 'Reissverschluss ersetzt')
        ->press('form[action$="/maintenances"] button[type=submit]')
        ->assertSee('Reissverschluss ersetzt');

    expect(ItemMaintenance::sole()->type->value)->toBe('repair');

    visit('/maintenance?due=1')->assertDontSee('Spatz-Zelt');
    visit('/overwatch')->assertDontSee('Wartung fällig');
});
