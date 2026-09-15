<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code' => 'weekly',
                'name' => 'Paket Mingguan (Starter)',
                'price' => 9900,
                'duration_days' => 7,
                'daily_download_limit' => 50,
                'features' => [
                    'Maksimal 50 unduhan foto/video per hari',
                    'Masa aktif selama 7 hari',
                    'Kualitas Full HD original',
                    'Cocok untuk tugas & kebutuhan sesekali',
                ],
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'code' => 'monthly',
                'name' => 'Paket Bulanan (Pro)',
                'price' => 24900,
                'duration_days' => 30,
                'daily_download_limit' => null,
                'features' => [
                    'Download tanpa batas (Unlimited) selama 30 hari',
                    'Kualitas Full HD original',
                    'Batch Download 1-Klik Multi-Select',
                    'Prioritas Kecepatan Maksimal',
                    'Paling Populer & Hemat ⭐',
                ],
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'code' => 'lifetime',
                'name' => 'Paket Lifetime',
                'price' => 69000,
                'duration_days' => null,
                'daily_download_limit' => null,
                'features' => [
                    'Akses Selamanya Tanpa Perpanjangan',
                    'Download Tanpa Batas (Unlimited) Selamanya',
                    'Semua Fitur Premium & Update Masa Depan',
                    'Sekali Bayar & Bebas Ribet',
                ],
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
