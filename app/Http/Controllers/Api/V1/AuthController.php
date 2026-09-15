<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'device_id' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'device_id' => $validated['device_id'] ?? null,
            'role' => 'user',
        ]);

        $token = $user->createToken('chrome-extension')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_pro' => false,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi tidak cocok.'],
            ]);
        }

        if ($request->filled('device_id')) {
            $user->update(['device_id' => $request->device_id]);
        }

        $token = $user->createToken('chrome-extension')->plainTextToken;
        $activeSub = $user->activeSubscription;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_pro' => $user->isPro(),
                'plan' => $activeSub ? $activeSub->plan->code : 'free',
                'expires_at' => $activeSub?->expires_at?->toIso8601String(),
                'license_key' => $activeSub?->license_key,
            ],
        ]);
    }

    public function bindDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:255',
        ]);

        $user = $request->user();
        if ($user) {
            $user->update(['device_id' => $request->device_id]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device ID berhasil dihubungkan.',
        ]);
    }
}
