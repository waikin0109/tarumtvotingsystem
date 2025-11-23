<?php
// (no namespace — keep it global if you like, or add one and update the `use` line)

class FileHelper
{
    private array $allowedPaths = [];
    private string $permission;
    /** Base dir where this file lives (usually .../app) */
    private string $base;

    public function __construct(string $permission)
    {
        $this->permission = $permission;

        // Anchor to the directory where FileHelper.php is located.
        // If FileHelper.php is in /app, then $this->base === .../app/
        $this->base = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->initializePaths();
    }

    /** Join relative paths to $this->base and normalize separators */
    private function prefixWithBase(array $paths): array
    {
        foreach ($paths as $key => $relPath) {
            // Normalize incoming slashes and strip any leading slash
            $rel = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
            $paths[$key] = $this->base . $rel;
        }
        return $paths;
    }

    private function initializePaths(): void
    {
        // IMPORTANT: make this relative to /app (where FileHelper.php sits)
        // So View/VotingView/election-event.php resolves to .../app/View/VotingView/election-event.php
        $electionEventPaths = $this->prefixWithBase([
            'ElectionEventList' => 'View/VotingView/electionEvent.php',
        ]);

        $loginpaths = $this->prefixWithBase([
            'Login' => 'View/LoginView/login.php',
        ]);

        $studentPaths = $this->prefixWithBase([
            'StudentHome' => 'View/StudentView/studentHome.php',
        ]);

        $adminPaths = $this->prefixWithBase([
            'AdminHome' => 'View/AdminView/adminHome.php',
        ]);

        $nomineePaths = $this->prefixWithBase([
            'NomineeHome' => 'View/NomineeView/nomineeHome.php',
        ]);

        $announcementPaths = $this->prefixWithBase([
            'AnnouncementList' => 'View/VotingView/announcement.php',
            'CreateAnnouncement' => 'View/VotingView/createAnnouncement.php',
            'EditAnnouncement' => 'View/VotingView/editAnnouncement.php',
            'ViewAnnouncementDetails' => 'View/VotingView/viewAnnouncementDetails.php',
            'StudentNomineeAnnouncementList' => 'View/VotingView/studentNomineeAnnouncementList.php',
            'StudentNomineeAnnouncementDetails' => 'View/VotingView/studentNomineeAnnouncementDetails.php',

        ]);

        $voteSessionPaths = $this->prefixWithBase([
            'VoteSessionList' => 'View/VotingView/voteSessionList.php',
            'CreateVoteSession' => 'View/VotingView/createVoteSession.php',
            'EditVoteSession' => 'View/VotingView/editVoteSession.php',
            'ViewVoteSessionDetails' => 'View/VotingView/viewVoteSessionDetails.php',
            'StudentNomineeVotingSessionList' => 'View/VotingView/studentNomineeVotingSessionList.php',
            'VoteSessionResults' => 'View/VotingView/voteSessionResults.php',

        ]);

        $ballotPaths = $this->prefixWithBase([
            'StartBallot' => 'View/VotingView/startBallot.php',
            'CastVote' => 'View/VotingView/castVote.php',
        ]);

        $resultPaths = $this->prefixWithBase([
            'ViewStatisticalData' => 'View/ResultView/viewStatisticalData.php',   // Live turnout dashboard
            'ViewFinalResultsAdmin' => 'View/ResultView/viewFinalResultsAdmin.php',     // Official admin view
            'ViewFinalResultsPublic' => 'View/ResultView/viewFinalResultsPublic.php',    // Official public view
        ]);


        $reportPaths = $this->prefixWithBase([
            'ReportGenerator' => 'View/ResultView/reportGenerator.php',
            'OverallTurnoutSummary' => 'View/ResultView/overallTurnoutSummary.php',
            'OfficialResultsAll' => 'View/ResultView/officialResultsAll.php',
            'ResultsByFaculty' => 'View/ResultView/resultsByFaculty.php',
            'ReportList' => 'View/ResultView/reportList.php',
            'EarlyVoteStatus' => 'View/ResultView/earlyVoteStatus.php',
        ]);

        // If your CSS is under /public/css, and FileHelper sits in /app,
        // then go one level up to project root and into public:
        $assetPaths = $this->prefixWithBase([
            'AppCSS' => '../public/css/app.css',
        ]);

        switch ($this->permission) {
            case 'election_event':
                $this->allowedPaths = array_merge($electionEventPaths, $assetPaths);
                break;
            case 'login':
                $this->allowedPaths = array_merge($loginpaths, $assetPaths);
                break;
            case 'student':
                $this->allowedPaths = array_merge($studentPaths, $assetPaths);
                break;
            case 'admin':
                $this->allowedPaths = array_merge($adminPaths, $assetPaths);
                break;
            case 'nominee':
                $this->allowedPaths = array_merge($nomineePaths, $assetPaths);
                break;
            case 'announcement':
                $this->allowedPaths = array_merge($announcementPaths, $assetPaths);
                break;
            case 'vote_session':
                $this->allowedPaths = array_merge($voteSessionPaths, $assetPaths);
                break;
            case 'ballot':
                $this->allowedPaths = array_merge($ballotPaths, $assetPaths);
                break;
            case 'result':
                $this->allowedPaths = array_merge($resultPaths, $assetPaths);
                break;
            case 'report':
                $this->allowedPaths = array_merge($reportPaths, $assetPaths);
                break;
            case 'asset':
                $this->allowedPaths = $assetPaths;
                break;
            default:
                $this->allowedPaths = [];
        }
    }

    public function getFilePath(string $key): ?string
    {
        return $this->allowedPaths[$key] ?? null;
    }
}
