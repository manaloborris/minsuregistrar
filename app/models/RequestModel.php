<?php

class RequestModel
{
    private ?bool $hasRequestTypeAmountColumn = null;
    private ?bool $hasDocumentRequestsQuantityColumn = null;
    private ?bool $hasAdminProcessAppointmentsTable = null;
    private ?bool $hasAdminProcessAppointmentSlotsTable = null;
    private ?bool $hasAdminProcessAppointmentAccessTable = null;
    private ?bool $hasPaymentDeadlinesTable = null;

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        $sql = "SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                LIMIT 1";

        $row = db()->raw($sql, [DB_NAME, $tableName, $columnName])->fetch();
        return (bool) $row;
    }

    private function requestTypesHasAmountColumn(): bool
    {
        if ($this->hasRequestTypeAmountColumn !== null) {
            return $this->hasRequestTypeAmountColumn;
        }

        $this->hasRequestTypeAmountColumn = $this->tableHasColumn('request_types', 'amount');

        return $this->hasRequestTypeAmountColumn;
    }

    private function documentRequestsHasQuantityColumn(): bool
    {
        if ($this->hasDocumentRequestsQuantityColumn !== null) {
            return $this->hasDocumentRequestsQuantityColumn;
        }

        $this->hasDocumentRequestsQuantityColumn = $this->tableHasColumn('document_requests', 'quantity');

        return $this->hasDocumentRequestsQuantityColumn;
    }

    private function tableExists(string $tableName): bool
    {
        $sql = "SELECT 1
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                LIMIT 1";

        $row = db()->raw($sql, [DB_NAME, $tableName])->fetch();
        return (bool) $row;
    }

    private function ensureAdminProcessAppointmentsTable(): bool
    {
        if ($this->hasAdminProcessAppointmentsTable !== null) {
            return $this->hasAdminProcessAppointmentsTable;
        }

        if (!$this->tableExists('admin_process_appointments')) {
            db()->raw(
                "CREATE TABLE IF NOT EXISTS admin_process_appointments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    appointment_type VARCHAR(50) NOT NULL,
                    appointment_date DATE NOT NULL,
                    appointment_time TIME NOT NULL,
                    note TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY uniq_request_type (request_id, appointment_type),
                    INDEX idx_process_slot (appointment_date, appointment_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        $this->hasAdminProcessAppointmentsTable = $this->tableExists('admin_process_appointments');
        return $this->hasAdminProcessAppointmentsTable;
    }

    private function ensureAdminProcessAppointmentSlotsTable(): bool
    {
        if ($this->hasAdminProcessAppointmentSlotsTable !== null) {
            return $this->hasAdminProcessAppointmentSlotsTable;
        }

        if (!$this->tableExists('admin_process_appointment_slots')) {
            db()->raw(
                "CREATE TABLE IF NOT EXISTS admin_process_appointment_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    appointment_type VARCHAR(50) NOT NULL,
                    appointment_date DATE NOT NULL,
                    appointment_time TIME NOT NULL,
                    note TEXT NULL,
                    is_selected TINYINT(1) NOT NULL DEFAULT 0,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    INDEX idx_slot_request_type (request_id, appointment_type),
                    INDEX idx_slot_date_time (appointment_date, appointment_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        $this->hasAdminProcessAppointmentSlotsTable = $this->tableExists('admin_process_appointment_slots');
        return $this->hasAdminProcessAppointmentSlotsTable;
    }

    private function ensureAdminProcessAppointmentAccessTable(): bool
    {
        if ($this->hasAdminProcessAppointmentAccessTable !== null) {
            return $this->hasAdminProcessAppointmentAccessTable;
        }

        if (!$this->tableExists('admin_process_appointment_access')) {
            db()->raw(
                "CREATE TABLE IF NOT EXISTS admin_process_appointment_access (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    appointment_type VARCHAR(50) NOT NULL,
                    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
                    preferred_date DATE NULL,
                    note TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY uniq_request_access_type (request_id, appointment_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        if (!$this->tableHasColumn('admin_process_appointment_access', 'preferred_date')) {
            db()->raw("ALTER TABLE admin_process_appointment_access ADD COLUMN preferred_date DATE NULL AFTER is_enabled");
        }

        $this->hasAdminProcessAppointmentAccessTable = $this->tableExists('admin_process_appointment_access');
        return $this->hasAdminProcessAppointmentAccessTable;
    }

    private function ensurePaymentDeadlinesTable(): bool
    {
        if ($this->hasPaymentDeadlinesTable !== null) {
            return $this->hasPaymentDeadlinesTable;
        }

        if (!$this->tableExists('payment_deadlines')) {
            db()->raw(
                "CREATE TABLE IF NOT EXISTS payment_deadlines (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    request_id INT NOT NULL,
                    deadline_at DATETIME NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    cancelled_at DATETIME NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    UNIQUE KEY uniq_payment_deadline_request (request_id),
                    INDEX idx_payment_deadline_active (is_active, deadline_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }

        $this->hasPaymentDeadlinesTable = $this->tableExists('payment_deadlines');
        return $this->hasPaymentDeadlinesTable;
    }

    private function defaultAmountForDocument(string $documentName): float
    {
        $normalized = strtolower(trim($documentName));
        if ($normalized === '') {
            return 0.00;
        }

        if (
            str_contains($normalized, 'cor')
            || str_contains($normalized, 'tor')
            || str_contains($normalized, 'good moral')
            || str_contains($normalized, 'coe')
        ) {
            return 40.00;
        }

        return 0.00;
    }

    public function isAppointmentSlotTaken(string $date, string $time): bool
    {
        $row = db()->table('appointments')
            ->select('appointment_id')
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->limit(1)
            ->get();

        return (bool) $row;
    }

    public function studentHasAppointmentConflict(string $studentId, string $date, string $time): bool
    {
        $sql = "SELECT a.appointment_id
                FROM appointments a
                INNER JOIN document_requests dr ON dr.request_id = a.request_id
                WHERE dr.student_id = ?
                  AND a.appointment_date = ?
                  AND a.appointment_time = ?
                  AND dr.status NOT IN ('Completed', 'Rejected')
                LIMIT 1";

        $row = db()->raw($sql, [$studentId, $date, $time])->fetch();
        return (bool) $row;
    }

    public function getRequestTypes(): array
    {
        if ($this->requestTypesHasAmountColumn()) {
            return db()->table('request_types')
                ->select('id, document_name, amount')
                ->order_by('document_name', 'asc')
                ->get_all() ?: [];
        }

        $rows = db()->table('request_types')->order_by('document_name', 'asc')->get_all() ?: [];
        foreach ($rows as &$row) {
            $row['amount'] = $this->defaultAmountForDocument((string) ($row['document_name'] ?? ''));
        }
        unset($row);

        return $rows;
    }

    public function getRequestTypeById(int $requestTypeId): ?array
    {
        if ($this->requestTypesHasAmountColumn()) {
            $columns = 'id, document_name, amount';
        } else {
            $columns = 'id, document_name';
        }

        $row = db()->table('request_types')
            ->select($columns)
            ->where('id', $requestTypeId)
            ->limit(1)
            ->get();

        if (!$row) {
            return null;
        }

        if (!isset($row['amount']) || $row['amount'] === null) {
            $row['amount'] = $this->defaultAmountForDocument((string) ($row['document_name'] ?? ''));
        }

        return $row;
    }

    public function createRequest(string $studentId, int $typeId, string $purpose, string $date, string $status = 'Pending', int $quantity = 1): int
    {
        $payload = [
            'student_id' => $studentId,
            'request_type_id' => $typeId,
            'purpose' => $purpose,
            'request_date' => $date,
            'status' => $status,
        ];

        if ($this->documentRequestsHasQuantityColumn()) {
            $payload['quantity'] = max(1, min($quantity, 10));
        }

        return db()->table('document_requests')->insert($payload);
    }

    public function createAppointment(int $requestId, string $date, string $time): int
    {
        return db()->table('appointments')->insert([
            'request_id' => $requestId,
            'appointment_date' => $date,
            'appointment_time' => $time,
        ]);
    }

    public function getAppointmentByRequestId(int $requestId): ?array
    {
        $row = db()->table('appointments')
            ->select('appointment_id, request_id, appointment_date, appointment_time')
            ->where('request_id', $requestId)
            ->limit(1)
            ->get();

        return $row ?: null;
    }

    public function isAppointmentSlotTakenByOtherRequest(string $date, string $time, int $requestId): bool
    {
        $row = db()->table('appointments')
            ->select('appointment_id')
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->where('request_id', '<>', $requestId)
            ->limit(1)
            ->get();

        if ($row) {
            return true;
        }

        if (!$this->ensureAdminProcessAppointmentsTable()) {
            return false;
        }

        $processRow = db()->table('admin_process_appointments')
            ->select('id')
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->where('request_id', '<>', $requestId)
            ->limit(1)
            ->get();

        return (bool) $processRow;
    }

    public function setAppointmentScheduleForRequest(int $requestId, string $date, string $time): bool
    {
        $existing = $this->getAppointmentByRequestId($requestId);
        if ($existing) {
            $affected = db()->table('appointments')
                ->where('request_id', $requestId)
                ->update([
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                ]);

            return $affected >= 0;
        }

        return $this->createAppointment($requestId, $date, $time) > 0;
    }

    public function isAdminProcessSlotTakenByOtherRequest(string $type, string $date, string $time, int $requestId): bool
    {
        if (!$this->ensureAdminProcessAppointmentsTable()) {
            return false;
        }

        $type = strtolower(trim($type));

        $row = db()->table('admin_process_appointments')
            ->select('id')
            ->where('appointment_type', $type)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->where('request_id', '<>', $requestId)
            ->limit(1)
            ->get();

        if ($row) {
            return true;
        }

        $pickupRow = db()->table('appointments')
            ->select('appointment_id')
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->where('request_id', '<>', $requestId)
            ->limit(1)
            ->get();

        return (bool) $pickupRow;
    }

    public function setAdminProcessAppointmentForRequest(int $requestId, string $type, string $date, string $time, string $note = ''): bool
    {
        if (!$this->ensureAdminProcessAppointmentsTable()) {
            return false;
        }

        $type = strtolower(trim($type));
        $note = trim($note);
        $now = date('Y-m-d H:i:s');

        $existing = db()->table('admin_process_appointments')
            ->select('id')
            ->where('request_id', $requestId)
            ->where('appointment_type', $type)
            ->limit(1)
            ->get();

        if ($existing) {
            $affected = db()->table('admin_process_appointments')
                ->where('request_id', $requestId)
                ->where('appointment_type', $type)
                ->update([
                    'appointment_date' => $date,
                    'appointment_time' => $time,
                    'note' => $note,
                    'updated_at' => $now,
                ]);

            return $affected >= 0;
        }

        $inserted = db()->table('admin_process_appointments')->insert([
            'request_id' => $requestId,
            'appointment_type' => $type,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted > 0;
    }

    public function getAdminProcessAppointmentsByRequestId(int $requestId): array
    {
        if (!$this->ensureAdminProcessAppointmentsTable()) {
            return [];
        }

        return db()->table('admin_process_appointments')
            ->select('id, request_id, appointment_type, appointment_date, appointment_time, note, created_at, updated_at')
            ->where('request_id', $requestId)
            ->order_by('appointment_date', 'asc')
            ->order_by('appointment_time', 'asc')
            ->get_all() ?: [];
    }

    public function setAdminProcessAppointmentAccessForRequest(int $requestId, string $type, bool $enabled = true, string $note = '', ?string $preferredDate = null): bool
    {
        if (!$this->ensureAdminProcessAppointmentAccessTable()) {
            return false;
        }

        $type = strtolower(trim($type));
        $note = trim($note);
        $preferredDate = $preferredDate !== null ? trim($preferredDate) : null;
        if ($preferredDate === '') {
            $preferredDate = null;
        }
        $now = date('Y-m-d H:i:s');

        $existing = db()->table('admin_process_appointment_access')
            ->select('id')
            ->where('request_id', $requestId)
            ->where('appointment_type', $type)
            ->limit(1)
            ->get();

        $payload = [
            'is_enabled' => $enabled ? 1 : 0,
            'preferred_date' => $preferredDate,
            'note' => $note,
            'updated_at' => $now,
        ];

        if ($existing) {
            $affected = db()->table('admin_process_appointment_access')
                ->where('request_id', $requestId)
                ->where('appointment_type', $type)
                ->update($payload);

            return $affected >= 0;
        }

        $inserted = db()->table('admin_process_appointment_access')->insert([
            'request_id' => $requestId,
            'appointment_type' => $type,
            'is_enabled' => $enabled ? 1 : 0,
            'preferred_date' => $preferredDate,
            'note' => $note,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted > 0;
    }

    public function hasAdminProcessAppointmentForRequestType(int $requestId, string $type): bool
    {
        if (!$this->ensureAdminProcessAppointmentsTable()) {
            return false;
        }

        $type = strtolower(trim($type));
        if ($type === '') {
            return false;
        }

        $row = db()->table('admin_process_appointments')
            ->select('id')
            ->where('request_id', $requestId)
            ->where('appointment_type', $type)
            ->limit(1)
            ->get();

        return (bool) $row;
    }

    public function getAdminProcessAppointmentAccessByRequestId(int $requestId): array
    {
        if (!$this->ensureAdminProcessAppointmentAccessTable()) {
            return [];
        }

        return db()->table('admin_process_appointment_access')
                ->select('id, request_id, appointment_type, is_enabled, preferred_date, note, created_at, updated_at')
            ->where('request_id', $requestId)
            ->order_by('appointment_type', 'asc')
            ->get_all() ?: [];
    }

    public function getStudentOpenProcessAppointmentAccess(string $studentId): array
    {
        if (!$this->ensureAdminProcessAppointmentAccessTable()) {
            return [];
        }

        $sql = "SELECT
                    apx.id AS access_id,
                    apx.request_id,
                    dr.status,
                    rt.document_name,
                    apx.appointment_type,
                        apx.preferred_date,
                    apx.note,
                    apx.is_enabled,
                    CASE
                        WHEN apx.appointment_type = 'payment' THEN 'Payment Appointment'
                        WHEN apx.appointment_type = 'followup' THEN 'Follow-up Documents Appointment'
                        ELSE 'Admin Appointment'
                    END AS schedule_label
                FROM admin_process_appointment_access apx
                INNER JOIN document_requests dr ON dr.request_id = apx.request_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                WHERE dr.student_id = ?
                  AND apx.is_enabled = 1
                  AND dr.status NOT IN ('Completed', 'Rejected', 'Cancelled')
                ORDER BY dr.request_date DESC, apx.appointment_type ASC";

        return db()->raw($sql, [$studentId])->fetchAll() ?: [];
    }

    public function studentScheduleProcessAppointment(string $studentId, int $requestId, string $type, string $date, string $time): bool
    {
        if (!$this->ensureAdminProcessAppointmentAccessTable()) {
            return false;
        }

        $type = strtolower(trim($type));

        $sql = "SELECT apx.id, apx.note, apx.preferred_date
                FROM admin_process_appointment_access apx
                INNER JOIN document_requests dr ON dr.request_id = apx.request_id
                WHERE apx.request_id = ?
                  AND apx.appointment_type = ?
                  AND apx.is_enabled = 1
                  AND dr.student_id = ?
                  AND dr.status NOT IN ('Completed', 'Rejected', 'Cancelled')
                LIMIT 1";

        $row = db()->raw($sql, [$requestId, $type, $studentId])->fetch();
        if (!$row) {
            return false;
        }

        $preferredDate = trim((string) ($row['preferred_date'] ?? ''));
        if ($preferredDate !== '' && $date !== $preferredDate) {
            return false;
        }

        if ($this->isAdminProcessSlotTakenByOtherRequest($type, $date, $time, $requestId)) {
            return false;
        }

        $saved = $this->setAdminProcessAppointmentForRequest(
            $requestId,
            $type,
            $date,
            $time,
            (string) ($row['note'] ?? '')
        );

        if (!$saved) {
            return false;
        }

        return $this->setAdminProcessAppointmentAccessForRequest($requestId, $type, false, (string) ($row['note'] ?? ''));
    }

    public function createAdminProcessAppointmentSlot(int $requestId, string $type, string $date, string $time, string $note = ''): bool
    {
        if (!$this->ensureAdminProcessAppointmentSlotsTable()) {
            return false;
        }

        $type = strtolower(trim($type));
        $note = trim($note);
        $now = date('Y-m-d H:i:s');

        $inserted = db()->table('admin_process_appointment_slots')->insert([
            'request_id' => $requestId,
            'appointment_type' => $type,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'note' => $note,
            'is_selected' => 0,
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted > 0;
    }

    public function getAdminProcessAppointmentSlotsByRequestId(int $requestId): array
    {
        if (!$this->ensureAdminProcessAppointmentSlotsTable()) {
            return [];
        }

        return db()->table('admin_process_appointment_slots')
            ->select('id, request_id, appointment_type, appointment_date, appointment_time, note, is_selected, is_active, created_at, updated_at')
            ->where('request_id', $requestId)
            ->order_by('appointment_date', 'asc')
            ->order_by('appointment_time', 'asc')
            ->get_all() ?: [];
    }

    public function getStudentSelectableProcessAppointmentSlots(string $studentId): array
    {
        if (!$this->ensureAdminProcessAppointmentSlotsTable()) {
            return [];
        }

        $sql = "SELECT
                    aps.id AS slot_id,
                    aps.request_id,
                    dr.status,
                    rt.document_name,
                    aps.appointment_type,
                    aps.appointment_date,
                    aps.appointment_time,
                    COALESCE(aps.note, '') AS note,
                    CASE
                        WHEN aps.appointment_type = 'payment' THEN 'Payment Appointment'
                        WHEN aps.appointment_type = 'followup' THEN 'Follow-up Documents Appointment'
                        ELSE 'Admin Appointment'
                    END AS schedule_label
                FROM admin_process_appointment_slots aps
                INNER JOIN document_requests dr ON dr.request_id = aps.request_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                WHERE dr.student_id = ?
                  AND aps.is_active = 1
                  AND aps.is_selected = 0
                  AND dr.status NOT IN ('Completed', 'Rejected', 'Cancelled')
                  AND aps.appointment_date >= CURDATE()
                ORDER BY aps.appointment_date ASC, aps.appointment_time ASC, aps.id ASC";

        return db()->raw($sql, [$studentId])->fetchAll() ?: [];
    }

    public function selectProcessAppointmentSlotByStudent(int $slotId, string $studentId): ?array
    {
        if (!$this->ensureAdminProcessAppointmentSlotsTable()) {
            return null;
        }

        $sql = "SELECT
                    aps.id AS slot_id,
                    aps.request_id,
                    aps.appointment_type,
                    aps.appointment_date,
                    aps.appointment_time,
                    COALESCE(aps.note, '') AS note,
                    aps.is_active,
                    aps.is_selected,
                    dr.status,
                    u.id AS user_id
                FROM admin_process_appointment_slots aps
                INNER JOIN document_requests dr ON dr.request_id = aps.request_id
                LEFT JOIN users u ON u.username = dr.student_id
                WHERE aps.id = ?
                  AND dr.student_id = ?
                LIMIT 1";

        $row = db()->raw($sql, [$slotId, $studentId])->fetch();
        if (!$row) {
            return null;
        }

        if ((int) ($row['is_active'] ?? 0) !== 1) {
            return null;
        }

        if (in_array((string) ($row['status'] ?? ''), ['Completed', 'Rejected', 'Cancelled'], true)) {
            return null;
        }

        $requestId = (int) ($row['request_id'] ?? 0);
        $type = strtolower((string) ($row['appointment_type'] ?? ''));
        $date = (string) ($row['appointment_date'] ?? '');
        $time = (string) ($row['appointment_time'] ?? '');
        $note = (string) ($row['note'] ?? '');

        if ($requestId <= 0 || $type === '' || $date === '' || $time === '') {
            return null;
        }

        if ($this->isAdminProcessSlotTakenByOtherRequest($type, $date, $time, $requestId)) {
            return null;
        }

        $now = date('Y-m-d H:i:s');

        db()->table('admin_process_appointment_slots')
            ->where('request_id', $requestId)
            ->where('appointment_type', $type)
            ->update([
                'is_selected' => 0,
                'is_active' => 0,
                'updated_at' => $now,
            ]);

        $selected = db()->table('admin_process_appointment_slots')
            ->where('id', $slotId)
            ->update([
                'is_selected' => 1,
                'is_active' => 1,
                'updated_at' => $now,
            ]);

        if ($selected < 0) {
            return null;
        }

        $saved = $this->setAdminProcessAppointmentForRequest($requestId, $type, $date, $time, $note);
        if (!$saved) {
            return null;
        }

        return [
            'request_id' => $requestId,
            'appointment_type' => $type,
            'appointment_date' => $date,
            'appointment_time' => $time,
            'note' => $note,
            'user_id' => (int) ($row['user_id'] ?? 0),
        ];
    }

    public function createPaymentRecord(int $requestId, float $amount, string $paymentMethod, ?string $referenceNumber = null): int
    {
        return db()->table('payments')->insert([
            'request_id' => $requestId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'reference_number' => $referenceNumber,
            'payment_status' => 'Pending',
            'payment_date' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getPaymentByRequestId(int $requestId): ?array
    {
        $row = db()->table('payments')
            ->select('id, request_id, amount, payment_method, reference_number, payment_status, payment_date')
            ->where('request_id', $requestId)
            ->order_by('payment_date', 'desc')
            ->order_by('id', 'desc')
            ->limit(1)
            ->get();

        return $row ?: null;
    }

    public function updatePaymentStatusByRequestId(int $requestId, string $paymentStatus): bool
    {
        $paymentStatus = trim($paymentStatus);
        if ($paymentStatus === '') {
            return false;
        }

        $affected = db()->table('payments')
            ->where('request_id', $requestId)
            ->update([
                'payment_status' => $paymentStatus,
                'payment_date' => date('Y-m-d H:i:s'),
            ]);

        return $affected >= 0;
    }

    public function upsertCashPaymentDeadline(int $requestId, string $deadlineAt): bool
    {
        if (!$this->ensurePaymentDeadlinesTable()) {
            return false;
        }

        $deadlineAt = trim($deadlineAt);
        if ($deadlineAt === '') {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $existing = db()->table('payment_deadlines')
            ->select('id')
            ->where('request_id', $requestId)
            ->limit(1)
            ->get();

        if ($existing) {
            $affected = db()->table('payment_deadlines')
                ->where('request_id', $requestId)
                ->update([
                    'deadline_at' => $deadlineAt,
                    'is_active' => 1,
                    'cancelled_at' => null,
                    'updated_at' => $now,
                ]);

            return $affected >= 0;
        }

        $inserted = db()->table('payment_deadlines')->insert([
            'request_id' => $requestId,
            'deadline_at' => $deadlineAt,
            'is_active' => 1,
            'cancelled_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $inserted > 0;
    }

    public function getCashPaymentDeadlineByRequestId(int $requestId): ?array
    {
        if (!$this->ensurePaymentDeadlinesTable()) {
            return null;
        }

        $row = db()->table('payment_deadlines')
            ->select('id, request_id, deadline_at, is_active, cancelled_at, created_at, updated_at')
            ->where('request_id', $requestId)
            ->limit(1)
            ->get();

        return $row ?: null;
    }

    public function deactivateCashPaymentDeadline(int $requestId): bool
    {
        if (!$this->ensurePaymentDeadlinesTable()) {
            return false;
        }

        $affected = db()->table('payment_deadlines')
            ->where('request_id', $requestId)
            ->update([
                'is_active' => 0,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $affected >= 0;
    }

    public function autoCancelOverdueCashRequests(): int
    {
        if (!$this->ensurePaymentDeadlinesTable()) {
            return 0;
        }

        $sql = "SELECT
                    dr.request_id,
                    dr.student_id,
                    u.id AS user_id,
                    pd.deadline_at,
                    p.payment_status,
                    p.payment_method
                FROM payment_deadlines pd
                INNER JOIN document_requests dr ON dr.request_id = pd.request_id
                LEFT JOIN users u ON u.username = dr.student_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                WHERE pd.is_active = 1
                  AND pd.deadline_at < NOW()
                  AND dr.status NOT IN ('Completed', 'Rejected', 'Cancelled')
                  AND LOWER(COALESCE(p.payment_method, '')) = 'cash'
                  AND LOWER(COALESCE(p.payment_status, 'pending')) NOT IN ('paid', 'cancelled')";

        $rows = db()->raw($sql)->fetchAll() ?: [];
        if (empty($rows)) {
            return 0;
        }

        $cancelledCount = 0;
        foreach ($rows as $row) {
            $requestId = (int) ($row['request_id'] ?? 0);
            if ($requestId <= 0) {
                continue;
            }

            db()->table('document_requests')
                ->where('request_id', $requestId)
                ->update(['status' => 'Cancelled']);

            db()->table('payments')
                ->where('request_id', $requestId)
                ->update([
                    'payment_status' => 'Cancelled',
                    'payment_date' => date('Y-m-d H:i:s'),
                ]);

            $this->deactivateCashPaymentDeadline($requestId);

            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId > 0) {
                $deadlineAt = (string) ($row['deadline_at'] ?? '');
                $this->createNotification(
                    $userId,
                    'Request #' . $requestId . ' was automatically cancelled because cash payment was not completed before the deadline (' . $deadlineAt . ').'
                );
            }

            $cancelledCount++;
        }

        return $cancelledCount;
    }

    public function attachRequirementFile(int $requestId, string $path, string $dateTime): int
    {
        return db()->table('document_files')->insert([
            'request_id' => $requestId,
            'file_path' => $path,
            'generated_at' => $dateTime,
        ]);
    }

    public function createNotification(int $userId, string $message, string $status = 'Unread'): int
    {
        return db()->table('notifications')->insert([
            'user_id' => $userId,
            'message' => $message,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getStudentStats(string $studentId): array
    {
        $sql = "SELECT
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'Processing' THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN status IN ('Completed', 'Ready', 'Ready for Pickup') THEN 1 ELSE 0 END) AS completed,
                    COUNT(*) AS total
                FROM document_requests
                WHERE student_id = ?";

        $row = db()->raw($sql, [$studentId])->fetch();

        return [
            'pending' => (int) ($row['pending'] ?? 0),
            'processing' => (int) ($row['processing'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    public function getStudentRecentActivity(string $studentId): array
    {
        $sql = "SELECT dr.request_id, rt.document_name, dr.request_date, dr.status
                FROM document_requests dr
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                WHERE dr.student_id = ?
                ORDER BY dr.request_date DESC, dr.request_id DESC
                LIMIT 5";

        return db()->raw($sql, [$studentId])->fetchAll() ?: [];
    }

    public function getStudentRequests(string $studentId): array
    {
        $sql = "SELECT dr.request_id, rt.document_name, dr.purpose, dr.request_date, dr.status,
                       a.appointment_date, a.appointment_time,
                       p.amount, p.payment_method, p.reference_number, p.payment_status, p.payment_date,
                       CASE
                           WHEN LOWER(COALESCE(p.payment_method, '')) IN ('gcash', 'g-cash', 'g cash') THEN 'GCash'
                           WHEN LOWER(COALESCE(p.payment_method, '')) = 'cash' THEN 'Cash'
                           WHEN LOWER(COALESCE(p.payment_status, '')) = 'gcash' THEN 'GCash'
                           WHEN LOWER(COALESCE(p.payment_status, '')) = 'cash' THEN 'Cash'
                           ELSE p.payment_method
                       END AS payment_method_display,
                       p.payment_status AS payment_status_display
                FROM document_requests dr
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                LEFT JOIN appointments a ON a.request_id = dr.request_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                WHERE dr.student_id = ?
                ORDER BY dr.request_date DESC, dr.request_id DESC";

        return db()->raw($sql, [$studentId])->fetchAll() ?: [];
    }

    public function getStudentRequestById(int $requestId, string $studentId): ?array
    {
        $row = db()->table('document_requests')
            ->select('request_id, student_id, status')
            ->where('request_id', $requestId)
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        return $row ?: null;
    }

    public function getStudentAppointments(string $studentId): array
    {
        $sql = "SELECT
                    a.appointment_id AS appointment_id,
                    dr.request_id,
                    dr.status,
                    rt.document_name,
                    a.appointment_date,
                    a.appointment_time,
                    'Pickup' AS schedule_type,
                    'Document Pickup' AS schedule_label,
                    '' AS note
                FROM appointments a
                INNER JOIN document_requests dr ON dr.request_id = a.request_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                WHERE dr.student_id = ?";

        $rows = db()->raw($sql, [$studentId])->fetchAll() ?: [];

        if ($this->ensureAdminProcessAppointmentsTable()) {
            $processSql = "SELECT
                            apa.id AS appointment_id,
                            dr.request_id,
                            dr.status,
                            rt.document_name,
                            apa.appointment_date,
                            apa.appointment_time,
                            'Process' AS schedule_type,
                            CASE
                                WHEN apa.appointment_type = 'payment' THEN 'Payment Appointment'
                                WHEN apa.appointment_type = 'followup' THEN 'Follow-up Documents Appointment'
                                ELSE 'Admin Appointment'
                            END AS schedule_label,
                            COALESCE(apa.note, '') AS note
                        FROM admin_process_appointments apa
                        INNER JOIN document_requests dr ON dr.request_id = apa.request_id
                        INNER JOIN request_types rt ON rt.id = dr.request_type_id
                        WHERE dr.student_id = ?";

            $processRows = db()->raw($processSql, [$studentId])->fetchAll() ?: [];
            $rows = array_merge($rows, $processRows);
        }

        usort($rows, static function (array $a, array $b): int {
            $dateCmp = strcmp((string) ($b['appointment_date'] ?? ''), (string) ($a['appointment_date'] ?? ''));
            if ($dateCmp !== 0) {
                return $dateCmp;
            }

            $timeCmp = strcmp((string) ($b['appointment_time'] ?? ''), (string) ($a['appointment_time'] ?? ''));
            if ($timeCmp !== 0) {
                return $timeCmp;
            }

            return (int) ($b['appointment_id'] ?? 0) <=> (int) ($a['appointment_id'] ?? 0);
        });

        return $rows;
    }

    public function getAppointmentTimeSlots(): array
    {
        $slots = [];
        $current = new DateTimeImmutable('08:00:00');
        $end = new DateTimeImmutable('17:00:00');

        while ($current <= $end) {
            $slots[] = $current->format('H:i');
            $current = $current->modify('+15 minutes');
        }

        return $slots;
    }

    public function getBookedAppointmentTimesMap(?int $excludeRequestId = null): array
    {
        $this->ensureAdminProcessAppointmentsTable();

        $appointmentsSql = "SELECT appointment_date, appointment_time
                            FROM appointments";
        $appointmentsParams = [];
        if ($excludeRequestId !== null && $excludeRequestId > 0) {
            $appointmentsSql .= " WHERE request_id <> ?";
            $appointmentsParams[] = $excludeRequestId;
        }

        $processSql = "SELECT appointment_date, appointment_time
                       FROM admin_process_appointments";
        $processParams = [];
        if ($excludeRequestId !== null && $excludeRequestId > 0) {
            $processSql .= " WHERE request_id <> ?";
            $processParams[] = $excludeRequestId;
        }

        $rows = array_merge(
            db()->raw($appointmentsSql, $appointmentsParams)->fetchAll() ?: [],
            db()->raw($processSql, $processParams)->fetchAll() ?: []
        );

        $map = [];
        foreach ($rows as $row) {
            $date = trim((string) ($row['appointment_date'] ?? ''));
            $time = trim((string) ($row['appointment_time'] ?? ''));
            if ($date === '' || $time === '') {
                continue;
            }

            $time = substr($time, 0, 5);
            $map[$date][$time] = true;
        }

        ksort($map);

        return $map;
    }

    public function getBookedAppointmentTimesForDate(string $date, ?int $excludeRequestId = null): array
    {
        $date = trim($date);
        if ($date === '') {
            return [];
        }

        $map = $this->getBookedAppointmentTimesMap($excludeRequestId);
        return array_keys($map[$date] ?? []);
    }

    public function getBookedAppointmentDetailsMap(?int $excludeRequestId = null): array
    {
        $this->ensureAdminProcessAppointmentsTable();

        $appointmentsSql = "SELECT
                                a.appointment_date,
                                a.appointment_time,
                                dr.request_id,
                                dr.student_id,
                                rt.document_name,
                                dr.status,
                                'pickup' AS schedule_type,
                                'Pickup' AS schedule_label,
                                '' AS note
                            FROM appointments a
                            INNER JOIN document_requests dr ON dr.request_id = a.request_id
                            INNER JOIN request_types rt ON rt.id = dr.request_type_id";
        $appointmentsParams = [];
        if ($excludeRequestId !== null && $excludeRequestId > 0) {
            $appointmentsSql .= " WHERE a.request_id <> ?";
            $appointmentsParams[] = $excludeRequestId;
        }

        $processSql = "SELECT
                           apa.appointment_date,
                           apa.appointment_time,
                           dr.request_id,
                           dr.student_id,
                           rt.document_name,
                           dr.status,
                           apa.appointment_type AS schedule_type,
                           CASE
                               WHEN apa.appointment_type = 'payment' THEN 'Payment Appointment'
                               WHEN apa.appointment_type = 'followup' THEN 'Follow-up Documents Appointment'
                               ELSE 'Admin Appointment'
                           END AS schedule_label,
                           COALESCE(apa.note, '') AS note
                       FROM admin_process_appointments apa
                       INNER JOIN document_requests dr ON dr.request_id = apa.request_id
                       INNER JOIN request_types rt ON rt.id = dr.request_type_id";
        $processParams = [];
        if ($excludeRequestId !== null && $excludeRequestId > 0) {
            $processSql .= " WHERE apa.request_id <> ?";
            $processParams[] = $excludeRequestId;
        }

        $rows = array_merge(
            db()->raw($appointmentsSql, $appointmentsParams)->fetchAll() ?: [],
            db()->raw($processSql, $processParams)->fetchAll() ?: []
        );

        $map = [];
        foreach ($rows as $row) {
            $date = trim((string) ($row['appointment_date'] ?? ''));
            $time = substr(trim((string) ($row['appointment_time'] ?? '')), 0, 5);
            if ($date === '' || $time === '') {
                continue;
            }

            $map[$date][$time][] = [
                'request_id' => (int) ($row['request_id'] ?? 0),
                'student_id' => (string) ($row['student_id'] ?? ''),
                'document_name' => (string) ($row['document_name'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'schedule_type' => (string) ($row['schedule_type'] ?? ''),
                'schedule_label' => (string) ($row['schedule_label'] ?? ''),
                'note' => (string) ($row['note'] ?? ''),
            ];
        }

        ksort($map);

        return $map;
    }

    public function getNotificationsByUser(int $userId): array
    {
        $rows = db()->table('notifications')
            ->select('notification_id, message, status, created_at')
            ->where('user_id', $userId)
            ->get_all() ?: [];

        usort($rows, static function (array $a, array $b): int {
            $createdAtCmp = strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            if ($createdAtCmp !== 0) {
                return $createdAtCmp;
            }

            return (int) ($b['notification_id'] ?? 0) <=> (int) ($a['notification_id'] ?? 0);
        });

        return $rows;
    }

    public function countUnreadNotifications(int $userId): int
    {
        $sql = "SELECT COUNT(*) AS total_unread
                FROM notifications
                WHERE user_id = ? AND LOWER(status) = 'unread'";

        $row = db()->raw($sql, [$userId])->fetch();
        return (int) ($row['total_unread'] ?? 0);
    }

    public function markNotificationsAsRead(int $userId): bool
    {
        $affected = db()->table('notifications')
            ->where('user_id', $userId)
            ->where('status', 'Unread')
            ->update(['status' => 'Read']);

        return $affected >= 0;
    }

    public function getAdminRequestStats(?string $courseFilter = null): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN dr.status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN dr.status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN dr.status = 'Completed' THEN 1 ELSE 0 END) AS completed
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $row = db()->raw($sql, $params)->fetch();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'approved' => (int) ($row['approved'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
        ];
    }

    public function getRequestTypeChart(?string $courseFilter = null): array
    {
        $sql = "SELECT rt.document_name, COUNT(dr.request_id) AS total
                FROM request_types rt
                LEFT JOIN document_requests dr ON dr.request_type_id = rt.id
                LEFT JOIN students s ON s.student_id = dr.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " WHERE (dr.request_id IS NULL OR s.course = ?)";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= " GROUP BY rt.id, rt.document_name
                  ORDER BY total DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getAllRequests(?string $courseFilter = null): array
    {
        $sql = "SELECT dr.request_id, dr.student_id, s.first_name, s.last_name,
                       rt.document_name, dr.request_date, dr.status,
                       COUNT(df.file_id) AS file_count,
                       MAX(df.generated_at) AS last_file_upload,
                       a.appointment_date, a.appointment_time,
                       p.amount, p.payment_method, p.reference_number, p.payment_status, p.payment_date,
                       CASE
                           WHEN LOWER(COALESCE(p.payment_method, '')) = 'gcash' THEN 'GCash'
                           WHEN LOWER(COALESCE(p.payment_method, '')) = 'cash' THEN 'Cash'
                           WHEN LOWER(COALESCE(p.payment_status, '')) = 'gcash' THEN 'GCash'
                           WHEN LOWER(COALESCE(p.payment_status, '')) = 'cash' THEN 'Cash'
                           ELSE p.payment_method
                       END AS payment_method_display,
                       CASE
                           WHEN LOWER(COALESCE(p.payment_status, '')) IN ('gcash', 'cash') THEN 'Pending'
                           ELSE p.payment_status
                       END AS payment_status_display
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                LEFT JOIN document_files df ON df.request_id = dr.request_id
                LEFT JOIN appointments a ON a.request_id = dr.request_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= "\n                WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "\n                GROUP BY dr.request_id, dr.student_id, s.first_name, s.last_name,
                         rt.document_name, dr.request_date, dr.status,
                         a.appointment_date, a.appointment_time,
                         p.amount, p.payment_method, p.reference_number, p.payment_status, p.payment_date
                ORDER BY dr.request_date DESC, dr.request_id DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getAdminRequestRowById(int $requestId, ?string $courseFilter = null): ?array
    {
        foreach ($this->getAllRequests($courseFilter) as $row) {
            if ((int) ($row['request_id'] ?? 0) === $requestId) {
                return $row;
            }
        }

        return null;
    }

    public function updateRequestStatus(int $requestId, string $status): bool
    {
        $affected = db()->table('document_requests')
            ->where('request_id', $requestId)
            ->update(['status' => $status]);

        return $affected >= 0;
    }

    public function getRequestById(int $requestId, ?string $courseFilter = null): ?array
    {
        $this->ensurePaymentDeadlinesTable();

        $sql = "SELECT dr.request_id, dr.student_id, dr.status, dr.request_date, dr.request_type_id,
                       rt.document_name, s.first_name, s.last_name,
                       u.id AS user_id,
                       p.id AS payment_id,
                       p.amount,
                       p.payment_method,
                       p.reference_number,
                       p.payment_status,
                       p.payment_date,
                       pd.deadline_at AS cash_payment_deadline,
                       pd.is_active AS cash_payment_deadline_active,
                       CASE
                           WHEN LOWER(COALESCE(p.payment_method, '')) IN ('gcash', 'g-cash', 'g cash') THEN 'GCash'
                           WHEN LOWER(COALESCE(p.payment_method, '')) = 'cash' THEN 'Cash'
                           ELSE p.payment_method
                       END AS payment_method_display,
                       p.payment_status AS payment_status_display
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                LEFT JOIN users u ON u.username = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                LEFT JOIN payment_deadlines pd ON pd.request_id = dr.request_id AND pd.is_active = 1
                WHERE dr.request_id = ?";

        $params = [$requestId];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " AND s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                LIMIT 1";

        $row = db()->raw($sql, $params)->fetch();

        return $row ?: null;
    }

    public function getAllAppointments(?string $courseFilter = null): array
    {
        $sql = "SELECT a.appointment_id, a.appointment_date, a.appointment_time,
                       dr.request_id, dr.status,
                       s.student_id, s.first_name, s.last_name,
                       rt.document_name
                FROM appointments a
                INNER JOIN document_requests dr ON dr.request_id = a.request_id
                INNER JOIN students s ON s.student_id = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= "\n                WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getPendingRequestsForAppointment(?string $courseFilter = null): array
    {
        $sql = "SELECT dr.request_id, dr.request_date, dr.status,
                       s.student_id, s.first_name, s.last_name,
                       rt.document_name,
                       p.payment_method, p.payment_status
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                LEFT JOIN appointments a ON a.request_id = dr.request_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                WHERE dr.status IN ('Pending', 'Processing', 'Approved', 'Ready for Pickup')
                  AND a.appointment_id IS NULL";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " AND s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "\n                ORDER BY dr.request_date DESC, dr.request_id DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getFilesByRequestId(int $requestId, ?string $courseFilter = null): array
    {
        $sql = "SELECT df.file_id, df.file_path, df.generated_at,
                       dr.request_id, dr.student_id,
                       s.first_name, s.last_name,
                       rt.document_name
                FROM document_files df
                INNER JOIN document_requests dr ON dr.request_id = df.request_id
                INNER JOIN students s ON s.student_id = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                WHERE df.request_id = ?";

        $params = [$requestId];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " AND s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                ORDER BY df.generated_at DESC, df.file_id DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getMonthlyReportData(?string $courseFilter = null): array
    {
        $sql = "SELECT DATE_FORMAT(request_date, '%Y-%m') AS month,
                       COUNT(*) AS total_requests
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= "\n                WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                ORDER BY month DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getDocumentStatsReportData(?string $courseFilter = null): array
    {
        $sql = "SELECT rt.document_name, COUNT(dr.request_id) AS total
                FROM request_types rt
                LEFT JOIN document_requests dr ON dr.request_type_id = rt.id
                LEFT JOIN students s ON s.student_id = dr.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= "\n                WHERE (dr.request_id IS NULL OR s.course = ?)";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                GROUP BY rt.id, rt.document_name
                ORDER BY total DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getStudentSummaryReportData(?string $courseFilter = null): array
    {
        $sql = "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
                       COUNT(dr.request_id) AS total_requests
                FROM students s
                LEFT JOIN document_requests dr ON dr.student_id = s.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= "\n                WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "
                GROUP BY s.student_id, s.first_name, s.last_name
                ORDER BY total_requests DESC, student_name ASC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getDepartmentReportOverview(): array
    {
        $sql = "SELECT
                    UPPER(TRIM(s.course)) AS department_code,
                    COUNT(DISTINCT s.student_id) AS total_students,
                    COUNT(dr.request_id) AS total_requests,
                    SUM(CASE WHEN dr.status IN ('Pending', 'Processing', 'Approved', 'Ready for Pickup') THEN 1 ELSE 0 END) AS active_requests,
                    SUM(CASE WHEN dr.status = 'Completed' THEN 1 ELSE 0 END) AS completed_requests,
                    SUM(CASE WHEN dr.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_requests
                FROM students s
                LEFT JOIN document_requests dr ON dr.student_id = s.student_id
                WHERE TRIM(COALESCE(s.course, '')) <> ''
                GROUP BY UPPER(TRIM(s.course))
                ORDER BY total_requests DESC, department_code ASC";

        return db()->raw($sql)->fetchAll() ?: [];
    }

    public function getStatusDistributionData(?string $courseFilter = null): array
    {
        $sql = "SELECT dr.status, COUNT(*) AS total
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " WHERE s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "\n                GROUP BY dr.status
                ORDER BY total DESC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getPaymentMethodStatusData(?string $courseFilter = null): array
    {
        $sql = "SELECT
                    CASE
                        WHEN LOWER(COALESCE(p.payment_method, '')) IN ('gcash', 'g-cash', 'g cash') THEN 'GCash'
                        WHEN LOWER(COALESCE(p.payment_method, '')) = 'cash' THEN 'Cash'
                        ELSE 'Other'
                    END AS payment_method,
                    CASE
                        WHEN TRIM(COALESCE(p.payment_status, '')) = '' THEN 'Pending'
                        ELSE p.payment_status
                    END AS payment_status,
                    COUNT(*) AS total
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                WHERE 1 = 1";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " AND s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "\n                GROUP BY payment_method, payment_status
                ORDER BY payment_method ASC, payment_status ASC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getTurnaroundTimeByDocumentData(?string $courseFilter = null): array
    {
        $sql = "SELECT
                    rt.document_name,
                    ROUND(AVG(GREATEST(
                        0,
                        TIMESTAMPDIFF(
                            DAY,
                            dr.request_date,
                            COALESCE(
                                CONCAT(a.appointment_date, ' ', a.appointment_time),
                                p.payment_date,
                                dr.request_date
                            )
                        )
                    )), 2) AS avg_days,
                    COUNT(*) AS total_requests
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                INNER JOIN request_types rt ON rt.id = dr.request_type_id
                LEFT JOIN appointments a ON a.request_id = dr.request_id
                LEFT JOIN payments p ON p.id = (
                    SELECT p2.id
                    FROM payments p2
                    WHERE p2.request_id = dr.request_id
                    ORDER BY p2.payment_date DESC, p2.id DESC
                    LIMIT 1
                )
                WHERE dr.status IN ('Ready for Pickup', 'Completed')";

        $params = [];
        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $sql .= " AND s.course = ?";
            $params[] = strtoupper(trim($courseFilter));
        }

        $sql .= "\n                GROUP BY rt.document_name
                ORDER BY avg_days DESC, rt.document_name ASC";

        return db()->raw($sql, $params)->fetchAll() ?: [];
    }

    public function getAdminNotifications(): array
    {
        $sql = "SELECT al.log_id, al.action, al.created_at, a.name AS admin_name
                FROM audit_logs al
                LEFT JOIN admins a ON a.admin_id = al.admin_id
                ORDER BY al.created_at DESC, al.log_id DESC
                LIMIT 20";

        return db()->raw($sql)->fetchAll() ?: [];
    }

    public function logAdminAction(int $adminId, string $action): int
    {
        return db()->table('audit_logs')->insert([
            'admin_id' => $adminId,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
