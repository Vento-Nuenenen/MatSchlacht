<?php

use App\Enums\MaintenanceType;
use App\Mail\MaintenanceDueReminder;
use App\Models\Item;
use App\Models\ItemMaintenance;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\MaintenanceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    configureMaintenance();

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');
});

/**
 * @param  array<string, mixed>  $settings
 */
function configureMaintenance(bool $enabled = true, array $settings = []): void
{
    app(ComponentManager::class)->update(['maintenance' => ['enabled' => $enabled, 'settings' => array_merge([
        'reminders' => true,
        'reminder_days_ahead' => 30,
        'reminder_recipients' => 'wartung@example.com',
    ], $settings)]]);
}

function itemMaintainedMonthsAgo(?int $monthsAgo, int $intervalMonths = 12): Item
{
    $item = Item::factory()->create(['maintenance_interval_months' => $intervalMonths]);

    if ($monthsAgo !== null) {
        ItemMaintenance::factory()->create(['item_id' => $item->id, 'performed_on' => now()->subMonthsNoOverflow($monthsAgo)->format('Y-m-d')]);
    }

    return $item;
}

test('maintenance is not reachable while the component is disabled', function () {
    configureMaintenance(enabled: false);

    $this->actingAs($this->materialwart)->get(route('maintenance.index'))->assertNotFound();
});

test('the materialwart can record a maintenance', function () {
    $item = Item::factory()->create();

    $this->actingAs($this->materialwart)
        ->post(route('items.maintenances.store', $item), [
            'performed_on' => now()->format('Y-m-d'),
            'type' => MaintenanceType::Repair->value,
            'quantity' => 2,
            'cost' => '45.50',
            'description' => 'Reissverschluss ersetzt',
        ])
        ->assertSessionHasNoErrors();

    $maintenance = ItemMaintenance::sole();
    expect($maintenance->type)->toBe(MaintenanceType::Repair)
        ->and($maintenance->cost_cents)->toBe(4550)
        ->and($maintenance->user_id)->toBe($this->materialwart->id);

    $this->actingAs($this->materialwart)
        ->get(route('items.edit', $item))
        ->assertSuccessful()
        ->assertSee('Reissverschluss ersetzt');
});

test('leaders can see the maintenance overview but not record maintenance', function () {
    $item = Item::factory()->create();

    $this->actingAs($this->leader)->get(route('maintenance.index'))->assertSuccessful();
    $this->actingAs($this->leader)
        ->post(route('items.maintenances.store', $item), ['performed_on' => now()->format('Y-m-d'), 'type' => 'inspection'])
        ->assertForbidden();

    expect(ItemMaintenance::count())->toBe(0);
});

test('the maintenance interval is saved with the item', function () {
    $item = Item::factory()->create(['lot' => 1, 'repair' => 0]);

    $this->actingAs($this->materialwart)
        ->put(route('items.update', $item), [
            'name' => $item->name,
            'item_group_id' => $item->item_group_id,
            'stock' => $item->stock,
            'lot' => 1,
            'repair' => 0,
            'maintenance_interval_months' => 6,
        ])
        ->assertSessionHasNoErrors();

    expect($item->fresh()->maintenance_interval_months)->toBe(6);
});

test('the maintenance status follows the interval and the last maintenance', function (?int $monthsAgo, string $expectedStatus) {
    $item = itemMaintainedMonthsAgo($monthsAgo);

    expect(app(MaintenanceService::class)->status($item))->toBe($expectedStatus);
})->with([
    'never maintained' => [null, MaintenanceService::STATUS_DUE],
    'interval passed' => [13, MaintenanceService::STATUS_OVERDUE],
    'due within the next days' => [12, MaintenanceService::STATUS_DUE],
    'recently maintained' => [3, MaintenanceService::STATUS_OK],
]);

test('items without an interval have no maintenance status', function () {
    $item = Item::factory()->create(['maintenance_interval_months' => null]);

    expect(app(MaintenanceService::class)->status($item))->toBeNull();
});

test('the overview can be filtered to due maintenance', function () {
    $overdueItem = itemMaintainedMonthsAgo(13);
    $okItem = itemMaintainedMonthsAgo(3);

    $this->actingAs($this->materialwart)
        ->get(route('maintenance.index', ['due' => 1]))
        ->assertSuccessful()
        ->assertSee($overdueItem->name)
        ->assertDontSee($okItem->name);

    $this->actingAs($this->materialwart)
        ->get(route('overwatch'))
        ->assertSee('Wartung fällig');
});

test('the reminder mail lists the due items', function () {
    Mail::fake();
    $overdueItem = itemMaintainedMonthsAgo(13);
    itemMaintainedMonthsAgo(3);

    $this->artisan('maintenance:remind')->assertSuccessful();

    Mail::assertSent(MaintenanceDueReminder::class, fn (MaintenanceDueReminder $mail) => $mail->hasTo('wartung@example.com')
        && $mail->items->pluck('id')->all() === [$overdueItem->id]
        && str_contains($mail->render(), $overdueItem->name));
});

test('no reminder mail is sent when nothing is due or reminders are off', function (bool $isAnythingDue, bool $areRemindersEnabled) {
    Mail::fake();
    configureMaintenance(settings: ['reminders' => $areRemindersEnabled]);
    itemMaintainedMonthsAgo($isAnythingDue ? 13 : 3);

    $this->artisan('maintenance:remind')->assertSuccessful();

    Mail::assertNothingSent();
})->with([
    'nothing due' => [false, true],
    'reminders off' => [true, false],
]);

test('a maintenance entry can be edited with photos and keeps its author', function () {
    Storage::fake('s3');
    $author = User::factory()->create();
    $maintenance = ItemMaintenance::factory()->create(['user_id' => $author->id, 'type' => MaintenanceType::Inspection]);
    $photo = $maintenance->attachments()->create(['path' => UploadedFile::fake()->image('vorher.jpg')->store('maintenances', 's3')]);

    $this->actingAs($this->materialwart)
        ->get(route('items.maintenances.edit', $maintenance))
        ->assertSuccessful()
        ->assertSee(basename($photo->path));

    $this->actingAs($this->materialwart)
        ->put(route('items.maintenances.update', $maintenance), [
            'performed_on' => now()->subDay()->format('Y-m-d'),
            'type' => MaintenanceType::Repair->value,
            'description' => 'Stange geschweisst',
            'remove_attachments' => [$photo->id],
            'files' => [UploadedFile::fake()->image('nachher.jpg')],
        ])
        ->assertRedirect(route('items.edit', $maintenance->item_id));

    $maintenance->refresh();
    expect($maintenance->type)->toBe(MaintenanceType::Repair)
        ->and($maintenance->description)->toBe('Stange geschweisst')
        ->and($maintenance->user_id)->toBe($author->id)
        ->and($maintenance->attachments)->toHaveCount(1);
    Storage::disk('s3')->assertMissing($photo->path);
    Storage::disk('s3')->assertExists($maintenance->attachments->first()->path);

    $this->actingAs($this->materialwart)->delete(route('items.maintenances.destroy', $maintenance));

    Storage::disk('s3')->assertMissing($maintenance->attachments->first()->path);
});

test('leaders cannot edit maintenance entries', function () {
    $maintenance = ItemMaintenance::factory()->create(['description' => 'Original']);

    $this->actingAs($this->leader)->get(route('items.maintenances.edit', $maintenance))->assertForbidden();
    $this->actingAs($this->leader)
        ->put(route('items.maintenances.update', $maintenance), ['performed_on' => now()->format('Y-m-d'), 'type' => 'other', 'description' => 'Geändert'])
        ->assertForbidden();

    expect($maintenance->fresh()->description)->toBe('Original');
});
