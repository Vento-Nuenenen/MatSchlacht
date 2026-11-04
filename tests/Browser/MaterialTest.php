<?php

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemGroup;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    e2eLogin();
    $this->group = ItemGroup::factory()->create(['name' => 'Zelte']);
});

test('material is created, edited and deleted', function () {
    $page = visit('/items/create')
        ->fill('name', 'Spatz-Zelt')
        ->select('item_group_id', (string) $this->group->id)
        ->fill('stock', '12')
        ->fill('lot', '1')
        ->press('form[enctype] button[type=submit]')
        ->assertPathIs('/items')
        ->assertSee('Spatz-Zelt');

    $item = Item::sole();

    $page->click("a[href$=\"/items/{$item->id}/edit\"]")
        ->fill('name', 'Spatz-Zelt gross')
        ->press('#item-update-form button[type=submit]')
        ->assertPathIs('/items')
        ->assertSee('Spatz-Zelt gross');

    $page->script('window.confirm = () => true');
    $page->click("form[action$=\"/items/{$item->id}\"] button[type=submit]")
        ->assertDontSee('Spatz-Zelt gross');

    expect(Item::count())->toBe(0);
});

test('deleting needs a confirmation', function () {
    $item = e2eItem('Zelt');

    $page = visit('/items');
    $page->script('window.confirm = () => false');
    $page->click("form[action$=\"/items/{$item->id}\"] button[type=submit]")->assertSee('Zelt');

    expect(Item::count())->toBe(1);
});

// Uploading itself is covered by the feature tests: the test server of the browser plugin does not
// nest fields like "files[]", so a real upload cannot arrive here.
test('an image of the material is served through the app', function () {
    Storage::fake('attachments');
    Storage::disk('attachments')->put('items/zelt.png', base64_decode(E2E_PNG));
    $item = e2eItem('Zelt');
    $attachment = $item->attachments()->create(['path' => 'items/zelt.png']);

    visit("/items/{$item->id}/edit")
        ->assertPresent("img[src$=\"/attachments/{$attachment->id}\"]")
        ->assertNoBrokenImages();
});

test('content lists are maintained on the material', function () {
    $item = e2eItem('Kochkiste');

    visit("/items/{$item->id}/edit")
        ->fill('form[action$="/contents"] input[name=quantity]', '2x')
        ->fill('form[action$="/contents"] input[name=name]', 'Pfanne')
        ->press('form[action$="/contents"] button[type=submit]')
        ->assertSee('Pfanne')
        ->assertNoJavaScriptErrors();

    expect($item->contents()->sole()->name)->toBe('Pfanne');
});

test('the material page shows the data of the material', function () {
    $item = e2eItem('Zelt', 12);

    visit('/items')
        ->click("td a[href$=\"/items/{$item->id}\"]")
        ->assertPathIs("/items/{$item->id}")
        ->assertSee('Zelt');
});

test('the list can be filtered by group', function () {
    e2eItem('Zelt', 5, ['item_group_id' => $this->group->id]);
    e2eItem('Pfanne', 5, ['item_group_id' => ItemGroup::factory()->create()->id]);

    visit('/items')
        ->select('item_group_id', (string) $this->group->id)
        ->assertQueryStringHas('item_group_id', (string) $this->group->id)
        ->assertSee('Zelt')
        ->assertDontSee('Pfanne');
});

test('a click on a column header sorts the table', function () {
    e2eItem('Zelt', 3, ['item_group_id' => $this->group->id]);
    e2eItem('Beil', 30, ['item_group_id' => $this->group->id]);
    e2eItem('Seil', 12, ['item_group_id' => $this->group->id]);
    $firstName = "document.querySelector('tbody tr td a').textContent.trim()";

    visit('/items')
        ->click('thead th:nth-child(3)')
        ->assertScript($firstName, 'Zelt')
        ->click('thead th:nth-child(3)')
        ->assertScript($firstName, 'Beil');
});

test('tables become entries with labels on a smartphone', function () {
    e2eItem('Zelt', 12);

    visit('/items')->on()->mobile()
        ->assertScript("getComputedStyle(document.querySelector('.table-stack thead')).display", 'none')
        ->assertAttribute('.table-stack td[data-label="Bestand"]', 'data-label', 'Bestand')
        ->assertSeeIn('.table-stack td[data-cell-role="title"]', 'Zelt')
        ->assertPresent('.table-stack td[data-cell-role="actions"] a')
        ->assertScript("getComputedStyle(document.querySelector('.table-stack td[data-label=\"Bestand\"]'), '::before').content", '"Bestand"');
});

test('tables stay tables on a tablet', function () {
    e2eItem('Zelt', 12);

    visit('/items')->resize(820, 1180)
        ->assertScript("getComputedStyle(document.querySelector('.table-stack thead')).display", 'table-header-group');
});

test('a collection is put together in the material list', function () {
    $item = e2eItem('Zelt');

    visit('/item-collections/create')
        ->fill('name', 'Lagerset')
        ->click("tr:has(input[name=\"items[{$item->id}]\"]) [data-qty-step=\"1\"]")
        ->press('form[action$="/item-collections"] button[type=submit]')
        ->assertPathIs('/item-collections')
        ->assertSee('Lagerset')
        ->assertNoJavaScriptErrors();

    expect(ItemCollection::sole()->items()->sole()->id)->toBe($item->id);
});

test('material groups are created', function () {
    visit('/item-groups/create')
        ->fill('name', 'Küche')
        ->press('form[action$="/item-groups"] button[type=submit]')
        ->assertPathIs('/item-groups')
        ->assertSee('Küche');
});

test('a group that still has material is not deleted', function () {
    e2eItem('Zelt', 5, ['item_group_id' => $this->group->id]);

    $page = visit('/item-groups');
    $page->script('window.confirm = () => true');
    $page->click("form[action$=\"/item-groups/{$this->group->id}\"] button[type=submit]")
        ->assertSee('enthält noch Material');

    expect(ItemGroup::find($this->group->id))->not->toBeNull();
});
