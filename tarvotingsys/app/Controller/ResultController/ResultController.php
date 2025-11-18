<?php

namespace Controller\ResultController;

use Model\ResultModel\ResultModel;
use Model\VotingModel\VoteSessionModel;
use FileHelper;

class ResultController
{
    private $resultModel;
    private $voteSessionModel;
    private $fileHelper;

    public function __construct()
    {
        $this->resultModel     = new ResultModel();
        $this->voteSessionModel = new VoteSessionModel();
        $this->fileHelper      = new FileHelper('result'); // views in /View/result
    }

    /**
     * GET /statistics
     * View Statistical Data (Admin, Student, Nominee)
     */
    public function viewStatisticalData(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to view statistical data.');
            header('Location: /login');
            exit;
        }

        // Keep vote session statuses fresh (OPEN/CLOSED).
        $this->voteSessionModel->autoRollStatuses();

        // 1) Elections
        $elections = $this->resultModel->getElectionsForStats();
        $selectedElectionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;

        if ($selectedElectionID <= 0 && !empty($elections)) {
            $selectedElectionID = (int) $elections[0]['electionID'];
        }

        // 2) Vote sessions for selected election
        $voteSessions          = [];
        $selectedSessionID     = 0;
        $selectedSessionStatus = '';

        if ($selectedElectionID > 0) {
            $voteSessions = $this->resultModel->getVoteSessionsForElection($selectedElectionID);

            $selectedSessionID = isset($_GET['voteSessionID']) ? (int) $_GET['voteSessionID'] : 0;
            if ($selectedSessionID <= 0 && !empty($voteSessions)) {
                $selectedSessionID = (int) $voteSessions[0]['voteSessionID'];
            }

            foreach ($voteSessions as $vs) {
                if ((int) $vs['voteSessionID'] === $selectedSessionID) {
                    $selectedSessionStatus = strtoupper($vs['voteSessionStatus'] ?? '');
                    break;
                }
            }
        }

        $isLive  = ($selectedSessionStatus === 'OPEN');   // real-time
        $isFinal = ($selectedSessionStatus === 'CLOSED'); // final

        // 3) Races for selected session
        $races          = [];
        $selectedRaceID = 0;
        $selectedRace   = null;

        if ($selectedSessionID > 0) {
            $races = $this->resultModel->getRacesForSession($selectedSessionID);

            $selectedRaceID = isset($_GET['raceID']) ? (int) $_GET['raceID'] : 0;
            if ($selectedRaceID <= 0 && !empty($races)) {
                $selectedRaceID = (int) $races[0]['raceID'];
            }

            foreach ($races as $r) {
                if ((int) $r['raceID'] === $selectedRaceID) {
                    $selectedRace = $r;
                    break;
                }
            }
        }

        // 4) Statistics for selected combo
        $votesByNominee   = [];
        $turnout          = null;
        $ballotsOverTime  = [];
        $lastUpdated      = null;

        if ($selectedSessionID > 0 && $selectedRaceID > 0 && $selectedRace) {
            $votesByNominee  = $this->resultModel->getRaceVoteBreakdown($selectedSessionID, $selectedRaceID);

            $seatType   = strtoupper($selectedRace['seatType'] ?? '');
            $facultyID  = $selectedRace['facultyID'] ?? null;
            $turnout    = $this->resultModel->getTurnoutStats($selectedSessionID, $facultyID, $seatType);

            $ballotsOverTime = $this->resultModel->getBallotsSubmittedOverTime($selectedSessionID);
            $lastUpdated     = $this->resultModel->getLastBallotSubmittedAt($selectedSessionID);
        }

        if (!$lastUpdated) {
            $lastUpdated = date('Y-m-d H:i:s');
        }

        include $this->fileHelper->getFilePath('ViewStatisticalData');
    }
}