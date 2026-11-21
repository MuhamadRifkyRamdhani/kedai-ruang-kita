<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetStaffPasswordRequest;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Akun staf dibuat dan dikelola oleh admin. Tidak ada pendaftaran publik,
 * supaya orang luar tidak bisa membuat akun yang punya akses ke panel operasional.
 */
class StaffController extends Controller
{
    public function index(): View
    {
        return view('admin.staff', [
            'staff' => User::orderBy('name')->get(),
        ]);
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $data = $request->validated();

        User::create([
            'name' => $data['name'],
            // Login memakai nama; kolom email bawaan Laravel wajib diisi, jadi dibuat otomatis dan tidak dipakai.
            'email' => 'staf-' . Str::lower(Str::random(12)) . '@ruangkita.local',
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
        ]);

        return back()->with('success', 'Akun ' . $data['name'] . ' dibuat. Berikan nama dan kata sandinya ke yang bersangkutan.');
    }

    /** Ganti kata sandi staf (mis. lupa sandi). */
    public function update(ResetStaffPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Kata sandi ' . $user->name . ' diganti.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Admin yang sedang login tidak boleh menghapus dirinya sendiri; ini juga menjamin selalu ada minimal satu admin.
        if ($user->is($request->user())) {
            return back()->with('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun ' . $user->name . ' dihapus.');
    }
}
