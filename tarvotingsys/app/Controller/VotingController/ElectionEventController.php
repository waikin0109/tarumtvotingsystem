<?php

namespace Controller\VotingController;


use Model\VotingModel\ElectionEventModel;
use FileHelper;

class ElectionEventController
{
    private $electionEventModel;
    private $fileHelper;

    public function __construct()
    {
        $this->electionEventModel = new ElectionEventModel();
        $this->fileHelper = new FileHelper("election_event");
    }

    public function listElectionEvents()
    {
        $electionEvents = $this->electionEventModel->getAllElectionEvents(); // ensure method name matches your model
        $filePath = $this->fileHelper->getFilePath('ElectionEventList');

        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Create Election Event ----------------------------------------- //
    // Display Create Election Event Form
    public function CreateElectionEvent()
    {
        $filePath = $this->fileHelper->getFilePath('CreateElectionEvent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Create Election Event  + Validation
    public function storeElectionEvent() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->CreateElectionEvent();
            return;
        }

        // Collect Election Event input
        $electionEventCreationData = [
            'electionEventName'        => trim($_POST['electionEventName'] ?? ''),
            'electionEventDescription' => trim($_POST['electionEventDescription'] ?? ''),
            'electionEventStartDate'   => $_POST['electionEventStartDate'] ?? '',
            'electionEventStartTime'   => $_POST['electionEventStartTime'] ?? '',
            'electionEventEndDate'     => $_POST['electionEventEndDate'] ?? '',
            'electionEventEndTime'     => $_POST['electionEventEndTime'] ?? '',
            'electionEventStatus'      => ''
        ];

        // --------- Validate Election Event input --------- //
        $errors = [];
        $fieldErrors = [];

        // Validate Election Event Name
        if ($electionEventCreationData['electionEventName'] == '') {
            $errors[] = "Election Event Name is required.";
            $fieldErrors['electionEventName'][] = "Please enter an Election Event Name.";
        } elseif (mb_strlen($electionEventCreationData['electionEventName']) < 3) {
            $errors[] = "Election Event Name must be at least 3 characters.";
            $fieldErrors['electionEventName'][] = "At least 3 characters.";
        }

        // Validate Election Event Description
        if ($electionEventCreationData['electionEventDescription'] == '') {
            $errors[] = "Election Event Description is required.";
            $fieldErrors['electionEventDescription'][] = "Please add a Election Event Description.";
        }

        // Validate Election Event Start Date & Time
        if ($electionEventCreationData['electionEventStartDate'] == '') {
            $errors[] = "Election Event Start Date is required.";
            $fieldErrors['electionEventStartDate'][] = "Select an Election Event Start Date.";
        } else if ($electionEventCreationData['electionEventStartDate'] < date('Y-m-d')) {
            $errors[] = "Election Event Start Date cannot be in the past.";
            $fieldErrors['electionEventStartDate'][] = "Election Event Start Date must be today or later.";
        } else if ($electionEventCreationData['electionEventStartTime'] == '') {
            $errors[] = "Election Event Start Time is required.";
            $fieldErrors['electionEventStartTime'][] = "Select an Election Event Start Time.";
        } else if ($electionEventCreationData['electionEventStartDate'] == date('Y-m-d') && $electionEventCreationData['electionEventStartTime'] <= date('H:i')) {
            $errors[] = "Election Event Start Time cannot be in the past.";
            $fieldErrors['electionEventStartTime'][] = "Election Event Start Time must be later than current time.";
        }

        // Validate Election Event End Date & Time
        if ($electionEventCreationData['electionEventEndDate'] == '') {
            $errors[] = "Election Event End Date is required.";
            $fieldErrors['electionEventEndDate'][] = "Select an Election Event End Date.";
        } else if ($electionEventCreationData['electionEventEndTime'] == '') {
            $errors[] = "Election Event End Time is required.";
            $fieldErrors['electionEventEndTime'][] = "Select an Election Event End Time.";
        } else {
            $startTs = strtotime($electionEventCreationData['electionEventStartDate'].' '.$electionEventCreationData['electionEventStartTime']);
            $endTs   = strtotime($electionEventCreationData['electionEventEndDate'].' '.$electionEventCreationData['electionEventEndTime']);

            // End must be after Start
            if ($endTs <= $startTs) {
                $errors[] = "Election Event End Date & Time must be after Start Date & Time.";
                $fieldErrors['electionEventEndDate'][] = "End Date & Time must be after Start Date & Time.";
                $fieldErrors['electionEventEndTime'][] = "End Date & Time must be after Start Date & Time.";
            }
            // nd must be at least 7 days after Start
            else if ($endTs < strtotime('+7 days', $startTs)) {
                $errors[] = "Election Event must last at least 7 days from the start.";
                $fieldErrors['electionEventEndDate'][] = "Choose an end date/time ≥ 7 days after the start.";
                $fieldErrors['electionEventEndTime'][] = "Choose an end date/time ≥ 7 days after the start.";
            }
        }


        // check status
        if (
            $electionEventCreationData['electionEventStartDate'] > date('Y-m-d') ||
            (
                $electionEventCreationData['electionEventStartDate'] == date('Y-m-d') &&
                $electionEventCreationData['electionEventStartTime'] > date('H:i')
            )
        ) {
            $electionEventCreationData['electionEventStatus'] = 'PENDING';
        } elseif (
            (
                $electionEventCreationData['electionEventStartDate'] < date('Y-m-d') ||
                (
                    $electionEventCreationData['electionEventStartDate'] == date('Y-m-d') &&
                    $electionEventCreationData['electionEventStartTime'] <= date('H:i')
                )
            ) &&
            (
                $electionEventCreationData['electionEventEndDate'] > date('Y-m-d') ||
                (
                    $electionEventCreationData['electionEventEndDate'] == date('Y-m-d') &&
                    $electionEventCreationData['electionEventEndTime'] > date('H:i')
                )
            )
        ) {
            $electionEventCreationData['electionEventStatus'] = 'ONGOING';
        } else {
            $electionEventCreationData['electionEventStatus'] = 'COMPLETED';
        }


        // Invalid input -> put back the SAME view with errors + old values
        if (!empty($errors)) {
            $filePath = $this->fileHelper->getFilePath('CreateElectionEvent');
            if ($filePath && file_exists($filePath)) {
                include $filePath; // view will use $errors, $fieldErrors, $electionEventCreationData
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Valid input -> save then redirect to list
        $this->electionEventModel->createElectionEvent($electionEventCreationData);
        // Redirect to election event list with success message
        \set_flash('success', 'Election Event created successfully.');
        header('Location: /election-event');
    }

    // ----------------------------------------- Edit Election Event ----------------------------------------- //
    // Display Edit Election Event Form
    public function editElectionEvent($electionID)
    {
        $electionEventData = $this->electionEventModel->getElectionEventById($electionID);
        $filePath = $this->fileHelper->getFilePath('EditElectionEvent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Edit Election Event + Validation
    public function editStoreElectionEvent($electionID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editElectionEvent($electionID);
            return;
        }

        // Collect Election Event input
        $electionEventData = [
            'electionEventName'        => trim($_POST['electionEventName'] ?? ''),
            'electionEventDescription' => trim($_POST['electionEventDescription'] ?? ''),
            'electionEventStartDate'   => $_POST['electionEventStartDate'] ?? '',
            'electionEventStartTime'   => $_POST['electionEventStartTime'] ?? '',
            'electionEventEndDate'     => $_POST['electionEventEndDate'] ?? '',
            'electionEventEndTime'     => $_POST['electionEventEndTime'] ?? '',
        ];

        // --------- Validate Election Event input --------- //
        $errors = [];
        $fieldErrors = [];

        // Validate Election Event Name
        if ($electionEventData['electionEventName'] == '') {
            $errors[] = "Election Event Name is required.";
            $fieldErrors['electionEventName'][] = "Please enter an Election Event Name.";
        } elseif (mb_strlen($electionEventData['electionEventName']) < 3) {
            $errors[] = "Election Event Name must be at least 3 characters.";
            $fieldErrors['electionEventName'][] = "At least 3 characters.";
        }

        // Validate Election Event Description
        if ($electionEventData['electionEventDescription'] == '') {
            $errors[] = "Election Event Description is required.";
            $fieldErrors['electionEventDescription'][] = "Please add a Election Event Description.";
        }

        // Validate Election Event Start Date & Time
        if ($electionEventData['electionEventStartDate'] == '') {
            $errors[] = "Election Event Start Date is required.";
            $fieldErrors['electionEventStartDate'][] = "Select an Election Event Start Date.";
        } else if ($electionEventData['electionEventStartDate'] < date('Y-m-d')) {
            $errors[] = "Election Event Start Date cannot be in the past.";
            $fieldErrors['electionEventStartDate'][] = "Election Event Start Date must be today or later.";
        } else if ($electionEventData['electionEventStartTime'] == '') {
            $errors[] = "Election Event Start Time is required.";
            $fieldErrors['electionEventStartTime'][] = "Select an Election Event Start Time.";
        } else if ($electionEventData['electionEventStartDate'] == date('Y-m-d') && $electionEventData['electionEventStartTime'] <= date('H:i')) {
            $errors[] = "Election Event Start Time cannot be in the past.";
            $fieldErrors['electionEventStartTime'][] = "Election Event Start Time must be later than current time.";
        }

        // Validate Election Event End Date & Time
        if ($electionEventData['electionEventEndDate'] == '') {
            $errors[] = "Election Event End Date is required.";
            $fieldErrors['electionEventEndDate'][] = "Select an Election Event End Date.";
        } else if ($electionEventData['electionEventEndTime'] == '') {
            $errors[] = "Election Event End Time is required.";
            $fieldErrors['electionEventEndTime'][] = "Select an Election Event End Time.";
        } else {
            $startTs = strtotime($electionEventData['electionEventStartDate'].' '.$electionEventData['electionEventStartTime']);
            $endTs   = strtotime($electionEventData['electionEventEndDate'].' '.$electionEventData['electionEventEndTime']);

            // End must be after Start
            if ($endTs <= $startTs) {
                $errors[] = "Election Event End Date & Time must be after Start Date & Time.";
                $fieldErrors['electionEventEndDate'][] = "End Date & Time must be after Start Date & Time.";
                $fieldErrors['electionEventEndTime'][] = "End Date & Time must be after Start Date & Time.";
            }
            // nd must be at least 7 days after Start
            else if ($endTs < strtotime('+7 days', $startTs)) {
                $errors[] = "Election Event must last at least 7 days from the start.";
                $fieldErrors['electionEventEndDate'][] = "Choose an end date/time ≥ 7 days after the start.";
                $fieldErrors['electionEventEndTime'][] = "Choose an end date/time ≥ 7 days after the start.";
            }
        }

        // check status
        if (
            $electionEventData['electionEventStartDate'] > date('Y-m-d') ||
            (
                $electionEventData['electionEventStartDate'] == date('Y-m-d') &&
                $electionEventData['electionEventStartTime'] > date('H:i')
            )
        ) {
            $electionEventData['electionEventStatus'] = 'PENDING';
        } elseif (
            (
                $electionEventData['electionEventStartDate'] < date('Y-m-d') ||
                (
                    $electionEventData['electionEventStartDate'] == date('Y-m-d') &&
                    $electionEventData['electionEventStartTime'] <= date('H:i')
                )
            ) &&
            (
                $electionEventData['electionEventEndDate'] > date('Y-m-d') ||
                (
                    $electionEventData['electionEventEndDate'] == date('Y-m-d') &&
                    $electionEventData['electionEventEndTime'] > date('H:i')
                )
            )
        ) {
            $electionEventData['electionEventStatus'] = 'ONGOING';
        } else {
            $electionEventData['electionEventStatus'] = 'COMPLETED';
        }

        // Invalid input -> put back the SAME view with errors + old values
        if (!empty($errors)) {
            // Map POST values to the same keys your view expects
            $electionEventData = [
                'electionID'  => $electionID,
                'title'       => $_POST['electionEventName']        ?? '',
                'description' => $_POST['electionEventDescription'] ?? '',
                'startDate'   => $_POST['electionEventStartDate']   ?? '',
                'startTime'   => $_POST['electionEventStartTime']   ?? '',
                'endDate'     => $_POST['electionEventEndDate']     ?? '',
                'endTime'     => $_POST['electionEventEndTime']     ?? '',
            ];

            // Load edit form again with same values + validation errors
            $filePath = $this->fileHelper->getFilePath('EditElectionEvent');
            if ($filePath && file_exists($filePath)) {
                include $filePath; // view uses $errors, $fieldErrors, $electionEventData
            } else {
                echo "View file not found.";
            }
            return;
        }


        // Valid input -> save then redirect to list
        $this->electionEventModel->updateElectionEvent($electionID, $electionEventData);
        // Redirect to election event list with success message
        \set_flash('success', 'Election Event updated successfully.');
        header('Location: /election-event');
    }

    // ----------------------------------------- Read Election Event Details ----------------------------------------- //
    public function viewElectionEvent($electionID)
    {
        $electionEventData = $this->electionEventModel->getElectionEventById($electionID);
        $filePath = $this->fileHelper->getFilePath('ViewElectionEvent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }

    }

    // ----------------------------------------- Delete Election Event ----------------------------------------- //
    public function deleteElectionEvent($electionID)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /election-event');
            return;
        }

        $this->electionEventModel->deleteElectionEvent($electionID);
        \set_flash('success', 'Election Event deleted successfully.');
        header('Location: /election-event');
    }

}
