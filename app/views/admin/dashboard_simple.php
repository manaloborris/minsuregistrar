<?php
$title = 'Admin Dashboard - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';
?>

<script>
(function() {
	const loadingScreen = document.getElementById('loadingScreen');
	if (loadingScreen) {
		loadingScreen.style.display = 'none !important';
		loadingScreen.style.visibility = 'hidden !important';
	}
})();
</script>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
	<div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
	<?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
	<div class="flex flex-col gap-6 lg:flex-row">
		<?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

		<main class="flex-1 space-y-6 fade-in">
			<div class="bubble-card p-6">
				<p class="text-sm font-bold uppercase tracking-[0.16em] text-emerald-700">Admin Dashboard</p>
				<h1 class="mt-2 text-2xl font-extrabold text-slate-800">Welcome Back</h1>
				<p class="text-sm text-slate-600">Your dashboard is loading. Please select an option below to get started.</p>
			</div>

			<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
				<a href="<?= url('admin/manage-requests') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-folder2-open text-2xl text-emerald-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Manage Requests</h3>
					</div>
					<p class="text-sm text-slate-600">View and manage document requests from students.</p>
				</a>

				<a href="<?= url('admin/students') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-mortarboard text-2xl text-blue-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Manage Students</h3>
					</div>
					<p class="text-sm text-slate-600">Approve or reject student registrations.</p>
				</a>

				<a href="<?= url('admin/appointments') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-calendar-check text-2xl text-indigo-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Appointments</h3>
					</div>
					<p class="text-sm text-slate-600">Schedule and manage student appointments.</p>
				</a>

				<a href="<?= url('admin/document-types') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-file-earmark-text text-2xl text-amber-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Document Types</h3>
					</div>
					<p class="text-sm text-slate-600">Manage available document types and fees.</p>
				</a>

				<a href="<?= url('admin/reports') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-graph-up-arrow text-2xl text-green-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Reports</h3>
					</div>
					<p class="text-sm text-slate-600">Generate and view system reports.</p>
				</a>

				<a href="<?= url('admin/notifications') ?>" class="bubble-card hover:shadow-lg transition p-6 no-underline">
					<div class="flex items-center gap-3 mb-3">
						<i class="bi bi-bell text-2xl text-red-600"></i>
						<h3 class="text-lg font-bold text-slate-800">Notifications</h3>
					</div>
					<p class="text-sm text-slate-600">View system activity and notifications.</p>
				</a>
			</div>

			<div class="bubble-card p-6 bg-blue-50 border-blue-200">
				<p class="text-sm text-blue-700">
					<strong>Note:</strong> The full analytics dashboard is loading in the background. If you need analytics data, please go to <a href="<?= url('admin/reports') ?>" class="font-bold underline">Reports</a> instead.
				</p>
			</div>
		</main>
	</div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>
