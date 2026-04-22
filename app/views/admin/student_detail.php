<?php $title = 'Student Details - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$courseOptions = $courseOptions ?? [];
$yearLevelOptions = $yearLevelOptions ?? [];
$supportsStudentApproval = $supportsStudentApproval ?? false;
$supportsRejectionNotes = $supportsRejectionNotes ?? false;

$registrationStatus = trim((string) ($student['registration_status'] ?? 'Approved'));
$statusClass = 'bg-emerald-100 text-emerald-700';
if ($registrationStatus === 'Pending') {
    $statusClass = 'bg-amber-100 text-amber-700';
} elseif ($registrationStatus === 'Rejected') {
    $statusClass = 'bg-red-100 text-red-700';
}
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="min-w-0 flex-1 fade-in">
            <!-- Back Button -->
            <div class="mb-6">
                <a href="<?= url('admin/students') ?>" class="page-back-link">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Students
                </a>
            </div>

            <!-- Student Profile Card -->
            <section class="bubble-card mb-6 p-6">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold text-slate-800"><?= esc($student['first_name'] . ' ' . $student['last_name']) ?></h1>
                        <p class="mt-1 text-sm text-slate-600">Student ID: <span class="font-bold text-slate-800"><?= esc($student['student_id']) ?></span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Current Status</p>
                        <p class="mt-1 rounded-full px-3 py-1 text-sm font-bold <?= esc($statusClass) ?>"><?= esc($registrationStatus) ?></p>
                    </div>
                </div>

                <div class="grid gap-6 border-t border-slate-100 pt-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Course</p>
                        <p class="mt-1 text-lg font-bold text-slate-800"><?= esc($student['course']) ?></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Year Level</p>
                        <p class="mt-1 text-lg font-bold text-slate-800"><?= esc($student['year_level']) ?></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Section</p>
                        <p class="mt-1 text-lg font-bold text-slate-800"><?= esc($student['section'] ?: 'N/A') ?></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Email</p>
                        <p class="mt-1 truncate text-sm font-bold text-slate-800"><?= esc($student['email']) ?></p>
                    </div>
                </div>

                <div class="mt-6 border-t border-slate-100 pt-6">
                    <p class="text-xs uppercase tracking-wider text-slate-500">Contact Number</p>
                    <p class="mt-1 font-bold text-slate-800"><?= esc($student['contact_number']) ?></p>
                </div>
            </section>

            <?php if ($supportsStudentApproval): ?>
            <section class="bubble-card mb-6 p-6">
                <h2 class="mb-4 text-lg font-extrabold text-slate-800">Registration Actions</h2>
                <p class="mb-5 text-sm text-slate-600">Approval, rejection, and permanent delete actions are handled here to keep the student list clean.</p>

                <?php if ($registrationStatus === 'Pending'): ?>
                    <div class="grid gap-4 md:grid-cols-2">
                        <form method="post" action="<?= url('admin/students/approve') ?>" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="student_id" value="<?= esc($student['student_id']) ?>">
                            <p class="text-sm font-bold text-emerald-800">Approve Registration</p>
                            <p class="mt-1 text-xs text-emerald-700">Student can log in immediately after approval.</p>
                            <button class="mt-4 w-full rounded-lg bg-emerald-500 px-4 py-3 text-sm font-black text-white hover:bg-emerald-600">Approve Student</button>
                        </form>

                        <form method="post" action="<?= url('admin/students/reject') ?>" class="rounded-xl border border-red-200 bg-red-50 p-4">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="student_id" value="<?= esc($student['student_id']) ?>">
                            <label class="block text-xs font-bold uppercase tracking-wider text-red-700">Rejection Note</label>
                            <textarea
                                name="rejection_note"
                                rows="3"
                                required
                                maxlength="255"
                                placeholder="Please proceed to registrar office for F2F validation before re-registering."
                                class="mt-2 w-full rounded-lg border border-red-200 px-3 py-2 text-sm text-slate-700 focus:border-red-500 focus:outline-none"
                            ></textarea>
                            <?php if (!$supportsRejectionNotes): ?>
                                <p class="mt-2 text-xs font-semibold text-amber-700">Database note column is missing; this note may not be stored.</p>
                            <?php endif; ?>
                            <button class="mt-3 w-full rounded-lg bg-red-500 px-4 py-3 text-sm font-black text-white hover:bg-red-600">Reject Student</button>
                        </form>
                    </div>
                <?php elseif ($registrationStatus === 'Rejected'): ?>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-bold text-red-800">Rejected Registration</p>
                        <?php if (!empty($student['rejection_note'])): ?>
                            <p class="mt-2 rounded-lg bg-white/60 px-3 py-2 text-sm font-semibold text-red-700">Note: <?= esc((string) $student['rejection_note']) ?></p>
                        <?php endif; ?>
                        <form method="post" action="<?= url('admin/students/delete') ?>" class="mt-4" onsubmit="return confirm('Permanently delete this rejected student record? This cannot be undone.');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="student_id" value="<?= esc($student['student_id']) ?>">
                            <button class="rounded-lg bg-slate-800 px-4 py-3 text-sm font-black text-white hover:bg-slate-900">Delete Permanently</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
                        This registration is already approved. No approval action needed.
                    </div>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <!-- Edit Student Info Section -->
            <section class="bubble-card mb-6 p-6">
                <h2 class="mb-4 text-lg font-extrabold text-slate-800">Edit Student Information</h2>
                <p class="mb-5 text-sm text-slate-600">Update student details below.</p>

                <form method="post" action="<?= url('admin/students/update') ?>" class="grid gap-4 md:grid-cols-2">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="student_id" value="<?= esc($student['student_id']) ?>">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">First Name</label>
                        <input type="text" name="first_name" required value="<?= esc($student['first_name']) ?>" pattern="[A-Za-z .'-]{2,60}" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Last Name</label>
                        <input type="text" name="last_name" required value="<?= esc($student['last_name']) ?>" pattern="[A-Za-z .'-]{2,60}" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Course</label>
                        <select name="course" required class="mt-2 w-full rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">Select Course/Program</option>
                            <?php foreach ($courseOptions as $courseCode => $courseLabel): ?>
                                <option value="<?= esc($courseCode) ?>" <?= (($student['course'] ?? '') === $courseCode) ? 'selected' : '' ?>><?= esc($courseLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Year Level</label>
                        <select name="year_level" required class="mt-2 w-full rounded-lg border border-emerald-200 bg-white px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="">Select Year Level</option>
                            <?php foreach ($yearLevelOptions as $yearLevel): ?>
                                <option value="<?= esc($yearLevel) ?>" <?= (($student['year_level'] ?? '') === $yearLevel) ? 'selected' : '' ?>><?= esc($yearLevel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Section</label>
                        <input type="text" name="section" value="<?= esc($student['section']) ?>" placeholder="e.g., 2F4" pattern="[1-4][A-Za-z][A-Za-z0-9]{1,4}" title="Use section format like 2F4" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm uppercase focus:border-emerald-500 focus:outline-none">
                        <p class="mt-1 text-xs text-slate-500">Use section format like 2F4 (Year + Letter + Number).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Contact Number</label>
                        <input type="text" name="contact_number" required value="<?= esc($student['contact_number']) ?>" placeholder="e.g., 09123456789" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Email</label>
                        <input type="email" name="email" required value="<?= esc($student['email']) ?>" placeholder="e.g., student@university.edu" class="mt-2 w-full rounded-lg border border-emerald-200 px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>

                    <button class="md:col-span-2 rounded-lg bg-emerald-500 px-4 py-3 font-bold text-white hover:bg-emerald-600">Save Changes</button>
                </form>
            </section>

            <!-- Send Warning / Notice Section -->
            <section class="bubble-card p-6">
                <h2 class="mb-4 text-lg font-extrabold text-slate-800">Send Warning or Notice</h2>
                <p class="mb-5 text-sm text-slate-600">Send a notification to this student with important information or warnings.</p>

                <form method="post" action="<?= url('admin/students/warn') ?>" class="space-y-4">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="student_id" value="<?= esc($student['student_id']) ?>">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Message</label>
                        <textarea name="warning_message" required rows="5" placeholder="Type your message or warning here..." class="mt-2 w-full rounded-lg border border-amber-200 px-4 py-3 text-sm focus:border-amber-500 focus:outline-none"></textarea>
                    </div>

                    <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-4">
                        <input type="checkbox" id="needs_f2f" name="needs_f2f" value="1" class="rounded border-amber-200 text-amber-600">
                        <label for="needs_f2f" class="text-sm font-bold text-slate-700">Mark as requires Face-to-Face (F2F) resolution</label>
                    </div>

                    <button class="w-full rounded-lg bg-amber-500 px-4 py-3 font-bold text-white hover:bg-amber-600">Send to Student</button>
                </form>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
