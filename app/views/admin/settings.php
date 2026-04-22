<?php $title = 'Settings - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>

<?php
$admin = $_SESSION['auth']['admin'] ?? [];
$isSuperAdmin = strtolower((string) ($admin['role'] ?? 'admin')) === 'superadmin';
$settingsRoleLabel = $isSuperAdmin ? 'Superadmin' : 'Admin';
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 space-y-6 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">System Settings</h1>
                <p class="mt-1 text-sm text-slate-600">Configuration summary for MinSU e-Registrar.</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="soft-panel p-4">
                        <p class="text-xs uppercase text-slate-500">Framework</p>
                        <p class="text-sm font-bold text-slate-700">LavaLite MVC</p>
                    </div>
                    <div class="soft-panel p-4">
                        <p class="text-xs uppercase text-slate-500">Database</p>
                        <p class="text-sm font-bold text-slate-700">registar_system (MySQL)</p>
                    </div>
                    <div class="soft-panel p-4">
                        <p class="text-xs uppercase text-slate-500">Styling</p>
                        <p class="text-sm font-bold text-slate-700">Tailwind CSS + custom theme</p>
                    </div>
                    <div class="soft-panel p-4">
                        <p class="text-xs uppercase text-slate-500">Reports</p>
                        <p class="text-sm font-bold text-slate-700">TCPDF export enabled</p>
                    </div>
                </div>
            </section>

            <?php include APP_ROOT . '/app/views/partials/app_preferences_panel.php'; ?>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

