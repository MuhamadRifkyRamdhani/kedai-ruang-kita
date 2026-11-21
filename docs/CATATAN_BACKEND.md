# Catatan Pengerjaan Backend

Dokumen ini merangkum apa yang ditambahkan ke backend prototype, mengikuti `ARAHAN_BACKEND.md`.
Route name, nama variabel view, dan status (`baru`, `diproses`, `siap`, `selesai`, `dibatalkan`) tidak diubah,
jadi tampilan frontend tetap berjalan.

## 1. Controller dipecah per fitur

`AdminController` (satu file untuk semua halaman admin) dipecah menjadi:

| Controller | Tugas |
|---|---|
| `StorefrontController`, `OrderController` | katalog pelanggan, kirim pesanan, halaman konfirmasi |
| `Admin\AuthController` | login, logout |
| `Admin\DashboardController` | dashboard (logika hitung ada di `DashboardService`) |
| `Admin\OrderBoardController` | antrean dapur + pindah status |
| `Admin\OrderHistoryController` | riwayat selesai/dibatalkan |
| `Admin\MenuController` | tambah, edit, hapus, tandai habis |
| `Admin\TableController` | daftar meja/QR, tambah dan nonaktifkan meja |
| `Admin\ChannelController` | status kanal online |
| `Admin\ExternalOrderController` | form catat pesanan marketplace |
| `Api\PlatformWebhookController` | penerima webhook GoFood/GrabFood/ShopeeFood |

Logika bisnis ada di `app/Services` (`OrderService`, `MarketplaceOrderService`, `DashboardService`).
Validasi form ada di `app/Http/Requests`.

## 2. Pekerjaan di ARAHAN_BACKEND yang sudah dikerjakan

| Poin arahan | Yang dikerjakan |
|---|---|
| Akun & role (4) | Login memakai tabel `users` (password di-hash), batas 5 percobaan gagal, role `admin` dan `kasir`. Kasir tidak bisa tambah/edit/hapus menu dan kelola meja. |
| Konfirmasi pesanan bisa ditebak (4) | `/pesanan/{token}` memakai token acak 40 karakter, bukan ID urut. Pesanan lama otomatis diberi token oleh migration. |
| Audit perubahan status (4) | Tabel `order_status_logs`: siapa, dari status apa, ke status apa, kapan. |
| Tabel meja di database (5) | Tabel `restaurant_tables`. `/meja/{n}` hanya aktif untuk meja terdaftar; nomor meja di form pesanan divalidasi. |
| ID order platform & idempotensi (1, 2) | Kolom `platform_order_id` + unique `(source, platform_order_id)`. Form marketplace sekarang mengisi kolom ini, bukan menimpa kode pesanan. |
| Webhook + pencatatan event (1) | `POST /api/webhooks/{gofood\|grabfood\|shopeefood}`: verifikasi HMAC `X-Signature`, tiap request dicatat di `webhook_events`, retry/duplikat tidak membuat pesanan ganda. |
| Feature tests (6) | 27 test: pemesanan valid/invalid, harga dari server, batas quantity, alur status, otorisasi role, laporan, webhook (signature, duplikat, payload). |

Perbaikan kecil: baris menu yang sama dalam satu pesanan digabung (sebelumnya bisa memicu error "menu habis" palsu),
menu habis menampilkan pesan di halaman menu (bukan halaman error), `public/images/menu-placeholder.svg` ditambahkan
karena dirujuk `storefront.blade.php` tapi belum ada, dan `phpunit.xml` memakai SQLite in-memory supaya test tidak menghapus data lokal.

## 3. Yang BELUM dikerjakan

- **Integrasi resmi GoFood/GrabFood/ShopeeFood.** Webhook memakai format payload normalisasi buatan tim. Setelah kredensial
  merchant dan dokumentasi resmi ada, tambahkan mapper per platform (payload resmi -> format normalisasi), validasi timestamp
  sesuai dokumentasi, pemetaan status platform, queue/job, dan rekonsiliasi.
- Isi `GOFOOD_WEBHOOK_SECRET`, `GRABFOOD_WEBHOOK_SECRET`, `SHOPEEFOOD_WEBHOOK_SECRET` di `.env` setelah ada. Kosong = webhook ditolak (503).
- Stok/jumlah bahan, ekspor laporan, push realtime, reset password, dan deployment produksi (MySQL, HTTPS, backup).
- Kredensial demo masih tampil di halaman login (`admin/login.blade.php`). Hapus sebelum dipublikasikan.
- Kode ini belum dijalankan di environment PHP saat dibuat. Jalankan `php artisan test` dulu.

## 4. Cara pasang di project yang sudah ada

```powershell
php artisan migrate --seed   # migration baru tidak mengubah tabel lama; data pesanan lama tetap aman
php artisan test
```

Akun awal (UserSeeder, dari `.env`): `admin@ruangkita.id` / `ruangkita123` (admin) dan `kasir@ruangkita.id` / `kasirkita123` (kasir).
Jika database lokal sudah pernah di-seed sebelum update ini, `migrate --seed` aman dijalankan ulang.
