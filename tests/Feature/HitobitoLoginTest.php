<?php

use App\Http\Controllers\HitobitoTestController;
use App\Models\HitobitoAccessRule;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\Hitobito\HitobitoConnector;
use App\Services\Hitobito\HitobitoProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    enableHitobito();
});

function enableHitobito(array $settings = []): void
{
    app(ComponentManager::class)->update(['hitobito_login' => ['enabled' => true, 'settings' => [
        'button_label' => 'Login mit MiData',
        'base_url' => 'https://hitobito.test',
        'client_id' => 'client',
        'client_secret' => 'secret',
        'sync_group_roles' => true,
        ...$settings,
    ]]]);
}

/**
 * @return array<string, mixed>
 */
function hitobitoProfile(array $overrides = []): array
{
    return [
        'id' => 42,
        'email' => 'lea@example.com',
        'first_name' => 'Lea',
        'last_name' => 'Muster',
        'nickname' => 'Flamme',
        'roles' => [
            ['group_id' => 7, 'group_name' => 'Wölfe', 'role_name' => 'Einheitsleiter*in', 'role_class' => 'Group::Woelfe::Einheitsleitung', 'permissions' => ['group_full']],
            ['group_id' => 9, 'group_name' => 'Abteilung', 'role_name' => 'Mitglied', 'role_class' => 'Group::Abteilung::Mitglied', 'permissions' => ['group_read']],
        ],
        ...$overrides,
    ];
}

function fakeHitobitoLogin(array $profile): void
{
    $socialiteUser = (new SocialiteUser)->setRaw($profile)->map([
        'id' => (string) $profile['id'],
        'email' => $profile['email'] ?? null,
        'nickname' => $profile['nickname'] ?? null,
    ]);

    $provider = Mockery::mock(HitobitoProvider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);

    $connector = Mockery::mock(HitobitoConnector::class);
    $connector->shouldReceive('provider')->andReturn($provider);
    $connector->shouldReceive('isConfigured')->andReturn(true);
    $connector->shouldReceive('baseUrl')->andReturn('https://hitobito.test');
    app()->instance(HitobitoConnector::class, $connector);
}

test('a person matching a rule is admitted', function (array $criteria) {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => null, ...$criteria]);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'))->assertRedirect(route('overwatch'));

    $this->assertAuthenticatedAs(User::where('hitobito_id', '42')->sole());
    expect(User::sole())
        ->firstname->toBe('Lea')
        ->lastname->toBe('Muster')
        ->scoutname->toBe('Flamme')
        ->email->toBe('lea@example.com');
})->with([
    'person id' => [['hitobito_person_id' => '42']],
    'group' => [['hitobito_group_id' => '7']],
    'role' => [['hitobito_role_class' => 'Group::Woelfe::Einheitsleitung']],
    'permission' => [['hitobito_permission' => 'group_full']],
    'group and role on the same hitobito role' => [['hitobito_group_id' => '7', 'hitobito_role_class' => 'Group::Woelfe::Einheitsleitung']],
    'person and group' => [['hitobito_person_id' => '42', 'hitobito_group_id' => '9']],
]);

test('a person without a matching rule is refused', function (array $criteria) {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => null, ...$criteria]);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Dein Hitobito-Konto ist für diese App nicht freigeschaltet.');

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'other person' => [['hitobito_person_id' => '43']],
    'other group' => [['hitobito_group_id' => '8']],
    'other role' => [['hitobito_role_class' => 'Group::Abteilung::Admin']],
    'other permission' => [['hitobito_permission' => 'layer_and_below_full']],
    'group and permission of two different roles' => [['hitobito_group_id' => '9', 'hitobito_permission' => 'group_full']],
    'right group but other person' => [['hitobito_person_id' => '43', 'hitobito_group_id' => '7']],
    'a rule without criteria' => [[]],
]);

test('nobody is admitted without rules', function () {
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('a linked user loses access when no rule applies anymore', function () {
    User::factory()->create(['hitobito_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile(['roles' => []]));
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => null, 'hitobito_group_id' => '7']);

    $this->get(route('hitobito.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the app roles of all matching rules are assigned', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42', 'role_name' => 'Wolfsstufe']);
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => null, 'hitobito_group_id' => '9', 'role_name' => 'Abteilungsleitung']);
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '43', 'role_name' => 'Materialwart']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    expect(User::sole()->getRoleNames()->sort()->values()->all())->toBe(['Abteilungsleitung', 'Wolfsstufe']);
});

test('existing roles stay when no matching rule gives a role or the sync is off', function (?string $ruleRole, bool $isSyncEnabled) {
    enableHitobito(['sync_group_roles' => $isSyncEnabled]);
    User::factory()->create(['hitobito_id' => '42'])->assignRole('Pfadistufe');
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42', 'role_name' => $ruleRole]);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    expect(User::sole()->getRoleNames()->all())->toBe(['Pfadistufe']);
})->with([
    'rule without role' => [null, true],
    'sync turned off' => ['Wolfsstufe', false],
]);

test('an account with the same e-mail address is linked', function () {
    $user = User::factory()->create(['email' => 'lea@example.com']);
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->hitobito_id)->toBe('42');
});

test('an account of another hitobito person is not taken over through its e-mail address', function () {
    $other = User::factory()->create(['email' => 'lea@example.com', 'hitobito_id' => '99']);
    $own = User::factory()->create(['email' => 'alt@example.com', 'hitobito_id' => '42']);
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    $this->assertAuthenticatedAs($own);
    expect($own->fresh()->email)->toBe('alt@example.com')
        ->and($other->fresh()->hitobito_id)->toBe('99');
});

test('a hitobito account without e-mail address is refused', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile(['email' => null]));

    $this->get(route('hitobito.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Dein Hitobito-Konto hat keine E-Mail-Adresse.');

    $this->assertGuest();
});

test('a failed authentication is reported', function () {
    $provider = Mockery::mock(HitobitoProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);
    $connector = Mockery::mock(HitobitoConnector::class);
    $connector->shouldReceive('provider')->andReturn($provider);
    app()->instance(HitobitoConnector::class, $connector);

    $this->get(route('hitobito.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Die Anmeldung mit Hitobito ist fehlgeschlagen.');
});

test('the login redirects to the configured instance with the roles scope', function () {
    $response = $this->get(route('hitobito.login'))->assertRedirect();

    $location = $response->headers->get('Location');

    expect($location)->toStartWith('https://hitobito.test/oauth/authorize?')
        ->toContain('client_id=client')
        ->toContain('scope=with_roles')
        ->toContain('redirect_uri='.urlencode(route('hitobito.callback')));
});

test('the profile is requested with the roles scope', function () {
    $history = [];
    $handler = HandlerStack::create(new MockHandler([new Response(200, [], json_encode(hitobitoProfile()))]));
    $handler->push(Middleware::history($history));

    $user = (new HitobitoProvider(Request::create('/'), 'client', 'secret', 'https://app.test/callback'))
        ->setBaseUrl('https://hitobito.test/')
        ->setHttpClient(new Client(['handler' => $handler]))
        ->userFromToken('token');

    $request = $history[0]['request'];

    expect((string) $request->getUri())->toBe('https://hitobito.test/oauth/profile')
        ->and($request->getHeaderLine('X-Scope'))->toBe('with_roles')
        ->and($request->getHeaderLine('Authorization'))->toBe('Bearer token')
        ->and($user->getId())->toBe('42')
        ->and($user->getEmail())->toBe('lea@example.com');
});

test('the login is only offered once it is configured', function () {
    $this->get(route('login'))->assertSee('Login mit MiData');

    enableHitobito(['client_id' => '']);

    $this->get(route('login'))->assertDontSee('Login mit MiData');
    $this->get(route('hitobito.login'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Der Hitobito-Login ist noch nicht eingerichtet.');
});

test('the login and the rules are not reachable while the component is disabled', function () {
    app(ComponentManager::class)->update(['hitobito_login' => ['enabled' => false, 'settings' => ['base_url' => 'https://hitobito.test']]]);
    $materialwart = User::factory()->create()->assignRole('Materialwart');

    $this->get(route('hitobito.login'))->assertNotFound();
    $this->get(route('hitobito.callback'))->assertNotFound();
    $this->actingAs($materialwart)->get(route('hitobito-access-rules.index'))->assertNotFound();
});

test('the materialwart manages the access rules', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'));

    $this->post(route('hitobito-access-rules.store'), ['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe', 'description' => 'Wölfe'])
        ->assertRedirect(route('hitobito-access-rules.index'));
    $rule = HitobitoAccessRule::sole();

    $this->get(route('hitobito-access-rules.index'))->assertSuccessful()->assertSee('Wölfe');
    $this->get(route('hitobito-access-rules.edit', $rule))->assertSuccessful()->assertSee('Wolfsstufe');

    $this->put(route('hitobito-access-rules.update', $rule), ['hitobito_group_id' => '', 'hitobito_permission' => 'group_full'])
        ->assertSessionHasNoErrors();
    expect($rule->fresh())
        ->hitobito_permission->toBe('group_full')
        ->hitobito_group_id->toBeNull();

    $this->delete(route('hitobito-access-rules.destroy', $rule));
    expect(HitobitoAccessRule::count())->toBe(0);
});

test('a rule needs at least one criterion', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('hitobito-access-rules.store'), ['role_name' => 'Wolfsstufe'])
        ->assertSessionHasErrors(['hitobito_person_id' => 'Gib mindestens eine Personen-ID, Gruppen-ID, Rolle oder Berechtigung an.']);

    expect(HitobitoAccessRule::count())->toBe(0);
});

test('a rule cannot give an unknown app role', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('hitobito-access-rules.store'), ['hitobito_group_id' => '7', 'role_name' => 'Gibt es nicht'])
        ->assertSessionHasErrors('role_name');
});

test('leaders cannot manage the access rules', function () {
    $this->actingAs(User::factory()->create()->assignRole('Wolfsstufe'))
        ->get(route('hitobito-access-rules.index'))
        ->assertForbidden();
});

test('hitobito users have no password in the app', function () {
    $user = User::factory()->create(['hitobito_id' => '42']);

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('users.password-reset', $user))
        ->assertSessionHas('error', 'Passwort-Reset für Benutzer mit externem Login nicht möglich.');

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSee('Hitobito Benutzer')
        ->assertDontSee('name="current_password"', false);
});

/**
 * A rule that only gives an app role, as the former group role mappings did.
 */
function hitobitoRoleRule(array $attributes): HitobitoAccessRule
{
    return HitobitoAccessRule::factory()->create(['hitobito_person_id' => null, 'grants_access' => false, ...$attributes]);
}

test('rules without access give the app roles of the hitobito groups', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    hitobitoRoleRule(['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe']);
    hitobitoRoleRule(['hitobito_group_id' => '9', 'hitobito_role_class' => 'Group::Abteilung::Mitglied', 'role_name' => 'Pfadistufe']);
    hitobitoRoleRule(['hitobito_group_id' => '7', 'hitobito_role_class' => 'Group::Abteilung::Mitglied', 'role_name' => 'Abteilungsleitung']);
    hitobitoRoleRule(['hitobito_group_id' => '8', 'role_name' => 'Materialwart']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    expect(User::sole()->getRoleNames()->sort()->values()->all())->toBe(['Pfadistufe', 'Wolfsstufe']);
});

test('roles from rules with and without access are combined', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42', 'role_name' => 'Abteilungsleitung']);
    hitobitoRoleRule(['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'));

    expect(User::sole()->getRoleNames()->sort()->values()->all())->toBe(['Abteilungsleitung', 'Wolfsstufe']);
});

test('a rule without access does not admit anybody', function () {
    hitobitoRoleRule(['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

test('a rule with a deleted role is skipped', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    hitobitoRoleRule(['hitobito_group_id' => '7', 'role_name' => 'Gibt es nicht mehr']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->get(route('hitobito.callback'))->assertRedirect(route('overwatch'));

    expect(User::sole()->getRoleNames()->all())->toBe([]);
});

test('the materialwart manages rules without access', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'));

    $this->post(route('hitobito-access-rules.store'), ['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe', 'grants_access' => '0'])
        ->assertRedirect(route('hitobito-access-rules.index'));
    $rule = HitobitoAccessRule::sole();
    expect($rule->grants_access)->toBeFalse();

    $this->get(route('hitobito-access-rules.index'))->assertSuccessful()->assertSee('Nur Rolle');
    $this->get(route('hitobito-access-rules.edit', $rule))->assertSuccessful();

    $this->put(route('hitobito-access-rules.update', $rule), ['hitobito_group_id' => '8', 'role_name' => 'Pfadistufe', 'grants_access' => '1'])
        ->assertSessionHasNoErrors();
    expect($rule->fresh())->hitobito_group_id->toBe('8')->role_name->toBe('Pfadistufe')->grants_access->toBeTrue();
});

test('a rule without access needs an app role', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('hitobito-access-rules.store'), ['hitobito_group_id' => '7', 'role_name' => '', 'grants_access' => '0'])
        ->assertSessionHasErrors(['role_name' => 'Eine Regel ohne Zugang muss eine App-Rolle vergeben.']);

    expect(HitobitoAccessRule::count())->toBe(0);
});

test('leaders cannot use the test page', function () {
    $this->actingAs(User::factory()->create()->assignRole('Wolfsstufe'))
        ->get(route('hitobito-test.index'))
        ->assertForbidden();
});

test('leaders cannot start a test', function () {
    $this->actingAs(User::factory()->create()->assignRole('Wolfsstufe'))
        ->post(route('hitobito-test.start'))
        ->assertForbidden();

    expect(session()->has(HitobitoTestController::SESSION_KEY))->toBeFalse();
});

test('a test login shows the data without logging in or creating an account', function () {
    $materialwart = User::factory()->create()->assignRole('Materialwart');
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    hitobitoRoleRule(['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->actingAs($materialwart)
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->followingRedirects()
        ->get(route('hitobito.callback'))
        ->assertSuccessful()
        ->assertSee('Zugelassen')
        ->assertSee('Group::Woelfe::Einheitsleitung')
        ->assertSee('group_full')
        ->assertSee('Wolfsstufe')
        ->assertSee('Ein neues Konto würde angelegt.')
        ->assertSee('lea@example.com');

    $this->assertAuthenticatedAs($materialwart);
    expect(User::count())->toBe(1)
        ->and($materialwart->fresh())->hitobito_id->toBeNull()
        ->and($materialwart->fresh()->getRoleNames()->all())->toBe(['Materialwart']);
});

test('a test login shows when a person would be refused', function () {
    fakeHitobitoLogin(hitobitoProfile());

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->followingRedirects()
        ->get(route('hitobito.callback'))
        ->assertSee('Abgelehnt');
});

test('the test data is escaped', function () {
    fakeHitobitoLogin(hitobitoProfile(['nickname' => '<script>alert(1)</script>']));

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->followingRedirects()
        ->get(route('hitobito.callback'))
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('the test mark is ignored for users without the permission', function () {
    $leader = User::factory()->create(['hitobito_id' => '42'])->assignRole('Wolfsstufe');
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->actingAs($leader)
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->get(route('hitobito.callback'))
        ->assertRedirect(route('overwatch'))
        ->assertSessionMissing(HitobitoTestController::RESULT_KEY);
});

test('a test only applies to the next callback', function () {
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    fakeHitobitoLogin(hitobitoProfile());

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->get(route('hitobito.callback'))
        ->assertRedirect(route('hitobito-test.index'));

    expect(session()->has(HitobitoTestController::SESSION_KEY))->toBeFalse();
});

test('a failed test returns to the test page with the reason', function () {
    $provider = Mockery::mock(HitobitoProvider::class);
    $provider->shouldReceive('user')->andThrow(new RuntimeException('invalid_client'));
    $connector = Mockery::mock(HitobitoConnector::class);
    $connector->shouldReceive('provider')->andReturn($provider);
    app()->instance(HitobitoConnector::class, $connector);

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->withSession([HitobitoTestController::SESSION_KEY => true])
        ->get(route('hitobito.callback'))
        ->assertRedirect(route('hitobito-test.index'))
        ->assertSessionHas('error', 'Die Testanmeldung ist fehlgeschlagen: invalid_client');
});

test('starting a test redirects to hitobito and marks the session', function () {
    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('hitobito-test.start'))
        ->assertRedirectContains('https://hitobito.test/oauth/authorize')
        ->assertSessionHas(HitobitoTestController::SESSION_KEY, true);
});

test('a test needs the credentials', function () {
    enableHitobito(['client_id' => '']);

    $this->actingAs(User::factory()->create()->assignRole('Materialwart'))
        ->post(route('hitobito-test.start'))
        ->assertRedirect(route('hitobito-test.index'))
        ->assertSessionHas('error', 'Trage zuerst Client-ID und Client-Secret in den Einstellungen ein.');
});

test('a pasted link to the sign-in page still points to the instance', function (string $url, string $baseUrl) {
    enableHitobito(['base_url' => $url]);

    expect(app(HitobitoConnector::class)->baseUrl())->toBe($baseUrl);
})->with([
    'sign-in page' => ['https://pbs.puzzle.ch/de/users/sign_in', 'https://pbs.puzzle.ch'],
    'trailing slash' => ['https://pbs.puzzle.ch/', 'https://pbs.puzzle.ch'],
    'with port' => ['https://hitobito.test:8443/', 'https://hitobito.test:8443'],
]);

/**
 * The mappings table as an earlier Hitobito branch created it.
 */
function createLegacyMappingsTable(): void
{
    Schema::dropIfExists('hitobito_group_role_mappings');
    Schema::create('hitobito_group_role_mappings', function (Blueprint $table) {
        $table->id();
        $table->string('hitobito_group_id')->nullable();
        $table->string('hitobito_role_name')->nullable();
        $table->string('role_name');
        $table->timestamps();
    });
}

function hitobitoMigration(string $name): object
{
    return require collect(glob(database_path("migrations/*{$name}.php")))->sole();
}

test('the migrations work on a database of the earlier hitobito branch', function () {
    createLegacyMappingsTable();

    hitobitoMigration('add_hitobito_id_to_users_table')->up();
    hitobitoMigration('create_hitobito_group_role_mappings_table')->up();

    expect(Schema::hasColumn('hitobito_group_role_mappings', 'hitobito_role_class'))->toBeTrue()
        ->and(Schema::hasColumn('hitobito_group_role_mappings', 'hitobito_role_name'))->toBeFalse();
});

test('a filled table of the earlier hitobito branch is not replaced', function () {
    createLegacyMappingsTable();
    DB::table('hitobito_group_role_mappings')->insert(['hitobito_group_id' => '7', 'role_name' => 'Wolfsstufe']);

    expect(fn () => hitobitoMigration('create_hitobito_group_role_mappings_table')->up())->toThrow(QueryException::class);

    expect(DB::table('hitobito_group_role_mappings')->count())->toBe(1);
});

test('mappings become rules that do not admit anybody and back', function () {
    $migration = hitobitoMigration('merge_hitobito_group_role_mappings_into_access_rules');
    HitobitoAccessRule::factory()->create(['hitobito_person_id' => '42']);
    $migration->down();
    DB::table('hitobito_group_role_mappings')->insert(['hitobito_group_id' => '7', 'hitobito_role_class' => 'Group::Woelfe::Einheitsleitung', 'role_name' => 'Wolfsstufe']);

    $migration->up();

    expect(Schema::hasTable('hitobito_group_role_mappings'))->toBeFalse()
        ->and(HitobitoAccessRule::where('hitobito_group_id', '7')->sole())
        ->hitobito_role_class->toBe('Group::Woelfe::Einheitsleitung')
        ->role_name->toBe('Wolfsstufe')
        ->grants_access->toBeFalse()
        ->and(HitobitoAccessRule::where('hitobito_person_id', '42')->sole()->grants_access)->toBeTrue();

    hitobitoRoleRule(['hitobito_permission' => 'group_full', 'role_name' => 'Pfadistufe']);
    $migration->down();

    expect(DB::table('hitobito_group_role_mappings')->pluck('role_name')->all())->toBe(['Wolfsstufe'])
        ->and(DB::table('hitobito_access_rules')->pluck('hitobito_person_id')->all())->toBe(['42']);

    $migration->up();
});
