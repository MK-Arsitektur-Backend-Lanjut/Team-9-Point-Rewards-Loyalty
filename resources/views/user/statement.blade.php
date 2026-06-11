@php
    $title = 'Statement Poin';
    $subtitle = 'Pantau saldo, poin aktif, dan riwayat transaksi Anda.';
@endphp

@extends('layouts.user-dashboard')

@section('content')
    <div class="summary">
        <div class="card">
            <small>Saldo Saat Ini</small>
            <strong>{{ number_format($statement['summary']['current_balance'] ?? 0) }}</strong>
        </div>
        <div class="card">
            <small>Poin Aktif</small>
            <strong>{{ number_format($statement['summary']['active_points'] ?? 0) }}</strong>
        </div>
        <div class="card">
            <small>Poin Akan Kedaluwarsa</small>
            <strong>{{ number_format($statement['summary']['points_expiring_soon'] ?? 0) }}</strong>
        </div>
    </div>

    <section class="panel">
        <h2 class="section-title">Filter Statement</h2>
        <form action="{{ route('user.statement') }}" method="GET" class="form-row">
            <div>
                <label for="start_date">Tanggal Mulai</label>
                <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $filters['start_date'] ?? '') }}">
            </div>

            <div>
                <label for="end_date">Tanggal Akhir</label>
                <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $filters['end_date'] ?? '') }}">
            </div>

            <div>
                <label for="activity_code">Kode Aktivitas</label>
                <input id="activity_code" name="activity_code" type="text" value="{{ old('activity_code', $filters['activity_code'] ?? '') }}" placeholder="Contoh: DAILY_LOGIN">
            </div>

            <div>
                <label for="point_status">Status Poin</label>
                <select id="point_status" name="point_status">
                    <option value="">Semua</option>
                    <option value="active" {{ old('point_status', $filters['point_status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ old('point_status', $filters['point_status'] ?? '') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="redeemed" {{ old('point_status', $filters['point_status'] ?? '') === 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                </select>
            </div>

            <div>
                <label for="per_page">Baris per Halaman</label>
                <select id="per_page" name="per_page">
                    @foreach([10, 15, 25, 50] as $size)
                        <option value="{{ $size }}" {{ old('per_page', $filters['per_page'] ?? 15) == $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label>&nbsp;</label>
                <button type="submit">Terapkan Filter</button>
            </div>
        </form>
    </section>

    <section class="panel" style="margin-top: 18px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:12px; flex-wrap:wrap;">
            <div>
                <h2 class="section-title" style="margin-bottom: 4px;">Riwayat Poin</h2>
                <p class="muted">Diperbarui pada {{ \Carbon\Carbon::parse($statement['generated_at'])->translatedFormat('d F Y H:i') }}</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Aktivitas</th>
                        <th>Poin</th>
                        <th>Status</th>
                        <th>Kedaluwarsa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['history'] as $log)
                        <tr>
                            <td>{{ optional($log->earned_at)->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $log->activity_code }}</td>
                            <td>{{ number_format($log->points_earned) }}</td>
                            <td>
                                <span class="status-badge status-{{ $log->point_status }}">{{ $log->point_status }}</span>
                            </td>
                            <td>{{ optional($log->expired_at)->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">Belum ada riwayat poin untuk periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">
            {{ $statement['history']->withQueryString()->onEachSide(1)->links() }}
        </div>
    </section>

    @push('styles')
    <style>
        /* Menghilangkan link previous dan next berdasarkan atribut rel */
        .pagination a[rel="prev"], 
        .pagination span[aria-hidden="true"], 
        .pagination a[rel="next"] {
            display: none !important;
        }
        
        /* Menghilangkan SVG (panah) jika menggunakan Tailwind pagination */
        .pagination svg {
            display: none !important;
        }
    </style>
    @endpush
@endsection
