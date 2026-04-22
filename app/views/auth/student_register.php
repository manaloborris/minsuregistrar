<?php $title = 'Student Registration - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$courseOptions = $courseOptions ?? [];
$yearLevelOptions = $yearLevelOptions ?? [];
$registrationOtpEnabled = (bool) ($registrationOtpEnabled ?? false);
$pendingRegisterOtp = (bool) ($pendingRegisterOtp ?? false);
$pendingRegisterEmail = trim((string) ($pendingRegisterEmail ?? ''));
$registerEmailVerified = (bool) ($registerEmailVerified ?? false);
$registerOtpExpiryMinutes = (int) ($registerOtpExpiryMinutes ?? 10);
?>

<div class="relative min-h-screen overflow-hidden">
    <div class="absolute -left-20 top-8 h-60 w-60 rounded-full bg-emerald-200/50 blur-3xl"></div>
    <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-lime-200/50 blur-3xl"></div>

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

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-110px)] w-full max-w-3xl items-center px-5 py-10 lg:px-8">
        <section class="bubble-card w-full p-8">
            <h2 class="text-2xl font-extrabold text-slate-800">Student Registration</h2>
            <p class="mt-1 text-sm text-slate-600"><?= $registrationOtpEnabled ? 'Step 1: verify your email before entering registration details.' : 'Fill in your student information.' ?></p>

            <div class="mt-5"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>

            <?php if ($registrationOtpEnabled && !$pendingRegisterOtp): ?>
                <form method="post" action="<?= url('student/register') ?>" class="mt-4 space-y-4" data-no-loading>
                    <?php csrf_field(); ?>
                    <input type="hidden" name="step" value="request_email_code">

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Email</label>
                        <input type="email" name="email" required placeholder="youremail@minsu.edu/gmail.com" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Send Verification Code</button>
                </form>

                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-600">
                    A 6-digit code will be sent to your email. Verify it first before proceeding to student details.
                </div>
            <?php elseif ($registrationOtpEnabled && $pendingRegisterOtp && !$registerEmailVerified): ?>
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">
                    Verification pending for <?= esc($pendingRegisterEmail) ?>. Code expires in <?= esc((string) $registerOtpExpiryMinutes) ?> minutes.
                </div>

                <form method="post" action="<?= url('student/register') ?>" class="mt-4 space-y-4" data-no-loading>
                    <?php csrf_field(); ?>
                    <input type="hidden" name="step" value="verify_email_code">

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Email Verification Code</label>
                        <input type="text" name="verification_code" required inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="6-digit code" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Verify and Create Account</button>
                </form>

                <a href="<?= url('student/register?restart_email=1') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Use a different email</a>
            <?php else: ?>
                <form method="post" action="<?= url('student/register') ?>" class="mt-4 grid gap-4 md:grid-cols-2">
                    <?php csrf_field(); ?>
                    <?php if ($registrationOtpEnabled): ?>
                        <input type="hidden" name="step" value="register">
                    <?php endif; ?>

                    <?php if ($registrationOtpEnabled): ?>
                        <div class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700">
                            Email verified: <?= esc($pendingRegisterEmail) ?>
                            <a href="<?= url('student/register?restart_email=1') ?>" class="ml-2 underline">change</a>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Student ID</label>
                        <input type="text" name="student_id" required placeholder="MCC2024-00160" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Course</label>
                        <select name="course" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                            <option value="">Select Course/Program</option>
                            <?php foreach ($courseOptions as $courseCode => $courseLabel): ?>
                                <option value="<?= esc($courseCode) ?>"><?= esc($courseLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">First Name</label>
                        <input type="text" name="first_name" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Last Name</label>
                        <input type="text" name="last_name" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Year Level</label>
                        <select name="year_level" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                            <option value="">Select Year Level</option>
                            <?php foreach ($yearLevelOptions as $yearLevel): ?>
                                <option value="<?= esc($yearLevel) ?>"><?= esc($yearLevel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Section</label>
                        <input type="text" name="section" required placeholder="2F4" pattern="[1-4][A-Za-z][A-Za-z0-9]{1,4}" title="Use section format like 2F4" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 uppercase">
                        <p class="mt-1 text-xs text-slate-500">Use section format like 2F4 (Year + Letter + Number).</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Contact Number</label>
                        <input type="text" name="contact_number" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-slate-700">Email</label>
                        <input type="email" name="email" value="<?= esc($pendingRegisterEmail) ?>" <?= $registrationOtpEnabled ? 'readonly' : '' ?> required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 <?= $registrationOtpEnabled ? 'cursor-not-allowed bg-slate-100 text-slate-500' : '' ?>">
                        <p class="mt-1 text-xs text-amber-600">
                            <?= $registrationOtpEnabled ? 'A 6-digit verification code will be sent to this email before account creation.' : 'Please use a valid active email address. If possible, use your MinSU email for notifications and account updates.' ?>
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Password</label>
                        <input type="password" name="password" required minlength="10" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                        <p class="mt-1 text-xs text-slate-500">Use at least 10 characters with uppercase, lowercase, number, and special character.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-bold text-slate-700">Confirm Password</label>
                        <input type="password" name="password_confirmation" required minlength="10" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    </div>

                    <div class="md:col-span-2">
                        <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600"><?= $registrationOtpEnabled ? 'Send Verification Code' : 'Create Account' ?></button>
                    </div>
                </form>
            <?php endif; ?>

            <p class="mt-4 text-sm text-slate-600">
                Already registered?
                <a href="<?= url('login') ?>" class="font-bold text-emerald-700">Go to Log In</a>
            </p>

            <a href="<?= url('') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Back to Home</a>
        </section>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
