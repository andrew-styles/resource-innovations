<?php

namespace Tests\Feature;

use App\Models\OTPToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SecretsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_secrets_page(): void
    {
        $response = $this->get('/secrets');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_secrets_page_with_their_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $token1 = $user->otpTokens()->create([
            'name' => 'My Secret 1',
            'token' => Str::random(64),
            'abilities' => 'read',
            'expires_at' => now()->addDays(7),
        ]);

        $otherToken = $otherUser->otpTokens()->create([
            'name' => 'Other Secret',
            'token' => Str::random(64),
            'abilities' => 'write',
        ]);

        $response = $this->actingAs($user)->get('/secrets');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Secrets')
            ->has('secrets', 1)
            ->where('secrets.0.id', $token1->id)
            ->where('secrets.0.name', 'My Secret 1')
            ->has('tokens', 1)
            ->where('tokens.0.id', $token1->id)
        );
    }

    public function test_user_can_create_a_new_secret_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/secrets', [
            'name' => 'CI Deployment Token',
            'abilities' => 'deploy,read',
        ]);

        $response->assertRedirect('/secrets');

        $this->assertDatabaseHas('otp_tokens', [
            'user_id' => $user->id,
            'name' => 'CI Deployment Token',
            'abilities' => 'deploy,read',
        ]);

        $token = OTPToken::first();
        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token->token));
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
        $this->assertEquals(now()->addYear()->format('Y-m-d H:i'), $token->expires_at->format('Y-m-d H:i'));
    }

    public function test_secret_token_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/secrets', [
            'name' => '',
            'abilities' => 'read',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertDatabaseCount('otp_tokens', 0);
    }

    public function test_api_json_request_can_create_secret_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/secrets', [
            'name' => 'API Secret',
            'abilities' => 'api:access',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'name' => 'API Secret',
            'abilities' => 'api:access',
            'user_id' => $user->id,
        ]);

        $token = OTPToken::where('name', 'API Secret')->first();
        $this->assertNotNull($token->expires_at);
        $this->assertEquals(now()->addYear()->format('Y-m-d H:i'), $token->expires_at->format('Y-m-d H:i'));
    }
}
