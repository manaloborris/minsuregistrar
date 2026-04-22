<?php $title = 'Appointments - Admin - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php
$pendingAppointments = $pendingAppointments ?? [];
$appointments = $appointments ?? [];

$appointmentYears = [];
foreach ($appointments as $row) {
    $dateRaw = trim((string) ($row['appointment_date'] ?? ''));
    if ($dateRaw === '') {
        continue;
    }

    $dateTs = strtotime($dateRaw);
    if ($dateTs !== false) {
        $appointmentYears[(int) date('Y', $dateTs)] = true;
    }
}
$appointmentYears = array_keys($appointmentYears);
rsort($appointmentYears);
?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/admin_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/admin_sidebar.php'; ?>

        <main class="flex-1 fade-in" style="scrollbar-gutter: stable;">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Appointments</h1>
                <p class="mt-1 text-sm text-slate-600">Pickup schedules linked to document requests.</p>

                <div class="mt-5 border-b border-slate-200">
                    <div class="flex gap-4">
                        <button id="appointmentViewPendingBtn" class="border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-600 hover:text-slate-800 -mb-0.5" data-view="pending">
                            Pending Requests
                        </button>
                        <button id="appointmentViewByDateBtn" class="border-b-2 border-emerald-500 px-4 py-2 text-sm font-bold text-emerald-700 -mb-0.5" data-view="bydate">
                            By Date
                        </button>
                    </div>
                </div>

                <div id="appointmentViewPending" class="mt-5 overflow-x-auto hidden">
                    <h2 class="mb-2 text-sm font-black uppercase tracking-[0.08em] text-slate-500">Pending for Appointment</h2>
                    <table class="w-full min-w-[960px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Student ID</th>
                                <th class="px-3 py-2">Student Name</th>
                                <th class="px-3 py-2">Document</th>
                                <th class="px-3 py-2">Request Date</th>
                                <th class="px-3 py-2">Appointment Time</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pendingAppointments as $row): ?>
                            <tr class="border-t border-amber-50">
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['student_id']) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td>
                                <td class="px-3 py-2 text-slate-600"><?= esc($row['request_date']) ?></td>
                                <td class="px-3 py-2 text-slate-700">
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 font-black text-slate-600">Not set yet</span>
                                </td>
                                <td class="px-3 py-2"><span class="status-pill bg-amber-100 text-amber-700"><?= esc($row['status']) ?></span></td>
                                <td class="px-3 py-2">
                                    <a href="<?= url('admin/manage-requests/' . (int) $row['request_id']) ?>" class="inline-flex items-center rounded-lg bg-indigo-100 px-3 py-1.5 text-xs font-black text-indigo-700 hover:bg-indigo-200">Open Request Actions</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pendingAppointments)): ?>
                            <tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">No pending requests waiting for appointment setup.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="appointmentViewByDate" class="mt-5 space-y-4" style="scrollbar-gutter: stable;">
                    <div id="appointmentByDateFiltersBar" class="sticky top-0 z-20 rounded-lg border border-slate-200 bg-white/95 p-4 backdrop-blur supports-[backdrop-filter]:bg-white/80">
                        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-600">Date-first Filters</p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <input type="text" id="appointmentByDateSearch" placeholder="Search student ID, name, or document..." class="rounded-lg border border-slate-300 px-3 py-2 text-xs placeholder-slate-400">
                            <select id="appointmentByDateMonth" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="">All Months</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                            <select id="appointmentByDateYear" class="rounded-lg border border-slate-300 px-3 py-2 text-xs">
                                <option value="">All Years</option>
                                <?php foreach ($appointmentYears as $year): ?>
                                    <option value="<?= esc((string) $year) ?>"><?= esc((string) $year) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="appointmentByDateReset" class="rounded-lg bg-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-400">Reset Date Filters</button>
                        </div>
                    </div>

                    <?php
                    $groupedAppointments = [];
                    foreach ($appointments as $row) {
                        $appointmentDateRaw = trim((string) ($row['appointment_date'] ?? ''));
                        if ($appointmentDateRaw === '') {
                            $groupKey = 'No Date';
                            $groupLabel = 'No Date Assigned';
                            $groupMonth = '';
                            $groupYear = '';
                        } else {
                            $appointmentDateTs = strtotime($appointmentDateRaw);
                            if ($appointmentDateTs !== false) {
                                $groupKey = date('Y-m-d', $appointmentDateTs);
                                $groupLabel = date('F d, Y', $appointmentDateTs);
                                $groupMonth = date('m', $appointmentDateTs);
                                $groupYear = date('Y', $appointmentDateTs);
                            } else {
                                $groupKey = $appointmentDateRaw;
                                $groupLabel = $appointmentDateRaw;
                                $groupMonth = '';
                                $groupYear = '';
                            }
                        }

                        if (!isset($groupedAppointments[$groupKey])) {
                            $groupedAppointments[$groupKey] = [
                                'label' => $groupLabel,
                                'month' => $groupMonth,
                                'year' => $groupYear,
                                'rows' => [],
                            ];
                        }
                        $groupedAppointments[$groupKey]['rows'][] = $row;
                    }

                    $datedGroups = [];
                    $undatedGroups = [];
                    foreach ($groupedAppointments as $k => $groupData) {
                        if (($groupData['year'] ?? '') !== '') {
                            $datedGroups[$k] = $groupData;
                        } else {
                            $undatedGroups[$k] = $groupData;
                        }
                    }
                    krsort($datedGroups);
                    $groupedAppointments = $datedGroups + $undatedGroups;
                    ?>

                    <?php foreach ($groupedAppointments as $groupKey => $groupData): ?>
                        <?php $groupCount = count($groupData['rows']); ?>
                        <div class="appointment-date-group-wrapper w-full rounded-lg border border-slate-200 bg-white" data-appointment-date-group-wrapper="1" data-month="<?= esc($groupData['month']) ?>" data-year="<?= esc($groupData['year']) ?>">
                            <button class="appointment-date-group-toggle sticky top-0 z-10 w-full flex items-center justify-between px-4 py-3 bg-white hover:bg-slate-50 border-b border-slate-200 font-bold text-slate-700" data-group="<?= esc($groupKey) ?>">
                                <span>
                                    <?= esc($groupData['label']) ?>
                                    <span class="text-xs font-normal text-slate-500">(<span class="appointment-date-group-count" data-original-count="<?= $groupCount ?>"><?= $groupCount ?></span> schedule<?= $groupCount !== 1 ? 's' : '' ?>)</span>
                                </span>
                                <span class="appointment-date-group-arrow text-slate-600">v</span>
                            </button>
                            <div class="appointment-date-group-content overflow-x-auto" data-group="<?= esc($groupKey) ?>">
                                <table class="w-full min-w-[960px] text-sm">
                                    <thead>
                                        <tr class="text-left text-slate-500">
                                            <th class="px-3 py-2">Student ID</th>
                                            <th class="px-3 py-2">Student Name</th>
                                            <th class="px-3 py-2">Document</th>
                                            <th class="px-3 py-2">Date</th>
                                            <th class="px-3 py-2">Time</th>
                                            <th class="px-3 py-2">Status</th>
                                            <th class="px-3 py-2">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($groupData['rows'] as $row): ?>
                                        <?php
                                        $appointmentDateRaw = trim((string) ($row['appointment_date'] ?? ''));
                                        $appointmentTimeRaw = trim((string) ($row['appointment_time'] ?? ''));
                                        $hasAppointmentDate = $appointmentDateRaw !== '';
                                        $hasAppointmentTime = $appointmentTimeRaw !== '' && $appointmentTimeRaw !== '00:00:00';
                                        $appointmentDateLabel = $hasAppointmentDate ? $appointmentDateRaw : 'Not set yet';
                                        $appointmentTimeLabel = $hasAppointmentTime
                                            ? date('g:i A', strtotime($appointmentTimeRaw))
                                            : 'Not set yet';

                                        $searchValue = strtolower(
                                            (string) ($row['student_id'] ?? '') . ' ' .
                                            (string) ($row['first_name'] ?? '') . ' ' .
                                            (string) ($row['last_name'] ?? '') . ' ' .
                                            (string) ($row['document_name'] ?? '')
                                        );
                                        ?>
                                        <tr class="appointment-by-date-row border-t border-emerald-50" data-search="<?= esc($searchValue) ?>">
                                            <td class="px-3 py-2 text-slate-700"><?= esc($row['student_id']) ?></td>
                                            <td class="px-3 py-2 text-slate-700"><?= esc($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                            <td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td>
                                            <td class="px-3 py-2 text-slate-600"><?= esc($appointmentDateLabel) ?></td>
                                            <td class="px-3 py-2 text-slate-700">
                                                <span class="inline-flex rounded-full px-2 py-0.5 font-black <?= $hasAppointmentTime ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' ?>">
                                                    <?= esc($appointmentTimeLabel) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2"><span class="status-pill bg-emerald-100 text-emerald-700"><?= esc($row['status']) ?></span></td>
                                            <td class="px-3 py-2">
                                                <a href="<?= url('admin/manage-requests/' . (int) $row['request_id']) ?>" class="inline-flex items-center rounded-lg bg-emerald-100 px-3 py-1.5 text-xs font-black text-emerald-700 hover:bg-emerald-200">Open Request</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div id="appointmentByDateNoResults" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
                        No appointments match the current date filters.
                    </div>

                    <?php if (empty($appointments)): ?>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-6 text-center text-slate-600">
                            No appointments found.
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

