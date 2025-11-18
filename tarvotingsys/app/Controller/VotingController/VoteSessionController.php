<?php

namespace Controller\VotingController;

use Model\VotingModel\VoteSessionModel;
use Model\VotingModel\BallotModel;
use FileHelper;

class VoteSessionController
{
    private $voteSessionModel;
    private $ballotModel;
    private $fileHelper;

    public function __construct()
    {
        $this->voteSessionModel = new VoteSessionModel();
        $this->ballotModel = new BallotModel();
        $this->fileHelper = new FileHelper("vote_session");
    }

 private function expireUnsubmittedForClosedSessions(): void
    {
        // We reuse the admin listing because it contains all sessions + statuses
        $sessions = $this->voteSessionModel->listForAdmin();

        foreach ($sessions as $row) {
            $status = strtoupper($row['VoteSessionStatus'] ?? $row['voteSessionStatus'] ?? '');
            if ($status !== 'CLOSED') {
                continue;
            }

            $sid = (int) ($row['VoteSessionID'] ?? $row['voteSessionID'] ?? 0);
            if ($sid <= 0) {
                continue;
            }

            $this->ballotModel->expireUnsubmittedEnvelopesForSession($sid);
        }
    }

    /** GET /voting-session */
    public function listVoteSessions(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            header('Location: /login');
            exit;
        }

        // Real-world behaviour: auto roll statuses based on time windows
        $this->voteSessionModel->autoRollStatuses();

        $this->expireUnsubmittedForClosedSessions();

        $voteSessions = $this->voteSessionModel->listForAdmin();

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID > 0) {
            foreach ($voteSessions as &$row) {
                $sid = (int) ($row['VoteSessionID'] ?? $row['voteSessionID'] ?? 0);
                $row['HasVoted'] = $this->ballotModel->hasSubmittedEnvelope($accountID, $sid);
            }
            unset($row);
        }

        include $this->fileHelper->getFilePath('VoteSessionList');
    }

    public function createVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            header('Location: /login');
            exit;
        }

        $elections = $this->voteSessionModel->listElectionsForSession();
        $faculties = $this->voteSessionModel->listFaculties();
        $old = [
            'electionID' => '',
            'sessionName' => '',
            'sessionType' => '',
            'startAtLocal' => '',
            'endAtLocal' => '',
            'races' => []
        ];
        $fieldErrors = [];

        include $this->fileHelper->getFilePath('CreateVoteSession');
    }

    public function storeVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session/create');
            exit;
        }

        $electionID = (int) ($_POST['electionID'] ?? 0);
        $sessionName = trim($_POST['sessionName'] ?? '');
        $sessionType = strtoupper(trim($_POST['sessionType'] ?? ''));   // ⬅️ no default
        $startAt = trim($_POST['startAt'] ?? '');
        $endAt = trim($_POST['endAt'] ?? '');
        $publishMode = $_POST['publishMode'] ?? 'draft';

        // races[]
        $racesIn = array_values($_POST['races'] ?? []);

        $old = [
            'electionID' => $electionID,
            'sessionName' => $sessionName,
            'sessionType' => $sessionType,
            'startAtLocal' => $_POST['startAtLocal'] ?? '',
            'endAtLocal' => $_POST['endAtLocal'] ?? '',
            'races' => $racesIn
        ];

        $fieldErrors = [];

        // Basic validation
        if ($electionID <= 0)
            $fieldErrors['electionID'][] = 'Please choose an election.';
        if ($sessionName === '')
            $fieldErrors['sessionName'][] = 'Session name is required.';
        if (mb_strlen($sessionName) > 100)
            $fieldErrors['sessionName'][] = 'Session name must not exceed 100 characters.';
        if (!in_array($sessionType, ['EARLY', 'MAIN'], true)) {
            $fieldErrors['sessionType'][] = 'Please select a session type.';
        }

        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);
        if (!$startTs)
            $fieldErrors['startAt'][] = 'Start date and time is required.';
        if (!$endTs)
            $fieldErrors['endAt'][] = 'End date and time is required.';
        if ($startTs && $endTs) {
            if ($endTs <= $startTs) {
                $fieldErrors['endAt'][] = 'End datetime must be after start datetime.';
            }
            if ($startTs < time()) {
                $fieldErrors['startAt'][] = 'The voting session start time cannot be in the past.';
            }
            // ⬅️ At least 8 hours
            if (($endTs - $startTs) < 8 * 3600) {
                $fieldErrors['endAt'][] = 'Voting session must last at least 8 hours.';
            }
        }

        // Election window + overlap check
        $ev = $this->voteSessionModel->getElectionWindow($electionID);
        if (!$ev)
            $fieldErrors['electionID'][] = 'Election is not found.';
        if ($ev && $startTs && $endTs) {
            if ($startTs < strtotime($ev['start']) || $endTs > strtotime($ev['end'])) {
                $fieldErrors['startAt'][] = 'Session must be inside the election window.';
            }
            if ($this->voteSessionModel->overlapsExisting($electionID, null, date('Y-m-d H:i:s', $startTs), date('Y-m-d H:i:s', $endTs))) {
                $fieldErrors['startAt'][] = 'Session overlaps with another session of this election.';
            }
        }

        // Races validation
        $cleanRaces = [];
        $raceErrors = [];
        $anyRowSeen = false;

        foreach ($racesIn as $i => $r) {
            $rowNo = $i + 1;
            $title = trim($r['title'] ?? '');
            $type = strtoupper(trim($r['seatType'] ?? ''));
            $facultyID = (int) ($r['facultyID'] ?? 0);

            // raw numbers first (then we validate/override)
            $seatCount = (int) ($r['seatCount'] ?? 0);
            $maxSel = (int) ($r['maxSelectable'] ?? 0);

            // Did user touch this row?
            if ($title !== '' || $type !== '' || $facultyID || ($r['seatCount'] ?? null) !== null || ($r['maxSelectable'] ?? null) !== null) {
                $anyRowSeen = true;
            }

            $rowHasError = false;

            // detect if user touched numeric fields (posted any value, including "0")
            $numbersTouched = array_key_exists('seatCount', $r) || array_key_exists('maxSelectable', $r);

            // title
            if ($title === '') {
                $raceErrors[$i]['title'][] = 'Race title is required.';
                $rowHasError = true;
            } elseif (mb_strlen($title) > 100) {
                $raceErrors[$i]['title'][] = 'Race title must not exceed 100 characters.';
                $rowHasError = true;
            }

            // seatType
            $seatTypeMissingOrInvalid = false;
            if ($type === '') {
                $raceErrors[$i]['seatType'][] = 'Seat type is required.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            } elseif (!in_array($type, ['FACULTY_REP', 'CAMPUS_WIDE'], true)) {
                $raceErrors[$i]['seatType'][] = 'Invalid seat type.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            }

            /* Always validate faculty when seatType is a valid FACULTY_REP */
            if (!$seatTypeMissingOrInvalid && $type === 'FACULTY_REP') {
                if ($facultyID <= 0) {
                    $raceErrors[$i]['facultyID'][] = 'Faculty is required for Faculty Representative.';
                    $rowHasError = true;
                }
            }

            $numbersTouched = array_key_exists('seatCount', $r) || array_key_exists('maxSelectable', $r);
            $shouldValidateNumbers = $numbersTouched || !$seatTypeMissingOrInvalid;

            if ($shouldValidateNumbers) {
                if ($seatCount < 1) {
                    $raceErrors[$i]['seatCount'][] = 'Must have at least one seat.';
                    $rowHasError = true;
                }
                if ($maxSel < 1) {
                    $raceErrors[$i]['maxSelectable'][] = 'Must allow at least one selection.';
                    $rowHasError = true;
                }
                if ($maxSel > $seatCount) {
                    $raceErrors[$i]['maxSelectable'][] = 'maxSelectable cannot exceed seatCount.';
                    $rowHasError = true;
                }
            }

            /* Seat-type exactness rules can still run */
            if (!$seatTypeMissingOrInvalid) {
                if ($type === 'FACULTY_REP') {
                    if ($seatCount !== 1) {
                        $raceErrors[$i]['seatCount'][] = 'Faculty Representative must have exactly 1 seat.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 1) {
                        $raceErrors[$i]['maxSelectable'][] = 'Faculty Representative max selectable must be exactly 1.';
                        $rowHasError = true;
                    }
                } else { // CAMPUS_WIDE
                    $facultyID = null;
                    if ($seatCount !== 4) {
                        $raceErrors[$i]['seatCount'][] = 'Campus Wide Representative must have exactly 4 seats.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 4) {
                        $raceErrors[$i]['maxSelectable'][] = 'Campus Wide Representative max selectable must be exactly 4.';
                        $rowHasError = true;
                    }
                }
            }

            if (!$rowHasError) {
                $cleanRaces[] = [
                    'title' => $title,
                    'seatType' => $type,
                    'facultyID' => $facultyID ?: null,
                    'seatCount' => ($type === 'FACULTY_REP') ? 1 : 4,
                    'maxSelectable' => ($type === 'FACULTY_REP') ? 1 : 4,
                ];
            }
        }

        // No valid races at all
        if (empty($cleanRaces)) {
            $fieldErrors['races_general'][] = $anyRowSeen
                ? 'Please fix the errors in your races or remove invalid rows.'
                : 'Please add at least one race.';
        }

        // expose per-row errors to the view
        if (!empty($raceErrors)) {
            $fieldErrors['races_by_row'] = $raceErrors;
        }

        if (!empty($fieldErrors)) {
            $elections = $this->voteSessionModel->listElectionsForSession();
            $faculties = $this->voteSessionModel->listFaculties();
            include $this->fileHelper->getFilePath('CreateVoteSession');
            return;
        }

        // Persist
        $status = ($publishMode === 'schedule') ? 'SCHEDULED' : 'DRAFT';
        $sessionId = $this->voteSessionModel->insertVoteSession([
            'electionID' => $electionID,
            'name' => $sessionName,
            'type' => $sessionType,
            'startAt' => date('Y-m-d H:i:s', $startTs),
            'endAt' => date('Y-m-d H:i:s', $endTs),
            'status' => $status
        ]);

        if (!$sessionId) {
            set_flash('fail', 'Failed to create voting session.');
            header('Location:/vote-session/create');
            exit;
        }

        // Insert races and link to session
        foreach ($cleanRaces as $r) {
            $raceId = $this->voteSessionModel->insertRace([
                'title' => $r['title'],
                'seatType' => $r['seatType'],
                'seatCount' => $r['seatCount'],
                'maxSelectable' => $r['maxSelectable'],
                'electionID' => $electionID,
                'facultyID' => $r['facultyID'],
                'voteSessionID' => $sessionId,
            ]);
        }
        set_flash('success', 'Voting session created' . ($status === 'SCHEDULED' ? ' and scheduled.' : ' as draft.'));
        header('Location: /vote-session');
        exit;
    }

    // Edit Vote Session
    public function editVoteSession(int $id): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        // Fetch the current vote session data
        $voteSession = $this->voteSessionModel->getById($id);
        if (!$voteSession) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        // Fetch election titles and faculties to populate the form
        $elections = $this->voteSessionModel->listElectionsForSession();
        $faculties = $this->voteSessionModel->listFaculties();

        // Fetch races associated with the session
        $races = $this->voteSessionModel->getRacesBySessionId($id);

        // Pass the data to the view
        $old = [
            'electionID' => $voteSession['electionID'],
            'sessionName' => $voteSession['voteSessionName'],
            'sessionType' => $voteSession['voteSessionType'],
            'startAtLocal' => date('Y-m-d\TH:i', strtotime($voteSession['voteSessionStartAt'])),
            'endAtLocal' => date('Y-m-d\TH:i', strtotime($voteSession['voteSessionEndAt'])),
            'races' => $races
        ];

        $fieldErrors = [];
        include $this->fileHelper->getFilePath('EditVoteSession');
    }

    // Update Vote Session
    public function updateVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session');
            exit;
        }

        $voteSessionID = (int) ($_POST['voteSessionID'] ?? 0);
        $electionID = (int) ($_POST['electionID'] ?? 0);
        $sessionName = trim($_POST['sessionName'] ?? '');
        $sessionType = strtoupper(trim($_POST['sessionType'] ?? ''));
        $startAt = trim($_POST['startAt'] ?? '');
        $endAt = trim($_POST['endAt'] ?? '');
        $publishMode = $_POST['publishMode'] ?? 'draft';

        $racesIn = array_values($_POST['races'] ?? []);
        $old = [
            'electionID' => $electionID,
            'sessionName' => $sessionName,
            'sessionType' => $sessionType,
            'startAtLocal' => $_POST['startAtLocal'] ?? '',
            'endAtLocal' => $_POST['endAtLocal'] ?? '',
            'races' => $racesIn
        ];

        $fieldErrors = [];

        if ($voteSessionID <= 0)
            $fieldErrors['general'][] = 'Invalid session.';
        if ($electionID <= 0)
            $fieldErrors['electionID'][] = 'Please choose an election.';
        if ($sessionName === '')
            $fieldErrors['sessionName'][] = 'Session name is required.';
        if (mb_strlen($sessionName) > 100)
            $fieldErrors['sessionName'][] = 'Session name must not exceed 100 characters.';
        if (!in_array($sessionType, ['EARLY', 'MAIN'], true)) {
            $fieldErrors['sessionType'][] = 'Please select a session type.';
        }

        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);
        if (!$startTs)
            $fieldErrors['startAt'][] = 'Start date and time is required.';
        if (!$endTs)
            $fieldErrors['endAt'][] = 'End date and time is required.';
        if ($startTs && $endTs) {
            if ($endTs <= $startTs)
                $fieldErrors['endAt'][] = 'End datetime must be after start datetime.';
            if ($startTs < time())
                $fieldErrors['startAt'][] = 'The voting session start time cannot be in the past.';
            if (($endTs - $startTs) < 8 * 3600)
                $fieldErrors['endAt'][] = 'Voting session must last at least 8 hours.';
        }

        // Window + overlap (exclude current session)
        $ev = $this->voteSessionModel->getElectionWindow($electionID);
        if (!$ev)
            $fieldErrors['electionID'][] = 'Election is not found.';
        if ($ev && $startTs && $endTs) {
            if ($startTs < strtotime($ev['start']) || $endTs > strtotime($ev['end'])) {
                $fieldErrors['startAt'][] = 'Session must be inside the election window.';
            }
            if (
                $this->voteSessionModel->overlapsExisting(
                    $electionID,
                    $voteSessionID,
                    date('Y-m-d H:i:s', $startTs),
                    date('Y-m-d H:i:s', $endTs)
                )
            ) {
                $fieldErrors['startAt'][] = 'Session overlaps with another session of this election.';
            }
        }

        // Races validation
        $cleanRaces = [];
        $raceErrors = [];
        $anyRowSeen = false;

        foreach ($racesIn as $i => $r) {
            $title = trim($r['title'] ?? '');
            $type = strtoupper(trim($r['seatType'] ?? ''));
            $facultyID = (int) ($r['facultyID'] ?? 0);
            $raceID = (int) ($r['raceID'] ?? 0);
            $seatCount = (int) ($r['seatCount'] ?? 0);
            $maxSel = (int) ($r['maxSelectable'] ?? 0);

            if ($title !== '' || $type !== '' || $facultyID || array_key_exists('seatCount', $r) || array_key_exists('maxSelectable', $r)) {
                $anyRowSeen = true;
            }

            $rowHasError = false;
            $seatTypeMissingOrInvalid = false;

            if ($title === '') {
                $raceErrors[$i]['title'][] = 'Race title is required.';
                $rowHasError = true;
            } elseif (mb_strlen($title) > 100) {
                $raceErrors[$i]['title'][] = 'Race title must not exceed 100 characters.';
                $rowHasError = true;
            }

            if ($type === '') {
                $raceErrors[$i]['seatType'][] = 'Seat type is required.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            } elseif (!in_array($type, ['FACULTY_REP', 'CAMPUS_WIDE'], true)) {
                $raceErrors[$i]['seatType'][] = 'Invalid seat type.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            }

            if (!$seatTypeMissingOrInvalid && $type === 'FACULTY_REP') {
                if ($facultyID <= 0) {
                    $raceErrors[$i]['facultyID'][] = 'Faculty is required for Faculty Representative.';
                    $rowHasError = true;
                }
            }

            $numbersTouched = array_key_exists('seatCount', $r) || array_key_exists('maxSelectable', $r);
            $shouldValidateNumbers = $numbersTouched || !$seatTypeMissingOrInvalid;

            if ($shouldValidateNumbers) {
                if ($seatCount < 1) {
                    $raceErrors[$i]['seatCount'][] = 'Must have at least one seat.';
                    $rowHasError = true;
                }
                if ($maxSel < 1) {
                    $raceErrors[$i]['maxSelectable'][] = 'Must allow at least one selection.';
                    $rowHasError = true;
                }
                if ($maxSel > $seatCount) {
                    $raceErrors[$i]['maxSelectable'][] = 'maxSelectable cannot exceed seatCount.';
                    $rowHasError = true;
                }
            }

            if (!$seatTypeMissingOrInvalid) {
                if ($type === 'FACULTY_REP') {
                    if ($seatCount !== 1) {
                        $raceErrors[$i]['seatCount'][] = 'Faculty Representative must have exactly 1 seat.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 1) {
                        $raceErrors[$i]['maxSelectable'][] = 'Faculty Representative max selectable must be exactly 1.';
                        $rowHasError = true;
                    }
                } else {
                    $facultyID = null;
                    if ($seatCount !== 4) {
                        $raceErrors[$i]['seatCount'][] = 'Campus Wide Representative must have exactly 4 seats.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 4) {
                        $raceErrors[$i]['maxSelectable'][] = 'Campus Wide Representative max selectable must be exactly 4.';
                        $rowHasError = true;
                    }
                }
            }

            if (!$rowHasError) {
                $cleanRaces[] = [
                    'raceID' => $raceID ?: null,
                    'title' => $title,
                    'seatType' => $type,
                    'facultyID' => $facultyID ?: null,
                    'seatCount' => ($type === 'FACULTY_REP') ? 1 : 4,
                    'maxSelectable' => ($type === 'FACULTY_REP') ? 1 : 4,
                ];
            }
        }

        if (empty($cleanRaces)) {
            $fieldErrors['races_general'][] = $anyRowSeen
                ? 'Please fix the errors in your races or remove invalid rows.'
                : 'Please add at least one race.';
        }
        if (!empty($raceErrors))
            $fieldErrors['races_by_row'] = $raceErrors;

        if (!empty($fieldErrors)) {
            $elections = $this->voteSessionModel->listElectionsForSession();
            $faculties = $this->voteSessionModel->listFaculties();

            $old = [
                'electionID' => $electionID,
                'sessionName' => $sessionName,
                'sessionType' => $sessionType,
                'startAtLocal' => $_POST['startAtLocal'] ?? '',
                'endAtLocal' => $_POST['endAtLocal'] ?? '',
                'races' => $racesIn,
            ];
            $id = $voteSessionID;
            include $this->fileHelper->getFilePath('EditVoteSession');
            return;
        }

        // Persist session
        $status = ($publishMode === 'schedule') ? 'SCHEDULED' : 'DRAFT';
        $this->voteSessionModel->updateVoteSession([
            'voteSessionID' => $voteSessionID,
            'electionID' => $electionID,
            'sessionName' => $sessionName,
            'sessionType' => $sessionType,
            'startAt' => date('Y-m-d H:i:s', $startTs),
            'endAt' => date('Y-m-d H:i:s', $endTs),
        ]);

        // Upsert races
        $keepIds = [];
        foreach ($cleanRaces as $r) {
            if (!empty($r['raceID'])) {
                $this->voteSessionModel->updateRace([
                    'raceID' => (int) $r['raceID'],
                    'title' => $r['title'],
                    'seatType' => $r['seatType'],
                    'facultyID' => $r['facultyID'],
                    'seatCount' => $r['seatCount'],
                    'maxSelectable' => $r['maxSelectable'],
                ]);
                $keepIds[] = (int) $r['raceID'];
            } else {
                $newId = $this->voteSessionModel->insertRace([
                    'electionID' => $electionID,
                    'voteSessionID' => $voteSessionID,
                    'title' => $r['title'],
                    'seatType' => $r['seatType'],
                    'facultyID' => $r['facultyID'],
                    'seatCount' => $r['seatCount'],
                    'maxSelectable' => $r['maxSelectable'],
                ]);

                if ($newId)
                    $keepIds[] = (int) $newId;
            }
        }

        // Delete races removed by admin
        $this->voteSessionModel->deleteRacesNotIn($voteSessionID, $keepIds);

        set_flash('success', 'Voting session updated.');
        header('Location: /vote-session');
        exit;
    }

    //View Vote Session Details
    public function viewVoteSessionDetails(int $id): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        // Fetch the voting session details
        $voteSession = $this->voteSessionModel->getById($id);
        if (!$voteSession) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        // Fetch races associated with this voting session
        $races = $this->voteSessionModel->getRacesBySessionId($id);

        // Pass the data to the view
        include $this->fileHelper->getFilePath('ViewVoteSessionDetails');
    }

    //Delete Vote Session
    public function deleteVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session');
            exit;
        }

        $id = (int) ($_POST['vote_session_id'] ?? 0);
        if ($id <= 0) {
            set_flash('fail', 'Invalid voting session.');
            header('Location: /vote-session');
            exit;
        }

        $row = $this->voteSessionModel->getById($id);
        if (!$row) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }
        $status = strtoupper($row['voteSessionStatus'] ?? $row['VoteSessionStatus'] ?? '');
        if ($status !== 'DRAFT') {
            set_flash('fail', 'Only draft sessions can be deleted.');
            header('Location: /vote-session');
            exit;
        }

        $ok = $this->voteSessionModel->deleteSecure($id);
        set_flash($ok ? 'success' : 'fail', $ok ? 'Voting session deleted.' : 'Unable to delete. It may be referenced by ballots or results.');
        header('Location: /vote-session');
        exit;
    }

    public function scheduleVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session');
            exit;
        }

        $id = (int) ($_POST['vote_session_id'] ?? 0);
        if ($id <= 0) {
            set_flash('fail', 'Invalid voting session.');
            header('Location: /vote-session');
            exit;
        }

        $row = $this->voteSessionModel->getById($id);
        if (!$row) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        $status = strtoupper($row['voteSessionStatus'] ?? $row['VoteSessionStatus'] ?? '');

        // Only DRAFT can be scheduled
        if ($status !== 'DRAFT') {
            set_flash('fail', 'Only draft sessions can be scheduled.');
            header('Location: /vote-session');
            exit;
        }

        // Extra safety: start time must still be in the future
        $startTs = $row['voteSessionStartAt'] ? strtotime($row['voteSessionStartAt']) : 0;
        if (!$startTs || $startTs <= time()) {
            set_flash('fail', 'Start time has already passed. Please edit the session before scheduling.');
            header('Location: /vote-session');
            exit;
        }

        $ok = $this->voteSessionModel->updateStatus($id, 'SCHEDULED');

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Voting session scheduled.' : 'Unable to schedule session.'
        );
        header('Location: /vote-session');
        exit;
    }

    public function unscheduleVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session');
            exit;
        }

        $id = (int) ($_POST['vote_session_id'] ?? 0);
        if ($id <= 0) {
            set_flash('fail', 'Invalid voting session.');
            header('Location: /vote-session');
            exit;
        }

        $row = $this->voteSessionModel->getById($id);
        if (!$row) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        $status = strtoupper($row['voteSessionStatus'] ?? $row['VoteSessionStatus'] ?? '');
        if ($status !== 'SCHEDULED') {
            set_flash('fail', 'Only scheduled sessions can be unscheduled.');
            header('Location: /vote-session');
            exit;
        }

        $ok = $this->voteSessionModel->updateStatus($id, 'DRAFT');
        set_flash($ok ? 'success' : 'fail', $ok ? 'Voting session unscheduled and turns to draft.' : 'Unable to unschedule session.');
        header('Location: /vote-session');
        exit;
    }

    public function cancelVoteSession(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /vote-session');
            exit;
        }

        $id = (int) ($_POST['vote_session_id'] ?? 0);
        if ($id <= 0) {
            set_flash('fail', 'Invalid voting session.');
            header('Location: /vote-session');
            exit;
        }

        $row = $this->voteSessionModel->getById($id);
        if (!$row) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        $status = strtoupper($row['voteSessionStatus'] ?? $row['VoteSessionStatus'] ?? '');

        if ($status !== 'SCHEDULED') {
            set_flash('fail', 'Only scheduled sessions can be cancelled.');
            header('Location: /vote-session');
            exit;
        }

        $ok = $this->voteSessionModel->updateStatus($id, 'CANCELLED');
        set_flash($ok ? 'success' : 'fail', $ok ? 'Voting session cancelled.' : 'Unable to cancel session.');
        header('Location: /vote-session');
        exit;
    }

    public function viewVoteSessionForStudentAndNominee(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        // Only STUDENT and NOMINEE can access this page
        if (!in_array($role, ['STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to access the voter sessions page.');
            header('Location: /login');
            exit;
        }

        // Keep auto-roll behaviour so statuses stay correct
        $this->voteSessionModel->autoRollStatuses();

        $this->expireUnsubmittedForClosedSessions();

        $voteSessions = $this->voteSessionModel->listForStudentNominee();

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID > 0) {
            foreach ($voteSessions as &$row) {
                $sid = (int) ($row['VoteSessionID'] ?? $row['voteSessionID'] ?? 0);
                $row['HasVoted'] = $this->ballotModel->hasSubmittedEnvelope($accountID, $sid);
            }
            unset($row);
        }

        include $this->fileHelper->getFilePath('StudentNomineeVotingSessionList');
    }


    //     /** GET /vote-session/results/{id} */
    // public function viewResults(int $id): void
    // {
    //     // Only ADMIN can see results
    //     if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
    //         set_flash('fail', 'You must be an admin to view results.');
    //         header('Location: /login');
    //         exit;
    //     }

    //     // Keep statuses up-to-date (optional)
    //     $this->voteSessionModel->autoRollStatuses();

    //     // If you added the expiry helper, you can also call it here:
    //     // $this->expireUnsubmittedForClosedSessions();

    //     // Load the vote session
    //     $vs = $this->voteSessionModel->getById($id);
    //     if (!$vs) {
    //         set_flash('fail', 'Voting session not found.');
    //         header('Location: /vote-session');
    //         exit;
    //     }

    //     $status = strtoupper($vs['voteSessionStatus'] ?? '');
    //     // Decide when you allow results – here: OPEN and CLOSED
    //     if (!in_array($status, ['OPEN', 'CLOSED'], true)) {
    //         set_flash('fail', 'Results are only available for open or closed sessions.');
    //         header('Location: /vote-session');
    //         exit;
    //     }

    //     // Get raw results from BallotModel
    //     $rows = $this->ballotModel->getResultsBySession($id);

    //     // Group by race for easier display
    //     $races = [];
    //     foreach ($rows as $row) {
    //         $rid = (int) $row['raceID'];

    //         if (!isset($races[$rid])) {
    //             $races[$rid] = [
    //                 'raceID'      => $rid,
    //                 'raceTitle'   => $row['raceTitle'],
    //                 'seatType'    => $row['seatType'],
    //                 'facultyID'   => $row['facultyID'],
    //                 'facultyName' => $row['facultyName'],
    //                 'candidates'  => [],
    //                 'totalVotes'  => 0,
    //             ];
    //         }

    //         $votes = (int) $row['votes'];

    //         $races[$rid]['candidates'][] = [
    //             'nomineeID' => (int) $row['nomineeID'],
    //             'fullName'  => $row['fullName'],
    //             'votes'     => $votes,
    //         ];
    //         $races[$rid]['totalVotes'] += $votes;
    //     }

    //     $electionTitle = $vs['ElectionTitle'] ?? '(Unknown Election)';
    //     $sessionName   = $vs['voteSessionName'] ?? '';
    //     $sessionStatus = $status;

    //     // Convert to simple indexed array for the view
    //     $races = array_values($races);

    //     include $this->fileHelper->getFilePath('VoteSessionResults');
    // }

}