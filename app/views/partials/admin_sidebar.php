<?php
$active = $active ?? '';
$admin = $_SESSION['auth']['admin'] ?? [];
$isSuperAdmin = strtolower((string) ($admin['role'] ?? 'admin')) === 'superadmin';

$adminRole = strtolower((string) ($admin['role'] ?? 'admin'));
$adminDepartmentId = (int) ($admin['department_id'] ?? 0);

// Initialize with defaults; sidebar queries now load via AJAX
$adminNewLogs = 0;
$pendingRequestsCount = 0;
$pendingAppointmentsCount = 0;
$departmentCode = null;
$adminLastSeenLogId = (int) ($_SESSION['admin_last_seen_log_id'] ?? 0);
?>
<aside id="adminSidebar" class="bubble-card fixed inset-y-0 left-0 z-[999] w-72 shrink-0 -translate-x-full overflow-y-auto p-5 transition-transform duration-200 xl:static xl:translate-x-0 fade-in isolate">
    <div class="mb-4 flex items-center justify-between xl:hidden">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Menu</p>
        <button id="adminSidebarCloseBtn" type="button" onclick="window.MinSUSidebar && window.MinSUSidebar.hide('adminSidebar','adminSidebarOverlay')" data-sidebar-close="adminSidebar" data-overlay-target="adminSidebarOverlay" class="relative z-[1003] inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg bg-white px-2 py-1 text-xs font-black text-slate-700 ring-1 ring-emerald-100 pointer-events-auto">Close</button>
    </div>
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-emerald-500 to-lime-600 p-4 text-white">
        <p class="sidebar-meta text-xs uppercase tracking-[0.16em]">Admin Panel</p>
        <h3 class="sidebar-meta text-lg font-bold"><?= esc($admin['name'] ?? 'Administrator') ?></h3>
        <p class="sidebar-meta text-xs opacity-90"><?= esc($admin['email'] ?? '') ?></p>
    </div>

    <nav class="space-y-2 text-sm font-bold text-slate-700">
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'dashboard' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/dashboard') ?>"><i aria-hidden="true" class="bi bi-speedometer2 text-base leading-none"></i><span class="menu-label">Dashboard</span></a>
        <?php if (!$isSuperAdmin): ?>
            <a class="flex items-center justify-between rounded-xl px-3 py-2 <?= $active === 'requests' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/manage-requests') ?>">
                <span class="flex items-center gap-2"><i aria-hidden="true" class="bi bi-folder2-open text-base leading-none"></i><span class="menu-label">Manage Requests</span></span>
                <?php if ($pendingRequestsCount > 0): ?>
                    <span class="menu-badge rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-black text-white"><?= esc((string) $pendingRequestsCount) ?></span>
                <?php endif; ?>
            </a>
            <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'students' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/students') ?>"><i aria-hidden="true" class="bi bi-mortarboard text-base leading-none"></i><span class="menu-label">Manage Students</span></a>
            <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'types' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/document-types') ?>"><i aria-hidden="true" class="bi bi-file-earmark-text text-base leading-none"></i><span class="menu-label">Document Types</span></a>
            <a class="flex items-center justify-between rounded-xl px-3 py-2 <?= $active === 'appointments' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/appointments') ?>">
                <span class="flex items-center gap-2"><i aria-hidden="true" class="bi bi-calendar-check text-base leading-none"></i><span class="menu-label">Appointments</span></span>
                <?php if ($pendingAppointmentsCount > 0): ?>
                    <span class="menu-badge rounded-full bg-indigo-500 px-2 py-0.5 text-[10px] font-black text-white"><?= esc((string) $pendingAppointmentsCount) ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'reports' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/reports') ?>"><i aria-hidden="true" class="bi bi-graph-up-arrow text-base leading-none"></i><span class="menu-label">Reports</span></a>
        <a class="flex items-center justify-between rounded-xl px-3 py-2 <?= $active === 'notifications' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/notifications') ?>">
            <span class="flex items-center gap-2"><i aria-hidden="true" class="bi bi-bell text-base leading-none"></i><span class="menu-label">Notifications</span></span>
            <?php if ($adminNewLogs > 0): ?>
                <span class="menu-badge rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white">NEW <?= esc((string) $adminNewLogs) ?></span>
            <?php endif; ?>
        </a>
        <?php if ($isSuperAdmin): ?>
            <div class="mt-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-[11px] font-bold text-blue-700">
                Superadmin mode: governance and analytics only.
            </div>
            <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'admins' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/manage-admins') ?>"><i aria-hidden="true" class="bi bi-people text-base leading-none"></i><span class="menu-label">Manage Admins</span></a>
            <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'admins' ? 'bg-emerald-50 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/manage-admins') ?>#admin-accounts"><i aria-hidden="true" class="bi bi-person-badge text-base leading-none"></i><span class="menu-label">Admin Accounts</span></a>
            <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'admins' ? 'bg-blue-50 text-blue-700' : 'hover:bg-white' ?>" href="<?= url('admin/manage-admins') ?>#superadmin-accounts"><i aria-hidden="true" class="bi bi-shield-lock text-base leading-none"></i><span class="menu-label">Superadmin Accounts</span></a>
        <?php endif; ?>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'settings' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('admin/settings') ?>"><i aria-hidden="true" class="bi bi-gear text-base leading-none"></i><span class="menu-label">Settings</span></a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 text-red-600 hover:bg-red-50" href="<?= url('admin/logout') ?>"><i aria-hidden="true" class="bi bi-box-arrow-right text-base leading-none"></i><span class="menu-label">Logout</span></a>
    </nav>
</aside>
