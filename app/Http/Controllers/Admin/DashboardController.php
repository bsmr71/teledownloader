<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // ── Financial Metrics ──
        $totalRevenue = Transaction::where('payment_status', 'paid')->sum('gross_amount');
        $monthRevenue = Transaction::where('payment_status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('gross_amount');
        $todayRevenue = Transaction::where('payment_status', 'paid')
            ->whereDate('paid_at', today())
            ->sum('gross_amount');

        $paidTransactionsCount = Transaction::where('payment_status', 'paid')->count();
        $pendingTransactionsCount = Transaction::where('payment_status', 'pending')->count();

        // ── User & Subscription Metrics ──
        $totalUsers = User::count();
        $totalProUsers = User::whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
        })->count();

        $activeSubscriptionsCount = Subscription::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();

        // ── Download Metrics ──
        $todayDownloads = DownloadLog::where('download_date', today())->sum('downloads_count');
        $totalDownloads = DownloadLog::sum('downloads_count');

        // ── Plan Breakdown ──
        $plans = Plan::withCount(['subscriptions' => function ($query) {
            $query->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
        }])->get();

        // ── Revenue Trend (Last 7 Days) ──
        $revenueChartLabels = [];
        $revenueChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenueChartLabels[] = $date->format('d M');
            $revenueChartData[] = (int) Transaction::where('payment_status', 'paid')
                ->whereDate('paid_at', $date)
                ->sum('gross_amount');
        }

        // ── Download Trend (Last 7 Days) ──
        $downloadChartLabels = [];
        $downloadChartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $downloadChartLabels[] = $date->format('d M');
            $downloadChartData[] = (int) DownloadLog::where('download_date', $date)->sum('downloads_count');
        }

        // ── Recent Activity ──
        $recentTransactions = Transaction::with(['user', 'plan'])
            ->latest()
            ->take(6)
            ->get();

        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->latest('starts_at')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'monthRevenue',
            'todayRevenue',
            'paidTransactionsCount',
            'pendingTransactionsCount',
            'totalUsers',
            'totalProUsers',
            'activeSubscriptionsCount',
            'todayDownloads',
            'totalDownloads',
            'plans',
            'revenueChartLabels',
            'revenueChartData',
            'downloadChartLabels',
            'downloadChartData',
            'recentTransactions',
            'recentSubscriptions'
        ));
    }
}
