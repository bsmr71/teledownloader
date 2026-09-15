<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class StatusController extends Controller
{
    public function checkStatus(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? auth('sanctum')->user();
        if (! $user && ($bearer = $request->bearerToken())) {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token) {
                $user = $token->tokenable;
            }
        }

        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');
        $today = now()->toDateString();

        $isPro = false;
        $planCode = 'free';
        $planName = 'Free Tier';
        $expiresAt = null;
        $licenseKey = null;
        $planDailyLimit = null;

        if ($user && $user->isPro()) {
            $isPro = true;
            $sub = $user->activeSubscription;
            $plan = $sub?->plan;
            $planCode = $plan ? $plan->code : 'lifetime';
            $planName = $plan ? $plan->name : 'Paket Lifetime';
            $expiresAt = $sub?->expires_at?->toIso8601String();
            $licenseKey = $sub?->license_key;
            $planDailyLimit = $plan?->daily_download_limit;
        }

        $freeDailyLimit = (int) Setting::get('free_daily_limit', 10);
        $allowFree = (bool) Setting::get('allow_free_download', true);
        $usedToday = 0;

        if ($user) {
            $log = DownloadLog::where('user_id', $user->id)
                ->whereDate('download_date', $today)
                ->first();
            $usedToday = $log ? $log->downloads_count : 0;
        } elseif ($deviceId) {
            $log = DownloadLog::where('device_id', $deviceId)
                ->whereDate('download_date', $today)
                ->first();
            $usedToday = $log ? $log->downloads_count : 0;
        }

        // Determine effective daily limit & remaining downloads
        if ($isPro) {
            if ($planDailyLimit !== null) {
                // Tier with explicit daily limit (e.g. Weekly = 50)
                $effectiveDailyLimit = (int) $planDailyLimit;
                $remaining = max(0, $effectiveDailyLimit - $usedToday);
                $canDownload = $remaining > 0;
                $isUnlimited = false;
            } else {
                // Unlimited Tier (e.g. Monthly, Lifetime)
                $effectiveDailyLimit = null;
                $remaining = 999999;
                $canDownload = true;
                $isUnlimited = true;
            }
        } else {
            // Free Tier: Logged-in member gets 15/day (bonus 5), Guest gets 10/day
            if ($user) {
                $registeredLimit = (int) Setting::get('free_registered_daily_limit', 15);
                $effectiveDailyLimit = $registeredLimit;
                $planName = 'Free Member (15/hari)';
            } else {
                $effectiveDailyLimit = $freeDailyLimit;
                $planName = 'Free Guest (10/hari)';
            }
            $remaining = $allowFree ? max(0, $effectiveDailyLimit - $usedToday) : 0;
            $canDownload = $allowFree && $remaining > 0;
            $isUnlimited = false;
        }

        return response()->json([
            'success' => true,
            'is_pro' => $isPro,
            'plan' => $planCode,
            'plan_name' => $planName,
            'is_unlimited' => $isUnlimited,
            'daily_limit' => $effectiveDailyLimit,
            'used_today' => $usedToday,
            'remaining_today' => $remaining,
            'can_download' => $canDownload,
            'expires_at' => $expiresAt,
            'license_key' => $licenseKey,
            'announcement' => Setting::get('show_announcement', true) ? Setting::get('announcement_banner') : null,
            'token' => $user ? ($bearer ?? $user->createToken('auto-sync-token')->plainTextToken) : null,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_pro' => $isPro,
            ] : null,
        ]);
    }

    public function getConfig(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'app_name' => Setting::get('app_name', 'Tele Downloader PRO'),
            'free_daily_limit' => (int) Setting::get('free_daily_limit', 10),
            'allow_free_download' => (bool) Setting::get('allow_free_download', true),
            'maintenance_mode' => (bool) Setting::get('maintenance_mode', false),
            'maintenance_message' => Setting::get('maintenance_message'),
            'features' => [
                'batch_download' => (bool) Setting::get('enable_batch_download', true),
                'high_quality' => (bool) Setting::get('enable_high_quality', true),
                'auto_retry' => (bool) Setting::get('enable_auto_retry', true),
                'video_preview' => (bool) Setting::get('enable_video_preview', true),
            ],
            'support' => [
                'telegram' => Setting::get('support_telegram'),
                'whatsapp' => Setting::get('support_whatsapp'),
            ],
            'announcement' => Setting::get('show_announcement', true) ? Setting::get('announcement_banner') : null,
        ]);
    }
}
