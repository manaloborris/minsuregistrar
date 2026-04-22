<?php
/**
 * All routes here
 */

require_once APP_ROOT . '/app/controllers/AuthController.php';
require_once APP_ROOT . '/app/controllers/StudentController.php';
require_once APP_ROOT . '/app/controllers/AdminController.php';

$authController = new AuthController();
$studentController = new StudentController();
$adminController = new AdminController();

// Public
$router->get('/', fn() => $authController->home());
$router->get('about', fn() => $authController->about());

// Auth routes
$router->get('login', fn() => $authController->showStudentLogin(), ['GuestOnly']);
$router->post('login', fn() => $authController->studentLogin(), ['GuestOnly']);
$router->get('student/login', fn() => $authController->showStudentLogin(), ['GuestOnly']);
$router->post('student/login', fn() => $authController->studentLogin(), ['GuestOnly']);
$router->get('student/forgot-password', fn() => $authController->showStudentForgotPassword(), ['GuestOnly']);
$router->post('student/forgot-password', fn() => $authController->studentForgotPassword(), ['GuestOnly']);
$router->get('student/register', fn() => $authController->showStudentRegister(), ['GuestOnly']);
$router->post('student/register', fn() => $authController->studentRegister(), ['GuestOnly']);
$router->get('student/logout', fn() => $authController->logoutStudent(), ['StudentAuth']);

$router->get('admin/login', fn() => $authController->showAdminLogin(), ['GuestOnly']);
$router->post('admin/login', fn() => $authController->adminLogin(), ['GuestOnly']);
$router->get('admin/access', fn() => $authController->showAdminAccess(), ['GuestOnly']);
$router->post('admin/access', fn() => $authController->verifyAdminAccess(), ['GuestOnly']);
$router->get('admin/logout', fn() => $authController->logoutAdmin(), ['AdminAuth']);

// Student portal
$router->get('student/dashboard', fn() => $studentController->dashboard(), ['StudentAuth']);
$router->get('student/request-document', fn() => $studentController->requestForm(), ['StudentAuth']);
$router->post('student/request-document', fn() => $studentController->submitRequest(), ['StudentAuth']);
$router->get('student/track-requests', fn() => $studentController->trackRequests(), ['StudentAuth']);
$router->post('student/request-followup/{request_id}', fn($request_id) => $studentController->submitFollowUp($request_id), ['StudentAuth']);
$router->get('student/appointments', fn() => $studentController->appointments(), ['StudentAuth']);
$router->post('student/appointments/select-process-slot', fn() => $studentController->selectProcessAppointmentSlot(), ['StudentAuth']);
$router->get('student/notifications', fn() => $studentController->notifications(), ['StudentAuth']);
$router->get('student/profile', fn() => $studentController->profile(), ['StudentAuth']);
$router->get('student/settings', fn() => $studentController->settings(), ['StudentAuth']);
$router->post('student/profile', fn() => $studentController->updateProfile(), ['StudentAuth']);

// Admin system
$router->get('admin/dashboard', fn() => $adminController->dashboard(), ['AdminAuth']);
$router->get('admin/dashboard-analytics', fn() => $adminController->dashboardAnalytics(), ['AdminAuth']);
$router->get('admin/manage-requests', fn() => $adminController->manageRequests(), ['AdminAuth']);
$router->get('admin/manage-requests/{request_id}', fn($request_id) => $adminController->manageRequestDetail($request_id), ['AdminAuth']);
$router->post('admin/manage-requests/status', fn() => $adminController->updateRequestStatus(), ['AdminAuth']);
$router->post('admin/manage-requests/payment-status', fn() => $adminController->updateRequestPaymentStatus(), ['AdminAuth']);
$router->post('admin/manage-requests/process-appointment', fn() => $adminController->updateProcessAppointment(), ['AdminAuth']);
$router->post('admin/manage-requests/pickup-schedule', fn() => $adminController->updatePickupSchedule(), ['AdminAuth']);
$router->post('admin/manage-requests/quick-status', fn() => $adminController->quickStatusUpdate(), ['AdminAuth']);
$router->post('admin/manage-requests/bulk-status', fn() => $adminController->bulkStatusUpdate(), ['AdminAuth']);
$router->get('admin/request-files/{request_id}', fn($request_id) => $adminController->requestFiles($request_id), ['AdminAuth']);

$router->get('admin/students', fn() => $adminController->students(), ['AdminAuth']);
$router->get('admin/students/{student_id}', fn($student_id) => $adminController->viewStudentDetail($student_id), ['AdminAuth']);
$router->post('admin/students/approve', fn() => $adminController->approveStudentRegistration(), ['AdminAuth']);
$router->post('admin/students/reject', fn() => $adminController->rejectStudentRegistration(), ['AdminAuth']);
$router->post('admin/students/bulk-approve', fn() => $adminController->bulkApproveStudentRegistration(), ['AdminAuth']);
$router->post('admin/students/bulk-reject', fn() => $adminController->bulkRejectStudentRegistration(), ['AdminAuth']);
$router->post('admin/students/delete', fn() => $adminController->deleteRejectedStudentPermanently(), ['AdminAuth']);
$router->post('admin/students/update', fn() => $adminController->updateStudentInfo(), ['AdminAuth']);
$router->post('admin/students/warn', fn() => $adminController->sendStudentWarning(), ['AdminAuth']);

$router->get('admin/document-types', fn() => $adminController->documentTypes(), ['AdminAuth']);
$router->post('admin/document-types/add', fn() => $adminController->addDocumentType(), ['AdminAuth']);
$router->post('admin/document-types/update', fn() => $adminController->updateDocumentType(), ['AdminAuth']);
$router->post('admin/document-types/delete', fn() => $adminController->deleteDocumentType(), ['AdminAuth']);

$router->get('admin/appointments', fn() => $adminController->appointments(), ['AdminAuth']);
$router->get('admin/reports', fn() => $adminController->reports(), ['AdminAuth']);
$router->get('admin/reports/pdf', fn() => $adminController->exportReportsPdf(), ['AdminAuth']);
$router->get('admin/notifications', fn() => $adminController->notifications(), ['AdminAuth']);
$router->get('admin/settings', fn() => $adminController->settings(), ['AdminAuth']);
$router->get('admin/sidebar-counts', fn() => $adminController->sidebarCounts(), ['AdminAuth']);

$router->get('admin/manage-admins', fn() => $adminController->manageAdmins(), ['AdminAuth']);
$router->get('admin/manage-admins/{admin_id}', fn($admin_id) => $adminController->viewAdminDetail($admin_id), ['AdminAuth']);
$router->post('admin/manage-admins/add', fn() => $adminController->addAdminAccount(), ['AdminAuth']);
$router->post('admin/manage-admins/update', fn() => $adminController->updateAdminAccount(), ['AdminAuth']);
$router->post('admin/manage-admins/reset-password', fn() => $adminController->resetAdminAccountPassword(), ['AdminAuth']);