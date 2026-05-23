# Velora SaaS POS

Sistem Point of Sales (POS) Multi-Tenant berbasis Software as a Service (SaaS). 
Proyek ini mengadopsi arsitektur Modular Monolith (Domain-Driven) dengan Laravel sebagai backend dan Next.js (segera hadir) sebagai frontend.

---

## Prasyarat (Prerequisites)

Sebelum melakukan clone dan menjalankan aplikasi ini di perangkat baru, pastikan perangkat Anda sudah terinstal perangkat lunak berikut:

1. **PHP >= 8.2** (Rekomendasi: Menggunakan Laragon atau XAMPP di Windows).
2. **Composer** (Package Manager untuk PHP).
3. **MySQL / MariaDB** (Rekomendasi: Versi 8.x).
4. **Git** (Untuk version control).
5. **Node.js & npm** (Minimal versi LTS 18.x atau 20.x untuk Frontend nantinya).
6. **Postman** (Opsional, sangat disarankan untuk pengujian API).

---

## Panduan Instalasi (Full Clone & Setup Guide)

Ikuti langkah-langkah di bawah ini secara berurutan untuk menjalankan project ini pada perangkat (device) yang baru.

### 1. Clone Repositori
Buka Terminal / Command Prompt / PowerShell, arahkan ke folder di mana Anda ingin meletakkan project, lalu jalankan:

```bash
git clone https://github.com/yopaayy/velora-project.git
cd velora-project
```

### 2. Setup Database (MySQL)
Buka aplikasi database management kesayangan Anda (misalnya Laragon phpMyAdmin, DBeaver, TablePlus, atau MySQL CLI).
1. Buat sebuah database baru dengan nama `velora`.
   *Command SQL:* `CREATE DATABASE velora;`

### 3. Setup Backend (Laravel)

Masuk ke folder `backend`:
```bash
cd backend
```

**Install dependensi PHP via Composer:**
```bash
composer install
```

**Siapkan konfigurasi Environment (.env):**
Salin file `.env.example` menjadi `.env`.
```bash
cp .env.example .env
# Jika menggunakan CMD/PowerShell di Windows:
# copy .env.example .env
```
Buka file `.env` di text editor, pastikan bagian konfigurasi database sudah benar sesuai dengan database lokal Anda (biasanya user root tanpa password jika menggunakan Laragon bawaan):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=velora
DB_USERNAME=root
DB_PASSWORD=
```

**Generate Application Key:**
```bash
php artisan key:generate
```

**Migrasi Database dan Eksekusi Seeder:**
Jalankan perintah ini untuk membangun seluruh struktur tabel ke database `velora` sekaligus mengisi data default (Mata Uang, Role, Permission, dan Paket Langganan).
```bash
php artisan migrate --seed
```

**Jalankan Server Lokal Backend:**
```bash
php artisan serve
```
*Server API sekarang akan berjalan di `http://127.0.0.1:8000`.* Biarkan terminal ini tetap terbuka.

### 4. Setup Frontend (Next.js)

Buka terminal/CMD baru (biarkan terminal Backend tetap berjalan).
Masuk ke folder `frontend`:
```bash
cd frontend
```

**Install dependensi Node.js:**
```bash
npm install
```

*(Opsional)* Jika ada file `.env.example` di folder frontend, salin menjadi `.env.local`. Saat ini konfigurasi bawaan sudah siap dipakai untuk *development*.

**Jalankan Server Lokal Frontend:**
```bash
npm run dev
```

*Server Web sekarang akan berjalan di `http://localhost:3000`.* 
Buka browser Anda dan arahkan ke alamat tersebut untuk melihat tampilan Dashboard. Aplikasi akan secara otomatis beralih ke rute multi-bahasa (`http://localhost:3000/id/login`).

---

## Panduan Pengujian API Menggunakan Postman

Untuk mempermudah pengujian API tanpa frontend, kami telah menyediakan file **Koleksi Postman**. Koleksi ini dilengkapi dengan script otomatis yang akan menyimpan *Bearer Token* saat Anda berhasil login.

1. Buka aplikasi **Postman**.
2. Klik tombol **Import** (di panel kiri atas).
3. Cari file `Velora_API_Collection.json` yang berada di *root* direktori project Anda (`velora-project/Velora_API_Collection.json`), kemudian pilih file tersebut.
4. Akan muncul folder koleksi baru bernama **Velora SaaS POS API**.
5. Pastikan Environment Postman berada di status `No Environment`.

### Skenario Singkat Pengujian
1. **Daftar**: Buka endpoint `Auth > 1. Register Owner & Business` dan klik Send. Anda akan mendapatkan balasan *Status 201 Created*.
2. **Login**: Buka endpoint `Auth > 2. Login` dan klik Send. Sistem Postman otomatis akan menangkap Token rahasia Anda.
3. **Mengecek Data Diri**: Coba `Auth > 3. Get Profile (Me)`.
4. **Dashboard Tenant**: Akses `Tenant (Protected) > Test Tenant Dashboard Access`. Anda sudah siap melanjutkan integrasi!

---

## Struktur Folder Utama

- `backend/` - Berisi source code utama Laravel 11. Menggunakan pattern Modular Monolith di `app/Modules/` dan `app/Shared/`.
- `frontend/` - Berisi source code Next.js 15 App Router dengan shadcn/ui dan next-intl (i18n).
- `Velora_API_Collection.json` - Endpoint lengkap untuk testing Postman.

---

> *Project Setup Complete! Selamat mengembangkan Velora SaaS.*
