<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $isPro = $user->isPro();
        $activeSub = $user->activeSubscription;
        $plan = $activeSub?->plan;

        $today = now()->toDateString();
        $downloadLog = DownloadLog::where('user_id', $user->id)
            ->whereDate('download_date', $today)
            ->first();

        $usedToday = $downloadLog ? $downloadLog->downloads_count : 0;
        $freeDailyLimit = (int) Setting::get('free_registered_daily_limit', 15);
        $planLimit = $plan?->daily_download_limit;

        if ($isPro) {
            $isUnlimited = is_null($planLimit);
            $dailyLimit = $planLimit;
            $remaining = $isUnlimited ? 999999 : max(0, $dailyLimit - $usedToday);
        } else {
            $isUnlimited = false;
            $dailyLimit = $freeDailyLimit;
            $remaining = max(0, $freeDailyLimit - $usedToday);
        }

        $plans = Plan::where('is_active', true)->get();
        $transactions = $user->transactions()->with('plan')->latest()->take(10)->get();

        return view('member.dashboard', compact(
            'user',
            'isPro',
            'activeSub',
            'plan',
            'isUnlimited',
            'dailyLimit',
            'usedToday',
            'remaining',
            'plans',
            'transactions'
        ));
    }
}
