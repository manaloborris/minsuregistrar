<?php

class AdminModel
{
    private ?bool $hasSectionColumn = null;
    private ?bool $hasRequestTypeAmountColumn = null;
    private ?bool $hasRegistrationStatusColumn = null;
    private ?bool $hasRejectionNoteColumn = null;
    private ?bool $hasRejectedAtColumn = null;
    private ?bool $hasAdminUsernameColumn = null;
    private ?bool $hasAdminRoleColumn = null;
    private ?bool $hasAdminDepartmentIdColumn = null;
    private ?bool $hasAdminIsActiveColumn = null;
    private ?bool $hasDepartmentsTable = null;

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

    private function adminsHasUsernameColumn(): bool
    {
        if ($this->hasAdminUsernameColumn !== null) {
            return $this->hasAdminUsernameColumn;
        }

        $this->hasAdminUsernameColumn = $this->tableHasColumn('admins', 'username');

        return $this->hasAdminUsernameColumn;
    }

    private function adminsHasRoleColumn(): bool
    {
        if ($this->hasAdminRoleColumn !== null) {
            return $this->hasAdminRoleColumn;
        }

        $this->hasAdminRoleColumn = $this->tableHasColumn('admins', 'role');

        return $this->hasAdminRoleColumn;
    }

    private function adminsHasDepartmentIdColumn(): bool
    {
        if ($this->hasAdminDepartmentIdColumn !== null) {
            return $this->hasAdminDepartmentIdColumn;
        }

        $this->hasAdminDepartmentIdColumn = $this->tableHasColumn('admins', 'department_id');

        return $this->hasAdminDepartmentIdColumn;
    }

    private function adminsHasIsActiveColumn(): bool
    {
        if ($this->hasAdminIsActiveColumn !== null) {
            return $this->hasAdminIsActiveColumn;
        }

        $this->hasAdminIsActiveColumn = $this->tableHasColumn('admins', 'is_active');

        return $this->hasAdminIsActiveColumn;
    }

    private function hasDepartmentsTable(): bool
    {
        if ($this->hasDepartmentsTable !== null) {
            return $this->hasDepartmentsTable;
        }

        $this->hasDepartmentsTable = $this->tableExists('departments');

        return $this->hasDepartmentsTable;
    }

    public function supportsAdminAccountManagement(): bool
    {
        return $this->adminsHasUsernameColumn() && $this->adminsHasRoleColumn();
    }

    public function getDepartmentCodeById(int $departmentId): ?string
    {
        if ($departmentId <= 0 || !$this->hasDepartmentsTable()) {
            return null;
        }

        $row = db()->table('departments')
            ->select('code')
            ->where('id', $departmentId)
            ->limit(1)
            ->get();

        if (!$row) {
            return null;
        }

        return strtoupper(trim((string) ($row['code'] ?? '')));
    }

    public function getAllAdminAccounts(): array
    {
        $hasUsername = $this->adminsHasUsernameColumn();
        $hasRole = $this->adminsHasRoleColumn();
        $hasDepartmentId = $this->adminsHasDepartmentIdColumn();
        $hasIsActive = $this->adminsHasIsActiveColumn();

        $columns = ['admin_id', 'name', 'email'];
        if ($hasUsername) {
            $columns[] = 'username';
        }
        if ($hasRole) {
            $columns[] = 'role';
        }
        if ($hasDepartmentId) {
            $columns[] = 'department_id';
        }
        if ($hasIsActive) {
            $columns[] = 'is_active';
        }

        $rows = db()->table('admins')
            ->select(implode(', ', $columns))
            ->order_by('admin_id', 'asc')
            ->get_all() ?: [];

        foreach ($rows as &$row) {
            $row['username'] = $row['username'] ?? '';
            $row['role'] = $row['role'] ?? 'admin';
            $row['department_id'] = isset($row['department_id']) ? (int) $row['department_id'] : null;
            $row['is_active'] = isset($row['is_active']) ? (int) $row['is_active'] : 1;
        }
        unset($row);

        return $rows;
    }

    public function getAdminAccountById(int $adminId): ?array
    {
        if ($adminId <= 0) {
            return null;
        }

        $hasUsername = $this->adminsHasUsernameColumn();
        $hasRole = $this->adminsHasRoleColumn();
        $hasDepartmentId = $this->adminsHasDepartmentIdColumn();
        $hasIsActive = $this->adminsHasIsActiveColumn();

        $columns = ['admin_id', 'name', 'email'];
        if ($hasUsername) {
            $columns[] = 'username';
        }
        if ($hasRole) {
            $columns[] = 'role';
        }
        if ($hasDepartmentId) {
            $columns[] = 'department_id';
        }
        if ($hasIsActive) {
            $columns[] = 'is_active';
        }

        $row = db()->table('admins')
            ->select(implode(', ', $columns))
            ->where('admin_id', $adminId)
            ->limit(1)
            ->get();

        if (!$row) {
            return null;
        }

        $row['username'] = $row['username'] ?? '';
        $row['role'] = $row['role'] ?? 'admin';
        $row['department_id'] = isset($row['department_id']) ? (int) $row['department_id'] : null;
        $row['is_active'] = isset($row['is_active']) ? (int) $row['is_active'] : 1;

        return $row;
    }

    public function getDepartments(): array
    {
        if (!$this->hasDepartmentsTable()) {
            return [];
        }

        return db()->table('departments')
            ->select('id, code, name')
            ->order_by('name', 'asc')
            ->get_all() ?: [];
    }

    public function syncDepartmentsFromCourses(array $courseOptions): void
    {
        if (!$this->hasDepartmentsTable()) {
            db()->raw(
                "CREATE TABLE IF NOT EXISTS departments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(50) NOT NULL UNIQUE,
                    name VARCHAR(100) NOT NULL
                )"
            );

            // Refresh cache after creating table.
            $this->hasDepartmentsTable = true;
        }

        foreach ($courseOptions as $courseCode => $courseLabel) {
            $code = strtoupper(trim((string) $courseCode));
            if ($code === '') {
                continue;
            }

            $name = trim((string) $courseLabel);
            if ($name === '') {
                $name = $code;
            }

            $existing = db()->table('departments')
                ->select('id')
                ->where('code', $code)
                ->limit(1)
                ->get();

            if ($existing) {
                continue;
            }

            db()->table('departments')->insert([
                'code' => $code,
                'name' => $name,
            ]);
        }
    }

    public function createAdminAccount(array $payload): int
    {
        if (!$this->supportsAdminAccountManagement()) {
            return 0;
        }

        $insert = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'password' => $payload['password'],
            'username' => $payload['username'],
            'role' => $payload['role'],
        ];

        if ($this->adminsHasDepartmentIdColumn()) {
            $insert['department_id'] = $payload['department_id'];
        }

        if ($this->adminsHasIsActiveColumn()) {
            $insert['is_active'] = $payload['is_active'];
        }

        return db()->table('admins')->insert($insert);
    }

    public function usernameExists(string $username, ?int $excludeAdminId = null): bool
    {
        if (!$this->adminsHasUsernameColumn()) {
            return false;
        }

        $query = db()->table('admins')
            ->select('admin_id')
            ->where('username', $username);

        if ($excludeAdminId !== null && $excludeAdminId > 0) {
            $query->where('admin_id', '!=', $excludeAdminId);
        }

        $row = $query->limit(1)->get();

        return (bool) $row;
    }

    public function emailExists(string $email, ?int $excludeAdminId = null): bool
    {
        $query = db()->table('admins')
            ->select('admin_id')
            ->where('email', $email);

        if ($excludeAdminId !== null && $excludeAdminId > 0) {
            $query->where('admin_id', '!=', $excludeAdminId);
        }

        $row = $query->limit(1)->get();

        return (bool) $row;
    }

    public function updateAdminAccount(int $adminId, array $payload): bool
    {
        if (!$this->supportsAdminAccountManagement()) {
            return false;
        }

        $update = [
            'name' => $payload['name'],
            'email' => $payload['email'],
            'username' => $payload['username'],
            'role' => $payload['role'],
        ];

        if ($this->adminsHasDepartmentIdColumn()) {
            $update['department_id'] = $payload['department_id'];
        }

        if ($this->adminsHasIsActiveColumn()) {
            $update['is_active'] = $payload['is_active'];
        }

        $affected = db()->table('admins')
            ->where('admin_id', $adminId)
            ->update($update);

        return $affected >= 0;
    }

    public function resetAdminPassword(int $adminId, string $passwordHash): bool
    {
        $affected = db()->table('admins')
            ->where('admin_id', $adminId)
            ->update(['password' => $passwordHash]);

        return $affected >= 0;
    }

    private function studentsHasSectionColumn(): bool
    {
        if ($this->hasSectionColumn !== null) {
            return $this->hasSectionColumn;
        }

        $this->hasSectionColumn = $this->tableHasColumn('students', 'section');

        return $this->hasSectionColumn;
    }

    private function studentsHasRegistrationStatusColumn(): bool
    {
        if ($this->hasRegistrationStatusColumn !== null) {
            return $this->hasRegistrationStatusColumn;
        }

        $this->hasRegistrationStatusColumn = $this->tableHasColumn('students', 'registration_status');

        return $this->hasRegistrationStatusColumn;
    }

    public function supportsStudentApproval(): bool
    {
        return $this->studentsHasRegistrationStatusColumn();
    }

    private function studentsHasRejectionNoteColumn(): bool
    {
        if ($this->hasRejectionNoteColumn !== null) {
            return $this->hasRejectionNoteColumn;
        }

        $this->hasRejectionNoteColumn = $this->tableHasColumn('students', 'rejection_note');

        return $this->hasRejectionNoteColumn;
    }

    private function studentsHasRejectedAtColumn(): bool
    {
        if ($this->hasRejectedAtColumn !== null) {
            return $this->hasRejectedAtColumn;
        }

        $this->hasRejectedAtColumn = $this->tableHasColumn('students', 'rejected_at');

        return $this->hasRejectedAtColumn;
    }

    public function supportsRejectionNotes(): bool
    {
        return $this->studentsHasRejectionNoteColumn();
    }

    public function supportsRejectedAutoPurge(): bool
    {
        return $this->studentsHasRejectedAtColumn() && $this->studentsHasRegistrationStatusColumn();
    }

    private function requestTypesHasAmountColumn(): bool
    {
        if ($this->hasRequestTypeAmountColumn !== null) {
            return $this->hasRequestTypeAmountColumn;
        }

        $this->hasRequestTypeAmountColumn = $this->tableHasColumn('request_types', 'amount');

        return $this->hasRequestTypeAmountColumn;
    }

    public function supportsRequestTypeAmount(): bool
    {
        return $this->requestTypesHasAmountColumn();
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

    public function getStudents(?string $courseFilter = null): array
    {
        $hasSection = $this->studentsHasSectionColumn();
        $hasStatus = $this->studentsHasRegistrationStatusColumn();
        $hasRejectionNote = $this->studentsHasRejectionNoteColumn();
        $hasRejectedAt = $this->studentsHasRejectedAtColumn();

        $columns = [
            'student_id',
            'first_name',
            'last_name',
            'course',
            'year_level',
            'email',
            'contact_number',
        ];

        if ($hasSection) {
            $columns[] = 'section';
        }

        if ($hasStatus) {
            $columns[] = 'registration_status';
        }

        if ($hasRejectionNote) {
            $columns[] = 'rejection_note';
        }

        if ($hasRejectedAt) {
            $columns[] = 'rejected_at';
        }

        $query = db()->table('students')
            ->select(implode(', ', $columns));

        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $query->where('course', strtoupper(trim($courseFilter)));
        }

        $rows = $query->get_all() ?: [];

        foreach ($rows as &$row) {
            if (!$hasSection) {
                $row['section'] = '';
            }

            if (!$hasStatus) {
                $row['registration_status'] = 'Approved';
            }

            if (!$hasRejectionNote) {
                $row['rejection_note'] = '';
            }

            if (!$hasRejectedAt) {
                $row['rejected_at'] = null;
            }
        }
        unset($row);

        usort($rows, static function (array $a, array $b) use ($hasSection): int {
            $yearCmp = strcmp((string) ($a['year_level'] ?? ''), (string) ($b['year_level'] ?? ''));
            if ($yearCmp !== 0) {
                return $yearCmp;
            }

            if ($hasSection) {
                $sectionCmp = strcmp((string) ($a['section'] ?? ''), (string) ($b['section'] ?? ''));
                if ($sectionCmp !== 0) {
                    return $sectionCmp;
                }
            }

            $lastNameCmp = strcmp((string) ($a['last_name'] ?? ''), (string) ($b['last_name'] ?? ''));
            if ($lastNameCmp !== 0) {
                return $lastNameCmp;
            }

            return strcmp((string) ($a['first_name'] ?? ''), (string) ($b['first_name'] ?? ''));
        });

        return $rows;
    }

    public function getStudentById(string $studentId, ?string $courseFilter = null): ?array
    {
        $hasSection = $this->studentsHasSectionColumn();
        $hasStatus = $this->studentsHasRegistrationStatusColumn();
        $hasRejectionNote = $this->studentsHasRejectionNoteColumn();
        $hasRejectedAt = $this->studentsHasRejectedAtColumn();

        $columns = [
            'student_id',
            'first_name',
            'last_name',
            'course',
            'year_level',
            'email',
            'contact_number',
        ];

        if ($hasSection) {
            $columns[] = 'section';
        }

        if ($hasStatus) {
            $columns[] = 'registration_status';
        }

        if ($hasRejectionNote) {
            $columns[] = 'rejection_note';
        }

        if ($hasRejectedAt) {
            $columns[] = 'rejected_at';
        }

        $query = db()->table('students')
            ->select(implode(', ', $columns))
            ->where('student_id', $studentId);

        if ($courseFilter !== null && trim($courseFilter) !== '') {
            $query->where('course', strtoupper(trim($courseFilter)));
        }

        $row = $query->limit(1)->get();

        if ($row) {
            if (!$hasSection) {
                $row['section'] = '';
            }

            if (!$hasStatus) {
                $row['registration_status'] = 'Approved';
            }

            if (!$hasRejectionNote) {
                $row['rejection_note'] = '';
            }

            if (!$hasRejectedAt) {
                $row['rejected_at'] = null;
            }
        }

        return $row ?: null;
    }

    public function getStudentUserId(string $studentId): ?int
    {
        $row = db()->table('users')
            ->select('id')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->limit(1)
            ->get();

        return $row ? (int) $row['id'] : null;
    }

    public function updateStudentInfo(string $studentId, array $payload): bool
    {
        $update = [
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'course' => $payload['course'],
            'year_level' => $payload['year_level'],
            'email' => $payload['email'],
            'contact_number' => $payload['contact_number'],
        ];

        if ($this->studentsHasSectionColumn()) {
            $update['section'] = $payload['section'];
        }

        $affected = db()->table('students')
            ->where('student_id', $studentId)
            ->update($update);

        return $affected >= 0;
    }

    public function updateStudentRegistrationStatus(string $studentId, string $status, ?string $rejectionNote = null): bool
    {
        if (!$this->studentsHasRegistrationStatusColumn()) {
            return false;
        }

        $allowed = ['Pending', 'Approved', 'Rejected'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $payload = ['registration_status' => $status];

        if ($this->studentsHasRejectionNoteColumn()) {
            if ($status === 'Rejected') {
                $payload['rejection_note'] = trim((string) $rejectionNote);
            } else {
                $payload['rejection_note'] = null;
            }
        }

        if ($this->studentsHasRejectedAtColumn()) {
            if ($status === 'Rejected') {
                $payload['rejected_at'] = date('Y-m-d H:i:s');
            } else {
                $payload['rejected_at'] = null;
            }
        }

        $affected = db()->table('students')
            ->where('student_id', $studentId)
            ->update($payload);

        return $affected >= 0;
    }

    public function purgeRejectedStudentsOlderThan(int $days): int
    {
        if ($days <= 0 || !$this->supportsRejectedAutoPurge()) {
            return 0;
        }

        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

        $rows = db()->table('students')
            ->select('student_id')
            ->where('registration_status', 'Rejected')
            ->where('rejected_at', '<=', $cutoff)
            ->get_all() ?: [];

        if (empty($rows)) {
            return 0;
        }

        $studentIds = array_values(array_filter(array_map(
            static fn(array $row): string => trim((string) ($row['student_id'] ?? '')),
            $rows
        )));

        if (empty($studentIds)) {
            return 0;
        }

        db()->table('users')
            ->where('role', 'student')
            ->in('username', $studentIds)
            ->delete();

        return db()->table('students')
            ->in('student_id', $studentIds)
            ->delete();
    }

    public function permanentlyDeleteRejectedStudent(string $studentId): bool
    {
        $student = $this->getStudentById($studentId);
        if (!$student) {
            return false;
        }

        $status = trim((string) ($student['registration_status'] ?? 'Approved'));
        if ($status !== 'Rejected') {
            return false;
        }

        $requestCount = db()->table('document_requests')
            ->select_count('request_id', 'total')
            ->where('student_id', $studentId)
            ->get();

        if ((int) ($requestCount['total'] ?? 0) > 0) {
            return false;
        }

        db()->table('users')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->delete();

        $deleted = db()->table('students')
            ->where('student_id', $studentId)
            ->delete();

        return $deleted > 0;
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

    public function addRequestType(string $name, float $amount): int
    {
        if ($this->requestTypesHasAmountColumn()) {
            return db()->table('request_types')->insert([
                'document_name' => $name,
                'amount' => $amount,
            ]);
        }

        return db()->table('request_types')->insert(['document_name' => $name]);
    }

    public function updateRequestType(int $id, string $name, float $amount): bool
    {
        $payload = ['document_name' => $name];
        if ($this->requestTypesHasAmountColumn()) {
            $payload['amount'] = $amount;
        }

        $affected = db()->table('request_types')
            ->where('id', $id)
            ->update($payload);

        return $affected >= 0;
    }

    public function deleteRequestType(int $id): bool
    {
        $affected = db()->table('request_types')->where('id', $id)->delete();
        return $affected >= 0;
    }
}
