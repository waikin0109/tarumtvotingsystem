<?php

namespace Controller\CampaignHandlingController;

use Model\CampaignHandlingModel\ScheduleLocationModel;
use Model\VotingModel\ElectionEventModel;
use FileHelper;
use DateTime;
use DateTimeZone;

class ScheduleLocationController
{
    private ScheduleLocationModel $scheduleLocationModel;
    private ElectionEventModel $electionEventModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->scheduleLocationModel = new ScheduleLocationModel();
        $this->electionEventModel    = new ElectionEventModel();
        $this->fileHelper            = new FileHelper('schedule_location');
    }

    // Role Decision Area
    private function requireRole(...$allowed)
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if (!in_array($role, $allowed, true)) {
            \set_flash('fail', 'You do not have permission to access this page.');
            $this->redirectByRole($role);
        }
    }

    private function redirectByRole($role)
    {
        switch ($role) {
            case 'ADMIN':   
                header('Location: /admin/schedule-location'); 
                break;
            case 'STUDENT': 
                header('Location: /student/schedule-location'); 
                break;
            case 'NOMINEE': 
                header('Location: /nominee/schedule-location'); 
                break;
            default:        
                header('Location: /login'); 
                break;
        }
        exit;
    }

    // ------------------ List ------------------ //
    public function listScheduleLocations(): void
    {
        $this->requireRole('ADMIN');
        $scheduleLocations = $this->scheduleLocationModel->getAllScheduleLocations();
        $filePath = $this->fileHelper->getFilePath('ScheduleLocationList');

        if ($filePath && file_exists($filePath)) {
            include $filePath; 
        } else {
            echo "View file not found.";
        }
    }

    public function listScheduleLocationsStudent(): void
    {
        $this->requireRole('STUDENT');
        $scheduleLocations = $this->electionEventModel->getAllPublishedElectionEvents();
        $filePath = $this->fileHelper->getFilePath('ScheduleLocationListStudent');

        if ($filePath && file_exists($filePath)) {
            include $filePath; 
        } else {
            echo "View file not found.";
        }
    }

    public function listScheduleLocationsNominee(): void
    {
        $this->requireRole('NOMINEE');
        $scheduleLocations = $this->electionEventModel->getAllPublishedElectionEvents();
        $filePath = $this->fileHelper->getFilePath('ScheduleLocationListStudent');

        if ($filePath && file_exists($filePath)) {
            include $filePath; 
        } else {
            echo "View file not found.";
        }
    }

    // ------------------ Create (GET) ------------------ //
    public function createScheduleLocation(): void
    {
        $this->requireRole('ADMIN');
        $errors = [];
        $fieldErrors = [];
        $old = [
            'electionID'           => $_GET['electionID']           ?? '',
            'nomineeID'            => $_GET['nomineeID']            ?? '',
            'eventName'            => $_GET['eventName']            ?? '',
            'eventType'            => $_GET['eventType']            ?? '',
            'desiredStartDateTime' => $_GET['desiredStartDateTime'] ?? '',
            'desiredEndDateTime'   => $_GET['desiredEndDateTime']   ?? ''
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
        $this->requireRole('ADMIN');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $this->createScheduleLocation(); return; }

        $old = [
            'electionID'           => trim((string)($_POST['electionID'] ?? '')),
            'nomineeID'            => trim((string)($_POST['nomineeID'] ?? '')),
            'eventName'            => trim((string)($_POST['eventName'] ?? '')),
            'eventType'            => trim((string)($_POST['eventType'] ?? '')),
            'desiredStartDateTime' => trim((string)($_POST['desiredStartDateTime'] ?? '')),
            'desiredEndDateTime'   => trim((string)($_POST['desiredEndDateTime'] ?? '')),
        ];

        $errors = [];
        $fieldErrors = [
            'electionID'=>[], 'nomineeID'=>[], 'eventName'=>[], 'eventType'=>[], 'desiredStartDateTime'=>[], 'desiredEndDateTime'=>[]
        ];

        foreach (['electionID','nomineeID','eventName','eventType','desiredStartDateTime','desiredEndDateTime'] as $k) {
            if ($old[$k] === '') $fieldErrors[$k][] = 'This field is required.';
        }

        $elections = $this->scheduleLocationModel->getEligibleElections();
        $eligibleElectionIds = array_map('intval', array_column($elections, 'electionID'));

        $electionId = ctype_digit((string)$old['electionID']) ? (int)$old['electionID'] : 0;
        if (!$electionId || !in_array($electionId, $eligibleElectionIds, true)) {
            $fieldErrors['electionID'][] = 'Please select a valid eligible election.';
        }

        $tz  = new DateTimeZone('Asia/Kuala_Lumpur');
        $now = new DateTime('now', $tz);

        // Validate election hasn't ended
        if ($electionId) {
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($now > $electionEnd) $fieldErrors['electionID'][] = 'This election has ended and cannot accept schedule locations.';
            } else {
                $errors[] = 'Election end date not found for the selected election.';
            }
        }

        // Nominee belongs to election
        $nominees   = $electionId ? $this->scheduleLocationModel->getNomineesByElection($electionId) : [];
        $nomineeIds = array_map('intval', array_column($nominees, 'nomineeID'));
        $nomineeId  = ctype_digit((string)$old['nomineeID']) ? (int)$old['nomineeID'] : 0;
        if (!$nomineeId || !in_array($nomineeId, $nomineeIds, true)) {
            $fieldErrors['nomineeID'][] = 'Please choose a nominee from the selected election.';
        }

        if (!in_array($old['eventType'], ['CAMPAIGN','DEBATE'], true)) {
            $fieldErrors['eventType'][] = 'Invalid event type.';
        }

        // Parse datetimes
        $start = null; $end = null;
        if ($old['desiredStartDateTime'] !== '') {
            $start = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredStartDateTime'], $tz)
                ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredStartDateTime'], $tz);
            if (!$start) $fieldErrors['desiredStartDateTime'][] = 'Invalid date/time format.';
        }
        if ($old['desiredEndDateTime'] !== '') {
            $end = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredEndDateTime'], $tz)
                ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredEndDateTime'], $tz);
            if (!$end) $fieldErrors['desiredEndDateTime'][] = 'Invalid date/time format.';
        }

        // Business rules: start/end window
        if ($start && $electionId) {
            $win = $this->scheduleLocationModel->getRegistrationWindow($electionId);
            if (!$win || empty($win['endAt'])) {
                $errors[] = 'Registration window not found for the selected election.';
            } else {
                $regEnd = new DateTime($win['endAt'], $tz);
                if ($start <= $regEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be after registration closing.';
                if ($start <= $now)    $fieldErrors['desiredStartDateTime'][] = 'Start must be in the future.';
            }
        }
        if ($start && $end) {
            // End ≥ start + 1 hour
            $minEnd = (clone $start)->modify('+1 hour');
            if ($end < $minEnd) {
                $fieldErrors['desiredEndDateTime'][] = 'End time must be at least 1 hour after start.';
            }
        }
        if (($start || $end) && $electionId) {
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($start && $start > $electionEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be on or before the election end.';
                if ($end   && $end   > $electionEnd) $fieldErrors['desiredEndDateTime'][]   = 'End must be on or before the election end.';
            }
        }

        $hasErrors = !empty($errors) || array_reduce($fieldErrors, fn($c,$a)=>$c||!empty($a), false);
        if ($hasErrors) {
            $elections = $this->scheduleLocationModel->getEligibleElections();
            $nominees  = $electionId ? $this->scheduleLocationModel->getNomineesByElection($electionId) : [];
            $filePath = $this->fileHelper->getFilePath('CreateScheduleLocation');
            if ($filePath && file_exists($filePath)) { include $filePath; return; }
            echo "View file not found."; return;
        }

        $ok = $this->scheduleLocationModel->createScheduleLocation([
            'eventName'            => $old['eventName'],
            'eventType'            => $old['eventType'],
            'desiredStartDateTime' => $start->format('Y-m-d H:i:00'),
            'desiredEndDateTime'   => $end->format('Y-m-d H:i:00'),
            'nomineeID'            => $nomineeId,
            'electionID'           => $electionId,
        ]);

        if ($ok) { \set_flash('success','Schedule Location created successfully.'); header('Location: /admin/schedule-location'); exit; }

        \set_flash('fail','Failed to create Schedule Location.');
        $filePath = $this->fileHelper->getFilePath('CreateScheduleLocation');
        if ($filePath && file_exists($filePath)) { include $filePath; return; }
        echo "View file not found.";
    }


    // ------------------ Edit (GET) ------------------ //
    public function editScheduleLocation($eventApplicationID): void
    {
        $this->requireRole('ADMIN');
        $row = $this->scheduleLocationModel->getScheduleLocationById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /admin/schedule-location'); exit;
        }

        $errors = [];
        $fieldErrors = [];

        // Pre-fill "old" from DB; convert desiredDateTime to datetime-local
        $old = [
            'eventName'            => (string)($row['eventName'] ?? ''),
            'eventType'            => (string)($row['eventType'] ?? ''),
            'desiredStartDateTime' => '',
            'desiredEndDateTime'   => '',
        ];
        if (!empty($row['desiredStartDateTime'])) {
            $dt = new DateTime($row['desiredStartDateTime'], new DateTimeZone('Asia/Kuala_Lumpur'));
            $old['desiredStartDateTime'] = $dt->format('Y-m-d\TH:i');
        }
        if (!empty($row['desiredEndDateTime'])) {
            $dt2 = new DateTime($row['desiredEndDateTime'], new DateTimeZone('Asia/Kuala_Lumpur'));
            $old['desiredEndDateTime'] = $dt2->format('Y-m-d\TH:i');
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
        $this->requireRole('ADMIN');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $this->editScheduleLocation($eventApplicationID); return; }

        $row = $this->scheduleLocationModel->getScheduleLocationById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /admin/schedule-location'); exit;
        }

        $electionId = (int)$row['electionID'];
        $nomineeId  = (int)$row['nomineeID'];

        $old = [
            'eventName'            => trim((string)($_POST['eventName'] ?? '')),
            'eventType'            => trim((string)($_POST['eventType'] ?? '')),
            'desiredStartDateTime' => trim((string)($_POST['desiredStartDateTime'] ?? '')),
            'desiredEndDateTime'   => trim((string)($_POST['desiredEndDateTime'] ?? '')),
        ];

        $errors = [];
        $fieldErrors = ['eventName'=>[], 'eventType'=>[], 'desiredStartDateTime'=>[], 'desiredEndDateTime'=>[]];

        foreach ($fieldErrors as $k => $_) if ($old[$k] === '') $fieldErrors[$k][] = 'This field is required.';
        if (!in_array($old['eventType'], ['CAMPAIGN','DEBATE'], true)) $fieldErrors['eventType'][] = 'Invalid event type.';

        $tz  = new DateTimeZone('Asia/Kuala_Lumpur');
        $now = new DateTime('now', $tz);
        $start = null; $end = null;

        if ($old['desiredStartDateTime'] !== '') {
            $start = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredStartDateTime'], $tz)
                ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredStartDateTime'], $tz);
            if (!$start) $fieldErrors['desiredStartDateTime'][] = 'Invalid date/time format.';
        }
        if ($old['desiredEndDateTime'] !== '') {
            $end = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredEndDateTime'], $tz)
                ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredEndDateTime'], $tz);
            if (!$end) $fieldErrors['desiredEndDateTime'][] = 'Invalid date/time format.';
        }

        // Election not ended; start after registration close; future; end ≥ start + 1h; both ≤ electionEnd
        if ($start) {
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($now > $electionEnd) $errors[] = 'This election has ended; editing is not allowed.';
                if ($start > $electionEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be on or before the election end.';
            } else {
                $errors[] = 'Election end date not found for the selected election.';
            }

            $win = $this->scheduleLocationModel->getRegistrationWindow($electionId);
            if (!$win || empty($win['endAt'])) {
                $errors[] = 'Registration window not found for the selected election.';
            } else {
                $regEnd = new DateTime($win['endAt'], $tz);
                if ($start <= $regEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be after registration closing.';
                if ($start <= $now)    $fieldErrors['desiredStartDateTime'][] = 'Start must be in the future.';
            }
        }
        if ($start && $end) {
            $minEnd = (clone $start)->modify('+1 hour');
            if ($end < $minEnd) $fieldErrors['desiredEndDateTime'][] = 'End time must be at least 1 hour after start.';
            $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
            if ($endStr) {
                $electionEnd = new DateTime($endStr, $tz);
                if ($end > $electionEnd) $fieldErrors['desiredEndDateTime'][] = 'End must be on or before the election end.';
            }
        }

        $hasErrors = !empty($errors) || array_reduce($fieldErrors, fn($c,$a)=>$c||!empty($a), false);
        if ($hasErrors) {
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
            'eventName'            => $old['eventName'],
            'eventType'            => $old['eventType'],
            'desiredStartDateTime' => $start->format('Y-m-d H:i:00'),
            'desiredEndDateTime'   => $end->format('Y-m-d H:i:00'),
        ]);

        if ($ok) { \set_flash('success', 'Schedule Location updated successfully.'); header('Location: /admin/schedule-location'); exit; }

        \set_flash('fail', 'Failed to update Schedule Location.');
        $this->editScheduleLocation($eventApplicationID);
    }

    // --------------------------------- View -----------------------------------//
    public function viewScheduleLocation($eventApplicationID): void
    {
        $this->requireRole('ADMIN');
        $row = $this->scheduleLocationModel->getScheduleLocationDetailsById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /admin/schedule-location'); exit;
        }

        $tz = new \DateTimeZone('Asia/Kuala_Lumpur');
        $fmt = 'd M Y, h:i A';

        $startFmt = '';
        if (!empty($row['desiredStartDateTime'])) {
            $startFmt = (new \DateTime($row['desiredStartDateTime'], $tz))->format($fmt);
        }

        $endFmt = '';
        if (!empty($row['desiredEndDateTime'])) {
            $endFmt = (new \DateTime($row['desiredEndDateTime'], $tz))->format($fmt);
        }

        $submittedFmt = '';
        if (!empty($row['eventApplicationSubmittedAt'])) {
            $submittedFmt = (new \DateTime($row['eventApplicationSubmittedAt'], $tz))->format($fmt);
        }

        $status = strtoupper((string)($row['eventApplicationStatus'] ?? 'PENDING'));
        $badgeClass = match ($status) {
            'APPROVED' => 'bg-success',
            'REJECTED' => 'bg-danger',
            'PENDING'  => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };

        $vm = [
            'id'            => (int)$row['eventApplicationID'],
            'eventName'     => (string)$row['eventName'],
            'electionTitle' => (string)$row['electionTitle'],
            'eventType'     => (string)$row['eventType'],
            'desiredStart'  => $startFmt,
            'desiredEnd'    => $endFmt,
            'submittedAt'   => $submittedFmt,
            'status'        => (string)$row['eventApplicationStatus'],
            'badgeClass'    => $badgeClass,
            'adminName'     => (string)($row['adminFullName']   ?? '—'),
            'nomineeName'   => (string)($row['nomineeFullName'] ?? '—'),
        ];

        $filePath = $this->fileHelper->getFilePath('ViewScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            $schedule = $vm;
            include $filePath;
            return;
        }
        echo "View file not found.";
    }

    public function viewScheduleLocationStudent($eventApplicationID): void
    {
        $this->requireRole('STUDENT');
        $row = $this->scheduleLocationModel->getScheduleLocationDetailsById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /student/schedule-location'); exit;
        }

        $tz = new \DateTimeZone('Asia/Kuala_Lumpur');
        $fmt = 'd M Y, h:i A';

        $startFmt = '';
        if (!empty($row['desiredStartDateTime'])) {
            $startFmt = (new \DateTime($row['desiredStartDateTime'], $tz))->format($fmt);
        }

        $endFmt = '';
        if (!empty($row['desiredEndDateTime'])) {
            $endFmt = (new \DateTime($row['desiredEndDateTime'], $tz))->format($fmt);
        }

        $submittedFmt = '';
        if (!empty($row['eventApplicationSubmittedAt'])) {
            $submittedFmt = (new \DateTime($row['eventApplicationSubmittedAt'], $tz))->format($fmt);
        }

        $status = strtoupper((string)($row['eventApplicationStatus'] ?? 'PENDING'));
        $badgeClass = match ($status) {
            'APPROVED' => 'bg-success',
            'REJECTED' => 'bg-danger',
            'PENDING'  => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };

        $vm = [
            'id'            => (int)$row['eventApplicationID'],
            'eventName'     => (string)$row['eventName'],
            'electionTitle' => (string)$row['electionTitle'],
            'eventType'     => (string)$row['eventType'],
            'desiredStart'  => $startFmt,
            'desiredEnd'    => $endFmt,
            'submittedAt'   => $submittedFmt,
            'status'        => (string)$row['eventApplicationStatus'],
            'badgeClass'    => $badgeClass,
            'adminName'     => (string)($row['adminFullName']   ?? '—'),
            'nomineeName'   => (string)($row['nomineeFullName'] ?? '—'),
        ];

        $filePath = $this->fileHelper->getFilePath('ViewScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            $schedule = $vm;
            include $filePath;
            return;
        }
        echo "View file not found.";
    }

    public function viewScheduleLocationNominee($eventApplicationID): void
    {
        $this->requireRole('NOMINEE');
        $row = $this->scheduleLocationModel->getScheduleLocationDetailsById((int)$eventApplicationID);
        if (!$row) {
            \set_flash('fail', 'Schedule Location not found.');
            header('Location: /nominee/schedule-location'); exit;
        }

        $tz = new \DateTimeZone('Asia/Kuala_Lumpur');
        $fmt = 'd M Y, h:i A';

        $startFmt = '';
        if (!empty($row['desiredStartDateTime'])) {
            $startFmt = (new \DateTime($row['desiredStartDateTime'], $tz))->format($fmt);
        }

        $endFmt = '';
        if (!empty($row['desiredEndDateTime'])) {
            $endFmt = (new \DateTime($row['desiredEndDateTime'], $tz))->format($fmt);
        }

        $submittedFmt = '';
        if (!empty($row['eventApplicationSubmittedAt'])) {
            $submittedFmt = (new \DateTime($row['eventApplicationSubmittedAt'], $tz))->format($fmt);
        }

        $status = strtoupper((string)($row['eventApplicationStatus'] ?? 'PENDING'));
        $badgeClass = match ($status) {
            'APPROVED' => 'bg-success',
            'REJECTED' => 'bg-danger',
            'PENDING'  => 'bg-warning text-dark',
            default    => 'bg-secondary',
        };

        $vm = [
            'id'            => (int)$row['eventApplicationID'],
            'eventName'     => (string)$row['eventName'],
            'electionTitle' => (string)$row['electionTitle'],
            'eventType'     => (string)$row['eventType'],
            'desiredStart'  => $startFmt,
            'desiredEnd'    => $endFmt,
            'submittedAt'   => $submittedFmt,
            'status'        => (string)$row['eventApplicationStatus'],
            'badgeClass'    => $badgeClass,
            'adminName'     => (string)($row['adminFullName']   ?? '—'),
            'nomineeName'   => (string)($row['nomineeFullName'] ?? '—'),
        ];

        $filePath = $this->fileHelper->getFilePath('ViewScheduleLocation');
        if ($filePath && file_exists($filePath)) {
            $schedule = $vm;
            include $filePath;
            return;
        }
        echo "View file not found.";
    }


    // ------------------ Schedule Board (GET) ------------------ //
public function scheduleBoard(): void
{
    $this->requireRole('ADMIN');
    // Only PENDING, election not ended, ordered by submittedAt ASC
    $queue = $this->scheduleLocationModel->getPendingEventApplications();

    // Locations master list (AVAILABLE only)
    $locations = $this->scheduleLocationModel->getAllEventLocations(true);

    $filePath = $this->fileHelper->getFilePath('ScheduleBoard');
    if ($filePath && file_exists($filePath)) {
        include $filePath; // exposes $queue, $locations
        return;
    }
    echo "View file not found.";
}

// ------------------ Accept (POST) ------------------ //
public function scheduleAccept(string $eventApplicationID): void
{
    $this->requireRole('ADMIN');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: /admin/schedule-location/schedule'); return; }

    $eaid = (int)$eventApplicationID;
    $loc  = (int)($_POST['eventLocationID'] ?? 0);
    if ($eaid <= 0 || $loc <= 0) {
        \set_flash('fail', 'Please choose a location.');
        header('Location: /admin/schedule-location/schedule'); return;
    }

    // Per your request: hard-code admin ID = 1 for now
    $adminId = $_SESSION['roleID'];

    $ok = $this->scheduleLocationModel->acceptApplicationWithLocation($eaid, $loc, $adminId);

    if ($ok) {
        \set_flash('success', 'Event scheduled and application accepted.');
    } else {
        \set_flash('fail', 'Time/location conflict or save failed. Pick another slot/location.');
    }
    header('Location: /admin/schedule-location/schedule');
}

// ------------------ Reject (POST) ------------------ //
public function scheduleReject(string $eventApplicationID): void
{
    $this->requireRole('ADMIN');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: /admin/schedule-location/schedule'); return; }

    $eaid = (int)$eventApplicationID;
    $adminId = $_SESSION['roleID'];

    $ok = $this->scheduleLocationModel->rejectApplication($eaid, $adminId);
    if ($ok) {
        \set_flash('success', 'Application rejected.');
    } else {
        \set_flash('fail', 'Failed to reject application.');
    }
    header('Location: /admin/schedule-location/schedule');
}

public function scheduleUnschedule(string $eventApplicationID): void
{
    $this->requireRole('ADMIN');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); echo 'Method Not Allowed'; return; }

    $eaid = (int)$eventApplicationID;
    if ($eaid <= 0) { http_response_code(400); echo 'Bad Request'; return; }

    $ok = $this->scheduleLocationModel->unscheduleToPending($eaid);
    if ($ok) {
        // support both form and fetch callers
        if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json'); echo json_encode(['ok'=>true]); return;
        }
        \set_flash('success','Event unscheduled. Application is back to PENDING.');
        header('Location: /admin/schedule-location/schedule'); return;
    }

    if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        http_response_code(409); header('Content-Type: application/json');
        echo json_encode(['ok'=>false,'error'=>'Failed to unschedule.']); return;
    }
    \set_flash('fail','Failed to unschedule.'); header('Location: /admin/schedule-location/schedule');
}

/** POST /schedule-location/accept-back/{id} -> accept a REJECTED or PENDING item with chosen location */
public function scheduleAcceptBack(string $eventApplicationID): void
{
    $this->requireRole('ADMIN');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Location: /schedule-location/schedule'); return;
    }

    $eaid = (int)$eventApplicationID;
    $loc  = (int)($_POST['eventLocationID'] ?? 0);
    if ($eaid <= 0 || $loc <= 0) {
        \set_flash('fail', 'Pick a location.');
        header('Location: /admin/schedule-location/schedule'); return;
    }

    // Load the row to know its status & election window
    $row = $this->scheduleLocationModel->getScheduleLocationDetailsById($eaid);
    if (!$row) {
        \set_flash('fail','Application not found.');
        header('Location: /admin/schedule-location/schedule'); return;
    }

    // Election must not be over
    $endStr = $this->scheduleLocationModel->getElectionEndDate((int)$row['electionID']);
    if ($endStr) {
        $tz = new \DateTimeZone('Asia/Kuala_Lumpur');
        $now = new \DateTime('now', $tz);
        $electionEnd = new \DateTime($endStr, $tz);
        if ($now > $electionEnd) {
            \set_flash('fail','Election ended; cannot accept.');
            header('Location: /admin/schedule-location/schedule'); return;
        }
    }

    // If REJECTED, move back to PENDING first, then accept
    $status = strtoupper((string)($row['eventApplicationStatus'] ?? ''));
    if ($status === 'REJECTED') {
        $okPending = $this->scheduleLocationModel->markPendingIfRejected($eaid, $_SESSION['roleID']);
        if (!$okPending) {
            \set_flash('fail','Could not re-open the application.'); 
            header('Location: /admin/schedule-location/schedule'); return;
        }
    } elseif ($status !== 'PENDING') {
        \set_flash('fail','Only PENDING or REJECTED applications can be accepted.');
        header('Location: /admin/schedule-location/schedule'); return;
    }

    // Try to accept + schedule (conflict-checked in the model)
    $ok = $this->scheduleLocationModel->acceptApplicationWithLocation($eaid, $loc, $_SESSION['roleID']);

    if ($ok) { \set_flash('success','Application accepted and scheduled.'); }
    else     { \set_flash('fail','Conflict or save failed. Pick another slot/location.'); }

    header('Location: /admin/schedule-location/schedule');
}



// ------------------ Calendar feed (JSON) ------------------ //
public function calendarFeed(): void
{
    $eid = isset($_GET['electionID']) ? (int)$_GET['electionID'] : 0;

    $rows = $this->scheduleLocationModel->getCalendarEvents($eid ?: null);

    header('Content-Type: application/json');
    echo json_encode($rows);
}


// ------------------ Final Schedule (Calendar-only) ------------------ //
public function viewCampaignSchedule(): void
{
    $this->requireRole('ADMIN');
    $filePath = $this->fileHelper->getFilePath('ViewCampaignSchedule'); 
    if ($filePath && file_exists($filePath)) {
        include $filePath;
        return;
    }
    echo "View file not found.";
}

    public function viewCampaignScheduleStudent(int $electionID): void
    {
        $this->requireRole('STUDENT');
        $election = $this->electionEventModel->getElectionEventById($electionID);
        if (!$election) {
            \set_flash('fail', 'Election not found.');
            // back to the list page depending on role
            $roleUpper = strtoupper($_SESSION['role'] ?? '');
            $back = ($roleUpper === 'NOMINEE')
                ? '/nominee/schedule-location'
                : '/student/schedule-location';
            header("Location: $back");
            exit;
        }

        // expose these variables to the view
        $electionId    = (int)$election['electionID'];
        $electionTitle = (string)$election['title'];

        $filePath = $this->fileHelper->getFilePath('ViewCampaignScheduleStudent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
            return;
        }

        echo "View file not found.";
    }

    public function viewCampaignScheduleNominee(int $electionID): void
    {
        $this->requireRole('NOMINEE');
        $election = $this->electionEventModel->getElectionEventById($electionID);
        if (!$election) {
            \set_flash('fail', 'Election not found.');
            // back to the list page depending on role
            $roleUpper = strtoupper($_SESSION['role'] ?? '');
            $back = ($roleUpper === 'NOMINEE')
                ? '/nominee/schedule-location'
                : '/student/schedule-location';
            header("Location: $back");
            exit;
        }

        // expose these variables to the view
        $electionId    = (int)$election['electionID'];
        $electionTitle = (string)$election['title'];

        $filePath = $this->fileHelper->getFilePath('ViewCampaignScheduleStudent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
            return;
        }

        echo "View file not found.";
    }

public function createScheduleLocationNominee(): void
{
    $this->requireRole('NOMINEE');

    $accountId = (int)($_SESSION['accountID'] ?? 0);
    if ($accountId <= 0) {
        \set_flash('fail', 'Account not found in session.');
        header('Location: /login');
        exit;
    }

    $errors = [];
    $fieldErrors = [];
    $old = [
        'electionID'           => $_GET['electionID']           ?? '',
        'eventName'            => $_GET['eventName']            ?? '',
        'eventType'            => $_GET['eventType']            ?? '',
        'desiredStartDateTime' => $_GET['desiredStartDateTime'] ?? '',
        'desiredEndDateTime'   => $_GET['desiredEndDateTime']   ?? ''
    ];

    // Only elections where THIS user is PUBLISHED nominee
    $elections = $this->scheduleLocationModel->getEligibleElectionsForNominee($accountId);

    $filePath = $this->fileHelper->getFilePath('CreateScheduleLocationNominee');
    if ($filePath && file_exists($filePath)) {
        include $filePath; // exposes $elections, $errors, $fieldErrors, $old
        return;
    }
    echo "View file not found.";
}

public function storeCreateScheduleLocationNominee(): void
{
    $this->requireRole('NOMINEE');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        $this->createScheduleLocationNominee();
        return;
    }

    if (strtoupper($_SESSION['role'] ?? '') !== 'NOMINEE') {
        \set_flash('fail', 'You do not have permission to perform this action.');
        header('Location: /login');
        exit;
    }

    $accountId = (int)($_SESSION['accountID'] ?? 0);
    if ($accountId <= 0) {
        \set_flash('fail', 'Account not found in session.');
        header('Location: /login');
        exit;
    }

    $old = [
        'electionID'           => trim((string)($_POST['electionID'] ?? '')),
        'eventName'            => trim((string)($_POST['eventName'] ?? '')),
        'eventType'            => trim((string)($_POST['eventType'] ?? '')),
        'desiredStartDateTime' => trim((string)($_POST['desiredStartDateTime'] ?? '')),
        'desiredEndDateTime'   => trim((string)($_POST['desiredEndDateTime'] ?? '')),
    ];

    $errors = [];
    $fieldErrors = [
        'electionID'=>[], 'eventName'=>[], 'eventType'=>[],
        'desiredStartDateTime'=>[], 'desiredEndDateTime'=>[]
    ];

    foreach (['electionID','eventName','eventType','desiredStartDateTime','desiredEndDateTime'] as $k) {
        if ($old[$k] === '') $fieldErrors[$k][] = 'This field is required.';
    }

    // Elections limited to THIS nominee
    $elections = $this->scheduleLocationModel->getEligibleElectionsForNominee($accountId);
    $eligibleElectionIds = array_map('intval', array_column($elections, 'electionID'));

    $electionId = ctype_digit((string)$old['electionID']) ? (int)$old['electionID'] : 0;
    if (!$electionId || !in_array($electionId, $eligibleElectionIds, true)) {
        $fieldErrors['electionID'][] = 'Please select a valid election where you are a published nominee.';
    }

    $tz  = new DateTimeZone('Asia/Kuala_Lumpur');
    $now = new DateTime('now', $tz);

    // Election must not have ended
    if ($electionId) {
        $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
        if ($endStr) {
            $electionEnd = new DateTime($endStr, $tz);
            if ($now > $electionEnd) {
                $fieldErrors['electionID'][] = 'This election has ended and cannot accept schedule locations.';
            }
        } else {
            $errors[] = 'Election end date not found for the selected election.';
        }
    }

    // Resolve nomineeID from session + election
    $nomineeRow = null;
    $nomineeId  = 0;
    if ($electionId) {
        $nomineeRow = $this->scheduleLocationModel->getNomineeForElectionAndAccount($electionId, $accountId);
        if (!$nomineeRow) {
            $errors[] = 'Your nominee record for this election could not be found or is not PUBLISHED.';
        } else {
            $nomineeId = (int)$nomineeRow['nomineeID'];
        }
    }

    if (!in_array($old['eventType'], ['CAMPAIGN','DEBATE'], true)) {
        $fieldErrors['eventType'][] = 'Invalid event type.';
    }

    // Parse datetimes
    $start = null; $end = null;
    if ($old['desiredStartDateTime'] !== '') {
        $start = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredStartDateTime'], $tz)
            ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredStartDateTime'], $tz);
        if (!$start) $fieldErrors['desiredStartDateTime'][] = 'Invalid date/time format.';
    }
    if ($old['desiredEndDateTime'] !== '') {
        $end = DateTime::createFromFormat('Y-m-d\TH:i', $old['desiredEndDateTime'], $tz)
            ?: DateTime::createFromFormat('Y-m-d\TH:i:s', $old['desiredEndDateTime'], $tz);
        if (!$end) $fieldErrors['desiredEndDateTime'][] = 'Invalid date/time format.';
    }

    // Business rules: start/end window
    if ($start && $electionId) {
        $win = $this->scheduleLocationModel->getRegistrationWindow($electionId);
        if (!$win || empty($win['endAt'])) {
            $errors[] = 'Registration window not found for the selected election.';
        } else {
            $regEnd = new DateTime($win['endAt'], $tz);
            if ($start <= $regEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be after registration closing.';
            if ($start <= $now)    $fieldErrors['desiredStartDateTime'][] = 'Start must be in the future.';
        }
    }

    if ($start && $end) {
        $minEnd = (clone $start)->modify('+1 hour');
        if ($end < $minEnd) {
            $fieldErrors['desiredEndDateTime'][] = 'End time must be at least 1 hour after start.';
        }
    }

    if (($start || $end) && $electionId) {
        $endStr = $this->scheduleLocationModel->getElectionEndDate($electionId);
        if ($endStr) {
            $electionEnd = new DateTime($endStr, $tz);
            if ($start && $start > $electionEnd) $fieldErrors['desiredStartDateTime'][] = 'Start must be on or before the election end.';
            if ($end   && $end   > $electionEnd) $fieldErrors['desiredEndDateTime'][]   = 'End must be on or before the election end.';
        }
    }

    $hasErrors = !empty($errors) || array_reduce($fieldErrors, fn($c,$a)=>$c||!empty($a), false);
    if ($hasErrors) {
        // Reload elections for this nominee
        $elections = $this->scheduleLocationModel->getEligibleElectionsForNominee($accountId);

        $filePath = $this->fileHelper->getFilePath('CreateScheduleLocationNominee');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
            return;
        }
        echo "View file not found.";
        return;
    }

    $ok = $this->scheduleLocationModel->createScheduleLocation([
        'eventName'            => $old['eventName'],
        'eventType'            => $old['eventType'],
        'desiredStartDateTime' => $start->format('Y-m-d H:i:00'),
        'desiredEndDateTime'   => $end->format('Y-m-d H:i:00'),
        'nomineeID'            => $nomineeId,
        'electionID'           => $electionId,
    ]);

    if ($ok) {
        \set_flash('success', 'Schedule Location application submitted successfully.');
        header('Location: /nominee/schedule-location');
        exit;
    }

    \set_flash('fail', 'Failed to create Schedule Location.');
    $elections = $this->scheduleLocationModel->getEligibleElectionsForNominee($accountId);
    $filePath = $this->fileHelper->getFilePath('CreateScheduleLocationNominee');
    if ($filePath && file_exists($filePath)) {
        include $filePath;
        return;
    }
    echo "View file not found.";
}
   

}
