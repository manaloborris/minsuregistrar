<?php
$title = 'Admin Dashboard - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';

$departmentCode = trim((string) ($departmentCode ?? ''));
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-amber-700">Dashboard fallback</p>
                <h1 class="mt-2 text-2xl font-extrabold text-slate-800">Analytics temporarily unavailable</h1>
                <p class="mt-2 text-sm text-slate-600">
                    The dashboard could not finish loading its analytics data. The admin route is still working, so you can continue using the system while the data issue is checked.
                </p>
                <?php if ($departmentCode !== ''): ?>
                    <p class="mt-2 text-xs font-bold uppercase tracking-wide text-emerald-700">Scoped to department: <?= esc($departmentCode) ?></p>
                <?php endif; ?>

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="<?= url('admin/manage-requests') ?>" class="page-back-link">Go to Manage Requests</a>
                    <a href="<?= url('admin/students') ?>" class="page-back-link">Go to Manage Students</a>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>