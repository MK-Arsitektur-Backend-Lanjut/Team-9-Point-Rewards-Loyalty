<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($title ?? 'Membership'); ?></title>
    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #172033;
            --muted: #5a667f;
            --panel: #ffffff;
            --line: #d8e0ef;
            --brand: #1363df;
            --brand-soft: #e8f1ff;
            --ok: #0a8f55;
            --warn: #b45f06;
            --danger: #c62828;
            --radius: 14px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 0% 0%, rgba(19, 99, 223, 0.08), transparent 30%),
                var(--bg);
        }

        .shell {
            max-width: 1180px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid var(--line);
            border-radius: var(--radius);
        }

        .topbar h1 {
            margin: 0;
            font-size: 24px;
        }

        .topbar p {
            margin: 4px 0 0;
            color: var(--muted);
        }

        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .nav a {
            text-decoration: none;
            color: var(--ink);
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            font-weight: 600;
        }

        .nav a.active,
        .nav a:hover {
            background: var(--brand-soft);
            border-color: #bad3ff;
        }

        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            font-weight: 600;
        }

        .alert.success {
            background: #ebfdf4;
            border-color: #b9f0d3;
            color: #0f6a43;
        }

        .alert.error {
            background: #fff1f1;
            border-color: #f1b7b7;
            color: #8b1b1b;
        }

        .panel {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: 0 12px 28px rgba(18, 50, 102, 0.06);
        }

        .grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        .summary {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-bottom: 20px;
        }

        .card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
        }

        .card small {
            display: block;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .card strong {
            font-size: 24px;
        }

        .section-title {
            margin: 0 0 12px;
            font-size: 20px;
        }

        form {
            display: grid;
            gap: 12px;
        }

        .form-row {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid #cbd6eb;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
        }

        button {
            border: 0;
            border-radius: 10px;
            padding: 11px 14px;
            font-weight: 700;
            cursor: pointer;
            background: var(--brand);
            color: #fff;
        }

        button.secondary {
            background: var(--brand-soft);
            color: #0d3f8f;
        }

        .muted {
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }

        th {
            color: var(--muted);
            font-weight: 700;
        }

        .status-badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-active {
            background: #e9fbf1;
            color: var(--ok);
        }

        .status-expired {
            background: #fff4e5;
            color: var(--warn);
        }

        .status-redeemed {
            background: #f1f2f8;
            color: #3f4b67;
        }

        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: -4px;
        }

        .pagination {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .pagination a, .pagination span {
            text-decoration: none;
            color: var(--ink);
            border: 1px solid var(--line);
            padding: 8px 12px;
            border-radius: 8px;
            background: #fff;
        }

        .pagination .active {
            background: var(--brand-soft);
            border-color: #bad3ff;
        }

        .pagination svg {
            display: none !important;
        }
        @media (max-width: 780px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .nav {
                justify-content: flex-start;
            }
        }
    </style>
    <?php echo e($styles ?? ''); ?>

</head>
<body>
    <div class="shell">
        <header class="topbar">
            <div>
                <h1><?php echo e($title ?? 'Membership'); ?></h1>
                <p><?php echo e($subtitle ?? 'Kelola akun dan statement poin Anda.'); ?></p>
            </div>
            <nav class="nav">
                <a href="/" class="<?php echo e(request()->is('/') ? 'active' : ''); ?>">Dashboard</a>
                <a href="<?php echo e(route('user.profile')); ?>" class="<?php echo e(request()->routeIs('user.profile') ? 'active' : ''); ?>">Profil</a>
                <a href="<?php echo e(route('user.statement')); ?>" class="<?php echo e(request()->routeIs('user.statement') ? 'active' : ''); ?>">Statement</a>
                <a href="<?php echo e(route('logout')); ?>">Logout</a>
            </nav>
        </header>

        <?php if(session('success')): ?>
            <div class="alert success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert error">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>
</body>
</html>
<?php /**PATH /app/resources/views/layouts/user-dashboard.blade.php ENDPATH**/ ?>