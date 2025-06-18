<?php

test('new users can register', function () {
    $response = $this->post('/api/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'message',
        'user' => ['id', 'first_name', 'last_name', 'full_name', 'email', 'email_verified_at'],
        'token'
    ]);
    
    $this->assertDatabaseHas('users', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
    ]);
});
