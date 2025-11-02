<?php

namespace Model\NomineeModel;

use PDO;
use PDOException;
use Database;

/**
 * Handles nominee application header + submission creation and queries.
 */
class NomineeApplicationModel
{
    /** @var PDO */
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * List all nominee applications with student name and event title.
     * @return array|false
     */
    public function getAllNomineeApplications() {
        try { 
            $stmt = $this->db->prepare(" 
                SELECT na.*, ac.fullName, e.title AS event_name 
                FROM nomineeapplication na 
                LEFT JOIN electionevent e ON na.electionID = e.electionID 
                LEFT JOIN student s ON na.studentID = s.studentID 
                LEFT JOIN account ac ON s.accountID = ac.accountID 
                ORDER BY na.nomineeApplicationID ASC 
            "); 
            $stmt->execute(); 
            return $stmt->fetchAll(PDO::FETCH_ASSOC); 
        } catch (PDOException $e) { 
            error_log("Error in getAllNomineeApplications: " . $e->getMessage()); return false; 
        } 
}

    /**
     * Create a nominee application (header) and its submission row (dynamic fields).
     * Returns both IDs:
     * [
     *   'nomineeApplicationID'    => int,
     *   'applicationSubmissionID' => int
     * ]
     */
    public function createNomineeApplication(
        int $studentID,
        int $registrationFormID,
        int $electionID,
        array $fieldValues
    ): array {
        $this->db->beginTransaction();

        try {
            // 1) Insert header into nomineeapplication
            $insertHeader = "
                INSERT INTO nomineeapplication
                    (submittedDate, applicationStatus, registrationFormID, adminID, studentID, electionID)
                VALUES
                    (CURDATE(), 'PENDING', ?, NULL, ?, ?)
            ";
            $st1 = $this->db->prepare($insertHeader);
            $st1->execute([$registrationFormID, $studentID, $electionID]);
            $appId = (int) $this->db->lastInsertId();

            // 2) Insert submission (dynamic columns) into nomineeapplicationsubmission
            $cols = ['nomineeApplicationID'];
            $vals = [$appId];
            $qs   = ['?'];

            foreach ($fieldValues as $col => $val) {
                $cols[] = $col;
                $vals[] = $val;
                $qs[]   = '?';
            }

            $insertSubmission = "
                INSERT INTO nomineeapplicationsubmission (".implode(',', $cols).") 
                VALUES (".implode(',', $qs).")";
            $st2 = $this->db->prepare($insertSubmission);
            $st2->execute($vals);

            $submissionId = (int) $this->db->lastInsertId();

            $this->db->commit();

            return [
                'nomineeApplicationID'    => $appId,
                'applicationSubmissionID' => $submissionId,
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('NomineeApplication create failed: ' . $e->getMessage());
            return ['nomineeApplicationID' => 0, 'applicationSubmissionID' => 0];
        }
    }

    /**
     * True if the student already has an application for the given event.
     */
    public function hasAppliedForEvent(int $studentID, int $electionID): bool
    {
        $sql = "
            SELECT 1
            FROM nomineeapplication
            WHERE studentID = ? AND electionID = ?
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$studentID, $electionID]);
        return (bool) $stmt->fetchColumn();
    }


    // --------- Edit Nominee Application --------- //
    public function getNomineeApplicationById(int $nomineeApplicationID): ?array
    {
        $sql = "
            SELECT 
                na.*,
                rf.registrationFormTitle,
                e.title AS event_name,
                ac.fullName AS student_fullname,
                s.studentID AS studentID,
                sub.applicationSubmissionID
            FROM nomineeapplication na
            LEFT JOIN registrationform rf ON na.registrationFormID = rf.registrationFormID
            LEFT JOIN electionevent e ON na.electionID = e.electionID
            LEFT JOIN student s ON na.studentID = s.studentID
            LEFT JOIN account ac ON s.accountID = ac.accountID
            LEFT JOIN nomineeapplicationsubmission sub ON sub.nomineeApplicationID = na.nomineeApplicationID
            WHERE na.nomineeApplicationID = ?
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$nomineeApplicationID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Get the submission row by applicationSubmissionID */
    public function getSubmissionById(int $applicationSubmissionID): ?array
    {
        $sql = "SELECT * FROM nomineeapplicationsubmission WHERE applicationSubmissionID = ? LIMIT 1";
        $st  = $this->db->prepare($sql);
        $st->execute([$applicationSubmissionID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Get the submission row by nomineeApplicationID */
    public function getSubmissionByAppId(int $nomineeApplicationID): ?array
    {
        $sql = "SELECT * FROM nomineeapplicationsubmission WHERE nomineeApplicationID = ? LIMIT 1";
        $st  = $this->db->prepare($sql);
        $st->execute([$nomineeApplicationID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update dynamic columns in nomineeapplicationsubmission.
     * $fieldValues keys must be column names (your code uses lowercase; MySQL is case-insensitive).
     */
    public function updateSubmission(int $applicationSubmissionID, array $fieldValues): bool
    {
        if (empty($fieldValues)) return true;

        $sets = [];
        $vals = [];
        foreach ($fieldValues as $col => $val) {
            $sets[] = "$col = ?";
            $vals[] = $val;
        }
        $vals[] = $applicationSubmissionID;

        $sql = "UPDATE nomineeapplicationsubmission SET ".implode(', ', $sets)." WHERE applicationSubmissionID = ?";
        $st  = $this->db->prepare($sql);
        return $st->execute($vals);
    }

    // ---------------------------------- View Nominee Applications ---------------------------------- //
    // Get admin full name by adminID
    public function getAdminFullnameByAdminId(?int $adminID): ?string
    {
        if (!$adminID) return null;

        $sql = "
            SELECT ac.fullName
            FROM administrator a
            JOIN account ac ON a.accountID = ac.accountID
            WHERE a.adminID = ?
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$adminID]);
        $name = $st->fetchColumn();
        return $name !== false ? (string)$name : null;
    }

    // -------------------------------------- Accept / Reject Nominee Application -------------------------------------- //
    public function acceptNomineeApplication($nomineeApplicationID, $adminID)
    {
        try {
            $stmt = $this->db->prepare("UPDATE nomineeapplication SET applicationStatus = 'ACCEPTED', adminID = ? WHERE nomineeApplicationID = ?");
            return $stmt->execute([$adminID, $nomineeApplicationID]);
        } catch (PDOException $e) {
            error_log("Error in acceptNomineeApplication: " . $e->getMessage());
            return false;
        }
    }

    public function rejectNomineeApplication($nomineeApplicationID, $adminID)
    {
        try {
            $stmt = $this->db->prepare("UPDATE nomineeapplication SET applicationStatus = 'REJECTED', adminID = ? WHERE nomineeApplicationID = ?");
            return $stmt->execute([$adminID, $nomineeApplicationID]);
        } catch (PDOException $e) {
            error_log("Error in rejectNomineeApplication: " . $e->getMessage());
            return false;
        }
    }

}
