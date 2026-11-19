<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_without_session_returns_401_even_without_json_header(): void
    {
        $this->get('/api/me')->assertUnauthorized();
    }

    public function test_login_with_session_cookie_and_logout(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'segreta123']);
        $headers = ['Referer' => config('app.url'), 'Origin' => config('app.url')];

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'sbagliata'], $headers)->assertJsonValidationErrors('email');
        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'segreta123'], $headers)->assertOk()->assertJsonPath('data.email', 'a@example.com');

        $this->getJson('/api/me', $headers)->assertOk()->assertJsonPath('data.id', $user->id);
        $this->postJson('/api/logout', [], $headers)->assertNoContent();
    }
}
