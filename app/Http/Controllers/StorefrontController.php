<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RestaurantTable;
use Illuminate\View\View;

/** Halaman katalog pelanggan: / dan /meja/{nomor} (tujuan QR). */
class StorefrontController extends Controller
{
    public function home(): View
    {
        return $this->render(null);
    }

    public function table(int $table): View
    {
        // Hanya meja yang terdaftar dan aktif; QR lama/meja yang dihapus mendapat 404.
        abort_unless(RestaurantTable::active()->where('number', $table)->exists(), 404);

        return $this->render($table);
    }

    private function render(?int $table): View
    {
        $products = Product::available()->orderBy('category')->orderBy('name')->get();
        $categories = $products->pluck('category')->unique()->values();

        return view('storefront', compact('products', 'categories', 'table'));
    }
}
