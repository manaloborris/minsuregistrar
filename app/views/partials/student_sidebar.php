<?php
$active = $active ?? '';
$student = $_SESSION['auth']['student'] ?? [];
$studentUserId = (int) ($student['user_id'] ?? 0);
$studentId = strtoupper(trim((string) ($student['student_id'] ?? '')));
$studentUnreadCount = 0;
if ($studentUserId > 0) {
    $row = db()->raw("SELECT COUNT(*) AS total_unread FROM notifications WHERE user_id = ? AND LOWER(status) = 'unread'", [$studentUserId])->fetch();
    $studentUnreadCount = (int) ($row['total_unread'] ?? 0);
}

$studentAppointmentNoticeCount = 0;
if ($studentId !== '') {
    try {
        $openAccessRow = db()->raw(
            "SELECT COUNT(*) AS total
             FROM admin_process_appointment_access apx
             INNER JOIN document_requests dr ON dr.request_id = apx.request_id
             WHERE dr.student_id = ?
               AND apx.is_enabled = 1
               AND dr.status NOT IN ('Completed', 'Rejected', 'Cancelled')",
            [$studentId]
        )->fetch();
        $studentAppointmentNoticeCount += (int) ($openAccessRow['total'] ?? 0);
    } catch (\Throwable $e) {
        // Table may not exist yet in early setup; keep count at zero.
    }

    try {
        $upcomingPickupRow = db()->raw(
            "SELECT COUNT(*) AS total
             FROM appointments a
             INNER JOIN document_requests dr ON dr.request_id = a.request_id
             WHERE dr.student_id = ?
               AND dr.status IN ('Processing', 'Approved', 'Ready for Pickup')
               AND a.appointment_date >= CURDATE()",
            [$studentId]
        )->fetch();
        $studentAppointmentNoticeCount += (int) ($upcomingPickupRow['total'] ?? 0);
    } catch (\Throwable $e) {
        // Keep badge hidden when appointment query is unavailable.
    }
}
?>
<aside id="studentSidebar" class="bubble-card fixed inset-y-0 left-0 z-[999] w-72 shrink-0 -translate-x-full overflow-y-auto p-5 transition-transform duration-200 xl:static xl:translate-x-0 fade-in isolate">
    <div class="mb-4 flex items-center justify-between xl:hidden">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-700">Menu</p>
        <button id="studentSidebarCloseBtn" type="button" onclick="window.MinSUSidebar && window.MinSUSidebar.hide('studentSidebar','studentSidebarOverlay')" data-sidebar-close="studentSidebar" data-overlay-target="studentSidebarOverlay" class="relative z-[1003] inline-flex min-h-10 min-w-10 items-center justify-center rounded-lg bg-white px-2 py-1 text-xs font-black text-slate-700 ring-1 ring-emerald-100 pointer-events-auto">Close</button>
    </div>
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-emerald-400 to-green-600 p-4 text-white">
        <p class="sidebar-meta text-xs uppercase tracking-[0.16em]">Student</p>
        <h3 class="sidebar-meta text-lg font-bold"><?= esc($student['name'] ?? 'MinSUan') ?></h3>
        <p class="sidebar-meta text-xs opacity-90"><?= esc($student['student_id'] ?? '') ?></p>
    </div>

    <nav class="space-y-2 text-sm font-bold text-slate-700">
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'dashboard' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/dashboard') ?>"><i aria-hidden="true" class="bi bi-house-door text-base leading-none"></i><span class="menu-label">Dashboard</span></a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'request' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/request-document') ?>"><i aria-hidden="true" class="bi bi-file-earmark-plus text-base leading-none"></i><span class="menu-label">Request Document</span></a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'track' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/track-requests') ?>"><i aria-hidden="true" class="bi bi-box-seam text-base leading-none"></i><span class="menu-label">Track Requests</span></a>
        <a class="flex items-center justify-between rounded-xl px-3 py-2 <?= $active === 'appointments' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/appointments') ?>">
            <span class="flex items-center gap-2"><i aria-hidden="true" class="bi bi-calendar-check text-base leading-none"></i><span class="menu-label">Appointments</span></span>
            <?php if ($studentAppointmentNoticeCount > 0): ?>
                <span class="menu-badge rounded-full bg-indigo-500 px-2 py-0.5 text-[10px] font-black text-white"><?= esc((string) $studentAppointmentNoticeCount) ?></span>
            <?php endif; ?>
        </a>
        <a class="flex items-center justify-between rounded-xl px-3 py-2 <?= $active === 'notifications' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/notifications') ?>">
            <span class="flex items-center gap-2"><i aria-hidden="true" class="bi bi-bell text-base leading-none"></i><span class="menu-label">Notifications</span></span>
            <?php if ($studentUnreadCount > 0): ?>
                <span class="menu-badge rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white"><?= esc((string) $studentUnreadCount) ?></span>
            <?php endif; ?>
        </a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'profile' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/profile') ?>"><i aria-hidden="true" class="bi bi-person-circle text-base leading-none"></i><span class="menu-label">Profile</span></a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 <?= $active === 'settings' ? 'bg-emerald-100 text-emerald-700' : 'hover:bg-white' ?>" href="<?= url('student/settings') ?>"><i aria-hidden="true" class="bi bi-gear text-base leading-none"></i><span class="menu-label">Settings</span></a>
        <a class="flex items-center gap-2 rounded-xl px-3 py-2 text-red-600 hover:bg-red-50" href="<?= url('student/logout') ?>"><i aria-hidden="true" class="bi bi-box-arrow-right text-base leading-none"></i><span class="menu-label">Logout</span></a>
    </nav>
</aside>
