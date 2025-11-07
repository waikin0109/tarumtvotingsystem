<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;

class RuleModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllRules()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, e.title AS event_name
                FROM rule r
                LEFT JOIN electionevent e ON r.electionID = e.electionID
                ORDER BY r.ruleID ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getAllRules: ' . $e->getMessage());
            return false;
        }
    }

    public function createRule($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO rule (ruleTitle, content, dateCreated, electionID) VALUES (?, ?, NOW(),?)");
            $stmt->execute([
                $data['ruleTitle'],
                $data['content'],
                $data['electionID']
            ]);
        } catch (PDOException $e) {
            error_log("Error in createRule: " . $e->getMessage());
            return false;
        }
    }

    public function getRuleById($ruleID) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, e.title AS event_name
                FROM rule r
                LEFT JOIN electionevent e ON r.electionID = e.electionID
                WHERE r.ruleID = ?
            ");
            $stmt->execute([$ruleID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getRuleById: " . $e->getMessage());
            return false;
        }
    }

    public function updateRule($ruleID, $data) {
        try {
            $stmt = $this->db->prepare("UPDATE rule SET ruleTitle = ?, content = ?, electionID = ? WHERE ruleID = ?");
            $stmt->execute([
                $data['ruleTitle'],
                $data['content'],
                $data['electionID'],
                $ruleID
            ]);
        } catch (PDOException $e) {
            error_log("Error in updateRule: " . $e->getMessage());
            return false;
        }
    }

    public function deleteRule($ruleID) {
        try {
            $stmt = $this->db->prepare("DELETE FROM rule WHERE ruleID = :ruleID");
            $stmt->bindParam(':ruleID', $ruleID, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteRule: " . $e->getMessage());
            return false;
        }
    }

}