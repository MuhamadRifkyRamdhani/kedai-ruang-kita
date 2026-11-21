<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /** Meja 1-12 seperti sebelumnya; selanjutnya dikelola dari halaman QR Meja. */
    public function run(): void
    {
        foreach (range(1, 12) as $number) {
            RestaurantTable::firstOrCreate(['number' => $number]);
        }
    }
}
