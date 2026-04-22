<?php

class StudentModel
{
    private ?bool $hasSectionColumn = null;
    private ?bool $hasProfilePhotoColumn = null;

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

    private function studentsHasSectionColumn(): bool
    {
        if ($this->hasSectionColumn !== null) {
            return $this->hasSectionColumn;
        }

        $this->hasSectionColumn = $this->tableHasColumn('students', 'section');

        return $this->hasSectionColumn;
    }

    private function studentsHasProfilePhotoColumn(): bool
    {
        if ($this->hasProfilePhotoColumn !== null) {
            return $this->hasProfilePhotoColumn;
        }

        $this->hasProfilePhotoColumn = $this->tableHasColumn('students', 'profile_photo');

        return $this->hasProfilePhotoColumn;
    }

    public function supportsProfilePhoto(): bool
    {
        return $this->studentsHasProfilePhotoColumn();
    }

    public function getProfile(string $studentId): ?array
    {
        $hasSection = $this->studentsHasSectionColumn();
        $hasProfilePhoto = $this->studentsHasProfilePhotoColumn();

        $columns = 'student_id, first_name, last_name, course, year_level, email, contact_number';
        if ($hasSection) {
            $columns .= ', section';
        }
        if ($hasProfilePhoto) {
            $columns .= ', profile_photo';
        }

        $row = db()->table('students')
            ->select($columns)
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        if ($row && !$hasSection) {
            $row['section'] = '';
        }

        if ($row && !$hasProfilePhoto) {
            $row['profile_photo'] = '';
        }

        return $row ?: null;
    }

    public function findUserIdByStudentId(string $studentId): ?int
    {
        $row = db()->table('users')
            ->select('id')
            ->where('username', $studentId)
            ->limit(1)
            ->get();

        return $row ? (int) $row['id'] : null;
    }

    public function updateContact(string $studentId, string $email, string $contact): bool
    {
        $affected = db()->table('students')
            ->where('student_id', $studentId)
            ->update([
                'email' => $email,
                'contact_number' => $contact,
            ]);

        return $affected >= 0;
    }

    public function updateProfilePhoto(string $studentId, string $photoPath): bool
    {
        if (!$this->studentsHasProfilePhotoColumn()) {
            return false;
        }

        $affected = db()->table('students')
            ->where('student_id', $studentId)
            ->update([
                'profile_photo' => $photoPath,
            ]);

        return $affected >= 0;
    }

    public function getProfilePhotoPath(string $studentId): ?string
    {
        if (!$this->studentsHasProfilePhotoColumn()) {
            return null;
        }

        $row = db()->table('students')
            ->select('profile_photo')
            ->where('student_id', $studentId)
            ->limit(1)
            ->get();

        if (!$row) {
            return null;
        }

        return (string) ($row['profile_photo'] ?? '');
    }
}
