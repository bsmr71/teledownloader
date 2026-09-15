<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::withCount(['subscriptions' => function ($q) {
            $q->where('status', 'active');
        }])->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:plans,code'],
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'daily_download_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $featuresArray = [];
        if ($request->filled('features')) {
            $featuresArray = array_filter(array_map('trim', explode("\n", $request->features)));
        }

        Plan::create([
            'code' => $request->filled('code') ? Str::slug($request->code) : Str::slug($request->name),
            'name' => $validated['name'],
            'price' => (int) $validated['price'],
            'duration_days' => $request->filled('duration_days') ? (int) $request->duration_days : null,
            'daily_download_limit' => $request->filled('daily_download_limit') ? (int) $request->daily_download_limit : null,
            'features' => $featuresArray,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Paket langganan baru berhasil ditambahkan.');
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'daily_download_limit' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'string'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $featuresArray = [];
        if ($request->filled('features')) {
            $featuresArray = array_filter(array_map('trim', explode("\n", $request->features)));
        }

        $plan->update([
            'name' => $validated['name'],
            'price' => (int) $validated['price'],
            'duration_days' => $request->filled('duration_days') ? (int) $request->duration_days : null,
            'daily_download_limit' => $request->filled('daily_download_limit') ? (int) $request->daily_download_limit : null,
            'features' => $featuresArray,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', "Paket '{$plan->name}' berhasil diperbarui.");
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        $status = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Paket '{$plan->name}' berhasil {$status}.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Paket ini tidak dapat dihapus karena masih memiliki pengguna langganan aktif.');
        }

        $planName = $plan->name;
        $plan->delete();

        return back()->with('success', "Paket '{$planName}' berhasil dihapus.");
    }
}
