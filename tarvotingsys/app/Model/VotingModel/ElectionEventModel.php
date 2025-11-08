<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;

class ElectionEventModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function determineStatus($start, $end)
    {
        $now = time();
        $startTs = strtotime($start);
        $endTs   = strtotime($end);

        if ($startTs > $now) {
            return 'Pending';
        } elseif ($endTs > $now) {
            return 'Ongoing';
        } else {
            return 'Completed';
        }
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
            ");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($events as &$event) {
                $currentStatus = $this->determineStatus($event['electionStartDate'], $event['electionEndDate']);

                if ($currentStatus !== $event['status']) {
                    $update = $this->db->prepare("UPDATE electionevent SET status = ? WHERE electionID = ?");
                    $update->execute([$currentStatus, $event['electionID']]);
                    $event['status'] = $currentStatus;
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
            return $stmt->fetch(PDO::FETCH_ASSOC);
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

    // --------------------------------------------------------------------------------------- //
    
    

}