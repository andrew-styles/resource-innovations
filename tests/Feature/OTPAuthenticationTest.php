<?php

namespace Tests\Feature;

use App\Models\OTPToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OTPAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_if_no_token_provided(): void
    {
        $response = $this->get('/otp');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_if_token_is_empty(): void
    {
        $response = $this->get('/otp?token=');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_if_token_is_invalid(): void
    {
        $response = $this->get('/otp?token=' . Str::random(64));

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_if_token_was_already_used(): void
    {
        $user = User::factory()->create();
        $token = $user->otpTokens()->create([
            'name' => 'Used Token',
            'token' => Str::random(64),
            'last_used_at' => now()->subMinutes(5),
        ]);

        $response = $this->get('/otp?token=' . $token->token);

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_to_login_if_token_has_expired(): void
    {
        $user = User::factory()->create();
        $token = $user->otpTokens()->create([
            'name' => 'Expired Token',
            'token' => Str::random(64),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get('/otp?token=' . $token->token);

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_guest_can_authenticate_with_valid_token_query_parameter(): void
    {
        $user = User::factory()->create();
        $token = $user->otpTokens()->create([
            'name' => 'Valid OTP',
            'token' => Str::random(64),
            'expires_at' => now()->addHour(),
        ]);

        $this->assertNull($token->last_used_at);

        $response = $this->get('/otp?token=' . $token->token);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');

        $token->refresh();
        $this->assertNotNull($token->last_used_at);
    }

    public function test_token_cannot_be_reused_after_first_login(): void
    {
        $user = User::factory()->create();
        $token = $user->otpTokens()->create([
            'name' => 'Single Use OTP',
            'token' => Str::random(64),
        ]);

        $firstResponse = $this->get('/otp?token=' . $token->token);
        $this->assertAuthenticatedAs($user);
        $firstResponse->assertRedirect('/dashboard');

        // Logout
        $this->post('/logout');
        $this->assertGuest();

        // Attempt second login with same token
        $secondResponse = $this->get('/otp?token=' . $token->token);
        $this->assertGuest();
        $secondResponse->assertRedirect('/login');
    }
}
