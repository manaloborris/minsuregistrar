<?php $title = 'Request Files - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in space-y-6">
            <section class="bubble-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-2xl font-extrabold text-slate-800">Uploaded Request Files</h1>
                        <p class="mt-1 text-sm text-slate-600">
                            Student: <?= esc($request['first_name'] . ' ' . $request['last_name']) ?> (<?= esc($request['student_id']) ?>)
                            | Document: <?= esc($request['document_name']) ?>
                        </p>
                    </div>
                    <a href="<?= url('admin/manage-requests') ?>" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-bold text-slate-700">Back</a>
                </div>
            </section>

            <section class="bubble-card p-6">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Uploaded At</th>
                                <th class="px-3 py-2">Path</th>
                                <th class="px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($files as $file): ?>
                            <?php $publicUrl = url('public/' . ltrim($file['file_path'], '/')); ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 text-slate-700"><?= esc($file['generated_at']) ?></td>
                                <td class="px-3 py-2 text-xs text-slate-500"><?= esc($file['file_path']) ?></td>
                                <td class="px-3 py-2">
                                    <a href="<?= esc($publicUrl) ?>" target="_blank" class="rounded-lg bg-emerald-500 px-3 py-1 text-xs font-bold text-white">Open</a>
                                    <a href="<?= esc($publicUrl) ?>" download class="ml-2 rounded-lg bg-blue-500 px-3 py-1 text-xs font-bold text-white">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($files)): ?>
                            <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">No uploaded files yet for this request.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

