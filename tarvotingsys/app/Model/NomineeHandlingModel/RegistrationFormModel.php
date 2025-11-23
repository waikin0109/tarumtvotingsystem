<?php

namespace Model\NomineeHandlingModel;

use Model\VotingModel\ElectionEventModel;
use Model\NomineeModel\NomineeModel;
use PDO;
use PDOException;
use Database;
use Library\SimplePager;

class RegistrationFormModel
{
    private $db;
    private ElectionEventModel $electionEventModel;
    private NomineeModel $nomineeModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->electionEventModel = new ElectionEventModel();
        $this->nomineeModel = new NomineeModel();

    }

    public function getAllRegistrationForms()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    rf.registrationFormID,
                    rf.registrationFormTitle,
                    rf.registerStartDate,
                    rf.registerEndDate,
                    rf.electionID,
                    e.title AS event_name,
                    e.status,
                    e.electionStartDate, 
                    e.electionEndDate,
                    acc.fullName AS admin_name,
                    rf.adminID
                FROM registrationform rf
                LEFT JOIN electionevent  e   ON rf.electionID = e.electionID
                LEFT JOIN administrator  a   ON a.adminID     = rf.adminID
                LEFT JOIN account        acc ON acc.accountID = a.accountID
                ORDER BY rf.registerEndDate DESC
            ");
            $stmt->execute();
            $registrationForms = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$registrationForms) {
                return false;
            }
            
            // Check Election Event Status + Nominee Role (After Completed)
            foreach ($registrationForms as &$registrationForm) {
                $currentStatus = $this->electionEventModel->determineStatus($registrationForm['electionStartDate'], $registrationForm['electionEndDate']);

                if ($currentStatus !== $registrationForm['status']) {
                    $this->electionEventModel->updateElectionStatus($currentStatus, $registrationForm['electionID']);
                    $registrationForm['status'] = $currentStatus;

                    // Update Nominee Role (when event just became COMPLETED) 
                    if ($currentStatus === 'COMPLETED') {
                        $this->nomineeModel->resetNomineeRolesToStudentByElection($registrationForm['electionID']);
                    }
                }
            }
            
            return $registrationForms;

        } catch (PDOException $e) {
            error_log("Error in getAllRegistrationForms: " . $e->getMessage());
            return false;
        }
    }


/** True if the form has started (start <= NOW()). Once started it is "locked". */
public function hasStarted(int $formId): bool {
    $st = $this->db->prepare("
        SELECT 1
        FROM registrationform
        WHERE registrationFormID = ?
          AND registerStartDate IS NOT NULL
          AND registerStartDate <= NOW()
        LIMIT 1
    ");
    $st->execute([$formId]);
    return (bool)$st->fetchColumn();
}

/** True if the form has ended (end < NOW()) — not strictly needed but handy. */
public function hasEnded(int $formId): bool {
    $st = $this->db->prepare("
        SELECT 1
        FROM registrationform
        WHERE registrationFormID = ?
          AND registerEndDate IS NOT NULL
          AND registerEndDate < NOW()
        LIMIT 1
    ");
    $st->execute([$formId]);
    return (bool)$st->fetchColumn();
}

/** Convenience: locked once started (includes 'open' and 'ended'). */
public function isLocked(int $formId): bool {
    return $this->hasStarted($formId);
}


   
    public function createRegistrationFormWithAttributes($data, $attributes)
    {
        // allow only known attribute keys
        $whitelist = ['cgpa','reason','achievements','behaviorReport'];
        $attributes = array_values(array_intersect($attributes, $whitelist));

        try {
            $this->db->beginTransaction();

            // Insert the form (columns per your schema)
            $stmt = $this->db->prepare("
                INSERT INTO registrationform
                (registrationFormTitle, registerStartDate, registerEndDate, dateCreated, electionID, adminID)
                VALUES
                (?, ?, ?, NOW(), ?, ?)
            ");
            $stmt->execute([
                $data['registrationFormTitle'],
                $data['registerStartDateTime'], // Y-m-d H:i:s
                $data['registerEndDateTime'],   // Y-m-d H:i:s
                $data['electionID'],
                $data['adminID']
            ]);

            $formId = (int)$this->db->lastInsertId();

            // Insert selected attributes
            if (!empty($attributes)) {
                $stmtAttr = $this->db->prepare("
                    INSERT INTO registrationformattribute (attributeName, registrationFormID)
                    VALUES (?, ?)
                ");
                foreach ($attributes as $attr) {
                    $stmtAttr->execute([$attr, $formId]);
                }
            }

            $this->db->commit();
            return $formId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in createRegistrationFormWithAttributes: ".$e->getMessage());
            return false;
        }
    }

    public function existsForElection($electionID)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM registrationform WHERE electionID = ? LIMIT 1");
        $stmt->execute([$electionID]);
        return (bool) $stmt->fetchColumn();
    }



    public function getAllRegistrationFormAttributes()
    {
        // Hardcode for now (or load from a table)
        return [
            ['key' => 'cgpa',            'label' => 'CGPA'],
            ['key' => 'reason',          'label' => 'Reason for Participation'],
            ['key' => 'achievements',    'label' => 'Achievements / Awards'],
            ['key' => 'behaviorReport',  'label' => 'Behavior Report'],
        ];
    }


    // -------------------------------- Get Registration Form Attributes ----------------------------------------- //
    // Get attributes for a certain ID of registration form
    public function getAttributesByFormId($formId) {
        $stmt = $this->db->prepare("SELECT attributeName FROM registrationformattribute WHERE registrationFormID = ?");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    // Fetch a registration form by ID
    public function getRegistrationFormById($formId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    rf.*,
                    e.title AS event_name,
                    e.status,
                    e.electionStartDate, 
                    e.electionEndDate,
                    acc.fullName AS admin_name
                FROM registrationform rf
                LEFT JOIN electionevent e   ON rf.electionID = e.electionID
                LEFT JOIN administrator a   ON a.adminID     = rf.adminID
                LEFT JOIN account acc       ON acc.accountID = a.accountID
                WHERE rf.registrationFormID = ?
                LIMIT 1
            ");
            $stmt->execute([$formId]);
            $registrationForm = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$registrationForm) {
                return false;
            }

            // Check Election Event Status + Nominee Role (After Completed)
            $currentStatus = $this->electionEventModel->determineStatus($registrationForm['electionStartDate'], $registrationForm['electionEndDate']);

            if ($currentStatus !== $registrationForm['status']) {
                $this->electionEventModel->updateElectionStatus($currentStatus, $registrationForm['electionID']);
                $registrationForm['status'] = $currentStatus;

                // Update Nominee Role (when event just became COMPLETED) 
                if ($currentStatus === 'COMPLETED') {
                    $this->nomineeModel->resetNomineeRolesToStudentByElection($registrationForm['electionID']);
                }
            }
            
            return $registrationForm;
        } catch (PDOException $e) {
            error_log('Error in getRegistrationFormById: '.$e->getMessage());
            return false;
        }

    }



    // Update form + replace its attributes
    public function updateRegistrationFormWithAttributes($data, $attributes)
    {
        $whitelist = ['cgpa','reason','achievements','behaviorReport'];
        $attributes = array_values(array_intersect($attributes, $whitelist));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE registrationform
                SET registrationFormTitle = ?,
                    registerStartDate = ?,
                    registerEndDate = ?,
                    electionID = ?
                WHERE registrationFormID = ?
            ");
            $stmt->execute([
                $data['registrationFormTitle'],
                $data['registerStartDateTime'],
                $data['registerEndDateTime'],
                $data['electionID'],
                $data['registrationFormID']
            ]);

            // Here we use delete not update is because user might remove some attributes e.g 4->3
            // Delete old attributes
            $stmtDel = $this->db->prepare("DELETE FROM registrationformattribute WHERE registrationFormID = ?");
            $stmtDel->execute([$data['registrationFormID']]);

            // Insert new attributes
            if (!empty($attributes)) {
                $stmtAttr = $this->db->prepare("
                    INSERT INTO registrationformattribute (attributeName, registrationFormID)
                    VALUES (?, ?)
                ");
                foreach ($attributes as $attr) {
                    $stmtAttr->execute([$attr, $data['registrationFormID']]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in updateRegistrationFormWithAttributes: ".$e->getMessage());
            return false;
        }
    }

    public function existsForOtherElection(int $electionID, int $excludeFormID): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM registrationform 
            WHERE electionID = ? 
            AND registrationFormID != ?
            LIMIT 1
        ");
        $stmt->execute([$electionID, $excludeFormID]);
        return (bool) $stmt->fetchColumn();
    }

    //-------------------------------- Delete Registration Form ----------------------------------------- //
    public function deleteRegistrationForm($formId)
    {
        try {
            $stmtAttr = $this->db->prepare("DELETE FROM registrationformattribute WHERE registrationFormID = :id");
            $stmt = $this->db->prepare("DELETE FROM registrationform WHERE registrationFormID = :id");
            $stmtAttr->bindParam(':id', $formId, PDO::PARAM_INT);
            $stmt->bindParam(':id', $formId, PDO::PARAM_INT);
            return $stmtAttr->execute() && $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteRegistrationForm: " . $e->getMessage());
            return false;
        }
    }

    //-----------------------------------------------------------------------------------------------//
    // Nominee Application Related Methods

    public function listForms(): array {
        $sql = "SELECT registrationFormID, registrationFormTitle, electionID
                FROM registrationform
                ORDER BY registrationFormID DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** lowercased column => sql type for nomineeapplicationsubmission */
    public function getSubmissionColumns(): array {
        $cols = [];
        $st = $this->db->query("DESCRIBE nomineeapplicationsubmission");
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cols[strtolower($c['Field'])] = strtolower($c['Type']);
        }
        return $cols;
    }

    /** Return only forms whose register window is currently open */
    public function listOpenForms(): array {
        $sql = "SELECT registrationFormID, registrationFormTitle, electionID, registerStartDate, registerEndDate
                FROM registrationform
                WHERE (registerStartDate IS NULL OR registerStartDate <= NOW())
                AND (registerEndDate   IS NULL OR registerEndDate   >= NOW())
                ORDER BY registrationFormID DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Small struct with start/end for a form (used for banner text) */
    public function getRegWindowByFormId(int $formId): ?array {
        $st = $this->db->prepare("SELECT registerStartDate, registerEndDate FROM registrationform WHERE registrationFormID = ? LIMIT 1");
        $st->execute([$formId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** True if the form’s register window is open right now */
    public function isRegistrationOpen(int $formId): bool {
        $st = $this->db->prepare("
            SELECT 1
            FROM registrationform
            WHERE registrationFormID = ?
            AND (registerStartDate IS NULL OR registerStartDate <= NOW())
            AND (registerEndDate   IS NULL OR registerEndDate   >= NOW())
            LIMIT 1");
        $st->execute([$formId]);
        return (bool)$st->fetchColumn();
    }


    // -----------------------------------------------------------------------------------------------------------------------------------------------//
    // Paging Settings
    public function getPagedRegistrationForms(int $page, int $limit, string $search = '', string $filterStatus = ''): SimplePager {
        $this->electionEventModel->autoRollElectionStatuses();
        $sql = "
            SELECT 
                rf.registrationFormID,
                rf.registrationFormTitle,
                rf.registerStartDate,
                rf.registerEndDate,
                rf.electionID,
                e.title  AS event_name,
                e.status AS election_status
            FROM registrationform rf
            LEFT JOIN electionevent e ON rf.electionID = e.electionID
            WHERE 1
        ";

        $params = [];

        // Search by form title
        if ($search !== '') {
            $sql .= " AND rf.registrationFormTitle LIKE :q";
            $params[':q'] = '%' . $search . '%';
        }

        $sql .= "
            ORDER BY 
                rf.registerEndDate DESC,
                rf.registrationFormID DESC
        ";

        // SimplePager will handle LIMIT/OFFSET and total counting
        return new SimplePager($this->db, $sql, $params, $limit, $page);
    }


}


