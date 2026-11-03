<?php

use App\Enums\StorageLevel;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\Setting;
use App\Models\StorageUnit;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\Pdf\OrderSheetPdf;
use App\States\Order\Accepted;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(ComponentManager::class)->update(['storage' => ['enabled' => true, 'settings' => []]]);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');
});

test('the storage is not reachable while the component is disabled', function () {
    app(ComponentManager::class)->update(['storage' => ['enabled' => false, 'settings' => []]]);

    $this->actingAs($this->materialwart)
        ->get(route('storage-units.index'))
        ->assertNotFound();
});

test('the storage component is disabled by default', function () {
    Setting::query()->delete();
    app(ComponentManager::class)->flush();

    expect(app(ComponentManager::class)->isEnabled('storage'))->toBeFalse();
});

test('the materialwart can build the storage hierarchy', function () {
    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => 'location', 'name' => 'Pfadiheim'])
        ->assertSessionHasNoErrors();
    $location = StorageUnit::where('name', 'Pfadiheim')->sole();

    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => 'room', 'parent_id' => $location->id, 'name' => 'Keller'])
        ->assertRedirect(route('storage-units.show', $location));

    $room = StorageUnit::where('name', 'Keller')->sole();
    expect($room->level)->toBe(StorageLevel::Room)
        ->and($room->parent->is($location))->toBeTrue();

    $this->actingAs($this->materialwart)
        ->get(route('storage-units.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Pfadiheim', 'Keller']);
});

test('a storage unit has to be placed directly below the next higher level', function (string $level, Closure $parent) {
    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => $level, 'parent_id' => $parent()?->id, 'name' => 'Fach 1'])
        ->assertSessionHasErrors('parent_id');

    expect(StorageUnit::where('name', 'Fach 1')->exists())->toBeFalse();
})->with([
    'room without location' => ['room', fn () => null],
    'bin in a room' => ['bin', fn () => StorageUnit::factory()->room()->create()],
    'bin directly in a shelf' => ['bin', fn () => StorageUnit::factory()->shelf()->create()],
    'shelf tier in a room' => ['tier', fn () => StorageUnit::factory()->room()->create()],
    'location inside a location' => ['location', fn () => StorageUnit::factory()->create()],
]);

test('leaders can view the storage but not change it', function () {
    $bin = StorageUnit::factory()->bin()->create();

    $this->actingAs($this->leader)->get(route('storage-units.show', $bin))->assertSuccessful();
    $this->actingAs($this->leader)
        ->post(route('storage-units.store'), ['level' => 'location', 'name' => 'Pfadiheim'])
        ->assertForbidden();
    $this->actingAs($this->leader)
        ->post(route('item-stocks.store'), ['storage_unit_id' => $bin->id, 'item_id' => Item::factory()->create()->id, 'quantity' => 3])
        ->assertForbidden();

    expect(ItemStock::count())->toBe(0);
});

test('the item stock is the sum of its bins', function () {
    $item = Item::factory()->create(['stock' => 99]);
    [$firstBin, $secondBin] = StorageUnit::factory()->bin()->count(2)->create();

    $this->actingAs($this->materialwart)
        ->post(route('item-stocks.store'), ['storage_unit_id' => $firstBin->id, 'item_id' => $item->id, 'quantity' => 4])
        ->assertSessionHasNoErrors();
    $this->actingAs($this->materialwart)
        ->post(route('item-stocks.store'), ['storage_unit_id' => $secondBin->id, 'item_id' => $item->id, 'quantity' => 6]);

    expect($item->fresh()->stock)->toBe(10);

    $this->actingAs($this->materialwart)
        ->put(route('item-stocks.update', ItemStock::where('storage_unit_id', $firstBin->id)->sole()), ['quantity' => 1]);

    expect($item->fresh()->stock)->toBe(7);

    ItemStock::all()->each(fn (ItemStock $stock) => $this->actingAs($this->materialwart)->delete(route('item-stocks.destroy', $stock)));

    expect($item->fresh()->stock)->toBe(0);
});

test('the manual stock is ignored for items stored in bins', function () {
    $item = Item::factory()->create(['lot' => 1, 'repair' => 0]);
    ItemStock::factory()->create(['item_id' => $item->id, 'quantity' => 5]);

    $this->actingAs($this->materialwart)
        ->put(route('items.update', $item), [
            'name' => $item->name,
            'item_group_id' => $item->item_group_id,
            'stock' => 50,
            'lot' => 1,
            'repair' => 0,
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->stock)->toBe(5);
});

test('stock can only be stored in bins and only once per bin', function () {
    $item = Item::factory()->create();
    $shelf = StorageUnit::factory()->shelf()->create();
    $stock = ItemStock::factory()->create(['item_id' => $item->id]);

    $this->actingAs($this->materialwart)
        ->post(route('item-stocks.store'), ['storage_unit_id' => $shelf->id, 'item_id' => $item->id, 'quantity' => 1])
        ->assertSessionHasErrors('storage_unit_id');
    $this->actingAs($this->materialwart)
        ->post(route('item-stocks.store'), ['storage_unit_id' => $stock->storage_unit_id, 'item_id' => $item->id, 'quantity' => 1])
        ->assertSessionHasErrors('item_id');

    expect(ItemStock::count())->toBe(1);
});

test('only empty storage units can be deleted', function () {
    $stock = ItemStock::factory()->create();
    $bin = $stock->storageUnit;
    $tier = $bin->parent;

    $this->actingAs($this->materialwart)->delete(route('storage-units.destroy', $tier))->assertSessionHas('error');
    $this->actingAs($this->materialwart)->delete(route('storage-units.destroy', $bin))->assertSessionHas('error');

    $stock->delete();

    $this->actingAs($this->materialwart)
        ->delete(route('storage-units.destroy', $bin))
        ->assertRedirect(route('storage-units.show', $tier));

    expect(StorageUnit::find($bin->id))->toBeNull()
        ->and(StorageUnit::find($tier->id))->not->toBeNull();
});

test('the item page shows where the item is stored', function () {
    $stock = ItemStock::factory()->create();
    $path = $stock->storageUnit->path();

    $this->actingAs($this->leader)
        ->get(route('items.show', $stock->item))
        ->assertSuccessful()
        ->assertSee($path);
});

test('the storage pages render for the materialwart', function () {
    $stock = ItemStock::factory()->create();
    $bin = $stock->storageUnit;
    StorageUnit::factory()->bin()->create();

    $pages = [
        route('storage-units.show', $bin->parent) => $bin->name,
        route('storage-units.show', $bin) => $stock->item->name,
        route('storage-units.create', ['parent' => $bin->parent_id]) => $bin->parent->path(),
        route('storage-units.edit', $bin) => $bin->name,
        route('items.edit', $stock->item) => $bin->path(),
        route('items.index') => 'Etiketten für Auswahl',
    ];

    foreach ($pages as $page => $expectedText) {
        $this->actingAs($this->materialwart)->get($page)->assertSuccessful()->assertSee($expectedText);
    }
});

test('the order sheet lists the items in storage order', function () {
    $shelf = StorageUnit::factory()->shelf()->create(['name' => 'Regal A']);
    $upperTier = StorageUnit::factory()->tier()->create(['parent_id' => $shelf->id, 'name' => 'Ebene 2']);
    $lowerTier = StorageUnit::factory()->tier()->create(['parent_id' => $shelf->id, 'name' => 'Ebene 1']);
    $bins = [
        'Fach 10' => StorageUnit::factory()->bin()->create(['parent_id' => $lowerTier->id, 'name' => 'Fach 10']),
        'Fach 2' => StorageUnit::factory()->bin()->create(['parent_id' => $lowerTier->id, 'name' => 'Fach 2']),
        'Oben' => StorageUnit::factory()->bin()->create(['parent_id' => $upperTier->id, 'name' => 'Fach 1']),
    ];

    $order = Order::factory()->create(['state' => Accepted::class]);
    $itemNames = ['Abseilgerät' => null, 'Seil' => 'Oben', 'Zelt' => 'Fach 2', 'Blache' => 'Fach 10'];

    foreach ($itemNames as $itemName => $binName) {
        $item = Item::factory()->create(['name' => $itemName]);
        OrderedItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id, 'quantity' => 1]);

        if ($binName !== null) {
            ItemStock::factory()->create(['item_id' => $item->id, 'storage_unit_id' => $bins[$binName]->id, 'quantity' => 3]);
        }
    }

    $order->load('orderedItems.item.stocks.storageUnit.'.StorageUnit::ANCESTORS);

    expect(app(OrderSheetPdf::class)->sortByStoragePath($order->orderedItems)->pluck('item.name')->all())
        ->toBe(['Zelt', 'Blache', 'Seil', 'Abseilgerät']);

    $this->actingAs($this->materialwart)
        ->get(route('orders.manage.order-pdf', $order))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});
