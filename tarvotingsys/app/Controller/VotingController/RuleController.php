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

    // Role Decision Area
    private function requireRole(...$allowed)
    {
        $role = strtoupper($_SESSION['role'] ?? '');
        if (!in_array($role, $allowed, true)) {
            \set_flash('fail', 'You do not have permission to access this page.');
            $this->redirectByRole($role);
        }
    }

    private function redirectByRole($role)
    {
        switch ($role) {
            case 'ADMIN':   
                header('Location: /admin/rule'); 
                break;
            case 'STUDENT': 
                header('Location: /student/rule'); 
                break;
            case 'NOMINEE': 
                header('Location: /nominee/rule'); 
                break;
            default:        
                header('Location: /login'); 
                break;
        }
        exit;
    }

    // Admin Display List
    public function listRules()
    {
        $this->requireRole('ADMIN');
        
        // Paging Setup
        $page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search       = trim($_GET['q'] ?? '');
        $filterStatus = strtoupper(trim($_GET['status'] ?? ''));

        $pager          = $this->ruleModel->getPagedRules($page, 10, $search, $filterStatus);
        $rules = $pager->result;

        $filePath = $this->fileHelper->getFilePath('RuleList');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Student Display List
    public function listRulesStudent()
    {
        $this->requireRole('STUDENT');

        $page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search       = trim($_GET['q'] ?? '');
        $filterStatus = strtoupper(trim($_GET['status'] ?? ''));

        $pager = $this->ruleModel->getPagedRules($page, 10, $search, $filterStatus);
        $rules = $pager->result ?? [];

        $filePath = $this->fileHelper->getFilePath('RuleListStudent');
        if ($filePath && file_exists($filePath)) {
            include $filePath; // uses $rules, $pager, $search, $filterStatus
        } else {
            echo "View file not found.";
        }
    }

    // Nominee Display List
    public function listRulesNominee()
    {
        $this->requireRole('NOMINEE');

        $page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search       = trim($_GET['q'] ?? '');
        $filterStatus = strtoupper(trim($_GET['status'] ?? ''));

        $pager = $this->ruleModel->getPagedRules($page, 10, $search, $filterStatus);
        $rules = $pager->result ?? [];

        // Reuse same view as student
        $filePath = $this->fileHelper->getFilePath('RuleListStudent');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }


    // ----------------------------------------- Create Rules ----------------------------------------- //
    public function createRule()
    {
        $this->requireRole('ADMIN');

        // only eligible events
        $electionEvents = $this->electionEventModel->getEligibleElectionEvents(['Pending','Ongoing']);

        $filePath = $this->fileHelper->getFilePath('CreateRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // Store New Rule + Validation
    public function storeRule() 
    {
        $this->requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->createRule();
            return;
        }

        $ruleCreationData = [
            'ruleTitle'  => trim($_POST['ruleTitle'] ?? ''),
            'content'    => trim($_POST['content'] ?? ''),
            'electionID' => $_POST['electionID'] ?? null,
        ];

        $errors = [];
        $fieldErrors = [];

        // Rule Title Validation
        if ($ruleCreationData['ruleTitle'] === '') {
            $errors[] = "Rule Title is required.";
            $fieldErrors['ruleTitle'][] = "Please provide a Rule Title.";
        } elseif (mb_strlen($ruleCreationData['ruleTitle']) < 5) {
            $errors[] = "Rule Title must be at least 5 characters long.";
            $fieldErrors['ruleTitle'][] = "At least 5 characters.";
        }

        // Rule Content Validation
        if ($ruleCreationData['content'] === '') {
            $errors[] = "Rule Content is required.";
            $fieldErrors['content'][] = "Please add Rule Content.";
        }

        // Election Event Validation (eligible only)
        if (empty($ruleCreationData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } else {
            $eligible = $this->electionEventModel->getElectionEventByIdIfEligible(
                $ruleCreationData['electionID'],
                ['Pending','Ongoing']
            );
            if (!$eligible) {
                $errors[] = "Selected Election Event is not allowed (must be Pending or Ongoing).";
                $fieldErrors['electionID'][] = "Please select a valid pending/ongoing event.";
            }
        }

        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getEligibleElectionEvents(['Pending','Ongoing']);
            $filePath = $this->fileHelper->getFilePath('CreateRule');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        // Store
        $this->ruleModel->createRule($ruleCreationData);
        \set_flash('success', 'Rule created successfully.');
        header('Location: /admin/rule'); 
        exit;
    }

    // ----------------------------------------- Edit Rules ----------------------------------------- //
    public function editRule($ruleID) {
        $this->requireRole('ADMIN');

        $ruleData = $this->ruleModel->getRuleById($ruleID);

        if (!$ruleData) {
            \set_flash('fail','Rule not found.');
            header('Location: /admin/rule'); 
            exit;
        }

        if (strtolower($ruleData['event_status'] ?? '') === 'completed' || strtolower($ruleData['event_status'] ?? '') === 'ongoing') {
            set_flash('fail','This rule belongs to an ongoing and completed event and cannot be edited.');
            header('Location: /admin/rule'); 
            exit;
        }
        
        $electionEvents = $this->electionEventModel->getEligibleElectionEvents(['Pending','Ongoing']);

        $filePath = $this->fileHelper->getFilePath('EditRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    public function editStoreRule($ruleID) 
    {
        $this->requireRole('ADMIN');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->editRule($ruleID);
            return;
        }

        $existing = $this->ruleModel->getRuleById($ruleID);
        if (!$existing) { 
            set_flash('fail','Rule not found.'); 
            header('Location: /admin/rule'); 
            exit; 
        }
        if (strtolower($existing['event_status'] ?? '') === 'completed') {
            set_flash('fail','This rule belongs to a completed event and cannot be edited.');
            header('Location: /admin/rule'); 
            exit;
        }

        $ruleData = [
            'ruleTitle'  => trim($_POST['ruleTitle'] ?? ''),
            'content'    => trim($_POST['content'] ?? ''),
            'electionID' => $_POST['electionID'] ?? null,
        ];

        $errors = [];
        $fieldErrors = [];

        if ($ruleData['ruleTitle'] === '') {
            $errors[] = "Rule Title is required.";
            $fieldErrors['ruleTitle'][] = "Please provide a Rule Title.";
        } elseif (mb_strlen($ruleData['ruleTitle']) < 5) {
            $errors[] = "Rule Title must be at least 5 characters long.";
            $fieldErrors['ruleTitle'][] = "At least 5 characters.";
        }

        if ($ruleData['content'] === '') {
            $errors[] = "Rule Content is required.";
            $fieldErrors['content'][] = "Please add Rule Content.";
        }

        if (empty($ruleData['electionID'])) {
            $errors[] = "Associated Election Event is required.";
            $fieldErrors['electionID'][] = "Please select an Election Event.";
        } else {
            $eligible = $this->electionEventModel->getElectionEventByIdIfEligible(
                $ruleData['electionID'],
                ['Pending','Ongoing']
            );
            if (!$eligible) {
                $errors[] = "Selected Election Event is not allowed (must be Pending or Ongoing).";
                $fieldErrors['electionID'][] = "Please select a valid pending/ongoing event.";
            }
        }

        if (!empty($errors)) {
            $electionEvents = $this->electionEventModel->getEligibleElectionEvents(['Pending','Ongoing']);
            $filePath = $this->fileHelper->getFilePath('EditRule');
            if ($filePath && file_exists($filePath)) {
                include $filePath;
            } else {
                echo "View file not found.";
            }
            return;
        }

        $this->ruleModel->updateRule($ruleID, $ruleData);
        \set_flash('success', 'Rule updated successfully.');
        header('Location: /admin/rule'); exit;
    }


    // ----------------------------------------- View Rule Details ----------------------------------------- //
    public function viewRule($ruleID) 
    {
        $this->requireRole('ADMIN');

        $ruleData = $this->ruleModel->getRuleById($ruleID);
        if (!$ruleData) {
            \set_flash('fail','Rule not found.');
            $this->redirectByRole(strtoupper($_SESSION['role'] ?? ''));
        }
        $filePath = $this->fileHelper->getFilePath('ViewRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    public function viewRuleStudent($ruleID) 
    {
        $this->requireRole('STUDENT');

        $ruleData = $this->ruleModel->getRuleById($ruleID);
        if (!$ruleData) {
            \set_flash('fail','Rule not found.');
            $this->redirectByRole(strtoupper($_SESSION['role'] ?? ''));
        }
        $filePath = $this->fileHelper->getFilePath('ViewRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    public function viewRuleNominee($ruleID) 
    {
        $this->requireRole('NOMINEE');

        $ruleData = $this->ruleModel->getRuleById($ruleID);
        if (!$ruleData) {
            \set_flash('fail','Rule not found.');
            $this->redirectByRole(strtoupper($_SESSION['role'] ?? ''));
        }
        $filePath = $this->fileHelper->getFilePath('ViewRule');
        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }

    // ----------------------------------------- Delete Rule ----------------------------------------- //
    public function deleteRule($ruleID) 
    {
        if (empty($_SESSION['role']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
            set_flash('fail', 'You do not have permission to access!');
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /election-event');
            return;
        }

        $ruleData = $this->ruleModel->getRuleById($ruleID);
        if (!$ruleData) { 
            set_flash('fail','Rule not found.'); 
            header('Location: /admin/rule'); 
            exit; 
        }
        if (strtolower($ruleData['event_status'] ?? '') === 'completed' || strtolower($ruleData['event_status'] ?? '') === 'ongoing') {
            set_flash('fail','This rule belongs to an ongoing and completed event and cannot be deleted.');
            header('Location: /admin/rule'); 
            exit;
        }

        $this->ruleModel->deleteRule($ruleID);
        \set_flash('success', 'Rule deleted successfully.');
        header('Location: /admin/rule');
    }

}