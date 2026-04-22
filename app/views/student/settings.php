<?php $title = 'Settings - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<?php $settingsRoleLabel = 'Student'; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 space-y-6 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Settings</h1>
                <p class="mt-1 text-sm text-slate-600">Customize how the portal looks and feels for accessibility and comfort.</p>
            </section>

            <?php include APP_ROOT . '/app/views/partials/app_preferences_panel.php'; ?>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
