<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['activeSubscription.plan'])
            ->withCount(['downloadLogs as total_downloads' => function ($q) {
                $q->select(\DB::raw('COALESCE(SUM(downloads_count), 0)'));
            }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            if ($status === 'pro') {
                $query->whereHas('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where(function ($sq) {
                            $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        });
                });
            } elseif ($status === 'free') {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->where('status', 'active')
                        ->where(function ($sq) {
                            $sq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        });
                });
            }
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $plans = Plan::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'plans'));
    }

    public function show(User $user): View
    {
        $user->load([
            'subscriptions.plan',
            'transactions.plan',
            'downloadLogs' => fn ($q) => $q->latest('download_date')->take(10),
        ]);

        $plans = Plan::where('is_active', true)->get();

        return view('admin.users.show', compact('user', 'plans'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:user,admin'],
        ]);

        if ($user->id === Auth::id() && $request->role !== 'admin') {
            return back()->with('error', 'Anda tidak dapat menurunkan role akun Anda sendiri.');
        }

        $user->update(['role' => $request->role]);

        return back()->with('success', "Role untuk pengguna {$user->name} berhasil diperbarui menjadi {$request->role}.");
    }

    public function grantSubscription(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        $customDays = $request->filled('duration_days') ? (int) $request->duration_days : null;

        $user->grantSubscription($plan, $customDays);

        return back()->with('success', "Langganan paket '{$plan->name}' berhasil diaktifkan langsung untuk {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna {$userName} berhasil dihapus dari sistem.");
    }
}
