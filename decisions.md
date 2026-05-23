# VELORA — Architecture Decisions Log

> Dokumen ini mencatat semua keputusan arsitektur yang telah difinalisasi.
> Dijadikan acuan utama untuk konsistensi implementasi kedepannya.

---

## Decision #1: Frontend Stack

**Pertanyaan**: Vue/React SPA, Flutter, atau Blade?

**Keputusan**: **Next.js 15 (React) + TypeScript + Tailwind CSS**

**Alasan**:
- **Elegant & Minimalis** — Next.js + Tailwind CSS memungkinkan design system yang modern, clean, dan konsisten
- **Interaktif tapi mudah dipahami** — React ecosystem menyediakan komponen UI library terbaik (shadcn/ui, Radix UI) untuk UX yang intuitif
- **User-friendly untuk orang awam** — Setiap menu, tombol, input field akan dilengkapi:
  - 🔹 Tooltip (hover hints)
  - 🔹 Placeholder text yang deskriptif
  - 🔹 Inline validation messages (bahasa Indonesia)
  - 🔹 Onboarding tour/walkthrough untuk user baru
  - 🔹 Confirmation dialog sebelum aksi berbahaya (hapus, void, dll)
  - 🔹 Toast notifications untuk feedback aksi
  - 🔹 Empty states dengan panduan "Apa yang harus dilakukan selanjutnya"
  - 🔹 Loading states dan skeleton screens
- **SEO-ready** — Next.js SSR/SSG untuk landing page & public pages
- **TypeScript** — Type safety mengurangi bug dan mempermudah maintenance
- **Performance** — Next.js App Router untuk code splitting otomatis, fast navigation

**Alternatif yang dipertimbangkan**:
- Vue.js/Nuxt: Lebih ringan tapi ecosystem komponen UI kurang mature
- Flutter Web: Performanya masih kalah dengan web native untuk SaaS dashboard
- Blade: Server-side rendering terlalu kaku untuk POS yang butuh interaktivitas tinggi

---

## Decision #2: Receipt Printing

**Pertanyaan**: Apakah perlu integrasi thermal printer (ESC/POS)?

**Keputusan**: **Bertahap — MVP dulu, ESC/POS nanti**

### Fase 1 (MVP):
- ✅ Download PDF (invoice/receipt)
- ✅ Print via browser (`window.print()`) — support A4 dan nota kecil (58mm/80mm)
- ✅ Layout receipt yang sudah dioptimalkan untuk ukuran nota kecil
- ✅ Export invoice ke PDF

### Fase 2 (Lanjutan):
- 🔜 Integrasi ESC/POS via Laravel package
- 🔜 Support printer Bluetooth (untuk mobile)
- 🔜 Support printer LAN (untuk desktop POS)
- 🔜 Template receipt yang customizable
- 🔜 Auto-print setelah transaksi selesai

---

## Decision #3: Deployment Target

**Pertanyaan**: VPS, Docker, atau managed cloud (AWS/GCP)?

**Keputusan**: **Shared Hosting → VPS murah (bertahap)**

### Fase 1 (MVP / UMKM kecil):
- **Target**: Shared hosting atau VPS murah (Rp 50-150rb/bulan)
- **Stack**: LAMP/LEMP stack standar
- **Database**: MySQL di server yang sama
- **Redis**: Bisa skip dulu, pakai file/database driver
- **Alasan**: Client UMKM butuh yang "penting jalan dan bisa dipakai"

### Fase 2 (Scale-up / Perusahaan besar):
- **Target**: VPS lebih besar atau Docker-based deployment
- **Pisahkan**: Database server, Redis server, App server
- **CDN**: Untuk static assets
- **Backup**: Automated daily backup

### Fase 3 (Enterprise / Approved by perusahaan besar):
- **Target**: AWS/GCP/Azure
- **Container**: Docker + Kubernetes
- **Database**: Managed MySQL (RDS/Cloud SQL)
- **Full infrastructure** sesuai blueprint `infrastructure_architecture_part2.md`

**Implikasi untuk kode**:
- Kode harus portable — jangan hardcode cloud-specific features
- Config via `.env` — mudah switch environment
- Redis optional — harus ada fallback ke file/database driver
- Queue optional — sync mode untuk shared hosting, Redis queue untuk VPS+

---

## Decision #4: OpenAI API Key Strategy

**Pertanyaan**: Platform-level key (Velora pays) atau per-tenant key?

**Keputusan**: **Platform-level (Velora yang simpan dan kelola)**

**Alasan**:
- Client UMKM tidak perlu bikin akun OpenAI sendiri
- Setup lebih simpel — user tidak perlu tahu tentang API key
- Support lebih mudah — semua under 1 account
- Cost management terpusat — tracking usage per tenant di `ai_usage_logs`
- Rate limiting terkontrol dari platform

**Implementasi**:
```
.env:
OPENAI_API_KEY=sk-xxxx    # Platform-level key
OPENAI_MODEL=gpt-4o
AI_MAX_TOKENS_PER_REQUEST=4000
AI_CACHE_TTL=300           # 5 menit
```

**Cost Handling**:
- AI cost sudah masuk ke harga subscription
- Usage tracking per tenant per bulan
- Quota limit per plan tier (sesuai `ai_architecture.md`)
- Over-quota → fitur AI disabled sampai next month

---

## Decision #5: UX Philosophy

**Keputusan**: Design yang **elegant, minimalis, interaktif, dan ramah untuk user awam**

**Prinsip UX**:
1. **Jangan biarkan user bingung** — Setiap elemen harus self-explanatory
2. **Guide, don't assume** — Berikan panduan di setiap langkah
3. **Feedback instan** — User harus tahu apa yang terjadi setelah setiap aksi
4. **Error prevention** — Validasi sebelum submit, confirmation sebelum delete
5. **Progressive disclosure** — Tampilkan yang penting dulu, detail bisa di-expand

**Komponen UX wajib**:
| Komponen | Fungsi |
|----------|--------|
| **Tooltip** | Penjelasan singkat saat hover elemen |
| **Toast** | Notifikasi sukses/error setelah aksi |
| **Dialog Konfirmasi** | Sebelum hapus, void, atau aksi berbahaya |
| **Empty State** | Panduan saat data kosong ("Belum ada produk, klik di sini untuk tambah") |
| **Inline Validation** | Pesan error langsung di bawah input field |
| **Breadcrumb** | Navigasi posisi user di aplikasi |
| **Search & Filter** | Pencarian mudah di setiap halaman list |
| **Skeleton Loading** | Placeholder saat data loading |
| **Onboarding Tour** | Walkthrough interaktif untuk user baru |
| **Keyboard Shortcuts** | Untuk power users (kasir) |

---

## Decision #6: API Management & Documentation Tool

**Keputusan**: **Postman**

**Alasan**:
- Postman adalah standar industri untuk pengujian dan dokumentasi API.
- Mendukung fitur *Collections* yang bisa diekspor dan dibagikan dengan tim (misalnya antara backend developer dan frontend developer).
- Mudah digunakan untuk simulasi otentikasi (mengatur Bearer Token dari endpoint login ke seluruh request lainnya secara otomatis).

**Implementasi**:
- Kita akan mengatur setiap endpoint yang selesai dibuat di Laravel langsung ke dalam sebuah file Postman Collection (contoh: `Velora_API_Collection.json`).
- File collection tersebut nanti akan disimpan di dalam repositori agar mudah diakses.

---

## Summary — Keputusan Final

| # | Area | Keputusan |
|---|------|-----------|
| 1 | Frontend | Next.js 15 + TypeScript + Tailwind CSS |
| 2 | Receipt (MVP) | PDF download + browser print (A4 & nota kecil) |
| 3 | Receipt (Phase 2) | ESC/POS + Bluetooth/LAN printer |
| 4 | Deploy (MVP) | Shared hosting → VPS murah |
| 5 | Deploy (Scale) | Docker → Cloud (AWS/GCP) |
| 6 | AI Key | Platform-level (Velora kelola) |
| 7 | UX | Elegant, minimalis, guided, user-friendly untuk awam |
| 8 | Framework | Laravel 13 (PHP 8.3+) |
| 9 | Payment | Midtrans Snap API |
| 10 | Currency | Multi-currency (default IDR) |
| 11 | Billing | Dual: Auto-Charge + Manual Transfer |
| 12 | Offline POS | Tidak — online only, subscription-gated |
| 13 | API Management | Postman (menggunakan Collection exports) |

---

*Dokumen ini adalah sumber kebenaran (source of truth) untuk semua keputusan arsitektur VELORA.*
*Update terakhir: 23 Mei 2026*
