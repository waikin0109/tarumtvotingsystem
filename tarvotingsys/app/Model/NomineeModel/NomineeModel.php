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

    public function getNomineeIdByAccId(int $accountID): ?int
    {
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
            return $id !== false ? (int) $id : null;
        } catch (PDOException $e) {
            error_log('getNomineeIdByAccId error: ' . $e->getMessage());
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
            return (int) $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('resetNomineeRolesToStudentByElection error: ' . $e->getMessage());
            return 0;
        }
    }

    public function getNomineeProfileByAccountId(int $accountID): ?array
    {
        try {
            $sql = "
                SELECT
                    n.nomineeID,
                    n.manifesto,
                    n.raceID,
                    n.electionID,

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
                    acc.passwordHash,

                    f.facultyCode,
                    f.facultyName,

                    s.studentID,
                    s.program,
                    s.intakeYear,

                    r.raceTitle,
                    r.seatType,

                    e.title  AS electionTitle,
                    e.status AS electionStatus
                FROM nominee n
                INNER JOIN account acc ON acc.accountID = n.accountID
                LEFT JOIN student s      ON s.accountID = acc.accountID
                LEFT JOIN faculty f      ON acc.facultyID = f.facultyID
                LEFT JOIN race r         ON r.raceID = n.raceID
                LEFT JOIN electionevent e ON e.electionID = n.electionID
                WHERE acc.accountID = ?
                ORDER BY e.electionStartDate DESC
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (PDOException $e) {
            error_log('NomineeModel getNomineeProfileByAccountId error: ' . $e->getMessage());
            return null;
        }
    }

    public function updatePassword(int $accountID, string $passwordHash): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account
                SET passwordHash = ?
                WHERE accountID = ?
            ");
            return $stmt->execute([$passwordHash, $accountID]);
        } catch (PDOException $e) {
            error_log('NomineeModel updatePassword error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateProfilePhoto(int $accountID, string $photoUrl): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account
                SET profilePhotoURL = ?
                WHERE accountID = ?
            ");
            return $stmt->execute([$photoUrl, $accountID]);
        } catch (PDOException $e) {
            error_log('NomineeModel updateProfilePhoto error: ' . $e->getMessage());
            return false;
        }
    }

    public function getBasicNomineeByAccountId(int $accountID): ?array
    {
        try {
            $sql = "
            SELECT 
                n.nomineeID,
                n.electionID,
                n.raceID,
                n.accountID,
                acc.facultyID
            FROM nominee n
            INNER JOIN account acc ON acc.accountID = n.accountID
            WHERE n.accountID = ?
            LIMIT 1
        ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$accountID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getBasicNomineeByAccountId error: ' . $e->getMessage());
            return null;
        }
    }

    public function updateNomineeRace(int $nomineeID, int $raceID): bool
    {
        try {
            $sql = "UPDATE nominee SET raceID = ? WHERE nomineeID = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$raceID, $nomineeID]);
        } catch (PDOException $e) {
            error_log('updateNomineeRace error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateManifesto(int $nomineeID, string $manifesto): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE nominee
               SET manifesto = :manifesto
             WHERE nomineeID = :id
        ");
            return $stmt->execute([
                ':manifesto' => $manifesto,
                ':id' => $nomineeID,
            ]);
        } catch (PDOException $e) {
            error_log('updateManifesto error: ' . $e->getMessage());
            return false;
        }
    }

    public function getElectionsForBrowse(): array
    {
        try {
            $sql = "
            SELECT DISTINCT ee.electionID, ee.title
            FROM electionevent ee
            INNER JOIN race r 
                ON r.electionID = ee.electionID
            INNER JOIN nominee n 
                ON n.electionID = ee.electionID
            WHERE ee.status IN ('ONGOING', 'COMPLETED')
            ORDER BY ee.electionStartDate DESC, ee.electionID DESC
        ";

            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getElectionsForBrowse error: ' . $e->getMessage());
            return [];
        }
    }

    /* ------------------------------------------------------------------
     * 2. Races dropdown for a selected election
     * ------------------------------------------------------------------ */
    public function getRacesByElection(int $electionID): array
    {
        try {
            $sql = "
        SELECT DISTINCT
            r.raceID,
            r.raceTitle
        FROM race r
        INNER JOIN electionevent ee
            ON ee.electionID = r.electionID
        INNER JOIN nominee n
            ON n.raceID = r.raceID
        WHERE ee.electionID = :electionID
        ORDER BY r.raceTitle
    ";

            $st = $this->db->prepare($sql);
            $st->execute(['electionID' => $electionID]);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getRacesByElection error: ' . $e->getMessage());
            return [];
        }
    }

    /* ------------------------------------------------------------------
     * 3. Nominees list (filtered by election + optional race)
     * ------------------------------------------------------------------ */
    public function getNomineesForBrowse(int $electionID, ?int $raceID = null): array
    {
        try {
            $params = ['electionID' => $electionID];
            $raceFilter = '';

            if (!empty($raceID)) {
                $raceFilter = ' AND r.raceID = :raceID ';
                $params['raceID'] = $raceID;
            }

            $sql = "
            SELECT
                n.nomineeID,
                a.accountID,
                a.fullName,
                a.profilePhotoURL,
                fac.facultyName,
                fac.facultyCode,
                s.program AS programmeName,
                ''        AS programmeCode,
                n.manifesto,
                r.raceTitle,
                r.seatType
            FROM nominee n
            INNER JOIN race r
                ON r.raceID = n.raceID
            INNER JOIN electionevent ee
                ON ee.electionID = r.electionID
            INNER JOIN account a
                ON a.accountID = n.accountID
            LEFT JOIN student s
                ON s.accountID = a.accountID
            INNER JOIN faculty fac
                ON fac.facultyID = a.facultyID
            WHERE ee.electionID = :electionID
            $raceFilter
            ORDER BY r.raceTitle, a.fullName
        ";

            $st = $this->db->prepare($sql);
            $st->execute($params);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getNomineesForBrowse error: ' . $e->getMessage());
            return [];
        }
    }

}