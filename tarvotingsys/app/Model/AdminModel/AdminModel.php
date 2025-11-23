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

    public function getAdminProfileByAccountId(int $accountID): ?array
    {
        try {
            $sql = "
                SELECT 
                    ad.adminID,
                    ad.administratorLevel,
                    ad.department,
                    acc.accountID,
                    acc.role,
                    acc.loginID,
                    acc.fullName,
                    acc.gender,
                    acc.email,
                    acc.phoneNumber,
                    acc.profilePhotoURL,
                    acc.status,
                    acc.lastLoginAt,
                    f.facultyCode,
                    f.facultyName,
                    acc.passwordHash
                FROM administrator ad
                INNER JOIN account acc ON acc.accountID = ad.accountID
                LEFT JOIN faculty f ON acc.facultyID = f.facultyID
                WHERE acc.accountID = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getAdminProfileByAccountId error: ' . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $accountID, string $passwordHash): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE account SET passwordHash = ? WHERE accountID = ?");
            return $stmt->execute([$passwordHash, $accountID]);
        } catch (PDOException $e) {
            error_log('updatePassword error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePhoto(int $accountID, string $photoUrl): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE account SET profilePhotoURL = ? WHERE accountID = ?");
            return $stmt->execute([$photoUrl, $accountID]);
        } catch (PDOException $e) {
            error_log('updateProfilePhoto error: ' . $e->getMessage());
            return false;
        }
    }



    
}