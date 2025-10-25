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

    public function getAllElectionEvents()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM electionevent");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Handle exception (log it, rethrow it, etc.)
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }
}