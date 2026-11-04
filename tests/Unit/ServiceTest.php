<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_order_service_can_create_order_with_items()
    {
        $user = User::factory()->create();
        $role = Role::findByName('Wolfsstufe');
        $item = Item::factory()->create(['stock' => 100, 'item_group_id' => ItemGroup::factory()->create()->id]);

        $service = app(OrderService::class);
        $order = $service->createOrder([
            'event' => 'Service Test',
            'event_start' => now()->addDay()->format('Y-m-d'),
            'event_end' => now()->addDays(2)->format('Y-m-d'),
            'user_id' => $user->id,
            'role_id' => $role->id,
            'items' => [$item->id => 15],
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('Service Test', $order->event);
        $this->assertCount(1, $order->orderedItems);
        $this->assertEquals(15, $order->orderedItems->first()->quantity);
    }

    public function test_order_service_can_update_order_and_sync_items()
    {
        $user = User::factory()->create();
        $role = Role::findByName('Wolfsstufe');
        $item1 = Item::factory()->create(['item_group_id' => ItemGroup::factory()->create()->id]);
        $item2 = Item::factory()->create(['item_group_id' => ItemGroup::factory()->create()->id]);

        $service = app(OrderService::class);
        $order = $service->createOrder([
            'event' => 'Initial',
            'event_start' => now()->format('Y-m-d'),
            'event_end' => now()->addDay()->format('Y-m-d'),
            'user_id' => $user->id,
            'role_id' => $role->id,
            'items' => [$item1->id => 10],
        ]);

        $service->updateOrder($order, [
            'event' => 'Updated',
            'event_start' => now()->format('Y-m-d'),
            'event_end' => now()->addDay()->format('Y-m-d'),
            'user_id' => $user->id,
            'role_id' => $role->id,
            'items' => [$item2->id => 5],
        ]);

        $order->refresh();
        $this->assertEquals('Updated', $order->event);
        $this->assertCount(1, $order->orderedItems);
        $this->assertEquals($item2->id, $order->orderedItems->first()->item_id);
    }
}
