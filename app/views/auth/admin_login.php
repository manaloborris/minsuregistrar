<?php $title = 'Admin Login - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="relative min-h-screen overflow-hidden">
    <div class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center">
        <div class="absolute h-[36rem] w-[36rem] rounded-full bg-emerald-100/80 blur-3xl"></div>
        <img
            src="<?= esc($minsuLogoUrl ?? url('public/assets/minsulogo.png')) ?>"
            alt=""
            aria-hidden="true"
            class="h-[30rem] w-[30rem] select-none object-contain opacity-[0.2] blur-[2px] brightness-110 saturate-75"
        >
    </div>

<div class="relative z-10 mx-auto flex min-h-screen w-full max-w-6xl items-center px-5 py-10 lg:px-8">
    <div class="grid w-full gap-6 lg:grid-cols-2">
        <section class="bubble-card hidden p-8 text-slate-700 lg:block">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Registrar Office</p>
            <h1 class="mt-3 text-4xl font-extrabold text-slate-800">Admin Console</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-600">Manage requests, students, appointments, and reports from one secure dashboard.</p>
            <ul class="mt-8 space-y-3 text-sm font-bold">
                <li class="soft-panel p-3">Approve and process document requests</li>
                <li class="soft-panel p-3">Generate monthly and summary reports</li>
                <li class="soft-panel p-3">Track actions via notifications and logs</li>
            </ul>
        </section>

        <section class="bubble-card p-8">
            <h2 class="text-2xl font-extrabold text-slate-800">Administrator Login</h2>
            <p class="mt-1 text-sm text-slate-600">Use Username: admin and Password: admin.</p>

            <div class="mt-5"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>

            <form method="post" action="<?= url('admin/login') ?>" class="mt-4 space-y-4">
                <?php csrf_field(); ?>
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Username</label>
                    <input type="text" name="username" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Password</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400">
                </div>
                <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Login to Admin</button>
            </form>

            <a href="<?= url('admin/access') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Back to Admin Access Check</a>
        </section>
    </div>
</div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
