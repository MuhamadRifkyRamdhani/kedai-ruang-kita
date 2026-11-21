<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Akun awal. Dicari berdasarkan NAMA (karena login memakai nama dan nama unik).
     * firstOrCreate: kalau akunnya sudah ada, tidak diubah, jadi password yang sudah diganti
     * tidak ter-reset setiap kali `php artisan db:seed` dijalankan ulang.
     * Akun staf berikutnya dibuat admin lewat halaman /admin/staf.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['name' => 'Admin Kedai'],
            [
                'email' => config('ruangkita.admin_email'),
                'password' => config('ruangkita.admin_password'),
                'role' => UserRole::Admin,
            ]
        );

        User::firstOrCreate(
            ['name' => 'Kasir Kedai'],
            [
                'email' => config('ruangkita.kasir_email'),
                'password' => config('ruangkita.kasir_password'),
                'role' => UserRole::Kasir,
            ]
        );
    }
}
