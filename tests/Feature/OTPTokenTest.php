<?php

namespace Tests\Feature;

use App\Models\OTPToken;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class OTPTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_many_otp_tokens(): void
    {
        $user = User::factory()->create();

        $token1 = $user->otpTokens()->create([
            'name' => 'login-otp',
            'token' => Str::random(64),
            'abilities' => 'read,write',
            'last_used_at' => now(),
            'expires_at' => now()->addMinutes(15),
        ]);

        $token2 = $user->otpTokens()->create([
            'name' => 'action-otp',
            'token' => Str::random(64),
            'abilities' => 'action',
            'last_used_at' => null,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertCount(2, $user->fresh()->otpTokens);
        $this->assertTrue($user->otpTokens->contains($token1));
        $this->assertTrue($user->otpTokens->contains($token2));
        $this->assertEquals($user->id, $token1->user->id);
    }

    public function test_otp_token_attributes_and_casts(): void
    {
        $user = User::factory()->create();
        $tokenString = Str::random(64);
        $now = now();

        $otpToken = OTPToken::create([
            'user_id' => $user->id,
            'name' => 'auth-token',
            'token' => $tokenString,
            'abilities' => 'authenticate',
            'last_used_at' => $now,
            'expires_at' => $now->copy()->addMinutes(10),
        ]);

        $this->assertEquals(64, strlen($otpToken->token));
        $this->assertEquals('auth-token', $otpToken->name);
        $this->assertEquals('authenticate', $otpToken->abilities);
        $this->assertInstanceOf(Carbon::class, $otpToken->last_used_at);
        $this->assertInstanceOf(Carbon::class, $otpToken->expires_at);
        $this->assertInstanceOf(Carbon::class, $otpToken->created_at);
        $this->assertInstanceOf(Carbon::class, $otpToken->updated_at);
    }

    public function test_otp_token_must_be_unique(): void
    {
        $user = User::factory()->create();
        $tokenString = Str::random(64);

        OTPToken::create([
            'user_id' => $user->id,
            'name' => 'token-1',
            'token' => $tokenString,
        ]);

        $this->expectException(QueryException::class);

        OTPToken::create([
            'user_id' => $user->id,
            'name' => 'token-2',
            'token' => $tokenString,
        ]);
    }

    public function test_deleting_user_cascades_and_deletes_otp_tokens(): void
    {
        $user = User::factory()->create();

        $user->otpTokens()->create([
            'name' => 'token-to-delete',
            'token' => Str::random(64),
        ]);

        $this->assertDatabaseCount('otp_tokens', 1);

        $user->delete();

        $this->assertDatabaseCount('otp_tokens', 0);
    }
}
