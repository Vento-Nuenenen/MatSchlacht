<?php

use App\Models\User;

test('the application returns a successful response', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/overwatch');

    $response->assertStatus(200);
});
