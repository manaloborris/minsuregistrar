<?php $title = 'Student Dashboard - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 space-y-6 fade-in">
            <div class="bubble-card p-6">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700">Student Dashboard</p>
                <h1 class="mt-2 text-2xl font-extrabold text-slate-800">Welcome, <?= esc($_SESSION['auth']['student']['name'] ?? 'Student') ?></h1>
                <p class="text-sm text-slate-600">Monitor all your registrar requests and recent transactions.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="bubble-card p-5"><p class="text-xs font-bold uppercase text-slate-500">Pending Requests</p><h3 class="mt-2 text-3xl font-extrabold text-amber-500"><?= esc((string) ($stats['pending'] ?? 0)) ?></h3></div>
                <div class="bubble-card p-5"><p class="text-xs font-bold uppercase text-slate-500">Processing Requests</p><h3 class="mt-2 text-3xl font-extrabold text-blue-500"><?= esc((string) ($stats['processing'] ?? 0)) ?></h3></div>
                <div class="bubble-card p-5"><p class="text-xs font-bold uppercase text-slate-500">Completed Requests</p><h3 class="mt-2 text-3xl font-extrabold text-emerald-600"><?= esc((string) ($stats['completed'] ?? 0)) ?></h3></div>
                <div class="bubble-card p-5"><p class="text-xs font-bold uppercase text-slate-500">Total Requests</p><h3 class="mt-2 text-3xl font-extrabold text-slate-800"><?= esc((string) ($stats['total'] ?? 0)) ?></h3></div>
            </div>

            <section class="bubble-card p-6">
                <h2 class="text-lg font-extrabold text-slate-800">Recent Activity</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Document Type</th>
                                <th class="px-3 py-2">Request Date</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentActivity as $row): ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td>
                                <td class="px-3 py-2 text-slate-600"><?= esc($row['request_date']) ?></td>
                                <td class="px-3 py-2">
                                    <span class="status-pill bg-emerald-100 text-emerald-700"><?= esc($row['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentActivity)): ?>
                            <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">No recent activity found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

