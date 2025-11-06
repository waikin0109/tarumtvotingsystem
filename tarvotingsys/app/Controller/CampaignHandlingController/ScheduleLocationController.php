<?php

namespace Controller\CampaignHandlingController;

use Model\CampaignHandlingModel\ScheduleLocationModel;
use FileHelper;
use DateTime;
use DateTimeZone;

class ScheduleLocationController
{
    private ScheduleLocationModel $scheduleLocationModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->scheduleLocationModel = new ScheduleLocationModel();
        $this->fileHelper            = new FileHelper('schedule_location');
    }

    // ------------------ List ------------------ //
    public function listScheduleLocations(): void
    {
        $scheduleLocations = $this->scheduleLocationModel->getAllScheduleLocations();
        $filePath = $this->fileHelper->getFilePath('ScheduleLocationList');

        if ($filePath && file_exists($filePath)) {
            include $filePath; // exposes $scheduleLocations
        } else {
            echo "View file not found.";
        }
    }

    // ------------------ Create (GET) ------------------ //
    public function createScheduleLocation(): void
    {
        $errors = [];
        $fieldErrors = [];
        $old = [
            'electionID'      => $_GET['electionID']      ?? '',
            'nomineeID'       => $_GET['nomineeID']       ?? '',
            'eventName'       => $_GET['eventName']       ?? '',
            'eventType'       => $_GET['eventType']       ?? '',
            'desiredDateTime' => $_GET['desiredDateTime'] ?? '',
        ];

        $elections = $this->scheduleLocationModel->getEligibleElections();
        $nominees  = (!empty($old['electionID']) && ctype_digit((string)$old['electionID']))
            ? $this->scheduleLocationModel->getNomineesByElection((int)$old['electionID'])
            : [];

        $filePath = $this->fileHelper->getFilePath('CreateScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
            return;
        }
        echo "View file not found.";
    }

    // ------------------ Create (POST) ------------------ //
    public function storeCreateScheduleLocation(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $this->createScheduleLocation(); return; }

        $old = [
            'electionID'      => trim((string)($_POST['electionID'] ?? '')),
            'nomineeID'       => trim((string)($_POST['nomineeID'] ?? '')),
            'eventName'       => trim((string)($_POST['eventName'] ?? '')),
            'eventType'       => trim((string)($_POST['eventType'] ?? '')),
            'desiredDateTime' => trim((string)($_POST['desiredDateTime'] ?? '')),
        ];

        $errors = [];
        $fieldErrors = ['electionID'=>[], 'nomineeID'=>[], 'eventName'=>[], 'eventType'=>[], 'desiredDateTime'=>[]];

        foreach ($fieldErrors as $k => $_) if ($old[$k] === '') $fieldErrors[$k][] = 'This field is required.';

        $elections = $this->scheduleLocationModel->getEligibleElections();
        $eligibleElectionIds = array_column($elections, 'electionID');

        $electionId = ctype_digit((string)$old['electionID']) ? (int)$old['electionID'] : 0;
        if (!$electionId || !in_array($electionId, $eligibleElectionIds, true)) {
            $fieldErrors['electionID'][] = 'Please select a valid eligible election.';
        }

        // extra guard: election not ended now
        $tz  = new DateTimeZone('Asia/Kuala_Lumpur');
        $now = new DateTime('now', $tz);
        if ($electionId) {
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($now > $electionEnd) $fieldErrors['electionID'][] = 'This election has ended and cannot accept schedule locations.';
            } else {
                $errors[] = 'Election end date not found for the selected election.';
            }
        }

        $nominees   = $electionId ? $this->scheduleLocationModel->getNomineesByElection($electionId) : [];
        $nomineeIds = array_column($nominees, 'nomineeID');
        $nomineeId  = ctype_digit((string)$old['nomineeID']) ? (int)$old['nomineeID'] : 0;
        if (!$nomineeId || !in_array($nomineeId, $nomineeIds, true)) {
            $fieldErrors['nomineeID'][] = 'Please choose a nominee from the selected election.';
        }

        if (!in_array($old['eventType'], ['CAMPAIGN','DEBATE'], true)) {
            $fieldErrors['eventType'][] = 'Invalid event type.';
        }

        $tz      = new DateTimeZone('Asia/Kuala_Lumpur');
        $now     = new DateTime('now', $tz);
        $desired = null;
        if ($old['desiredDateTime'] !== '') {
            $desired = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredDateTime'], $tz)
                    ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredDateTime'], $tz);
            if (!$desired) $fieldErrors['desiredDateTime'][] = 'Invalid date/time format.';
        }

        if ($desired && $electionId) {
            $win = $this->scheduleLocationModel->getRegistrationWindow($electionId);
            if (!$win || empty($win['endAt'])) {
                $errors[] = 'Registration window not found for the selected election.';
            } else {
                $regEnd = new DateTime($win['endAt'], $tz);
                if ($desired <= $regEnd) $fieldErrors['desiredDateTime'][] = 'Must be after registration closing.';
                if ($desired <= $now)    $fieldErrors['desiredDateTime'][] = 'Must be in the future.';
            }

            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if (!$endStr) {
                $errors[] = 'Election end date not found for the selected election.';
            } else {
                $electionEnd = new DateTime($endStr, $tz);
                if ($desired > $electionEnd) $fieldErrors['desiredDateTime'][] = 'Must be on or before the election end date & time.';
            }
        }

        $hasErrors = !empty($errors) || array_reduce($fieldErrors, fn($c,$a)=>$c||!empty($a), false);
        if ($hasErrors) {
            $filePath = $this->fileHelper->getFilePath('CreateScheduleLocation');
            if ($filePath && file_exists($filePath)) { include $filePath; return; }
            echo "View file not found."; return;
        }

        $accountId = (int)($_SESSION['accountID'] ?? 0);
        $adminId   = $this->scheduleLocationModel->getAdminIdByAccount($accountId) ?? ($_SESSION['adminID'] ?? null);

        $ok = $this->scheduleLocationModel->createScheduleLocation([
            'eventName'       => $old['eventName'],
            'eventType'       => $old['eventType'],
            'desiredDateTime' => $desired->format('Y-m-d H:i:00'),
            'adminID'         => $adminId,
            'nomineeID'       => $nomineeId,
            'electionID'      => $electionId,
        ]);

        if ($ok) { \set_flash('success','Schedule Location created successfully.'); header('Location: /schedule-location'); exit; }

        \set_flash('fail','Failed to create Schedule Location.');
        $filePath = $this->fileHelper->getFilePath('CreateScheduleLocation');
        if ($filePath && file_exists($filePath)) { include $filePath; return; }
        echo "View file not found.";
    }

    // ------------------ Edit (GET) ------------------ //
    public function editScheduleLocation($eventApplicationID): void
    {
        $row = $this->scheduleLocationModel->getScheduleLocationById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /schedule-location'); exit;
        }

        $errors = [];
        $fieldErrors = [];

        // Pre-fill "old" from DB; convert desiredDateTime to datetime-local
        $old = [
            'eventName'       => (string)($row['eventName'] ?? ''),
            'eventType'       => (string)($row['eventType'] ?? ''),
            'desiredDateTime' => '',
        ];
        if (!empty($row['desiredDateTime'])) {
            $dt = new DateTime($row['desiredDateTime'], new DateTimeZone('Asia/Kuala_Lumpur'));
            $old['desiredDateTime'] = $dt->format('Y-m-d\TH:i');
        }

        // Display-only info
        $display = [
            'electionTitle' => (string)($row['electionTitle'] ?? ''),
            'nomineeLabel'  => (string)($row['nomineeFullName'] ?? '') . ' (ID ' . (int)($row['studentID'] ?? 0) . ')',
            'electionID'    => (int)($row['electionID'] ?? 0),
            'nomineeID'     => (int)($row['nomineeID'] ?? 0),
            'eventApplicationID' => (int)$row['eventApplicationID'],
        ];

        $filePath = $this->fileHelper->getFilePath('EditScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            $scheduleLocationData = $display; // if your view expects this name
            include $filePath; // expects $errors, $fieldErrors, $old, $scheduleLocationData
            return;
        }
        echo "View file not found.";
    }

    // ------------------ Edit (POST) ------------------ //
    public function storeEditScheduleLocation($eventApplicationID): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $this->editScheduleLocation($eventApplicationID); return; }

        $row = $this->scheduleLocationModel->getScheduleLocationById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /schedule-location'); exit;
        }

        // Election & nominee are fixed (display-only)
        $electionId = (int)$row['electionID'];
        $nomineeId  = (int)$row['nomineeID'];

        $old = [
            'eventName'       => trim((string)($_POST['eventName'] ?? '')),
            'eventType'       => trim((string)($_POST['eventType'] ?? '')),
            'desiredDateTime' => trim((string)($_POST['desiredDateTime'] ?? '')),
        ];

        $errors = [];
        $fieldErrors = ['eventName'=>[], 'eventType'=>[], 'desiredDateTime'=>[]];

        foreach ($fieldErrors as $k => $_) if ($old[$k] === '') $fieldErrors[$k][] = 'This field is required.';

        if (!in_array($old['eventType'], ['CAMPAIGN','DEBATE'], true)) {
            $fieldErrors['eventType'][] = 'Invalid event type.';
        }

        // Same datetime rules as create
        $tz      = new DateTimeZone('Asia/Kuala_Lumpur');
        $now     = new DateTime('now', $tz);
        $desired = null;
        if ($old['desiredDateTime'] !== '') {
            $desired = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredDateTime'], $tz)
                    ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredDateTime'], $tz);
            if (!$desired) $fieldErrors['desiredDateTime'][] = 'Invalid date/time format.';
        }

        if ($desired) {
            // election not ended now
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($now > $electionEnd) $errors[] = 'This election has ended; editing date/time is not allowed.';
                if ($desired > $electionEnd) $fieldErrors['desiredDateTime'][] = 'Must be on or before the election end date & time.';
            } else {
                $errors[] = 'Election end date not found for the selected election.';
            }

            // after registration close, and in future
            $win = $this->scheduleLocationModel->getRegistrationWindow($electionId);
            if (!$win || empty($win['endAt'])) {
                $errors[] = 'Registration window not found for the selected election.';
            } else {
                $regEnd = new DateTime($win['endAt'], $tz);
                if ($desired <= $regEnd) $fieldErrors['desiredDateTime'][] = 'Must be after registration closing.';
                if ($desired <= $now)    $fieldErrors['desiredDateTime'][] = 'Must be in the future.';
            }
        }

        $hasErrors = !empty($errors) || array_reduce($fieldErrors, fn($c,$a)=>$c||!empty($a), false);
        if ($hasErrors) {
            // recreate display-only bundle
            $scheduleLocationData = [
                'eventApplicationID' => (int)$row['eventApplicationID'],
                'electionTitle'      => (string)$row['electionTitle'],
                'nomineeLabel'       => (string)$row['nomineeFullName'] . ' (ID ' . (int)$row['studentID'] . ')',
            ];
            $filePath = $this->fileHelper->getFilePath('EditScheduleLocation');
            if ($filePath && file_exists($filePath)) { include $filePath; return; }
            echo "View file not found."; return;
        }

        $ok = $this->scheduleLocationModel->updateScheduleLocation((int)$eventApplicationID, [
            'eventName'       => $old['eventName'],
            'eventType'       => $old['eventType'],
            'desiredDateTime' => $desired->format('Y-m-d H:i:00'),
        ]);

        if ($ok) {
            \set_flash('success', 'Schedule Location updated successfully.');
            header('Location: /schedule-location'); exit;
        }

        \set_flash('fail', 'Failed to update Schedule Location.');
        $this->editScheduleLocation($eventApplicationID);
    }

    // --------------------------------- View -----------------------------------//
    public function viewScheduleLocation($eventApplicationID): void
    {
        $row = $this->scheduleLocationModel->getScheduleLocationDetailsById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /schedule-location'); exit;
        }

        // Format datetime for display
        $dtFmt = '';
        if (!empty($row['desiredDateTime'])) {
            $tz = new \DateTimeZone('Asia/Kuala_Lumpur');
            $dt = new \DateTime($row['desiredDateTime'], $tz);
            $dtFmt = $dt->format('d M Y, h:i A'); // e.g. 06 Nov 2025, 04:30 PM
        }

        // Badge class
        $status = strtoupper((string)($row['eventApplicationStatus'] ?? 'PENDING'));
        $badgeClass = match ($status) {
            'APPROVED' => 'bg-success',
            'REJECTED' => 'bg-danger',
            'PENDING'  => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };

        // Pack a simple view model
        $vm = [
            'id'            => (int)$row['eventApplicationID'],
            'eventName'     => (string)$row['eventName'],
            'electionTitle' => (string)$row['electionTitle'],
            'eventType'     => (string)$row['eventType'],
            'desiredAt'     => $dtFmt,
            'status'        => (string)$row['eventApplicationStatus'],
            'badgeClass'    => $badgeClass,
            'adminName'     => (string)($row['adminFullName']   ?? '—'),
            'nomineeName'   => (string)($row['nomineeFullName'] ?? '—'),
        ];

        $filePath = $this->fileHelper->getFilePath('ViewScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            $schedule = $vm; // expose as $schedule to the view
            include $filePath;
            return;
        }
        echo "View file not found.";
    }

}
