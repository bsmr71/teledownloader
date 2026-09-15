<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subscription::with(['user', 'plan']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('license_key', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($status === 'expired') {
                $query->where(function ($q) {
                    $q->where('status', 'expired')
                        ->orWhere(function ($sq) {
                            $sq->where('status', 'active')->whereNotNull('expires_at')->where('expires_at', '<=', now());
                        });
                });
            } elseif ($status === 'cancelled') {
                $query->where('status', 'cancelled');
            }
        }

        $subscriptions = $query->latest('starts_at')->paginate(15)->withQueryString();
        $plans = Plan::where('is_active', true)->get();
        $users = User::orderBy('name')->select('id', 'name', 'email')->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $user = User::findOrFail($request->user_id);
        $plan = Plan::findOrFail($request->plan_id);
        $customDays = $request->filled('duration_days') ? (int) $request->duration_days : null;

        $user->grantSubscription($plan, $customDays);

        return back()->with('success', "Langganan paket '{$plan->name}' berhasil diaktifkan langsung untuk pengguna {$user->name}.");
    }

    public function extend(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'action_type' => ['required', 'in:add_7,add_30,add_custom,make_lifetime'],
            'custom_days' => ['nullable', 'required_if:action_type,add_custom', 'integer', 'min:1'],
        ]);

        $userName = $subscription->user ? $subscription->user->name : 'Pengguna';

        switch ($request->action_type) {
            case 'add_7':
                $subscription->extendDays(7);
                $msg = "Masa aktif langganan {$userName} berhasil diperpanjang 7 hari.";
                break;
            case 'add_30':
                $subscription->extendDays(30);
                $msg = "Masa aktif langganan {$userName} berhasil diperpanjang 30 hari.";
                break;
            case 'add_custom':
                $days = (int) $request->custom_days;
                $subscription->extendDays($days);
                $msg = "Masa aktif langganan {$userName} berhasil diperpanjang {$days} hari.";
                break;
            case 'make_lifetime':
                $subscription->makeLifetime();
                $msg = "Langganan {$userName} berhasil diubah menjadi Akses Lifetime (Selamanya).";
                break;
            default:
                return back()->with('error', 'Aksi tidak valid.');
        }

        return back()->with('success', $msg);
    }

    public function cancel(Subscription $subscription): RedirectResponse
    {
        $subscription->cancel();
        $userName = $subscription->user ? $subscription->user->name : 'Pengguna';

        return back()->with('success', "Langganan untuk {$userName} berhasil dinonaktifkan.");
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return back()->with('success', 'Data langganan berhasil dihapus.');
    }
}
