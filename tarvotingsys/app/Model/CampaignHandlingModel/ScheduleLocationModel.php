<?php

namespace Model\CampaignHandlingModel;

use PDO;
use PDOException;
use Database;

class ScheduleLocationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** List page (already working from your earlier code) */
    public function getAllScheduleLocations(): array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventApplicationStatus,
                    ee.title AS election_event,
                    nomAcc.fullName AS nominee_fullName,
                    admAcc.fullName AS admin_fullName,
                    ea.nomineeID,
                    ea.adminID,
                    ea.electionID
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
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getAllScheduleLocations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Eligible elections:
     *  - registration ended (MAX(registerEndDate) < NOW())
     *  - has at least one PUBLISHED nomineeapplication
     */
    // ***
    public function getEligibleElections(): array
    {
        try {
            $sql = "
                SELECT ee.electionID, ee.title
                FROM electionevent ee
                WHERE ee.electionEndDate > NOW()                            -- not ended
                AND EXISTS (                                              -- registration is over
                        SELECT 1
                        FROM registrationform rf
                        WHERE rf.electionID = ee.electionID
                        GROUP BY rf.electionID
                        HAVING MAX(rf.registerEndDate) < NOW()
                )
                AND EXISTS (                                              -- has at least one PUBLISHED nominee
                        SELECT 1
                        FROM nomineeapplication na
                        WHERE na.electionID = ee.electionID
                        AND UPPER(na.applicationStatus) = 'PUBLISHED'
                )
                ORDER BY ee.title ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute();
            return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("getEligibleElections: " . $e->getMessage());
            return [];
        }
    }


    /** Nominees in the selected election, displayed as Name (ID) */
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
                AND na.electionID = :eid
                AND UPPER(na.applicationStatus) = 'PUBLISHED'
                ORDER BY a.fullName ASC
            ";
            $st = $this->db->prepare($sql);
            $st->bindValue(':eid', $electionID, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("getNomineesByElection: " . $e->getMessage());
            return [];
        }
    }


    /** Registration window (for validation rule #4) */
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

    /** Resolve adminID from session accountID (if you track admins that way) */
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
                    (eventName, eventType, desiredDateTime, eventApplicationStatus, adminID, nomineeID, electionID)
                VALUES
                    (:eventName, :eventType, :desiredDateTime, 'PENDING', :adminID, :nomineeID, :electionID)
            ");
            return $st->execute([
                ':eventName'       => $data['eventName'],
                ':eventType'       => $data['eventType'],
                ':desiredDateTime' => $data['desiredDateTime'], // 'Y-m-d H:i:s'
                ':adminID'         => $data['adminID'],
                ':nomineeID'       => $data['nomineeID'],
                ':electionID'      => $data['electionID'],
            ]);
        } catch (PDOException $e) {
            error_log("createScheduleLocation: " . $e->getMessage());
            return false;
        }
    }

    // ***
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
            return $val ?: null; // returns e.g. "2025-10-31 18:00:00" or null
        } catch (PDOException $e) {
            error_log("getElectionEndDate: " . $e->getMessage());
            return null;
        }
    }

    // Read one application with joins for display
    public function getScheduleLocationById(int $eventApplicationID): ?array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventType,
                    ea.desiredDateTime,
                    ea.electionID,
                    ea.nomineeID,
                    ee.title AS electionTitle,
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
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("getScheduleLocationById: " . $e->getMessage());
            return null;
        }
    }

    // Update editable fields only
    public function updateScheduleLocation(int $eventApplicationID, array $data): bool
    {
        try {
            $st = $this->db->prepare("
                UPDATE eventapplication
                SET eventName = :eventName,
                    eventType = :eventType,
                    desiredDateTime = :desiredDateTime
                WHERE eventApplicationID = :id
            ");
            return $st->execute([
                ':eventName'       => $data['eventName'],
                ':eventType'       => $data['eventType'],
                ':desiredDateTime' => $data['desiredDateTime'],
                ':id'              => $eventApplicationID,
            ]);
        } catch (PDOException $e) {
            error_log("updateScheduleLocation: " . $e->getMessage());
            return false;
        }
    }

    public function getScheduleLocationDetailsById(int $eventApplicationID): ?array
    {
        try {
            $sql = "
                SELECT
                    ea.eventApplicationID,
                    ea.eventName,
                    ea.eventType,
                    ea.desiredDateTime,
                    ea.eventApplicationStatus,
                    ea.electionID,
                    ee.title AS electionTitle,

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
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\PDOException $e) {
            error_log("getScheduleLocationDetailsById: " . $e->getMessage());
            return null;
        }
    }



}
