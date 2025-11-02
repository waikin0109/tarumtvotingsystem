<?php
namespace Model\NomineeHandlingModel;

use PDO;
use Database;

class AcademicDocumentModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function insert(int $applicationSubmissionID, string $storedFilename): bool
    {
        $sql = "INSERT INTO academicdocument (academicFilename, applicationSubmissionID) VALUES (?, ?)";
        $st = $this->db->prepare($sql);
        return $st->execute([$storedFilename, $applicationSubmissionID]);
    }

    /** List existing documents for one submission */
    public function listBySubmissionId(int $applicationSubmissionID): array
    {
        $sql = "SELECT academicID, academicFilename, applicationSubmissionID FROM academicdocument WHERE applicationSubmissionID = ? ORDER BY academicID ASC";
        $st  = $this->db->prepare($sql);
        $st->execute([$applicationSubmissionID]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Delete a single document row */
    public function delete(int $academicID): bool
    {
        $sql = "DELETE FROM academicdocument WHERE academicID = ?";
        $st  = $this->db->prepare($sql);
        return $st->execute([$academicID]);
    }

    /** Fetch one document (to remove file from disk safely) */
    public function findOne(int $academicID): ?array
    {
        $sql = "SELECT * FROM academicdocument WHERE academicID = ? LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([$academicID]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
