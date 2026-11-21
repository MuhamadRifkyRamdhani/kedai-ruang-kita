<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveProductRequest;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Kelola katalog menu digital. */
class MenuController extends Controller
{
    public function index(): View
    {
        return $this->render(null);
    }

    public function edit(Product $product): View
    {
        return $this->render($product);
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        Product::create($request->validated() + ['available' => true]);

        return back()->with('success', 'Menu baru ditambahkan.');
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()->route('admin.menu')->with('success', 'Perubahan menu berhasil disimpan.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete(); // order_items.product_id jadi null; nama & harga di riwayat tetap tersimpan

        return redirect()->route('admin.menu')->with('success', 'Menu berhasil dihapus. Riwayat pesanan tetap tersimpan.');
    }

    /** Tombol tandai habis / tersedia (boleh dipakai kasir). */
    public function toggleStock(Product $product): RedirectResponse
    {
        $product->update(['available' => ! $product->available]);

        return back()->with('success', 'Ketersediaan menu diperbarui.');
    }

    private function render(?Product $editingProduct): View
    {
        $products = Product::orderBy('category')->orderBy('name')->get();

        return view('admin.menu', compact('products', 'editingProduct'));
    }
}
