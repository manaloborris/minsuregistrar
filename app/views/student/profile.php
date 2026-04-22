<?php $title = 'Profile - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Profile</h1>
                <p class="mt-1 text-sm text-slate-600">Your student account details from the registrar database.</p>

                <?php if ($profile): ?>
                    <?php $profilePhoto = trim((string) ($profile['profile_photo'] ?? '')); ?>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="soft-panel p-4"><p class="text-xs uppercase text-slate-500">Student ID</p><p class="text-sm font-bold text-slate-700"><?= esc($profile['student_id']) ?></p></div>
                        <div class="soft-panel p-4"><p class="text-xs uppercase text-slate-500">Name</p><p class="text-sm font-bold text-slate-700"><?= esc($profile['first_name'] . ' ' . $profile['last_name']) ?></p></div>
                        <div class="soft-panel p-4"><p class="text-xs uppercase text-slate-500">Course</p><p class="text-sm font-bold text-slate-700"><?= esc($profile['course']) ?></p></div>
                        <div class="soft-panel p-4"><p class="text-xs uppercase text-slate-500">Year Level</p><p class="text-sm font-bold text-slate-700"><?= esc($profile['year_level']) ?></p></div>
                        <div class="soft-panel p-4"><p class="text-xs uppercase text-slate-500">Section</p><p class="text-sm font-bold text-slate-700"><?= esc($profile['section'] ?? '') ?></p></div>
                        <?php if (!empty($supportsProfilePhoto)): ?>
                            <div class="soft-panel p-4">
                                <p class="text-xs uppercase text-slate-500">Profile Photo</p>
                                <?php if ($profilePhoto !== ''): ?>
                                    <?php $profilePhotoUrl = url('public/' . ltrim($profilePhoto, '/')); ?>
                                    <img src="<?= esc($profilePhotoUrl) ?>" alt="Profile photo" class="mt-2 h-20 w-20 rounded-full object-cover ring-2 ring-emerald-200">
                                    <div class="mt-3">
                                        <a href="<?= esc($profilePhotoUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600">View Photo</a>
                                    </div>
                                <?php else: ?>
                                    <p class="mt-2 text-sm font-bold text-slate-600">No photo uploaded yet.</p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" action="<?= url('student/profile') ?>" enctype="multipart/form-data" class="mt-6 grid gap-4 md:grid-cols-2">
                        <?php csrf_field(); ?>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Email</label>
                            <input type="email" name="email" required value="<?= esc($profile['email']) ?>" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-bold text-slate-700">Contact Number</label>
                            <input type="text" name="contact_number" required value="<?= esc($profile['contact_number']) ?>" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        </div>
                        <?php if (!empty($supportsProfilePhoto)): ?>
                            <div>
                                <label class="mb-1 block text-sm font-bold text-slate-700">Upload Photo</label>
                                <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                                <p class="mt-1 text-xs text-slate-500">Optional. JPG/PNG/WEBP only, max 2MB.</p>
                            </div>
                        <?php endif; ?>
                        <div class="md:col-span-2">
                            <button class="rounded-xl bg-emerald-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-emerald-600">Update Profile</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700">Profile record was not found in students table.</div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

