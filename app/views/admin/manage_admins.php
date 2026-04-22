<?php $title = 'Manage Admin Accounts - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$admins = $admins ?? [];
$departments = $departments ?? [];
$supportsAdminManagement = $supportsAdminManagement ?? false;

$adminAccounts = [];
$superadminAccounts = [];
foreach ($admins as $account) {
    $role = strtolower((string) ($account['role'] ?? 'admin'));
    if ($role === 'superadmin') {
        $superadminAccounts[] = $account;
        continue;
    }

    $adminAccounts[] = $account;
}

$createTab = strtolower(trim((string) ($_GET['create'] ?? 'admin')));
if (!in_array($createTab, ['admin', 'superadmin'], true)) {
    $createTab = 'admin';
}

$departmentLookup = [];
$departmentsByAdmins = []; // Track departments that already have admin accounts
foreach ($departments as $department) {
    $departmentId = (int) ($department['id'] ?? 0);
    if ($departmentId > 0) {
        $departmentLookup[$departmentId] = strtoupper(trim((string) ($department['code'] ?? '')));
    }
}

// Build list of departments that already have admin accounts
foreach ($adminAccounts as $account) {
    $deptId = (int) ($account['department_id'] ?? 0);
    if ($deptId > 0) {
        $departmentsByAdmins[$deptId] = true;
    }
}

$adminAccountsCount = count($adminAccounts);
$superadminAccountsCount = count($superadminAccounts);
$activeAccountsCount = 0;
foreach ($admins as $account) {
    if ((int) ($account['is_active'] ?? 1) === 1) {
        $activeAccountsCount++;
    }
}
$inactiveAccountsCount = max(0, count($admins) - $activeAccountsCount);
$uniqueDepartments = [];
foreach ($adminAccounts as $account) {
    $deptCode = $departmentLookup[(int) ($account['department_id'] ?? 0)] ?? '';
    if ($deptCode !== '') {
        $uniqueDepartments[$deptCode] = true;
    }
}
$managedDepartmentCount = count($uniqueDepartments);
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <section class="bubble-card p-6">
                <p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700">Superadmin Panel</p>
                <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-2xl">
                        <h1 class="text-2xl font-extrabold text-slate-800">Manage Admin Accounts</h1>
                        <p class="mt-1 text-sm text-slate-600">Consistent account management view for superadmin: browse, filter, create, and open each profile for actions.</p>
                        <p class="mt-2 text-xs font-semibold text-slate-500">Use this page for account lifecycle only. Keep daily request processing in the sidebar pages to reduce superadmin risk.</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-right ring-1 ring-emerald-100">
                        <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">Current View</p>
                        <p class="mt-1 text-sm font-bold text-slate-800">Accounts and access control</p>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="mt-6 flex gap-2 border-b border-slate-200">
                    <button type="button" data-tab-btn="admin-accounts" class="tab-btn tab-btn-active flex items-center gap-2 border-b-2 border-emerald-500 px-4 py-3 text-sm font-bold text-emerald-700">
                        <i class="bi bi-people-fill"></i>
                        Admin Accounts <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black"><?= esc((string) count($adminAccounts)) ?></span>
                    </button>
                    <button type="button" data-tab-btn="superadmin-accounts" class="tab-btn flex items-center gap-2 border-b-2 border-transparent px-4 py-3 text-sm font-bold text-slate-600 hover:text-slate-800">
                        <i class="bi bi-shield-lock-fill"></i>
                        Superadmin Accounts <span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-black"><?= esc((string) count($superadminAccounts)) ?></span>
                    </button>
                    <button type="button" data-tab-btn="create-account" class="tab-btn flex items-center gap-2 border-b-2 border-transparent px-4 py-3 text-sm font-bold text-slate-600 hover:text-slate-800">
                        <i class="bi bi-plus-circle-fill"></i>
                        Create Account
                    </button>
                </div>

                <!-- Admin and Superadmin tables with tabs -->
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="bubble-card p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Total Accounts</p>
                        <h3 class="mt-2 text-3xl font-extrabold text-slate-800"><?= esc((string) count($admins)) ?></h3>
                    </div>
                    <div class="bubble-card p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Active Accounts</p>
                        <h3 class="mt-2 text-3xl font-extrabold text-emerald-600"><?= esc((string) $activeAccountsCount) ?></h3>
                    </div>
                    <div class="bubble-card p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Admin Accounts</p>
                        <h3 class="mt-2 text-3xl font-extrabold text-blue-600"><?= esc((string) $adminAccountsCount) ?></h3>
                    </div>
                    <div class="bubble-card p-4">
                        <p class="text-xs font-bold uppercase text-slate-500">Superadmins</p>
                        <h3 class="mt-2 text-3xl font-extrabold text-indigo-600"><?= esc((string) $superadminAccountsCount) ?></h3>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-600">Search and Filter</p>
                            <p class="mt-1 text-sm text-slate-500">Search by name, email, username, department, or role.</p>
                        </div>
                        <span id="adminVisibleCount" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Visible: <?= esc((string) count($admins)) ?></span>
                    </div>
                    <div class="mt-4 grid gap-3 lg:grid-cols-4">
                        <input id="adminSearchFilter" type="text" placeholder="Search admin accounts..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400">
                        <select id="adminRoleFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Roles</option>
                            <option value="admin">Admin Only</option>
                            <option value="superadmin">Superadmin Only</option>
                        </select>
                        <select id="adminStatusFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <button id="adminFilterReset" type="button" class="rounded-lg bg-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-400">Reset Filters</button>
                    </div>
                </div>

                <?php if (!$supportsAdminManagement): ?>
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">
                        Admin account management columns are missing in your database. Run the superadmin migration SQL first.
                    </div>
                <?php else: ?>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-bold text-slate-700">Governance focus</p>
                        <p class="mt-1">Superadmin should stay on recovery, account control, and oversight. Routine request handling should remain in the department pages.</p>
                    </div>

                    <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4" id="tab-content-admin-accounts">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h2 id="admin-accounts" class="text-sm font-extrabold uppercase tracking-wide text-emerald-800">Admin Accounts</h2>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700">Total: <?= esc((string) count($adminAccounts)) ?></span>
                        </div>
                    </div>

                    <div class="hidden tab-content" data-tab="admin-accounts">
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr class="border-b border-emerald-100 text-left text-slate-500">
                                        <th class="px-3 py-2">ID</th>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Email</th>
                                        <th class="px-3 py-2">Username</th>
                                        <th class="px-3 py-2">Department</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($adminAccounts)): ?>
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center font-bold text-slate-500">No admin accounts found.</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($adminAccounts as $account): ?>
                                        <tr class="border-b border-emerald-50 align-top">
                                        <?php
                                            $deptCode = $departmentLookup[(int) ($account['department_id'] ?? 0)] ?? '-';
                                            $searchText = strtolower(trim((string) ($account['name'] ?? '') . ' ' . ($account['email'] ?? '') . ' ' . ($account['username'] ?? '') . ' ' . $deptCode . ' admin'));
                                            $statusValue = ((int) ($account['is_active'] ?? 1) === 1) ? 'active' : 'inactive';
                                        ?>
                                        <tr class="border-b border-emerald-50 align-top" data-admin-row="1" data-admin-role="admin" data-admin-status="<?= esc($statusValue) ?>" data-admin-search="<?= esc($searchText) ?>">
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['name'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['email'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['username'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-slate-700">
                                                <?= esc($deptCode) ?>
                                            </td>
                                            <td class="px-3 py-3">
                                                <?php if ((int) ($account['is_active'] ?? 1) === 1): ?>
                                                    <span class="status-pill bg-emerald-100 text-emerald-700">Active</span>
                                                <?php else: ?>
                                                    <span class="status-pill bg-slate-100 text-slate-700">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <a href="<?= url('admin/manage-admins/' . (int) ($account['admin_id'] ?? 0)) ?>" class="inline-flex items-center gap-2 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-200">
                                                    Open Profile & Actions
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-center text-sm font-bold text-slate-500" data-admin-empty-state="admin">No admin accounts match the current filters.</div>
                    </div>

                    <!-- Superadmin table in tab -->
                    <div class="hidden tab-content" data-tab="superadmin-accounts">
                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full min-w-[900px] text-sm">
                                <thead>
                                    <tr class="border-b border-blue-100 text-left text-slate-500">
                                        <th class="px-3 py-2">ID</th>
                                        <th class="px-3 py-2">Name</th>
                                        <th class="px-3 py-2">Email</th>
                                        <th class="px-3 py-2">Username</th>
                                        <th class="px-3 py-2">Scope</th>
                                        <th class="px-3 py-2">Status</th>
                                        <th class="px-3 py-2">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($superadminAccounts)): ?>
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center font-bold text-slate-500">No superadmin accounts found.</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php foreach ($superadminAccounts as $account): ?>
                                        <?php
                                            $searchText = strtolower(trim((string) ($account['name'] ?? '') . ' ' . ($account['email'] ?? '') . ' ' . ($account['username'] ?? '') . ' global superadmin'));
                                            $statusValue = ((int) ($account['is_active'] ?? 1) === 1) ? 'active' : 'inactive';
                                        ?>
                                        <tr class="border-b border-blue-50 align-top" data-admin-row="1" data-admin-role="superadmin" data-admin-status="<?= esc($statusValue) ?>" data-admin-search="<?= esc($searchText) ?>">
                                            <td class="px-3 py-3 font-bold text-slate-800">#<?= esc((string) ($account['admin_id'] ?? '')) ?></td>
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['name'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['email'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-slate-700"><?= esc((string) ($account['username'] ?? '-')) ?></td>
                                            <td class="px-3 py-3 text-xs font-semibold text-slate-600">
                                                Global access
                                            </td>
                                            <td class="px-3 py-3">
                                                <?php if ((int) ($account['is_active'] ?? 1) === 1): ?>
                                                    <span class="status-pill bg-emerald-100 text-emerald-700">Active</span>
                                                <?php else: ?>
                                                    <span class="status-pill bg-slate-100 text-slate-700">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-3 text-right">
                                                <a href="<?= url('admin/manage-admins/' . (int) ($account['admin_id'] ?? 0)) ?>" class="inline-flex items-center gap-2 rounded-lg bg-blue-100 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-200">
                                                    Open Profile & Actions
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-center text-sm font-bold text-slate-500" data-admin-empty-state="superadmin">No superadmin accounts match the current filters.</div>
                    </div>

                    <section id="create-account" class="hidden tab-content rounded-2xl border border-slate-200 bg-white/80 p-5" data-tab="create-account">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h2 class="text-sm font-extrabold uppercase tracking-wide text-slate-700">Create Account</h2>
                                <p class="mt-1 text-xs text-slate-500">Create admin accounts only for account management. Keep superadmin count minimal.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-6 md:grid-cols-2">
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                <h3 class="text-sm font-bold text-emerald-800">Create Admin</h3>
                                <p class="mt-1 text-xs text-emerald-700">Admin account must be linked to one department. Each department can only have one admin.</p>
                                <?php if (count($departmentsByAdmins) > 0): ?>
                                    <p class="mt-2 text-xs text-amber-600 font-semibold">Note: Departments with existing admins are disabled in the dropdown.</p>
                                <?php endif; ?>
                                <form method="post" action="<?= url('admin/manage-admins/add') ?>" class="mt-4 grid gap-3">
                                    <?php csrf_field(); ?>
                                    <input id="create-admin-name" name="name" required placeholder="Full name" class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                    <input type="email" name="email" required placeholder="Email" class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                    <input name="username" required placeholder="Username" class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                    <input type="password" name="password" required minlength="8" placeholder="Password (min 8 chars)" class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                    <select name="department_id" required class="w-full rounded-lg border border-emerald-200 px-3 py-2 text-sm">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <?php 
                                                $deptId = (int) ($dept['id'] ?? 0);
                                                $isDepartmentTaken = isset($departmentsByAdmins[$deptId]);
                                            ?>
                                            <option value="<?= esc((string) $deptId) ?>" title="<?= esc((string) ($dept['name'] ?? '')) ?>" <?= $isDepartmentTaken ? 'disabled' : '' ?>>
                                                <?= esc((string) ($dept['code'] ?? '')) ?><?= $isDepartmentTaken ? ' (Admin exists)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="role" value="admin">
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-emerald-300">
                                            Active account
                                        </label>
                                        <button class="ml-auto rounded-lg bg-emerald-500 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-600">Create Admin</button>
                                    </div>
                                </form>
                            </div>

                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                <h3 class="text-sm font-bold text-blue-800">Create Superadmin</h3>
                                <p class="mt-1 text-xs text-blue-700">Superadmin account has global scope and no department assignment.</p>
                                <form method="post" action="<?= url('admin/manage-admins/add') ?>" class="mt-4 grid gap-3">
                                    <?php csrf_field(); ?>
                                    <input id="create-superadmin-name" name="name" required placeholder="Full name" class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm">
                                    <input type="email" name="email" required placeholder="Email" class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm">
                                    <input name="username" required placeholder="Username" class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm">
                                    <input type="password" name="password" required minlength="8" placeholder="Password (min 8 chars)" class="w-full rounded-lg border border-blue-200 px-3 py-2 text-sm">
                                    <input type="hidden" name="role" value="superadmin">
                                    <input type="hidden" name="department_id" value="">
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-blue-300">
                                            Active account
                                        </label>
                                        <button class="ml-auto rounded-lg bg-blue-500 px-4 py-2 text-sm font-bold text-white hover:bg-blue-600">Create Superadmin</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                    </section>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script>
// Tab navigation functionality
(function () {
    const tabButtons = document.querySelectorAll('[data-tab-btn]');
    const tabContents = document.querySelectorAll('.tab-content');

    if (!tabButtons.length || !tabContents.length) {
        return;
    }

    // Function to activate a tab
    const activateTab = (tabName) => {
        // Hide all tabs
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });

        // Deactivate all buttons
        tabButtons.forEach(btn => {
            btn.classList.remove('tab-btn-active', 'border-emerald-500', 'text-emerald-700');
            btn.classList.add('border-transparent', 'text-slate-600');
        });

        // Activate selected tab
        const activeContent = document.querySelector(`[data-tab="${tabName}"]`);
        if (activeContent) {
            activeContent.classList.remove('hidden');
        }

        // Activate selected button
        const activeButton = document.querySelector(`[data-tab-btn="${tabName}"]`);
        if (activeButton) {
            activeButton.classList.add('tab-btn-active', 'border-emerald-500', 'text-emerald-700');
            activeButton.classList.remove('border-transparent', 'text-slate-600');
        }

        // Store the active tab in session storage
        sessionStorage.setItem('activeAdminTab', tabName);
    };

    // Attach click handlers to tab buttons
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.getAttribute('data-tab-btn');
            activateTab(tabName);
        });
    });

    // Restore active tab from session storage or set default
    const savedTab = sessionStorage.getItem('activeAdminTab') || 'admin-accounts';
    activateTab(savedTab);
})();
</script>

<script>
    const searchInput = document.getElementById('adminSearchFilter');
    const roleFilter = document.getElementById('adminRoleFilter');
    const statusFilter = document.getElementById('adminStatusFilter');
    const resetButton = document.getElementById('adminFilterReset');
    const visibleCount = document.getElementById('adminVisibleCount');
    const rows = Array.from(document.querySelectorAll('[data-admin-row]'));
    const emptyStates = {
        admin: document.querySelector('[data-admin-empty-state="admin"]'),
        superadmin: document.querySelector('[data-admin-empty-state="superadmin"]'),
    };

    if (!searchInput || !roleFilter || !statusFilter || !resetButton || !rows.length) {
        return;
    }

    const normalize = (value) => String(value || '').trim().toLowerCase();

    const applyFilters = () => {
        const search = normalize(searchInput.value);
        const role = normalize(roleFilter.value);
        const status = normalize(statusFilter.value);
        let visibleTotal = 0;
        const sectionCounts = { admin: 0, superadmin: 0 };

        rows.forEach((row) => {
            const rowRole = normalize(row.getAttribute('data-admin-role'));
            const rowStatus = normalize(row.getAttribute('data-admin-status'));
            const rowSearch = normalize(row.getAttribute('data-admin-search'));
            const matchesSearch = !search || rowSearch.includes(search);
            const matchesRole = !role || rowRole === role;
            const matchesStatus = !status || rowStatus === status;
            const isVisible = matchesSearch && matchesRole && matchesStatus;

            row.classList.toggle('hidden', !isVisible);

            if (isVisible) {
                visibleTotal += 1;
                if (rowRole in sectionCounts) {
                    sectionCounts[rowRole] += 1;
                }
            }
        });

        if (visibleCount) {
            visibleCount.textContent = `Visible: ${visibleTotal}`;
        }

        Object.entries(emptyStates).forEach(([key, node]) => {
            if (!node) {
                return;
            }
            const shouldShow = sectionCounts[key] === 0;
            node.classList.toggle('hidden', !shouldShow);
        });
    };

    const debounce = (fn, wait = 200) => {
        let timer;
        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn(...args), wait);
        };
    };

    const applyFiltersDebounced = debounce(applyFilters, 120);

    searchInput.addEventListener('input', applyFiltersDebounced);
    roleFilter.addEventListener('change', applyFilters);
    statusFilter.addEventListener('change', applyFilters);

    resetButton.addEventListener('click', () => {
        searchInput.value = '';
        roleFilter.value = '';
        statusFilter.value = '';
        applyFilters();
        searchInput.focus();
    });

    applyFilters();
})();
</script>

<script>
// If user arrives with #create-account or #create-admin, activate the create-account tab
document.addEventListener('DOMContentLoaded', function() {
    var hash = (location.hash || '').toLowerCase();
    if (hash === '#create-account' || hash === '#create-admin') {
        var createBtn = document.querySelector('[data-tab-btn="create-account"]');
        if (createBtn) {
            createBtn.click();
        }
        var nameEl = document.getElementById('create-admin-name');
        if (nameEl) {
            setTimeout(function() {
                try { nameEl.focus(); } catch (e) {}
                try { nameEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); } catch (e) {}
            }, 120);
        }
    }
    if (hash === '#superadmin-accounts') {
        var superadminBtn = document.querySelector('[data-tab-btn="superadmin-accounts"]');
        if (superadminBtn) {
            superadminBtn.click();
        }
    }
    if (hash === '#admin-accounts') {
        var adminBtn = document.querySelector('[data-tab-btn="admin-accounts"]');
        if (adminBtn) {
            adminBtn.click();
        }
    }
});
        }
    }
});
</script>
<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
