<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'app_name',
                'value' => 'Tele Downloader PRO',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Nama aplikasi / judul dashboard & ekstensi',
            ],
            [
                'key' => 'free_daily_limit',
                'value' => '10',
                'group' => 'general',
                'type' => 'integer',
                'description' => 'Batas download gratis per hari untuk tamu (guest / belum login)',
            ],
            [
                'key' => 'free_registered_daily_limit',
                'value' => '15',
                'group' => 'general',
                'type' => 'integer',
                'description' => 'Batas download gratis per hari untuk member terdaftar (sudah login)',
            ],
            [
                'key' => 'allow_free_download',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Izinkan pengguna free mengunduh media (dengan batas kuota)',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Aktifkan mode pemeliharaan sistem',
            ],
            [
                'key' => 'maintenance_message',
                'value' => 'Server sedang dalam pemeliharaan berkala. Silakan coba beberapa saat lagi.',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Pesan saat mode pemeliharaan aktif',
            ],

            // Feature Toggles
            [
                'key' => 'enable_batch_download',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Fitur multi-select / download massal untuk user Pro',
            ],
            [
                'key' => 'enable_high_quality',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Prioritas download kualitas Full HD original',
            ],
            [
                'key' => 'enable_auto_retry',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Otomatis mencoba unduh ulang jika koneksi terputus',
            ],
            [
                'key' => 'enable_video_preview',
                'value' => '1',
                'group' => 'features',
                'type' => 'boolean',
                'description' => 'Tampilkan pratinjau thumbnail sebelum download',
            ],

            // Payment Settings
            [
                'key' => 'default_payment_gateway',
                'value' => 'tripay',
                'group' => 'payment',
                'type' => 'string',
                'description' => 'Payment gateway aktif utama (tripay, midtrans, bri, manual)',
            ],
            [
                'key' => 'payment_sandbox_mode',
                'value' => '1',
                'group' => 'payment',
                'type' => 'boolean',
                'description' => 'Mode Sandbox / Testing untuk simulasi pembayaran',
            ],
            [
                'key' => 'manual_bank_name',
                'value' => 'BCA / Mandiri / BRI / QRIS Manual',
                'group' => 'payment',
                'type' => 'string',
                'description' => 'Nama bank untuk instruksi transfer manual',
            ],
            [
                'key' => 'manual_account_number',
                'value' => '123-456-7890 (A/N Tele Downloader)',
                'group' => 'payment',
                'type' => 'string',
                'description' => 'Nomor rekening transfer manual',
            ],

            // Contact & Support
            [
                'key' => 'support_telegram',
                'value' => '@TeleDownloaderSupport',
                'group' => 'support',
                'type' => 'string',
                'description' => 'Username / Link Telegram Customer Service',
            ],
            [
                'key' => 'support_whatsapp',
                'value' => '+6281234567890',
                'group' => 'support',
                'type' => 'string',
                'description' => 'Nomor WhatsApp Customer Service',
            ],
            [
                'key' => 'announcement_banner',
                'value' => '🚀 PRO Promo: Dapatkan akses download tanpa batas selamanya dengan paket Lifetime!',
                'group' => 'support',
                'type' => 'string',
                'description' => 'Pesan pengumuman yang muncul di ekstensi / aplikasi',
            ],
            [
                'key' => 'show_announcement',
                'value' => '1',
                'group' => 'support',
                'type' => 'boolean',
                'description' => 'Tampilkan banner pengumuman di ekstensi',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
