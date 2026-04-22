<?php

require_once APP_ROOT . '/app/controllers/BaseController.php';
require_once APP_ROOT . '/app/models/AuthModel.php';
require_once APP_ROOT . '/app/services/EmailService.php';

class AuthController extends BaseController
{
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

    private AuthModel $authModel;
    private EmailService $emailService;

    private const FORGOT_CODE_LENGTH = 6;
    private const REGISTER_CODE_LENGTH = 6;

    private function sanitizeInput(string $value): string
    {
        $value = trim($value);
        // Strip control characters to avoid invisible/binary input issues.
        return (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }

    private function normalizeStudentId(string $value): string
    {
        return strtoupper($this->sanitizeInput($value));
    }

    private function validateStrongPassword(string $password): ?string
    {
        if (strlen($password) < 10) {
            return 'Password must be at least 10 characters long.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least one uppercase letter.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least one lowercase letter.';
        }

        if (!preg_match('/\d/', $password)) {
            return 'Password must contain at least one number.';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password must contain at least one special character.';
        }

        return null;
    }

    private function forgotPasswordState(): array
    {
        $state = $_SESSION['auth']['forgot_password'] ?? null;
        if (!is_array($state)) {
            return [];
        }

        $expiresAt = (int) ($state['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            unset($_SESSION['auth']['forgot_password']);
            return [];
        }

        return $state;
    }

    private function issueForgotPasswordCode(string $studentId, string $email): string
    {
        $min = (int) pow(10, self::FORGOT_CODE_LENGTH - 1);
        $max = (int) pow(10, self::FORGOT_CODE_LENGTH) - 1;
        $code = (string) random_int($min, $max);

        $_SESSION['auth']['forgot_password'] = [
            'student_id' => $studentId,
            'email' => $email,
            'code_hash' => hash('sha256', $code),
            'expires_at' => time() + (FORGOT_CODE_EXPIRY_MINUTES * 60),
        ];

        return $code;
    }

    private function registrationEmailState(): array
    {
        $state = $_SESSION['auth']['register_email_verify'] ?? null;
        if (!is_array($state)) {
            return [];
        }

        $expiresAt = (int) ($state['expires_at'] ?? 0);
        if ($expiresAt <= time()) {
            unset($_SESSION['auth']['register_otp']);
            return [];
        }

        return $state;
    }

    private function clearRegistrationEmailState(): void
    {
        unset($_SESSION['auth']['register_email_verify']);
    }

    private function issueRegistrationEmailCode(string $email): string
    {
        $min = (int) pow(10, self::REGISTER_CODE_LENGTH - 1);
        $max = (int) pow(10, self::REGISTER_CODE_LENGTH) - 1;
        $code = (string) random_int($min, $max);

        $_SESSION['auth']['register_email_verify'] = [
            'email' => $email,
            'verified' => false,
            'code_hash' => hash('sha256', $code),
            'expires_at' => time() + (REGISTER_OTP_EXPIRY_MINUTES * 60),
        ];

        return $code;
    }

    private function markRegistrationEmailVerified(): void
    {
        $state = $this->registrationEmailState();
        if (empty($state)) {
            return;
        }

        $state['verified'] = true;
        unset($state['code_hash']);
        $_SESSION['auth']['register_email_verify'] = $state;
    }

    private function sendRegistrationOtpEmail(string $email, string $code): bool
    {
        $subject = 'MinSU Registration Verification Code';
        $body = "Verification code: {$code}\n"
            . "Code expiry: " . REGISTER_OTP_EXPIRY_MINUTES . " minute(s)\n\n"
            . "If this request was not made by you, ignore this message.";

        return $this->emailService->send($email, $subject, $body);
    }

    private function sendForgotPasswordOtpEmail(string $email, string $studentId, string $code): bool
    {
        $subject = 'MinSU Forgot Password Verification Code';
        $body = "Student ID: {$studentId}\n"
            . "Verification code: {$code}\n"
            . "Code expiry: " . FORGOT_CODE_EXPIRY_MINUTES . " minute(s)\n\n"
            . "If this request was not made by you, ignore this message.";

        return $this->emailService->send($email, $subject, $body);
    }

    private function checkScopedRateLimit(string $identifier, int $maxAttempts, int $windowMinutes): bool
    {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        $file = $this->getLoginAttemptFile($identifier);
        $now = time();
        $window = $windowMinutes * 60;

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && is_array($data)) {
                $attempts = (int) ($data['attempts'] ?? 0);
                $firstAttempt = (int) ($data['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    return $attempts < $maxAttempts;
                }
            }
        }

        return true;
    }

    private function recordScopedRateLimitAttempt(string $identifier, int $windowMinutes): void
    {
        if (!RATE_LIMIT_ENABLED) {
            return;
        }

        $file = $this->getLoginAttemptFile($identifier);
        $now = time();
        $window = $windowMinutes * 60;

        $data = ['attempts' => 1, 'first_attempt' => $now];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && is_array($existing)) {
                $attempts = (int) ($existing['attempts'] ?? 0);
                $firstAttempt = (int) ($existing['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    $data['attempts'] = $attempts + 1;
                    $data['first_attempt'] = $firstAttempt;
                }
            }
        }

        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    private function clearScopedRateLimit(string $identifier): void
    {
        $this->clearLoginAttempts($identifier);
    }

    public function __construct()
    {
        $this->authModel = new AuthModel();
        $this->emailService = new EmailService();
    }

    public function home(): void
    {
        $this->render('homepage');
    }

    public function about(): void
    {
        $this->render('about');
    }

    public function showStudentLogin(): void
    {
        if (!empty($_GET['fresh'])) {
            unset($_SESSION['auth']['admin'], $_SESSION['auth']['student']);
            unset($_SESSION['ui']['skip_initial_loading'], $_SESSION['ui']['admin_sidebar_force_closed'], $_SESSION['ui']['student_sidebar_force_closed']);
        }

        $this->render('auth/student_login');
    }

    public function showStudentRegister(): void
    {
        if (!empty($_GET['restart_otp']) || !empty($_GET['restart_email'])) {
            $this->clearRegistrationEmailState();
        }

        $emailState = $this->registrationEmailState();
        $this->render('auth/student_register', [
            'courseOptions' => self::COURSE_OPTIONS,
            'yearLevelOptions' => self::YEAR_LEVEL_OPTIONS,
            'registrationOtpEnabled' => (bool) REGISTER_EMAIL_OTP_ENABLED,
            'pendingRegisterOtp' => !empty($emailState),
            'pendingRegisterEmail' => $emailState['email'] ?? '',
            'registerEmailVerified' => (bool) ($emailState['verified'] ?? false),
            'registerOtpExpiryMinutes' => (int) REGISTER_OTP_EXPIRY_MINUTES,
        ]);
    }

    public function showStudentForgotPassword(): void
    {
        if (!empty($_GET['fresh'])) {
            unset($_SESSION['auth']['forgot_password']);
        }

        $state = $this->forgotPasswordState();

        $this->render('auth/student_forgot_password', [
            'pendingReset' => !empty($state),
            'pendingStudentId' => $state['student_id'] ?? '',
            'codeExpiryMinutes' => (int) FORGOT_CODE_EXPIRY_MINUTES,
        ]);
    }

    public function studentRegister(): void
    {
        $step = trim((string) $this->post('step', 'register'));

        if (REGISTER_EMAIL_OTP_ENABLED) {
            if ($step === 'request_email_code') {
                $email = $this->sanitizeInput($this->post('email'));
                $identifier = 'register-email:' . strtolower($email);

                if (!$this->checkScopedRateLimit($identifier, (int) REGISTER_OTP_MAX_ATTEMPTS, (int) REGISTER_OTP_WINDOW_MINUTES)) {
                    set_flash('error', 'Too many email verification requests. Please try again in ' . REGISTER_OTP_WINDOW_MINUTES . ' minutes.');
                    redirect('student/register');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->recordScopedRateLimitAttempt($identifier, (int) REGISTER_OTP_WINDOW_MINUTES);
                    set_flash('error', 'Please provide a valid email address.');
                    redirect('student/register');
                }

                $code = $this->issueRegistrationEmailCode($email);
                $sent = $this->sendRegistrationOtpEmail($email, $code);
                if (!$sent) {
                    $this->recordScopedRateLimitAttempt($identifier, (int) REGISTER_OTP_WINDOW_MINUTES);
                    set_flash('error', 'Failed to send verification code. Check SMTP settings and try again.');
                    redirect('student/register');
                }

                $this->clearScopedRateLimit($identifier);
                set_flash('success', 'Verification code sent to your email. Enter the 6-digit code to proceed.');
                redirect('student/register');
            }

            if ($step === 'verify_email_code') {
                $emailState = $this->registrationEmailState();
                if (empty($emailState)) {
                    set_flash('error', 'Email verification session expired. Request a new code.');
                    redirect('student/register');
                }

                $email = strtolower((string) ($emailState['email'] ?? ''));
                $identifier = 'register-email-code:' . $email;
                if (!$this->checkScopedRateLimit($identifier, (int) REGISTER_OTP_MAX_ATTEMPTS, (int) REGISTER_OTP_WINDOW_MINUTES)) {
                    set_flash('error', 'Too many code verification attempts. Please request a new code.');
                    redirect('student/register?restart_email=1');
                }

                $otpCode = $this->sanitizeInput($this->post('verification_code'));
                if (!preg_match('/^\d{6}$/', $otpCode)) {
                    $this->recordScopedRateLimitAttempt($identifier, (int) REGISTER_OTP_WINDOW_MINUTES);
                    set_flash('error', 'Please enter a valid 6-digit verification code.');
                    redirect('student/register');
                }

                $expectedHash = (string) ($emailState['code_hash'] ?? '');
                $actualHash = hash('sha256', $otpCode);
                if ($expectedHash === '' || !hash_equals($expectedHash, $actualHash)) {
                    $this->recordScopedRateLimitAttempt($identifier, (int) REGISTER_OTP_WINDOW_MINUTES);
                    set_flash('error', 'Invalid verification code.');
                    redirect('student/register');
                }

                $this->markRegistrationEmailVerified();
                $this->clearScopedRateLimit($identifier);
                set_flash('success', 'Email verified. You can now continue with registration details.');
                redirect('student/register');
            }
        }

        $registerIdentifier = 'register:ip:' . $this->getClientIp();
        if (!$this->checkScopedRateLimit($registerIdentifier, (int) REGISTER_RATE_LIMIT_MAX_ATTEMPTS, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES)) {
            set_flash('error', 'Too many registration attempts. Please try again in ' . REGISTER_RATE_LIMIT_WINDOW_MINUTES . ' minutes.');
            redirect('student/register');
        }

        $studentId = $this->normalizeStudentId($this->post('student_id'));
        $firstName = $this->sanitizeInput($this->post('first_name'));
        $lastName = $this->sanitizeInput($this->post('last_name'));
        $course = strtoupper($this->sanitizeInput($this->post('course')));
        $yearLevel = $this->sanitizeInput($this->post('year_level'));
        $section = strtoupper($this->sanitizeInput($this->post('section')));
        $email = $this->sanitizeInput($this->post('email'));
        $contactNumber = $this->sanitizeInput($this->post('contact_number'));
        $password = $this->post('password');
        $confirmPassword = $this->post('password_confirmation');

        if (REGISTER_EMAIL_OTP_ENABLED) {
            $emailState = $this->registrationEmailState();
            if (empty($emailState) || !(bool) ($emailState['verified'] ?? false)) {
                $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
                set_flash('error', 'Verify your email first before entering registration details.');
                redirect('student/register');
            }

            $email = trim((string) ($emailState['email'] ?? ''));
            if ($email === '') {
                $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
                set_flash('error', 'Verified email session is invalid. Please verify email again.');
                redirect('student/register?restart_email=1');
            }
        }

        if ($studentId === '' || $firstName === '' || $lastName === '' || $course === '' || $yearLevel === '' || $section === '' || $email === '' || $contactNumber === '' || $password === '') {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'All registration fields are required.');
            redirect('student/register');
        }

        if (!array_key_exists($course, self::COURSE_OPTIONS)) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Please select a valid course/program option.');
            redirect('student/register');
        }

        if (!in_array($yearLevel, self::YEAR_LEVEL_OPTIONS, true)) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Please select a valid year level (1st to 4th Year).');
            redirect('student/register');
        }

        if (!preg_match('/^[1-4][A-Z][A-Z0-9]{1,4}$/', $section)) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Invalid section format. Use format similar to 2F4.');
            redirect('student/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Please provide a valid active email address. If possible, use your MinSU email for notifications.');
            redirect('student/register');
        }

        $passwordIssue = $this->validateStrongPassword($password);
        if ($passwordIssue !== null) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', $passwordIssue);
            redirect('student/register');
        }

        if ($password !== $confirmPassword) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Password confirmation does not match.');
            redirect('student/register');
        }

        if ($this->authModel->studentExists($studentId)) {
            $this->recordScopedRateLimitAttempt($registerIdentifier, (int) REGISTER_RATE_LIMIT_WINDOW_MINUTES);
            set_flash('error', 'Student ID already exists. Please use a different Student ID.');
            redirect('student/register');
        }

        $registrationPayload = [
            'student_id' => $studentId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'course' => $course,
            'year_level' => $yearLevel,
            'section' => $section,
            'email' => $email,
            'contact_number' => $contactNumber,
        ];

        $this->authModel->registerStudent([
            'student_id' => $registrationPayload['student_id'],
            'first_name' => $registrationPayload['first_name'],
            'last_name' => $registrationPayload['last_name'],
            'course' => $registrationPayload['course'],
            'year_level' => $registrationPayload['year_level'],
            'section' => $registrationPayload['section'],
            'email' => $registrationPayload['email'],
            'contact_number' => $registrationPayload['contact_number'],
            'password' => $password,
        ]);

        if ($this->authModel->supportsStudentApproval()) {
            set_flash('success', 'Registration submitted successfully. Please wait for admin approval before logging in.');
        } else {
            set_flash('success', 'Registration successful. You may now log in.');
        }
        if (REGISTER_EMAIL_OTP_ENABLED) {
            $this->clearRegistrationEmailState();
        }
        $this->clearScopedRateLimit($registerIdentifier);
        redirect('login');
    }

    public function studentLogin(): void
    {
        $loginId = $this->sanitizeInput($this->post('login_id'));
        if ($loginId === '') {
            // Backward compatibility for existing forms still using student_id.
            $loginId = $this->sanitizeInput($this->post('student_id'));
        }

        $password = $this->post('password');

        if ($loginId === '' || $password === '') {
            set_flash('error', 'Login ID and password are required.');
            redirect('login');
        }

        $studentId = $this->normalizeStudentId($loginId);

        // Check rate limit before attempting login
        if (!$this->checkLoginRateLimit('student:' . $studentId)) {
            set_flash('error', 'Too many failed login attempts. Please try again in ' . RATE_LIMIT_WINDOW_MINUTES . ' minutes.');
            redirect('login');
        }

        $student = $this->authModel->loginStudent($studentId, $password);

        if ($student) {
            $registrationStatus = trim((string) ($student['registration_status'] ?? 'Approved'));
            if ($registrationStatus !== 'Approved') {
                if ($registrationStatus === 'Pending') {
                    set_flash('error', 'Your account is pending admin approval. Please wait for confirmation.');
                } elseif ($registrationStatus === 'Rejected') {
                    $rejectionNote = trim((string) ($student['rejection_note'] ?? ''));
                    $message = 'Your registration was not approved.';
                    if ($rejectionNote !== '') {
                        $message .= ' Registrar note: ' . $rejectionNote;
                    }
                    $message .= ' Please proceed to registrar office for face-to-face communication before re-registering.';
                    set_flash('error', $message);
                } else {
                    set_flash('error', 'Your account is not yet approved for login.');
                }
                redirect('login');
            }

            $_SESSION['auth']['student'] = [
                'user_id' => (int) $student['user_id'],
                'student_id' => $student['student_id'],
                'name' => $student['first_name'] . ' ' . $student['last_name'],
                'course' => $student['course'],
                'year_level' => $student['year_level'],
                'section' => $student['section'] ?? '',
                'profile_photo' => $student['profile_photo'] ?? '',
                'email' => $student['email'] ?? '',
                'contact_number' => $student['contact_number'] ?? '',
            ];

            $_SESSION['ui']['student_sidebar_force_closed'] = true;
            session_regenerate_id(true);
            $this->clearLoginAttempts('student:' . $studentId);

            set_flash('success', 'Welcome to MinSU e-Registrar.');
            redirect('student/dashboard');
        }

        // Failed login attempt, record it
        $this->recordLoginAttempt('student:' . $studentId);

        $admin = $this->authModel->loginAdmin($loginId, $password);
        if (!$admin) {
            set_flash('error', 'Invalid login credentials.');
            redirect('login');
        }

        $_SESSION['auth']['admin'] = [
            'admin_id' => (int) ($admin['admin_id'] ?? 0),
            'name' => $admin['name'] ?? 'Administrator',
            'email' => $admin['email'] ?? '',
            'username' => $admin['username'] ?? $loginId,
            'role' => $admin['role'] ?? 'admin',
            'department_id' => $admin['department_id'] ?? null,
            'is_active' => (int) ($admin['is_active'] ?? 1),
        ];

        $_SESSION['ui']['admin_sidebar_force_open'] = true;
        session_regenerate_id(true);
        $this->clearLoginAttempts('admin:' . $loginId);

        $role = strtolower((string) ($_SESSION['auth']['admin']['role'] ?? 'admin'));
        $departmentId = $_SESSION['auth']['admin']['department_id'] ?? null;
        if ($role === 'admin' && (empty($departmentId) || (int) $departmentId <= 0)) {
            unset($_SESSION['auth']['admin']);
            set_flash('error', 'Your admin account has no assigned department. Please contact superadmin.');
            redirect('login');
        }

        $welcomeRole = $role === 'superadmin'
            ? 'Superadmin'
            : 'Administrator';

        set_flash('success', 'Welcome, ' . $welcomeRole . '.');
        redirect('admin/dashboard');
    }

    public function studentForgotPassword(): void
    {
        $step = trim((string) $this->post('step', 'request_code'));

        if ($step === 'verify_code') {
            $state = $this->forgotPasswordState();
            if (empty($state)) {
                set_flash('error', 'Password reset session expired. Request a new verification code.');
                redirect('student/forgot-password');
            }

            $studentId = $this->normalizeStudentId((string) ($state['student_id'] ?? ''));
            $codeIdentifier = 'forgot-code:' . $studentId;
            if (!$this->checkScopedRateLimit($codeIdentifier, (int) FORGOT_CODE_MAX_ATTEMPTS, (int) FORGOT_CODE_WINDOW_MINUTES)) {
                set_flash('error', 'Too many verification attempts. Please request a new code.');
                redirect('student/forgot-password');
            }

            $verificationCode = $this->sanitizeInput($this->post('verification_code'));
            $password = $this->post('new_password');
            $confirmPassword = $this->post('password_confirmation');

            if ($verificationCode === '' || $password === '' || $confirmPassword === '') {
                $this->recordScopedRateLimitAttempt($codeIdentifier, (int) FORGOT_CODE_WINDOW_MINUTES);
                set_flash('error', 'Verification code and password fields are required.');
                redirect('student/forgot-password');
            }

            $passwordIssue = $this->validateStrongPassword($password);
            if ($passwordIssue !== null) {
                $this->recordScopedRateLimitAttempt($codeIdentifier, (int) FORGOT_CODE_WINDOW_MINUTES);
                set_flash('error', $passwordIssue);
                redirect('student/forgot-password');
            }

            if ($password !== $confirmPassword) {
                $this->recordScopedRateLimitAttempt($codeIdentifier, (int) FORGOT_CODE_WINDOW_MINUTES);
                set_flash('error', 'Password confirmation does not match.');
                redirect('student/forgot-password');
            }

            $expectedHash = (string) ($state['code_hash'] ?? '');
            $actualHash = hash('sha256', $verificationCode);
            if ($expectedHash === '' || !hash_equals($expectedHash, $actualHash)) {
                $this->recordScopedRateLimitAttempt($codeIdentifier, (int) FORGOT_CODE_WINDOW_MINUTES);
                set_flash('error', 'Invalid verification code.');
                redirect('student/forgot-password');
            }

            $email = (string) ($state['email'] ?? '');
            $isReset = $this->authModel->resetStudentPassword($studentId, $email, $password);
            if (!$isReset) {
                $this->recordScopedRateLimitAttempt($codeIdentifier, (int) FORGOT_CODE_WINDOW_MINUTES);
                set_flash('error', 'Password reset failed. Please request a new verification code.');
                redirect('student/forgot-password');
            }

            unset($_SESSION['auth']['forgot_password']);
            $this->clearScopedRateLimit($codeIdentifier);
            $this->clearScopedRateLimit('forgot-request:' . $studentId);
            set_flash('success', 'Password reset successful. You may now log in.');
            redirect('login');
        }

        $studentId = $this->normalizeStudentId($this->post('student_id'));
        $email = $this->sanitizeInput($this->post('email'));
        $requestIdentifier = 'forgot-request:' . $studentId;

        if (!$this->checkScopedRateLimit($requestIdentifier, (int) FORGOT_REQUEST_MAX_ATTEMPTS, (int) FORGOT_REQUEST_WINDOW_MINUTES)) {
            set_flash('error', 'Too many reset requests. Please try again in ' . FORGOT_REQUEST_WINDOW_MINUTES . ' minutes.');
            redirect('student/forgot-password');
        }

        if ($studentId === '' || $email === '') {
            $this->recordScopedRateLimitAttempt($requestIdentifier, (int) FORGOT_REQUEST_WINDOW_MINUTES);
            set_flash('error', 'Student ID and email are required.');
            redirect('student/forgot-password');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordScopedRateLimitAttempt($requestIdentifier, (int) FORGOT_REQUEST_WINDOW_MINUTES);
            set_flash('error', 'Please provide a valid email address.');
            redirect('student/forgot-password');
        }

        if (!$this->authModel->studentIdentityMatches($studentId, $email)) {
            $this->recordScopedRateLimitAttempt($requestIdentifier, (int) FORGOT_REQUEST_WINDOW_MINUTES);
            set_flash('error', 'Password reset failed. Check your Student ID and email address.');
            redirect('student/forgot-password');
        }

        $code = $this->issueForgotPasswordCode($studentId, $email);
        $sent = $this->sendForgotPasswordOtpEmail($email, $studentId, $code);
        if (!$sent) {
            unset($_SESSION['auth']['forgot_password']);
            $this->recordScopedRateLimitAttempt($requestIdentifier, (int) FORGOT_REQUEST_WINDOW_MINUTES);
            set_flash('error', 'Failed to send verification code. Check SMTP settings and try again.');
            redirect('student/forgot-password');
        }

        $this->clearScopedRateLimit($requestIdentifier);
        set_flash('success', 'Verification code sent to your registered email. Enter it to continue.');
        redirect('student/forgot-password');
    }

    public function showAdminLogin(): void
    {
        if (!empty($_GET['fresh'])) {
            unset($_SESSION['auth']['admin'], $_SESSION['auth']['student']);
            unset($_SESSION['ui']['skip_initial_loading'], $_SESSION['ui']['admin_sidebar_force_closed'], $_SESSION['ui']['student_sidebar_force_closed']);
        }

        $this->render('auth/admin_login');
    }

    public function showAdminAccess(): void
    {
        $this->render('auth/admin_access_gate');
    }

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    }

    private function getAccessGateAttemptFile(string $ip): string
    {
        $cacheDir = APP_ROOT . '/storage/cache/auth';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        return $cacheDir . '/access_gate_' . md5($ip) . '.json';
    }

    private function checkAccessGateRateLimit(string $ip): bool
    {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        $file = $this->getAccessGateAttemptFile($ip);
        $now = time();
        $window = RATE_LIMIT_WINDOW_MINUTES * 60;

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && is_array($data)) {
                $attempts = (int) ($data['attempts'] ?? 0);
                $firstAttempt = (int) ($data['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    return $attempts < RATE_LIMIT_MAX_ATTEMPTS;
                }
            }
        }
        return true;
    }

    private function recordAccessGateAttempt(string $ip): void
    {
        if (!RATE_LIMIT_ENABLED) {
            return;
        }

        $file = $this->getAccessGateAttemptFile($ip);
        $now = time();
        $window = RATE_LIMIT_WINDOW_MINUTES * 60;

        $data = ['attempts' => 1, 'first_attempt' => $now];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && is_array($existing)) {
                $attempts = (int) ($existing['attempts'] ?? 0);
                $firstAttempt = (int) ($existing['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    $data['attempts'] = $attempts + 1;
                    $data['first_attempt'] = $firstAttempt;
                }
            }
        }
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    private function getLoginAttemptFile(string $identifier): string
    {
        $cacheDir = APP_ROOT . '/storage/cache/auth';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        return $cacheDir . '/login_' . md5($identifier) . '.json';
    }

    private function checkLoginRateLimit(string $identifier): bool
    {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        $file = $this->getLoginAttemptFile($identifier);
        $now = time();
        $window = RATE_LIMIT_WINDOW_MINUTES * 60;

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && is_array($data)) {
                $attempts = (int) ($data['attempts'] ?? 0);
                $firstAttempt = (int) ($data['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    return $attempts < RATE_LIMIT_MAX_ATTEMPTS;
                }
            }
        }
        return true;
    }

    private function recordLoginAttempt(string $identifier): void
    {
        if (!RATE_LIMIT_ENABLED) {
            return;
        }

        $file = $this->getLoginAttemptFile($identifier);
        $now = time();
        $window = RATE_LIMIT_WINDOW_MINUTES * 60;

        $data = ['attempts' => 1, 'first_attempt' => $now];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true);
            if ($existing && is_array($existing)) {
                $attempts = (int) ($existing['attempts'] ?? 0);
                $firstAttempt = (int) ($existing['first_attempt'] ?? 0);
                if ($now - $firstAttempt < $window) {
                    $data['attempts'] = $attempts + 1;
                    $data['first_attempt'] = $firstAttempt;
                }
            }
        }
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES));
    }

    private function clearLoginAttempts(string $identifier): void
    {
        $file = $this->getLoginAttemptFile($identifier);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function verifyAdminAccess(): void
    {
        $clientIp = $this->getClientIp();

        if (!$this->checkAccessGateRateLimit($clientIp)) {
            set_flash('error', 'Too many failed access attempts. Please try again in ' . RATE_LIMIT_WINDOW_MINUTES . ' minutes.');
            redirect('admin/access');
        }

        $accessKey = $this->sanitizeInput($this->post('access_key'));
        $confirmAdminAccess = $this->post('confirm_admin_access');

        if (empty($confirmAdminAccess)) {
            set_flash('error', 'You must confirm that you are authorized registrar staff.');
            $this->recordAccessGateAttempt($clientIp);
            redirect('admin/access');
        }

        $validAccessKey = (string) ADMIN_ACCESS_KEY;

        if ($accessKey !== $validAccessKey) {
            set_flash('error', 'Invalid admin access key. Please try again.');
            $this->recordAccessGateAttempt($clientIp);
            redirect('admin/access');
        }

        // Access verified, redirect to admin login form
        set_flash('success', 'Access verified. Please log in with your admin credentials.');
        redirect('admin/login?fresh=1');
    }

    public function adminLogin(): void
    {
        $username = $this->sanitizeInput($this->post('username'));
        $password = $this->post('password');

        if ($username === '' || $password === '') {
            set_flash('error', 'Username and password are required.');
            redirect('login');
        }

        // Check rate limit before attempting login
        if (!$this->checkLoginRateLimit('admin:' . $username)) {
            set_flash('error', 'Too many failed login attempts. Please try again in ' . RATE_LIMIT_WINDOW_MINUTES . ' minutes.');
            redirect('login');
        }

        $admin = $this->authModel->loginAdmin($username, $password);
        if (!$admin) {
            $this->recordLoginAttempt('admin:' . $username);
            set_flash('error', 'Invalid admin credentials.');
            redirect('login');
        }

        $_SESSION['auth']['admin'] = [
            'admin_id' => (int) ($admin['admin_id'] ?? 0),
            'name' => $admin['name'] ?? 'Administrator',
            'email' => $admin['email'] ?? '',
            'username' => $admin['username'] ?? $username,
            'role' => $admin['role'] ?? 'admin',
            'department_id' => $admin['department_id'] ?? null,
            'is_active' => (int) ($admin['is_active'] ?? 1),
        ];

        $_SESSION['ui']['admin_sidebar_force_open'] = true;
        session_regenerate_id(true);
        $this->clearLoginAttempts('admin:' . $username);

        $role = strtolower((string) ($_SESSION['auth']['admin']['role'] ?? 'admin'));
        $departmentId = $_SESSION['auth']['admin']['department_id'] ?? null;
        if ($role === 'admin' && (empty($departmentId) || (int) $departmentId <= 0)) {
            unset($_SESSION['auth']['admin']);
            set_flash('error', 'Your admin account has no assigned department. Please contact superadmin.');
            redirect('login');
        }

        $welcomeRole = $role === 'superadmin'
            ? 'Superadmin'
            : 'Administrator';

        set_flash('success', 'Welcome, ' . $welcomeRole . '.');
        redirect('admin/dashboard');
    }

    public function logoutStudent(): void
    {
        unset($_SESSION['auth']['student']);
        set_flash('success', 'Student session ended.');
        redirect('login');
    }

    public function logoutAdmin(): void
    {
        unset($_SESSION['auth']['admin']);
        set_flash('success', 'Admin session ended.');
        redirect('login');
    }
}
