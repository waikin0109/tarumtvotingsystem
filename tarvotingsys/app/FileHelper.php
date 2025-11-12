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
            'DeleteRule' => 'View/VotingView/deleteRule.php',
            'RuleListStudent' => 'View/VotingView/ruleStudent.php'
        ]);

        $electionRegistrationFormPaths = $this->prefixWithBase([
            'ElectionRegistrationFormList' => 'View/NomineeHandlingView/registrationForm.php',
            'CreateElectionRegistrationForm' => 'View/NomineeHandlingView/createRegistrationForm.php',
            'EditElectionRegistrationForm' => 'View/NomineeHandlingView/editRegistrationForm.php',
            'ViewElectionRegistrationForm' => 'View/NomineeHandlingView/viewRegistrationForm.php',
            'DeleteElectionRegistrationForm' => 'View/NomineeHandlingView/deleteRegistrationForm.php',
            'ElectionRegistrationFormListStudent' => 'View/NomineeHandlingView/registrationFormListStudent.php',
            
        ]);

        $nomineeApplicationPaths = $this->prefixWithBase([
            'NomineeApplicationList' => 'View/NomineeView/nomineeApplication.php',
            'CreateNomineeApplication' => 'View/NomineeView/createNomineeApplication.php',
            'EditNomineeApplication' => 'View/NomineeView/editNomineeApplication.php',
            'ViewNomineeApplication' => 'View/NomineeView/viewNomineeApplication.php',
            'AcceptNomineeApplication' => 'View/NomineeView/acceptNomineeApplication.php',
            'RejectNomineeApplication' => 'View/NomineeView/rejectNomineeApplication.php',
            'PublishNomineeApplications' => 'View/NomineeView/publishNomineeApplication.php',
            'ViewPublishNomineeApplications' => 'View/NomineeView/viewPublishNomineeApplication.php',
            'ApplyNomineeApplicationStudent' => 'View/NomineeHandlingView/applyRegistrationForm.php',
            'NomineeApplicationListStudent' => 'View/NomineeView/nomineeFinalList.php'
        ]);

        $campaignMaterialPaths = $this->prefixWithBase([
            'CampaignMaterialList' => 'View/CampaignHandlingView/campaignMaterial.php',
            'CreateCampaignMaterial' => 'View/CampaignHandlingView/createCampaignMaterial.php',
            'EditCampaignMaterial' => 'View/CampaignHandlingView/editCampaignMaterial.php',
            'ViewCampaignMaterial' => 'View/CampaignHandlingView/viewCampaignMaterial.php',
            'AcceptCampaignMaterial' => 'View/CampaignHandlingView/acceptCampaignMaterial.php',
            'RejectCampaignMaterial' => 'View/CampaignHandlingView/rejectCampaignMaterial.php',
        ]);

        $scheduleLocationPaths = $this->prefixWithBase([
            'ScheduleLocationList' => 'View/CampaignHandlingView/scheduleLocation.php',
            'CreateScheduleLocation' => 'View/CampaignHandlingView/createScheduleLocation.php',
            'EditScheduleLocation' => 'View/CampaignHandlingView/editScheduleLocation.php',
            'ViewScheduleLocation' => 'View/CampaignHandlingView/viewScheduleLocation.php',
            'ScheduleBoard' => 'View/CampaignHandlingView/scheduleLocationBoard.php',
            'ViewCampaignSchedule' => 'View/CampaignHandlingView/viewCampaignSchedule.php',
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
            'StudentNomineeAnnouncementList'=> 'View/VotingView/studentNomineeAnnouncementList.php',
            'StudentNomineeAnnouncementDetails'=> 'View/VotingView/studentNomineeAnnouncementDetails.php',
            
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
            case 'rule':
                $this->allowedPaths = array_merge($rulePaths, $assetPaths);
                break;
            case 'election_registration_form':
                $this->allowedPaths = array_merge($electionRegistrationFormPaths, $assetPaths);
                break;
            case 'nominee_application':
                $this->allowedPaths = array_merge($nomineeApplicationPaths, $assetPaths);
                break;
            case 'campaign_material':
                $this->allowedPaths = array_merge($campaignMaterialPaths, $assetPaths);
                break;
            case 'schedule_location':
                $this->allowedPaths = array_merge($scheduleLocationPaths, $assetPaths);
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
