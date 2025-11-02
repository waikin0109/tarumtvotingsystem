<?php

namespace Model\AdminModel;

use PDO;
use PDOException;
use Database;

class LoginModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByLoginID(int $loginID): ?array
    {
        try {
            $sql = "SELECT accountID, role, loginID, passwordHash, status, fullName
                    FROM account
                    WHERE loginID = :loginID
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['loginID' => $loginID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("findByLoginID error: " . $e->getMessage());
            return null;
        }
    }

    public function updateLastLoginAt(int $accountID): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE account SET lastLoginAt = NOW() WHERE accountID = ?");
            return $stmt->execute([$accountID]);
        } catch (PDOException $e) {
            error_log("updateLastLoginAt error: " . $e->getMessage());
            return false;
        }
    }
}
