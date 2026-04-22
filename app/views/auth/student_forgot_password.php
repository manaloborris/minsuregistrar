<?php $title = 'Forgot Password - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$pendingReset = (bool) ($pendingReset ?? false);
$pendingStudentId = trim((string) ($pendingStudentId ?? ''));
$codeExpiryMinutes = (int) ($codeExpiryMinutes ?? 10);
?>

<div class="relative min-h-screen overflow-hidden">
    <div class="absolute -left-20 top-8 h-60 w-60 rounded-full bg-emerald-200/50 blur-3xl"></div>
    <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-lime-200/50 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center">
        <div class="absolute h-[36rem] w-[36rem] rounded-full bg-emerald-100/80 blur-3xl"></div>
        <img
            src="<?= esc($minsuLogoUrl ?? url('public/assets/minsulogo.png')) ?>"
            alt=""
            aria-hidden="true"
            class="h-[30rem] w-[30rem] select-none object-contain opacity-[0.2] blur-[2px] brightness-110 saturate-75"
        >
    </div>

    <header class="relative z-40 mx-auto w-full max-w-7xl px-5 pt-6 lg:px-8">
        <nav class="bubble-card flex flex-wrap items-center justify-between gap-4 px-5 py-4">
            <div class="flex items-center gap-3">
                <img src="<?= esc($minsuLogoUrl ?? url('public/assets/minsulogo.png')) ?>" alt="MinSU Logo" class="h-11 w-11 rounded-2xl object-cover ring-2 ring-emerald-100">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Mindoro State University</p>
                    <h1 class="text-lg font-bold text-slate-800">MinSU e-Registrar</h1>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm font-bold">
                <a class="rounded-xl px-3 py-2 text-slate-600 transition hover:bg-white" href="<?= url('/') ?>">Home</a>
                <a class="rounded-xl px-3 py-2 text-slate-600 transition hover:bg-white" href="<?= url('login') ?>">Log In</a>
            </div>
        </nav>
    </header>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-110px)] w-full max-w-md items-center px-5 py-10 lg:px-8">
        <section class="bubble-card w-full p-5 lg:p-6">
            <h2 class="text-2xl font-extrabold text-slate-800">Forgot Password</h2>
            <p class="mt-1 text-sm text-slate-600">Two-step verification is required before password reset.</p>

            <div class="mt-5"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>

            <?php if (!$pendingReset): ?>
                <form method="post" action="<?= url('student/forgot-password') ?>" class="mt-4 space-y-4" data-no-loading>
                    <?php csrf_field(); ?>
                    <input type="hidden" name="step" value="request_code">

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Student ID</label>
                        <input type="text" name="student_id" required placeholder="MCC2024-00160" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Registered Email</label>
                        <input type="email" name="email" required placeholder="youremail@minsu.edu.ph" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                    </div>

                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Generate Verification Code</button>
                </form>

                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-bold text-amber-700">
                    Security policy: max 3 code requests in 15 minutes.
                </div>
            <?php else: ?>
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">
                    Code challenge active for Student ID <?= esc($pendingStudentId) ?>. Code expires in <?= esc((string) $codeExpiryMinutes) ?> minutes.
                </div>

                <form method="post" action="<?= url('student/forgot-password') ?>" class="mt-4 space-y-4" data-no-loading>
                    <?php csrf_field(); ?>
                    <input type="hidden" name="step" value="verify_code">

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Verification Code</label>
                        <input type="text" name="verification_code" required inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="6-digit code" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">New Password</label>
                        <input type="password" name="new_password" minlength="10" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Confirm New Password</label>
                        <input type="password" name="password_confirmation" minlength="10" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                    </div>

                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Verify Code and Reset Password</button>
                </form>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-600">
                    Password policy: at least 10 characters with uppercase, lowercase, number, and special character.
                </div>
            <?php endif; ?>

            <a href="<?= url('login') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Back to Log In</a>
        </section>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
