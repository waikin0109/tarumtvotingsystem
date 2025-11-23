<?php

namespace Controller\AdminController;

use Model\AdminModel\LoginModel;
// Admin,  Student, Nominee
use Model\AdminModel\AdminModel;
use Model\NomineeModel\NomineeModel;
use Model\StudentModel\StudentModel;

use Model\VotingModel\ElectionEventModel;
use Model\NomineeModel\NomineeApplicationModel;
use Model\CampaignHandlingModel\ScheduleLocationModel; 
use Model\CampaignHandlingModel\CampaignMaterialModel;

use FileHelper;
use SessionHelper;

class LoginController
{
    private $loginModel;
    private $adminModel;
    private $nomineeModel;
    private $studentModel;
    private ElectionEventModel $electionEventModel;
    private NomineeApplicationModel $nomineeApplicationModel;
    private ScheduleLocationModel $scheduleLocationModel;
    private CampaignMaterialModel $campaignMaterialModel;
    private $fileHelper;

    public function __construct()
    {
        $this->loginModel = new LoginModel();
        $this->adminModel = new AdminModel();
        $this->nomineeModel = new NomineeModel();
        $this->studentModel = new StudentModel();
        $this->electionEventModel     = new ElectionEventModel();
        $this->nomineeApplicationModel = new NomineeApplicationModel();
        $this->scheduleLocationModel  = new ScheduleLocationModel();
        $this->campaignMaterialModel  = new CampaignMaterialModel();
        $this->fileHelper = new FileHelper("login");
    }

    public function login()
    {
        $errors = SessionHelper::flash('errors') ?? [];
        $oldData = SessionHelper::flash('oldData') ?? [];

        $filePath = $this->fileHelper->getFilePath('Login');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Login view not found.";
        }
    }

    public function authenticate()
    {
        $loginID = trim($_POST['loginID'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $oldData = ['loginID' => $loginID];

        // Validate input
        $errors = $this->validateLoginInput($loginID, $password);
        if (!empty($errors)) {
            SessionHelper::flash('errors', $errors);
            SessionHelper::flash('oldData', $oldData);
            return $this->login();
        }

        // Find user
        $user = $this->loginModel->findByLoginID((int) $loginID);
        $invalid = 'Invalid Login ID or Password.';

        if (!$user) {
            $errors['global'][] = $invalid;
            SessionHelper::flash('errors', $errors);
            SessionHelper::flash('oldData', $oldData);
            return $this->login();
        }

        // Account status check
        if ($user['status'] !== 'ACTIVE') {
            $errors['global'][] = 'Your account is not active. Please contact admin.';
            SessionHelper::flash('errors', $errors);
            SessionHelper::flash('oldData', $oldData);
            return $this->login();
        }

        // Password check
        if (!password_verify($password, $user['passwordHash'])) {
            $errors['global'][] = $invalid;
            SessionHelper::flash('errors', $errors);
            SessionHelper::flash('oldData', $oldData);
            return $this->login();
        }

        // Success login
        $this->loginUser($user);
        $this->loginModel->updateLastLoginAt($user['accountID']);

        if ($user['role'] === 'ADMIN') {
            header('Location: /admin/home');
        } elseif ($user['role'] === 'STUDENT') {
            header('Location: /student/home');
        } elseif ($user['role'] === 'NOMINEE') {
            header('Location: /nominee/home');
        } else {
            // Unknown role, logout for safety
            $this->logout();
        }
        exit;
    }

    private function validateLoginInput(string $loginID, string $password): array
    {
        $errors = [];
        if ($loginID === '') {
            $errors['loginID'][] = 'Login ID is required.';
        } elseif (!preg_match('/^\d{7}$/', $loginID)) {
            $errors['loginID'][] = 'Login ID must be exactly 7 digits.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        }

        return $errors;
    }

    private function loginUser(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        session_regenerate_id(true);

        $_SESSION['accountID'] = (int) $user['accountID'];
        $_SESSION['loginID'] = (int) $user['loginID'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['fullName'] = (string) $user['fullName'];
        // admin/student/nominee
        if ($user['role'] === 'ADMIN') {
            $_SESSION['roleID'] = $this->adminModel->getAdminIdByAccId($user['accountID']);
        } elseif ($user['role'] === 'STUDENT') {
            $_SESSION['roleID'] = $this->studentModel->getStudentIdByAccId($user['accountID']);
        } elseif ($user['role'] === 'NOMINEE') {
            $_SESSION['roleID'] = $this->nomineeModel->getNomineeIdByAccId($user['accountID']);
        }
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_unset();
        session_destroy();

        session_start();
        session_regenerate_id(true);
        header('Location: /login');
        exit;
    }

    public static function requireAuth(?string $requiredRole = null): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (empty($_SESSION['accountID'])) {
            header('Location: /login');
            exit;
        }
        if ($requiredRole !== null && ($_SESSION['role'] ?? '') !== $requiredRole) {
            http_response_code(403);
            exit('Forbidden');
        }
    }

    public function adminHome()
    {
        self::requireAuth('ADMIN');

        // 1) Build dashboard stats here
        $dashboardStats = [
            'totalElectionEvents'        => $this->electionEventModel->countAll(),
            'ongoingElectionEvents'      => $this->electionEventModel->countByStatus('ONGOING'),
            'totalNomineeApplications'   => $this->nomineeApplicationModel->countAll(),
            'pendingNomineeApplications' => $this->nomineeApplicationModel->countByStatus('PENDING'),
            'pendingScheduleLocations'   => $this->scheduleLocationModel->countByStatus('PENDING'),
            'pendingCampaignMaterials'   => $this->campaignMaterialModel->countByStatus('PENDING'),
        ];

        // 2) Recent elections (for dashboard list/card)
        $recentElections = $this->electionEventModel->getRecent(5);

        $fileHelper = new FileHelper('admin');
        $filePath = $fileHelper->getFilePath('AdminHome');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Admin Home view not found.";
        }
    }

    public function studentHome()
    {
        self::requireAuth('STUDENT');

    // Simple stats for student dashboard (campus-wide, not per-student)
    $studentStats = [
        'ongoingElectionEvents'   => $this->electionEventModel->countByStatus('ONGOING'),
        'completedElectionEvents' => $this->electionEventModel->countByStatus('COMPLETED'),
    ];

    // Latest election events (e.g. 5 most recent)
    $recentElections = $this->electionEventModel->getRecent(5);

        $fileHelper = new FileHelper('student');
        $filePath = $fileHelper->getFilePath('StudentHome');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Student Home view not found.";
        }
    }

    public function nomineeHome()
    {
        self::requireAuth('NOMINEE');

        $accountID = (int)($_SESSION['accountID'] ?? 0);

        // ---------- 1) My nominee applications ----------
        $appsByForm = $this->nomineeApplicationModel->getApplicationsByAccountIndexed($accountID);
        $nomineeApplicationStats = [
            'total'     => 0,
            'pending'   => 0,
            'accepted'  => 0,
            'rejected'  => 0,
            'published' => 0,
        ];

        foreach ($appsByForm as $row) {
            $nomineeApplicationStats['total']++;
            $status = strtoupper((string)($row['applicationStatus'] ?? ''));
            if (isset($nomineeApplicationStats[strtolower($status)])) {
                $nomineeApplicationStats[strtolower($status)]++;
            }
        }

        // ---------- 2) My campaign materials ----------
        $myCampaignMaterials = $this->campaignMaterialModel->getCampaignMaterialsByAccount($accountID);

        $campaignMaterialStats = [
            'total'   => 0,
            'pending' => 0,
            'approved'=> 0,
            'rejected'=> 0,
        ];

        foreach ($myCampaignMaterials as $cm) {
            $campaignMaterialStats['total']++;
            $status = strtoupper((string)($cm['materialsApplicationStatus'] ?? ''));
            if ($status === 'PENDING') {
                $campaignMaterialStats['pending']++;
            } elseif ($status === 'APPROVED') {
                $campaignMaterialStats['approved']++;
            } elseif ($status === 'REJECTED') {
                $campaignMaterialStats['rejected']++;
            }
        }

        // ---------- 3) Elections where I am a nominee ----------
        $myElectionsAsNominee = $this->campaignMaterialModel->getElectionsForNominee($accountID);
        $electionStats = [
            'as_nominee' => is_array($myElectionsAsNominee) ? count($myElectionsAsNominee) : 0,
        ];

        $fileHelper = new FileHelper('nominee');
        $filePath = $fileHelper->getFilePath('NomineeHome');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Nominee Home view not found.";
        }
    }
}
