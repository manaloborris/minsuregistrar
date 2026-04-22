<?php $title = 'MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

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
                <div class="relative">
                    <button id="homeMenuBtn" type="button" aria-expanded="false" aria-controls="homeMenuPanel" class="rounded-xl px-3 py-2 text-slate-600 transition hover:bg-white">Menu</button>
                    <div id="homeMenuPanel" class="fixed left-5 right-5 top-24 z-[120] hidden max-h-[70vh] overflow-y-auto rounded-2xl border border-emerald-100 bg-white p-2 text-xs font-bold text-slate-700 shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-12 sm:w-56">
                        <a class="block rounded-xl px-3 py-2 hover:bg-emerald-50" href="<?= url('about') ?>">About</a>
                        <a class="block rounded-xl px-3 py-2 hover:bg-emerald-50" href="<?= url('login?fresh=1') ?>">Log In</a>
                        <a class="block rounded-xl px-3 py-2 hover:bg-emerald-50" href="<?= url('student/request-document') ?>">Request Document</a>
                        <a class="block rounded-xl px-3 py-2 hover:bg-emerald-50" href="<?= url('student/track-requests') ?>">Track Requests</a>
                        <a class="block rounded-xl px-3 py-2 hover:bg-emerald-50" href="<?= url('student/notifications') ?>">Notifications</a>
                    </div>
                </div>
                <a class="rounded-xl bg-emerald-500 px-4 py-2 text-white shadow-lg shadow-emerald-400/30 transition hover:-translate-y-0.5" href="<?= url('about') ?>">About</a>
            </div>
        </nav>
    </header>

    <main class="relative z-10 mx-auto mt-6 w-full max-w-7xl px-5 pb-16 lg:px-8">
        <section class="bubble-card overflow-hidden">
            <div class="relative min-h-[330px] bg-cover bg-center" style="background-image:url('<?= url('public/assets/banner.jpg') ?>');">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-700/55 to-emerald-400/40"></div>
                <div class="relative px-8 py-14 text-white sm:px-12 sm:py-16">
                    <p class="mb-3 inline-block rounded-full bg-white/20 px-4 py-1 text-xs font-bold uppercase tracking-[0.2em]">University Registrar Portal</p>
                    <h2 class="text-3xl font-extrabold sm:text-5xl">WELCOME, MINSUans!</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-emerald-50 sm:text-base">
                        Request registrar documents, monitor status updates, set appointments, and stay informed in one modern portal.
                    </p>
                    <div class="mt-7 flex flex-wrap gap-3">
                        <a href="<?= url('login?fresh=1') ?>" class="rounded-xl bg-white px-5 py-3 text-sm font-bold text-emerald-700 transition hover:-translate-y-0.5">Log In</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="menu" class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <a href="<?= url('student/dashboard') ?>" class="bubble-card p-6">
                <p class="text-xs font-bold uppercase tracking-[0.17em] text-emerald-700">Portal</p>
                <h3 class="mt-2 text-xl font-extrabold text-slate-800">MinSU Student Portal</h3>
                <p class="mt-2 text-sm text-slate-600">Open your personalized dashboard and activity summary.</p>
            </a>
            <a href="<?= url('student/request-document') ?>" class="bubble-card p-6">
                <p class="text-xs font-bold uppercase tracking-[0.17em] text-emerald-700">Actions</p>
                <h3 class="mt-2 text-xl font-extrabold text-slate-800">Request Document</h3>
                <p class="mt-2 text-sm text-slate-600">Send COR, COE, TOR, and Good Moral requests online.</p>
            </a>
            <a href="<?= url('student/track-requests') ?>" class="bubble-card p-6">
                <p class="text-xs font-bold uppercase tracking-[0.17em] text-emerald-700">Tracking</p>
                <h3 class="mt-2 text-xl font-extrabold text-slate-800">Pending Request</h3>
                <p class="mt-2 text-sm text-slate-600">View pending and processing updates in real-time.</p>
            </a>
            <a href="<?= url('student/track-requests') ?>" class="bubble-card p-6">
                <p class="text-xs font-bold uppercase tracking-[0.17em] text-emerald-700">History</p>
                <h3 class="mt-2 text-xl font-extrabold text-slate-800">Completed</h3>
                <p class="mt-2 text-sm text-slate-600">Review completed requests and prior transactions.</p>
            </a>
        </section>
    </main>
</div>

<script>
(() => {
    const btn = document.getElementById('homeMenuBtn');
    const panel = document.getElementById('homeMenuPanel');
    if (!btn || !panel) return;

    function isOpen() {
        return !panel.classList.contains('hidden');
    }

    function openPanel() {
        panel.classList.remove('hidden');
        btn.setAttribute('aria-expanded', 'true');
    }

    function closePanel() {
        panel.classList.add('hidden');
        btn.setAttribute('aria-expanded', 'false');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (isOpen()) {
            closePanel();
            return;
        }

        openPanel();
    });

    document.addEventListener('click', function (e) {
        if (isOpen() && !panel.contains(e.target) && !btn.contains(e.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && isOpen()) {
            closePanel();
        }
    });
})();
</script>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>