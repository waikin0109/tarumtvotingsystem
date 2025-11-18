<?php

namespace Controller\VotingController;

use Model\VotingModel\VoteSessionModel;
use Model\VotingModel\BallotModel;
use FileHelper;

class BallotController
{
    private $voteSessionModel;
    private $ballotModel;
    private $fileHelper;

    public function __construct()
    {
        $this->voteSessionModel = new VoteSessionModel();
        $this->ballotModel = new BallotModel();
        $this->fileHelper = new FileHelper('ballot');
    }

    /**
     * GET /ballot/start/{id}
     * Ballot start / info page before voting.
     */
    public function startBallot(int $id): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        $listUrl = ($role === 'ADMIN') ? '/vote-session' : '/vote-session/public';


        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You must log in to view this ballot.');
            header('Location: /login');
            exit;
        }

        // Load session + election info
        $vs = $this->voteSessionModel->getById($id);
        if (!$vs) {
            set_flash('fail', 'Voting session not found.');
            header('Location: ' . $listUrl);
            exit;
        }

        // Only allow when OPEN
        $statusRaw = $vs['voteSessionStatus'] ?? $vs['VoteSessionStatus'] ?? '';
        $status = strtoupper($statusRaw);

        if ($status !== 'OPEN') {
            set_flash('fail', 'This voting session is not open.');
            header('Location: ' . $listUrl);
            exit;
        }

        // We can reuse races later for the actual ballot page if needed
        $races = $this->voteSessionModel->getRacesBySessionId($id);

        // Build simple view model
        $sessionName = $vs['voteSessionName'] ?? $vs['VoteSessionName'] ?? '';
        $typeCode = strtoupper($vs['voteSessionType'] ?? $vs['VoteSessionType'] ?? '');
        $sessionTypeLabel = ($typeCode === 'EARLY') ? 'Early Voting Session' : 'Main Voting Session';

        $startAtRaw = $vs['voteSessionStartAt'] ?? $vs['StartAt'] ?? null;
        $endAtRaw = $vs['voteSessionEndAt'] ?? $vs['EndAt'] ?? null;

        $startFormatted = $startAtRaw ? date('Y.m.d H:i', strtotime($startAtRaw)) : '';
        $endFormatted = $endAtRaw ? date('Y.m.d H:i', strtotime($endAtRaw)) : '';

        $electionTitle = $vs['ElectionTitle'] ?? $vs['title'] ?? '(Unknown Election)';

        $sessionId = (int) $id;

        // Back target depends on role
        $backUrl = ($role === 'ADMIN') ? '/vote-session' : '/vote-session/public';

        // For info display: has this voter already submitted?
        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        $alreadySubmitted = false;
        if ($accountID > 0) {
            $alreadySubmitted = $this->ballotModel->hasSubmittedEnvelope($accountID, $sessionId);
        }

        include $this->fileHelper->getFilePath('StartBallot');
    }














    /**
     * POST /ballot/start
     * Called when user clicks "Start Ballot" on the info page.
     * Issues an envelope and redirects to /ballot/cast/{id}.
     */
    public function clickStartBallot(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        $listUrl = ($role === 'ADMIN') ? '/vote-session' : '/vote-session/public';


        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to vote in this session.');
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $listUrl);
            exit;
        }

        $sessionId = (int) ($_POST['voteSessionID'] ?? 0);
        if ($sessionId <= 0) {
            set_flash('fail', 'Invalid voting session.');
            header('Location: ' . $listUrl);
            exit;
        }

        $vs = $this->voteSessionModel->getById($sessionId);
        if (!$vs) {
            set_flash('fail', 'Voting session not found.');
            header('Location: ' . $listUrl);
            exit;
        }

        $status = strtoupper($vs['voteSessionStatus'] ?? '');
        if ($status !== 'OPEN') {
            set_flash('fail', 'This voting session is not open.');
            header('Location: ' . $listUrl);
            exit;
        }

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            set_flash('fail', 'Your login session is invalid. Please log in again.');
            header('Location: /login');
            exit;
        }

        // Has this voter already submitted a ballot?
        if ($this->ballotModel->hasSubmittedEnvelope($accountID, $sessionId)) {
            set_flash('fail', 'You have already submitted a ballot for this voting session.');
            header('Location: ' . $listUrl);
            exit;
        }

        // Try to reuse an existing ISSUED envelope; otherwise create a new one
        $envelope = $this->ballotModel->getActiveEnvelope($accountID, $sessionId);
        if (!$envelope) {
            $hash = $this->ballotModel->generateReceiptHash();
            $envelopeId = $this->ballotModel->createEnvelope($accountID, $sessionId, $hash);
            if (!$envelopeId) {
                set_flash('fail', 'Unable to start ballot now. Please try again later.');
                header('Location: ' . $listUrl);
                exit;
            }
            $envelope = [
                'ballotEnvelopeID' => $envelopeId,
                'voteSessionID' => $sessionId,
            ];
        }

        // Store active envelope in session
        $_SESSION['current_ballot_envelope_id'] = (int) $envelope['ballotEnvelopeID'];
        $_SESSION['current_ballot_session_id'] = $sessionId;

        header('Location: /ballot/cast/' . $sessionId);
        exit;
    }

    /**
     * GET /ballot/cast/{id}
     * Show the cast-vote page.
     */
    public function showCastPage(int $id): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        $listUrl = ($role === 'ADMIN') ? '/vote-session' : '/vote-session/public';


        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to vote in this session.');
            header('Location: /login');
            exit;
        }

        $vs = $this->voteSessionModel->getById($id);
        if (!$vs) {
            set_flash('fail', 'Voting session not found.');
            header('Location: ' . $listUrl);
            exit;
        }

        $status = strtoupper($vs['voteSessionStatus'] ?? '');
        if ($status !== 'OPEN') {
            set_flash('fail', 'This voting session is not open.');
            header('Location: ' . $listUrl);
            exit;
        }

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            set_flash('fail', 'Your login session is invalid. Please log in again.');
            header('Location: /login');
            exit;
        }

        // Must have an ISSUED envelope
        $sessionIdInSession = (int) ($_SESSION['current_ballot_session_id'] ?? 0);
        $envelopeId = (int) ($_SESSION['current_ballot_envelope_id'] ?? 0);
        $envelope = $this->ballotModel->getActiveEnvelope($accountID, $id);

        if (!$envelope || $sessionIdInSession !== $id || $envelopeId !== (int) $envelope['ballotEnvelopeID']) {
            set_flash('fail', 'Your ballot session is not active. Please start your ballot again.');
            header('Location: /ballot/start/' . $id);
            exit;
        }

        $voterFacultyID = (int) ($_SESSION['facultyID'] ?? 0);

        $races = $this->ballotModel->getRacesWithNomineesForVoter($id, $voterFacultyID);

        // View model for header
        $sessionName = $vs['voteSessionName'] ?? $vs['VoteSessionName'] ?? '';
        $typeCode = strtoupper($vs['voteSessionType'] ?? $vs['VoteSessionType'] ?? '');
        $sessionTypeLabel = ($typeCode === 'EARLY') ? 'Early Voting' : 'Main Voting';

        $electionTitle = $vs['ElectionTitle'] ?? $vs['title'] ?? '(Unknown Election)';
        $sessionId = (int) $id;

        // No previous selections first time
        $oldSelections = [];
        $selectionErrors = [];

        include $this->fileHelper->getFilePath('CastVote');
    }

    /**
     * POST /ballot/cast/{id}
     * Validate selections and submit ballot.
     */
    public function submitBallot(int $id): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        $listUrl = ($role === 'ADMIN') ? '/vote-session' : '/vote-session/public';


        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to vote in this session.');
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /ballot/cast/' . $id);
            exit;
        }

        $vs = $this->voteSessionModel->getById($id);
        if (!$vs) {
            set_flash('fail', 'Voting session not found.');
            header('Location: ' . $listUrl);
            exit;
        }

        $status = strtoupper($vs['voteSessionStatus'] ?? '');
        if ($status !== 'OPEN') {
            set_flash('fail', 'This voting session is not open.');
            header('Location: ' . $listUrl);
            exit;
        }

        $accountID = (int) ($_SESSION['accountID'] ?? 0);
        if ($accountID <= 0) {
            set_flash('fail', 'Your login session is invalid. Please log in again.');
            header('Location: /login');
            exit;
        }

        // Envelope checks again
        $sessionIdInSession = (int) ($_SESSION['current_ballot_session_id'] ?? 0);
        $envelopeId = (int) ($_SESSION['current_ballot_envelope_id'] ?? 0);
        $envelope = $this->ballotModel->getActiveEnvelope($accountID, $id);

        if (!$envelope || $sessionIdInSession !== $id || $envelopeId !== (int) $envelope['ballotEnvelopeID']) {
            set_flash('fail', 'Your ballot session is not active. Please start your ballot again.');
            header('Location: /ballot/start/' . $id);
            exit;
        }

        // If somehow already submitted, block
        if ($this->ballotModel->hasSubmittedEnvelope($accountID, $id)) {
            set_flash('fail', 'You have already submitted a ballot for this session.');
            header('Location: ' . $listUrl);
            exit;
        }

        $voterFacultyID = (int) ($_SESSION['facultyID'] ?? 0);
        $races = $this->ballotModel->getRacesWithNomineesForVoter($id, $voterFacultyID);

        // Build map of allowed nominees per race
        $allowedNominees = [];
        foreach ($races as $race) {
            $rid = (int) $race['raceID'];
            $allowedNominees[$rid] = [];
            foreach ($race['nominees'] as $n) {
                $allowedNominees[$rid][] = (int) $n['nomineeID'];
            }
        }

        $selectionsIn = $_POST['selections'] ?? [];
        $oldSelections = [];
        $selectionErrors = [];
        $hasError = false;
        $cleanSelections = [];

        foreach ($races as $race) {
            $rid = (int) $race['raceID'];
            $seatType = strtoupper($race['seatType']);
            $maxSel = (int) $race['maxSelectable'];

            $selected = isset($selectionsIn[$rid])
                ? (array) $selectionsIn[$rid]
                : [];

            // Normalise & filter invalid nominee IDs
            $selected = array_unique(array_map('intval', $selected));
            $selected = array_values(array_filter($selected, function (int $nid) use ($allowedNominees, $rid): bool {
                return in_array($nid, $allowedNominees[$rid] ?? [], true);
            }));

            $oldSelections[$rid] = $selected;

            if ($seatType === 'FACULTY_REP') {
                // Allow 0 or 1 for Faculty Rep
                if (count($selected) > 1) {
                    $selectionErrors[$rid][] = 'You can choose at most 1 Faculty Representative.';
                    $hasError = true;
                }
            } else {
                // CAMPUS_WIDE – allow 0..max but never above max
                if (count($selected) > $maxSel) {
                    $selectionErrors[$rid][] = 'You can choose up to ' . $maxSel . ' candidates for Campus Wide Representative.';
                    $hasError = true;
                }
            }


            if (!empty($selected)) {
                $cleanSelections[$rid] = $selected;
            }
        }

        // If any errors, redisplay the page with messages
        $sessionName = $vs['voteSessionName'] ?? $vs['VoteSessionName'] ?? '';
        $typeCode = strtoupper($vs['voteSessionType'] ?? $vs['VoteSessionType'] ?? '');
        $sessionTypeLabel = ($typeCode === 'EARLY') ? 'Early Voting' : 'Main Voting';
        $electionTitle = $vs['ElectionTitle'] ?? $vs['title'] ?? '(Unknown Election)';
        $sessionId = (int) $id;

        if ($hasError) {
            set_flash('fail', 'Please fix the highlighted issues before submitting your ballot.');
            // $selectionErrors = $selectionErrors;
            include $this->fileHelper->getFilePath('CastVote');
            return;
        }

        // // If no selections at all, do not allow empty ballot (you can relax this if wanted)
        // if (empty($cleanSelections)) {
        //     set_flash('fail', 'Your ballot is empty. Please choose at least one candidate.');
        //     // $selectionErrors = $selectionErrors;
        //     include $this->fileHelper->getFilePath('CastVote');
        //     return;
        // }

        // Submit
        // $ok = $this->ballotModel->submitBallot(
        //     $id,
        //     (int) $envelope['ballotEnvelopeID'],
        //     $cleanSelections
        // );
// We now allow a completely empty ballot (blank / undervote).
        $ok = $this->ballotModel->submitBallot(
            $id,
            (int) $envelope['ballotEnvelopeID'],
            $cleanSelections
        );

        if (!$ok) {
            set_flash('fail', 'Unable to submit your ballot right now. Please try again.');
            include $this->fileHelper->getFilePath('CastVote');
            return;
        }
        // Clear active envelope from session
        unset($_SESSION['current_ballot_envelope_id'], $_SESSION['current_ballot_session_id']);

        if (empty($cleanSelections)) {
            // Blank / undervote – still SUBMITTED, not VOID
            set_flash('success', 'Your ballot was submitted without any candidate selections (blank ballot).');
        } else {
            set_flash('success', 'Your ballot has been successfully submitted.');
        }

        header('Location: ' . $listUrl);
        exit;

    }


    /**
     * GET /ballot/vote/{id}
     * Temporary placeholder for the actual ballot voting UI.
     * So the "Start Ballot" button does not hit a 404.
     */
    // public function votePage(int $id): void
    // {
    //     $role = strtoupper($_SESSION['role'] ?? '');

    //     if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
    //         set_flash('fail', 'You must log in to vote.');
    //         header('Location: /login');
    //         exit;
    //     }

    //     // For now, just show a simple placeholder.
    //     // Later you will replace this with a proper ballot UI.
    //     header('Content-Type: text/html; charset=utf-8');
    //     echo '<!DOCTYPE html><html><head><title>Ballot Voting</title></head><body>';
    //     echo '<h1>Ballot voting page (placeholder)</h1>';
    //     echo '<p>Voting session ID: ' . htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') . '</p>';
    //     echo '<p>You can now implement the actual ballot form here.</p>';
    //     echo '<p><a href="/vote-session/public">Back to Voting Sessions</a></p>';
    //     echo '</body></html>';
    // }



}