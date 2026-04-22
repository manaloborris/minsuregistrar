<?php $title = 'Admin Notifications - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Notifications & Audit Logs</h1>
                <p class="mt-1 text-sm text-slate-600">Latest admin actions from the audit_logs table.</p>

                <div class="mt-5 space-y-3">
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $action = strtolower($log['action'] ?? '');
                            $tag = 'General';
                            $tagClass = 'bg-slate-100 text-slate-700';

                            if (str_contains($action, 'rejected') || str_contains($action, 'required file')) {
                                $tag = 'Attention';
                                $tagClass = 'bg-amber-100 text-amber-700';
                            } elseif (str_contains($action, 'completed') || str_contains($action, 'approved') || str_contains($action, 'ready for pickup')) {
                                $tag = 'Update';
                                $tagClass = 'bg-emerald-100 text-emerald-700';
                            }
                        ?>
                        <article class="soft-panel p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-slate-700"><?= esc($log['action']) ?></p>
                                <div class="flex items-center gap-2">
                                    <span class="status-pill <?= esc($tagClass) ?>"><?= esc($tag) ?></span>
                                    <span class="status-pill bg-emerald-100 text-emerald-700"><?= esc($log['admin_name'] ?? 'System') ?></span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><?= esc($log['created_at']) ?></p>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($logs)): ?>
                        <div class="soft-panel p-4 text-center text-sm text-slate-500">No admin logs found.</div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

