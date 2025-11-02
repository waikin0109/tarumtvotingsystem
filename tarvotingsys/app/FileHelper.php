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
            'CreateElectionEvent' => 'View/VotingView/createElectionEvent.php',
            'EditElectionEvent' => 'View/VotingView/editElectionEvent.php',
            'ViewElectionEvent' => 'View/VotingView/viewElectionEvent.php',
            'DeleteElectionEvent' => 'View/VotingView/deleteElectionEvent.php'
        ]);

        $rulePaths = $this->prefixWithBase([
            'RuleList' => 'View/VotingView/rule.php',
            'CreateRule' => 'View/VotingView/createRule.php',
            'EditRule' => 'View/VotingView/editRule.php',
            'ViewRule' => 'View/VotingView/viewRule.php',
            'DeleteRule' => 'View/VotingView/deleteRule.php'
        ]);

        $electionRegistrationFormPaths = $this->prefixWithBase([
            'ElectionRegistrationFormList' => 'View/NomineeHandlingView/registrationForm.php',
            'CreateElectionRegistrationForm' => 'View/NomineeHandlingView/createRegistrationForm.php',
            'EditElectionRegistrationForm' => 'View/NomineeHandlingView/editRegistrationForm.php',
            'ViewElectionRegistrationForm' => 'View/NomineeHandlingView/viewRegistrationForm.php',
            'DeleteElectionRegistrationForm' => 'View/NomineeHandlingView/deleteRegistrationForm.php'
        ]);

        $nomineeApplicationPaths = $this->prefixWithBase([
            'NomineeApplicationList' => 'View/NomineeView/nomineeApplication.php',
            'CreateNomineeApplication' => 'View/NomineeView/createNomineeApplication.php',
            'EditNomineeApplication' => 'View/NomineeView/editNomineeApplication.php',
            'ViewNomineeApplication' => 'View/NomineeView/viewNomineeApplication.php',
            'AcceptNomineeApplication' => 'View/NomineeView/acceptNomineeApplication.php',
            'RejectNomineeApplication' => 'View/NomineeView/rejectNomineeApplication.php',
            'PublishNomineeApplications' => 'View/NomineeView/publishNomineeApplication.php',
            'ViewPublishNomineeApplications' => 'View/NomineeView/viewPublishNomineeApplication.php'
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
            case 'rule':
                $this->allowedPaths = array_merge($rulePaths, $assetPaths);
                break;
            case 'election_registration_form':
                $this->allowedPaths = array_merge($electionRegistrationFormPaths, $assetPaths);
                break;
            case 'nominee_application':
                $this->allowedPaths = array_merge($nomineeApplicationPaths, $assetPaths);
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
