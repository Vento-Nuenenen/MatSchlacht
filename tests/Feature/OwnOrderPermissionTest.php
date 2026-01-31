<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\States\Order\Stored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OwnOrderPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('view orders');
        Permission::findOrCreate('edit orders');
        Permission::findOrCreate('delete orders');
        Permission::findOrCreate('edit own orders');
        Permission::findOrCreate('delete own orders');
    }

    public function test_user_can_edit_own_order_with_edit_own_orders_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('edit own orders');

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->get(route('orders.edit', $order));

        $response->assertStatus(200);
    }

    public function test_edit_button_visible_for_own_order_with_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('edit own orders');

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index'));
        $response->assertSee(route('orders.edit', $order));

        $response = $this->actingAs($user)->get(route('orders.show', $order));
        $response->assertSee(route('orders.edit', $order));
    }

    public function test_edit_button_not_visible_for_others_order_with_only_own_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('edit own orders');

        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->get(route('orders.index'));
        $response->assertDontSee(route('orders.edit', $order));

        $response = $this->actingAs($user)->get(route('orders.show', $order));
        $response->assertDontSee(route('orders.edit', $order));
    }

    public function test_user_cannot_edit_others_order_with_only_edit_own_orders_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('edit own orders');

        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->get(route('orders.edit', $order));

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_order_with_delete_own_orders_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('delete own orders');

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_delete_others_order_with_only_delete_own_orders_permission()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('delete own orders');

        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

        $response->assertStatus(403);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_user_with_general_edit_permission_can_edit_any_order()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('view orders');
        $user->givePermissionTo('edit orders');

        $otherUser = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $otherUser->id,
            'status' => Stored::class,
        ]);

        $response = $this->actingAs($user)->get(route('orders.edit', $order));

        $response->assertStatus(200);
    }
}
