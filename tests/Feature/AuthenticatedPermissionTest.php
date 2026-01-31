<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('an authenticated user has authenticated permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();

    $this->actingAs($user)->get('/overwatch');

    $this->assertTrue($user->can('authenticated'));
    $this->assertTrue(Gate::allows('authenticated'));
});

test('a guest does not have authenticated permission', function () {
    $this->assertFalse(Gate::allows('authenticated'));
});
