<?php
$student = $_SESSION['auth']['student'] ?? [];
$studentPhoto = trim((string) ($student['profile_photo'] ?? ''));
$studentPhotoUrl = $studentPhoto !== '' ? url('public/' . ltrim($studentPhoto, '/')) : '';
?>
<div class="bubble-card relative z-[60] mb-5 flex items-center justify-between gap-3 px-4 py-3 fade-in">
    <div class="flex items-center gap-2">
        <button type="button" data-sidebar-target="studentSidebar" data-overlay-target="studentSidebarOverlay" class="rounded-xl bg-white px-3 py-2 text-slate-700 shadow-sm ring-1 ring-emerald-100 hover:bg-emerald-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm1 4a1 1 0 100 2h12a1 1 0 100-2H4z" clip-rule="evenodd"/></svg>
        </button>
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.14em] text-emerald-700">Student Panel</p>
            <h2 class="text-sm font-extrabold text-slate-800">MinSU e-Registrar</h2>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <a href="<?= url('student/notifications') ?>" class="rounded-xl bg-white p-2 text-slate-600 shadow-sm ring-1 ring-emerald-100 hover:bg-emerald-50" title="Notifications">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z"/><path d="M8 15a2 2 0 104 0H8z"/></svg>
        </a>
        <a href="<?= url('student/settings') ?>" class="rounded-xl bg-white p-2 text-slate-600 shadow-sm ring-1 ring-emerald-100 hover:bg-emerald-50" title="Settings">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17a1 1 0 00-1.98 0l-.16.9a1 1 0 01-1.24.78l-.87-.26a1 1 0 00-1.21 1.21l.26.87a1 1 0 01-.78 1.24l-.9.16a1 1 0 000 1.98l.9.16a1 1 0 01.78 1.24l-.26.87a1 1 0 001.21 1.21l.87-.26a1 1 0 011.24.78l.16.9a1 1 0 001.98 0l.16-.9a1 1 0 011.24-.78l.87.26a1 1 0 001.21-1.21l-.26-.87a1 1 0 01.78-1.24l.9-.16a1 1 0 000-1.98l-.9-.16a1 1 0 01-.78-1.24l.26-.87a1 1 0 00-1.21-1.21l-.87.26a1 1 0 01-1.24-.78l-.16-.9zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
        </a>
        <a href="<?= url('student/profile') ?>" class="flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-slate-700 shadow-sm ring-1 ring-emerald-100 hover:bg-emerald-50">
            <?php if ($studentPhotoUrl !== ''): ?>
                <img src="<?= esc($studentPhotoUrl) ?>" alt="Student photo" class="h-7 w-7 rounded-full object-cover ring-1 ring-emerald-200">
            <?php else: ?>
                <span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-100 text-emerald-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 9a3 3 0 100-6 3 3 0 000 6z"/><path fill-rule="evenodd" d="M2 16a6 6 0 1112 0H2z" clip-rule="evenodd"/></svg>
                </span>
            <?php endif; ?>
            <span class="hidden text-xs font-bold md:inline"><?= esc($student['name'] ?? 'Student') ?></span>
        </a>
    </div>
</div>
<div id="studentSidebarOverlay" data-overlay="studentSidebar" onclick="window.MinSUSidebar && window.MinSUSidebar.hide('studentSidebar', 'studentSidebarOverlay')" class="fixed inset-0 z-[998] hidden bg-black/30 xl:hidden">
</div>
