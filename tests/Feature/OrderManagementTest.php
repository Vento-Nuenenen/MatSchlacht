<?php

use App\Models\User;

test('example', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/overwatch');

    $response->assertSuccessful();
});
