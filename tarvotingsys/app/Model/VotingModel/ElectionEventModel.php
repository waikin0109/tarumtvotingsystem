<?php

namespace Model\VotingModel;

use Model\NomineeModel\NomineeModel; 
use PDO;
use PDOException;
use Database;
use Library\SimplePager;

class ElectionEventModel
{
    private $db;
    private NomineeModel $nomineeModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->nomineeModel = new NomineeModel();
    }

    public function determineStatus($start, $end)
    {
        $now = time();
        $startTs = strtotime($start);
        $endTs   = strtotime($end);

        if ($startTs > $now) {
            return 'PENDING';
        } elseif ($endTs > $now) {
            return 'ONGOING';
        } else {
            return 'COMPLETED';
        }
    }

    public function updateElectionStatus($currentStatus, $electionID){
        $update = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
        $update->execute([$currentStatus, $electionID]);
    }


    public function getAllElectionEvents()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.*,
                    acc.fullName AS creatorName
                FROM electionevent e
                LEFT JOIN account acc ON acc.accountID = e.accountID
                ORDER BY e.electionEndDate DESC
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$events) {
                return false;
            }

            // Check Election Event Status + Nominee Role (After Completed)
            foreach ($events as &$event) {
                $currentStatus = $this->determineStatus($event['electionStartDate'], $event['electionEndDate']);

                if ($currentStatus !== $event['status']) {
                    // You can wrap these two lines in a transaction if you want atomicity
                    $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                    $upd->execute([$currentStatus, $event['electionID']]);
                    $event['status'] = $currentStatus;

                    // Update Nominee Role (when event just became COMPLETED) 
                    if ($currentStatus === 'COMPLETED') {
                        $this->nomineeModel->resetNomineeRolesToStudentByElection($event['electionID']);
                    }
                }
            }

            return $events;
        } catch (PDOException $e) {
            error_log("Error in getAllElectionEvents: " . $e->getMessage());
            return false;
        }
    }

    public function createElectionEvent($data)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO electionevent (title, description, electionStartDate, electionEndDate, dateCreated, status, accountID) VALUES (?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->execute([
                $data['electionEventName'],
                $data['electionEventDescription'],
                $data['electionEventStartDate'] . ' ' . $data['electionEventStartTime'],
                $data['electionEventEndDate'] . ' ' . $data['electionEventEndTime'],
                $data['electionEventStatus'],
                $data['accountID']  
            ]);
        } catch (PDOException $e) {
            // Handle exception (log it, rethrow it, etc.)
            error_log("Error in createElectionEvent: " . $e->getMessage());
            return false;
        }
    }

    public function getElectionEventById($electionID)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    e.electionID,
                    e.title,
                    e.description,
                    e.electionStartDate,
                    e.electionEndDate,
                    DATE(e.electionStartDate) AS startDate,
                    DATE_FORMAT(e.electionStartDate, '%H:%i') AS startTime,
                    DATE(e.electionEndDate)   AS endDate,
                    DATE_FORMAT(e.electionEndDate,   '%H:%i') AS endTime,
                    e.status,
                    e.dateCreated,
                    e.accountID,
                    acc.fullName AS creatorName
                FROM electionevent e
                LEFT JOIN account acc ON acc.accountID = e.accountID
                WHERE e.electionID = ?
            ");
            $stmt->execute([$electionID]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                return false;
            }

            // Check Election Event Status + Nominee Role (After Completed)
            $currentStatus = $this->determineStatus($event['electionStartDate'], $event['electionEndDate']);

            if ($currentStatus !== $event['status']) {
                $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                $upd->execute([$currentStatus, $event['electionID']]);
                $event['status'] = $currentStatus;

                // Update Nominee Role on completion
                if ($currentStatus === 'COMPLETED') {
                    $this->nomineeModel->resetNomineeRolesToStudentByElection($event['electionID']);
                }
            }

            return $event;
        } catch (PDOException $e) {
            error_log("Error in getElectionEventById: " . $e->getMessage());
            return false;
        }
    }


    public function updateElectionEvent($electionID, $data)
    {
        try {
            $stmt = $this->db->prepare("UPDATE electionevent SET title = ?, description = ?, electionStartDate = ?, electionEndDate = ?, status = ? WHERE electionID = ?");
            $stmt->execute([
                $data['electionEventName'],
                $data['electionEventDescription'],
                $data['electionEventStartDate'] . ' ' . $data['electionEventStartTime'],
                $data['electionEventEndDate'] . ' ' . $data['electionEventEndTime'],
                $data['electionEventStatus'],
                $electionID
            ]);
        } catch (PDOException $e) {
            error_log("Error in updateElectionEvent: " . $e->getMessage());
            return false;
        }
    }

    public function deleteElectionEvent($electionID)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM electionevent WHERE electionID = :id");
            $stmt->bindParam(':id', $electionID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteElectionEvent: " . $e->getMessage());
            return false;
        }
    }

    // --------------------------------------- Other Needed Functions ------------------------------------------------ //
    // Functions Use in Rules
    public function getEligibleElectionEvents($allowed = ['Pending','Ongoing'])
    {
        $allowed = array_map('strtolower', $allowed);

        // We must SELECT start/end to recompute status
        $sql = "SELECT electionID, title, status, electionStartDate, electionEndDate
                FROM electionevent
                ORDER BY electionID ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $eligible = [];

        foreach ($rows as $ev) {
            $current = $this->determineStatus($ev['electionStartDate'], $ev['electionEndDate']);

            // If status changed, persist it
            if ($current !== $ev['status']) {
                $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                $upd->execute([$current, $ev['electionID']]);
                $ev['status'] = $current; // keep local row consistent
            }

            if (in_array(strtolower($ev['status']), $allowed, true)) {
                // Return only fields the callers need
                $eligible[] = [
                    'electionID' => $ev['electionID'],
                    'title'      => $ev['title'],
                    'status'     => $ev['status'],
                ];
            }
        }

        return $eligible;
    }

    // --------- Refresh + return single row if eligible ---------
    public function getElectionEventByIdIfEligible($electionID, $allowed = ['Pending','Ongoing'])
    {
        $allowed = array_map('strtolower', $allowed);

        $sql = "SELECT electionID, title, status, electionStartDate, electionEndDate
                FROM electionevent
                WHERE electionID = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionID]);
        $ev = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ev) return false;

        $current = $this->determineStatus($ev['electionStartDate'], $ev['electionEndDate']);

        if ($current !== $ev['status']) {
            $upd = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
            $upd->execute([$current, $ev['electionID']]);
            $ev['status'] = $current;
        }

        return in_array(strtolower($ev['status']), $allowed, true)
            ? ['electionID' => $ev['electionID'], 'title' => $ev['title'], 'status' => $ev['status']]
            : false;
    }

    public function getAllPublishedElectionEvents(): array
    {
        try {
            $sql = "
                SELECT DISTINCT
                    ee.electionID,
                    ee.title AS event_name
                FROM electionevent ee
                INNER JOIN nomineeapplication na
                    ON na.electionID = ee.electionID
                WHERE UPPER(na.applicationStatus) = 'PUBLISHED'
                AND ee.electionEndDate > NOW()
                ORDER BY ee.electionEndDate DESC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $rows;
        } catch (PDOException $e) {
            error_log('getAllPublishedElectionEvents: ' . $e->getMessage());
            return [];
        }
    }

    // Paging Settings
    public function getPagedElectionEvents(int $page, int $limit, string $search = '', string $filterStatus = ''): SimplePager 
    {
        $sql    = "SELECT electionID, title, dateCreated, status FROM electionevent WHERE 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND title LIKE :q";
            $params[':q'] = '%' . $search . '%';
        }

        if ($filterStatus !== '' && in_array($filterStatus, ['PENDING','ONGOING','COMPLETED'], true)) {
            $sql .= " AND status = :status";
            $params[':status'] = $filterStatus;
        }

        $sql .= " ORDER BY electionID DESC";

        // SimplePager does the LIMIT/OFFSET and total counting.
        return new SimplePager($this->db, $sql, $params, $limit, $page);
    }


}