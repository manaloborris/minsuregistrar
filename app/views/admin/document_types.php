<?php $title = 'Document Types - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php $supportsAmount = $supportsAmount ?? false; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 space-y-6 fade-in">
            <?php if (!$supportsAmount): ?>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-bold text-amber-700">
                    Amount column is not yet available in request_types table. Custom fixed amounts may not persist until DB is updated.
                </div>
            <?php endif; ?>

            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Document Types</h1>
                <p class="mt-1 text-sm text-slate-600">Manage available request types used by students.</p>

                <form method="post" action="<?= url('admin/document-types/add') ?>" class="mt-4 flex flex-wrap gap-3">
                    <?php csrf_field(); ?>
                    <input type="text" name="document_name" required placeholder="e.g. Certificate of Enrollment" class="w-full max-w-md rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    <input type="number" name="amount" required min="0" step="0.01" value="40.00" placeholder="Amount" class="w-full max-w-[180px] rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    <button class="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-bold text-white">Add Type</button>
                </form>
                <p class="mt-2 text-xs text-slate-500">Suggested default: COR, TOR, Good Moral, and COE = PHP 40.00</p>
            </section>

            <section class="bubble-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[920px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">ID</th>
                                <th class="px-3 py-2">Document Name</th>
                                <th class="px-3 py-2">Fixed Amount</th>
                                <th class="px-3 py-2">Update</th>
                                <th class="px-3 py-2">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($requestTypes as $type): ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 font-bold text-slate-700"><?= esc((string) $type['id']) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc($type['document_name']) ?></td>
                                <td class="px-3 py-2 text-slate-700">PHP <?= esc(number_format((float) ($type['amount'] ?? 0), 2)) ?></td>
                                <td class="px-3 py-2">
                                    <form method="post" action="<?= url('admin/document-types/update') ?>" class="flex gap-2">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $type['id']) ?>">
                                        <input type="text" name="document_name" value="<?= esc($type['document_name']) ?>" class="rounded-lg border border-emerald-100 px-2 py-1 text-xs">
                                        <input type="number" name="amount" min="0" step="0.01" value="<?= esc(number_format((float) ($type['amount'] ?? 0), 2, '.', '')) ?>" class="w-24 rounded-lg border border-emerald-100 px-2 py-1 text-xs">
                                        <button class="rounded-lg bg-blue-500 px-3 py-1 text-xs font-bold text-white">Save</button>
                                    </form>
                                </td>
                                <td class="px-3 py-2">
                                    <form method="post" action="<?= url('admin/document-types/delete') ?>">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= esc((string) $type['id']) ?>">
                                        <button class="rounded-lg bg-red-500 px-3 py-1 text-xs font-bold text-white">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requestTypes)): ?>
                            <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">No document types found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

