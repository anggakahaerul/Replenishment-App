# Replenishment App

Aplikasi web berbasis Laravel untuk rekonsiliasi (*matching*) data **Replenishment Report** dan **Analysis Replenishment** gudang per cabang dan shift, lengkap dengan dashboard monitoring, riwayat file impor, manajemen shift cabang, dan audit log.

---

## Fitur Utama

- **Upload & Matching Excel (`.xlsx` / `.xls`)**
  - Parsing file *Replenishment Report* dan *Analysis Replenishment* menggunakan `phpoffice/phpspreadsheet`.
  - Penentuan shift otomatis berdasarkan jam transaksi, cabang, tipe hari (Senin–Jumat / Sabtu), toleransi *pre-shift*, serta normalisasi shift lintas tengah malam.
  - Kategorisasi item (**Dry** / **Frozen**) dan jenis transaksi (**Put Away**, **Transfer**, dll.) serta deteksi kelebihan POC (*Melebihi POC*).
- **Dashboard & Grafik Tren**
  - Ringkasan KPI per tanggal untuk **Shift 1**, **Shift 2**, dan **All Shift** (Total Task, Match, Pending, Completion %, Put Away, Transfer, Melebihi POC).
  - Grafik **Tren Matching Pertanggal** dengan filter shift, kategori (`Dry` / `Frozen`), dan rentang tanggal/bulan (`7 Hari`, `14 Hari`, `30 Hari`, `Semua`, atau per bulan).
  - Tabel detail hasil matching dengan pencarian SKU/produk, filter status, dan filter tambahan.
- **Riwayat Laporan & Detail Matching**
  - Halaman **Replenishment Report History** dan **Analysis Replenishment History** lengkap dengan fitur unduh ulang berkas asli.
  - Halaman **Detail Matching** yang menggabungkan hasil Shift 1 dan Shift 2 per tanggal & cabang.
  - Hapus riwayat matching secara fleksibel (per shift atau seluruh tanggal).
- **Manajemen Akses & Role**
  - `admin`: Akses penuh ke seluruh fitur, manajemen user, master data cabang, master data shift, dan application log.
  - `operator`: Upload & matching berkas, melihat dashboard & riwayat, serta menghapus data matching pada cabang yang diizinkan.
  - `viewer`: Akses *read-only* untuk memantau dashboard, riwayat laporan, dan hasil matching.
  - Pembatasan akses per cabang (`branch_id` tertentu atau seluruh cabang).
- **Master Data & Application Log**
  - Pengelolaan data cabang (`branches`) dan aturan jam shift per cabang (`branch_shifts`), termasuk hari operasional dan durasi toleransi.
  - Pencatatan aktivitas pengguna (login, upload, hapus data, perubahan master data) pada halaman **Application Log**.

---

## Tech Stack

- **Backend**: PHP `^8.3`, Laravel `^13.17`
- **Spreadsheet Parser**: `phpoffice/phpspreadsheet` `^5.10`
- **Frontend**: Blade, Tailwind CSS v4 (`@tailwindcss/vite`), Alpine.js, Chart.js, Vite `^8.0`
- **Database**: SQLite (default lokal) atau MySQL / MariaDB

---

## Instalasi Lokal

### 1. Prasyarat
Pastikan sudah terpasang di mesin lokal:
- PHP >= 8.3 (dengan ekstensi umum Laravel & `zip`/`xml`/`gd` untuk PhpSpreadsheet)
- Composer
- Node.js & npm

### 2. Setup Project

Clone repository lalu masuk ke direktori project:

```bash
git clone <url-repository>
cd Replenishment-App
```

Salin file environment dan install dependensi:

```bash
cp .env.example .env
composer install
npm install
```

Generate app key, jalankan migrasi + seeder, dan build aset frontend:

```bash
php artisan key:generate
php artisan migrate --seed
npm run build
```

> **Catatan:** Secara default `.env.example` menggunakan `DB_CONNECTION=sqlite`. Jika menggunakan SQLite, pastikan file `database/database.sqlite` sudah ada (bisa dibuat dengan `touch database/database.sqlite` atau `New-Item database/database.sqlite` di PowerShell). Jika memakai MySQL/MariaDB, sesuaikan konfigurasi `DB_*` di `.env` sebelum menjalankan `php artisan migrate --seed`.

Atau Anda juga bisa memakai script bawaan Composer:

```bash
composer run setup
```

### 3. Menjalankan Server Development

```bash
php artisan serve
```

Untuk pengembangan frontend dengan hot-reload Vite, jalankan di terminal terpisah:

```bash
npm run dev
```

Aplikasi dapat diakses di `http://localhost:8000`.

---

## Akun Default (Seeder)

Menjalankan `php artisan migrate --seed` (atau `php artisan db:seed`) akan membuat akun bawaan berikut dengan password **`password`**:

| Role | Email | Hak Akses |
| :--- | :--- | :--- |
| **Admin** | `admin@replenishment.test` | Akses penuh, master cabang & shift, manajemen user, log aktivitas |
| **Operator** | `operator@replenishment.test` | Upload & proses matching, lihat dashboard/riwayat, hapus data matching |
| **Viewer** | `viewer@replenishment.test` | Read-only (lihat dashboard, riwayat laporan, dan hasil matching) |

> Segera ubah password atau buat akun baru melalui menu **Manajemen User** jika aplikasi dipakai di lingkungan produksi.

---

## Struktur Direktori Penting

```text
app/
├── Http/Controllers/
│   ├── AppLogController.php           # Halaman & pembersihan log aktivitas (Admin)
│   ├── AuthController.php             # Login & logout
│   ├── BranchShiftController.php      # CRUD Master Cabang & Master Shift (Admin)
│   ├── DashboardController.php        # Dashboard KPI, grafik tren, & hapus batch/hasil matching
│   ├── ReplenishmentController.php    # Halaman utama matching, upload, history, detail, & download
│   └── UserController.php             # Manajemen user & role (Admin)
├── Models/
│   ├── ActivityLog.php
│   ├── Branch.php
│   ├── BranchShift.php
│   ├── Import.php
│   ├── ReplenishmentAnalysisDetail.php
│   ├── ReplenishmentDailySummary.php
│   ├── ReplenishmentDetail.php
│   └── ReplenishmentMatchResult.php
└── Services/Replenishment/
    ├── CategoryService.php            # Pemetaan kategori item (Dry / Frozen)
    ├── ExcelParserService.php         # Pembacaan Excel & konversi serial tanggal wall-clock
    ├── MatchingEngineService.php      # Logika pencocokan Report vs Analysis
    ├── ReplenishmentImportService.php # Orkestrasi proses upload & penyimpanan hasil
    ├── ReplenishmentReconciliationService.php
    └── ShiftService.php               # Penentuan & normalisasi shift per cabang/hari
```

---

## Ringkasan Rute Utama (`routes/web.php`)

| Method | Path | Deskripsi | Akses |
| :--- | :--- | :--- | :--- |
| `GET` | `/login` | Halaman login | Guest |
| `GET` | `/` | Halaman utama Upload & Matching | Authenticated |
| `POST` | `/replenishment/import` | Proses upload & matching file Excel | Admin, Operator |
| `GET` | `/dashboard` | Dashboard KPI & Tren Matching | Authenticated |
| `DELETE` | `/dashboard/match-batch` | Hapus hasil matching per tanggal/shift | Admin, Operator |
| `GET` | `/replenishment/report-history` | Riwayat file Replenishment Report | Authenticated |
| `GET` | `/replenishment/analysis-history` | Riwayat file Analysis Replenishment | Authenticated |
| `GET` | `/replenishment/match-detail` | Detail gabungan Shift 1 & Shift 2 per tanggal & cabang | Authenticated |
| `GET` | `/replenishment/history/{import}/download` | Unduh ulang berkas Excel hasil upload | Authenticated |
| `GET` | `/users` | Manajemen pengguna | Admin |
| `GET` | `/master/branches` | Master data cabang | Admin |
| `GET` | `/master/shifts` | Master data jadwal shift cabang | Admin |
| `GET` | `/logs` | Application Log | Admin |

---

## Perintah Berguna

- **Jalankan unit/feature test**:
  ```bash
  composer test
  ```
- **Bersihkan & cache ulang view Blade** (setelah ubah file `.blade.php`):
  ```bash
  php artisan view:clear && php artisan view:cache
  ```
- **Format kode PHP (Laravel Pint)**:
  ```bash
  ./vendor/bin/pint
  ```
