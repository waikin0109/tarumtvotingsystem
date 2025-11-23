<?php

namespace Model\CampaignHandlingModel;

use Model\VotingModel\ElectionEventModel;
use Model\NomineeModel\NomineeModel; 
use PDO;
use PDOException;
use Database;
use Library\SimplePager;

class ScheduleLocationModel
{
    private PDO $db;
    private ElectionEventModel $electionEventModel;
    private NomineeModel $nomineeModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->electionEventModel = new ElectionEventModel();
        $this->nomineeModel = new NomineeModel();
    }

    public function autoRollScheduleLocationStatuses()
    {
        try {
            
            $now = date('Y-m-d H:i:s');

            $sql = "
                SELECT eventApplicationID, desiredStartDateTime, eventApplicationStatus
                FROM eventapplication
                WHERE eventApplicationStatus = 'PENDING'
                AND desiredStartDateTime < :now
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':now' => $now]);

            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $scheduleLocation) {
                $this->autoUpdateEventApplicationStatus($scheduleLocation['eventApplicationID']);
            }
        } catch (\PDOException $e) {
            error_log('autoRollScheduleLocationStatuses: ' . $e->getMessage());
        }
    }


    /** List page */
    public function getAllScheduleLocations(): array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventApplicationStatus,
                    ea.desiredStartDateTime,
                    ee.title AS election_event,
                    ee.electionStartDate,
                    ee.electionEndDate,
                    ee.status,
                    nomAcc.fullName AS nominee_fullName,
                    admAcc.fullName AS admin_fullName,
                    ea.nomineeID,
                    ea.adminID,
                    ea.electionID,
                    ee.title AS event_name
                FROM eventapplication ea
                INNER JOIN electionevent ee ON ee.electionID = ea.electionID
                INNER JOIN nominee n        ON n.nomineeID   = ea.nomineeID
                INNER JOIN account nomAcc   ON nomAcc.accountID = n.accountID
                LEFT  JOIN administrator adm ON adm.adminID  = ea.adminID
                LEFT  JOIN account admAcc    ON admAcc.accountID = adm.accountID
                ORDER BY ea.eventApplicationID DESC
            ";
            $st = $this->db->prepare($sql);
            $st->execute();
            $scheduleLocations =  $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!$scheduleLocations) {
                return [];
            }

            $now = date('Y-m-d H:i:s');

            // Check Election Event Status + Nominee Role (After Completed)
            foreach ($scheduleLocations as &$scheduleLocation) {
                $currentStatus = $this->electionEventModel->determineStatus($scheduleLocation['electionStartDate'], $scheduleLocation['electionEndDate']);

                if ($currentStatus !== $scheduleLocation['status']) {
                    // You can wrap these two lines in a transaction if you want atomicity
                    $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                    $upd->execute([$currentStatus, $campaignMaterial['electionID']]);
                    $scheduleLocation['status'] = $currentStatus;

                    // Update Nominee Role (when event just became COMPLETED) 
                    if ($currentStatus === 'COMPLETED') {
                        $this->nomineeModel->resetNomineeRolesToStudentByElection($scheduleLocation['electionID']);
                    }
                }

                // Check ScheduleLocation Status
                if ($scheduleLocation['desiredStartDateTime'] < $now && $scheduleLocation['eventApplicationStatus'] == 'PENDING') {
                    $this->autoUpdateEventApplicationStatus($scheduleLocation['eventApplicationID']);
                }
            }

            return $scheduleLocations;
        } catch (PDOException $e) {
            error_log("getAllScheduleLocations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eligible elections (create form):
     *  - election not ended
     *  - registration ended
     *  - has at least one PUBLISHED nominee
     */
    public function getEligibleElections(): array
    {
        try {
            $sql = "
                SELECT ee.electionID, ee.title
                FROM electionevent ee
                WHERE ee.electionEndDate > NOW()
                  AND EXISTS (
                    SELECT 1
                    FROM registrationform rf
                    WHERE rf.electionID = ee.electionID
                    GROUP BY rf.electionID
                    HAVING MAX(rf.registerEndDate) < NOW()
                  )
                  AND EXISTS (
                    SELECT 1
                    FROM nomineeapplication na
                    WHERE na.electionID = ee.electionID
                      AND UPPER(na.applicationStatus) = 'PUBLISHED'
                  )
                ORDER BY ee.title ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getEligibleElections: " . $e->getMessage());
            return [];
        }
    }

    /** Nominees in the selected election */
    public function getNomineesByElection(int $electionID): array
    {
        try {
            $sql = "
                SELECT DISTINCT
                    n.nomineeID,
                    a.fullName,
                    s.studentID,
                    CONCAT(a.fullName, ' (', s.studentID, ')') AS display
                FROM nominee n
                INNER JOIN account a ON a.accountID = n.accountID
                INNER JOIN student s ON s.accountID = a.accountID
                INNER JOIN nomineeapplication na
                    ON na.studentID = s.studentID
                   AND n.electionID = :eid
                   AND UPPER(na.applicationStatus) = 'PUBLISHED'
                ORDER BY a.fullName ASC
            ";
            $st = $this->db->prepare($sql);
            $st->bindValue(':eid', $electionID, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            
        } catch (PDOException $e) {
            error_log("getNomineesByElection: " . $e->getMessage());
            return [];
        }
    }

    /** Registration window for validation */
    public function getRegistrationWindow(int $electionID): ?array
    {
        try {
            $st = $this->db->prepare("
                SELECT MIN(rf.registerStartDate) AS startAt,
                       MAX(rf.registerEndDate)   AS endAt
                FROM registrationform rf
                WHERE rf.electionID = :eid
            ");
            $st->execute([':eid' => $electionID]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return ($row && $row['endAt']) ? $row : null;
        } catch (PDOException $e) {
            error_log("getRegistrationWindow: " . $e->getMessage());
            return null;
        }
    }

    public function getAdminIdByAccount(int $accountID): ?int
    {
        try {
            $st = $this->db->prepare("SELECT adminID FROM administrator WHERE accountID = :aid");
            $st->execute([':aid' => $accountID]);
            $id = $st->fetchColumn();
            return $id ? (int)$id : null;
        } catch (PDOException $e) {
            error_log("getAdminIdByAccount: " . $e->getMessage());
            return null;
        }
    }

    /** Create Event Application as PENDING */
    public function createScheduleLocation(array $data): bool
    {
        try {
            $st = $this->db->prepare("
                INSERT INTO eventapplication
                    (eventName, eventType, desiredStartDateTime, desiredEndDateTime, eventApplicationStatus, nomineeID, electionID)
                VALUES
                    (:eventName, :eventType, :desiredStart, :desiredEnd, 'PENDING', :nomineeID, :electionID)
            ");
            return $st->execute([
                ':eventName'  => $data['eventName'],
                ':eventType'  => $data['eventType'],
                ':desiredStart' => $data['desiredStartDateTime'], // 'Y-m-d H:i:s'
                ':desiredEnd'   => $data['desiredEndDateTime'],   // 'Y-m-d H:i:s'
                ':nomineeID'  => $data['nomineeID'],
                ':electionID' => $data['electionID'],
            ]);
        } catch (PDOException $e) {
            error_log("createScheduleLocation: " . $e->getMessage());
            return false;
        }
    }


    public function getElectionEndDate(int $electionID): ?string
    {
        try {
            $st = $this->db->prepare("
                SELECT electionEndDate
                FROM electionevent
                WHERE electionID = :eid
                LIMIT 1
            ");
            $st->execute([':eid' => $electionID]);
            $val = $st->fetchColumn();
            return $val ?: null;
        } catch (PDOException $e) {
            error_log("getElectionEndDate: " . $e->getMessage());
            return null;
        }
    }

    /** Read one application with joins (for edit) */
    public function getScheduleLocationById(int $eventApplicationID): ?array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventType,
                    ea.eventApplicationStatus,
                    ea.desiredStartDateTime,
                    ea.desiredEndDateTime,
                    ea.electionID,
                    ea.nomineeID,
                    ee.title AS electionTitle,
                    ee.electionStartDate,
                    ee.electionEndDate,
                    ee.status,
                    a.fullName AS nomineeFullName,
                    s.studentID
                FROM eventapplication ea
                INNER JOIN electionevent ee ON ee.electionID = ea.electionID
                INNER JOIN nominee n        ON n.nomineeID   = ea.nomineeID
                INNER JOIN account a        ON a.accountID   = n.accountID
                INNER JOIN student s        ON s.accountID   = a.accountID
                WHERE ea.eventApplicationID = :id
                LIMIT 1
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':id' => $eventApplicationID]);
            $scheduleLocation = $st->fetch(PDO::FETCH_ASSOC);

            if (!$scheduleLocation) {
                return false;
            }

            $now = date('Y-m-d H:i:s');

            // Check Election Event Status + Nominee Role (After Completed)
            $currentStatus = $this->electionEventModel->determineStatus($scheduleLocation['electionStartDate'], $scheduleLocation['electionEndDate']);

            if ($currentStatus !== $scheduleLocation['status']) {
                $update = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                $update->execute([$currentStatus, $scheduleLocation['electionID']]);
                $scheduleLocation['status'] = $currentStatus;

                // Update Nominee Role (when event just became COMPLETED) 
                if ($currentStatus === 'COMPLETED') {
                    $this->nomineeModel->resetNomineeRolesToStudentByElection($scheduleLocation['electionID']);
                }
            }
            // Check ScheduleLocation Status
            if ($scheduleLocation['desiredStartDateTime'] < $now && $scheduleLocation['eventApplicationStatus'] == 'PENDING') {
                $this->autoUpdateEventApplicationStatus($scheduleLocation['eventApplicationID']);
            }
            

            return $scheduleLocation;
        } catch (PDOException $e) {
            error_log("getScheduleLocationById: " . $e->getMessage());
            return null;
        }
    }

    /** Update editable fields only (edit page) */
    public function updateScheduleLocation(int $eventApplicationID, array $data): bool
    {
        try {
            $st = $this->db->prepare("
                UPDATE eventapplication
                SET eventName = :eventName,
                    eventType = :eventType,
                    desiredStartDateTime = :desiredStart,
                    desiredEndDateTime   = :desiredEnd
                WHERE eventApplicationID = :id
            ");
            return $st->execute([
                ':eventName'   => $data['eventName'],
                ':eventType'   => $data['eventType'],
                ':desiredStart'=> $data['desiredStartDateTime'],
                ':desiredEnd'  => $data['desiredEndDateTime'],
                ':id'          => $eventApplicationID,
            ]);
        } catch (PDOException $e) {
            error_log("updateScheduleLocation: " . $e->getMessage());
            return false;
        }
    }

    /** View details */
    public function getScheduleLocationDetailsById(int $eventApplicationID): ?array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventType,
                    ea.eventApplicationStatus,
                    ea.desiredStartDateTime,
                    ea.desiredEndDateTime,
                    ea.eventApplicationSubmittedAt,
                    ea.eventApplicationStatus,
                    ea.electionID,
                    ee.title AS electionTitle,
                    ee.electionStartDate,
                    ee.electionEndDate,
                    ee.status,
                    ea.nomineeID,
                    nomAcc.fullName AS nomineeFullName,
                    ea.adminID,
                    admAcc.fullName AS adminFullName
                FROM eventapplication ea
                INNER JOIN electionevent ee ON ee.electionID = ea.electionID
                INNER JOIN nominee n        ON n.nomineeID   = ea.nomineeID
                INNER JOIN account nomAcc   ON nomAcc.accountID = n.accountID
                LEFT  JOIN administrator adm ON adm.adminID  = ea.adminID
                LEFT  JOIN account admAcc    ON admAcc.accountID = adm.accountID
                WHERE ea.eventApplicationID = :id
                LIMIT 1
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':id' => $eventApplicationID]);
            $scheduleLocation = $st->fetch(PDO::FETCH_ASSOC);

            if (!$scheduleLocation) {
                return false;
            }

            $now = date('Y-m-d H:i:s');

            // Check Election Event Status + Nominee Role (After Completed)
            $currentStatus = $this->electionEventModel->determineStatus($scheduleLocation['electionStartDate'], $scheduleLocation['electionEndDate']);

            if ($currentStatus !== $scheduleLocation['status']) {
                $update = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                $update->execute([$currentStatus, $scheduleLocation['electionID']]);
                $scheduleLocation['status'] = $currentStatus;

                // Update Nominee Role (when event just became COMPLETED) 
                if ($currentStatus === 'COMPLETED') {
                    $this->nomineeModel->resetNomineeRolesToStudentByElection($scheduleLocation['electionID']);
                }
            }
            // Check ScheduleLocation Status
            if ($scheduleLocation['desiredStartDateTime'] < $now && $scheduleLocation['eventApplicationStatus'] == 'PENDING') {
                $this->autoUpdateEventApplicationStatus($scheduleLocation['eventApplicationID']);
            }
            

            return $scheduleLocation;
        } catch (PDOException $e) {
            error_log("getScheduleLocationDetailsById: " . $e->getMessage());
            return null;
        }
    }


    /** Pending queue for scheduling (oldest submitted first, election not ended) */
    public function getPendingEventApplications(): array
    {
        try {
            $sql = "
                SELECT 
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventType,
                    ea.eventApplicationStatus,
                    ea.desiredStartDateTime,
                    ea.desiredEndDateTime,
                    ea.eventApplicationSubmittedAt,
                    ee.title AS electionTitle,
                    ee.electionStartDate,
                    ee.electionEndDate,
                    ee.status,
                    acc.fullName AS nomineeName
                FROM eventapplication ea
                INNER JOIN electionevent ee ON ee.electionID = ea.electionID
                INNER JOIN nominee nom      ON nom.nomineeID = ea.nomineeID
                INNER JOIN account acc      ON acc.accountID = nom.accountID
                WHERE ea.eventApplicationStatus = 'PENDING'
                AND ee.electionEndDate > NOW()
                ORDER BY ea.eventApplicationSubmittedAt ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute();
            $scheduleLocations =  $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!$scheduleLocations) {
                return [];
            }

            $now = date('Y-m-d H:i:s');

            // Check Election Event Status + Nominee Role (After Completed)
            foreach ($scheduleLocations as &$scheduleLocation) {
                $currentStatus = $this->electionEventModel->determineStatus($scheduleLocation['electionStartDate'], $scheduleLocation['electionEndDate']);

                if ($currentStatus !== $scheduleLocation['status']) {
                    // You can wrap these two lines in a transaction if you want atomicity
                    $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                    $upd->execute([$currentStatus, $campaignMaterial['electionID']]);
                    $scheduleLocation['status'] = $currentStatus;

                    // Update Nominee Role (when event just became COMPLETED) 
                    if ($currentStatus === 'COMPLETED') {
                        $this->nomineeModel->resetNomineeRolesToStudentByElection($scheduleLocation['electionID']);
                    }
                }

                // Check ScheduleLocation Status
                if ($scheduleLocation['desiredStartDateTime'] < $now && $scheduleLocation['eventApplicationStatus'] == 'PENDING') {
                    $this->autoUpdateEventApplicationStatus($scheduleLocation['eventApplicationID']);
                }
            }

            return $scheduleLocations;
        } catch (PDOException $e) {
            error_log("getPendingEventApplications: " . $e->getMessage());
            return [];
        }
    }

/** All (or only AVAILABLE) event locations */
public function getAllEventLocations(bool $onlyAvailable = true): array
{
    try {
        $sql = "
            SELECT eventLocationID, eventLocationName, eventLocationStatus
            FROM eventlocation
            " . ($onlyAvailable ? "WHERE eventLocationStatus = 'AVAILABLE'" : "") . "
            ORDER BY eventLocationName ASC
        ";
        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("getAllEventLocations: " . $e->getMessage());
        return [];
    }
}

/** Range conflict at a location: overlap if (newStart < existingEnd) AND (newEnd > existingStart) */
public function hasLocationConflict(string $start, string $end, int $eventLocationID): bool
{
    try {
        $st = $this->db->prepare("
            SELECT COUNT(*)
            FROM event
            WHERE eventLocationID = :loc
              AND (:start < eventEndDateTime)
              AND (:end   > eventStartDateTime)
        ");
        $st->execute([':loc' => $eventLocationID, ':start' => $start, ':end' => $end]);
        return ((int)$st->fetchColumn()) > 0;
    } catch (PDOException $e) {
        error_log("hasLocationConflict: " . $e->getMessage());
        return true; // be safe
    }
}

/** ACCEPT: insert into event, mark application accepted, set admin, propagate to nomineeapplication */
public function acceptApplicationWithLocation(int $eventApplicationID, int $eventLocationID, int $adminId): bool
{
    try {
        $this->db->beginTransaction();

        // Lock + fetch basics (ensure PENDING)
        $st = $this->db->prepare("
            SELECT desiredStartDateTime, desiredEndDateTime, electionID, nomineeID, eventApplicationStatus
            FROM eventapplication
            WHERE eventApplicationID = :id
            FOR UPDATE
        ");
        $st->execute([':id' => $eventApplicationID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $this->db->rollBack(); return false; }

        if (strtoupper((string)$row['eventApplicationStatus']) !== 'PENDING') {
            // only PENDING can be accepted/scheduled
            $this->db->rollBack(); 
            return false;
        }

        // Prevent double scheduling
        if ($this->hasEventRow($eventApplicationID)) {
            $this->db->rollBack();
            return false;
        }

        $start     = $row['desiredStartDateTime'];
        $end       = $row['desiredEndDateTime'];
        $electionId= (int)$row['electionID'];
        $nomineeId = (int)$row['nomineeID'];

        // Conflict check at the chosen location
        if ($this->hasLocationConflict($start, $end, $eventLocationID)) {
            $this->db->rollBack();
            return false;
        }

        // Insert scheduled event
        $ins = $this->db->prepare("
            INSERT INTO event (eventStartDateTime, eventEndDateTime, eventApplicationID, eventLocationID)
            VALUES (:start, :end, :eaid, :loc)
        ");
        $ok1 = $ins->execute([
            ':start' => $start,
            ':end'   => $end,
            ':eaid'  => $eventApplicationID,
            ':loc'   => $eventLocationID,
        ]);

        // Accept + set admin (ensure still PENDING to avoid race)
        $upd = $this->db->prepare("
            UPDATE eventapplication
            SET eventApplicationStatus = 'ACCEPTED',
                adminID = :adm
            WHERE eventApplicationID = :id
              AND eventApplicationStatus = 'PENDING'
        ");
        $ok2 = $upd->execute([
            ':adm' => $adminId,
            ':id'  => $eventApplicationID,
        ]);

        // Propagate admin to nomineeapplication (same election + same candidate)
        $updNa = $this->db->prepare("
            UPDATE nomineeapplication na
            JOIN student s  ON s.studentID = na.studentID
            JOIN account a  ON a.accountID = s.accountID
            JOIN nominee n  ON n.accountID = a.accountID
            SET na.adminID = :adm
            WHERE na.electionID = :eid
              AND n.nomineeID   = :nid
        ");
        $ok3 = $updNa->execute([
            ':adm' => $adminId,
            ':eid' => $electionId,
            ':nid' => $nomineeId,
        ]);

        if ($ok1 && $ok2 && $upd->rowCount() === 1 && $ok3) {
            $this->db->commit();
            return true;
        }
        $this->db->rollBack();
        return false;
    } catch (PDOException $e) {
        error_log("acceptApplicationWithLocation: " . $e->getMessage());
        try { $this->db->rollBack(); } catch (\Throwable $t) {}
        return false;
    }
}


/** REJECT */
public function rejectApplication(int $eventApplicationID, int $adminId = 1): bool
{
    try {
        $st = $this->db->prepare("
            UPDATE eventapplication
            SET eventApplicationStatus = 'REJECTED',
                adminID = :adm
            WHERE eventApplicationID = :id
        ");
        return $st->execute([':adm' => $adminId, ':id' => $eventApplicationID]);
    } catch (PDOException $e) {
        error_log("rejectApplication: " . $e->getMessage());
        return false;
    }
}

/** Calendar feed: all scheduled events */
public function getCalendarEvents(?int $electionID = null): array
{
    try {
        $sql = "
            SELECT 
                e.eventID,
                e.eventStartDateTime,
                e.eventEndDateTime,
                el.eventLocationName,
                ea.eventApplicationID,
                ea.eventName,
                ea.eventType,
                ee.title AS electionTitle,
                ee.electionID
            FROM event e
            INNER JOIN eventapplication ea ON ea.eventApplicationID = e.eventApplicationID
            INNER JOIN electionevent ee     ON ee.electionID        = ea.electionID
            INNER JOIN eventlocation el     ON el.eventLocationID   = e.eventLocationID
        ";

        $params = [];
        if ($electionID !== null && $electionID > 0) {
            $sql .= " WHERE ee.electionID = :eid";
            $params[':eid'] = $electionID;
        }

        $sql .= " ORDER BY e.eventStartDateTime ASC, el.eventLocationName ASC";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("getCalendarEvents: " . $e->getMessage());
        return [];
    }
}


private function hasEventRow(int $eventApplicationID): bool
{
    $st = $this->db->prepare("SELECT COUNT(*) FROM event WHERE eventApplicationID = :id");
    $st->execute([':id' => $eventApplicationID]);
    return ((int)$st->fetchColumn()) > 0;
}

/** Unschedule only: delete event rows and set application back to PENDING */
public function unscheduleToPending(int $eventApplicationID, int $adminId = null): bool
{
    try {
        $this->db->beginTransaction();

        // Lock and ensure current state is ACCEPTED
        $chk = $this->db->prepare("
            SELECT eventApplicationStatus
            FROM eventapplication
            WHERE eventApplicationID = :id
            FOR UPDATE
        ");
        $chk->execute([':id' => $eventApplicationID]);
        $cur = $chk->fetchColumn();
        if ($cur === false) { $this->db->rollBack(); return false; }
        if (strtoupper((string)$cur) !== 'ACCEPTED') {
            // only ACCEPTED can be unscheduled
            $this->db->rollBack(); 
            return false;
        }

        // Remove scheduled event(s) for this application
        $del = $this->db->prepare("DELETE FROM event WHERE eventApplicationID = :id");
        $okDel = $del->execute([':id' => $eventApplicationID]);

        // Back to PENDING and stamp admin (guard current state)
        $upd = $this->db->prepare("
            UPDATE eventapplication
            SET eventApplicationStatus = 'PENDING',
                adminID = :adm
            WHERE eventApplicationID = :id
              AND eventApplicationStatus = 'ACCEPTED'
        ");
        $okUpd = $upd->execute([':adm' => $adminId, ':id' => $eventApplicationID]);

        if ($okDel && $okUpd && $upd->rowCount() === 1) { 
            $this->db->commit(); 
            return true; 
        }
        $this->db->rollBack(); 
        return false;
    } catch (\PDOException $e) {
        error_log("unscheduleToPending: " . $e->getMessage());
        try { $this->db->rollBack(); } catch (\Throwable $t) {}
        return false;
    }
}

public function markPendingIfRejected(int $eventApplicationID, int $adminId): bool
{
    try {
        $st = $this->db->prepare("
            UPDATE eventapplication
            SET eventApplicationStatus = 'PENDING',
                adminID = :adm
            WHERE eventApplicationID = :id
              AND eventApplicationStatus = 'REJECTED'
        ");
        $st->execute([':adm' => $adminId, ':id' => $eventApplicationID]);
        return $st->rowCount() === 1; // true only if it was REJECTED and is now PENDING
    } catch (\PDOException $e) {
        error_log("markPendingIfRejected: " . $e->getMessage());
        return false;
    }
}

public function autoUpdateEventApplicationStatus(int $eventApplicationID): void
{
    try {
        $sql = "
            UPDATE eventapplication
            SET eventApplicationStatus = :status
            WHERE eventApplicationID = :id AND eventApplicationStatus = 'PENDING'
        ";

        $st = $this->db->prepare($sql);
        $st->execute([
            ':status' => 'REJECTED', 
            ':id'     => $eventApplicationID,
        ]);
    } catch (\PDOException $e) {
        error_log('autoUpdateEventApplicationStatus: ' . $e->getMessage());
    }
}

public function getEligibleElectionsForNominee(int $accountID): array
{
    try {
        $sql = "
            SELECT ee.electionID, ee.title
            FROM electionevent ee
            WHERE ee.electionEndDate > NOW()
              AND EXISTS (
                SELECT 1
                FROM registrationform rf
                WHERE rf.electionID = ee.electionID
                GROUP BY rf.electionID
                HAVING MAX(rf.registerEndDate) < NOW()
              )
              AND EXISTS (
                SELECT 1
                FROM nomineeapplication na
                JOIN student s ON s.studentID = na.studentID
                JOIN account a ON a.accountID = s.accountID
                WHERE na.electionID = ee.electionID
                  AND a.accountID  = :acc
                  AND UPPER(na.applicationStatus) = 'PUBLISHED'
              )
            ORDER BY ee.title ASC
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':acc' => $accountID]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("getEligibleElectionsForNominee: " . $e->getMessage());
        return [];
    }
}

/**
 * Get THIS user's nominee record for a given election.
 */
public function getNomineeForElectionAndAccount(int $electionID, int $accountID): ?array
{
    try {
        $sql = "
            SELECT 
                n.nomineeID,
                a.fullName,
                s.studentID
            FROM nominee n
            INNER JOIN account a ON a.accountID = n.accountID
            INNER JOIN student s ON s.accountID = a.accountID
            INNER JOIN nomineeapplication na
                    ON na.studentID = s.studentID
                   AND na.electionID = n.electionID
            WHERE n.electionID = :eid
              AND a.accountID  = :acc
              AND UPPER(na.applicationStatus) = 'PUBLISHED'
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':eid' => $electionID, ':acc' => $accountID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (PDOException $e) {
        error_log("getNomineeForElectionAndAccount: " . $e->getMessage());
        return null;
    }
}

    // Paging 
    public function getPagedScheduleLocations(int $page, int $limit, string $search = '', string $filterStatus = ''): SimplePager 
    {
        $this->electionEventModel->autoRollElectionStatuses();
        $this->autoRollScheduleLocationStatuses();
        $sql    = "        
            SELECT
                ea.eventApplicationID,
                ea.eventName,
                ea.eventApplicationStatus,
                ea.desiredStartDateTime,
                ee.title AS election_event,
                ee.electionStartDate,
                ee.electionEndDate,
                ee.status,
                nomAcc.fullName AS nominee_fullName,
                admAcc.fullName AS admin_fullName,
                ea.nomineeID,
                ea.adminID,
                ea.electionID,
                ee.title AS event_name
            FROM eventapplication ea
            INNER JOIN electionevent ee ON ee.electionID = ea.electionID
            INNER JOIN nominee n        ON n.nomineeID   = ea.nomineeID
            INNER JOIN account nomAcc   ON nomAcc.accountID = n.accountID
            LEFT  JOIN administrator adm ON adm.adminID  = ea.adminID
            LEFT  JOIN account admAcc    ON admAcc.accountID = adm.accountID
            WHERE 1";

        $params = [];

        if ($search !== '') {
            $sql .= "
                AND (
                    ea.eventName       LIKE :q
                    OR ee.title        LIKE :q
                    OR nomAcc.fullName LIKE :q
                )
            ";
            $params[':q'] = '%' . $search . '%';
        }


        if ($filterStatus !== '' && in_array($filterStatus, ['PENDING', 'ACCEPTED', 'REJECTED'], true)) {
            $sql .= " AND ea.eventApplicationStatus = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY ea.eventApplicationID DESC";

        // SimplePager does the LIMIT/OFFSET and total counting.
        return new SimplePager($this->db, $sql, $params, $limit, $page);
    }

    // Looks like no use
    public function getPagedScheduleLocationsByAccount(int $page, int $limit, string $search = '', string $filterStatus = ''): SimplePager 
    {
        $this->electionEventModel->autoRollElectionStatuses();
        $this->autoRollScheduleLocationStatuses();
        $sql    = "        
            SELECT
                ea.eventApplicationID,
                ea.eventName,
                ea.eventApplicationStatus,
                ea.desiredStartDateTime,
                ee.title AS election_event,
                ee.electionStartDate,
                ee.electionEndDate,
                ee.status,
                nomAcc.fullName AS nominee_fullName,
                admAcc.fullName AS admin_fullName,
                ea.nomineeID,
                ea.adminID,
                ea.electionID,
                ee.title AS event_name
            FROM eventapplication ea
            INNER JOIN electionevent ee ON ee.electionID = ea.electionID
            INNER JOIN nominee n        ON n.nomineeID   = ea.nomineeID
            INNER JOIN account nomAcc   ON nomAcc.accountID = n.accountID
            LEFT  JOIN administrator adm ON adm.adminID  = ea.adminID
            LEFT  JOIN account admAcc    ON admAcc.accountID = adm.accountID
            WHERE 1";

        $params = [];

        if ($search !== '') {
            $sql .= "
                AND (
                    ea.eventName       LIKE :q
                    OR ee.title        LIKE :q
                    OR nomAcc.fullName LIKE :q
                )
            ";
            $params[':q'] = '%' . $search . '%';
        }


        if ($filterStatus !== '' && in_array($filterStatus, ['PENDING', 'ACCEPTED', 'REJECTED'], true)) {
            $sql .= " AND ea.eventApplicationStatus = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY ea.eventApplicationID DESC";

        // SimplePager does the LIMIT/OFFSET and total counting.
        return new SimplePager($this->db, $sql, $params, $limit, $page);
    }



}
