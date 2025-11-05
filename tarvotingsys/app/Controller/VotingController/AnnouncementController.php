<?php

namespace Controller\VotingController;

use Model\VotingModel\AnnouncementModel;
use FileHelper;

class AnnouncementController
{
    private $announcementModel;
    private $fileHelper;

    public function __construct()
    {
        $this->announcementModel = new AnnouncementModel();
        $this->fileHelper = new FileHelper("announcement");
    }

    public function listAnnouncements(): void
    {
        if (strtolower($_SESSION['role'] ?? '') !== 'admin') {
            header('Location: /login');
            exit;
        }

        $this->announcementModel->autoPublishDue();

        $announcements = $this->announcementModel->listForAdmin();
        include $this->fileHelper->getFilePath('AnnouncementList');
    }

    //Create Announcement
    public function createAnnouncement(): void
    {
        if (empty($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
            set_flash('fail', 'You must be an admin to create announcements.');
            header('Location: /login');
            exit;
        }

        $announcementCreationData = [
            'title' => '',
            'content' => '',
            'publishMode' => 'draft',
            'publishAtLocal' => ''
        ];
        $fieldErrors = [];
        include $this->fileHelper->getFilePath('CreateAnnouncement');
    }

    public function storeAnnouncement(): void
    {
        if (empty($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
            set_flash('fail', 'You must be an admin to create announcements.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createAnnouncement();
            return;
        }

        // Gather
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $publishMode = $_POST['publishMode'] ?? 'draft';
        $publishAt = trim($_POST['publishAt'] ?? '');   // MySQL DATETIME from hidden
        $publishAtLocal = trim($_POST['publishAtLocal'] ?? '');

        $announcementCreationData = [
            'title' => $title,
            'content' => $content,
            'publishMode' => $publishMode,
            'publishAtLocal' => $publishAtLocal
        ];

        $fieldErrors = [];

        // Per-field validation only
        if ($title === '') {
            $fieldErrors['title'][] = 'Title is required.';
        }
        if (mb_strlen($title) > 100) {
            $fieldErrors['title'][] = 'Title must be at most 100 characters.';
        }
        if ($content === '') {
            $fieldErrors['content'][] = 'Content is required.';
        }

        $status = 'DRAFT';
        $publishedAt = null;

        if ($publishMode === 'now') {
            $status = 'PUBLISHED';
            $publishedAt = date('Y-m-d H:i:s');
        } elseif ($publishMode === 'schedule') {
            if ($publishAt === '') {
                $fieldErrors['publishAt'][] = 'Please choose a valid date and time.';
            } else {
                $ts = strtotime($publishAt);
                if ($ts === false) {
                    $fieldErrors['publishAt'][] = 'Invalid publish datetime.';
                } elseif ($ts <= time()) {
                    $fieldErrors['publishAt'][] = 'Publish date/time must be in the future.';
                } else {
                    $status = 'SCHEDULED';
                    $publishedAt = date('Y-m-d H:i:s', $ts);
                }
            }
        }

        if (!empty($fieldErrors)) {
            include $this->fileHelper->getFilePath('CreateAnnouncement');
            return;
        }

        // Save
        $announcementId = $this->announcementModel->createAnnouncement([
            'title' => $title,
            'content' => $content,
            'createdBy' => (int) ($_SESSION['accountID'] ?? 0),
            'createdAt' => date('Y-m-d H:i:s'),
            'publishedAt' => $publishedAt,
            'status' => $status
        ]);

        if (!$announcementId) {
            $fieldErrors['title'][] = 'Failed to create announcement. Please try again.';
            include $this->fileHelper->getFilePath('CreateAnnouncement');
            return;
        }

        // Attachments (unchanged)
        $savedCount = 0;
        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/announcements/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $maxSize = 10 * 1024 * 1024;
            $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            $allowedMime = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png'
            ];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            foreach ($_FILES['attachments']['name'] as $i => $clientName) {
                $tmp = $_FILES['attachments']['tmp_name'][$i] ?? null;
                $err = $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                $size = (int) ($_FILES['attachments']['size'][$i] ?? 0);

                if ($err !== UPLOAD_ERR_OK || !$tmp)
                    continue;
                if ($size <= 0 || $size > $maxSize)
                    continue;

                $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true))
                    continue;

                $mime = $finfo->file($tmp) ?: '';
                if (!in_array($mime, $allowedMime, true))
                    continue;

                $stored = bin2hex(random_bytes(12)) . '.' . $ext;
                $dest = $uploadDir . $stored;

                if (move_uploaded_file($tmp, $dest)) {
                    $this->announcementModel->addAttachment($announcementId, [
                        'original' => $clientName,
                        'stored' => $stored,
                        'mime' => $mime,
                        'size' => $size
                    ]);
                    $savedCount++;
                }
            }
        }

        set_flash(
            'success',
            'New announcement is created successfully.' .
            ($savedCount ? " {$savedCount} attachment(s) uploaded." : '')
        );
        header('Location: /announcements');
        exit;
    }

    public function revertAnnouncementToDraft(string $id): void
    {
        if (empty($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $aid = (int) $id;
        $ownerId = (int) ($_SESSION['accountID'] ?? 0);

        $ok = $this->announcementModel->revertScheduledToDraft($aid, $ownerId);

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Announcement reverted to Draft.' :
            'Unable to revert. It may no longer be scheduled, the time may have passed, or you are not the owner.'
        );

        header('Location: /announcements');
        exit;
    }

    public function publishAnnouncement(string $id): void
    {
        if (empty($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $aid = (int) $id;
        $ownerId = (int) ($_SESSION['accountID'] ?? 0);

        $ok = $this->announcementModel->publishNowDraft($aid, $ownerId);

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Announcement published.' :
            'Unable to publish. It may not be a Draft or you are not the owner.'
        );

        header('Location: /announcements');
        exit;
    }

    public function viewAnnouncementDetails(string $id): void
    {
        if (empty($_SESSION['accountID'])) {
            set_flash('fail', 'Please log in to view announcements.');
            header('Location: /login');
            exit;
        }

        $aid = (int) $id;
        $ann = $this->announcementModel->getById($aid);
        if (!$ann) {
            http_response_code(404);
            exit('Announcement not found.');
        }

        // Only admin can see non-published
        $role = strtolower($_SESSION['role'] ?? '');
        if ($role !== 'admin' && strtoupper($ann['status']) !== 'PUBLISHED') {
            http_response_code(403);
            exit('Not allowed.');
        }

        // Decide which date to show + label
        $status = strtoupper($ann['status'] ?? '');
        $createdAt = $ann['createdAt'] ?? null;
        $publishedAt = $ann['publishedAt'] ?? null;

        switch ($status) {
            case 'DRAFT':
                $whenLabel = 'Created';
                $whenValue = $createdAt;
                break;
            case 'SCHEDULED':
                $whenLabel = 'Publishes';
                $whenValue = $publishedAt;
                break;
            default: // PUBLISHED
                $whenLabel = 'Published';
                $whenValue = $publishedAt ?: $createdAt;
        }

        // Pack for the view
        $announcement = [
            'id' => $ann['announcementID'],
            'title' => $ann['title'],
            'content' => $ann['content'],
            'status' => $status,
            'senderName' => $ann['senderName'] ?? 'Unknown',
            'whenLabel' => $whenLabel,
            'whenValue' => $whenValue,
            'attachments' => $ann['attachments'] ?? [],
        ];

        include $this->fileHelper->getFilePath('ViewAnnouncementDetails');
    }

    public function editAnnouncement(string $id): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $aid = (int) $id;
        $ownerId = (int) ($_SESSION['accountID'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $ann = $this->announcementModel->getById($aid);
            if (!$ann) {
                http_response_code(404);
                exit('Announcement not found.');
            }

            $isOwner = ((int) ($ann['accountID'] ?? 0) === $ownerId);
            if (!$isOwner || strtoupper($ann['announcementStatus'] ?? '') !== 'DRAFT') {
                set_flash('fail', 'Only the owner can edit a Draft announcement.');
                header('Location: /announcements');
                exit;
            }

            $announcement = [
                'id' => $aid,
                'title' => $ann['title'] ?? '',
                'content' => $ann['content'] ?? '',
                'status' => strtoupper($ann['announcementStatus'] ?? 'DRAFT'),
                'publishAtLocal' => ''
            ];
            $attachments = $this->announcementModel->getAttachmentsByAnnouncement($aid);
            $fieldErrors = [];

            include $this->fileHelper->getFilePath('EditAnnouncement');
            return;
        }

        // ---------- POST ----------
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $publishMode = $_POST['publishMode'] ?? 'draft';      // draft|now|schedule
        $publishAt = trim($_POST['publishAt'] ?? '');        // MySQL DATETIME
        $publishAtLocal = trim($_POST['publishAtLocal'] ?? '');   // for re-render
        $removeIds = array_filter(array_map('intval', $_POST['remove_ids'] ?? [])); // << batch delete

        // Re-verify draft + owner
        $ann = $this->announcementModel->getById($aid);
        if (!$ann) {
            http_response_code(404);
            exit('Announcement not found.');
        }
        $isOwner = ((int) ($ann['accountID'] ?? 0) === $ownerId);
        if (!$isOwner || strtoupper($ann['announcementStatus'] ?? '') !== 'DRAFT') {
            set_flash('fail', 'Only the owner can edit a Draft announcement.');
            header('Location: /announcements');
            exit;
        }

        // Validate fields
        $fieldErrors = [];
        if ($title === '') {
            $fieldErrors['title'][] = 'Title is required.';
        }
        if (mb_strlen($title) > 100) {
            $fieldErrors['title'][] = 'Title must be at most 100 characters.';
        }
        if ($content === '') {
            $fieldErrors['content'][] = 'Content is required.';
        }

        if ($publishMode === 'schedule') {
            if ($publishAt === '') {
                $fieldErrors['publishAt'][] = 'Please choose a valid date and time.';
            } else {
                $ts = strtotime($publishAt);
                if ($ts === false) {
                    $fieldErrors['publishAt'][] = 'Invalid publish datetime.';
                } elseif ($ts <= time()) {
                    $fieldErrors['publishAt'][] = 'Publish date/time must be in the future.';
                }
            }
        }

        // Prepare for re-render
        $announcement = [
            'id' => $aid,
            'title' => $title,
            'content' => $content,
            'status' => 'DRAFT',
            'publishAtLocal' => $publishAtLocal,
        ];
        $attachments = $this->announcementModel->getAttachmentsByAnnouncement($aid);

        if (!empty($fieldErrors)) {
            include $this->fileHelper->getFilePath('EditAnnouncement');
            return;
        }

        // Track if anything changed
        $changedAnything = false;

        // 1) Title/content change
        $titleChanged = ($title !== (string) ($ann['title'] ?? ''));
        $contentChanged = ($content !== (string) ($ann['content'] ?? ''));
        if ($titleChanged || $contentChanged) {
            $ok = $this->announcementModel->updateDraft($aid, $title, $content, $ownerId);
            if (!$ok) {
                $fieldErrors['title'][] = 'Failed to save changes.';
                include $this->fileHelper->getFilePath('EditAnnouncement');
                return;
            }
            $changedAnything = true;
        }

        // 2) Batch delete attachments (in the same Save)
        $removedCount = 0;
        if (!empty($removeIds)) {
            foreach ($removeIds as $attId) {
                if ($this->announcementModel->deleteAttachmentSecure($attId, $ownerId)) {
                    $removedCount++;
                }
            }
            if ($removedCount > 0) {
                $changedAnything = true;
            }
        }

        // 3) Add new attachments (block duplicates)
        $savedCount = 0;
        if (!empty($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
            // Pre-check duplicates in batch + DB
            $dupErrors = [];
            $batchSeen = [];
            foreach ($_FILES['attachments']['name'] as $i => $clientName) {
                $tmp = $_FILES['attachments']['tmp_name'][$i] ?? null;
                $err = $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                $size = (int) ($_FILES['attachments']['size'][$i] ?? 0);
                if ($err !== UPLOAD_ERR_OK || !$tmp || $size <= 0)
                    continue;

                $key = $clientName . '|' . $size;
                if (isset($batchSeen[$key])) {
                    $dupErrors[] = "“{$clientName}” is selected more than once.";
                    continue;
                }
                $batchSeen[$key] = true;

                if ($this->announcementModel->attachmentExists($aid, $clientName, $size)) {
                    $dupErrors[] = "“{$clientName}” already exists in this announcement.";
                }
            }
            if (!empty($dupErrors)) {
                $fieldErrors['attachments'] = $dupErrors;
                $attachments = $this->announcementModel->getAttachmentsByAnnouncement($aid);
                include $this->fileHelper->getFilePath('EditAnnouncement');
                return;
            }

            // Save files
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads/announcements/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $maxSize = 10 * 1024 * 1024;
            $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
            $allowedMime = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png'
            ];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);

            foreach ($_FILES['attachments']['name'] as $i => $clientName) {
                $tmp = $_FILES['attachments']['tmp_name'][$i] ?? null;
                $err = $_FILES['attachments']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                $size = (int) ($_FILES['attachments']['size'][$i] ?? 0);

                if ($err !== UPLOAD_ERR_OK || !$tmp)
                    continue;
                if ($size <= 0 || $size > $maxSize)
                    continue;

                $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true))
                    continue;

                $mime = $finfo->file($tmp) ?: '';
                if (!in_array($mime, $allowedMime, true))
                    continue;

                $stored = bin2hex(random_bytes(12)) . '.' . $ext;
                $dest = $uploadDir . $stored;

                if (move_uploaded_file($tmp, $dest)) {
                    $this->announcementModel->addAttachment($aid, [
                        'original' => $clientName,
                        'stored' => $stored,
                        'mime' => $mime,
                        'size' => $size
                    ]);
                    $savedCount++;
                }
            }

            if ($savedCount > 0)
                $changedAnything = true;
        }

        // 4) Publish actions
        $actionMsg = null;
        if ($publishMode === 'now') {
            if ($this->announcementModel->publishNowDraft($aid, $ownerId)) {
                $actionMsg = 'published';
                $changedAnything = true;
            } else {
                set_flash('fail', 'Saved, but failed to publish now.');
                header('Location: /announcements');
                exit;
            }
        } elseif ($publishMode === 'schedule') {
            if ($this->announcementModel->scheduleDraft($aid, $ownerId, $publishAt)) {
                $actionMsg = 'scheduled';
                $changedAnything = true;
            } else {
                set_flash('fail', 'Saved, but failed to schedule publish time.');
                header('Location: /announcements');
                exit;
            }
        }

        // 5) Outcome
        if (!$changedAnything) {
            // No flash; just reload edit page quietly to avoid confusing “no changes” message
            set_flash('info', 'No changes detected.');
            header('Location: /announcement/edit/' . $aid);
            exit;
        }

        $msg = 'Announcement ' . ($actionMsg ?: 'updated');
        if (isset($removedCount) && $removedCount)
            $msg .= ". {$removedCount} attachment(s) removed";
        if (isset($savedCount) && $savedCount)
            $msg .= ". {$savedCount} attachment(s) uploaded";
        set_flash('success', $msg . '.');

        header('Location: /announcements');
        exit;
    }

    public function deleteAttachment(?string $attachmentId = null): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $ownerId = (int) ($_SESSION['accountID'] ?? 0);
        $attachId = $attachmentId !== null ? (int) $attachmentId : (int) ($_POST['attachment_id'] ?? 0);
        $announcementId = (int) ($_REQUEST['announcement_id'] ?? 0); // works for GET/POST
        $returnTo = $_REQUEST['return_to'] ?? '/announcements';

        if ($attachId <= 0) {
            set_flash('fail', 'Invalid attachment.');
            header('Location: ' . ($returnTo ?: '/announcements'));
            exit;
        }

        $ok = $this->announcementModel->deleteAttachmentSecure($attachId, $ownerId);
        set_flash($ok ? 'success' : 'fail', $ok ? 'Attachment removed.' : 'Unable to remove attachment.');

        header('Location: ' . ($returnTo ?: '/announcements'));
        exit;
    }

    public function deleteAnnouncement(?string $id = null): void
    {
        // Admin only
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $ownerId = (int) ($_SESSION['accountID'] ?? 0);
        // Accept ID via URL or POST (for robustness)
        $aid = $id !== null ? (int) $id : (int) ($_POST['announcement_id'] ?? 0);

        if ($aid <= 0) {
            set_flash('fail', 'Invalid announcement.');
            header('Location: /announcements');
            exit;
        }

        // Verify ownership + get attachments to remove files
        $ann = $this->announcementModel->getById($aid);
        if (!$ann) {
            set_flash('fail', 'Announcement not found.');
            header('Location: /announcements');
            exit;
        }
        if ((int) ($ann['accountID'] ?? 0) !== $ownerId) {
            set_flash('fail', 'You can only delete your own announcements.');
            header('Location: /announcements');
            exit;
        }

        // Delete physical files (if any)
        $attachments = $this->announcementModel->getAttachmentsByAnnouncement($aid);
        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/announcements/';
        foreach ($attachments as $f) {
            $stored = $f['stored'] ?? '';
            if ($stored !== '') {
                $abs = $uploadDir . $stored;
                if (is_file($abs)) {
                    @unlink($abs);
                } // ignore if already missing
            }
        }

        // Delete DB rows (attachments + announcement) in one transaction
        $ok = $this->announcementModel->deleteAnnouncementSecure($aid, $ownerId);

        set_flash($ok ? 'success' : 'fail', $ok ? 'Announcement deleted.' : 'Delete failed.');
        header('Location: /announcements');
        exit;
    }

    public function viewAnnouncementForStudentAndNominee(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if (!in_array($role, ['STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'Only students and nominees can view this page.');
            header('Location: /login');
            exit;
        }

        $announcements = $this->announcementModel->listPublishedAnnouncement();
        include $this->fileHelper->getFilePath('StudentNomineeAnnouncementList');
    }

    public function viewAnnouncementDetailsForStudentAndNominee(string $id): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if (!in_array($role, ['STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'Only students and nominees can view this page.');
            header('Location: /login');
            exit;
        }

        $aid = (int) $id;
        $ann = $this->announcementModel->getPublishedById($aid);
        if (!$ann) {
            http_response_code(404);
            exit('Announcement not found.');
        }

        $announcement = [
            'id' => $ann['announcementID'],
            'title' => $ann['title'],
            'content' => $ann['content'],
            'senderName' => $ann['senderName'] ?? 'Unknown',
            'publishedAt' => $ann['publishedAt'],
            'attachments' => $ann['attachments'] ?? [],
        ];

        include $this->fileHelper->getFilePath('StudentNomineeAnnouncementDetails');
    }

}