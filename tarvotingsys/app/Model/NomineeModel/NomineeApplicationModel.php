<?php

namespace Model\NomineeModel;

use Model\VotingModel\ElectionEventModel;
use PDO;
use PDOException;
use Database;
use Library\SimplePager;


class NomineeApplicationModel
{
    /** @var PDO */
    private PDO $db;
    private ElectionEventModel $electionEventModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->electionEventModel = new ElectionEventModel();
    }

    /**
     * List all nominee applications with student name and event title.
     * @return array|false
     */
    public function getAllNomineeApplications() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    na.*,
                    ac.fullName,
                    e.title AS event_name,
                    (rf.registerEndDate IS NULL OR rf.registerEndDate > NOW()) AS reg_is_open,
                    EXISTS (
                        SELECT 1 FROM nomineeapplication na2
                        WHERE na2.electionID = na.electionID
                        AND na2.applicationStatus = 'PUBLISHED'
                        LIMIT 1
                    ) AS event_has_published
                FROM nomineeapplication na
                LEFT JOIN electionevent e  ON na.electionID = e.electionID
                LEFT JOIN registrationform rf ON na.registrationFormID = rf.registrationFormID
                LEFT JOIN student s        ON na.studentID = s.studentID 
                LEFT JOIN account ac       ON s.accountID = ac.accountID 
                ORDER BY na.nomineeApplicationID ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { 
            error_log("Error in getAllNomineeApplications: " . $e->getMessage());
            return false; 
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

    // -------------------------------------- Publish Nominee Applications -------------------------------------- //
    /** Dropdown list of election events */
    public function listElectionEvents(): array
    {
        try {
            $st = $this->db->query("
                SELECT electionID, title
                FROM electionevent
                ORDER BY electionID DESC
            ");
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('listElectionEvents error: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Get all ACCEPTED applications for an election (for preview/publish).
     * Returns: fullName, studentID, loginID, program, intakeYear, accountID
     */
    public function getAcceptedApplicationsByElection(int $electionID): array
    {
        try {
            $sql = "
                SELECT 
                    ac.fullName,
                    s.studentID,
                    ac.loginID,
                    s.program,
                    s.intakeYear,
                    ac.accountID
                FROM nomineeapplication na
                JOIN student s   ON na.studentID = s.studentID
                JOIN account ac  ON s.accountID = ac.accountID
                WHERE na.electionID = ?
                AND na.applicationStatus = 'ACCEPTED'
                ORDER BY s.studentID ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute([$electionID]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getAcceptedApplicationsByElection error: '.$e->getMessage());
            return [];
        }
    }

    // Count PENDING applications for an election
    public function countPendingByElection(int $electionID): int
    {
        try {
            $st = $this->db->prepare("
                SELECT COUNT(*) 
                FROM nomineeapplication 
                WHERE electionID = ? AND applicationStatus = 'PENDING'
            ");
            $st->execute([$electionID]);
            return (int)$st->fetchColumn();
        } catch (PDOException $e) {
            error_log('countPendingByElection error: ' . $e->getMessage());
            return 0;
        }
    }


    /**
     * Transactional publish for an election:
     * 1) Close registration: registrationform.registerEndDate = NOW() (for forms under this election)
     * 2) Flip ACCEPTED -> PUBLISHED in nomineeapplication for this election
     * 3) Update account.role to 'NOMINEE' for those students
     * 4) Insert into nominee(accountID) for those accounts (skip duplicates)
     */
    public function publishNomineeApplications(int $electionID): bool
    {
        // Prevent Publishing if there is PENDING applications
        if ($this->countPendingByElection($electionID) > 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1) Close registration
            $sqlClose = " UPDATE registrationform SET registerEndDate = NOW() WHERE electionID = ? AND (registerEndDate IS NULL OR registerEndDate > NOW())";
            $stClose = $this->db->prepare($sqlClose);
            $stClose->execute([$electionID]);

            // 2) Promote ACCEPTED -> PUBLISHED
            $sqlPub = "
                UPDATE nomineeapplication
                SET applicationStatus = 'PUBLISHED'
                WHERE electionID = ?
                AND applicationStatus = 'ACCEPTED'
            ";
            $stPub = $this->db->prepare($sqlPub);
            $stPub->execute([$electionID]);

            // 3) Update account role for those now PUBLISHED
            $sqlRole = "
                UPDATE account a
                JOIN student s ON s.accountID = a.accountID
                JOIN nomineeapplication na ON na.studentID = s.studentID
                SET a.role = 'NOMINEE'
                WHERE na.electionID = ?
                AND na.applicationStatus = 'PUBLISHED'
            ";
            $stRole = $this->db->prepare($sqlRole);
            $stRole->execute([$electionID]);

            // 4) Insert nominee rows (avoid duplicates)
            $sqlNominee = "
                INSERT INTO nominee (accountID, electionID)
                SELECT DISTINCT a.accountID, na.electionID
                FROM account a
                JOIN student s ON s.accountID = a.accountID
                JOIN nomineeapplication na ON na.studentID = s.studentID
                WHERE na.electionID = ?
                AND na.applicationStatus = 'PUBLISHED'
                AND NOT EXISTS (
                    SELECT 1
                    FROM nominee n
                    WHERE n.accountID = a.accountID
                        AND n.electionID = na.electionID
                )
            ";
            $stNominee = $this->db->prepare($sqlNominee);
            $stNominee->execute([$electionID]);


            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('publishNomineeApplications error: ' . $e->getMessage());
            return false;
        }
    }

    // Get all PUBLISHED applications for an election (for viewing after publish).
    public function getPublishedApplicationsByElection(int $electionID): array
    {
        try {
            $sql = "
                SELECT 
                    ac.fullName,
                    s.studentID,
                    ac.loginID,
                    s.program,
                    s.intakeYear,
                    ac.accountID
                FROM nomineeapplication na
                JOIN student s   ON na.studentID = s.studentID
                JOIN account ac  ON s.accountID = ac.accountID
                WHERE na.electionID = ?
                AND na.applicationStatus = 'PUBLISHED'
                ORDER BY s.studentID ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute([$electionID]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log('getPublishedApplicationsByElection error: '.$e->getMessage());
            return [];
        }
    }

    // List Unpublished Registration Form Election Events
    public function listUnpublishedElectionEvents()
    {
        try {
            $sql = "
                SELECT e.electionID, e.title
                FROM electionevent e
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM nomineeapplication na
                    WHERE na.electionID = e.electionID
                    AND na.applicationStatus = 'PUBLISHED'
                )
                ORDER BY e.electionID DESC
            ";
            $st = $this->db->query($sql);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('listUnpublishedElectionEvents error: '.$e->getMessage());
            return [];
        }
    }

    /** Return student's applications keyed by registrationFormID */
    public function getApplicationsByStudentIndexed(int $studentID): array
    {
        $sql = "
            SELECT nomineeApplicationID, registrationFormID, applicationStatus
            FROM nomineeapplication
            WHERE studentID = ?
            ORDER BY nomineeApplicationID DESC
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$studentID]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['registrationFormID']] = [
                'nomineeApplicationID' => (int)$r['nomineeApplicationID'],
                'applicationStatus'    => (string)$r['applicationStatus'],
            ];
        }
        return $out;
    }

    /** Return a nominee's applications (keyed by registrationFormID) using nomineeID */
    public function getApplicationsByAccountIndexed(int $accountID): array
    {
        $sql = "
            SELECT na.nomineeApplicationID, na.registrationFormID, na.applicationStatus
            FROM nomineeapplication na
            JOIN student s
            ON s.studentID = na.studentID
            WHERE s.accountID = ?
            ORDER BY na.nomineeApplicationID DESC
        ";

        $st = $this->db->prepare($sql);
        $st->execute([$accountID]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $formId = (int)$r['registrationFormID'];
            // first row is the latest (because of DESC), so only set if not already set
            if (!isset($out[$formId])) {
                $out[$formId] = [
                    'nomineeApplicationID' => (int)$r['nomineeApplicationID'],
                    'applicationStatus'    => (string)$r['applicationStatus'],
                ];
            }
        }
        return $out;
    }

    // ------------------------------------------------------------------------------------------------ //
    // Paging Settings
    public function getPagedNomineeApplications(int $page, int $limit, string $search = '', string $filterStatus = ''): SimplePager 
    {
        $this->electionEventModel->autoRollElectionStatuses();
        $sql    = "
            SELECT 
                na.*,
                ac.fullName,
                e.title AS event_name,
                (rf.registerEndDate IS NULL OR rf.registerEndDate > NOW()) AS reg_is_open,
                EXISTS (
                    SELECT 1 
                    FROM nomineeapplication na2
                    WHERE na2.electionID = na.electionID
                    AND na2.applicationStatus = 'PUBLISHED'
                    LIMIT 1
                ) AS event_has_published
            FROM nomineeapplication na
            LEFT JOIN electionevent   e  ON na.electionID        = e.electionID
            LEFT JOIN registrationform rf ON na.registrationFormID = rf.registrationFormID
            LEFT JOIN student         s  ON na.studentID         = s.studentID 
            LEFT JOIN account         ac ON s.accountID          = ac.accountID
            WHERE 1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND ac.fullName LIKE :q";
            $params[':q'] = '%' . $search . '%';
        }

        if ($filterStatus !== '' && in_array($filterStatus, ['PENDING','ACCEPTED','REJECTED','PUBLISHED'], true)) {
            $sql .= " AND na.applicationStatus = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY na.nomineeApplicationID DESC";

        // SimplePager does the LIMIT/OFFSET and total counting.
        return new SimplePager($this->db, $sql, $params, $limit, $page);
    }




}
