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
}