<?php

use App\Models\Item;
use App\Models\ItemContent;
use App\Models\ItemGroup;
use App\Models\ItemMaintenance;
use App\Models\ItemProcurement;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\FileStorage\SftpAdapter;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use phpseclib4\Net\SFTP;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');
});

test('uploaded files can never run scripts in the app', function (string $path, string $disposition) {
    Storage::fake('attachments');
    Storage::disk('attachments')->put($path, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
    $attachment = Item::factory()->create()->attachments()->create(['path' => $path]);

    $response = $this->actingAs($this->materialwart)->get(route('attachments.show', $attachment));

    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Security-Policy'))->toContain('sandbox')
        ->and($response->headers->get('Content-Disposition'))->toStartWith($disposition)
        ->and($response->headers->get('Cache-Control'))->toContain('private');
})->with([
    'svg' => ['items/bild.svg', 'attachment'],
    'image' => ['items/bild.png', 'inline'],
]);

test('content names reach the dialog as data, not as html', function () {
    $item = Item::factory()->create();
    ItemContent::factory()->create(['item_id' => $item->id, 'name' => '<img src=x onerror=alert(1)>', 'quantity' => '2x']);

    $this->actingAs($this->materialwart)
        ->get(route('orders.create'))
        ->assertSuccessful()
        ->assertSee('data-content-lines=', false)
        ->assertDontSee('data-bs-content', false);

    expect($item->load('contents')->contentLines())->toBe([['label' => '2x', 'text' => '<img src=x onerror=alert(1)>']]);
});

test('the number of queries does not grow with the items', function (string $routeName) {
    $this->actingAs($this->materialwart);
    $group = ItemGroup::factory()->create();
    $countQueries = function () use ($routeName): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route($routeName))->assertSuccessful();

        return count(DB::getQueryLog());
    };

    Item::factory()->count(2)->create(['item_group_id' => $group->id]);
    $countQueries();
    $queries = $countQueries();

    Item::factory()->count(5)->create(['item_group_id' => $group->id]);

    expect($countQueries())->toBe($queries);
})->with(['items.index', 'orders.create']);

test('the number of queries does not grow with the orders', function () {
    $this->actingAs($this->materialwart);
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('orders.index'))->assertSuccessful();

        return count(DB::getQueryLog());
    };
    $createOrder = fn () => OrderedItem::factory()->create([
        'order_id' => Order::factory()->create(['user_id' => $this->materialwart->id, 'role_id' => Role::findByName('Wolfsstufe')->id])->id,
    ]);

    $createOrder();
    $countQueries();
    $queries = $countQueries();

    $createOrder();
    $createOrder();

    expect($countQueries())->toBe($queries);
});

test('records in use are not deleted but explained', function (Closure $record, string $routeName) {
    $this->actingAs($this->materialwart)
        ->from(route('overwatch'))
        ->delete(route($routeName, $record()))
        ->assertSessionHas('error');
})->with([
    'item in an order' => [fn () => OrderedItem::factory()->create(['order_id' => Order::factory()->create()->id])->item, 'items.destroy'],
    'group with items' => [fn () => Item::factory()->create()->itemGroup, 'item-groups.destroy'],
    'user with orders' => [fn () => Order::factory()->create()->user, 'users.destroy'],
    'role with orders' => [fn () => Order::factory()->create()->role, 'roles.destroy'],
]);

test('every page of the app shows messages', function (string $routeName) {
    $this->actingAs($this->materialwart)
        ->withSession(['error' => 'Etwas ist schiefgegangen'])
        ->get(route($routeName))
        ->assertSeeInOrder(['<main id="main-content"', 'Etwas ist schiefgegangen'], false);
})->with(['item-collections.index', 'items.create', 'item-groups.create', 'users.create', 'roles.create']);

test('deleting an item removes the files of the item and its records', function () {
    Storage::fake('attachments');
    $item = Item::factory()->create();
    $paths = ['items/a.jpg', 'procurements/b.pdf', 'maintenances/c.jpg'];
    $item->attachments()->create(['path' => $paths[0]]);
    ItemProcurement::factory()->create(['item_id' => $item->id])->attachments()->create(['path' => $paths[1]]);
    ItemMaintenance::factory()->create(['item_id' => $item->id])->attachments()->create(['path' => $paths[2]]);

    foreach ($paths as $path) {
        Storage::disk('attachments')->put($path, 'x');
    }

    $this->actingAs($this->materialwart)->delete(route('items.destroy', $item))->assertSessionHas('success');

    Storage::disk('attachments')->assertMissing($paths);
    expect(Item::find($item->id))->toBeNull();
});

test('settings changed while a queue worker runs are used by its next job', function () {
    $resolvedBefore = app(ComponentManager::class);

    app()->forgetScopedInstances();

    expect(app(ComponentManager::class))->not->toBe($resolvedBefore);
});

test('the sftp adapter creates missing folders and works below its root', function () {
    $connection = Mockery::mock(SFTP::class);
    $connection->shouldReceive('is_dir')->with('/mat/items')->once()->andReturnFalse();
    $connection->shouldReceive('mkdir')->with('/mat/items', -1, true)->once();
    $connection->shouldReceive('put')->with('/mat/items/zelt.jpg', 'bild')->once();
    $connection->shouldReceive('get')->with('/mat/items/zelt.jpg')->once()->andReturn('bild');
    $connection->shouldReceive('is_file')->with('/mat/items/zelt.jpg')->once()->andReturnTrue();
    $connection->shouldReceive('file_exists')->with('/mat/items/zelt.jpg')->once()->andReturnTrue();
    $connection->shouldReceive('delete')->with('/mat/items/zelt.jpg', false)->once();

    $adapter = new class($connection) extends SftpAdapter
    {
        public function __construct(protected SFTP $fake)
        {
            parent::__construct('host', 22, 'user', root: '/mat');
        }

        protected function connection(): SFTP
        {
            return $this->fake;
        }
    };

    $adapter->write('items/zelt.jpg', 'bild', new Config);

    expect($adapter->read('items/zelt.jpg'))->toBe('bild')
        ->and($adapter->fileExists('items/zelt.jpg'))->toBeTrue();

    $adapter->delete('items/zelt.jpg');
});
