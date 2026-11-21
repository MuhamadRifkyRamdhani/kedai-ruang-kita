<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRestaurantTableRequest;
use App\Models\RestaurantTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Halaman "QR meja". Daftar meja sekarang dari database, bukan angka 1-12 hard-coded. */
class TableController extends Controller
{
    public function index(): View
    {
        // View mengharapkan daftar nomor meja; URL QR dibuat di view lewat route('table.menu', nomor).
        $tables = RestaurantTable::active()->orderBy('number')->pluck('number');

        return view('admin.tables', compact('tables'));
    }

    public function store(StoreRestaurantTableRequest $request): RedirectResponse
    {
        RestaurantTable::create(['number' => $request->validated('number')]);

        return back()->with('success', 'Meja baru ditambahkan.');
    }

    /** Meja dinonaktifkan (bukan dihapus) agar riwayat pesanan per meja tetap utuh. */
    public function destroy(RestaurantTable $table): RedirectResponse
    {
        $table->update(['is_active' => false]);

        return back()->with('success', 'Meja dinonaktifkan. QR-nya tidak bisa dipakai lagi.');
    }
}
