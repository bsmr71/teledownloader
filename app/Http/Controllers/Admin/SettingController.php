<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::all()->keyBy('key');
        $plans = Plan::all();

        return view('admin.settings.index', compact('settings', 'plans'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method', 'plans']);

        // List of boolean settings to properly handle unchecked checkboxes
        $booleanKeys = [
            'allow_free_download',
            'maintenance_mode',
            'enable_batch_download',
            'enable_high_quality',
            'enable_auto_retry',
            'enable_video_preview',
            'payment_sandbox_mode',
            'show_announcement',
        ];

        // Process explicit booleans that might be unchecked (missing from POST)
        foreach ($booleanKeys as $boolKey) {
            if ($request->has('__form_section_'.$boolKey) || $request->has('__has_booleans')) {
                $val = $request->boolean($boolKey) ? '1' : '0';
                Setting::set($boolKey, $val, null, 'boolean');
            }
        }

        // Process all provided fields
        foreach ($data as $key => $value) {
            if (str_starts_with($key, '__')) {
                continue;
            }

            $existing = Setting::where('key', $key)->first();
            $group = $existing ? $existing->group : 'general';
            $type = $existing ? $existing->type : 'string';

            if (in_array($key, $booleanKeys)) {
                $value = $value ? '1' : '0';
                $type = 'boolean';
            }

            Setting::set($key, $value, $group, $type);
        }

        // Process Plan Configurations if submitted from Settings
        if ($request->has('plans') && is_array($request->plans)) {
            foreach ($request->plans as $planId => $planData) {
                $plan = Plan::find($planId);
                if ($plan) {
                    $features = [];
                    if (! empty($planData['features'])) {
                        if (is_array($planData['features'])) {
                            $features = $planData['features'];
                        } else {
                            $features = array_values(array_filter(array_map('trim', explode("\n", (string) $planData['features']))));
                        }
                    }

                    $isLifetime = ! empty($planData['is_lifetime']);
                    $isUnlimited = ! empty($planData['is_unlimited']);

                    $durationDays = null;
                    if (! $isLifetime && isset($planData['duration_days']) && $planData['duration_days'] !== '') {
                        $durationDays = (int) $planData['duration_days'];
                    }

                    $dailyLimit = null;
                    if (! $isUnlimited && isset($planData['daily_download_limit']) && $planData['daily_download_limit'] !== '') {
                        $dailyLimit = (int) $planData['daily_download_limit'];
                    }

                    $plan->update([
                        'name' => $planData['name'] ?? $plan->name,
                        'price' => isset($planData['price']) ? (int) $planData['price'] : $plan->price,
                        'duration_days' => $durationDays,
                        'daily_download_limit' => $dailyLimit,
                        'features' => $features,
                        'is_featured' => ! empty($planData['is_featured']),
                        'is_active' => ! empty($planData['is_active']),
                    ]);
                }
            }
        }

        return back()->with('success', 'Semua pengaturan sistem, fitur, dan paket langganan berhasil diperbarui.');
    }
}
