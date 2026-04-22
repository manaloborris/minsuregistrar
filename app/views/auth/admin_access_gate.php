<?php $title = 'Admin Access Verification - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<div class="mx-auto flex min-h-screen w-full max-w-6xl items-center px-5 py-10 lg:px-8">
    <div class="grid w-full gap-6 lg:grid-cols-2">
        <section class="bubble-card hidden p-8 text-slate-700 lg:block">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-700">Restricted Area</p>
            <h1 class="mt-3 text-4xl font-extrabold text-slate-800">Admin Access Gate</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-600">This extra step helps prevent accidental admin login attempts from student users.</p>
            <ul class="mt-8 space-y-3 text-sm font-bold">
                <li class="soft-panel p-3">Confirmation is required before entering admin login</li>
                <li class="soft-panel p-3">Only authorized registrar personnel should continue</li>
                <li class="soft-panel p-3">Access key: minsuregistrarloginaccess</li>
            </ul>
        </section>

        <section class="bubble-card p-8">
            <h2 class="text-2xl font-extrabold text-slate-800">Are You Accessing as Admin?</h2>
            <p class="mt-1 text-sm text-slate-600">Confirm authorization and enter the admin access key to continue.</p>

            <div class="mt-5"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>

            <form method="post" action="<?= url('admin/access') ?>" class="mt-4 space-y-4" data-no-loading>
                <?php csrf_field(); ?>

                <label class="flex items-start gap-3 rounded-xl border border-emerald-100 bg-white px-4 py-3">
                    <input type="checkbox" name="confirm_admin_access" value="1" required class="mt-1 rounded border-emerald-300 text-emerald-600">
                    <span class="text-sm font-bold text-slate-700">Yes, I confirm that I am authorized registrar/admin staff.</span>
                </label>

                <div>
                    <label class="mb-1 block text-sm font-bold text-slate-700">Admin Access Key</label>
                    <input type="password" name="access_key" required class="w-full rounded-xl border border-emerald-100 bg-white px-4 py-3 outline-none transition focus:border-emerald-400" placeholder="Enter access key">
                </div>

                <button class="w-full rounded-xl bg-emerald-500 px-4 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-emerald-600">Continue to Admin Login</button>
            </form>

            <a href="<?= url('student/login') ?>" class="mt-4 inline-block text-sm font-bold text-emerald-700">Go to Student Login</a>
        </section>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
