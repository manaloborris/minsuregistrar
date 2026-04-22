<?php $title = 'Log In - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

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
                <a class="rounded-xl px-3 py-2 text-slate-600 transition hover:bg-white" href="<?= url('student/register') ?>">Register</a>
            </div>
        </nav>
    </header>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-110px)] w-full max-w-md items-center px-5 py-10 lg:px-8">
        <section class="bubble-card w-full p-5 lg:p-6">
            <h2 class="text-2xl font-extrabold text-slate-800">Log In</h2>
            <p class="mt-1 text-sm text-slate-600">Welcome back, MinSUan.</p>

            <div class="mt-5"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>

            <form method="post" action="<?= url('login') ?>" class="mt-4 space-y-4" data-no-loading>
                <?php csrf_field(); ?>
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Login ID</label>
                    <input type="text" name="login_id" required placeholder="Student ID" class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                </div>
                <div class="text-right">
                    <a href="<?= url('student/forgot-password?fresh=1') ?>" class="text-xs font-bold text-emerald-700 hover:underline">Forgot password?</a>
                </div>
                <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Log In</button>
            </form>

            <p class="mt-4 text-sm text-slate-600">
                No account yet?
                <a href="<?= url('student/register') ?>" class="font-bold text-emerald-700">Create student account</a>
            </p>

            <a href="<?= url('') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Back to Home</a>
        </section>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
