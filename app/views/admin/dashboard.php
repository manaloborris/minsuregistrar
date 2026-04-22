<?php
$title = 'Admin Dashboard - MinSU e-Registrar';
include APP_ROOT . '/app/views/partials/head.php';

$monthlyData = $monthlyData ?? [];
$documentStatsData = $documentStatsData ?? [];
$statusDistributionData = $statusDistributionData ?? [];
$paymentAnalyticsData = $paymentAnalyticsData ?? [];
$turnaroundData = $turnaroundData ?? [];

$departmentCode = trim((string) ($departmentCode ?? ''));
?>

<!-- Force hide loading screen immediately -->
<script>
(function() {
	const loadingScreen = document.getElementById('loadingScreen');
	if (loadingScreen) {
		loadingScreen.style.display = 'none !important';
		loadingScreen.style.visibility = 'hidden !important';
		loadingScreen.style.pointerEvents = 'none !important';
		loadingScreen.classList.add('hidden');
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
                <h1 class="mt-2 text-2xl font-extrabold text-slate-800">Registrar Request Analytics</h1>
                <p class="text-sm text-slate-600">High-level request volume and status overview.</p>
                <?php if ($departmentCode !== ''): ?>
                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700">Scoped to department: <?= esc($departmentCode) ?></p>
                <?php endif; ?>
            </div>


            <?php if (isset($departmentOverview) && is_array($departmentOverview) && count($departmentOverview) && isset($_SESSION['auth']['admin']['role']) && strtolower($_SESSION['auth']['admin']['role']) === 'superadmin'): ?>
            <section class="bubble-card p-6 mb-6">
                <h2 class="text-lg font-extrabold text-slate-800 mb-2">Department Summary Overview</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs border rounded-lg">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="px-2 py-1 text-left">Department</th>
                                <th class="px-2 py-1 text-center">Students</th>
                                <th class="px-2 py-1 text-center">Admins</th>
                                <th class="px-2 py-1 text-center">Appointments</th>
                                <th class="px-2 py-1 text-center">Requests</th>
                                <th class="px-2 py-1 text-center">Active</th>
                                <th class="px-2 py-1 text-center">Completed</th>
                                <th class="px-2 py-1 text-center">Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($departmentOverview as $row): ?>
                            <?php $dept = esc($row['department_code']); ?>
                            <tr>
                                <td class="px-2 py-1 font-bold text-slate-700"><?= $dept ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($row['total_students'] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($adminCounts[$dept] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($appointmentCounts[$dept] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($row['total_requests'] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($row['active_requests'] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($row['completed_requests'] ?? 0) ?></td>
                                <td class="px-2 py-1 text-center"><?= (int)($row['rejected_requests'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="bubble-card dashboard-stat p-5"><p class="text-xs font-bold uppercase text-slate-500">Total Requests</p><h3 class="mt-2 text-3xl font-extrabold text-slate-800" data-counter="<?= esc((string) ($stats['total'] ?? 0)) ?>">0</h3></div>
                <div class="bubble-card dashboard-stat p-5"><p class="text-xs font-bold uppercase text-slate-500">Pending Requests</p><h3 class="mt-2 text-3xl font-extrabold text-amber-500" data-counter="<?= esc((string) ($stats['pending'] ?? 0)) ?>">0</h3></div>
                <div class="bubble-card dashboard-stat p-5"><p class="text-xs font-bold uppercase text-slate-500">Approved Requests</p><h3 class="mt-2 text-3xl font-extrabold text-blue-500" data-counter="<?= esc((string) ($stats['approved'] ?? 0)) ?>">0</h3></div>
                <div class="bubble-card dashboard-stat p-5"><p class="text-xs font-bold uppercase text-slate-500">Completed Requests</p><h3 class="mt-2 text-3xl font-extrabold text-emerald-600" data-counter="<?= esc((string) ($stats['completed'] ?? 0)) ?>">0</h3></div>
            </div>

            <section class="bubble-card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="analyticsTitle" class="text-lg font-extrabold text-slate-800">Monthly Request Trend</h2>
                    <div class="flex items-center gap-2" id="chartControls">
                        <button type="button" data-analytics-feature="monthly" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700 transition hover:-translate-y-0.5 hover:shadow-sm">Monthly Trend</button>
                        <button type="button" data-analytics-feature="documents" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-sm">Document Types</button>
                        <button type="button" data-analytics-feature="status" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-sm">Status Distribution</button>
                        <button type="button" data-analytics-feature="payments" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-sm">Payment Analytics</button>
                        <button type="button" data-analytics-feature="turnaround" class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-sm">Turnaround Time</button>
                    </div>
                </div>
                <p id="analyticsSubtitle" class="mt-1 text-sm text-slate-600">Shows request volume over time.</p>
                <div class="mt-4">
                    <canvas id="requestTypeChart" height="120"></canvas>
                </div>
                <div id="analyticsBreakdown" class="mt-4 flex flex-wrap gap-2"></div>
            </section>
        </main>
    </div>
</div>

<script>
const monthlyData = <?= json_encode($monthlyData) ?>;
const documentStatsData = <?= json_encode($documentStatsData) ?>;
const statusDistributionData = <?= json_encode($statusDistributionData) ?>;
const paymentAnalyticsData = <?= json_encode($paymentAnalyticsData) ?>;
const turnaroundData = <?= json_encode($turnaroundData) ?>;

const defaultStatuses = ['Pending', 'Processing', 'Approved', 'Ready for Pickup', 'Completed', 'Rejected', 'Cancelled'];
const defaultPaymentMethods = ['GCash', 'Cash', 'Other'];
const defaultPaymentStatuses = ['Pending', 'Paid', 'Unpaid', 'Failed', 'Cancelled'];

function loadChartLibrary() {
    if (window.Chart) {
        return Promise.resolve();
    }

    const libraryPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-chartjs-loader="true"]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Chart.js failed to load')), { once: true });
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.async = true;
        script.dataset.chartjsLoader = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Chart.js failed to load'));
        document.head.appendChild(script);
    });

    // Add 4-second timeout to CDN load
    const timeoutPromise = new Promise((_, reject) => {
        window.setTimeout(() => {
            reject(new Error('Chart.js load timeout'));
        }, 4000);
    });

    return Promise.race([libraryPromise, timeoutPromise]);
}

const featureMeta = {
    monthly: {
        title: 'Monthly Request Trend',
        subtitle: 'Shows request volume over time.',
        type: 'line',
    },
    documents: {
        title: 'Requests by Document Type',
        subtitle: 'Compares request volume per document type.',
        type: 'bar',
    },
    status: {
        title: 'Request Status Distribution',
        subtitle: 'Shows the share of each request status.',
        type: 'doughnut',
    },
    payments: {
        title: 'Payment Method and Status Analytics',
        subtitle: 'Compares payment statuses across methods.',
        type: 'bar',
    },
    turnaround: {
        title: 'Turnaround Time by Document Type',
        subtitle: 'Average days from request to latest milestone.',
        type: 'bar',
    },
};

function buildMonthlyPayload() {
    const rows = [...monthlyData].sort((a, b) => String(a.month || '').localeCompare(String(b.month || '')));
    if (!rows.length) {
        return {
            labels: ['No data'],
            datasets: [{
                label: 'Requests',
                data: [0],
                fill: false,
                tension: 0.32,
                pointRadius: 3,
                borderColor: 'rgba(5, 150, 105, 0.95)',
                backgroundColor: 'rgba(16, 185, 129, 0.35)',
            }],
        };
    }

    return {
        labels: rows.map((row) => row.month || ''),
        datasets: [{
            label: 'Requests',
            data: rows.map((row) => Number(row.total_requests || 0)),
            fill: false,
            tension: 0.32,
            pointRadius: 3,
            borderColor: 'rgba(5, 150, 105, 0.95)',
            backgroundColor: 'rgba(16, 185, 129, 0.35)',
        }],
    };
}

function buildDocumentPayload() {
    if (!documentStatsData.length) {
        return {
            labels: ['No data'],
            datasets: [{
                label: 'Requests',
                data: [0],
                borderRadius: 10,
                backgroundColor: 'rgba(16, 185, 129, 0.72)',
                borderColor: 'rgba(4, 120, 87, 0.95)',
                borderWidth: 1,
            }],
        };
    }

    return {
        labels: documentStatsData.map((row) => row.document_name || '-'),
        datasets: [{
            label: 'Requests',
            data: documentStatsData.map((row) => Number(row.total || 0)),
            borderRadius: 10,
            backgroundColor: 'rgba(16, 185, 129, 0.72)',
            borderColor: 'rgba(4, 120, 87, 0.95)',
            borderWidth: 1,
        }],
    };
}

function buildStatusPayload() {
    const palette = ['#10b981', '#f59e0b', '#3b82f6', '#22c55e', '#ef4444', '#64748b', '#a855f7'];
    const totalsByStatus = new Map();

    statusDistributionData.forEach((row) => {
        const status = String(row.status || 'Unknown').trim() || 'Unknown';
        const total = Number(row.total || 0);
        totalsByStatus.set(status, (totalsByStatus.get(status) || 0) + total);
    });

    const labels = [...defaultStatuses];
    statusDistributionData.forEach((row) => {
        const status = String(row.status || '').trim();
        if (status !== '' && !labels.includes(status)) {
            labels.push(status);
        }
    });

    return {
        labels,
        datasets: [{
            label: 'Requests',
            data: labels.map((status) => Number(totalsByStatus.get(status) || 0)),
            backgroundColor: labels.map((_, index) => palette[index % palette.length]),
            borderWidth: 0,
        }],
    };
}

function buildPaymentPayload() {
    const methods = [...defaultPaymentMethods];
    paymentAnalyticsData.forEach((row) => {
        const method = String(row.payment_method || 'Other');
        if (!methods.includes(method)) {
            methods.push(method);
        }
    });

    const statuses = [...defaultPaymentStatuses];
    paymentAnalyticsData.forEach((row) => {
        const status = String(row.payment_status || 'Pending');
        if (!statuses.includes(status)) {
            statuses.push(status);
        }
    });

    const palette = {
        Pending: 'rgba(245, 158, 11, 0.85)',
        Paid: 'rgba(16, 185, 129, 0.85)',
        Unpaid: 'rgba(100, 116, 139, 0.85)',
        Failed: 'rgba(239, 68, 68, 0.85)',
        Cancelled: 'rgba(71, 85, 105, 0.85)',
    };

    const datasets = statuses.map((status) => {
        const values = methods.map((method) => {
            const row = paymentAnalyticsData.find((item) => String(item.payment_method || 'Other') === method && String(item.payment_status || 'Pending') === status);
            return Number(row?.total || 0);
        });

        return {
            label: status,
            data: values,
            backgroundColor: palette[status] || 'rgba(14, 165, 233, 0.85)',
            borderRadius: 6,
        };
    });

    return { labels: methods, datasets };
}

function buildTurnaroundPayload() {
    if (!turnaroundData.length) {
        return {
            labels: ['No data'],
            datasets: [{
                label: 'Avg Days',
                data: [0],
                borderRadius: 10,
                backgroundColor: 'rgba(14, 165, 233, 0.78)',
                borderColor: 'rgba(2, 132, 199, 0.95)',
                borderWidth: 1,
            }],
        };
    }

    return {
        labels: turnaroundData.map((row) => row.document_name || '-'),
        datasets: [{
            label: 'Avg Days',
            data: turnaroundData.map((row) => Number(row.avg_days || 0)),
            borderRadius: 10,
            backgroundColor: 'rgba(14, 165, 233, 0.78)',
            borderColor: 'rgba(2, 132, 199, 0.95)',
            borderWidth: 1,
        }],
    };
}

function getFeaturePayload(feature) {
    if (feature === 'documents') return buildDocumentPayload();
    if (feature === 'status') return buildStatusPayload();
    if (feature === 'payments') return buildPaymentPayload();
    if (feature === 'turnaround') return buildTurnaroundPayload();
    return buildMonthlyPayload();
}

function getFeatureOptions(feature) {
    const base = {
        responsive: true,
        plugins: {
            legend: {
                display: feature === 'status' || feature === 'payments',
            },
        },
    };

    if (feature === 'status') {
        return base;
    }

    return {
        ...base,
        scales: {
            y: {
                beginAtZero: true,
            },
            x: {
                stacked: feature === 'payments',
            },
        },
    };
}

function renderAnalyticsBreakdown(feature, payload) {
    const breakdownEl = document.getElementById('analyticsBreakdown');
    if (!breakdownEl) return;

    const firstDataset = payload?.datasets?.[0];
    const labels = payload?.labels || [];
    const values = firstDataset?.data || [];

    breakdownEl.innerHTML = '';

    if (!labels.length) {
        breakdownEl.innerHTML = '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">No variables available</span>';
        return;
    }

    labels.forEach((label, index) => {
        const value = Number(values[index] || 0);
        const chip = document.createElement('span');
        chip.className = 'rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700';
        chip.textContent = `${label}: ${value}`;
        breakdownEl.appendChild(chip);
    });

    if (feature === 'payments' && payload.datasets.length > 1) {
        const paymentTotals = payload.datasets.reduce((acc, dataset) => {
            (dataset.data || []).forEach((value, idx) => {
                acc[idx] = (acc[idx] || 0) + Number(value || 0);
            });
            return acc;
        }, []);

        const totalsTitle = document.createElement('span');
        totalsTitle.className = 'w-full text-xs font-black uppercase tracking-wide text-slate-500';
        totalsTitle.textContent = 'Payment method totals';
        breakdownEl.appendChild(totalsTitle);

        labels.forEach((label, index) => {
            const chip = document.createElement('span');
            chip.className = 'rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700';
            chip.textContent = `${label}: ${Number(paymentTotals[index] || 0)}`;
            breakdownEl.appendChild(chip);
        });
    }
}

function initAnalyticsCharts() {
    let requestTypeChart = null;

    function applyAnalyticsFeature(feature) {
        const key = featureMeta[feature] ? feature : 'monthly';
        const meta = featureMeta[key];
        const payload = getFeaturePayload(key);

        if (requestTypeChart) {
            requestTypeChart.destroy();
        }

        requestTypeChart = new Chart(document.getElementById('requestTypeChart'), {
            type: meta.type,
            data: payload,
            options: getFeatureOptions(key),
        });

        renderAnalyticsBreakdown(key, payload);

        const titleEl = document.getElementById('analyticsTitle');
        const subtitleEl = document.getElementById('analyticsSubtitle');
        if (titleEl) titleEl.textContent = meta.title;
        if (subtitleEl) subtitleEl.textContent = meta.subtitle;

        const controls = document.querySelectorAll('[data-analytics-feature]');
        controls.forEach((button) => {
            const isActive = button.getAttribute('data-analytics-feature') === key;
            button.classList.toggle('bg-emerald-100', isActive);
            button.classList.toggle('text-emerald-700', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-slate-600', !isActive);
        });
    }

    document.querySelectorAll('[data-analytics-feature]').forEach((button) => {
        button.addEventListener('click', function () {
            applyAnalyticsFeature(this.getAttribute('data-analytics-feature') || 'monthly');
        });
    });

    applyAnalyticsFeature('monthly');
}

loadChartLibrary().then(initAnalyticsCharts).catch(() => {
    const subtitleEl = document.getElementById('analyticsSubtitle');
    if (subtitleEl) {
        subtitleEl.textContent = 'Chart data could not load right now. The rest of the dashboard is still available.';
    }

    const breakdownEl = document.getElementById('analyticsBreakdown');
    if (breakdownEl) {
        breakdownEl.innerHTML = '<span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">Breakdown unavailable while chart library is loading.</span>';
    }
});

function animateCounter(el) {
    const target = Number(el.getAttribute('data-counter') || 0);
    const duration = 900;
    const start = performance.now();

    function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = String(Math.round(target * eased));
        if (progress < 1) {
            requestAnimationFrame(step);
        }
    }

    requestAnimationFrame(step);
}

document.querySelectorAll('[data-counter]').forEach((counter) => animateCounter(counter));
</script>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

