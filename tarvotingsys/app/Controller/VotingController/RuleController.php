<?php

namespace Controller\VotingController;

use Model\VotingModel\RuleModel;
use Model\VotingModel\ElectionEventModel;
use FileHelper;

class RuleController
{
    private $ruleModel;
    private $electionEventModel;
    private $fileHelper;

    public function __construct()
    {
        $this->ruleModel = new RuleModel();
        $this->electionEventModel = new ElectionEventModel();
        $this->fileHelper = new FileHelper("rule");
    }

    public function listRules()
    {
        $rules = $this->ruleModel->getAllRules();
        $filePath = $this->fileHelper->getFilePath('RuleList');

        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Create Rules ----------------------------------------- //
    // Display Create Rule Form
    public function createRule()
    {
        $electionEvents = $this->electionEventModel->getAllElectionEvents();
        $filePath = $this->fileHelper->getFilePath('CreateRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Store New Rule + Validation
    public function storeRule() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createRule();
            return;
        }

        //Collect Rule Input Data
        $ruleCreationData = [
            'ruleTitle' => $_POST['ruleTitle'] ?? '',
            'content' => $_POST['content'] ?? '',
            'electionID' => $_POST['electionID'] ?? null,
        ];

        // --------- Validate Rule input --------- //
        $errors = [];
        $fieldErrors = [];

        // Rule Title Validation
        if (empty($ruleCreationData['ruleTitle'])) {
            $errors[] = "Rule Title is required.";
            $fieldErrors['ruleTitle'][] = "Please provide a Rule Title.";
        } elseif (strlen($ruleCreationData['ruleTitle']) < 5) {
            $errors[] = "Election Event Name must be at least 5 characters long.";
            $fieldErrors['ruleTitle'][] = "At least 5 characters.";
        }

        // Rule Content Validation
        if (empty($ruleCreationData['content'])) {
            $errors[] = "Rule Content is required";
            $fieldErrors['content'][] = "Please add a Rule Content.";
        }

        // Election Event Validation
        if (empty($ruleCreationData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } elseif (!$this->electionEventModel->getElectionEventById($ruleCreationData['electionID'])) {
            $errors[] = "Selected Election Event does not exist.";
            $fieldErrors['electionID'][] = "Please select a valid Election Event.";
        }

        // Invalid Input -> put back the SAME view with errors + old values
        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getAllElectionEvents();
            $filePath = $this->fileHelper->getFilePath('CreateRule');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Valid Input -> Store the Rule
        $this->ruleModel->createRule($ruleCreationData);
        // Redirect to Rule List after successful creation
        \set_flash('success', 'Rule created successfully.');
        header('Location: /rule');
    }

    // ----------------------------------------- Edit Rules ----------------------------------------- //
    // Display Edit Rule Form
    public function editRule($ruleID) {
        $ruleData = $this->ruleModel->getRuleById($ruleID);
        $electionEvents = $this->electionEventModel->getAllElectionEvents();

        $filePath = $this->fileHelper->getFilePath('EditRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Store Edited Rule + Validation
    public function editStoreRule($ruleID) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editRule($ruleID);
            return;
        }

        // Collect Rule Input Data
        $ruleData = [
            'ruleTitle' => $_POST['ruleTitle'] ?? '',
            'content' => $_POST['content'] ?? '',
            'electionID' => $_POST['electionID'] ?? null,
        ];

        // --------- Validate Rule input --------- //
        $errors = [];
        $fieldErrors = [];

        // Rule Title Validation
        if (empty($ruleData['ruleTitle'])) {
            $errors[] = "Rule Title is required.";
            $fieldErrors['ruleTitle'][] = "Please provide a Rule Title.";
        } elseif (strlen($ruleData['ruleTitle']) < 5) {
            $errors[] = "Election Event Name must be at least 5 characters long.";
            $fieldErrors['ruleTitle'][] = "At least 5 characters.";
        }

        if (empty($ruleData['content'])) {
            $errors[] = "Rule Content is required";
            $fieldErrors['content'][] = "Please add a Rule Content.";
        }

        if (empty($ruleData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } elseif (!$this->electionEventModel->getElectionEventById($ruleData['electionID'])) {
            $errors[] = "Selected Election Event does not exist.";
            $fieldErrors['electionID'][] = "Please select a valid Election Event.";
        }

        // Invalid Input -> put back the SAME view with errors + old values
        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getAllElectionEvents();
            $filePath = $this->fileHelper->getFilePath('EditRule');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Valid Input -> Update the Rule
        $this->ruleModel->updateRule($ruleID, $ruleData);
        // Redirect to Rule List after successful update
        \set_flash('success', 'Rule updated successfully.');
        header('Location: /rule');
    }

    // ----------------------------------------- View Rule Details ----------------------------------------- //
    public function viewRule($ruleID) {
        $ruleData = $this->ruleModel->getRuleById($ruleID);
        $filePath = $this->fileHelper->getFilePath('ViewRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Delete Rule ----------------------------------------- //
    public function deleteRule($ruleID) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /election-event');
            return;
        }

        $this->ruleModel->deleteRule($ruleID);
        \set_flash('success', 'Rule deleted successfully.');
        header('Location: /rule');
    }

}