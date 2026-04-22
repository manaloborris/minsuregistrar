<?php $title = 'Manage Students - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<?php $supportsStudentApproval = $supportsStudentApproval ?? false; ?>
<?php $supportsRejectionNotes = $supportsRejectionNotes ?? false; ?>
<?php $supportsRejectedAutoPurge = $supportsRejectedAutoPurge ?? false; ?>
<?php $rejectedAutoPurgeDays = (int) ($rejectedAutoPurgeDays ?? 30); ?>
<?php $departmentCode = trim((string) ($departmentCode ?? '')); ?>

<?php
$groupedStudents = [];
foreach ($students as $student) {
    $year = trim((string) ($student['year_level'] ?? ''));
    $section = trim((string) ($student['section'] ?? ''));

    if ($year === '') {
        $year = 'Unspecified Year';
    }

    if ($section === '') {
        $section = 'No Section';
    }

    $groupKey = $year . '||' . $section;
    if (!isset($groupedStudents[$groupKey])) {
        $groupedStudents[$groupKey] = [
            'year' => $year,
            'section' => $section,
            'rows' => [],
        ];
    }

    $groupedStudents[$groupKey]['rows'][] = $student;
}

ksort($groupedStudents, SORT_NATURAL | SORT_FLAG_CASE);

$yearOptions = [];
$sectionOptions = [];
foreach ($groupedStudents as $group) {
    $yearOptions[(string) ($group['year'] ?? '')] = true;
    $sectionOptions[(string) ($group['section'] ?? '')] = true;
}
$yearOptions = array_keys($yearOptions);
$sectionOptions = array_keys($sectionOptions);
sort($yearOptions, SORT_NATURAL | SORT_FLAG_CASE);
sort($sectionOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Manage Students</h1>
                <p class="mt-1 text-sm text-slate-600">Students are grouped by year level and section for faster monitoring.</p>
                <?php if ($departmentCode !== ''): ?>
                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700">Scoped to department: <?= esc($departmentCode) ?></p>
                <?php endif; ?>

                <?php if (!$supportsStudentApproval): ?>
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-700">
                        Registration approval is currently unavailable. Add students.registration_status column to enable approve/reject workflow.
                    </div>
                <?php endif; ?>

                <?php if ($supportsStudentApproval && $supportsRejectedAutoPurge): ?>
                    <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-3 text-sm font-bold text-sky-700">
                        Rejected registrations are auto-deleted after <?= esc((string) $rejectedAutoPurgeDays) ?> days.
                    </div>
                <?php elseif ($supportsStudentApproval): ?>
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-bold text-amber-700">
                        Add students.rejected_at column to enable automatic cleanup of rejected registrations.
                    </div>
                <?php endif; ?>

                <?php if (empty($groupedStudents)): ?>
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">No student records found.</div>
                <?php endif; ?>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-600">Student Filters and Bulk Actions</p>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <input id="studentSearchFilter" type="text" placeholder="Search student ID or name..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400">
                        <select id="studentYearFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Year Levels</option>
                            <?php foreach ($yearOptions as $yearOpt): ?>
                                <option value="<?= esc($yearOpt) ?>"><?= esc($yearOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="studentSectionFilter" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                            <option value="">All Sections</option>
                            <?php foreach ($sectionOptions as $sectionOpt): ?>
                                <option value="<?= esc($sectionOpt) ?>"><?= esc($sectionOpt) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input id="bulkRejectNote" type="text" placeholder="Rejection note for Reject All..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400" <?= $supportsStudentApproval ? '' : 'disabled' ?>>
                        <button id="studentFilterReset" type="button" class="rounded-lg bg-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-400">Reset Filters</button>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button id="bulkApproveVisibleBtn" type="button" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" <?= $supportsStudentApproval ? '' : 'disabled' ?>>
                            Approve All Visible Pending
                        </button>
                        <button id="bulkRejectVisibleBtn" type="button" class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-slate-300" <?= $supportsStudentApproval ? '' : 'disabled' ?>>
                            Reject All Visible Pending
                        </button>
                        <span id="visiblePendingCount" class="rounded-full bg-slate-200 px-3 py-1 text-xs font-black text-slate-700">Visible Pending: 0</span>
                    </div>

                    <form id="bulkApproveStudentsForm" method="post" action="<?= url('admin/students/bulk-approve') ?>" class="hidden">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="student_ids_csv" id="bulkApproveStudentsCsv" value="">
                    </form>

                    <form id="bulkRejectStudentsForm" method="post" action="<?= url('admin/students/bulk-reject') ?>" class="hidden">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="student_ids_csv" id="bulkRejectStudentsCsv" value="">
                        <input type="hidden" name="rejection_note" id="bulkRejectStudentsNote" value="">
                    </form>
                </div>

                <div class="mt-5 space-y-5">
                    <?php foreach ($groupedStudents as $group): ?>
                        <article class="student-group-card rounded-2xl border border-emerald-100 bg-white/70 p-4" data-year="<?= esc((string) $group['year']) ?>" data-section="<?= esc((string) $group['section']) ?>">
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                <h2 class="text-base font-extrabold text-slate-800">Year <?= esc($group['year']) ?> - Section <?= esc($group['section']) ?></h2>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700"><?= esc((string) count($group['rows'])) ?> student(s)</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full min-w-[780px] text-sm">
                                    <thead>
                                        <tr class="text-left text-slate-500">
                                            <th class="px-3 py-2">Student ID</th>
                                            <th class="px-3 py-2">Name</th>
                                            <th class="px-3 py-2">Course</th>
                                            <th class="px-3 py-2">Section</th>
                                            <th class="px-3 py-2">Registration</th>
                                            <th class="px-3 py-2">Email</th>
                                            <th class="px-3 py-2">Contact Number</th>
                                            <th class="px-3 py-2">Manage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($group['rows'] as $student): ?>
                                        <?php
                                        $registrationStatus = trim((string) ($student['registration_status'] ?? 'Approved'));
                                        $registrationColor = 'bg-emerald-100 text-emerald-700';
                                        if ($registrationStatus === 'Pending') {
                                            $registrationColor = 'bg-amber-100 text-amber-700';
                                        } elseif ($registrationStatus === 'Rejected') {
                                            $registrationColor = 'bg-red-100 text-red-700';
                                        }

                                        $searchText = strtolower(trim((string) (
                                            ($student['student_id'] ?? '') . ' ' .
                                            ($student['first_name'] ?? '') . ' ' .
                                            ($student['last_name'] ?? '')
                                        )));
                                        ?>
                                        <tr class="student-row border-t border-emerald-50"
                                            data-student-id="<?= esc((string) ($student['student_id'] ?? '')) ?>"
                                            data-status="<?= esc($registrationStatus) ?>"
                                            data-year="<?= esc((string) ($group['year'] ?? '')) ?>"
                                            data-section="<?= esc((string) ($group['section'] ?? '')) ?>"
                                            data-search="<?= esc($searchText) ?>">
                                            <td class="px-3 py-2 font-bold text-slate-700"><?= esc($student['student_id']) ?></td>
                                            <td class="px-3 py-2 text-slate-700"><?= esc($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                            <td class="px-3 py-2 text-slate-700"><?= esc($student['course']) ?></td>
                                            <td class="px-3 py-2 text-slate-700"><?= esc($student['section'] ?: 'N/A') ?></td>
                                            <td class="px-3 py-2"><span class="status-pill <?= esc($registrationColor) ?>"><?= esc($registrationStatus) ?></span></td>
                                            <td class="px-3 py-2 text-slate-600"><?= esc($student['email']) ?></td>
                                            <td class="px-3 py-2 text-slate-600"><?= esc($student['contact_number']) ?></td>
                                            <td class="px-3 py-2 text-right">
                                                <a href="<?= url('admin/students/' . esc($student['student_id'])) ?>" class="inline-flex items-center gap-2 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-black text-emerald-700 hover:bg-emerald-200">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Open Profile & Actions
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<script>
(function () {
    const searchInput = document.getElementById('studentSearchFilter');
    const yearFilter = document.getElementById('studentYearFilter');
    const sectionFilter = document.getElementById('studentSectionFilter');
    const resetBtn = document.getElementById('studentFilterReset');
    const approveBtn = document.getElementById('bulkApproveVisibleBtn');
    const rejectBtn = document.getElementById('bulkRejectVisibleBtn');
    const rejectNoteInput = document.getElementById('bulkRejectNote');
    const visiblePendingCount = document.getElementById('visiblePendingCount');

    const approveForm = document.getElementById('bulkApproveStudentsForm');
    const approveCsv = document.getElementById('bulkApproveStudentsCsv');
    const rejectForm = document.getElementById('bulkRejectStudentsForm');
    const rejectCsv = document.getElementById('bulkRejectStudentsCsv');
    const rejectNoteHidden = document.getElementById('bulkRejectStudentsNote');

    const groupCards = Array.from(document.querySelectorAll('.student-group-card'));
    const rows = Array.from(document.querySelectorAll('.student-row'));
    if (!rows.length) {
        return;
    }

    const debounce = (fn, wait = 220) => {
        let timer;
        return (...args) => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn(...args), wait);
        };
    };

    let pendingUndoAction = null;

    const showUndoToast = (message, onUndo, timeoutMs = 10000) => {
        const existing = document.getElementById('studentsBulkUndoToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'studentsBulkUndoToast';
        toast.className = 'fixed bottom-5 right-5 z-[100] flex items-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 shadow-xl';
        toast.innerHTML = `
            <p class="text-xs font-semibold text-slate-700">${message}</p>
            <button type="button" class="undo-btn rounded bg-slate-800 px-3 py-1 text-xs font-bold text-white hover:bg-slate-700">Undo</button>
            <span class="undo-timer text-[11px] font-bold text-slate-500">${Math.ceil(timeoutMs / 1000)}s</span>
        `;
        document.body.appendChild(toast);

        const undoBtn = toast.querySelector('.undo-btn');
        const timerEl = toast.querySelector('.undo-timer');
        const started = Date.now();
        const intervalId = window.setInterval(() => {
            const remainingMs = Math.max(0, timeoutMs - (Date.now() - started));
            if (timerEl) timerEl.textContent = `${Math.ceil(remainingMs / 1000)}s`;
            if (remainingMs <= 0) window.clearInterval(intervalId);
        }, 250);

        const cleanup = () => {
            window.clearInterval(intervalId);
            toast.remove();
        };

        undoBtn?.addEventListener('click', () => {
            onUndo();
            cleanup();
        });

        return cleanup;
    };

    const scheduleUndoableSubmit = ({ message, timeoutMs = 10000, submit }) => {
        if (pendingUndoAction) {
            alert('Please finish or undo the pending bulk action first.');
            return;
        }

        let cancelled = false;
        const timerId = window.setTimeout(() => {
            if (cancelled) return;
            submit();
            pendingUndoAction = null;
        }, timeoutMs);

        const cleanupToast = showUndoToast(message, () => {
            cancelled = true;
            window.clearTimeout(timerId);
            pendingUndoAction = null;
        }, timeoutMs);

        pendingUndoAction = {
            cancel: () => {
                cancelled = true;
                window.clearTimeout(timerId);
                cleanupToast();
                pendingUndoAction = null;
            }
        };
    };

    const getVisiblePendingIds = () => {
        return rows
            .filter((row) => row.style.display !== 'none' && (row.dataset.status || '') === 'Pending')
            .map((row) => row.dataset.studentId || '')
            .filter(Boolean);
    };

    const applyStudentFilters = () => {
        const searchVal = (searchInput?.value || '').toLowerCase().trim();
        const yearVal = yearFilter?.value || '';
        const sectionVal = sectionFilter?.value || '';

        rows.forEach((row) => {
            const rowSearch = row.dataset.search || '';
            const rowYear = row.dataset.year || '';
            const rowSection = row.dataset.section || '';
            const visible =
                (!searchVal || rowSearch.includes(searchVal)) &&
                (!yearVal || rowYear === yearVal) &&
                (!sectionVal || rowSection === sectionVal);
            row.style.display = visible ? '' : 'none';
        });

        groupCards.forEach((card) => {
            const visibleRowsInCard = card.querySelectorAll('tr.student-row:not([style*="display: none"])').length;
            card.style.display = visibleRowsInCard > 0 ? '' : 'none';
        });

        const pendingIds = getVisiblePendingIds();
        if (visiblePendingCount) {
            visiblePendingCount.textContent = 'Visible Pending: ' + pendingIds.length;
        }
        if (approveBtn) approveBtn.disabled = pendingIds.length === 0;
        if (rejectBtn) rejectBtn.disabled = pendingIds.length === 0;
    };

    searchInput?.addEventListener('input', debounce(applyStudentFilters, 220));
    yearFilter?.addEventListener('change', applyStudentFilters);
    sectionFilter?.addEventListener('change', applyStudentFilters);
    resetBtn?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (yearFilter) yearFilter.value = '';
        if (sectionFilter) sectionFilter.value = '';
        applyStudentFilters();
    });

    approveBtn?.addEventListener('click', () => {
        const pendingIds = getVisiblePendingIds();
        if (!pendingIds.length) {
            alert('No visible pending students to approve.');
            return;
        }

        if (!confirm('Approve all visible pending students (' + pendingIds.length + ')?')) {
            return;
        }

        if (approveCsv) approveCsv.value = pendingIds.join(',');
        scheduleUndoableSubmit({
            message: 'Bulk approve scheduled. Submit in 10 seconds.',
            timeoutMs: 10000,
            submit: () => approveForm?.submit(),
        });
    });

    rejectBtn?.addEventListener('click', () => {
        const pendingIds = getVisiblePendingIds();
        if (!pendingIds.length) {
            alert('No visible pending students to reject.');
            return;
        }

        const note = (rejectNoteInput?.value || '').trim();
        if (!note) {
            alert('Please provide a rejection note before using Reject All Visible Pending.');
            rejectNoteInput?.focus();
            return;
        }

        if (!confirm('Reject all visible pending students (' + pendingIds.length + ')?')) {
            return;
        }

        if (rejectCsv) rejectCsv.value = pendingIds.join(',');
        if (rejectNoteHidden) rejectNoteHidden.value = note;
        scheduleUndoableSubmit({
            message: 'Bulk reject scheduled. Submit in 10 seconds.',
            timeoutMs: 10000,
            submit: () => rejectForm?.submit(),
        });
    });

    applyStudentFilters();
})();
</script>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

