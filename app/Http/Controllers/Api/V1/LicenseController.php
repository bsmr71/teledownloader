<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        $key = trim($request->input('license_key'));
        $deviceId = $request->input('device_id');

        // Handle Demo License for quick testing
        if (strtoupper($key) === 'DEMO-PRO') {
            return response()->json([
                'success' => true,
                'message' => 'Lisensi DEMO-PRO berhasil diaktifkan!',
                'plan' => 'pro',
                'is_pro' => true,
                'expires_at' => now()->addDays(30)->toIso8601String(),
            ]);
        }

        // Search by License Key or User Email
        $subscription = Subscription::with(['plan', 'user'])
            ->where('license_key', $key)
            ->first();

        if (! $subscription) {
            // Check if user entered email
            $user = User::where('email', $key)->first();
            if ($user && $user->activeSubscription) {
                $subscription = $user->activeSubscription;
            }
        }

        if (! $subscription || ! $subscription->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Kunci lisensi tidak valid atau sudah kedaluwarsa.',
            ], 404);
        }

        // Link device if provided
        if ($deviceId) {
            $subscription->update(['device_id' => $deviceId]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lisensi Pro berhasil diaktifkan!',
            'plan' => $subscription->plan->code,
            'plan_name' => $subscription->plan->name,
            'is_pro' => true,
            'license_key' => $subscription->license_key,
            'expires_at' => $subscription->expires_at?->toIso8601String(),
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['license_key' => 'required|string']);
        $key = trim($request->input('license_key'));

        $sub = Subscription::with('plan')->where('license_key', $key)->first();

        if ($sub && $sub->isValid()) {
            return response()->json([
                'valid' => true,
                'plan' => $sub->plan->code,
                'expires_at' => $sub->expires_at?->toIso8601String(),
            ]);
        }

        return response()->json(['valid' => false, 'message' => 'Lisensi tidak valid.'], 404);
    }
}
