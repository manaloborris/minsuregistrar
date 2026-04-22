<?php $title = 'Reports - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<?php $departmentCode = trim((string) ($departmentCode ?? '')); ?>
<?php $isSuperAdmin = (bool) ($isSuperAdmin ?? false); ?>
<?php $departmentOverview = is_array($departmentOverview ?? null) ? $departmentOverview : []; ?>
<?php $courseOptions = is_array($courseOptions ?? null) ? $courseOptions : []; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 space-y-6 fade-in">
            <section class="bubble-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Reports Module</h1>
                        <p class="mt-1 text-sm text-slate-600">Monthly request reports, document statistics, and student request summaries.</p>
                        <?php if ($isSuperAdmin): ?>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-sky-700">Superadmin global scope: all departments</p>
                        <?php elseif ($departmentCode !== ''): ?>
                            <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700">Scoped to department: <?= esc($departmentCode) ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?= url('admin/reports/pdf') ?>" class="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-white">Export to PDF</a>
                </div>
            </section>

            <?php if ($isSuperAdmin): ?>
                <section class="bubble-card p-6">
                    <h2 class="text-lg font-extrabold text-slate-800">Department Overview</h2>
                    <p class="mt-1 text-sm text-slate-600">Cross-department snapshot for superadmin governance and trend comparison.</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[860px] text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Department</th>
                                    <th class="px-3 py-2">Total Students</th>
                                    <th class="px-3 py-2">Total Requests</th>
                                    <th class="px-3 py-2">Active Requests</th>
                                    <th class="px-3 py-2">Completed</th>
                                    <th class="px-3 py-2">Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departmentOverview as $row): ?>
                                    <?php $code = strtoupper(trim((string) ($row['department_code'] ?? ''))); ?>
                                    <?php $label = $courseOptions[$code] ?? $code; ?>
                                    <tr class="border-t border-sky-50">
                                        <td class="px-3 py-2 text-slate-700"><?= esc($label) ?></td>
                                        <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['total_students'] ?? 0)) ?></td>
                                        <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['total_requests'] ?? 0)) ?></td>
                                        <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['active_requests'] ?? 0)) ?></td>
                                        <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['completed_requests'] ?? 0)) ?></td>
                                        <td class="px-3 py-2 text-slate-700"><?= esc((string) ($row['rejected_requests'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($departmentOverview)): ?><tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">No records found.</td></tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <section class="bubble-card p-6">
                <h2 class="text-lg font-extrabold text-slate-800">Monthly Request Reports</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[460px] text-sm">
                        <thead><tr class="text-left text-slate-500"><th class="px-3 py-2">Month</th><th class="px-3 py-2">Total Requests</th></tr></thead>
                        <tbody>
                        <?php foreach ($monthly as $row): ?>
                            <tr class="border-t border-emerald-50"><td class="px-3 py-2 text-slate-700"><?= esc($row['month']) ?></td><td class="px-3 py-2 text-slate-700"><?= esc((string) $row['total_requests']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($monthly)): ?><tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No records found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bubble-card p-6">
                <h2 class="text-lg font-extrabold text-slate-800">Document Statistics</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[460px] text-sm">
                        <thead><tr class="text-left text-slate-500"><th class="px-3 py-2">Document Type</th><th class="px-3 py-2">Total</th></tr></thead>
                        <tbody>
                        <?php foreach ($documents as $row): ?>
                            <tr class="border-t border-emerald-50"><td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td><td class="px-3 py-2 text-slate-700"><?= esc((string) $row['total']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($documents)): ?><tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No records found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bubble-card p-6">
                <h2 class="text-lg font-extrabold text-slate-800">Student Request Summaries</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead><tr class="text-left text-slate-500"><th class="px-3 py-2">Student ID</th><th class="px-3 py-2">Student Name</th><th class="px-3 py-2">Total Requests</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $row): ?>
                            <tr class="border-t border-emerald-50"><td class="px-3 py-2 text-slate-700"><?= esc($row['student_id']) ?></td><td class="px-3 py-2 text-slate-700"><?= esc($row['student_name']) ?></td><td class="px-3 py-2 text-slate-700"><?= esc((string) $row['total_requests']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($students)): ?><tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">No records found.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

