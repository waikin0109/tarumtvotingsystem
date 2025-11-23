<?php

namespace Controller\StudentController;

use Controller\AdminController\LoginController; // reuse auth guard
use Model\StudentModel\StudentModel;
use FileHelper;

class StudentController
{
    private StudentModel $studentModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->fileHelper   = new FileHelper('student');
    }

    // ---------- VIEW PROFILE ----------
    public function profile(): void
    {
        LoginController::requireAuth('STUDENT');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            header('Location: /login');
            exit;
        }

        $profile = $this->studentModel->getStudentProfileByAccountId($accountID);
        if (!$profile) {
            http_response_code(404);
            echo "Profile not found.";
            return;
        }

        $filePath = $this->fileHelper->getFilePath('StudentProfile');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Student Profile view not found.";
        }
    }

    // ---------- UPDATE PASSWORD ----------
    public function updatePassword(): void
    {
        LoginController::requireAuth('STUDENT');

        $accountID       = (int) ($_SESSION['accountID'] ?? 0);
        $currentPassword = (string) ($_POST['currentPassword'] ?? '');
        $newPassword     = (string) ($_POST['newPassword'] ?? '');
        $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

        $errors = [];

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        $profile = $this->studentModel->getStudentProfileByAccountId($accountID);
        if (!$profile) {
            $errors[] = 'Unable to load your profile.';
        } else {
            if (!password_verify($currentPassword, $profile['passwordHash'])) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        if (!empty($errors)) {
            if (function_exists('set_flash')) {
                foreach ($errors as $e) {
                    set_flash('fail', $e);
                }
            }
            header('Location: /student/profile');
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->studentModel->updatePassword($accountID, $newHash)) {
            if (function_exists('set_flash')) {
                set_flash('success', 'Password updated successfully.');
            }
        } else {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to update password. Please try again.');
            }
        }

        header('Location: /student/profile');
        exit;
    }

    // ---------- UPDATE PHOTO ----------
    public function updatePhoto(): void
    {
        LoginController::requireAuth('STUDENT');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);

        if (!isset($_FILES['profilePhoto']) || $_FILES['profilePhoto']['error'] !== UPLOAD_ERR_OK) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Please select a valid image file.');
            }
            header('Location: /student/profile');
            exit;
        }

        $file = $_FILES['profilePhoto'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes, true)) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Only JPG and PNG images are allowed.');
            }
            header('Location: /student/profile');
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Image size must not exceed 2 MB.');
            }
            header('Location: /student/profile');
            exit;
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $uploadDir  = $publicRoot . '/uploads/profile_photos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'student_' . $accountID . '_' . time() . '.' . $ext;
        $target   = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to upload image.');
            }
            header('Location: /student/profile');
            exit;
        }

        $relativeUrl = '/uploads/profile_photos/' . $filename;

        if ($this->studentModel->updateProfilePhoto($accountID, $relativeUrl)) {
            $_SESSION['profilePhotoURL'] = $relativeUrl;
            if (function_exists('set_flash')) {
                set_flash('success', 'Profile photo updated successfully.');
            }
        } else {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to save profile photo into database.');
            }
        }

        header('Location: /student/profile');
        exit;
    }
}
