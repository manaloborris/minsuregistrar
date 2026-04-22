<?php $title = 'Admin Account Details - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$account = $account ?? [];
$departments = $departments ?? [];
$supportsAdminManagement = $supportsAdminManagement ?? false;

$role = strtolower((string) ($account['role'] ?? 'admin'));
$isSuperadminAccount = $role === 'superadmin';
$statusClass = ((int) ($account['is_active'] ?? 1) === 1)
    ? 'bg-emerald-100 text-emerald-700'
    : 'bg-slate-100 text-slate-700';
$statusText = ((int) ($account['is_active'] ?? 1) === 1) ? 'Active' : 'Inactive';
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <div class="mb-6">
                <a href="<?= url('admin/manage-admins') ?>" class="page-back-link">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Manage Admins
                </a>
            </div>

            <?php if (!$supportsAdminManagement): ?>
                <section class="bubble-card p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
                        Admin account management columns are missing in your database. Run the superadmin migration SQL first.
                    </div>
                </section>
            <?php else: ?>
                <section class="bubble-card mb-6 p-6">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-extrabold text-slate-800"><?= esc((string) ($account['name'] ?? 'Admin Account')) ?></h1>
                            <p class="mt-1 text-sm text-slate-600">Admin ID: <span class="font-bold text-slate-800">#<?= esc((string) ($account['admin_id'] ?? '')) ?></span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Role</p>
                            <p class="mt-1 rounded-full px-3 py-1 text-sm font-bold <?= $isSuperadminAccount ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                <?= $isSuperadminAccount ? 'Superadmin' : 'Admin' ?>
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 border-t border-slate-100 pt-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Email</p>
                            <p class="mt-1 truncate text-sm font-bold text-slate-800"><?= esc((string) ($account['email'] ?? '-')) ?></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Username</p>
                            <p class="mt-1 text-sm font-bold text-slate-800"><?= esc((string) ($account['username'] ?? '-')) ?></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Department</p>
                            <p class="mt-1 text-sm font-bold text-slate-800">
                                <?php if ($isSuperadminAccount): ?>
                                    Global
                                <?php else: ?>
                                    <?php
                                    $deptLabel = 'Unassigned';
                                    foreach ($departments as $dept) {
                                        if ((int) ($dept['id'] ?? 0) === (int) ($account['department_id'] ?? 0)) {
                                            $deptLabel = (string) ($dept['code'] ?? 'Unassigned');
                                            break;
                                        }
                                    }
                                    ?>
                                    <?= esc($deptLabel) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-wider text-slate-500">Status</p>
                            <p class="mt-1 inline-block rounded-full px-3 py-1 text-sm font-bold <?= esc($statusClass) ?>"><?= esc($statusText) ?></p>
                        </div>
                    </div>
                </section>

                <section class="bubble-card mb-6 p-6">
                    <h2 class="mb-4 text-lg font-extrabold text-slate-800">Update Account Information</h2>
                    <form method="post" action="<?= url('admin/manage-admins/update') ?>" class="grid gap-4 md:grid-cols-2">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="account_id" value="<?= esc((string) ($account['admin_id'] ?? '0')) ?>">
                        <input type="hidden" name="role" value="<?= esc($isSuperadminAccount ? 'superadmin' : 'admin') ?>">
                        <input type="hidden" name="redirect_to" value="<?= esc('admin/manage-admins/' . (int) ($account['admin_id'] ?? 0)) ?>">

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Full Name</label>
                            <input name="name" required value="<?= esc((string) ($account['name'] ?? '')) ?>" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Email</label>
                            <input type="email" name="email" required value="<?= esc((string) ($account['email'] ?? '')) ?>" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Username</label>
                            <input name="username" required value="<?= esc((string) ($account['username'] ?? '')) ?>" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Department</label>
                            <?php if ($isSuperadminAccount): ?>
                                <input type="hidden" name="department_id" value="">
                                <div class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-bold text-blue-700">Global (no department)</div>
                            <?php else: ?>
                                <select name="department_id" required class="mt-2 w-full rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= esc((string) ($dept['id'] ?? '')) ?>" <?= ((int) ($account['department_id'] ?? 0) === (int) ($dept['id'] ?? 0)) ? 'selected' : '' ?>>
                                            <?= esc((string) ($dept['code'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="md:col-span-2 rounded-lg bg-slate-50 p-4">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="is_active" value="1" <?= ((int) ($account['is_active'] ?? 1) === 1) ? 'checked' : '' ?> class="rounded border-emerald-300">
                                Active account
                            </label>
                        </div>

                        <button class="md:col-span-2 rounded-lg bg-emerald-500 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-600">Save Account Changes</button>
                    </form>
                </section>

                <section class="bubble-card p-6">
                    <h2 class="mb-4 text-lg font-extrabold text-slate-800">Reset Account Password</h2>
                    <form method="post" action="<?= url('admin/manage-admins/reset-password') ?>" class="space-y-4">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="account_id" value="<?= esc((string) ($account['admin_id'] ?? '0')) ?>">
                        <input type="hidden" name="redirect_to" value="<?= esc('admin/manage-admins/' . (int) ($account['admin_id'] ?? 0)) ?>">

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">New Password</label>
                            <input type="password" name="new_password" required minlength="8" placeholder="At least 8 characters" class="mt-2 w-full rounded-lg border border-red-200 px-4 py-3 text-sm focus:border-red-500 focus:outline-none">
                        </div>

                        <button class="rounded-lg bg-red-500 px-4 py-3 text-sm font-bold text-white hover:bg-red-600">Reset Password</button>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
