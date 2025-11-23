<?php

namespace Model\AdminModel;

use PDO;
use PDOException;
use Database;

class AdminModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        
    }

    public function getAdminIdByAccId(int $accountID): ?int {
    try {
        $stmt = $this->db->prepare("
            SELECT a.adminID
            FROM administrator a
            INNER JOIN account acc ON acc.accountID = a.accountID
            WHERE a.accountID = ?
              AND acc.role = 'ADMIN'
            LIMIT 1
        ");
        $stmt->execute([$accountID]);
        $id = $stmt->fetchColumn();             
        return $id !== false ? (int)$id : null; 
    } catch (PDOException $e) {
        error_log('getAdminIdByAccId error: '.$e->getMessage());
        return null;
    }
}


    
}