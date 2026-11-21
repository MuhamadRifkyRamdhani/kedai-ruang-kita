@extends('layouts.admin')

@section('title', 'Staf · Kedai Ruang Kita')
@section('crumb', 'Staf')

@section('content')
    <div class="page-head">
        <div>
            <h1>Akun staf</h1>
            <p>Buat akun untuk kasir atau pengelola. Staf masuk dengan nama dan kata sandi di bawah ini.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="success" role="alert" style="background:#fae5e2;color:#a35e55">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="success" role="alert" style="background:#fae5e2;color:#a35e55">{{ $errors->first() }}</div>
    @endif

    <div class="panels" style="grid-template-columns:1fr 1.6fr">
        <section class="panel">
            <h2>Tambah staf</h2>
            <p class="panel-sub">Akun baru langsung bisa dipakai untuk masuk.</p>
            <form method="POST" action="{{ route('admin.staff.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="field full">
                        <label for="staff-name">Nama</label>
                        <input id="staff-name" name="name" required maxlength="60" value="{{ old('name') }}" placeholder="Contoh: Dewi Kasir">
                    </div>
                    <div class="field full">
                        <label for="staff-password">Kata sandi (min. 8 karakter)</label>
                        <input id="staff-password" type="password" name="password" required minlength="8" autocomplete="new-password">
                    </div>
                    <div class="field full">
                        <label for="staff-role">Peran</label>
                        <select id="staff-role" name="role">
                            <option value="kasir" @selected(old('role', 'kasir') === 'kasir')>Kasir (antrean, pesanan, ketersediaan menu)</option>
                            <option value="admin" @selected(old('role') === 'admin')>Pengelola (akses penuh)</option>
                        </select>
                    </div>
                </div>
                <button class="btn" style="margin-top:12px" type="submit">+ Buat akun</button>
            </form>
        </section>

        <section class="panel" style="padding:0;overflow:hidden">
            <div style="padding:17px 18px">
                <h2>Daftar staf</h2>
                <p class="panel-sub" style="margin:4px 0 0">{{ $staff->count() }} akun terdaftar.</p>
            </div>
            <div class="table-wrap" style="border:0;border-radius:0">
                <table class="table">
                    <thead>
                        <tr><th>NAMA</th><th>PERAN</th><th>GANTI KATA SANDI</th><th>AKSI</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($staff as $member)
                            <tr>
                                <td><b>{{ $member->name }}</b>@if ($member->is(auth()->user())) <span class="muted">(kamu)</span>@endif</td>
                                <td><span class="badge {{ $member->role?->value === 'admin' ? 'siap' : 'diproses' }}">{{ $member->role?->label() }}</span></td>
                                <td>
                                    <form class="inline" method="POST" action="{{ route('admin.staff.update', $member) }}" style="display:flex;gap:6px">
                                        @csrf
                                        @method('PATCH')
                                        <input class="select" type="password" name="password" required minlength="8" placeholder="Sandi baru" autocomplete="new-password" style="width:130px">
                                        <button class="btn light small" type="submit">Simpan</button>
                                    </form>
                                </td>
                                <td>
                                    @unless ($member->is(auth()->user()))
                                        <form class="inline" method="POST" action="{{ route('admin.staff.destroy', $member) }}" onsubmit="return confirm('Hapus akun {{ $member->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn small" type="submit" style="background:#fae5e2;color:#a35e55">Hapus</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
