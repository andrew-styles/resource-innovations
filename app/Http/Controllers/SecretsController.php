<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OTPToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SecretsController extends Controller
{
    public function index(Request $request): Response
    {
        $tokens = $request->user()->otpTokens()->latest()->get();

        return Inertia::render('Secrets', [
            'tokens' => $tokens,
            'secrets' => $tokens,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'string'],
        ]);

        $otpToken = $request->user()->otpTokens()->create([
            'name' => $validated['name'],
            'token' => Str::random(64),
            'abilities' => $validated['abilities'] ?? null,
            'expires_at' => now()->addYear(),
        ]);

        if ($request->wantsJson()) {
            return response()->json($otpToken, 201);
        }

        return redirect()->route('secrets.index');
    }
}
