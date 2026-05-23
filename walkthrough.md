# Walkthrough: Authentication & Tenant Middleware

Modul Authentication dan Tenant Middleware telah berhasil diimplementasikan sesuai dengan arsitektur SaaS Velora.

## Ringkasan Perubahan

### 1. API Authentication
Endpoint API dasar untuk otentikasi telah dibuat dan dilindungi menggunakan Laravel Sanctum:
- `POST /api/v1/auth/register`: Endpoint pendaftaran pengguna baru. Proses ini berjalan secara otomatis di dalam *database transaction*:
  1. Membuat akun `User`
  2. Membuat `Business` baru (tenant) dengan parameter default.
  3. Membuat `Branch` default (Pusat).
  4. Memberikan role `owner` pada bisnis tersebut kepada user.
  5. Mendaftarkan bisnis ke paket langganan `Free`.
- `POST /api/v1/auth/login`: Menghasilkan Sanctum Bearer Token berdasarkan email dan password.
- `GET /api/v1/auth/me`: Menampilkan profil pengguna beserta daftar relasi bisnis dan cabang yang dimilikinya.
- `POST /api/v1/auth/logout`: Menghapus token aktif (revoke).

### 2. Tenant Middleware
Tiga lapis keamanan tenant berhasil diintegrasikan di `bootstrap/app.php`:
- `tenant` (`EnsureTenantScope`): Memvalidasi header `X-Business-Id`. Middleware memastikan user login merupakan bagian dari bisnis tersebut dan secara dinamis mengatur scope operasi di tingkat model.
- `branch` (`EnsureBranchAccess`): Memvalidasi header `X-Branch-Id`. Memastikan user memiliki hak akses operasional pada cabang spesifik tersebut.
- `subscription` (`EnsureActiveSubscription`): Mencegah akses ke API (kecuali Auth) jika paket berlangganan bisnis dalam keadaan *expired*.

### 3. Error Handling
Kami telah melakukan standarisasi terhadap respon error di aplikasi. `ValidationException` maupun `AuthenticationException` sekarang di *intercept* secara otomatis di kernel untuk mengeluarkan JSON balasan standar (`ApiResponse::error`).

### 4. Koleksi Postman
Sebuah koleksi postman siap pakai telah ditambahkan di *root project* bernama `Velora_API_Collection.json`. Koleksi ini memiliki script otomatis yang akan menyimpan `token`, `business_id`, dan `branch_id` secara langsung ke dalam environment variables saat Anda melakukan *Login*.

## Cara Pengujian
1. Saya telah menjalankan *backend server* untuk Anda, API saat ini aktif pada `http://127.0.0.1:8000`.
2. Anda dapat membuka **Postman**, lalu import file `e:\velora_project\Velora_API_Collection.json`.
3. Jalankan request pendaftaran baru di folder *Auth*, kemudian jalankan request *Login*.
4. Perhatikan bahwa di Tab Environment, variabel `auth_token`, `business_id` akan terisi otomatis. Anda dapat langsung menguji endpoint proteksi `Tenant Dashboard Access`.
