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

    public function getStudentIdByAccId($accountID) {
        try {
            $stmt = $this->db-> prepare("
                SELECT s.studentID
                FROM student s
                INNER JOIN account acc ON acc.accountID = s.accountID
                WHERE s.accountID = ?
                AND acc.role = 'STUDENT'
                LIMIT 1
            ");
            $stmt->execute([$accountID]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getStudentIdByAccId error: ' . $e->getMessage());
            return false;
        }
    }
}