<?php

class AuthModel
{
    private ?bool $hasSectionColumn = null;
    private ?bool $hasRegistrationStatusColumn = null;
    private ?bool $hasRejectionNoteColumn = null;
    private ?bool $hasProfilePhotoColumn = null;
    private ?bool $hasAdminUsernameColumn = null;
    private ?bool $hasAdminRoleColumn = null;
    private ?bool $hasAdminDepartmentIdColumn = null;
    private ?bool $hasAdminIsActiveColumn = null;
    private array $columnExistsCache = [];

    private function normalizeStudentId(string $studentId): string
    {
        return strtoupper(trim($studentId));
    }

    private function sanitizeScalar(string $value): string
    {
        $value = trim($value);
        return (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }

    private function tableHasColumn(string $tableName, string $columnName): bool
    {
        $cacheKey = $tableName . '.' . $columnName;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $sql = "SELECT 1
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = ?
                  AND COLUMN_NAME = ?
                LIMIT 1";

        $row = db()->raw($sql, [DB_NAME, $tableName, $columnName])->fetch();
        $this->columnExistsCache[$cacheKey] = (bool) $row;

        return $this->columnExistsCache[$cacheKey];
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

    private function studentsHasProfilePhotoColumn(): bool
    {
        if ($this->hasProfilePhotoColumn !== null) {
            return $this->hasProfilePhotoColumn;
        }

        $this->hasProfilePhotoColumn = $this->tableHasColumn('students', 'profile_photo');

        return $this->hasProfilePhotoColumn;
    }

    public function studentExists(string $studentId): bool
    {
        $studentId = $this->normalizeStudentId($studentId);

        $student = db()->table('students')
            ->select('student_id')
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        $user = db()->table('users')
            ->select('id')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->limit(1)
            ->get();

        return (bool) ($student || $user);
    }

    public function registerStudent(array $payload): int
    {
        return $this->insertStudentAndUser($payload, password_hash((string) ($payload['password'] ?? ''), PASSWORD_DEFAULT));
    }

    public function registerStudentWithPasswordHash(array $payload, string $passwordHash): int
    {
        if ($passwordHash === '') {
            return 0;
        }

        return $this->insertStudentAndUser($payload, $passwordHash);
    }

    private function insertStudentAndUser(array $payload, string $passwordHash): int
    {
        $studentId = $this->normalizeStudentId((string) ($payload['student_id'] ?? ''));

        $studentPayload = [
            'student_id' => $studentId,
            'first_name' => $this->sanitizeScalar((string) ($payload['first_name'] ?? '')),
            'last_name' => $this->sanitizeScalar((string) ($payload['last_name'] ?? '')),
            'course' => strtoupper($this->sanitizeScalar((string) ($payload['course'] ?? ''))),
            'year_level' => $this->sanitizeScalar((string) ($payload['year_level'] ?? '')),
            'email' => $this->sanitizeScalar((string) ($payload['email'] ?? '')),
            'contact_number' => $this->sanitizeScalar((string) ($payload['contact_number'] ?? '')),
        ];

        if ($this->studentsHasSectionColumn()) {
            $studentPayload['section'] = strtoupper($this->sanitizeScalar((string) ($payload['section'] ?? '')));
        }

        if ($this->studentsHasRegistrationStatusColumn()) {
            $studentPayload['registration_status'] = 'Pending';
        }

        db()->table('students')->insert($studentPayload);

        return db()->table('users')->insert([
            'username' => $studentId,
            'password' => $passwordHash,
            'role' => 'student',
        ]);
    }

    public function loginStudent(string $studentId, string $password): ?array
    {
        $studentId = $this->normalizeStudentId($studentId);

        $hasSection = $this->studentsHasSectionColumn();
        $hasStatus = $this->studentsHasRegistrationStatusColumn();
        $hasRejectionNote = $this->studentsHasRejectionNoteColumn();
        $hasProfilePhoto = $this->studentsHasProfilePhotoColumn();

        $studentColumns = 'student_id, first_name, last_name, course, year_level, email, contact_number';
        if ($hasSection) {
            $studentColumns .= ', section';
        }

        if ($hasStatus) {
            $studentColumns .= ', registration_status';
        }

        if ($hasRejectionNote) {
            $studentColumns .= ', rejection_note';
        }

        if ($hasProfilePhoto) {
            $studentColumns .= ', profile_photo';
        }

        $student = db()->table('students')
            ->select($studentColumns)
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        $user = db()->table('users')
            ->select('id, username, password, role')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->limit(1)
            ->get();

        if (!$student || !$user) {
            return null;
        }

        $row = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'user_password' => $user['password'],
            'role' => $user['role'],
            'student_id' => $student['student_id'],
            'first_name' => $student['first_name'],
            'last_name' => $student['last_name'],
            'course' => $student['course'],
            'year_level' => $student['year_level'],
            'section' => $student['section'] ?? '',
            'registration_status' => $student['registration_status'] ?? 'Approved',
            'rejection_note' => $student['rejection_note'] ?? '',
            'profile_photo' => $student['profile_photo'] ?? '',
            'email' => $student['email'],
            'contact_number' => $student['contact_number'],
        ];

        if (!$row) {
            return null;
        }

        if (!verify_password($password, $row['user_password'])) {
            return null;
        }

        return $row;
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

    public function loginAdmin(string $username, string $password): ?array
    {
        $username = $this->sanitizeScalar($username);

        $hasUsername = $this->adminsHasUsernameColumn();
        $hasRole = $this->adminsHasRoleColumn();
        $hasDepartmentId = $this->adminsHasDepartmentIdColumn();
        $hasIsActive = $this->adminsHasIsActiveColumn();

        $columns = 'admin_id, name, email, password';
        if ($hasUsername) {
            $columns .= ', username';
        }
        if ($hasRole) {
            $columns .= ', role';
        }
        if ($hasDepartmentId) {
            $columns .= ', department_id';
        }
        if ($hasIsActive) {
            $columns .= ', is_active';
        }

        $query = db()->table('admins')
            ->select($columns)
            ->limit(1);

        if ($hasUsername) {
            $query->where('username', $username);
        } else {
            // Backward compatibility for old schema without username column.
            $query->where('email', $username);
        }

        $row = $query->get();

        if (!$row) {
            return null;
        }

        if (!verify_password($password, $row['password'])) {
            return null;
        }

        if ($hasIsActive && (int) ($row['is_active'] ?? 1) !== 1) {
            return null;
        }

        $row['username'] = $row['username'] ?? ($row['email'] ?? '');
        $row['role'] = $row['role'] ?? 'admin';
        $row['department_id'] = isset($row['department_id']) ? (int) $row['department_id'] : null;
        $row['is_active'] = isset($row['is_active']) ? (int) $row['is_active'] : 1;

        return $row;
    }

    public function resetStudentPassword(string $studentId, string $email, string $newPassword): bool
    {
        if ($newPassword === '') {
            return false;
        }

        $studentId = $this->normalizeStudentId($studentId);
        $email = $this->sanitizeScalar($email);

        $student = db()->table('students')
            ->select('student_id, email')
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        $user = db()->table('users')
            ->select('id')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->limit(1)
            ->get();

        if (!$student || !$user) {
            return false;
        }

        if (strtolower((string) ($student['email'] ?? '')) !== strtolower($email)) {
            return false;
        }

        $affected = db()->table('users')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->update(['password' => password_hash($newPassword, PASSWORD_DEFAULT)]);

        return $affected >= 0;
    }

    public function studentIdentityMatches(string $studentId, string $email): bool
    {
        $studentId = $this->normalizeStudentId($studentId);
        $email = $this->sanitizeScalar($email);

        $student = db()->table('students')
            ->select('student_id, email')
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        $user = db()->table('users')
            ->select('id')
            ->where('username', $studentId)
            ->where('role', 'student')
            ->limit(1)
            ->get();

        if (!$student || !$user) {
            return false;
        }

        return strtolower((string) ($student['email'] ?? '')) === strtolower($email);
    }
}
