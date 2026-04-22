<?php $title = 'Appointments - MinSU e-Registrar'; include APP_ROOT . '/app/views/partials/head.php'; ?>
<?php $selectableSlots = $selectableSlots ?? []; ?>
<?php $appointmentTimeSlots = $appointmentTimeSlots ?? []; ?>
<?php $occupiedAppointmentTimesByDate = $occupiedAppointmentTimesByDate ?? []; ?>

<div class="mx-auto w-full max-w-7xl px-5 py-6 lg:px-8">
    <div class="mb-6"><?php include APP_ROOT . '/app/views/partials/flash.php'; ?></div>
    <?php include APP_ROOT . '/app/views/partials/student_topbar.php'; ?>
    <div class="flex flex-col gap-6 lg:flex-row">
        <?php include APP_ROOT . '/app/views/partials/student_sidebar.php'; ?>

        <main class="flex-1 fade-in">
            <section class="bubble-card p-6">
                <h1 class="text-2xl font-extrabold text-slate-800">Appointments</h1>
                <p class="mt-1 text-sm text-slate-600">Registrar/admin schedules your payment/follow-up appointments first, then sets final pickup schedule when documents are ready.</p>

                <div class="mt-5 overflow-x-auto">
                    <h2 class="mb-2 text-sm font-black uppercase tracking-[0.08em] text-slate-500">Choose Your Own Time (After Admin Notice)</h2>
                    <table class="w-full min-w-[960px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Schedule Type</th>
                                <th class="px-3 py-2">Document</th>
                                <th class="px-3 py-2">Note</th>
                                <th class="px-3 py-2">Admin Date</th>
                                <th class="px-3 py-2">Your Time (8:00-17:00)</th>
                                <th class="px-3 py-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($selectableSlots as $slot): ?>
                            <?php
                                $preferredDate = trim((string) ($slot['preferred_date'] ?? ''));
                                $bookedTimes = $occupiedAppointmentTimesByDate[$preferredDate] ?? [];
                                $availableTimes = [];
                                foreach ($appointmentTimeSlots as $timeSlot) {
                                    if (!isset($bookedTimes[$timeSlot])) {
                                        $availableTimes[] = $timeSlot;
                                    }
                                }
                                $canSchedule = $preferredDate !== '' && !empty($availableTimes);
                            ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 text-slate-700 font-bold"><?= esc((string) ($slot['schedule_label'] ?? 'Admin Appointment')) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc((string) ($slot['document_name'] ?? '-')) ?></td>
                                <td class="px-3 py-2 text-slate-600"><?= esc((string) (($slot['note'] ?? '') !== '' ? $slot['note'] : '-')) ?></td>
                                <td class="px-3 py-2">
                                    <?php $preferredDate = trim((string) ($slot['preferred_date'] ?? '')); ?>
                                    <span class="text-xs font-semibold text-slate-700\"><?= esc($preferredDate !== '' ? $preferredDate : 'Not set yet') ?></span>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        <?= $preferredDate !== '' ? 'Date is set by admin. You will choose the exact time below.' : 'Waiting for admin to set appointment date/time window.' ?>
                                    </p>
                                    <form method="post" action="<?= url('student/appointments/select-process-slot') ?>">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="request_id" value="<?= esc((string) ($slot['request_id'] ?? 0)) ?>">
                                        <input type="hidden" name="appointment_type" value="<?= esc((string) ($slot['appointment_type'] ?? '')) ?>">
                                        <input type="hidden" name="appointment_date" value="<?= esc((string) ($slot['preferred_date'] ?? '')) ?>">
                                </td>
                                <td class="px-3 py-2">
                                        <select name="appointment_time" required class="w-full rounded-lg border border-emerald-100 px-2 py-1 text-xs" <?= $canSchedule ? '' : 'disabled' ?>>
                                            <option value=""><?= $canSchedule ? 'Choose a 15-minute slot' : 'No available slots' ?></option>
                                            <?php foreach ($appointmentTimeSlots as $timeSlot): ?>
                                                <option value="<?= esc($timeSlot) ?>" <?= isset($bookedTimes[$timeSlot]) ? 'disabled' : '' ?>>
                                                    <?= esc(date('g:i A', strtotime($timeSlot))) ?><?= isset($bookedTimes[$timeSlot]) ? ' - Booked' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($preferredDate !== ''): ?>
                                            <p class="mt-1 text-[11px] text-slate-500">
                                                <?= $canSchedule ? 'Booked times are disabled automatically for this date.' : 'All 15-minute slots for this date are already booked.' ?>
                                            </p>
                                        <?php endif; ?>
                                </td>
                                <td class="px-3 py-2">
                                        <button class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-slate-300" <?= $canSchedule ? '' : 'disabled' ?>>Submit My Schedule</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($selectableSlots)): ?>
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">No active schedule notice from admin right now.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <h2 class="mb-2 text-sm font-black uppercase tracking-[0.08em] text-slate-500">Confirmed Schedules</h2>
                    <table class="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">Schedule Type</th>
                                <th class="px-3 py-2">Document Type</th>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Time</th>
                                <th class="px-3 py-2">Note</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($appointments as $row): ?>
                            <?php
                                $appointmentDateRaw = trim((string) ($row['appointment_date'] ?? ''));
                                $appointmentTimeRaw = trim((string) ($row['appointment_time'] ?? ''));
                                $hasAppointmentDate = $appointmentDateRaw !== '';
                                $hasAppointmentTime = $appointmentTimeRaw !== '' && $appointmentTimeRaw !== '00:00:00';
                                $appointmentDateLabel = $hasAppointmentDate ? $appointmentDateRaw : 'Not set yet';
                                $appointmentTimeLabel = $hasAppointmentTime
                                    ? date('g:i A', strtotime($appointmentTimeRaw))
                                    : 'Not set yet';
                            ?>
                            <tr class="border-t border-emerald-50">
                                <td class="px-3 py-2 text-slate-700 font-bold"><?= esc((string) ($row['schedule_label'] ?? 'Appointment')) ?></td>
                                <td class="px-3 py-2 text-slate-700"><?= esc($row['document_name']) ?></td>
                                <td class="px-3 py-2 text-slate-600\"><?= esc($appointmentDateLabel) ?></td>
                                <td class="px-3 py-2 text-slate-600">
                                    <span class="inline-flex rounded-full px-2 py-0.5 font-black <?= $hasAppointmentTime ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600' ?>">
                                        <?= esc($appointmentTimeLabel) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-slate-600"><?= esc((string) (($row['note'] ?? '') !== '' ? $row['note'] : '-')) ?></td>
                                <td class="px-3 py-2"><span class="status-pill bg-emerald-100 text-emerald-700"><?= esc($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">No confirmed schedules yet. Select from available options above once admin sends them.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</div>

<?php include APP_ROOT . '/app/views/partials/foot.php'; ?>

