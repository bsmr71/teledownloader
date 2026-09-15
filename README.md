# 🚀 TeleDownloader & Tele Downloader PRO

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.3%20|%208.4-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/Chrome_Extension-Manifest_V3-4285F4?style=for-the-badge&logo=googlechrome&logoColor=white" alt="Chrome Extension Manifest V3">
  <img src="https://img.shields.io/badge/TailwindCSS-v3-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License MIT">
</p>

**TeleDownloader** adalah solusi lengkap (*full-stack ecosystem*) yang menggabungkan **Chrome Extension (Tele Downloader PRO)** untuk mengunduh media dari Telegram Web dengan **SaaS Backend & Admin Panel (Laravel 12)** untuk manajemen lisensi, sistem langganan (*subscription*), kuota unduhan, dan integrasi multi-payment gateway.

---

## 📑 Daftar Isi

- [✨ Fitur Utama](#-fitur-utama)
  - [🧩 Chrome Extension (Tele Downloader PRO)](#-chrome-extension-tele-downloader-pro)
  - [💻 Backend & Panel Manajemen (Laravel 12)](#-backend--panel-manajemen-laravel-12)
- [📁 Struktur Repositori](#-struktur-repositori)
- [⚙️ Persyaratan Sistem](#️-persyaratan-sistem)
- [🚀 Panduan Instalasi Backend](#-panduan-instalasi-backend)
- [🧩 Panduan Pemasangan Chrome Extension](#-panduan-pemasangan-chrome-extension)
- [💳 Integrasi Payment Gateway & Webhook](#-integrasi-payment-gateway--webhook)
- [🔌 Dokumentasi REST API v1](#-dokumentasi-rest-api-v1)
- [🛡️ Keamanan & Device Binding](#️-keamanan--device-binding)
- [📄 Lisensi](#-lisensi)

---

## ✨ Fitur Utama

### 🧩 Chrome Extension (Tele Downloader PRO)
Dibangun menggunakan standar terbaru **Manifest V3** untuk browser berbasis Chromium (Google Chrome, Microsoft Edge, Brave, Opera):
* **Unduh Semua Jenis Media**: Mendukung foto, video resolusi tinggi (HD/Original), voice note, pesan video (*round video*), audio, dokumen, story, dan foto profil.
* **Bypass Restricted Content**: Mampu mengunduh media dari channel atau grup privat Telegram yang mengaktifkan proteksi *Restrict Saving Content* / proteksi forward melalui teknik DOM canvas & blob stream extraction.
* **Batch Downloader (Multi-Select)**: Memilih banyak pesan media sekaligus dalam 1 klik dan mengemasnya langsung menjadi file arsip `.zip` menggunakan pustaka `JSZip`.
* **Floating Quick Download Button**: Tombol unduh instan yang tersemat otomatis pada setiap gelembung pesan media di antarmuka Telegram Web (Web K & Web A/Z).
* **Auto Web-Sync**: Sinkronisasi akun dan lisensi otomatis dari Member Portal ke ekstensi browser tanpa perlu memasukkan token manual.

### 💻 Backend & Panel Manajemen (Laravel 12)
* **Landing Page Modern**: Halaman penawaran produk yang responsif, modern, dan informatif dilengkapi perbandingan paket harga.
* **Member Portal**:
  * Informasi status paket langganan aktif & tanggal kedaluwarsa.
  * Pemantauan pemakaian kuota unduhan harian secara *real-time*.
  * Integrasi tombol pairing ekstensi otomatis.
* **Admin Dashboard & Management**:
  * **Analisis & Statistik**: Metrik pendapatan, transaksi berhasil, total pengguna, dan volume unduhan harian.
  * **Manajemen Pengguna**: Monitor pengguna, pengalihan peran (*role* admin/member), reset Device ID, dan pemberian lisensi manual (*grant subscription*).
  * **Manajemen Paket (*Plans*)**: Atur harga paket (Mingguan, Bulanan, Lifetime), durasi aktif, batas unduhan harian (*daily limit*), serta fitur-fitur paket.
  * **Manajemen Transaksi**: Riwayat pembayaran, konfirmasi transaksi manual, dan pembatalan transaksi.
  * **Log Unduhan**: Audit trail komprehensif dari setiap aktivitas unduhan pengguna dengan opsi reset kuota harian.
  * **Pengaturan Sistem**: Konfigurasi global, pilihan default payment gateway, kredensial API, dan batas limit default.
* **Multi Payment Gateway**:
  * **Midtrans** (Snap & Notification Webhook)
  * **Tripay** (Closed Payment Channel & Signature Verification)
  * **BRI Direct API** (Integrasi API Bank BRI)

---

## 📁 Struktur Repositori

```text
teledownloader/
├── app/
│   ├── Contracts/                 # Interface Service (PaymentGatewayInterface)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/            # Controller Admin Panel (User, Plan, Transaksi, Setting, Log)
│   │   │   └── Api/V1/           # Controller API untuk Extension & Webhook
│   │   └── Middleware/           # Middleware Keamanan & Admin Auth
│   ├── Models/                   # Model Eloquent (User, Plan, Subscription, Transaction, Log, Setting)
│   └── Services/Payment/         # Driver Gateway (MidtransService, TripayService, BriApiService, PaymentManager)
├── config/                       # Konfigurasi Laravel
├── database/
│   ├── migrations/               # Skema Database (Users, Plans, Subscriptions, Transactions, Logs)
│   └── seeders/                  # Seeder Akun Admin Default & Paket Langganan
├── resources/
│   ├── views/
│   │   ├── admin/                # Blade Template Dashboard & CRUD Admin
│   │   ├── member/               # Blade Template Member Area
│   │   └── landing.blade.php     # Halaman Depan / Landing Page
│   └── css/ & js/                # Aset Frontend (Tailwind CSS)
├── routes/
│   ├── web.php                   # Rute Web (Landing, Auth, Member, Admin)
│   └── api.php                   # Rute REST API v1 (Ekstensi & Webhook)
├── tele-donwloader-extension/    # Source Code Chrome Extension (Manifest V3)
│   ├── icons/                    # Ikon ekstensi (16, 32, 48, 128px)
│   ├── background.js             # Service Worker latar belakang
│   ├── content.js & content.css  # Skrip injeksi DOM Telegram Web
│   ├── inject.js                 # Skrip bypass & blob interceptor di Telegram
│   ├── jszip.min.js              # Pustaka kompresi ZIP untuk batch download
│   ├── manifest.json             # Manifest V3 Configuration
│   ├── popup.html & popup.js     # Antarmuka Popup Ekstensi
│   └── web-sync.js               # Skrip pendeteksi sinkronisasi web portal
└── README.md
```

---

## ⚙️ Persyaratan Sistem

- **PHP**: Versi `8.3` atau `8.4` (dengan ekstensi `pdo`, `mbstring`, `openssl`, `curl`, `json`, `bcmath`)
- **Composer**: Versi `2.x`
- **Node.js & NPM**: Versi `18.x` atau lebih baru
- **Database**: MySQL `8.0+`, MariaDB `10.4+`, atau SQLite `3`
- **Web Browser**: Google Chrome, Microsoft Edge, Brave, atau browser Chromium lainnya

---

## 🚀 Panduan Instalasi Backend

### 1. Kloning Repositori
```bash
git clone https://github.com/bsmr71/teledownloader.git
cd teledownloader
```

### 2. Pasang Dependensi PHP & Node.js
```bash
composer install
npm install
```

### 3. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan pengaturan database dan URL aplikasi:
```env
APP_NAME="TeleDownloader"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teledownloader
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Generate Kunci Aplikasi & Jalankan Migrasi
```bash
php artisan key:generate
php artisan migrate --seed
```

> [!NOTE]
> Seeder akan otomatis membuat paket langganan dasar (Starter, Pro, Lifetime) dan akun administrator default:
> - **Email**: `bismar71@gmail.com`
> - **Password**: `zabuaz71` *(Segera ubah password setelah login pertama kali di halaman admin)*

### 5. Kompilasi Aset Frontend
```bash
npm run build
```

### 6. Jalankan Server Pengembangan
```bash
php artisan serve
```
Buka peramban Anda dan akses:
- **Landing Page**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Admin Panel**: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)
- **Member Area**: [http://127.0.0.1:8000/member](http://127.0.0.1:8000/member)

---

## 🧩 Panduan Pemasangan Chrome Extension

1. Buka browser berbasis Chromium (Google Chrome / Brave / Edge).
2. Kunjungi halaman ekstensi browser dengan mengetikkan:
   ```text
   chrome://extensions/
   ```
3. Aktifkan tombol **Developer mode** di sudut kanan atas.
4. Klik tombol **Load unpacked** (*Muat yang belum dibongkar*).
5. Pilih folder [`tele-donwloader-extension`](file:///c:/laragon/www/teledownloader/tele-donwloader-extension) yang ada di dalam proyek ini.
6. Ekstensi **Tele Downloader PRO** kini telah terpasang!
7. Buka [Telegram Web](https://web.telegram.org/) (baik versi K maupun A), dan Anda akan melihat ikon serta tombol unduhan interaktif pada pesan media.

---

## 💳 Integrasi Payment Gateway & Webhook

Konfigurasi kunci API payment gateway dapat diatur melalui dashboard admin pada menu **Settings** atau langsung di file `.env`:

### 1. Midtrans
```env
MIDTRANS_SERVER_KEY="your-server-key"
MIDTRANS_CLIENT_KEY="your-client-key"
MIDTRANS_IS_PRODUCTION=false
```
* **Webhook URL**: `https://domain-anda.com/api/v1/webhook/midtrans`

### 2. Tripay
```env
TRIPAY_API_KEY="your-api-key"
TRIPAY_PRIVATE_KEY="your-private-key"
TRIPAY_MERCHANT_CODE="your-merchant-code"
TRIPAY_IS_PRODUCTION=false
```
* **Webhook URL**: `https://domain-anda.com/api/v1/webhook/tripay`

### 3. BRI Direct API
```env
BRI_CLIENT_ID="your-client-id"
BRI_CLIENT_SECRET="your-client-secret"
BRI_ACCOUNT_NUMBER="your-account-number"
```

---

## 🔌 Dokumentasi REST API v1

Semua rute API beralamat di `/api/v1`:

| Method | Endpoint | Keterangan | Autentikasi |
| :--- | :--- | :--- | :--- |
| `GET` | `/config` | Mengambil konfigurasi publik ekstensi | Publik |
| `GET` | `/plans` | Mengambil daftar paket langganan aktif | Publik |
| `GET` | `/status` | Cek status kesehatan server API | Publik |
| `POST` | `/auth/register` | Pendaftaran akun member baru | Publik |
| `POST` | `/auth/login` | Login member untuk mendapatkan Bearer Token | Publik |
| `POST` | `/license/activate` | Aktivasi lisensi pada ekstensi | Publik |
| `POST` | `/license/verify` | Verifikasi validitas lisensi & kuota harian | Publik |
| `POST` | `/download/track` | Mencatat dan memvalidasi kuota unduhan | Publik |
| `POST` | `/checkout/create` | Membuat order pembayaran transaksi baru | Publik / Member |
| `POST` | `/webhook/{gateway}` | Endpoint Webhook Pembayaran (Midtrans / Tripay) | Signature Check |
| `POST` | `/auth/bind-device` | Menghubungkan ID perangkat ke akun member | Bearer Token |

---

## 🛡️ Keamanan & Device Binding

- **Proteksi 1 Akun 1 Perangkat**: Sistem lisensi menerapkan *Hardware/Browser Device ID Binding* untuk mencegah penggunaan 1 akun berlangganan secara massal di berbagai komputer berbeda tanpa izin.
- **Validasi Webhook Signature**: Seluruh webhook pembayaran diverifikasi menggunakan hashing tanda tangan digital (*HMAC-SHA256* atau *SHA512*) untuk mencegah pemalsuan status pembayaran.
- **Sanctum API Token**: Komunikasi antar aplikasi dan ekstensi dilindungi dengan Laravel Sanctum.

---

## 📄 Lisensi

Proyek ini dirilis di bawah lisensi [MIT License](LICENSE).
Hak Cipta © 2026 TeleDownloader Ecosystem.
