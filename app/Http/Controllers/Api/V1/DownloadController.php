<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class DownloadController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? auth('sanctum')->user();
        if (! $user && ($bearer = $request->bearerToken())) {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token) {
                $user = $token->tokenable;
            }
        }

        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id') ?? 'unknown_device';
        $ip = $request->ip();
        $today = now()->toDateString();
        $count = (int) $request->input('count', 1);

        $isPro = $user ? $user->isPro() : false;
        $planDailyLimit = null;

        if ($isPro) {
            $planDailyLimit = $user->activeSubscription?->plan?->daily_download_limit;
        }

        $freeDailyLimit = (int) Setting::get('free_daily_limit', 10);
        $allowFree = (bool) Setting::get('allow_free_download', true);

        if (! $isPro && ! $allowFree) {
            return response()->json([
                'success' => false,
                'message' => 'Layanan download gratis saat ini dinonaktifkan. Silakan berlangganan Pro untuk mengunduh.',
                'used_today' => 0,
                'daily_limit' => 0,
                'remaining' => 0,
            ], 403);
        }

        // Check current usage today
        $query = DownloadLog::whereDate('download_date', $today);
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->where('device_id', $deviceId);
        }

        $log = $query->first();
        $currentUsed = $log ? $log->downloads_count : 0;

        // 1. Quota Check for PRO users with plan limit (e.g. Weekly = 50 limit)
        if ($isPro && $planDailyLimit !== null) {
            if (($currentUsed + $count) > $planDailyLimit) {
                return response()->json([
                    'success' => false,
                    'message' => "Batas kuota harian paket Anda ({$planDailyLimit} unduhan/hari) telah tercapai hari ini. Silakan coba kembali besok atau upgrade ke paket Bulanan/Lifetime untuk download tanpa batas.",
                    'used_today' => $currentUsed,
                    'daily_limit' => $planDailyLimit,
                    'remaining' => 0,
                ], 403);
            }
        }

        // 2. Quota Check for Free Tier users
        if (! $isPro) {
            $effectiveFreeLimit = $user ? (int) Setting::get('free_registered_daily_limit', 15) : $freeDailyLimit;
            if (($currentUsed + $count) > $effectiveFreeLimit) {
                $userType = $user ? 'Member' : 'Tamu (Guest)';

                return response()->json([
                    'success' => false,
                    'message' => "Batas harian {$effectiveFreeLimit} download gratis untuk {$userType} telah tercapai. Silakan upgrade ke Pro untuk download hingga 50 file/hari atau tanpa batas.",
                    'used_today' => $currentUsed,
                    'daily_limit' => $effectiveFreeLimit,
                    'remaining' => 0,
                ], 403);
            }
        }

        // Increment or create log
        if ($log) {
            $log->increment('downloads_count', $count);
        } else {
            DownloadLog::create([
                'user_id' => $user?->id,
                'device_id' => $deviceId,
                'ip_address' => $ip,
                'download_date' => $today,
                'downloads_count' => $count,
            ]);
        }

        $newTotal = $currentUsed + $count;

        // Calculate remaining
        if ($isPro) {
            if ($planDailyLimit !== null) {
                $effectiveLimit = $planDailyLimit;
                $remaining = max(0, $planDailyLimit - $newTotal);
            } else {
                $effectiveLimit = null;
                $remaining = 999999;
            }
        } else {
            $effectiveLimit = $user ? (int) Setting::get('free_registered_daily_limit', 15) : $freeDailyLimit;
            $remaining = max(0, $effectiveLimit - $newTotal);
        }

        return response()->json([
            'success' => true,
            'message' => 'Download berhasil dicatat.',
            'used_today' => $newTotal,
            'daily_limit' => $effectiveLimit,
            'remaining' => $remaining,
            'is_pro' => $isPro,
        ]);
    }
}
