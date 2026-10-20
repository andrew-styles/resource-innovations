<?php

namespace App\Http\Controllers;

use App\Models\OTPToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OTPController extends Controller
{
    /**
     * Handle the incoming request to authenticate via OTP token.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return $this->login($request);
    }

    /**
     * Authenticate via one-time password token.
     */
    public function login(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        // No token found, redirect to login
        if (!$token || !is_string($token) || trim($token) === '') {
            return redirect()->route('login');
        }

        $otpToken = OTPToken::where('token', trim($token))->first();

        // Token found but invalid, redirect to login
        if (
            !$otpToken ||
            $otpToken->last_used_at !== null ||
            ($otpToken->expires_at !== null && $otpToken->expires_at->isPast()) ||
            !$otpToken->user
        ) {
            return redirect()->route('login');
        }

        $otpToken->forceFill([
            'last_used_at' => now(),
        ])->save();

        Auth::login($otpToken->user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
