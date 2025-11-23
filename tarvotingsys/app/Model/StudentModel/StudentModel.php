<?php

namespace Model\StudentModel;

use PDO;
use PDOException;
use Database;

class StudentModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllStudents()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, a.fullName
                FROM student s
                LEFT JOIN account a ON s.accountID = a.accountID
                ORDER BY s.studentID ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllStudents: " . $e->getMessage());
            return false;
        }
    }

    public function getStudentIdByAccId(int $accountID): ?int {
        try {
            $stmt = $this->db->prepare("
                SELECT s.studentID
                FROM student s
                INNER JOIN account acc ON acc.accountID = s.accountID
                WHERE s.accountID = ?
                AND acc.role = 'STUDENT'
                LIMIT 1
            ");
            $stmt->execute([$accountID]);
            $id = $stmt->fetchColumn();
            return $id !== false ? (int)$id : null;
        } catch (PDOException $e) {
            error_log('getStudentIdByAccId error: '.$e->getMessage());
            return null;
        }
    }

    public function getStudentById(int $studentID): ?array {
        $st = $this->db->prepare("SELECT s.studentID, ac.fullName
                                FROM student s
                                JOIN account ac ON ac.accountID = s.accountID
                                WHERE s.studentID = ?
                                LIMIT 1");
        $st->execute([$studentID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getStudentIdByNomineeId(int $nomineeID): ?int
    {
        try {
            $st = $this->db->prepare("
                SELECT s.studentID
                FROM nominee n
                JOIN account a ON a.accountID = n.accountID
                JOIN student s ON s.accountID = a.accountID
                WHERE n.nomineeID = ?
                LIMIT 1
            ");
            $st->execute([$nomineeID]);
            $id = $st->fetchColumn();
            return $id !== false ? (int)$id : null;
        } catch (PDOException $e) {
            error_log('getStudentIdByNomineeId error: ' . $e->getMessage());
            return null;
        }
    }

    public function getStudentProfileByAccountId(int $accountID): ?array
    {
        try {
            $sql = "
                SELECT 
                    s.studentID,
                    s.program,
                    s.intakeYear,
                    acc.accountID,
                    acc.role,
                    acc.loginID,
                    acc.status,
                    acc.lastLoginAt,
                    acc.fullName,
                    acc.gender,
                    acc.email,
                    acc.phoneNumber,
                    acc.profilePhotoURL,
                    f.facultyCode,
                    f.facultyName,
                    acc.passwordHash
                FROM student s
                INNER JOIN account acc ON acc.accountID = s.accountID
                LEFT JOIN faculty f ON acc.facultyID = f.facultyID
                WHERE acc.accountID = ?
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getStudentProfileByAccountId error: ' . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $accountID, string $passwordHash): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE account SET passwordHash = ? WHERE accountID = ?");
            return $stmt->execute([$passwordHash, $accountID]);
        } catch (PDOException $e) {
            error_log('StudentModel updatePassword error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePhoto(int $accountID, string $photoUrl): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE account SET profilePhotoURL = ? WHERE accountID = ?");
            return $stmt->execute([$photoUrl, $accountID]);
        } catch (PDOException $e) {
            error_log('StudentModel updateProfilePhoto error: ' . $e->getMessage());
            return false;
        }
    }




}