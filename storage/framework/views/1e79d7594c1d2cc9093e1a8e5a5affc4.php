<?php
    $title = 'Statement Poin';
    $subtitle = 'Pantau saldo, poin aktif, dan riwayat transaksi Anda.';
?>



<?php $__env->startSection('content'); ?>
    <div class="summary">
        <div class="card">
            <small>Saldo Saat Ini</small>
            <strong><?php echo e(number_format($statement['summary']['current_balance'] ?? 0)); ?></strong>
        </div>
        <div class="card">
            <small>Poin Aktif</small>
            <strong><?php echo e(number_format($statement['summary']['active_points'] ?? 0)); ?></strong>
        </div>
        <div class="card">
            <small>Poin Akan Kedaluwarsa</small>
            <strong><?php echo e(number_format($statement['summary']['points_expiring_soon'] ?? 0)); ?></strong>
        </div>
    </div>

    <section class="panel">
        <h2 class="section-title">Filter Statement</h2>
        <form action="<?php echo e(route('user.statement')); ?>" method="GET" class="form-row">
            <div>
                <label for="start_date">Tanggal Mulai</label>
                <input id="start_date" name="start_date" type="date" value="<?php echo e(old('start_date', $filters['start_date'] ?? '')); ?>">
            </div>

            <div>
                <label for="end_date">Tanggal Akhir</label>
                <input id="end_date" name="end_date" type="date" value="<?php echo e(old('end_date', $filters['end_date'] ?? '')); ?>">
            </div>

            <div>
                <label for="activity_code">Kode Aktivitas</label>
                <input id="activity_code" name="activity_code" type="text" value="<?php echo e(old('activity_code', $filters['activity_code'] ?? '')); ?>" placeholder="Contoh: DAILY_LOGIN">
            </div>

            <div>
                <label for="point_status">Status Poin</label>
                <select id="point_status" name="point_status">
                    <option value="">Semua</option>
                    <option value="active" <?php echo e(old('point_status', $filters['point_status'] ?? '') === 'active' ? 'selected' : ''); ?>>Active</option>
                    <option value="expired" <?php echo e(old('point_status', $filters['point_status'] ?? '') === 'expired' ? 'selected' : ''); ?>>Expired</option>
                    <option value="redeemed" <?php echo e(old('point_status', $filters['point_status'] ?? '') === 'redeemed' ? 'selected' : ''); ?>>Redeemed</option>
                </select>
            </div>

            <div>
                <label for="per_page">Baris per Halaman</label>
                <select id="per_page" name="per_page">
                    <?php $__currentLoopData = [10, 15, 25, 50]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $size): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($size); ?>" <?php echo e(old('per_page', $filters['per_page'] ?? 15) == $size ? 'selected' : ''); ?>><?php echo e($size); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                <p class="muted">Diperbarui pada <?php echo e(\Carbon\Carbon::parse($statement['generated_at'])->translatedFormat('d F Y H:i')); ?></p>
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
                    <?php $__empty_1 = true; $__currentLoopData = $statement['history']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e(optional($log->earned_at)->format('d/m/Y H:i') ?? '-'); ?></td>
                            <td><?php echo e($log->activity_code); ?></td>
                            <td><?php echo e(number_format($log->points_earned)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo e($log->point_status); ?>"><?php echo e($log->point_status); ?></span>
                            </td>
                            <td><?php echo e(optional($log->expired_at)->format('d/m/Y H:i') ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5">Belum ada riwayat poin untuk periode ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?php echo e($statement['history']->withQueryString()->onEachSide(1)->links()); ?>

        </div>
    </section>

    <?php $__env->startPush('styles'); ?>
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
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.user-dashboard', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /app/resources/views/user/statement.blade.php ENDPATH**/ ?>