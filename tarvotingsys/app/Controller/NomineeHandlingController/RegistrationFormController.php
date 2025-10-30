<?php

namespace Controller\NomineeHandlingController;

use Model\NomineeHandlingModel\RegistrationFormModel;
use Model\VotingModel\ElectionEventModel;
use FileHelper;

class RegistrationFormController
{
    private RegistrationFormModel $registrationFormModel;
    private ElectionEventModel $electionEventModel;
    private FileHelper $fileHelper;

    public function __construct()
    {
        $this->registrationFormModel = new RegistrationFormModel();
        $this->electionEventModel = new ElectionEventModel();
        $this->fileHelper = new FileHelper('election_registration_form');
    }

    public function listRegistrationForms()
    {
        $registrationForms = $this->registrationFormModel->getAllRegistrationForms();
        $filePath = $this->fileHelper->getFilePath('ElectionRegistrationFormList');
        
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Create Registration Form ----------------------------------------- //
    // Display Create Registration Form
    public function createRegistrationForm()
    {
        $registrationFormAttributes = $this->registrationFormModel->getAllRegistrationFormAttributes();
        $electionEvents = $this->electionEventModel->getAllElectionEvents(); // <-- ADD
        // Optional: initialize to avoid undefined notices in the view
        $registrationFormData = $registrationFormData ?? [];
        $errors = $errors ?? [];
        $fieldErrors = $fieldErrors ?? [];

        $filePath = $this->fileHelper->getFilePath('CreateElectionRegistrationForm');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }


    // Store New Registration Form + Validation
    public function storeRegistrationForm()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createRegistrationForm();
            return;
        }

        // Collect input
        $registrationFormData = [
            'registrationFormTitle' => trim($_POST['registrationFormTitle'] ?? ''),
            'registrationStartDate' => $_POST['registrationStartDate'] ?? '',
            'registrationStartTime' => $_POST['registrationStartTime'] ?? '',
            'registrationEndDate'   => $_POST['registrationEndDate'] ?? '',
            'registrationEndTime'   => $_POST['registrationEndTime'] ?? '',
            'electionID'            => $_POST['electionID'] ?? '',
            'attributes'            => $_POST['attributes'] ?? [],
        ];

        $errors = [];
        $fieldErrors = [];

        // Build DateTime objects safely (may remain null if inputs missing)
        $startDt = null;
        $endDt   = null;

        if ($registrationFormData['registrationStartDate'] !== '' && $registrationFormData['registrationStartTime'] !== '') {
            $startDt = \DateTime::createFromFormat('Y-m-d H:i',
                $registrationFormData['registrationStartDate'].' '.$registrationFormData['registrationStartTime']);
        }
        if ($registrationFormData['registrationEndDate'] !== '' && $registrationFormData['registrationEndTime'] !== '') {
            $endDt = \DateTime::createFromFormat('Y-m-d H:i',
                $registrationFormData['registrationEndDate'].' '.$registrationFormData['registrationEndTime']);
        }

        // ---- Title
        if ($registrationFormData['registrationFormTitle'] === '') {
            $errors[] = "Registration Form Title is required.";
            $fieldErrors['registrationFormTitle'][] = "Please enter a title for the registration form.";
        } elseif (mb_strlen($registrationFormData['registrationFormTitle']) > 50) {
            $errors[] = "Registration Form Title must not exceed 50 characters.";
            $fieldErrors['registrationFormTitle'][] = "Title is too long.";
        }

        // ---- Start date/time (required + future time rule)
        if ($registrationFormData['registrationStartDate'] === '') {
            $errors[] = "Registration Start Date is required.";
            $fieldErrors['registrationStartDate'][] = "Please select a start date.";
        } elseif ($registrationFormData['registrationStartDate'] < date('Y-m-d')) {
            $errors[] = "Registration Start Date cannot be in the past.";
            $fieldErrors['registrationStartDate'][] = "Invalid start date.";
        }

        if ($registrationFormData['registrationStartTime'] === '') {
            $errors[] = "Registration Start Time is required.";
            $fieldErrors['registrationStartTime'][] = "Please select a start time.";
        } elseif ($registrationFormData['registrationStartDate'] === date('Y-m-d') && $registrationFormData['registrationStartTime'] <= date('H:i')) {
            $errors[] = "Registration Start Time must be in the future.";
            $fieldErrors['registrationStartTime'][] = "Invalid start time.";
        }

        // ---- End date/time (required)
        if ($registrationFormData['registrationEndDate'] === '') {
            $errors[] = "Registration End Date is required.";
            $fieldErrors['registrationEndDate'][] = "Please select an end date.";
        }
        if ($registrationFormData['registrationEndTime'] === '') {
            $errors[] = "Registration End Time is required.";
            $fieldErrors['registrationEndTime'][] = "Please select an end time.";
        }

        // ---- Election event exists?
        $event = null;
        if (empty($registrationFormData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } else {
            $event = $this->electionEventModel->getElectionEventById($registrationFormData['electionID']);
            if (!$event) {
                $errors[] = "Selected Election Event does not exist.";
                $fieldErrors['electionID'][] = "Invalid Election Event.";
            }

            // Only one registration form per election event
            if ($event && $this->registrationFormModel->existsForElection((int)$registrationFormData['electionID'])) {
                $errors[] = "This election event already has a registration form.";
                $fieldErrors['electionID'][] = "Only one registration form is allowed per election event.";
            }

        }

        // ---- Compare start & end (only if both DateTimes exist and no earlier field errors on date/time)
        if ($startDt && $endDt
            && empty($fieldErrors['registrationStartDate']) && empty($fieldErrors['registrationStartTime'])
            && empty($fieldErrors['registrationEndDate']) && empty($fieldErrors['registrationEndTime'])) {

            $startTs = $startDt->getTimestamp();
            $endTs   = $endDt->getTimestamp();

            if ($endTs <= $startTs) {
                $errors[] = "Registration End Date and Time must be after Start Date and Time.";
                $fieldErrors['registrationEndDate'][] = "End Date & Time must be after Start Date & Time.";
                $fieldErrors['registrationEndTime'][] = "End Date & Time must be after Start Date & Time.";
            } elseif ($endTs < strtotime('+3 days', $startTs)) {
                $errors[] = "Registration period must last at least 3 days from the start.";
                $fieldErrors['registrationEndDate'][] = "Choose an end date/time ≥ 3 days after the start.";
            }

            // ---- Registration window must be inside election window (only if event is valid)
            if ($event) {
                $eventStartTs = strtotime($event['electionStartDate']);
                $eventEndTs   = strtotime($event['electionEndDate']);

                if ($startTs < $eventStartTs) {
                    $fieldErrors['registrationStartDate'][] = "Registration must start on or after the election event start.";
                    $fieldErrors['registrationStartTime'][] = "Earliest allowed: ".date('Y-m-d H:i', $eventStartTs).".";
                    $errors[] = "Registration start must not be earlier than the election event start.";
                }
                if ($endTs > $eventEndTs) {
                    $fieldErrors['registrationEndDate'][] = "Registration must end on or before the election event end.";
                    $fieldErrors['registrationEndTime'][] = "Latest allowed: ".date('Y-m-d H:i', $eventEndTs).".";
                    $errors[] = "Registration end must not be later than the election event end.";
                }
            }
        }

        // ---- Attributes: must select at least 3
        $attrs = is_array($registrationFormData['attributes']) ? array_values($registrationFormData['attributes']) : [];
        if (count($attrs) < 3) {
            $errors[] = "Please select at least 3 registration attributes.";
            $fieldErrors['attributes'][] = "Select ≥ 3 attributes.";
        }

        // If invalid -> re-render with same data
        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getAllElectionEvents();
            $registrationFormAttributes = $this->registrationFormModel->getAllRegistrationFormAttributes();
            $filePath = $this->fileHelper->getFilePath('CreateElectionRegistrationForm');
            if ($filePath && file_exists($filePath)) { include $filePath; } else { echo "View file not found."; }
            return;
        }

        // Build DATETIME strings for DB columns registerStartDate/registerEndDate
        $registrationFormData['registerStartDateTime'] = $startDt->format('Y-m-d H:i:s');
        $registrationFormData['registerEndDateTime']   = $endDt->format('Y-m-d H:i:s');

        // Persist (transactional)
        $formId = $this->registrationFormModel->createRegistrationFormWithAttributes(
            $registrationFormData,
            $attrs
        );

        if ($formId === false) {
            \set_flash('danger', 'Failed to create Registration Form. Please try again.');
            $this->createRegistrationForm();
            return;
        }

        \set_flash('success', 'Registration Form created successfully.');
        header('Location: /election-registration-form');
    }

    // ----------------------------------------- Edit Registration Form ----------------------------------------- //
    // Display Edit Registration Form
    public function editRegistrationForm($registrationFormId)
    {
        // Fetch existing form data
        $form = $this->registrationFormModel->getRegistrationFormById($registrationFormId);
        if (!$form) {
            echo "<div class='alert alert-danger m-4'>Registration Form not found.</div>";
            return;
        }

        // Split datetime into date/time
        $registrationFormEditionData = [
            'registrationFormID'    => $form['registrationFormID'],
            'registrationFormTitle' => $form['registrationFormTitle'],
            'registrationStartDate' => date('Y-m-d', strtotime($form['registerStartDate'])),
            'registrationStartTime' => date('H:i', strtotime($form['registerStartDate'])),
            'registrationEndDate'   => date('Y-m-d', strtotime($form['registerEndDate'])),
            'registrationEndTime'   => date('H:i', strtotime($form['registerEndDate'])),
            'electionID'            => $form['electionID'],
        ];

        $registrationFormEditionAttributes = $this->registrationFormModel->getAllRegistrationFormAttributes();
        $selectedEditionAttributes = $this->registrationFormModel->getAttributesByFormId($registrationFormId);
        $electionEvents = $this->electionEventModel->getAllElectionEvents();
        $errors = [];
        $fieldErrors = [];

        $filePath = $this->fileHelper->getFilePath('EditElectionRegistrationForm');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }


    // Store Edited Registration Form + Validation
    public function editStoreRegistrationForm($registrationFormId)
    {
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editRegistrationForm($registrationFormId);
            return;
        }

        // Collect Registration Form Input
        $registrationFormEditionData = [
            'registrationFormID'    => $registrationFormId,
            'registrationFormTitle' => trim($_POST['registrationFormTitle'] ?? ''),
            'registrationStartDate' => $_POST['registrationStartDate'] ?? '',
            'registrationStartTime' => $_POST['registrationStartTime'] ?? '',
            'registrationEndDate'   => $_POST['registrationEndDate'] ?? '',
            'registrationEndTime'   => $_POST['registrationEndTime'] ?? '',
            'electionID'            => $_POST['electionID'] ?? '',
            'attributes'            => $_POST['attributes'] ?? [],
        ];

        // ---------------- Validation Registration Form ---------------- //
        $errors = [];
        $fieldErrors = [];

        // Build DateTime objects safely (may remain null if inputs missing)
        $startDt = null;
        $endDt   = null;

        if ($registrationFormEditionData['registrationStartDate'] !== '' && $registrationFormEditionData['registrationStartTime'] !== '') {
            $startDt = \DateTime::createFromFormat('Y-m-d H:i',
                $registrationFormEditionData['registrationStartDate'].' '.$registrationFormEditionData['registrationStartTime']);
        }
        if ($registrationFormEditionData['registrationEndDate'] !== '' && $registrationFormEditionData['registrationEndTime'] !== '') {
            $endDt = \DateTime::createFromFormat('Y-m-d H:i',
                $registrationFormEditionData['registrationEndDate'].' '.$registrationFormEditionData['registrationEndTime']);
        }

        // ---- Title
        if ($registrationFormEditionData['registrationFormTitle'] === '') {
            $errors[] = "Registration Form Title is required.";
            $fieldErrors['registrationFormTitle'][] = "Please enter a title for the registration form.";
        } elseif (mb_strlen($registrationFormEditionData['registrationFormTitle']) > 50) {
            $errors[] = "Registration Form Title must not exceed 50 characters.";
            $fieldErrors['registrationFormTitle'][] = "Title is too long.";
        }

        // ---- Start date/time (required + future time rule)
        if ($registrationFormEditionData['registrationStartDate'] === '') {
            $errors[] = "Registration Start Date is required.";
            $fieldErrors['registrationStartDate'][] = "Please select a start date.";
        } 

        if ($registrationFormEditionData['registrationStartTime'] === '') {
            $errors[] = "Registration Start Time is required.";
            $fieldErrors['registrationStartTime'][] = "Please select a start time.";
        }

        // ---- End date/time (required)
        if ($registrationFormEditionData['registrationEndDate'] === '') {
            $errors[] = "Registration End Date is required.";
            $fieldErrors['registrationEndDate'][] = "Please select an end date.";
        }
        if ($registrationFormEditionData['registrationEndTime'] === '') {
            $errors[] = "Registration End Time is required.";
            $fieldErrors['registrationEndTime'][] = "Please select an end time.";
        }

        // ---- Election event exists?
        $event = null;
        if (empty($registrationFormEditionData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } else {
            $event = $this->electionEventModel->getElectionEventById($registrationFormEditionData['electionID']);
            if (!$event) {
                $errors[] = "Selected Election Event does not exist.";
                $fieldErrors['electionID'][] = "Invalid Election Event.";
            }

            // Only one registration form per election event (ignores the current registration form)
            if ($event && $this->registrationFormModel->existsForOtherElection(
                (int)$registrationFormEditionData['electionID'],
                (int)$registrationFormId
            )) {
                $errors[] = "This election event already has another registration form.";
                $fieldErrors['electionID'][] = "Only one registration form is allowed per election event.";
            }


        }

        // ---- Compare start & end (only if both DateTimes exist and no earlier field errors on date/time)
        if ($startDt && $endDt
            && empty($fieldErrors['registrationStartDate']) && empty($fieldErrors['registrationStartTime'])
            && empty($fieldErrors['registrationEndDate']) && empty($fieldErrors['registrationEndTime'])) {

            $startTs = $startDt->getTimestamp();
            $endTs   = $endDt->getTimestamp();

            if ($endTs <= $startTs) {
                $errors[] = "Registration End Date and Time must be after Start Date and Time.";
                $fieldErrors['registrationEndDate'][] = "End Date & Time must be after Start Date & Time.";
                $fieldErrors['registrationEndTime'][] = "End Date & Time must be after Start Date & Time.";
            } elseif ($endTs < strtotime('+3 days', $startTs)) {
                $errors[] = "Registration period must last at least 3 days from the start.";
                $fieldErrors['registrationEndDate'][] = "Choose an end date/time ≥ 3 days after the start.";
            }

            // ---- Registration window must be inside election window (only if event is valid)
            if ($event) {
                $eventStartTs = strtotime($event['electionStartDate']);
                $eventEndTs   = strtotime($event['electionEndDate']);

                if ($startTs < $eventStartTs) {
                    $fieldErrors['registrationStartDate'][] = "Registration must start on or after the election event start.";
                    $fieldErrors['registrationStartTime'][] = "Earliest allowed: ".date('Y-m-d H:i', $eventStartTs).".";
                    $errors[] = "Registration start must not be earlier than the election event start.";
                }
                if ($endTs > $eventEndTs) {
                    $fieldErrors['registrationEndDate'][] = "Registration must end on or before the election event end.";
                    $fieldErrors['registrationEndTime'][] = "Latest allowed: ".date('Y-m-d H:i', $eventEndTs).".";
                    $errors[] = "Registration end must not be later than the election event end.";
                }
            }
        }

        // ---- Attributes: must select at least 3
        $attrs = is_array($registrationFormEditionData['attributes']) ? array_values($registrationFormEditionData['attributes']) : [];
        if (count($attrs) < 3) {
            $errors[] = "Please select at least 3 registration attributes.";
            $fieldErrors['attributes'][] = "Select ≥ 3 attributes.";
        }

        // If invalid -> re-render with same data
        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getAllElectionEvents();
            $registrationFormEditionAttributes = $this->registrationFormModel->getAllRegistrationFormAttributes();
            $selectedEditionAttributes = $attrs;

            $filePath = $this->fileHelper->getFilePath('EditElectionRegistrationForm');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }
        // Build DATETIME strings for DB columns registerStartDate/registerEndDate
        $registrationFormEditionData['registerStartDateTime'] = $startDt->format('Y-m-d H:i:s');
        $registrationFormEditionData['registerEndDateTime']   = $endDt->format('Y-m-d H:i:s');

        // Persist (transactional)
        $formId = $this->registrationFormModel->updateRegistrationFormWithAttributes(
            $registrationFormEditionData,
            $attrs
        );

        if ($formId === false) {
            \set_flash('danger', 'Failed to update Registration Form. Please try again.');
            $this->createRegistrationForm();
            return;
        }

        \set_flash('success', 'Registration Form updated successfully.');
        header('Location: /election-registration-form');

    }


    // ----------------------------------------- View Registration Form Details ----------------------------------------- //
    public function viewRegistrationForm($registrationFormId)
    {
        $registrationFormData = $this->registrationFormModel->getRegistrationFormById($registrationFormId);
        if (!$registrationFormData) {
            echo "<div class='alert alert-danger m-4'>Registration Form not found.</div>";
            return;
        }

        $registrationFormAttributes = $this->registrationFormModel->getAttributesByFormId($registrationFormId);

        $filePath = $this->fileHelper->getFilePath('ViewElectionRegistrationForm');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Delete Registration Form ----------------------------------------- //
    public function deleteRegistrationForm($registrationFormId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /election-registration-form');
            return;
        }

        $this->registrationFormModel->deleteRegistrationForm($registrationFormId);
        \set_flash('success', 'Registration Form deleted successfully.');
        header('Location: /election-registration-form');
    }

}