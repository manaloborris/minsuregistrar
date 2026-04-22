<?php

require_once APP_ROOT . '/app/controllers/BaseController.php';
require_once APP_ROOT . '/app/models/StudentModel.php';
require_once APP_ROOT . '/app/models/RequestModel.php';

class StudentController extends BaseController
{
    private const PAYMENT_METHOD_OPTIONS = ['Cash', 'GCash'];

    private StudentModel $studentModel;
    private RequestModel $requestModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->requestModel = new RequestModel();
    }

    public function dashboard(): void
    {
        $studentId = current_student_id();
        $stats = $this->requestModel->getStudentStats($studentId);
        $recentActivity = $this->requestModel->getStudentRecentActivity($studentId);

        $this->render('student/dashboard', [
            'active' => 'dashboard',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function requestForm(): void
    {
        $requestTypes = $this->requestModel->getRequestTypes();

        $this->render('student/request_document', [
            'active' => 'request',
            'requestTypes' => $requestTypes,
        ]);
    }

    public function submitRequest(): void
    {
        $studentId = current_student_id();
        $requestTypeId = (int) $this->post('request_type_id');
        $purpose = $this->post('purpose');
        $quantity = (int) $this->post('quantity', '1');
        $paymentMethod = trim($this->post('payment_method'));
        $referenceNumber = trim($this->post('reference_number'));

        if ($requestTypeId <= 0 || $purpose === '' || $paymentMethod === '' || $quantity <= 0 || $quantity > 10) {
            set_flash('error', 'Document type, purpose, payment method, and valid quantity (1-10) are required.');
            redirect('student/request-document');
        }

        if (!in_array($paymentMethod, self::PAYMENT_METHOD_OPTIONS, true)) {
            set_flash('error', 'Invalid payment method. Please choose Cash or GCash.');
            redirect('student/request-document');
        }

        if ($paymentMethod === 'GCash' && $referenceNumber === '') {
            set_flash('error', 'Reference number is required for GCash payments.');
            redirect('student/request-document');
        }

        if ($paymentMethod === 'Cash') {
            $referenceNumber = null;
        }

        $requestType = $this->requestModel->getRequestTypeById($requestTypeId);
        if (!$requestType) {
            set_flash('error', 'Selected document type was not found. Please choose another type.');
            redirect('student/request-document');
        }

        $requestAmount = (float) ($requestType['amount'] ?? 0);
        if ($requestAmount < 0) {
            $requestAmount = 0;
        }

        $requestId = $this->requestModel->createRequest(
            $studentId,
            $requestTypeId,
            $purpose,
            date('Y-m-d H:i:s'),
            'Pending',
            $quantity
        );

        $this->requestModel->createPaymentRecord($requestId, $requestAmount, $paymentMethod, $referenceNumber);

        if (!empty($_FILES['requirement_file']) && ($_FILES['requirement_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $path = upload_public_file($_FILES['requirement_file'], 'uploads/requirements');
            if ($path !== null) {
                $this->requestModel->attachRequirementFile($requestId, $path, date('Y-m-d H:i:s'));
            }
        }

        $userId = $_SESSION['auth']['student']['user_id'] ?? null;
        if ($userId) {
            $this->requestModel->createNotification(
                (int) $userId,
                'Your request #' . $requestId . ' was submitted and is now Pending.'
            );
        }

        set_flash('success', 'Document request submitted successfully.');
        redirect('student/track-requests');
    }

    public function trackRequests(): void
    {
        $studentId = current_student_id();
        $requests = $this->requestModel->getStudentRequests($studentId);

        $this->render('student/track_requests', [
            'active' => 'track',
            'requests' => $requests,
        ]);
    }

    public function submitFollowUp(string $request_id): void
    {
        $studentId = current_student_id();
        $requestId = (int) $request_id;
        $followupMessage = $this->post('followup_message');

        if ($requestId <= 0) {
            set_flash('error', 'Invalid request reference for follow-up.');
            redirect('student/track-requests');
        }

        $request = $this->requestModel->getStudentRequestById($requestId, $studentId);
        if (!$request) {
            set_flash('error', 'Request not found or does not belong to your account.');
            redirect('student/track-requests');
        }

        if (in_array($request['status'], ['Completed'], true)) {
            set_flash('error', 'This request is already completed and cannot accept follow-up submissions.');
            redirect('student/track-requests');
        }

        $hasUpload = !empty($_FILES['followup_file']) && (($_FILES['followup_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);
        if (!$hasUpload && $followupMessage === '') {
            set_flash('error', 'Add a follow-up message or upload a file before submitting.');
            redirect('student/track-requests');
        }

        if ($hasUpload) {
            $path = upload_public_file($_FILES['followup_file'], 'uploads/followups');
            if ($path === null) {
                set_flash('error', 'Follow-up file upload failed. Allowed: pdf, jpg, jpeg, png, doc, docx.');
                redirect('student/track-requests');
            }

            $this->requestModel->attachRequirementFile($requestId, $path, date('Y-m-d H:i:s'));
        }

        $userId = (int) ($_SESSION['auth']['student']['user_id'] ?? 0);
        if ($userId > 0) {
            $message = 'Follow-up submitted for request #' . $requestId . '.';
            if ($followupMessage !== '') {
                $message .= ' Student note: ' . $followupMessage;
            }
            if ($hasUpload) {
                $message .= ' A follow-up file was uploaded.';
            }

            $this->requestModel->createNotification($userId, $message);
        }

        set_flash('success', 'Follow-up sent successfully. Please wait for admin review.');
        redirect('student/track-requests');
    }

    public function appointments(): void
    {
        $this->requestModel->autoCancelOverdueCashRequests();

        $studentId = current_student_id();
        $appointments = $this->requestModel->getStudentAppointments($studentId);
        $selectableSlots = $this->requestModel->getStudentOpenProcessAppointmentAccess($studentId);
        $appointmentTimeSlots = $this->requestModel->getAppointmentTimeSlots();
        $occupiedAppointmentTimesByDate = $this->requestModel->getBookedAppointmentTimesMap();

        $this->render('student/appointments', [
            'active' => 'appointments',
            'appointments' => $appointments,
            'selectableSlots' => $selectableSlots,
            'appointmentTimeSlots' => $appointmentTimeSlots,
            'occupiedAppointmentTimesByDate' => $occupiedAppointmentTimesByDate,
        ]);
    }

    public function selectProcessAppointmentSlot(): void
    {
        $this->requestModel->autoCancelOverdueCashRequests();

        $studentId = current_student_id();
        $requestId = (int) $this->post('request_id');
        $appointmentType = strtolower(trim($this->post('appointment_type')));
        $appointmentDate = trim($this->post('appointment_date'));
        $appointmentTime = trim($this->post('appointment_time'));

        if ($requestId <= 0 || $appointmentDate === '' || $appointmentTime === '') {
            set_flash('error', 'Please provide date and time for your selected appointment type.');
            redirect('student/appointments');
        }

        if (!in_array($appointmentType, ['payment', 'followup'], true)) {
            set_flash('error', 'Invalid appointment type selected.');
            redirect('student/appointments');
        }

        $scheduleDateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
        $today = new DateTime('today');
        if (!$scheduleDateObj || $scheduleDateObj < $today) {
            set_flash('error', 'Appointment date cannot be in the past.');
            redirect('student/appointments');
        }

        $dayOfWeek = (int) $scheduleDateObj->format('N');
        if ($dayOfWeek > 5) {
            set_flash('error', 'Appointments are only available from Monday to Friday.');
            redirect('student/appointments');
        }

        $appointmentTime = $this->normalizeQuarterHourTime($appointmentTime);
        if ($appointmentTime === null) {
            set_flash('error', 'Appointment time must be in 15-minute intervals (:00, :15, :30, or :45).');
            redirect('student/appointments');
        }

        if ($appointmentTime < '08:00:00' || $appointmentTime > '17:00:00') {
            set_flash('error', 'Appointment time must be within office hours (8:00 AM to 5:00 PM).');
            redirect('student/appointments');
        }

        $selected = $this->requestModel->studentScheduleProcessAppointment(
            $studentId,
            $requestId,
            $appointmentType,
            $appointmentDate,
            $appointmentTime
        );
        if (!$selected) {
            set_flash('error', 'Schedule could not be saved. Make sure admin has enabled this appointment type and slot is still available.');
            redirect('student/appointments');
        }

        $typeLabel = ($appointmentType === 'payment')
            ? 'Payment Appointment'
            : 'Follow-up Documents Appointment';

        $studentUserId = (int) ($_SESSION['auth']['student']['user_id'] ?? 0);
        if ($studentUserId > 0) {
            $timeLabel = date('g:i A', strtotime($appointmentTime));
            $this->requestModel->createNotification(
                $studentUserId,
                $typeLabel . ' confirmed for request #' . $requestId . ': ' . $appointmentDate . ' ' . $timeLabel . '.'
            );
        }

        set_flash('success', $typeLabel . ' scheduled successfully for request #' . $requestId . '.');
        redirect('student/appointments');
    }

    public function notifications(): void
    {
        $userId = (int) ($_SESSION['auth']['student']['user_id'] ?? 0);
        $notifications = $this->requestModel->getNotificationsByUser($userId);
        if ($userId > 0) {
            $this->requestModel->markNotificationsAsRead($userId);
        }

        $this->render('student/notifications', [
            'active' => 'notifications',
            'notifications' => $notifications,
        ]);
    }

    public function profile(): void
    {
        $studentId = current_student_id();
        $profile = $this->studentModel->getProfile($studentId);

        if ($profile) {
            $_SESSION['auth']['student']['profile_photo'] = (string) ($profile['profile_photo'] ?? '');
        }

        $this->render('student/profile', [
            'active' => 'profile',
            'profile' => $profile,
            'supportsProfilePhoto' => $this->studentModel->supportsProfilePhoto(),
        ]);
    }

    public function settings(): void
    {
        $this->render('student/settings', [
            'active' => 'settings',
        ]);
    }

    public function updateProfile(): void
    {
        $studentId = current_student_id();
        $email = $this->post('email');
        $contact = $this->post('contact_number');

        if ($email === '' || $contact === '') {
            set_flash('error', 'Email and contact number are required.');
            redirect('student/profile');
        }

        $this->studentModel->updateContact($studentId, $email, $contact);

        $hasProfileUpload = !empty($_FILES['profile_photo'])
            && (($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK);

        if ($hasProfileUpload) {
            if (!$this->studentModel->supportsProfilePhoto()) {
                set_flash('error', 'Profile photo feature is not available yet. Ask admin to run the database update.');
                redirect('student/profile');
            }

            $oldPhotoPath = $this->studentModel->getProfilePhotoPath($studentId) ?? '';
            $newPhotoPath = upload_public_image($_FILES['profile_photo'], 'uploads/profile-photos');

            if ($newPhotoPath === null) {
                set_flash('error', 'Profile photo upload failed. Allowed: jpg, jpeg, png, webp (max 2MB).');
                redirect('student/profile');
            }

            $saved = $this->studentModel->updateProfilePhoto($studentId, $newPhotoPath);
            if (!$saved) {
                set_flash('error', 'Could not save profile photo. Please try again.');
                redirect('student/profile');
            }

            $_SESSION['auth']['student']['profile_photo'] = $newPhotoPath;

            $oldPhotoPath = trim($oldPhotoPath);
            if ($oldPhotoPath !== '') {
                $oldAbsolute = APP_ROOT . '/public/' . ltrim($oldPhotoPath, '/');
                if (is_file($oldAbsolute)) {
                    @unlink($oldAbsolute);
                }
            }
        }

        $_SESSION['auth']['student']['email'] = $email;
        $_SESSION['auth']['student']['contact_number'] = $contact;

        set_flash('success', 'Profile updated successfully.');
        redirect('student/profile');
    }
}
