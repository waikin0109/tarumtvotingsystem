<?php

namespace Model\NomineeModel;

use PDO;
use PDOException;
use Database;

class NomineeModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getNomineeIdByAccId(int $accountID): ?int {
    try {
        $stmt = $this->db->prepare("
            SELECT n.nomineeID
            FROM nominee n
            INNER JOIN account acc ON acc.accountID = n.accountID
            WHERE n.accountID = ?
              AND acc.role = 'NOMINEE'
            LIMIT 1
        ");
        $stmt->execute([$accountID]);
        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    } catch (PDOException $e) {
        error_log('getNomineeIdByAccId error: '.$e->getMessage());
        return null;
    }
}


    
}