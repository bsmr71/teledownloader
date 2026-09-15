<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;
use Illuminate\View\View;

class LandingController extends Controller
{
    /**
     * Display the public landing page.
     */
    public function index(): View
    {
        $plans = Plan::where('is_active', true)->get();
        $appName = Setting::get('app_name', 'Tele Downloader PRO');
        $announcement = Setting::get('announcement_bar');
        $supportTelegram = Setting::get('support_telegram', 'TeleDownloaderSupport');

        return view('landing', compact('plans', 'appName', 'announcement', 'supportTelegram'));
    }
}
