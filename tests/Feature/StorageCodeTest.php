<?php

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\StorageCodeGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    app(ComponentManager::class)->update(['storage' => ['enabled' => true, 'settings' => ['label_caption' => null]]]);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');
});

/**
 * Pfadiheim › Keller › Regal A › Ebene 1
 */
function storageTier(): StorageUnit
{
    $location = StorageUnit::factory()->create(['name' => 'Pfadiheim']);
    $room = StorageUnit::factory()->room()->create(['name' => 'Keller', 'parent_id' => $location->id]);
    $shelf = StorageUnit::factory()->shelf()->create(['name' => 'Regal A', 'parent_id' => $room->id]);

    return StorageUnit::factory()->tier()->create(['name' => 'Ebene 1', 'parent_id' => $shelf->id]);
}

test('a code is derived from the name', function (string $name, string $code) {
    expect(app(StorageCodeGenerator::class)->fromName($name))->toBe($code);
})->with([
    'single word' => ['Pfadiheim', 'PFA'],
    'short word' => ['Ab', 'AB'],
    'several words' => ['Regal A', 'RA'],
    'word and number' => ['Fach 12', 'F12'],
    'only a number' => ['2026', '2026'],
    'umlaut' => ['Überlauf', 'UEB'],
    'special characters' => ['Keller (Nord) & Süd', 'KNS'],
    'nothing usable' => ['***', 'X'],
    'too long' => ['Raum 123456789012', 'R123456789'],
]);

test('new units get a code that is unique below the same parent', function () {
    $location = StorageUnit::factory()->create();
    $otherLocation = StorageUnit::factory()->create();

    $first = StorageUnit::factory()->room()->create(['name' => 'Keller', 'parent_id' => $location->id]);
    $second = StorageUnit::factory()->room()->create(['name' => 'Kellerabteil', 'parent_id' => $location->id]);
    $third = StorageUnit::factory()->room()->create(['name' => 'Kellerraum', 'parent_id' => $location->id]);
    $elsewhere = StorageUnit::factory()->room()->create(['name' => 'Keller', 'parent_id' => $otherLocation->id]);

    expect([$first->code, $second->code, $third->code, $elsewhere->code])->toBe(['KEL', 'KEL2', 'KEL3', 'KEL']);
});

test('a bin is named after the codes of its ancestors and numbered within its tier', function () {
    $tier = storageTier();
    $payload = ['level' => 'bin', 'parent_id' => $tier->id, 'name' => 'Ignoriert', 'code' => 'NOPE'];

    $this->actingAs($this->materialwart)->post(route('storage-units.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('storage-units.store'), $payload)->assertSessionHasNoErrors();

    expect(StorageUnit::ofLevel(StorageLevel::Bin)->orderBy('id')->pluck('name')->all())
        ->toBe(['PFA-KEL-RA-E1-01', 'PFA-KEL-RA-E1-02']);
});

test('a chosen code is used in capital letters', function () {
    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => 'location', 'name' => 'Pfadiheim', 'code' => 'ph1'])
        ->assertSessionHasNoErrors();

    expect(StorageUnit::sole()->code)->toBe('PH1');
});

test('renaming a unit keeps its code and an emptied code is derived again', function () {
    $location = StorageUnit::factory()->create(['name' => 'Pfadiheim']);

    $this->actingAs($this->materialwart)
        ->put(route('storage-units.update', $location), ['name' => 'Materiallager', 'code' => 'PFA'])
        ->assertSessionHasNoErrors();
    expect($location->fresh()->code)->toBe('PFA');

    $this->put(route('storage-units.update', $location), ['name' => 'Materiallager', 'code' => ''])
        ->assertSessionHasNoErrors();
    expect($location->fresh()->code)->toBe('MAT');
});

test('changing a code renames the bins below it', function () {
    $tier = storageTier();
    $bin = StorageUnit::factory()->bin()->create(['name' => null, 'parent_id' => $tier->id]);
    $room = $tier->parent->parent;

    $this->actingAs($this->materialwart)
        ->put(route('storage-units.update', $room), ['parent_id' => $room->parent_id, 'name' => 'Keller', 'code' => 'UG'])
        ->assertSessionHasNoErrors();

    expect($bin->fresh()->name)->toBe('PFA-UG-RA-E1-01');
});

test('moving a unit renames the bins below it', function () {
    $tier = storageTier();
    $bin = StorageUnit::factory()->bin()->create(['name' => null, 'parent_id' => $tier->id]);
    $shelf = $tier->parent;
    $attic = StorageUnit::factory()->room()->create(['name' => 'Estrich', 'parent_id' => $shelf->parent->parent_id]);

    $this->actingAs($this->materialwart)
        ->put(route('storage-units.update', $shelf), ['parent_id' => $attic->id, 'name' => 'Regal A', 'code' => 'RA'])
        ->assertSessionHasNoErrors();

    expect($bin->fresh()->name)->toBe('PFA-EST-RA-E1-01');
});

test('a moved bin gets the next number of its new tier', function () {
    $tier = storageTier();
    $otherTier = StorageUnit::factory()->tier()->create(['name' => 'Ebene 2', 'parent_id' => $tier->parent_id]);
    StorageUnit::factory()->bin()->create(['name' => null, 'parent_id' => $otherTier->id]);
    $bin = StorageUnit::factory()->bin()->create(['name' => null, 'parent_id' => $tier->id]);

    $this->actingAs($this->materialwart)
        ->put(route('storage-units.update', $bin), ['parent_id' => $otherTier->id])
        ->assertSessionHasNoErrors();

    expect($bin->fresh())
        ->code->toBe('02')
        ->name->toBe('PFA-KEL-RA-E2-02');
});

test('an invalid code is rejected', function (string $code, string $message) {
    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => 'location', 'name' => 'Pfadiheim', 'code' => $code])
        ->assertSessionHasErrors(['code' => $message]);

    expect(StorageUnit::count())->toBe(0);
})->with([
    'separator' => ['PF-A', 'Der Code darf nur Buchstaben von A bis Z und Ziffern enthalten.'],
    'umlaut' => ['BÄR', 'Der Code darf nur Buchstaben von A bis Z und Ziffern enthalten.'],
]);

test('a code that is too long is rejected', function () {
    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => 'location', 'name' => 'Pfadiheim', 'code' => 'ABCDEFGHIJK'])
        ->assertSessionHasErrors('code');
});

test('a code can only be used once below the same parent', function (bool $isTopLevel) {
    $parent = $isTopLevel ? null : StorageUnit::factory()->create();
    $level = $isTopLevel ? 'location' : 'room';
    StorageUnit::factory()->create(['level' => $level, 'parent_id' => $parent?->id, 'code' => 'ABC']);

    $this->actingAs($this->materialwart)
        ->post(route('storage-units.store'), ['level' => $level, 'parent_id' => $parent?->id, 'name' => 'Neu', 'code' => 'abc'])
        ->assertSessionHasErrors(['code' => 'Dieser Code wird in derselben übergeordneten Lagereinheit schon verwendet.']);
})->with(['top level' => true, 'below a parent' => false]);

test('a unit keeps its own code when it is saved again', function () {
    $location = StorageUnit::factory()->create(['code' => 'ABC']);

    $this->actingAs($this->materialwart)
        ->put(route('storage-units.update', $location), ['name' => 'Neu', 'code' => 'ABC'])
        ->assertSessionHasNoErrors();
});

test('the forms ask for a code on every level except bins', function () {
    $tier = storageTier();

    $this->actingAs($this->materialwart)
        ->get(route('storage-units.create', ['parent' => $tier->parent_id]))
        ->assertSuccessful()
        ->assertSee('name="code"', false);

    $this->get(route('storage-units.create', ['parent' => $tier->id]))
        ->assertSuccessful()
        ->assertDontSee('name="code"', false)
        ->assertDontSee('name="name"', false);
});

test('the migration gives existing units codes and renames bins', function () {
    $migration = require collect(glob(database_path('migrations/*add_code_to_storage_units_table.php')))->sole();
    $migration->down();

    $now = now();
    $unit = fn (?int $parentId, string $level, string $name, ?string $description = null) => DB::table('storage_units')->insertGetId([
        'parent_id' => $parentId, 'level' => $level, 'name' => $name, 'description' => $description, 'created_at' => $now, 'updated_at' => $now,
    ]);

    $location = $unit(null, 'location', 'Pfadiheim');
    $room = $unit($location, 'room', 'Keller');
    $sameCodeRoom = $unit($location, 'room', 'Kellerabteil');
    $shelf = $unit($room, 'shelf', 'Regal A');
    $tier = $unit($shelf, 'tier', 'Ebene 1');
    $laterBin = $unit($tier, 'bin', 'Fach 10', 'Zelte');
    $earlierBin = $unit($tier, 'bin', 'Fach 2');

    $migration->up();

    expect(StorageUnit::find($sameCodeRoom)->code)->toBe('KEL2')
        ->and(StorageUnit::find($earlierBin))
        ->name->toBe('PFA-KEL-RA-E1-01')
        ->description->toBe('Fach 2')
        ->and(StorageUnit::find($laterBin))
        ->name->toBe('PFA-KEL-RA-E1-02')
        ->code->toBe('02')
        ->description->toBe('Fach 10 – Zelte');
});
