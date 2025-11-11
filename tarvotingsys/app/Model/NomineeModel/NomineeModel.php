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

    public function resetNomineeRolesToStudentByElection(int $electionID): int
    {
        try {
            $sql = "
                UPDATE account a
                INNER JOIN nominee n
                    ON n.accountID = a.accountID
                SET a.role = 'STUDENT'
                WHERE n.electionID = ?
                AND a.role = 'NOMINEE'
                AND NOT EXISTS (
                        SELECT 1
                        FROM nominee n2
                        INNER JOIN electionevent e2
                            ON e2.electionID = n2.electionID
                        WHERE n2.accountID = a.accountID
                        AND e2.electionEndDate > NOW()   -- still pending/ongoing by time
                    )
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$electionID]);
            return (int)$stmt->rowCount();
        } catch (PDOException $e) {
            error_log('resetNomineeRolesToStudentByElection error: '.$e->getMessage());
            return 0;
        }
    }

}
