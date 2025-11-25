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
        $this->resultModel = new ResultModel();
        $this->voteSessionModel = new VoteSessionModel();
        $this->fileHelper = new FileHelper('result');
    }

    public function viewStatisticalData(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to view statistical data.');
            header('Location: /login');
            exit;
        }

        // Keep vote session statuses fresh (OPEN / CLOSED).
        $this->voteSessionModel->autoRollStatuses();

        /* ---------------------------------------------------------------------
         * 1) ELECTION FILTER (LIVE page = ONGOING only)
         * ------------------------------------------------------------------- */
        $elections = $this->resultModel->getElectionsForStats(false); // ONGOING only
        $selectedElectionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $selectedElectionTitle = null;

        if ($selectedElectionID > 0 && !empty($elections)) {
            foreach ($elections as $e) {
                if ((int) $e['electionID'] === $selectedElectionID) {
                    $selectedElectionTitle = $e['title'];
                    break;
                }
            }
        }

        /* ---------------------------------------------------------------------
         * 2) VOTE SESSION FILTER (LIVE page = OPEN only)
         * ------------------------------------------------------------------- */
        $voteSessions = [];
        $selectedSessionID = isset($_GET['voteSessionID']) ? (int) $_GET['voteSessionID'] : 0;
        $selectedSessionStatus = '';
        $sessionName = '';

        if ($selectedElectionID > 0) {
            // Only OPEN sessions for this live page
            $voteSessions = $this->resultModel->getVoteSessionsForElection($selectedElectionID, false);

            if ($selectedSessionID > 0 && !empty($voteSessions)) {
                foreach ($voteSessions as $vs) {
                    if ((int) $vs['voteSessionID'] === $selectedSessionID) {
                        $selectedSessionStatus = strtoupper($vs['voteSessionStatus'] ?? '');
                        $sessionName = $vs['voteSessionName'] ?? '';
                        break;
                    }
                }
            }
        }

        $isLive = ($selectedSessionStatus === 'OPEN');   // real-time session only
        $isFinal = false; // this page is NOT for final results anymore

        /* ---------------------------------------------------------------------
         * 3) RACE FILTER (depends on vote session)
         * ------------------------------------------------------------------- */
        $races = [];
        $selectedRaceID = isset($_GET['raceID']) ? (int) $_GET['raceID'] : 0;
        $selectedRace = null;

        if ($selectedSessionID > 0) {
            $races = $this->resultModel->getRacesForSession($selectedSessionID);

            if ($selectedRaceID > 0 && !empty($races)) {
                foreach ($races as $r) {
                    if ((int) $r['raceID'] === $selectedRaceID) {
                        $selectedRace = $r;
                        break;
                    }
                }
            }
        }

        /* ---------------------------------------------------------------------
         * 4) STATISTICS (only if all three filters selected AND session is LIVE)
         * ------------------------------------------------------------------- */
        $turnout = null;
        $turnoutByFaculty = [];
        $lastUpdated = null;

        if ($selectedSessionID > 0 && $selectedRaceID > 0 && $selectedRace && $isLive) {
            $seatType = strtoupper($selectedRace['seatType'] ?? '');
            $facultyID = $selectedRace['facultyID'] !== null
                ? (int) $selectedRace['facultyID']
                : null;

            // Overall turnout for this race (faculty-specific or campus-wide).
            $turnout = $this->resultModel->getTurnoutStats(
                $selectedSessionID,
                $facultyID,
                $seatType
            );

            if (!$turnout) {
                $turnout = [
                    'eligible' => 0,
                    'ballotsSubmitted' => 0,
                    'turnoutPercent' => 0.0,
                ];
            }

            // Faculty breakdown
            if ($seatType === 'FACULTY_REP' && $facultyID !== null) {
                $one = $this->resultModel->getTurnoutStats(
                    $selectedSessionID,
                    $facultyID,
                    'FACULTY_REP'
                );

                if ($one) {
                    $turnoutByFaculty[] = [
                        'facultyID' => $facultyID,
                        'facultyName' => $selectedRace['facultyName'] ?? 'This Faculty',
                        'eligible' => (int) $one['eligible'],
                        'voted' => (int) $one['ballotsSubmitted'],
                        'turnoutPercent' => (float) $one['turnoutPercent'],
                    ];
                }
            } else {
                // Campus-wide or other seatType – show all faculties.
                $faculties = $this->voteSessionModel->listFaculties();

                foreach ($faculties as $fac) {
                    $fid = (int) $fac['facultyID'];
                    $stats = $this->resultModel->getTurnoutStats(
                        $selectedSessionID,
                        $fid,
                        'FACULTY_REP'
                    );

                    if (!$stats) {
                        continue;
                    }

                    $turnoutByFaculty[] = [
                        'facultyID' => $fid,
                        'facultyName' => $fac['facultyName'],
                        'eligible' => (int) $stats['eligible'],
                        'voted' => (int) $stats['ballotsSubmitted'],
                        'turnoutPercent' => (float) $stats['turnoutPercent'],
                    ];
                }
            }

            // Last ballot submission time for "Last updated".
            $lastUpdated = $this->resultModel->getLastBallotSubmittedAt($selectedSessionID);
        }

        // If we still have no timestamp, use current server time as a fallback.
        if (!$lastUpdated) {
            $lastUpdated = date('Y-m-d H:i:s');
        }

        include $this->fileHelper->getFilePath('ViewStatisticalData');
    }

    public function viewFinalResults(): void
    {
        $role = strtoupper($_SESSION['role'] ?? '');

        if (!in_array($role, ['ADMIN', 'STUDENT', 'NOMINEE'], true)) {
            set_flash('fail', 'You are not allowed to view final results.');
            header('Location: /login');
            exit;
        }

        // Ensure statuses (OPEN/CLOSED) are up to date
        $this->voteSessionModel->autoRollStatuses();

        /* ---------------------------------------------------------------------
         * 1) ELECTION FILTER (include completed for final results)
         * ------------------------------------------------------------------- */
        $elections = $this->resultModel->getElectionsForStats(true); // ONGOING + COMPLETED
        $selectedElectionID = isset($_GET['electionID']) ? (int) $_GET['electionID'] : 0;
        $selectedElectionTitle = null;

        if ($selectedElectionID > 0 && !empty($elections)) {
            foreach ($elections as $e) {
                if ((int) $e['electionID'] === $selectedElectionID) {
                    $selectedElectionTitle = $e['title'];
                    break;
                }
            }
        }

        /* ---------------------------------------------------------------------
         * 2) VOTE SESSION FILTER (only CLOSED sessions are meaningful here)
         * ------------------------------------------------------------------- */
        $voteSessions = [];
        $selectedSessionID = isset($_GET['voteSessionID']) ? (int) $_GET['voteSessionID'] : 0;
        $selectedSessionStatus = '';
        $sessionName = '';

        if ($selectedElectionID > 0) {
            // Get OPEN + CLOSED from model, then filter to CLOSED for final results
            $allSessions = $this->resultModel->getVoteSessionsForElection($selectedElectionID, true);

            foreach ($allSessions as $vs) {
                if (strtoupper($vs['voteSessionStatus'] ?? '') === 'CLOSED') {
                    $voteSessions[] = $vs;
                }
            }

            if ($selectedSessionID > 0 && !empty($voteSessions)) {
                foreach ($voteSessions as $vs) {
                    if ((int) $vs['voteSessionID'] === $selectedSessionID) {
                        $selectedSessionStatus = strtoupper($vs['voteSessionStatus'] ?? '');
                        $sessionName = $vs['voteSessionName'] ?? '';
                        break;
                    }
                }
            }
        }

        $isClosed = ($selectedSessionStatus === 'CLOSED');
        $isCertified = $isClosed;

        /* ---------------------------------------------------------------------
         * 3) RESULTS DATA (only when session is CLOSED and selected)
         * ------------------------------------------------------------------- */
        $overallTurnout = null;
        $turnoutByFaculty = [];
        $raceResults = [];
        $raceTurnoutSummary = []; // TOP pie: Faculty Rep only
        $campusWideTurnout = [
            'eligible' => 0,
            'ballotsCast' => 0,
            'turnoutPercent' => 0.0,
            'validBallots' => 0,
        ];
        $campusWideTurnoutByRace = []; // bottom Campus-wide pie (per race)

        if ($selectedSessionID > 0 && $isClosed) {
            // Session-level turnout (used for the very top KPI cards)
            $overallTurnout = $this->resultModel->getOverallTurnoutForSession($selectedSessionID);
            $turnoutByFaculty = $this->resultModel->getTurnoutByFacultyForSession($selectedSessionID);

            // All races in this session (with seatCount + maxSelectable)
            $races = $this->resultModel->getRacesForSession($selectedSessionID);

            // Will feed the top pie: one slice per FACULTY_REP race
            $facultyRepSummary = [];

            foreach ($races as $race) {
                $raceID = (int) $race['raceID'];
                $seatCount = (int) ($race['seatCount'] ?? 0);
                if ($seatCount <= 0) {
                    $seatCount = 1;
                }

                // ----------------- race-level turnout -----------------
                $seatType = strtoupper($race['seatType'] ?? '');
                $facultyIdForTurnout = null;

                if ($seatType === 'FACULTY_REP' && isset($race['facultyID']) && $race['facultyID'] !== null) {
                    $facultyIdForTurnout = (int) $race['facultyID'];
                }

                $raceTurnout = $this->resultModel->getTurnoutStats(
                    $selectedSessionID,
                    $facultyIdForTurnout,
                    $seatType
                );

                if (!$raceTurnout) {
                    $raceTurnout = [
                        'eligible' => 0,
                        'ballotsSubmitted' => 0,
                        'turnoutPercent' => 0.0,
                    ];
                }

                $race['turnoutEligible'] = (int) $raceTurnout['eligible'];
                $race['turnoutBallotsSubmitted'] = (int) $raceTurnout['ballotsSubmitted'];
                $race['turnoutPercent'] = (float) $raceTurnout['turnoutPercent'];

                // ---------- Build summaries for pies (by seat type) ----------
                if ($seatType === 'FACULTY_REP') {
                    // TOP block pie = all Faculty Rep races
                    $label = $race['facultyName'] ?? ($race['raceTitle'] ?? 'Faculty Rep Race');
                    $facultyRepSummary[] = [
                        'label' => $label,
                        'value' => $race['turnoutBallotsSubmitted'],
                        'tooltip' => sprintf(
                            '%s: %d of %d voters (%.2f%% turnout)',
                            $label,
                            $race['turnoutBallotsSubmitted'],
                            $race['turnoutEligible'],
                            $race['turnoutPercent']
                        ),
                    ];
                } else {
                    // Bottom Campus-wide pie = all non-FACULTY_REP races
                    $label = $race['raceTitle'] ?? 'Campus-wide race';
                    $campusWideTurnoutByRace[] = [
                        'label' => $label,
                        'value' => $race['turnoutBallotsSubmitted'],
                        'tooltip' => sprintf(
                            '%s: %d of %d voters (%.2f%% turnout)',
                            $label,
                            $race['turnoutBallotsSubmitted'],
                            $race['turnoutEligible'],
                            $race['turnoutPercent']
                        ),
                    ];
                }

                // Get vote breakdown for each candidate in this race
                $candidates = $this->resultModel->getRaceVoteBreakdown($selectedSessionID, $raceID);

                // Compute total votes for percentage
                $totalVotes = 0;
                foreach ($candidates as $cand) {
                    $totalVotes += (int) ($cand['votes'] ?? 0);
                }

                // Normalise candidate structure: votes, percent, default flags
                foreach ($candidates as &$cand) {
                    $votes = (int) ($cand['votes'] ?? 0);
                    $cand['votes'] = $votes;
                    $cand['percent'] = ($totalVotes > 0)
                        ? round(($votes / $totalVotes) * 100, 2)
                        : 0.0;
                    $cand['isWinner'] = false; // will be set below
                    $cand['isTieGroup'] = false; // for tie-break group
                }
                unset($cand);

                $numCandidates = count($candidates);
                $raceStatus = 'FINAL';
                $tieMeta = null;

                if ($numCandidates === 0) {
                    $raceStatus = 'NO_CANDIDATE';
                } elseif ($numCandidates <= $seatCount) {
                    foreach ($candidates as &$cand) {
                        $cand['isWinner'] = true;
                    }
                    unset($cand);
                } else {
                    // Normal case: more candidates than seats → check tie at last seat.
                    $cutIndex = $seatCount - 1;
                    if ($cutIndex < 0) {
                        $cutIndex = 0;
                    }
                    if ($cutIndex >= $numCandidates) {
                        $cutIndex = $numCandidates - 1;
                    }

                    $cutVotes = (int) ($candidates[$cutIndex]['votes'] ?? 0);

                    $countStrictAbove = 0;
                    $countAtCut = 0;

                    foreach ($candidates as $cand) {
                        $v = (int) ($cand['votes'] ?? 0);
                        if ($v > $cutVotes) {
                            $countStrictAbove++;
                        } elseif ($v === $cutVotes) {
                            $countAtCut++;
                        }
                    }

                    $seatsRemaining = $seatCount - $countStrictAbove;
                    if ($seatsRemaining < 0) {
                        $seatsRemaining = 0;
                    }

                    if ($countAtCut > $seatsRemaining) {
                        $raceStatus = 'TIE_BREAK_REQUIRED';
                        $tieMeta = [
                            'seatsTotal' => $seatCount,
                            'seatsRemaining' => $seatsRemaining,
                            'tiedVote' => $cutVotes,
                            'numTiedCandidates' => $countAtCut,
                        ];

                        foreach ($candidates as &$cand) {
                            $v = (int) ($cand['votes'] ?? 0);
                            if ($v > $cutVotes) {
                                $cand['isWinner'] = true;
                            }
                            if ($v === $cutVotes) {
                                $cand['isTieGroup'] = true;
                            }
                        }
                        unset($cand);
                    } else {
                        foreach ($candidates as $idx => &$cand) {
                            if ($idx < $seatCount) {
                                $cand['isWinner'] = true;
                            }
                        }
                        unset($cand);
                    }
                }

                $race['raceStatus'] = $raceStatus;
                $race['tieMeta'] = $tieMeta;

                $raceResults[] = [
                    'race' => $race,
                    'candidates' => $candidates,
                ];
            } // end foreach race

            // TOP pie = faculty representative races only
            $raceTurnoutSummary = $facultyRepSummary;

            // Bottom Campus-wide KPI cards use the same turnout numbers
            // as the whole session (eligible/ballots/turnout).
            if (!empty($campusWideTurnoutByRace)) {
                $campusWideTurnout = $overallTurnout;
            }
        }

        include $this->fileHelper->getFilePath("ViewFinalResults");
    }

}