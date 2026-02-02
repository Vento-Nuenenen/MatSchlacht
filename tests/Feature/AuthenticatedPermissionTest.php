<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a guest does not have authenticated permission', function () {
    $this->assertFalse(Gate::allows('authenticated'));
});
