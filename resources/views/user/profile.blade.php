@php
    $title = 'Profil Saya';
    $subtitle = 'Perbarui data akun dan password Anda.';
@endphp

@extends('layouts.user-dashboard')

@section('content')
    <div class="grid">
        <section class="panel">
            <h2 class="section-title">Informasi Akun</h2>
            <div class="summary">
                <div class="card">
                    <small>Nama</small>
                    <strong>{{ auth()->user()->name }}</strong>
                </div>
                <div class="card">
                    <small>Email</small>
                    <strong>{{ auth()->user()->email }}</strong>
                </div>
                <div class="card">
                    <small>Saldo Poin</small>
                    <strong>{{ number_format(auth()->user()->points_balance ?? 0) }}</strong>
                </div>
            </div>

            <p class="muted">Gunakan form di bawah untuk memperbarui profil Anda. Password tidak diubah di sini.</p>
        </section>

        <section class="panel">
            <h2 class="section-title">Perbarui Profil</h2>
            <form action="{{ route('user.profile.update') }}" method="POST">
                @csrf

                <div>
                    <label for="name">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required>
                </div>

                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required>
                </div>

                <div>
                    <button type="submit">Simpan Profil</button>
                </div>
            </form>
        </section>

        <section class="panel">
            <h2 class="section-title">Ubah Password</h2>
            <form action="{{ route('user.password.update') }}" method="POST">
                @csrf

                <div>
                    <label for="current_password">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" required>
                </div>

                <div>
                    <label for="password">Password Baru</label>
                    <input id="password" name="password" type="password" required>
                </div>

                <div>
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required>
                </div>

                <div>
                    <button type="submit" class="secondary">Ubah Password</button>
                </div>
            </form>
        </section>
    </div>
@endsection
