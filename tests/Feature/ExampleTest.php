<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('the application returns a successful response', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/overwatch');

    $response->assertStatus(200);
});
