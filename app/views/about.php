<?php $title = 'About MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-8 lg:px-8">
    <header class="bubble-card p-6 sm:p-8">
        <div class="mb-4">
            <a href="<?= url('/') ?>" class="page-back-link">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to Home
            </a>
        </div>
        <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">About</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-800 sm:text-4xl">MinSU e-Registrar</h1>
        <p class="mt-3 max-w-3xl text-sm leading-relaxed text-slate-600 sm:text-base">
            MinSU e-Registrar is an online service portal that helps students request registrar documents,
            schedule appointments, and track request progress in one place.
        </p>
        <div class="mt-5">
            <a href="https://www.minsu.edu.ph/about/vmgo" target="_blank" rel="noopener noreferrer" class="inline-flex items-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-emerald-600">
                View Official MinSU VMGO
            </a>
        </div>
    </header>

    <section class="mt-6 grid gap-5 lg:grid-cols-2">
        <article class="bubble-card p-6">
            <h2 class="text-xl font-extrabold text-slate-800">Mission Snapshot</h2>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                MinSU is committed to developing skilled lifelong learners and supporting innovation through
                quality instruction, research, extension, and production aligned with sustainable development.
            </p>
        </article>

        <article class="bubble-card p-6">
            <h2 class="text-xl font-extrabold text-slate-800">Core Values</h2>
            <div class="mt-3 flex flex-wrap gap-2 text-xs font-black uppercase tracking-[0.12em]">
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Resilience</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Integrity</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Commitment</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">Excellence</span>
            </div>
        </article>
    </section>

    <section class="mt-6 bubble-card p-6">
        <h2 class="text-xl font-extrabold text-slate-800">Contact</h2>
        <div class="mt-3 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
            <p><span class="font-bold text-slate-700">Address:</span> Alcate, Victoria, Oriental Mindoro</p>
            <p><span class="font-bold text-slate-700">Email:</span> universitypresident@minsu.edu.ph</p>
            <p><span class="font-bold text-slate-700">Phone:</span> +63 977 846 7228</p>
        </div>
    </section>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
