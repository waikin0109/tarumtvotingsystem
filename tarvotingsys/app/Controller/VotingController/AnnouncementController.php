<?php

namespace Controller\VotingController;

use Model\VotingModel\AnnouncementModel;
use FileHelper;
use SessionHelper;

class AnnouncementController
{
    private $announcementModel;
    private $fileHelper;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->fileHelper = new FileHelper("announcement");
    }

    // public function listAnnouncements()
    // {
    //     // session_start();
    //     // if (empty($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    //     //     header('Location: /login');
    //     //     exit;
    //     // }

    //     $announcements = $this->announcementModel->listForAdmin();
    //     $senderName = $_SESSION['fullName'] ?? 'Unknown';

    //     $filePath = $this->fileHelper->getFilePath('AnnouncementList');

    //     if ($filePath && file_exists($filePath)) {
    //         include $filePath;
    //     } else {
    //         echo "Announcement view file not found.";
    //     }
    // }

    public function listAnnouncements(): void
    {
        if (strtolower($_SESSION['role'] ?? '') !== 'admin') { 
            header('Location: /login');
            exit; 
        }
        $announcements = $this->announcementModel->listForAdmin();
        include $this->fileHelper->getFilePath('AnnouncementList');
    }

    // public function createAnnouncement(): void
    // {
    //     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //         if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login'); exit; }
    //         $errors = $GLOBALS['__errors'] ?? [];
    //         $old    = $GLOBALS['__old'] ?? [];
    //         include $this->fh->getFilePath('CreateAnnouncement');
    //         return;
    //     }

    //     // POST: save draft
    //     if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login'); exit; }
    //     $title   = trim($_POST['title']   ?? '');
    //     $content = trim($_POST['content'] ?? '');
    //     $errors = [];
    //     if ($title === '')   $errors['title'][]   = 'Title is required.';
    //     if ($content === '') $errors['content'][] = 'Content is required.';
    //     if ($errors) {
    //         $GLOBALS['__errors'] = $errors;
    //         $GLOBALS['__old']    = compact('title','content');
    //         return; //make changes hhere
    //     }
    //     $id = $this->model->createDraft($title, $content, (int)$_SESSION['accountID']);
    //     SessionHelper::flash('success', 'Draft saved.');
    //     header('Location: /announcement/edit/'.$id); exit;
    // }

    // public function editAnnouncement(string $id): void
    // {
    //     if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login'); exit; }

    //     if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    //         $ann = $this->model->getById((int)$id);
    //         if (!$ann) { http_response_code(404); exit('Not found'); }
    //         $errors = $GLOBALS['__errors'] ?? [];
    //         $old    = $GLOBALS['__old'] ?? [];
    //         include $this->fh->getFilePath('EditAnnouncement');
    //         return;
    //     }

    //     // POST: update draft
    //     $title   = trim($_POST['title']   ?? '');
    //     $content = trim($_POST['content'] ?? '');
    //     $errors = [];
    //     if ($title === '')   $errors['title'][]   = 'Title is required.';
    //     if ($content === '') $errors['content'][] = 'Content is required.';
    //     if ($errors) {
    //         $GLOBALS['__errors'] = $errors;
    //         $GLOBALS['__old']    = compact('title','content');
    //         return $this->editAnnouncement($id);
    //     }
    //     $ok = $this->model->updateDraft((int)$id, $title, $content, (int)$_SESSION['accountID']);
    //     SessionHelper::flash($ok ? 'success' : 'fail', $ok ? 'Draft updated.' : 'Failed to update.');
    //     header('Location: /announcements'); exit;
    // }

    // public function publishAnnouncement(string $id): void
    // {
    //     if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login'); exit; }
    //     $ok = $this->model->publish((int)$id, (int)$_SESSION['accountID']);
    //     SessionHelper::flash($ok ? 'success' : 'fail', $ok ? 'Announcement published.' : 'Publish failed.');
    //     header('Location: /announcements'); exit;
    // }

    // public function deleteAnnouncement(string $id): void
    // {
    //     if (strtolower($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login'); exit; }
    //     $ok = $this->model->deleteAnnouncement((int)$id);
    //     SessionHelper::flash($ok ? 'success' : 'fail', $ok ? 'Announcement deleted.' : 'Delete failed.');
    //     header('Location: /announcements'); exit;
    // }

    // /* ---------- PUBLIC (Student/Nominee/Admin) ---------- */

    // public function publishedList(): void
    // {
    //     if (!isset($_SESSION['accountID'])) { header('Location:/login'); exit; }
    //     $announcements = $this->model->listPublished();
    //     include $this->fh->getFilePath('ViewAnnouncement'); // this view renders the list for SN
    // }

    // public function viewAnnouncement(string $id): void
    // {
    //     if (!isset($_SESSION['accountID'])) { header('Location:/login'); exit; }
    //     $ann = $this->model->getById((int)$id);
    //     $isAdmin = strtolower($_SESSION['role'] ?? '') === 'admin';
    //     if (!$ann || (!$isAdmin && $ann['status'] !== 'PUBLISHED')) { http_response_code(404); exit('Not found'); }
    //     include $this->fh->getFilePath('ViewAnnouncement'); // same file can render details when $ann is set
    // }



    // public function delete($id)
    // {
    //     session_start();
    //     if (empty($_SESSION['role']) || $_SESSION['role'] !== 'ADMIN') {
    //         header('Location: /login');
    //         exit;
    //     }

    //     $this->announcementModel->deleteAnnouncement($id);
    //     header('Location: /announcement');
    //     exit;
    // }
}