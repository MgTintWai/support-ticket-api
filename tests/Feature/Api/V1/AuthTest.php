<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs in and returns a bearer token', function () {
    $user = User::factory()->agent()->create([
        'email' => 'agent@example.com',
        'password' => Hash::make('secret'),
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'agent@example.com',
        'password' => 'secret',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.token_type', 'Bearer');

    expect($response->json('data.token'))->not->toBeEmpty();
});

it('revokes the current token on logout', function () {
    $user = User::factory()->agent()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->fresh()->tokens)->toHaveCount(0);
});
