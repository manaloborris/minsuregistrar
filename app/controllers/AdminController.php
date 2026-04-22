<?php

require_once APP_ROOT . '/app/controllers/BaseController.php';
require_once APP_ROOT . '/app/models/AdminModel.php';
require_once APP_ROOT . '/app/models/RequestModel.php';

class AdminController extends BaseController
{
    private const REJECTED_AUTO_PURGE_DAYS = 30;
    private const YEAR_LEVEL_OPTIONS = [
        '1st Year',
        '2nd Year',
        '3rd Year',
        '4th Year',
    ];
    private const COURSE_OPTIONS = [
        'BAEL' => 'BAEL - Bachelor of Arts in English Language',
        'BAPSY' => 'BAPSY - Bachelor of Arts in Psychology',
        'BSTM' => 'BSTM - Bachelor of Science in Tourism Management',
        'BSHM' => 'BSHM - Bachelor of Science in Hospitality Management',
        'BSIT' => 'BSIT - Bachelor of Science in Information Technology',
        'BSCRIM' => 'BSCRIM - Bachelor of Science in Criminology',
        'BSED-ENG' => 'BSED-ENG - Bachelor of Secondary Education (English)',
        'BSED-FIL' => 'BSED-FIL - Bachelor of Secondary Education (Filipino)',
        'BSED-MATH' => 'BSED-MATH - Bachelor of Secondary Education (Mathematics)',
        'BSED-SCI' => 'BSED-SCI - Bachelor of Secondary Education (Science)',
        'BTVTED' => 'BTVTED - Bachelor of Technical-Vocational Teacher Education',
        'BTLED' => 'BTLED - Bachelor of Technology and Livelihood Education',
    ];

    private AdminModel $adminModel;
    private RequestModel $requestModel;

    private function isSuperAdmin(): bool
    {
        return strtolower((string) ($_SESSION['auth']['admin']['role'] ?? 'admin')) === 'superadmin';
    }

    private function requireSuperAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        set_flash('error', 'Only superadmin can access admin account management.');
        redirect('admin/dashboard');
    }

    private function requireRoutineAdmin(bool $asJson = false): bool
    {
        if (!$this->isSuperAdmin()) {
            return true;
        }

        $message = 'Superadmin is limited to governance and analytics. Use a department admin account for routine processing.';
        if ($asJson) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => $message,
            ]);
            exit;
        }

        set_flash('error', $message);
        redirect('admin/dashboard');
    }

    private function currentAdminRole(): string
    {
        return strtolower((string) ($_SESSION['auth']['admin']['role'] ?? 'admin'));
    }

    private function currentAdminDepartmentCode(): ?string
    {
        if ($this->currentAdminRole() === 'superadmin') {
            return null;
        }

        $departmentId = (int) ($_SESSION['auth']['admin']['department_id'] ?? 0);
        if ($departmentId <= 0) {
            return null;
        }

        return $this->adminModel->getDepartmentCodeById($departmentId);
    }

    private function resolveStudentUserIdFromRequest(array $request): int
    {
        $userId = (int) ($request['user_id'] ?? 0);
        if ($userId > 0) {
            return $userId;
        }

        $studentId = trim((string) ($request['student_id'] ?? ''));
        if ($studentId === '') {
            return 0;
        }

        return (int) ($this->adminModel->getStudentUserId($studentId) ?? 0);
    }

    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->requestModel = new RequestModel();
    }

    private function analyticsCacheDir(): string
    {
        return APP_ROOT . '/storage/cache/admin';
    }

    private function analyticsCachePath(string $cacheKey): string
    {
        $directory = $this->analyticsCacheDir();
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory . '/' . sha1($cacheKey) . '.cache';
    }

    private function readAnalyticsCache(string $cacheKey, int $ttlSeconds): ?array
    {
        $cacheFile = $this->analyticsCachePath($cacheKey);
        if (!is_file($cacheFile)) {
            return null;
        }

        if ((time() - (int) filemtime($cacheFile)) > $ttlSeconds) {
            return null;
        }

        $payload = @unserialize((string) file_get_contents($cacheFile));
        return is_array($payload) ? $payload : null;
    }

    private function writeAnalyticsCache(string $cacheKey, array $payload): void
    {
        $cacheFile = $this->analyticsCachePath($cacheKey);
        @file_put_contents($cacheFile, serialize($payload), LOCK_EX);
    }

    private function rememberAnalytics(string $cacheKey, int $ttlSeconds, callable $loader, array $fallback = []): array
    {
        $cached = $this->readAnalyticsCache($cacheKey, $ttlSeconds);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $payload = $loader();
            if (is_array($payload)) {
                $this->writeAnalyticsCache($cacheKey, $payload);
                return $payload;
            }
        } catch (\Throwable $e) {
            // Fall through to fallback below.
        }

        return $fallback;
    }

    private function runCashPaymentAutoCancelSweep(): void
    {
        $this->requestModel->autoCancelOverdueCashRequests();
    }

    public function dashboard(): void
    {
        $departmentCode = $this->currentAdminDepartmentCode();
        $cacheKey = 'dashboard.' . ($departmentCode ?: 'superadmin');
        $dashboardData = $this->rememberAnalytics($cacheKey, 300, function () use ($departmentCode): array {
            return [
                'stats' => $this->requestModel->getAdminRequestStats($departmentCode),
                'monthlyData' => $this->requestModel->getMonthlyReportData($departmentCode),
                'documentStatsData' => $this->requestModel->getDocumentStatsReportData($departmentCode),
                'statusDistributionData' => $this->requestModel->getStatusDistributionData($departmentCode),
                'paymentAnalyticsData' => $this->requestModel->getPaymentMethodStatusData($departmentCode),
                'turnaroundData' => $this->requestModel->getTurnaroundTimeByDocumentData($departmentCode),
            ];
        }, [
            'stats' => ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0],
            'monthlyData' => [],
            'documentStatsData' => [],
            'statusDistributionData' => [],
            'paymentAnalyticsData' => [],
            'turnaroundData' => [],
        ]);

        $stats = $dashboardData['stats'] ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
        $monthlyData = $dashboardData['monthlyData'] ?? [];
        $documentStatsData = $dashboardData['documentStatsData'] ?? [];
        $statusDistributionData = $dashboardData['statusDistributionData'] ?? [];
        $paymentAnalyticsData = $dashboardData['paymentAnalyticsData'] ?? [];
        $turnaroundData = $dashboardData['turnaroundData'] ?? [];

        try {
            // Superadmin summary data
            $departmentOverview = [];
            $adminCounts = [];
            $appointmentCounts = [];
            if ($this->isSuperAdmin()) {
                $departmentOverview = $this->requestModel->getDepartmentReportOverview();
                // Admins per department
                $adminRows = $this->adminModel->getAllAdminAccounts();
                $departments = $this->adminModel->getDepartments();
                $adminCounts = [];
                foreach ($departments as $dept) {
                    $adminCounts[$dept['code']] = 0;
                }
                foreach ($adminRows as $admin) {
                    $deptCode = null;
                    if (isset($admin['department_id']) && $admin['department_id']) {
                        $deptCode = $this->adminModel->getDepartmentCodeById($admin['department_id']);
                    }
                    if ($deptCode && isset($adminCounts[$deptCode])) {
                        $adminCounts[$deptCode]++;
                    }
                }
                // Appointments per department
                $appointments = $this->requestModel->getAllAppointments();
                $appointmentCounts = [];
                foreach ($departments as $dept) {
                    $appointmentCounts[$dept['code']] = 0;
                }
                foreach ($appointments as $appt) {
                    $deptCode = strtoupper(trim($appt['course'] ?? $appt['department_code'] ?? ''));
                    if ($deptCode && isset($appointmentCounts[$deptCode])) {
                        $appointmentCounts[$deptCode]++;
                    }
                }
            }
            $this->render('admin/dashboard', [
                'active' => 'dashboard',
                'stats' => $stats,
                'monthlyData' => $monthlyData,
                'documentStatsData' => $documentStatsData,
                'statusDistributionData' => $statusDistributionData,
                'paymentAnalyticsData' => $paymentAnalyticsData,
                'turnaroundData' => $turnaroundData,
                'departmentCode' => $departmentCode,
                // Superadmin analytics
                'departmentOverview' => $departmentOverview,
                'adminCounts' => $adminCounts,
                'appointmentCounts' => $appointmentCounts,
            ]);
        } catch (\Throwable $e) {
            set_flash('error', 'Dashboard content could not be loaded right now. A safe fallback view is shown instead.');
            $this->render('admin/dashboard_fallback', [
                'active' => 'dashboard',
                'departmentCode' => $departmentCode,
            ]);
        }
    }

    public function dashboardAnalytics(): void
    {
        $departmentCode = $this->currentAdminDepartmentCode();
        $cacheKey = 'dashboard-analytics.' . ($departmentCode ?: 'superadmin');
        $dashboardData = $this->rememberAnalytics($cacheKey, 300, function () use ($departmentCode): array {
            return [
                'stats' => $this->requestModel->getAdminRequestStats($departmentCode),
                'monthlyData' => $this->requestModel->getMonthlyReportData($departmentCode),
                'documentStatsData' => $this->requestModel->getDocumentStatsReportData($departmentCode),
                'statusDistributionData' => $this->requestModel->getStatusDistributionData($departmentCode),
                'paymentAnalyticsData' => $this->requestModel->getPaymentMethodStatusData($departmentCode),
                'turnaroundData' => $this->requestModel->getTurnaroundTimeByDocumentData($departmentCode),
            ];
        }, [
            'stats' => ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0],
            'monthlyData' => [],
            'documentStatsData' => [],
            'statusDistributionData' => [],
            'paymentAnalyticsData' => [],
            'turnaroundData' => [],
        ]);

        $stats = $dashboardData['stats'] ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'completed' => 0];
        $monthlyData = $dashboardData['monthlyData'] ?? [];
        $documentStatsData = $dashboardData['documentStatsData'] ?? [];
        $statusDistributionData = $dashboardData['statusDistributionData'] ?? [];
        $paymentAnalyticsData = $dashboardData['paymentAnalyticsData'] ?? [];
        $turnaroundData = $dashboardData['turnaroundData'] ?? [];

        try {
            $this->render('admin/dashboard', [
                'active' => 'dashboard',
                'stats' => $stats,
                'monthlyData' => $monthlyData,
                'documentStatsData' => $documentStatsData,
                'statusDistributionData' => $statusDistributionData,
                'paymentAnalyticsData' => $paymentAnalyticsData,
                'turnaroundData' => $turnaroundData,
                'departmentCode' => $departmentCode,
            ]);
        } catch (\Throwable $e) {
            set_flash('error', 'Dashboard content could not be loaded right now. A safe fallback view is shown instead.');
            $this->render('admin/dashboard_fallback', [
                'active' => 'dashboard',
                'departmentCode' => $departmentCode,
            ]);
        }
    }

    public function manageRequests(): void
    {
        $this->requireRoutineAdmin();
        $this->runCashPaymentAutoCancelSweep();

        $departmentCode = $this->currentAdminDepartmentCode();
        $requests = $this->requestModel->getAllRequests($departmentCode);

        $this->render('admin/manage_requests', [
            'active' => 'requests',
            'requests' => $requests,
            'departmentCode' => $departmentCode,
        ]);
    }

    public function manageRequestDetail(string $request_id): void
    {
        $this->requireRoutineAdmin();
        $this->runCashPaymentAutoCancelSweep();

        $requestId = (int) $request_id;
        if ($requestId <= 0) {
            set_flash('error', 'Invalid request reference.');
            redirect('admin/manage-requests');
        }

        $request = $this->requestModel->getAdminRequestRowById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        $this->render('admin/request_detail', [
            'active' => 'requests',
            'request' => $request,
            'processAppointments' => $this->requestModel->getAdminProcessAppointmentsByRequestId($requestId),
            'processAppointmentAccess' => $this->requestModel->getAdminProcessAppointmentAccessByRequestId($requestId),
            'appointmentTimeSlots' => $this->requestModel->getAppointmentTimeSlots(),
            'occupiedAppointmentTimesByDate' => $this->requestModel->getBookedAppointmentTimesMap($requestId),
            'bookedAppointmentDetailsByDate' => $this->requestModel->getBookedAppointmentDetailsMap($requestId),
        ]);
    }

    public function updateRequestPaymentStatus(): void
    {
        $this->requireRoutineAdmin();
        $this->runCashPaymentAutoCancelSweep();

        $requestId = (int) $this->post('request_id');
        $paymentStatus = trim($this->post('payment_status'));

        $allowed = ['Pending', 'Paid', 'Unpaid', 'Failed', 'Cancelled'];
        if ($requestId <= 0 || !in_array($paymentStatus, $allowed, true)) {
            set_flash('error', 'Invalid payment status update payload.');
            redirect('admin/manage-requests');
        }

        $detailPath = 'admin/manage-requests/' . $requestId;
        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record was not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        $currentPaymentStatus = strtolower(trim((string) ($request['payment_status'] ?? '')));
        $nextPaymentStatus = strtolower($paymentStatus);
        if ($currentPaymentStatus === 'paid') {
            set_flash('error', 'Payment status is locked once marked as Paid.');
            redirect($detailPath);
        }

        if (!$this->requestModel->updatePaymentStatusByRequestId($requestId, $paymentStatus)) {
            set_flash('error', 'Payment status update failed.');
            redirect($detailPath);
        }

        if (in_array($paymentStatus, ['Paid', 'Cancelled'], true)) {
            $this->requestModel->setAdminProcessAppointmentAccessForRequest($requestId, 'payment', false, 'Payment schedule closed.');
            $this->requestModel->deactivateCashPaymentDeadline($requestId);
        }

        $studentUserId = $this->resolveStudentUserIdFromRequest($request);

        if (in_array($paymentStatus, ['Unpaid', 'Cancelled'], true) && $studentUserId > 0) {
            $this->requestModel->createNotification(
                $studentUserId,
                'Payment for request #' . $requestId . ' is ' . $paymentStatus . '. Please wait for admin to send a new payment appointment date if needed.'
            );
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Updated payment status for request #' . $requestId . ' to ' . $paymentStatus . '.');
        }

        if ($studentUserId > 0) {
            $this->requestModel->createNotification(
                $studentUserId,
                'Payment status for request #' . $requestId . ' was updated to ' . $paymentStatus . '.'
            );
        }

        set_flash('success', 'Payment status updated successfully.');
        redirect($detailPath);
    }

    public function updateRequestStatus(): void
    {
        $this->requireRoutineAdmin();
        $requestId = (int) $this->post('request_id');
        $status = $this->post('status');
        $adminMessage = $this->post('admin_message');
        $requiredFiles = $this->post('required_files');

        $allowed = ['Pending', 'Processing', 'Approved', 'Ready for Pickup', 'Completed', 'Rejected', 'Cancelled'];
        if ($requestId <= 0 || !in_array($status, $allowed, true)) {
            set_flash('error', 'Invalid request update payload.');
            redirect('admin/manage-requests');
        }

        $detailPath = 'admin/manage-requests/' . $requestId;

        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record was not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        $currentStatus = $request['status'];
        $transitions = [
            'Pending' => ['Processing', 'Approved', 'Rejected', 'Cancelled'],
            'Processing' => ['Approved', 'Ready for Pickup', 'Rejected', 'Cancelled'],
            'Approved' => ['Ready for Pickup', 'Completed', 'Cancelled'],
            'Ready for Pickup' => ['Completed', 'Cancelled'],
            'Completed' => [],
            'Rejected' => [],
            'Cancelled' => [],
        ];

        if ($status === $currentStatus) {
            if ($adminMessage === '' && $requiredFiles === '') {
                set_flash('error', 'Request is already in this status. Add an instruction or choose a different status.');
                redirect($detailPath);
            }

            if (!empty($request['user_id'])) {
                $message = 'Update on request #' . $requestId . ': status remains ' . $status . '.';
                if ($adminMessage !== '') {
                    $message .= ' Admin instruction: ' . $adminMessage;
                }
                if ($requiredFiles !== '') {
                    $message .= ' Required file(s): ' . $requiredFiles;
                }
                $this->requestModel->createNotification((int) $request['user_id'], $message);
            }

            $adminId = current_admin_id();
            if ($adminId) {
                $action = 'Sent instruction for request #' . $requestId . ' while keeping status as ' . $status;
                if ($requiredFiles !== '') {
                    $action .= ' | Required file(s): ' . $requiredFiles;
                }
                if ($adminMessage !== '') {
                    $action .= ' | Message: ' . $adminMessage;
                }
                $this->requestModel->logAdminAction($adminId, $action);
            }

            set_flash('success', 'Instruction sent to student without changing request status.');
            redirect($detailPath);
        }

        $nextAllowed = $transitions[$currentStatus] ?? [];
        if (!in_array($status, $nextAllowed, true)) {
            set_flash('error', 'Invalid transition from ' . $currentStatus . ' to ' . $status . '.');
            redirect($detailPath);
        }

        $this->requestModel->updateRequestStatus($requestId, $status);

        if (!empty($request['user_id'])) {
            $message = 'Update on request #' . $requestId . ': status changed to ' . $status . '.';
            if ($adminMessage !== '') {
                $message .= ' Admin note: ' . $adminMessage;
            }
            if ($requiredFiles !== '') {
                $message .= ' Required file(s): ' . $requiredFiles;
            }
            $this->requestModel->createNotification((int) $request['user_id'], $message);
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $action = 'Updated request #' . $requestId . ' of student ' . $request['student_id'] . ' to ' . $status;
            if ($requiredFiles !== '') {
                $action .= ' | Required file(s): ' . $requiredFiles;
            }
            $this->requestModel->logAdminAction($adminId, $action);
        }

        set_flash('success', 'Request status updated successfully.');
        redirect($detailPath);
    }

    public function updatePickupSchedule(): void
    {
        $this->requireRoutineAdmin();
        $requestId = (int) $this->post('request_id');
        $pickupDate = trim($this->post('pickup_date'));
        $pickupTime = trim($this->post('pickup_time'));

        if ($requestId <= 0 || $pickupDate === '' || $pickupTime === '') {
            set_flash('error', 'Pickup schedule update failed: request, date, and time are required.');
            redirect('admin/manage-requests');
        }

        $detailPath = 'admin/manage-requests/' . $requestId;

        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record was not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        if (!in_array($request['status'], ['Processing', 'Approved', 'Ready for Pickup'], true)) {
            set_flash('error', 'Pickup schedule can only be edited when status is Processing, Approved, or Ready for Pickup.');
            redirect($detailPath);
        }

        $scheduleDateObj = DateTime::createFromFormat('Y-m-d', $pickupDate);
        $today = new DateTime('today');
        if (!$scheduleDateObj || $scheduleDateObj < $today) {
            set_flash('error', 'Pickup date cannot be in the past.');
            redirect($detailPath);
        }

        $dayOfWeek = (int) $scheduleDateObj->format('N');
        if ($dayOfWeek > 5) {
            set_flash('error', 'Pickup schedule must be Monday to Friday only.');
            redirect($detailPath);
        }

        $pickupTime = $this->normalizeQuarterHourTime($pickupTime);
        if ($pickupTime === null) {
            set_flash('error', 'Pickup time must be in 15-minute intervals (:00, :15, :30, or :45).');
            redirect($detailPath);
        }
        if ($pickupTime < '08:00:00' || $pickupTime > '17:00:00') {
            set_flash('error', 'Pickup time must be within office hours (8:00 AM to 5:00 PM).');
            redirect($detailPath);
        }

        if ($this->requestModel->isAppointmentSlotTakenByOtherRequest($pickupDate, $pickupTime, $requestId)) {
            set_flash('error', 'Pickup schedule conflict: date/time is already assigned to another request.');
            redirect($detailPath);
        }

        $this->requestModel->setAppointmentScheduleForRequest($requestId, $pickupDate, $pickupTime);

        $studentUserId = $this->resolveStudentUserIdFromRequest($request);
        if ($studentUserId > 0) {
            $message = 'Pickup schedule updated for request #' . $requestId . ': '
                . $pickupDate . ' ' . substr($pickupTime, 0, 5) . '.';
            $this->requestModel->createNotification($studentUserId, $message);
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction(
                $adminId,
                'Updated pickup schedule for request #' . $requestId
                . ' (' . $pickupDate . ' ' . substr($pickupTime, 0, 5) . ')'
            );
        }

        set_flash('success', 'Pickup schedule updated successfully.');
        redirect($detailPath);
    }

    public function updateProcessAppointment(): void
    {
        $this->requireRoutineAdmin();
        $this->runCashPaymentAutoCancelSweep();

        $requestId = (int) $this->post('request_id');
        $appointmentType = strtolower(trim($this->post('appointment_type')));
        $appointmentNote = trim($this->post('appointment_note'));
        $appointmentDate = trim((string) $this->post('appointment_date'));

        if ($requestId <= 0) {
            set_flash('error', 'Admin appointment update failed: request and type are required.');
            redirect('admin/manage-requests');
        }

        $allowedTypes = ['payment', 'followup'];
        if (!in_array($appointmentType, $allowedTypes, true)) {
            set_flash('error', 'Invalid admin appointment type.');
            redirect('admin/manage-requests/' . $requestId);
        }

        $detailPath = 'admin/manage-requests/' . $requestId;
        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record was not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        if (in_array($request['status'], ['Completed', 'Rejected', 'Cancelled'], true)) {
            set_flash('error', 'Admin appointment can only be set while request is active.');
            redirect($detailPath);
        }

        if ($appointmentDate === '') {
            set_flash('error', 'Appointment date is required when notifying student.');
            redirect($detailPath);
        }

        $appointmentDateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
        $today = new DateTime('today');
        if (!$appointmentDateObj || $appointmentDateObj < $today) {
            set_flash('error', 'Appointment date cannot be in the past.');
            redirect($detailPath);
        }

        if ((int) $appointmentDateObj->format('N') > 5) {
            set_flash('error', 'Appointment date must be Monday to Friday only.');
            redirect($detailPath);
        }

        $paymentStatus = strtolower(trim((string) ($request['payment_status'] ?? '')));
        if ($appointmentType === 'payment' && $paymentStatus === 'paid') {
            set_flash('error', 'Payment appointment cannot be opened because payment is already marked as Paid.');
            redirect($detailPath);
        }

        $hasExistingTypeSchedule = $this->requestModel->hasAdminProcessAppointmentForRequestType($requestId, $appointmentType);
        if ($hasExistingTypeSchedule && $appointmentType !== 'payment') {
            set_flash('error', 'This appointment type already has a selected schedule and can no longer be re-opened.');
            redirect($detailPath);
        }

        $paymentMethod = strtolower(trim((string) ($request['payment_method'] ?? '')));
        $deadlineAt = '';
        if ($appointmentType === 'payment' && $paymentMethod === 'cash') {
            // Auto-cancel unpaid cash requests after the admin-provided payment date.
            $deadlineAt = $appointmentDate . ' 23:59:59';
        }

        $saved = $this->requestModel->setAdminProcessAppointmentAccessForRequest(
            $requestId,
            $appointmentType,
            true,
            $appointmentNote,
            $appointmentDate
        );

        if (!$saved) {
            set_flash('error', 'Failed to save admin appointment schedule.');
            redirect($detailPath);
        }

        if ($deadlineAt !== '') {
            $this->requestModel->upsertCashPaymentDeadline($requestId, $deadlineAt);
        }

        $typeLabel = $appointmentType === 'payment' ? 'Payment Appointment' : 'Follow-up Documents Appointment';

        $studentUserId = $this->resolveStudentUserIdFromRequest($request);
        if ($studentUserId > 0) {
            $message = 'You may now select your own time for ' . strtolower($typeLabel)
                . ' on request #' . $requestId . ' in your Appointments page for date ' . $appointmentDate . ' (8:00 AM to 5:00 PM only).';
            if ($appointmentNote !== '') {
                $message .= ' Note: ' . $appointmentNote;
            }
            if ($deadlineAt !== '') {
                $message .= ' For cash payments, unpaid requests are auto-cancelled after ' . $appointmentDate . '.';
            }
            $this->requestModel->createNotification($studentUserId, $message);
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $action = 'Enabled student self-scheduling for ' . $typeLabel . ' on request #' . $requestId;
            if ($appointmentNote !== '') {
                $action .= ' | Note: ' . $appointmentNote;
            }
            if ($deadlineAt !== '') {
                $action .= ' | Cash auto-cancel after date: ' . $appointmentDate;
            }
            $this->requestModel->logAdminAction($adminId, $action);
        }

        set_flash('success', $typeLabel . ' self-scheduling enabled. Student can now pick available time.');
        redirect($detailPath);
    }

    public function students(): void
    {
        $this->requireRoutineAdmin();
        $autoPurged = $this->adminModel->purgeRejectedStudentsOlderThan(self::REJECTED_AUTO_PURGE_DAYS);
        $departmentCode = $this->currentAdminDepartmentCode();
        $students = $this->adminModel->getStudents($departmentCode);

        if ($autoPurged > 0) {
            set_flash('success', 'Auto-cleanup removed ' . $autoPurged . ' rejected registration(s) older than ' . self::REJECTED_AUTO_PURGE_DAYS . ' days.');
        }

        $this->render('admin/students', [
            'active' => 'students',
            'students' => $students,
            'supportsStudentApproval' => $this->adminModel->supportsStudentApproval(),
            'supportsRejectionNotes' => $this->adminModel->supportsRejectionNotes(),
            'supportsRejectedAutoPurge' => $this->adminModel->supportsRejectedAutoPurge(),
            'rejectedAutoPurgeDays' => self::REJECTED_AUTO_PURGE_DAYS,
            'departmentCode' => $departmentCode,
        ]);
    }

    public function approveStudentRegistration(): void
    {
        $this->requireRoutineAdmin();
        $studentId = trim($this->post('student_id'));
        if ($studentId === '') {
            set_flash('error', 'Student approval failed: missing student id.');
            redirect('admin/students');
        }

        if (!$this->adminModel->supportsStudentApproval()) {
            set_flash('error', 'Student approval workflow is unavailable. Please add registration_status column first.');
            redirect('admin/students/' . $studentId);
        }

        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        $this->adminModel->updateStudentRegistrationStatus($studentId, 'Approved');

        $userId = $this->adminModel->getStudentUserId($studentId);
        if ($userId) {
            $this->requestModel->createNotification($userId, 'Your student registration is approved. You may now log in.');
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Approved student registration for ' . $studentId . '.');
        }

        set_flash('success', 'Student registration approved.');
        redirect('admin/students/' . $studentId);
    }

    public function rejectStudentRegistration(): void
    {
        $this->requireRoutineAdmin();
        $studentId = trim($this->post('student_id'));
        $rejectionNote = trim($this->post('rejection_note'));
        if ($studentId === '') {
            set_flash('error', 'Student rejection failed: missing student id.');
            redirect('admin/students');
        }

        if ($rejectionNote === '') {
            set_flash('error', 'Student rejection failed: rejection note is required.');
            redirect('admin/students/' . $studentId);
        }

        if (!$this->adminModel->supportsStudentApproval()) {
            set_flash('error', 'Student approval workflow is unavailable. Please add registration_status column first.');
            redirect('admin/students/' . $studentId);
        }

        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        $this->adminModel->updateStudentRegistrationStatus($studentId, 'Rejected', $rejectionNote);

        $userId = $this->adminModel->getStudentUserId($studentId);
        if ($userId) {
            $message = 'Your student registration was not approved. Note from registrar: ' . $rejectionNote
                . ' Please proceed to registrar office for face-to-face communication before re-registering.';
            $this->requestModel->createNotification($userId, $message);
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Rejected student registration for ' . $studentId . '. Note: ' . $rejectionNote);
        }

        set_flash('success', 'Student registration marked as rejected.');
        redirect('admin/students/' . $studentId);
    }

    public function bulkApproveStudentRegistration(): void
    {
        $this->requireRoutineAdmin();
        if (!$this->adminModel->supportsStudentApproval()) {
            set_flash('error', 'Student approval workflow is unavailable. Please add registration_status column first.');
            redirect('admin/students');
        }

        $idsCsv = trim((string) $this->post('student_ids_csv'));
        $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $idsCsv)))));
        if (empty($ids)) {
            set_flash('error', 'No students were selected for bulk approval.');
            redirect('admin/students');
        }

        $updated = 0;
        $skipped = 0;

        foreach ($ids as $studentId) {
            $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
            if (!$student) {
                $skipped++;
                continue;
            }

            $currentStatus = trim((string) ($student['registration_status'] ?? 'Approved'));
            if ($currentStatus !== 'Pending') {
                $skipped++;
                continue;
            }

            $ok = $this->adminModel->updateStudentRegistrationStatus($studentId, 'Approved');
            if (!$ok) {
                $skipped++;
                continue;
            }

            $userId = $this->adminModel->getStudentUserId($studentId);
            if ($userId) {
                $this->requestModel->createNotification($userId, 'Your student registration is approved. You may now log in.');
            }

            $updated++;
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Bulk approved student registrations. Updated: ' . $updated . ', Skipped: ' . $skipped . '.');
        }

        set_flash('success', 'Bulk approval completed. Updated: ' . $updated . ', Skipped: ' . $skipped . '.');
        redirect('admin/students');
    }

    public function bulkRejectStudentRegistration(): void
    {
        $this->requireRoutineAdmin();
        if (!$this->adminModel->supportsStudentApproval()) {
            set_flash('error', 'Student approval workflow is unavailable. Please add registration_status column first.');
            redirect('admin/students');
        }

        $rejectionNote = trim((string) $this->post('rejection_note'));
        if ($rejectionNote === '') {
            set_flash('error', 'Bulk rejection requires a rejection note.');
            redirect('admin/students');
        }

        $idsCsv = trim((string) $this->post('student_ids_csv'));
        $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $idsCsv)))));
        if (empty($ids)) {
            set_flash('error', 'No students were selected for bulk rejection.');
            redirect('admin/students');
        }

        $updated = 0;
        $skipped = 0;

        foreach ($ids as $studentId) {
            $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
            if (!$student) {
                $skipped++;
                continue;
            }

            $currentStatus = trim((string) ($student['registration_status'] ?? 'Approved'));
            if ($currentStatus !== 'Pending') {
                $skipped++;
                continue;
            }

            $ok = $this->adminModel->updateStudentRegistrationStatus($studentId, 'Rejected', $rejectionNote);
            if (!$ok) {
                $skipped++;
                continue;
            }

            $userId = $this->adminModel->getStudentUserId($studentId);
            if ($userId) {
                $message = 'Your student registration was not approved. Note from registrar: ' . $rejectionNote
                    . ' Please proceed to registrar office for face-to-face communication before re-registering.';
                $this->requestModel->createNotification($userId, $message);
            }

            $updated++;
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Bulk rejected student registrations. Updated: ' . $updated . ', Skipped: ' . $skipped . '. Note: ' . $rejectionNote);
        }

        set_flash('success', 'Bulk rejection completed. Updated: ' . $updated . ', Skipped: ' . $skipped . '.');
        redirect('admin/students');
    }

    public function deleteRejectedStudentPermanently(): void
    {
        $this->requireRoutineAdmin();
        $studentId = trim($this->post('student_id'));
        if ($studentId === '') {
            set_flash('error', 'Delete failed: missing student id.');
            redirect('admin/students');
        }

        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        if (($student['registration_status'] ?? 'Approved') !== 'Rejected') {
            set_flash('error', 'Permanent delete is only allowed for rejected registrations.');
            redirect('admin/students');
        }

        $deleted = $this->adminModel->permanentlyDeleteRejectedStudent($studentId);
        if (!$deleted) {
            set_flash('error', 'Permanent delete failed. This student may have existing requests, or the record is already removed.');
            redirect('admin/students');
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Permanently deleted rejected student registration for ' . $studentId . '.');
        }

        set_flash('success', 'Rejected student permanently deleted.');
        redirect('admin/students');
    }

    public function viewStudentDetail($studentId): void
    {
        $this->requireRoutineAdmin();
        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        $this->render('admin/student_detail', [
            'active' => 'students',
            'student' => $student,
            'courseOptions' => self::COURSE_OPTIONS,
            'yearLevelOptions' => self::YEAR_LEVEL_OPTIONS,
            'supportsStudentApproval' => $this->adminModel->supportsStudentApproval(),
            'supportsRejectionNotes' => $this->adminModel->supportsRejectionNotes(),
        ]);
    }

    public function updateStudentInfo(): void
    {
        $this->requireRoutineAdmin();
        $studentId = $this->post('student_id');
        $firstName = trim($this->post('first_name'));
        $lastName = trim($this->post('last_name'));
        $course = strtoupper(trim($this->post('course')));
        $yearLevel = $this->post('year_level');
        $section = strtoupper(trim($this->post('section')));
        $email = $this->post('email');
        $contactNumber = $this->post('contact_number');

        if ($studentId === '' || $firstName === '' || $lastName === '' || $course === '' || $yearLevel === '' || $email === '' || $contactNumber === '') {
            set_flash('error', 'Student update failed: required fields are missing.');
            redirect('admin/students');
        }

        if (!preg_match("/^[A-Za-z .'-]{2,60}$/", $firstName) || !preg_match("/^[A-Za-z .'-]{2,60}$/", $lastName)) {
            set_flash('error', 'Student update failed: invalid first name or last name format.');
            redirect('admin/students/' . $studentId);
        }

        if (!array_key_exists($course, self::COURSE_OPTIONS)) {
            set_flash('error', 'Student update failed: invalid course/program option.');
            redirect('admin/students/' . $studentId);
        }

        if (!in_array($yearLevel, self::YEAR_LEVEL_OPTIONS, true)) {
            set_flash('error', 'Student update failed: invalid year level option.');
            redirect('admin/students/' . $studentId);
        }

        if ($section !== '' && !preg_match('/^[1-4][A-Z][A-Z0-9]{1,4}$/', $section)) {
            set_flash('error', 'Student update failed: invalid section format. Use format like 2F4.');
            redirect('admin/students/' . $studentId);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Student update failed: invalid email format.');
            redirect('admin/students');
        }

        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        $this->adminModel->updateStudentInfo($studentId, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'course' => $course,
            'year_level' => $yearLevel,
            'section' => $section,
            'email' => $email,
            'contact_number' => $contactNumber,
        ]);

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Updated student profile for ' . $studentId . ' (name/course/year/section/contact).');
        }

        set_flash('success', 'Student profile updated successfully.');
        redirect('admin/students');
    }

    public function sendStudentWarning(): void
    {
        $this->requireRoutineAdmin();
        $studentId = $this->post('student_id');
        $message = $this->post('warning_message');
        $needsF2f = $this->post('needs_f2f') === '1';

        if ($studentId === '' || $message === '') {
            set_flash('error', 'Warning was not sent. Student and message are required.');
            redirect('admin/students');
        }

        $student = $this->adminModel->getStudentById($studentId, $this->currentAdminDepartmentCode());
        if (!$student) {
            set_flash('error', 'Student record not found or outside your assigned department.');
            redirect('admin/students');
        }

        $userId = $this->adminModel->getStudentUserId($studentId);
        if (!$userId) {
            set_flash('error', 'Warning not sent: student account is missing in users table.');
            redirect('admin/students');
        }

        $prefix = $needsF2f ? '[F2F REQUIRED] ' : '[NOTICE] ';
        $fullMessage = $prefix . $message;
        $this->requestModel->createNotification($userId, $fullMessage);

        $adminId = current_admin_id();
        if ($adminId) {
            $action = 'Sent warning to student ' . $studentId;
            if ($needsF2f) {
                $action .= ' (F2F required)';
            }
            $action .= ': ' . $message;
            $this->requestModel->logAdminAction($adminId, $action);
        }

        set_flash('success', 'Warning sent to student successfully.');
        redirect('admin/students');
    }

    public function documentTypes(): void
    {
        $this->requireRoutineAdmin();
        $requestTypes = $this->adminModel->getRequestTypes();

        $this->render('admin/document_types', [
            'active' => 'types',
            'requestTypes' => $requestTypes,
            'supportsAmount' => $this->adminModel->supportsRequestTypeAmount(),
        ]);
    }

    public function addDocumentType(): void
    {
        $this->requireRoutineAdmin();
        $name = trim($this->post('document_name'));
        $amountInput = trim($this->post('amount'));

        if ($name === '' || $amountInput === '') {
            set_flash('error', 'Document type name and amount are required.');
            redirect('admin/document-types');
        }

        if (!is_numeric($amountInput)) {
            set_flash('error', 'Amount must be a valid number.');
            redirect('admin/document-types');
        }

        $amount = (float) $amountInput;
        if ($amount < 0) {
            set_flash('error', 'Amount cannot be negative.');
            redirect('admin/document-types');
        }

        $amount = round($amount, 2);

        $this->adminModel->addRequestType($name, $amount);
        set_flash('success', 'Document type added.');
        redirect('admin/document-types');
    }

    public function updateDocumentType(): void
    {
        $this->requireRoutineAdmin();
        $id = (int) $this->post('id');
        $name = trim($this->post('document_name'));
        $amountInput = trim($this->post('amount'));

        if ($id <= 0 || $name === '' || $amountInput === '') {
            set_flash('error', 'Invalid document type update data.');
            redirect('admin/document-types');
        }

        if (!is_numeric($amountInput)) {
            set_flash('error', 'Amount must be a valid number.');
            redirect('admin/document-types');
        }

        $amount = (float) $amountInput;
        if ($amount < 0) {
            set_flash('error', 'Amount cannot be negative.');
            redirect('admin/document-types');
        }

        $amount = round($amount, 2);

        $this->adminModel->updateRequestType($id, $name, $amount);
        set_flash('success', 'Document type updated.');
        redirect('admin/document-types');
    }

    public function deleteDocumentType(): void
    {
        $this->requireRoutineAdmin();
        $id = (int) $this->post('id');
        if ($id <= 0) {
            set_flash('error', 'Invalid document type id.');
            redirect('admin/document-types');
        }

        $this->adminModel->deleteRequestType($id);
        set_flash('success', 'Document type deleted.');
        redirect('admin/document-types');
    }

    public function appointments(): void
    {
        $this->requireRoutineAdmin();
        $this->runCashPaymentAutoCancelSweep();

        $departmentCode = $this->currentAdminDepartmentCode();
        $appointments = $this->requestModel->getAllAppointments($departmentCode);
        $pendingAppointments = $this->requestModel->getPendingRequestsForAppointment($departmentCode);

        $this->render('admin/appointments', [
            'active' => 'appointments',
            'appointments' => $appointments,
            'pendingAppointments' => $pendingAppointments,
            'departmentCode' => $departmentCode,
        ]);
    }

    public function requestFiles(string $request_id): void
    {
        $this->requireRoutineAdmin();
        $requestId = (int) $request_id;
        if ($requestId <= 0) {
            set_flash('error', 'Invalid request reference.');
            redirect('admin/manage-requests');
        }

        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            set_flash('error', 'Request record not found or outside your assigned department.');
            redirect('admin/manage-requests');
        }

        $files = $this->requestModel->getFilesByRequestId($requestId, $this->currentAdminDepartmentCode());

        $this->render('admin/request_files', [
            'active' => 'requests',
            'request' => $request,
            'files' => $files,
        ]);
    }

    public function reports(): void
    {
        $isSuperAdmin = $this->isSuperAdmin();
        $departmentCode = $this->currentAdminDepartmentCode();
        $cacheKey = 'reports.' . ($departmentCode ?: 'superadmin');
        $reportData = $this->rememberAnalytics($cacheKey, 300, function () use ($departmentCode, $isSuperAdmin): array {
            return [
                'monthly' => $this->requestModel->getMonthlyReportData($departmentCode),
                'documents' => $this->requestModel->getDocumentStatsReportData($departmentCode),
                'students' => $this->requestModel->getStudentSummaryReportData($departmentCode),
                'departmentOverview' => $isSuperAdmin ? $this->requestModel->getDepartmentReportOverview() : [],
            ];
        }, [
            'monthly' => [],
            'documents' => [],
            'students' => [],
            'departmentOverview' => [],
        ]);

        $monthly = $reportData['monthly'] ?? [];
        $documents = $reportData['documents'] ?? [];
        $students = $reportData['students'] ?? [];
        $departmentOverview = $reportData['departmentOverview'] ?? [];

        $this->render('admin/reports', [
            'active' => 'reports',
            'monthly' => $monthly,
            'documents' => $documents,
            'students' => $students,
            'departmentOverview' => $departmentOverview,
            'courseOptions' => self::COURSE_OPTIONS,
            'isSuperAdmin' => $isSuperAdmin,
            'departmentCode' => $departmentCode,
        ]);
    }

    public function notifications(): void
    {
        $logs = $this->requestModel->getAdminNotifications();
        $latestLogId = !empty($logs) ? (int) ($logs[0]['log_id'] ?? 0) : 0;
        $_SESSION['admin_last_seen_log_id'] = $latestLogId;
        $_SESSION['admin_last_seen_logs_at'] = date('Y-m-d H:i:s');

        $this->render('admin/notifications', [
            'active' => 'notifications',
            'logs' => $logs,
        ]);
    }

    public function settings(): void
    {
        $this->render('admin/settings', [
            'active' => 'settings',
        ]);
    }

    public function sidebarCounts(): void
    {
        header('Content-Type: application/json');
        
        $admin = $_SESSION['auth']['admin'] ?? [];
        $adminRole = strtolower((string) ($admin['role'] ?? 'admin'));
        $adminDepartmentId = (int) ($admin['department_id'] ?? 0);
        $adminLastSeenLogId = (int) ($_SESSION['admin_last_seen_log_id'] ?? 0);
        
        $adminNewLogs = 0;
        $pendingRequestsCount = 0;
        $pendingAppointmentsCount = 0;
        $departmentCode = null;
        
        $cacheKey = 'sidebar-counts.' . $adminRole . '.' . ($adminDepartmentId > 0 ? (string) $adminDepartmentId : 'global') . '.' . $adminLastSeenLogId;
        $sidebarData = $this->rememberAnalytics($cacheKey, 120, function () use ($adminRole, $adminDepartmentId, $adminLastSeenLogId): array {
            $departmentCode = null;
            if ($adminRole !== 'superadmin' && $adminDepartmentId > 0) {
                $departmentRow = db()->table('departments')
                    ->select('code')
                    ->where('id', $adminDepartmentId)
                    ->limit(1)
                    ->get();
                $departmentCode = strtoupper(trim((string) ($departmentRow['code'] ?? '')));
                if ($departmentCode === '') {
                    $departmentCode = null;
                }
            }

            $row = db()->table('audit_logs')
                ->select_count('log_id', 'total_new')
                ->where('log_id', '>', $adminLastSeenLogId)
                ->get();
            $adminNewLogs = (int) ($row['total_new'] ?? 0);

            $pendingRequestsSql = "SELECT COUNT(*) AS total
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                WHERE dr.status IN ('Pending', 'Processing', 'Approved', 'Ready for Pickup')";
            $pendingRequestsParams = [];
            if ($departmentCode !== null) {
                $pendingRequestsSql .= " AND s.course = ?";
                $pendingRequestsParams[] = $departmentCode;
            }
            $pendingRequestsRow = db()->raw($pendingRequestsSql, $pendingRequestsParams)->fetch();
            $pendingRequestsCount = (int) ($pendingRequestsRow['total'] ?? 0);

            $pendingAppointmentsSql = "SELECT COUNT(*) AS total
                FROM document_requests dr
                INNER JOIN students s ON s.student_id = dr.student_id
                LEFT JOIN appointments a ON a.request_id = dr.request_id
                WHERE dr.status IN ('Pending', 'Processing', 'Approved', 'Ready for Pickup')
                  AND a.appointment_id IS NULL";
            $pendingAppointmentsParams = [];
            if ($departmentCode !== null) {
                $pendingAppointmentsSql .= " AND s.course = ?";
                $pendingAppointmentsParams[] = $departmentCode;
            }
            $pendingAppointmentsRow = db()->raw($pendingAppointmentsSql, $pendingAppointmentsParams)->fetch();
            $pendingAppointmentsCount = (int) ($pendingAppointmentsRow['total'] ?? 0);

            return [
                'adminNewLogs' => $adminNewLogs,
                'pendingRequestsCount' => $pendingRequestsCount,
                'pendingAppointmentsCount' => $pendingAppointmentsCount,
            ];
        }, [
            'adminNewLogs' => 0,
            'pendingRequestsCount' => 0,
            'pendingAppointmentsCount' => 0,
        ]);
        
        echo json_encode([
            'adminNewLogs' => $sidebarData['adminNewLogs'] ?? 0,
            'pendingRequestsCount' => $sidebarData['pendingRequestsCount'] ?? 0,
            'pendingAppointmentsCount' => $sidebarData['pendingAppointmentsCount'] ?? 0,
        ]);
        exit;
    }

    public function manageAdmins(): void
    {
        $this->requireSuperAdmin();

        $supportsAdminManagement = $this->adminModel->supportsAdminAccountManagement();
        $this->adminModel->syncDepartmentsFromCourses(self::COURSE_OPTIONS);
        $admins = $this->adminModel->getAllAdminAccounts();
        $departments = $this->adminModel->getDepartments();

        $this->render('admin/manage_admins', [
            'active' => 'admins',
            'admins' => $admins,
            'departments' => $departments,
            'supportsAdminManagement' => $supportsAdminManagement,
        ]);
    }

    public function viewAdminDetail(string $admin_id): void
    {
        $this->requireSuperAdmin();

        $accountId = (int) $admin_id;
        if ($accountId <= 0) {
            set_flash('error', 'Invalid admin account reference.');
            redirect('admin/manage-admins');
        }

        $account = $this->adminModel->getAdminAccountById($accountId);
        if (!$account) {
            set_flash('error', 'Admin account not found.');
            redirect('admin/manage-admins');
        }

        $this->adminModel->syncDepartmentsFromCourses(self::COURSE_OPTIONS);
        $departments = $this->adminModel->getDepartments();

        $this->render('admin/admin_detail', [
            'active' => 'admins',
            'account' => $account,
            'departments' => $departments,
            'supportsAdminManagement' => $this->adminModel->supportsAdminAccountManagement(),
        ]);
    }

    public function addAdminAccount(): void
    {
        $this->requireSuperAdmin();

        if (!$this->adminModel->supportsAdminAccountManagement()) {
            set_flash('error', 'Admin management columns are missing. Run the SQL migration first.');
            redirect('admin/manage-admins');
        }

        $name = trim($this->post('name'));
        $email = trim($this->post('email'));
        $username = trim($this->post('username'));
        $role = strtolower(trim($this->post('role', 'admin')));
        $departmentIdRaw = $this->post('department_id');
        $departmentId = $departmentIdRaw === '' ? null : (int) $departmentIdRaw;
        $isActive = $this->post('is_active') === '1' ? 1 : 0;
        $password = $this->post('password');

        if ($name === '' || $email === '' || $username === '' || $password === '') {
            set_flash('error', 'Name, email, username, and password are required.');
            redirect('admin/manage-admins');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Invalid email format.');
            redirect('admin/manage-admins');
        }

        if (!in_array($role, ['superadmin', 'admin'], true)) {
            set_flash('error', 'Invalid role selected.');
            redirect('admin/manage-admins');
        }

        if ($role === 'admin' && ($departmentId === null || $departmentId <= 0)) {
            set_flash('error', 'Department is required for admin accounts.');
            redirect('admin/manage-admins');
        }

        $this->adminModel->syncDepartmentsFromCourses(self::COURSE_OPTIONS);
        $departmentIds = array_map(static fn(array $d): int => (int) ($d['id'] ?? 0), $this->adminModel->getDepartments());
        if ($role === 'admin' && !in_array((int) $departmentId, $departmentIds, true)) {
            set_flash('error', 'Invalid department selected for admin account.');
            redirect('admin/manage-admins');
        }

        if ($role === 'superadmin') {
            $departmentId = null;
        }

        if (strlen($password) < 8) {
            set_flash('error', 'Password must be at least 8 characters.');
            redirect('admin/manage-admins');
        }

        if ($this->adminModel->usernameExists($username)) {
            set_flash('error', 'Username already exists. Please use another username.');
            redirect('admin/manage-admins');
        }

        if ($this->adminModel->emailExists($email)) {
            set_flash('error', 'Email already exists. Please use another email.');
            redirect('admin/manage-admins');
        }

        try {
            $newId = $this->adminModel->createAdminAccount([
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'department_id' => $departmentId,
                'is_active' => $isActive,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            if ($newId <= 0) {
                set_flash('error', 'Failed to create admin account.');
                redirect('admin/manage-admins');
            }
        } catch (Throwable $e) {
            set_flash('error', 'Failed to create admin account. Username or email may already exist.');
            redirect('admin/manage-admins');
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Created admin account: ' . $username . ' (' . $role . ').');
        }

        set_flash('success', 'Admin account created successfully.');
        redirect('admin/manage-admins');
    }

    public function updateAdminAccount(): void
    {
        $this->requireSuperAdmin();

        $redirectTo = trim((string) $this->post('redirect_to', 'admin/manage-admins'));
        if ($redirectTo === '') {
            $redirectTo = 'admin/manage-admins';
        }

        if (!$this->adminModel->supportsAdminAccountManagement()) {
            set_flash('error', 'Admin management columns are missing. Run the SQL migration first.');
            redirect($redirectTo);
        }

        $accountId = (int) $this->post('account_id');
        $name = trim($this->post('name'));
        $email = trim($this->post('email'));
        $username = trim($this->post('username'));
        $role = strtolower(trim($this->post('role', 'admin')));
        $departmentIdRaw = $this->post('department_id');
        $departmentId = $departmentIdRaw === '' ? null : (int) $departmentIdRaw;
        $isActive = $this->post('is_active') === '1' ? 1 : 0;

        if ($accountId <= 0 || $name === '' || $email === '' || $username === '') {
            set_flash('error', 'Account update failed: required fields are missing.');
            redirect($redirectTo);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Invalid email format.');
            redirect($redirectTo);
        }

        if (!in_array($role, ['superadmin', 'admin'], true)) {
            set_flash('error', 'Invalid role selected.');
            redirect($redirectTo);
        }

        if ($role === 'admin' && ($departmentId === null || $departmentId <= 0)) {
            set_flash('error', 'Department is required for admin accounts.');
            redirect($redirectTo);
        }

        $this->adminModel->syncDepartmentsFromCourses(self::COURSE_OPTIONS);
        $departmentIds = array_map(static fn(array $d): int => (int) ($d['id'] ?? 0), $this->adminModel->getDepartments());
        if ($role === 'admin' && !in_array((int) $departmentId, $departmentIds, true)) {
            set_flash('error', 'Invalid department selected for admin account.');
            redirect($redirectTo);
        }

        if ($role === 'superadmin') {
            $departmentId = null;
        }

        $currentAdminId = (int) ($_SESSION['auth']['admin']['admin_id'] ?? 0);
        if ($accountId === $currentAdminId && $isActive !== 1) {
            set_flash('error', 'You cannot deactivate your own account.');
            redirect($redirectTo);
        }

        if ($this->adminModel->usernameExists($username, $accountId)) {
            set_flash('error', 'Username already exists. Please use another username.');
            redirect($redirectTo);
        }

        if ($this->adminModel->emailExists($email, $accountId)) {
            set_flash('error', 'Email already exists. Please use another email.');
            redirect($redirectTo);
        }

        try {
            $updated = $this->adminModel->updateAdminAccount($accountId, [
                'name' => $name,
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'department_id' => $departmentId,
                'is_active' => $isActive,
            ]);

            if (!$updated) {
                set_flash('error', 'Admin account update failed.');
                redirect($redirectTo);
            }
        } catch (Throwable $e) {
            set_flash('error', 'Admin account update failed. Username or email may already exist.');
            redirect($redirectTo);
        }

        if ($accountId === $currentAdminId) {
            $_SESSION['auth']['admin']['name'] = $name;
            $_SESSION['auth']['admin']['email'] = $email;
            $_SESSION['auth']['admin']['username'] = $username;
            $_SESSION['auth']['admin']['role'] = $role;
            $_SESSION['auth']['admin']['department_id'] = $departmentId;
            $_SESSION['auth']['admin']['is_active'] = $isActive;
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Updated admin account #' . $accountId . ' (' . $username . ').');
        }

        set_flash('success', 'Admin account updated successfully.');
        redirect($redirectTo);
    }

    public function resetAdminAccountPassword(): void
    {
        $this->requireSuperAdmin();

        $redirectTo = trim((string) $this->post('redirect_to', 'admin/manage-admins'));
        if ($redirectTo === '') {
            $redirectTo = 'admin/manage-admins';
        }

        $accountId = (int) $this->post('account_id');
        $newPassword = $this->post('new_password');

        if ($accountId <= 0 || $newPassword === '') {
            set_flash('error', 'Password reset failed: required fields are missing.');
            redirect($redirectTo);
        }

        if (strlen($newPassword) < 8) {
            set_flash('error', 'New password must be at least 8 characters.');
            redirect($redirectTo);
        }

        $ok = $this->adminModel->resetAdminPassword($accountId, password_hash($newPassword, PASSWORD_DEFAULT));
        if (!$ok) {
            set_flash('error', 'Password reset failed.');
            redirect($redirectTo);
        }

        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Reset password for admin account #' . $accountId . '.');
        }

        set_flash('success', 'Admin password reset successfully.');
        redirect($redirectTo);
    }

    public function exportReportsPdf(): void
    {
        if (!class_exists('TCPDF')) {
            set_flash('error', 'TCPDF is not installed yet. Run composer require tecnickcom/tcpdf.');
            redirect('admin/reports');
        }

        $isSuperAdmin = $this->isSuperAdmin();
        $departmentCode = $this->currentAdminDepartmentCode();
        $monthly = $this->requestModel->getMonthlyReportData($departmentCode);
        $documents = $this->requestModel->getDocumentStatsReportData($departmentCode);
        $students = $this->requestModel->getStudentSummaryReportData($departmentCode);
        $departmentOverview = $isSuperAdmin ? $this->requestModel->getDepartmentReportOverview() : [];

        $pdf = new TCPDF();
        $pdf->SetCreator('MinSU e-Registrar');
        $pdf->SetAuthor('MinSU Registrar');
        $pdf->SetTitle('MinSU e-Registrar Reports');
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);

        $html = '<h2>MinSU e-Registrar Reports</h2>';
        if ($isSuperAdmin) {
            $html .= '<p><b>Scope:</b> Superadmin global analytics (all departments).</p>';
        } elseif ($departmentCode !== null && trim($departmentCode) !== '') {
            $html .= '<p><b>Scope:</b> Department ' . esc($departmentCode) . '.</p>';
        }

        if ($isSuperAdmin) {
            $html .= '<h4>Department Overview</h4><table border="1" cellpadding="4"><tr><th>Department</th><th>Total Students</th><th>Total Requests</th><th>Active</th><th>Completed</th><th>Rejected</th></tr>';
            foreach ($departmentOverview as $row) {
                $code = strtoupper(trim((string) ($row['department_code'] ?? '')));
                $label = self::COURSE_OPTIONS[$code] ?? $code;
                $html .= '<tr><td>' . esc($label) . '</td><td>' . esc((string) ($row['total_students'] ?? 0)) . '</td><td>' . esc((string) ($row['total_requests'] ?? 0)) . '</td><td>' . esc((string) ($row['active_requests'] ?? 0)) . '</td><td>' . esc((string) ($row['completed_requests'] ?? 0)) . '</td><td>' . esc((string) ($row['rejected_requests'] ?? 0)) . '</td></tr>';
            }
            if (empty($departmentOverview)) {
                $html .= '<tr><td colspan="6">No records found.</td></tr>';
            }
            $html .= '</table>';
        }

        $html .= '<h4>Monthly Requests</h4><table border="1" cellpadding="4"><tr><th>Month</th><th>Total</th></tr>';
        foreach ($monthly as $row) {
            $html .= '<tr><td>' . esc($row['month']) . '</td><td>' . esc((string) $row['total_requests']) . '</td></tr>';
        }
        if (empty($monthly)) {
            $html .= '<tr><td colspan="2">No records found.</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h4>Document Statistics</h4><table border="1" cellpadding="4"><tr><th>Document</th><th>Total</th></tr>';
        foreach ($documents as $row) {
            $html .= '<tr><td>' . esc($row['document_name']) . '</td><td>' . esc((string) $row['total']) . '</td></tr>';
        }
        if (empty($documents)) {
            $html .= '<tr><td colspan="2">No records found.</td></tr>';
        }
        $html .= '</table>';

        $html .= '<h4>Student Request Summary</h4><table border="1" cellpadding="4"><tr><th>Student ID</th><th>Name</th><th>Total</th></tr>';
        foreach ($students as $row) {
            $html .= '<tr><td>' . esc($row['student_id']) . '</td><td>' . esc($row['student_name']) . '</td><td>' . esc((string) $row['total_requests']) . '</td></tr>';
        }
        if (empty($students)) {
            $html .= '<tr><td colspan="3">No records found.</td></tr>';
        }
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('minsu-registrar-reports.pdf', 'I');
        exit;
    }

    public function quickStatusUpdate(): void
    {
        $this->requireRoutineAdmin(true);
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = (int) ($input['request_id'] ?? 0);
        $status = trim((string) ($input['status'] ?? ''));

        if ($requestId <= 0 || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $allowed = ['Pending', 'Processing', 'Approved', 'Ready for Pickup', 'Completed', 'Rejected', 'Cancelled'];
        if (!in_array($status, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $request = $this->requestModel->getRequestById($requestId, $this->currentAdminDepartmentCode());
        if (!$request) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Request not found or outside your department']);
            exit;
        }

        // Block changes to terminal/locked states
        $terminalStates = ['Completed', 'Rejected', 'Cancelled'];
        if (in_array($request['status'], $terminalStates, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This request is in a terminal state and cannot be modified']);
            exit;
        }

        // Block status changes if payment is locked as paid
        if (strtolower((string) ($request['payment_status'] ?? '')) === 'paid') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Payment is locked. Request cannot be modified']);
            exit;
        }

        $transitions = [
            'Pending' => ['Processing', 'Approved', 'Rejected', 'Cancelled'],
            'Processing' => ['Approved', 'Ready for Pickup', 'Rejected', 'Cancelled'],
            'Approved' => ['Ready for Pickup', 'Completed', 'Cancelled'],
            'Ready for Pickup' => ['Completed', 'Cancelled'],
            'Completed' => [],
            'Rejected' => [],
            'Cancelled' => [],
        ];

        $currentStatus = $request['status'];
        if (!in_array($status, $transitions[$currentStatus] ?? [], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status transition']);
            exit;
        }

        if (!$this->requestModel->updateRequestStatusByRequestId($requestId, $status)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Status update failed']);
            exit;
        }

        // Notify student if user_id exists
        if (!empty($request['user_id'])) {
            $message = 'Request #' . $requestId . ' status updated to ' . $status . '.';
            $this->requestModel->createNotification((int) $request['user_id'], $message);
        }

        // Log admin action
        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Quick status change: Request #' . $requestId . ' → ' . $status);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    }

    public function bulkStatusUpdate(): void
    {
        $this->requireRoutineAdmin(true);
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $requestIds = (array) ($input['request_ids'] ?? []);
        $status = trim((string) ($input['status'] ?? ''));

        if (empty($requestIds) || !$status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        $allowed = ['Pending', 'Processing', 'Approved', 'Ready for Pickup', 'Completed', 'Rejected', 'Cancelled'];
        if (!in_array($status, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        $departmentCode = $this->currentAdminDepartmentCode();
        $updated = 0;
        $failed = 0;
        $locked = 0;

        $terminalStates = ['Completed', 'Rejected', 'Cancelled'];

        foreach ($requestIds as $id) {
            $requestId = (int) $id;
            if ($requestId <= 0) {
                $failed++;
                continue;
            }

            $request = $this->requestModel->getRequestById($requestId, $departmentCode);
            if (!$request) {
                $failed++;
                continue;
            }

            // Skip if terminal/locked state
            if (in_array($request['status'], $terminalStates, true)) {
                $locked++;
                continue;
            }

            // Skip if payment is locked
            if (strtolower((string) ($request['payment_status'] ?? '')) === 'paid') {
                $locked++;
                continue;
            }

            $transitions = [
                'Pending' => ['Processing', 'Approved', 'Rejected', 'Cancelled'],
                'Processing' => ['Approved', 'Ready for Pickup', 'Rejected', 'Cancelled'],
                'Approved' => ['Ready for Pickup', 'Completed', 'Cancelled'],
                'Ready for Pickup' => ['Completed', 'Cancelled'],
                'Completed' => [],
                'Rejected' => [],
                'Cancelled' => [],
            ];

            $currentStatus = $request['status'];
            if (!in_array($status, $transitions[$currentStatus] ?? [], true)) {
                $failed++;
                continue;
            }

            if ($this->requestModel->updateRequestStatusByRequestId($requestId, $status)) {
                // Notify student
                if (!empty($request['user_id'])) {
                    $message = 'Request #' . $requestId . ' status updated to ' . $status . '.';
                    $this->requestModel->createNotification((int) $request['user_id'], $message);
                }
                $updated++;
            } else {
                $failed++;
            }
        }

        // Log admin action
        $adminId = current_admin_id();
        if ($adminId) {
            $this->requestModel->logAdminAction($adminId, 'Bulk status change: ' . $updated . ' request(s) → ' . $status . ' (Locked: ' . $locked . ', Failed: ' . $failed . ')');
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Updated ' . $updated . ' request(s). Locked: ' . $locked . ', Failed: ' . $failed,
            'updated' => $updated,
            'locked' => $locked,
            'failed' => $failed
        ]);
        exit;
    }
}
