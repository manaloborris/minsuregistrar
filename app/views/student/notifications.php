<?php $title = 'Notifications - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Notifications</h1>
                <p class="mt-1 text-sm text-slate-600">Request updates and registrar announcements.</p>

                <div class="mt-5 space-y-3">
                    <?php foreach ($notifications as $note): ?>
                        <?php
                            $msg = strtolower($note['message'] ?? '');
                            $typeLabel = 'Info';
                            $typeClass = 'bg-slate-100 text-slate-700';

                            if (str_contains($msg, 'rejected') || str_contains($msg, 'required file') || str_contains($msg, 'follow-up')) {
                                $typeLabel = 'Action Needed';
                                $typeClass = 'bg-amber-100 text-amber-700';
                            } elseif (str_contains($msg, 'approved') || str_contains($msg, 'ready for pickup') || str_contains($msg, 'completed')) {
                                $typeLabel = 'Status Update';
                                $typeClass = 'bg-emerald-100 text-emerald-700';
                            }
                        ?>
                        <article class="soft-panel p-4 <?= strtolower($note['status']) === 'read' ? '' : 'ring-1 ring-emerald-200' ?>">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-bold text-slate-700"><?= esc($note['message']) ?></p>
                                <div class="flex items-center gap-2">
                                    <span class="status-pill <?= esc($typeClass) ?>"><?= esc($typeLabel) ?></span>
                                    <span class="status-pill <?= strtolower($note['status']) === 'read' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700' ?>"><?= esc($note['status']) ?></span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500"><?= esc($note['created_at']) ?></p>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($notifications)): ?>
                        <div class="soft-panel p-4 text-center text-sm text-slate-500">No notifications available.</div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

