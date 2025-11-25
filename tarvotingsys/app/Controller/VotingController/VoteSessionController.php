<?php

namespace Controller\VotingController;

use Model\VotingModel\VoteSessionModel;
use Model\VotingModel\BallotModel;
use Model\ResultModel\ResultModel;
use FileHelper;

class VoteSessionController
{
    private $voteSessionModel;
    private $ballotModel;
    private $resultModel;
    private $fileHelper;

    public function __construct()
    {
        $this->voteSessionModel = new VoteSessionModel();
        $this->ballotModel = new BallotModel();
        $this->resultModel = new ResultModel();
        $this->fileHelper = new FileHelper('vote_session');
    }

    /**
     * For CLOSED sessions:
     *  - expire unsubmitted envelopes
     *  - generate final results
     */
    private function expireUnsubmittedForClosedSessions(): void
    {
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
            $this->resultModel->generateFinalResultsForSession($sid);
        }
    }

    /** GET /vote-session */
    public function listVoteSessions(): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            header('Location: /login');
            exit;
        }

        // Keep statuses in sync with time windows
        $this->voteSessionModel->autoRollStatuses();
        $this->expireUnsubmittedForClosedSessions();

        // Paging + search
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $search = trim($_GET['q'] ?? '');

        $pager = $this->voteSessionModel->getPagedVoteSessionsForAdmin($page, 10, $search);
        $voteSessions = $pager->result ?? [];

        // Attach HasVoted for current admin (so admin can vote too)
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

    /** GET /vote-session/create */
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
            'races' => [],
        ];
        $fieldErrors = [];

        include $this->fileHelper->getFilePath('CreateVoteSession');
    }

    /** POST /vote-session/store */
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
            'races' => $racesIn,
        ];

        $fieldErrors = [];

        // --- Basic validation ---
        if ($electionID <= 0) {
            $fieldErrors['electionID'][] = 'Please choose an election.';
        }
        if ($sessionName === '') {
            $fieldErrors['sessionName'][] = 'Session name is required.';
        }
        if (mb_strlen($sessionName) > 100) {
            $fieldErrors['sessionName'][] = 'Session name must not exceed 100 characters.';
        }
        if (!in_array($sessionType, ['EARLY', 'MAIN'], true)) {
            $fieldErrors['sessionType'][] = 'Please select a session type.';
        }

        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);

        if (!$startTs) {
            $fieldErrors['startAt'][] = 'Start date and time is required.';
        }
        if (!$endTs) {
            $fieldErrors['endAt'][] = 'End date and time is required.';
        }
        if ($startTs && $endTs) {
            if ($endTs <= $startTs) {
                $fieldErrors['endAt'][] = 'End datetime must be after start datetime.';
            }
            if ($startTs < time()) {
                $fieldErrors['startAt'][] = 'The voting session start time cannot be in the past.';
            }
            if (($endTs - $startTs) < 8 * 3600) {
                $fieldErrors['endAt'][] = 'Voting session must last at least 8 hours.';
            }
        }

        // Election window + overlap check
        $ev = $this->voteSessionModel->getElectionWindow($electionID);
        if (!$ev) {
            $fieldErrors['electionID'][] = 'Election is not found.';
        }
        if ($ev && $startTs && $endTs) {
            if ($startTs < strtotime($ev['start']) || $endTs > strtotime($ev['end'])) {
                $fieldErrors['startAt'][] = 'Session must be inside the election window.';
            }
            if (
                $this->voteSessionModel->overlapsExisting(
                    $electionID,
                    null,
                    date('Y-m-d H:i:s', $startTs),
                    date('Y-m-d H:i:s', $endTs)
                )
            ) {
                $fieldErrors['startAt'][] = 'Session overlaps with another session of this election.';
            }
        }

        // --- Races validation ---
        $cleanRaces = [];
        $raceErrors = [];
        $anyRowSeen = false;

        foreach ($racesIn as $i => $r) {
            $title = trim($r['title'] ?? '');
            $type = strtoupper(trim($r['seatType'] ?? ''));
            $facultyID = (int) ($r['facultyID'] ?? 0);

            $seatCount = (int) ($r['seatCount'] ?? 0);
            $maxSel = (int) ($r['maxSelectable'] ?? 0);

            if (
                $title !== '' ||
                $type !== '' ||
                $facultyID ||
                array_key_exists('seatCount', $r) ||
                array_key_exists('maxSelectable', $r)
            ) {
                $anyRowSeen = true;
            }

            $rowHasError = false;
            $seatTypeMissingOrInvalid = false;

            // title
            if ($title === '') {
                $raceErrors[$i]['title'][] = 'Race title is required.';
                $rowHasError = true;
            } elseif (mb_strlen($title) > 100) {
                $raceErrors[$i]['title'][] = 'Race title must not exceed 100 characters.';
                $rowHasError = true;
            }

            // seatType
            if ($type === '') {
                $raceErrors[$i]['seatType'][] = 'Seat type is required.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            } elseif (!in_array($type, ['FACULTY_REP', 'CAMPUS_WIDE'], true)) {
                $raceErrors[$i]['seatType'][] = 'Invalid seat type.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            }

            // Faculty required for FACULTY_REP
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

            // Exact rules
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

        if (empty($cleanRaces)) {
            $fieldErrors['races_general'][] = $anyRowSeen
                ? 'Please fix the errors in your races or remove invalid rows.'
                : 'Please add at least one race.';
        }

        if (!empty($raceErrors)) {
            $fieldErrors['races_by_row'] = $raceErrors;
        }

        if (!empty($fieldErrors)) {
            $elections = $this->voteSessionModel->listElectionsForSession();
            $faculties = $this->voteSessionModel->listFaculties();
            include $this->fileHelper->getFilePath('CreateVoteSession');
            return;
        }

        // --- Persist session ---
        $status = ($publishMode === 'schedule') ? 'SCHEDULED' : 'DRAFT';
        $sessionId = $this->voteSessionModel->insertVoteSession([
            'electionID' => $electionID,
            'name' => $sessionName,
            'type' => $sessionType,
            'startAt' => date('Y-m-d H:i:s', $startTs),
            'endAt' => date('Y-m-d H:i:s', $endTs),
            'status' => $status,
        ]);

        if (!$sessionId) {
            set_flash('fail', 'Failed to create voting session.');
            header('Location: /vote-session/create');
            exit;
        }

        // Insert / reuse races (election-level) and link to this session
        foreach ($cleanRaces as $r) {
            $raceId = $this->voteSessionModel->findOrCreateRace([
                'title' => $r['title'],
                'seatType' => $r['seatType'],
                'seatCount' => $r['seatCount'],
                'maxSelectable' => $r['maxSelectable'],
                'electionID' => $electionID,
                'facultyID' => $r['facultyID'],
            ]);

            if ($raceId) {
                $this->voteSessionModel->addRaceToSession($sessionId, $raceId);
            }
        }

        // If admin chose "Schedule", enforce EARLY/MAIN race rule now
        if ($publishMode === 'schedule') {
            $check = $this->validateEarlyMainRacesRule($electionID);

            if (!$check['ok']) {
                // Downgrade to DRAFT so the broken configuration is never actually scheduled
                $this->voteSessionModel->updateStatus($sessionId, 'DRAFT');

                set_flash(
                    'fail',
                    'Voting session saved as draft. Cannot schedule because EARLY / MAIN race sets mismatch for this election. ' .
                    $check['message']
                );
                header('Location: /vote-session');
                exit;
            }

            set_flash('success', 'Voting session created and scheduled.');
        } else {
            set_flash('success', 'Voting session created as draft.');
        }

        header('Location: /vote-session');
        exit;
    }

    /** GET /vote-session/edit/{id} */
    public function editVoteSession(int $id): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $voteSession = $this->voteSessionModel->getById($id);
        if (!$voteSession) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        $elections = $this->voteSessionModel->listElectionsForSession();
        $faculties = $this->voteSessionModel->listFaculties();
        $races = $this->voteSessionModel->getRacesBySessionId($id);

        $old = [
            'electionID' => $voteSession['electionID'],
            'sessionName' => $voteSession['voteSessionName'],
            'sessionType' => $voteSession['voteSessionType'],
            'startAtLocal' => date('Y-m-d\TH:i', strtotime($voteSession['voteSessionStartAt'])),
            'endAtLocal' => date('Y-m-d\TH:i', strtotime($voteSession['voteSessionEndAt'])),
            'races' => $races,
        ];
        $fieldErrors = [];

        include $this->fileHelper->getFilePath('EditVoteSession');
    }

    /** POST /vote-session/update */
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
            'races' => $racesIn,
        ];

        $fieldErrors = [];

        if ($voteSessionID <= 0) {
            $fieldErrors['general'][] = 'Invalid session.';
        }
        if ($electionID <= 0) {
            $fieldErrors['electionID'][] = 'Please choose an election.';
        }
        if ($sessionName === '') {
            $fieldErrors['sessionName'][] = 'Session name is required.';
        }
        if (mb_strlen($sessionName) > 100) {
            $fieldErrors['sessionName'][] = 'Session name must not exceed 100 characters.';
        }
        if (!in_array($sessionType, ['EARLY', 'MAIN'], true)) {
            $fieldErrors['sessionType'][] = 'Please select a session type.';
        }

        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);

        if (!$startTs) {
            $fieldErrors['startAt'][] = 'Start date and time is required.';
        }
        if (!$endTs) {
            $fieldErrors['endAt'][] = 'End date and time is required.';
        }
        if ($startTs && $endTs) {
            if ($endTs <= $startTs) {
                $fieldErrors['endAt'][] = 'End datetime must be after start datetime.';
            }
            if ($startTs < time()) {
                $fieldErrors['startAt'][] = 'The voting session start time cannot be in the past.';
            }
            if (($endTs - $startTs) < 8 * 3600) {
                $fieldErrors['endAt'][] = 'Voting session must last at least 8 hours.';
            }
        }

        // Window + overlap (exclude current session)
        $ev = $this->voteSessionModel->getElectionWindow($electionID);
        if (!$ev) {
            $fieldErrors['electionID'][] = 'Election is not found.';
        }
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

        // --- Races validation with locking behaviour ---
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

            if (
                $title !== '' ||
                $type !== '' ||
                $facultyID ||
                array_key_exists('seatCount', $r) ||
                array_key_exists('maxSelectable', $r)
            ) {
                $anyRowSeen = true;
            }

            $rowHasError = false;
            $seatTypeMissingOrInvalid = false;

            // Prevent renaming races that are already used in SCHEDULED / OPEN / CLOSED
            if ($raceID > 0) {
                $dbRace = $this->voteSessionModel->getRaceById($raceID);
                if ($dbRace && $this->voteSessionModel->isRaceLocked($raceID)) {
                    if ($title !== '' && $title !== $dbRace['raceTitle']) {
                        $raceErrors[$i]['title'][] =
                            'This race is already used in a scheduled/open/closed session, so the title cannot be changed.';
                        $rowHasError = true;
                    }
                }
            }

            // title
            if ($title === '') {
                $raceErrors[$i]['title'][] = 'Race title is required.';
                $rowHasError = true;
            } elseif (mb_strlen($title) > 100) {
                $raceErrors[$i]['title'][] = 'Race title must not exceed 100 characters.';
                $rowHasError = true;
            }

            // seatType
            if ($type === '') {
                $raceErrors[$i]['seatType'][] = 'Seat type is required.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            } elseif (!in_array($type, ['FACULTY_REP', 'CAMPUS_WIDE'], true)) {
                $raceErrors[$i]['seatType'][] = 'Invalid seat type.';
                $rowHasError = true;
                $seatTypeMissingOrInvalid = true;
            }

            // Faculty required for FACULTY_REP
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

            // Exact rules
            if (!$seatTypeMissingOrInvalid) {
                if ($type === 'FACULTY_REP') {
                    if ($seatCount !== 1) {
                        $raceErrors[$i]['seatCount'][] = 'Faculty Representative must have exactly 1 seat.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 1) {
                        $raceErrors[$i]['maxSelectable'][] =
                            'Faculty Representative max selectable must be exactly 1.';
                        $rowHasError = true;
                    }
                } else { // CAMPUS_WIDE
                    $facultyID = null;

                    if ($seatCount !== 4) {
                        $raceErrors[$i]['seatCount'][] = 'Campus Wide Representative must have exactly 4 seats.';
                        $rowHasError = true;
                    }
                    if ($maxSel !== 4) {
                        $raceErrors[$i]['maxSelectable'][] =
                            'Campus Wide Representative max selectable must be exactly 4.';
                        $rowHasError = true;
                    }
                }
            }

            // Stop sneaky rename via delete + re-add
            if (!$rowHasError && !$seatTypeMissingOrInvalid) {
                $meta = $this->voteSessionModel->getExistingRaceMeta(
                    $electionID,
                    $type,
                    $facultyID ?: null
                );

                if ($meta) {
                    $canonicalId = (int) ($meta['raceID'] ?? 0);
                    $canonicalTitle = (string) ($meta['raceTitle'] ?? '');
                    $inUse = !empty($meta['inUse']);

                    $sameRace = $raceID && $raceID === $canonicalId;

                    if ($inUse) {
                        if ($sameRace && $title !== $canonicalTitle) {
                            $raceErrors[$i]['title'][] =
                                'This race is already used in scheduled/open/closed sessions, so its title cannot be changed.';
                            $rowHasError = true;
                        } elseif (!$sameRace && $title !== $canonicalTitle) {
                            $raceErrors[$i]['title'][] =
                                'There is already a race for this faculty and seat type used by other sessions ' .
                                'with the title "' . $canonicalTitle . '". You cannot create another race with a different title.';
                            $rowHasError = true;
                        }
                    }

                    // If titles match and no error, always reuse the canonical race ID
                    if (!$rowHasError && $title === $canonicalTitle) {
                        $raceID = $canonicalId;
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
        if (!empty($raceErrors)) {
            $fieldErrors['races_by_row'] = $raceErrors;
        }

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

        // Persist session itself (status change handled by schedule/unschedule endpoints)
        $status = ($publishMode === 'schedule') ? 'SCHEDULED' : 'DRAFT';

        $this->voteSessionModel->updateVoteSession([
            'voteSessionID' => $voteSessionID,
            'electionID' => $electionID,
            'sessionName' => $sessionName,
            'sessionType' => $sessionType,
            'startAt' => date('Y-m-d H:i:s', $startTs),
            'endAt' => date('Y-m-d H:i:s', $endTs),
        ]);

        // ---- Upsert races with "lock if already active" behaviour ----
        $keepIds = [];

        foreach ($cleanRaces as $r) {
            $raceID = !empty($r['raceID']) ? (int) $r['raceID'] : 0;

            if ($raceID > 0) {
                $locked = $this->voteSessionModel->isRaceUsedInActiveSession($raceID);

                if ($locked) {
                    $dbRace = $this->voteSessionModel->getRaceById($raceID);

                    if ($dbRace) {
                        $changed = false;

                        if ($dbRace['raceTitle'] !== $r['title']) {
                            $changed = true;
                        }
                        if ($dbRace['seatType'] !== $r['seatType']) {
                            $changed = true;
                        }

                        $dbFac = (string) ($dbRace['facultyID'] ?? '');
                        $newFac = (string) ($r['facultyID'] ?? '');
                        if ($dbFac !== $newFac) {
                            $changed = true;
                        }

                        if ((int) $dbRace['seatCount'] !== (int) $r['seatCount']) {
                            $changed = true;
                        }
                        if ((int) $dbRace['maxSelectable'] !== (int) $r['maxSelectable']) {
                            $changed = true;
                        }

                        if ($changed) {
                            $fieldErrors['races_locked'][] =
                                'Race "' . $dbRace['raceTitle'] . '" is already used by an active voting session and cannot be modified.';

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
                    }

                    // No changes -> keep the race as is, do NOT update definition
                    $keepIds[] = $raceID;
                    continue;
                }

                // Not locked -> safe to update race definition
                $this->voteSessionModel->updateRace([
                    'raceID' => $raceID,
                    'title' => $r['title'],
                    'seatType' => $r['seatType'],
                    'facultyID' => $r['facultyID'],
                    'seatCount' => $r['seatCount'],
                    'maxSelectable' => $r['maxSelectable'],
                ]);
                $keepIds[] = $raceID;
            } else {
                // New race: re-use an election-level race if it already exists
                $newId = $this->voteSessionModel->findOrCreateRace([
                    'title' => $r['title'],
                    'seatType' => $r['seatType'],
                    'seatCount' => $r['seatCount'],
                    'maxSelectable' => $r['maxSelectable'],
                    'electionID' => $electionID,
                    'facultyID' => $r['facultyID'],
                ]);

                if ($newId) {
                    $this->voteSessionModel->addRaceToSession($voteSessionID, $newId);
                    $keepIds[] = (int) $newId;
                }
            }
        }

        // Remove links to races that admin removed from this session
        $this->voteSessionModel->deleteRacesNotIn($voteSessionID, $keepIds);

        set_flash('success', 'Voting session updated.');
        header('Location: /vote-session');
        exit;
    }

    /** GET /vote-session/view/{id} */
    public function viewVoteSessionDetails(int $id): void
    {
        if (strtoupper($_SESSION['role'] ?? '') !== 'ADMIN') {
            set_flash('fail', 'You must be an admin.');
            header('Location: /login');
            exit;
        }

        $voteSession = $this->voteSessionModel->getById($id);
        if (!$voteSession) {
            set_flash('fail', 'Voting session not found.');
            header('Location: /vote-session');
            exit;
        }

        $races = $this->voteSessionModel->getRacesBySessionId($id);

        include $this->fileHelper->getFilePath('ViewVoteSessionDetails');
    }

    /** POST /vote-session/delete */
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

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Voting session deleted.' : 'Unable to delete. It may be referenced by ballots or results.'
        );
        header('Location: /vote-session');
        exit;
    }

    /** POST /vote-session/schedule */
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

        if ($status !== 'DRAFT') {
            set_flash('fail', 'Only draft sessions can be scheduled.');
            header('Location: /vote-session');
            exit;
        }

        $startTs = $row['voteSessionStartAt'] ? strtotime($row['voteSessionStartAt']) : 0;
        if (!$startTs || $startTs <= time()) {
            set_flash('fail', 'Start time has already passed. Please edit the session before scheduling.');
            header('Location: /vote-session');
            exit;
        }

        $electionID = (int) ($row['electionID'] ?? 0);
        $check = $this->validateEarlyMainRacesRule($electionID);

        if (!$check['ok']) {
            set_flash(
                'fail',
                'Cannot schedule this session. EARLY / MAIN race sets mismatch for this election. ' .
                $check['message']
            );
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

    /** POST /vote-session/unschedule */
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

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Voting session unscheduled and turns to draft.' : 'Unable to unschedule session.'
        );
        header('Location: /vote-session');
        exit;
    }

    /** POST /vote-session/cancel */
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

        set_flash(
            $ok ? 'success' : 'fail',
            $ok ? 'Voting session cancelled.' : 'Unable to cancel session.'
        );
        header('Location: /vote-session');
        exit;
    }

    /** GET /voting-session (student/nominee list) */
    public function viewVoteSessionForStudentAndNominee(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        if (!in_array($role, ['STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to access the voter sessions page.');
            header('Location: /login');
            exit;
        }

        $this->voteSessionModel->autoRollStatuses();
        $this->expireUnsubmittedForClosedSessions();

        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $search = trim($_GET['q'] ?? '');

        $pager = $this->voteSessionModel->getPagedVoteSessionsForStudentNominee($page, 10, $search);
        $voteSessions = $pager->result ?? [];

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

    /**
     * Enforce rule: if BOTH EARLY and MAIN sessions exist for an election,
     * their race sets (raceIDs) must be exactly equal.
     */
    private function validateEarlyMainRacesRule(int $electionID): array
    {
        $sets = $this->voteSessionModel->getRaceSetsBySessionTypeForElection($electionID);
        $early = array_unique($sets['earlyRaceIDs'] ?? []);
        $main = array_unique($sets['mainRaceIDs'] ?? []);

        sort($early);
        sort($main);

        $hasEarly = !empty($early);
        $hasMain = !empty($main);

        // If either type does not exist yet, do NOT enforce.
        if (!$hasEarly || !$hasMain) {
            return [
                'ok' => true,
                'hasEarly' => $hasEarly,
                'hasMain' => $hasMain,
                'earlyRaceIDs' => $early,
                'mainRaceIDs' => $main,
                'missingInEarly' => [],
                'missingInMain' => [],
                'message' => 'Rule only enforced when both EARLY and MAIN sessions exist for this election.',
            ];
        }

        $missingInEarly = array_values(array_diff($main, $early)); // main-only races
        $missingInMain = array_values(array_diff($early, $main)); // early-only races

        $ok = empty($missingInEarly) && empty($missingInMain);

        if ($ok) {
            $message = 'EARLY and MAIN race sets match for this election.';
        } else {
            $parts = [];
            if (!empty($missingInEarly)) {
                $parts[] = 'Some races appear in MAIN but not in EARLY.';
            }
            if (!empty($missingInMain)) {
                $parts[] = 'Some races appear in EARLY but not in MAIN.';
            }
            $message = implode(' ', $parts);
        }

        return [
            'ok' => $ok,
            'hasEarly' => $hasEarly,
            'hasMain' => $hasMain,
            'earlyRaceIDs' => $early,
            'mainRaceIDs' => $main,
            'missingInEarly' => $missingInEarly,
            'missingInMain' => $missingInMain,
            'message' => $message,
        ];
    }
}