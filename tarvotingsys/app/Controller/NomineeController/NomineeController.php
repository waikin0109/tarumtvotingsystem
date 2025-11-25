<?php

namespace Controller\NomineeController;

use Controller\AdminController\LoginController; // reuse auth guard
use Model\NomineeModel\NomineeModel;
use Model\VotingModel\VoteSessionModel;
use FileHelper;

class NomineeController
{
    private NomineeModel $nomineeModel;

    private VoteSessionModel $voteSessionModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->nomineeModel = new NomineeModel();
        $this->voteSessionModel = new VoteSessionModel();
        $this->fileHelper = new FileHelper('nominee');
    }

    // ---------- VIEW PROFILE ----------
    public function profile(): void
    {
        LoginController::requireAuth('NOMINEE');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            header('Location: /login');
            exit;
        }

        $profile = $this->nomineeModel->getNomineeProfileByAccountId($accountID);
        if (!$profile) {
            http_response_code(404);
            echo "Nominee profile not found.";
            return;
        }

        $filePath = $this->fileHelper->getFilePath('NomineeProfile');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "Nominee Profile view not found.";
        }
    }

    // ---------- UPDATE PASSWORD ----------
    public function updatePassword(): void
    {
        LoginController::requireAuth('NOMINEE');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        $currentPassword = (string) ($_POST['currentPassword'] ?? '');
        $newPassword = (string) ($_POST['newPassword'] ?? '');
        $confirmPassword = (string) ($_POST['confirmPassword'] ?? '');

        $errors = [];

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $errors[] = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        $profile = $this->nomineeModel->getNomineeProfileByAccountId($accountID);
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
            header('Location: /nominee/profile');
            exit;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($this->nomineeModel->updatePassword($accountID, $newHash)) {
            if (function_exists('set_flash')) {
                set_flash('success', 'Password updated successfully.');
            }
        } else {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to update password. Please try again.');
            }
        }

        header('Location: /nominee/profile');
        exit;
    }

    // ---------- UPDATE PHOTO ----------
    public function updatePhoto(): void
    {
        LoginController::requireAuth('NOMINEE');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);

        if (!isset($_FILES['profilePhoto']) || $_FILES['profilePhoto']['error'] !== UPLOAD_ERR_OK) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Please select a valid image file.');
            }
            header('Location: /nominee/profile');
            exit;
        }

        $file = $_FILES['profilePhoto'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowedTypes, true)) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Only JPG and PNG images are allowed.');
            }
            header('Location: /nominee/profile');
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Image size must not exceed 2 MB.');
            }
            header('Location: /nominee/profile');
            exit;
        }

        $publicRoot = dirname(__DIR__, 2) . '/public';
        $uploadDir = $publicRoot . '/uploads/profile_photos/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'nominee_' . $accountID . '_' . time() . '.' . $ext;
        $target = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to upload image.');
            }
            header('Location: /nominee/profile');
            exit;
        }

        $relativeUrl = '/uploads/profile_photos/' . $filename;

        if ($this->nomineeModel->updateProfilePhoto($accountID, $relativeUrl)) {
            $_SESSION['profilePhotoURL'] = $relativeUrl;
            if (function_exists('set_flash')) {
                set_flash('success', 'Profile photo updated successfully.');
            }
        } else {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to save profile photo into database.');
            }
        }

        header('Location: /nominee/profile');
        exit;
    }

    public function selectRace(): void
    {
        LoginController::requireAuth('NOMINEE');

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            header('Location: /login');
            exit;
        }

        // basic nominee info (includes electionID, facultyID, current raceID)
        $nominee = $this->nomineeModel->getBasicNomineeByAccountId($accountID);
        if (!$nominee) {
            set_flash('fail', 'You are not registered as a nominee in this system.');
            header('Location: /nominee/profile');
            exit;
        }

        $electionID = (int) $nominee['electionID'];
        $facultyID = (int) $nominee['facultyID'];

        // HERE: all races this nominee is allowed to choose
        $availableRaces = $this->voteSessionModel->getAvailableRacesForNominee($electionID, $facultyID);
        $currentRaceID = (int) ($nominee['raceID'] ?? 0);

        $filePath = $this->fileHelper->getFilePath('NomineeSelectRace');
        include $filePath;
    }


    // ---------- CHOOSE RACE (POST) ----------
    public function saveRaceSelection(): void
    {
        LoginController::requireAuth('NOMINEE');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /nominee/select-race');
            exit;
        }

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            header('Location: /login');
            exit;
        }

        $nominee = $this->nomineeModel->getBasicNomineeByAccountId($accountID);
        if (!$nominee) {
            if (function_exists('set_flash')) {
                set_flash('fail', 'You are not registered as a nominee in this system.');
            }
            header('Location: /nominee/profile');
            exit;
        }

        $raceID = (int) ($_POST['raceID'] ?? 0);
        $errors = [];

        if ($raceID <= 0) {
            $errors[] = 'Please select a race.';
        }

        // Re-fetch allowed races and check that chosen raceID is valid
        $availableRaces = $this->voteSessionModel->getAvailableRacesForNominee(
            (int) $nominee['electionID'],
            (int) $nominee['facultyID']
        );

        $allowed = false;
        foreach ($availableRaces as $r) {
            if ((int) $r['raceID'] === $raceID) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            $errors[] = 'The selected race is not valid for your election or faculty.';
        }

        if (!empty($errors)) {
            if (function_exists('set_flash')) {
                foreach ($errors as $e) {
                    set_flash('fail', $e);
                }
            }
            header('Location: /nominee/select-race');
            exit;
        }

        $ok = $this->nomineeModel->updateNomineeRace((int) $nominee['nomineeID'], $raceID);

        if ($ok) {
            if (function_exists('set_flash')) {
                set_flash('success', 'Your race and seat type have been saved for this election.');
            }
        } else {
            if (function_exists('set_flash')) {
                set_flash('fail', 'Failed to save your race selection. Please try again.');
            }
        }

        header('Location: /nominee/profile');
        exit;
    }

    public function updateManifesto(): void
    {
        LoginController::requireAuth('NOMINEE');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /nominee/profile');
            exit;
        }

        // 1) Get accountID from session
        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            set_flash('fail', 'Please log in again.');
            header('Location: /login');
            exit;
        }

        // 2) Load nominee row using accountID
        $nominee = $this->nomineeModel->getBasicNomineeByAccountId($accountID);
        if (!$nominee) {
            set_flash('fail', 'You are not registered as a nominee in this system.');
            header('Location: /nominee/profile');
            exit;
        }

        $nomineeID = (int) ($nominee['nomineeID'] ?? 0);
        if ($nomineeID <= 0) {
            set_flash('fail', 'Nominee account is not recognised.');
            header('Location: /nominee/profile');
            exit;
        }

        // 3) Validate manifesto
        $manifesto = trim($_POST['manifesto'] ?? '');

        if ($manifesto === '') {
            set_flash('fail', 'Manifesto cannot be empty.');
            header('Location: /nominee/profile');
            exit;
        }

        if (mb_strlen($manifesto) > 2000) {
            set_flash('fail', 'Manifesto is too long. Maximum 2000 characters.');
            header('Location: /nominee/profile');
            exit;
        }

        // 4) Save to DB
        $ok = $this->nomineeModel->updateManifesto($nomineeID, $manifesto);

        if ($ok) {
            set_flash('success', 'Manifesto updated successfully.');
        } else {
            set_flash('fail', 'Failed to update manifesto. Please try again later.');
        }

        header('Location: /nominee/profile');
        exit;
    }

}