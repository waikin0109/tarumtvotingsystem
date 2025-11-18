<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;

class BallotModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }


    /* ----------------------------  ENVELOPES  ---------------------------- */

    /** Latest envelope for this user & session (any status) */
    // public function getLatestEnvelope(int $accountID, int $voteSessionID): ?array
    // {
    //     try {
    //         $sql = "
    //             SELECT *
    //             FROM ballotenvelope
    //             WHERE accountID = :accountID
    //               AND voteSessionID = :sessionID
    //             ORDER BY ballotEnvelopeID DESC
    //             LIMIT 1
    //         ";
    //         $st = $this->db->prepare($sql);
    //         $st->execute([
    //             ':accountID' => $accountID,
    //             ':sessionID' => $voteSessionID,
    //         ]);
    //         $row = $st->fetch(PDO::FETCH_ASSOC);
    //         return $row ?: null;
    //     } catch (PDOException $e) {
    //         error_log('getLatestEnvelope error: ' . $e->getMessage());
    //         return null;
    //     }
    // }

    /** Check if user has already SUBMITTED an envelope for this session */
/** Check if user has already SUBMITTED an envelope for this session */
public function hasSubmittedEnvelope(int $accountID, int $voteSessionID): bool
{
    try {
        $sql = "
            SELECT 1
            FROM ballotenvelope
            WHERE accountID = :accountID
              AND voteSessionID = :sessionID
              AND ballotEnvelopeStatus = 'SUBMITTED'
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':accountID' => $accountID,
            ':sessionID' => $voteSessionID,
        ]);
        return (bool) $st->fetchColumn();
    } catch (PDOException $e) {
        error_log('hasSubmittedEnvelope error: ' . $e->getMessage());
        return false;
    }
}


    /** Active envelope = ISSUED for this user & session */
    public function getActiveEnvelope(int $accountID, int $voteSessionID): ?array
    {
        try {
            $sql = "
                SELECT *
                FROM ballotenvelope
                WHERE accountID = :accountID
                  AND voteSessionID = :sessionID
                  AND ballotEnvelopeStatus = 'ISSUED'
                ORDER BY ballotEnvelopeID DESC
                LIMIT 1
            ";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':accountID' => $accountID,
                ':sessionID' => $voteSessionID,
            ]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getActiveEnvelope error: ' . $e->getMessage());
            return null;
        }
    }

    /** Simple random 64-hex receipt hash */
    public function generateReceiptHash(): string
    {
        return bin2hex(random_bytes(32)); // 64 hex chars
    }

    /** Create ISSUED envelope; return its ID or null */
    public function createEnvelope(int $accountID, int $voteSessionID, string $receiptHash): ?int
    {
        try {
            $sql = "
                INSERT INTO ballotenvelope (
                    ballotEnvelopeIssuedAt,
                    ballotEnvelopeSubmittedAt,
                    ballotEnvelopeStatus,
                    receiptCodeHash,
                    accountID,
                    voteSessionID
                ) VALUES (
                    NOW(), NULL, 'ISSUED', :hash, :accountID, :sessionID
                )
            ";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':hash'      => $receiptHash,
                ':accountID' => $accountID,
                ':sessionID' => $voteSessionID,
            ]);
            return (int) $this->db->lastInsertId() ?: null;
        } catch (PDOException $e) {
            error_log('createEnvelope error: ' . $e->getMessage());
            return null;
        }
    }

    /** Mark envelope as SUBMITTED */
private function markEnvelopeSubmitted(int $ballotEnvelopeID, string $status = 'SUBMITTED'): bool
{
    try {
        $sql = "
            UPDATE ballotenvelope
            SET ballotEnvelopeStatus = :status,
                ballotEnvelopeSubmittedAt = NOW()
            WHERE ballotEnvelopeID = :id
        ";
        $st = $this->db->prepare($sql);
        return $st->execute([
            ':id'     => $ballotEnvelopeID,
            ':status' => $status,
        ]);
    } catch (PDOException $e) {
        error_log('markEnvelopeSubmitted error: ' . $e->getMessage());
        return false;
    }
}


    /* -------------------------  RACES & NOMINEES  ------------------------ */

    /**
     * Races + nominees for this session for a given faculty.
     * - Always returns Campus-wide races.
     * - Returns Faculty Rep races only for that faculty (if $facultyID > 0).
     *
     * Output structure:
     * [
     *   [
     *     'raceID' => ...,
     *     'raceTitle' => ...,
     *     'seatType' => 'FACULTY_REP'|'CAMPUS_WIDE',
     *     'seatCount' => ...,
     *     'maxSelectable' => ...,
     *     'facultyID' => ...,
     *     'facultyName' => ...,
     *     'nominees' => [
     *         ['nomineeID'=>..., 'fullName'=>..., 'manifesto'=>..., 'profilePhotoURL'=>...],
     *         ...
     *     ]
     *   ],
     *   ...
     * ]
     */
    public function getRacesWithNomineesForVoter(int $voteSessionID, int $facultyID): array
    {
        try {
            $sql = "
                SELECT
                    r.raceID,
                    r.raceTitle,
                    r.seatType,
                    r.seatCount,
                    r.maxSelectable,
                    r.facultyID,
                    f.facultyName,
                    n.nomineeID,
                    n.manifesto,
                    a.fullName,
                    a.profilePhotoURL
                FROM race r
                LEFT JOIN faculty f ON f.facultyID = r.facultyID
                LEFT JOIN nominee n ON n.raceID = r.raceID
                LEFT JOIN account a ON a.accountID = n.accountID
                WHERE r.voteSessionID = :sessionID
                  AND (
                        r.seatType <> 'FACULTY_REP'
                        OR :facultyID <= 0
                        OR r.facultyID = :facultyID
                      )
                ORDER BY
                    CASE r.seatType WHEN 'FACULTY_REP' THEN 1 ELSE 2 END,
                    r.raceTitle,
                    a.fullName
            ";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':sessionID' => $voteSessionID,
                ':facultyID' => $facultyID,
            ]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Group into races
            $byRace = [];
            foreach ($rows as $row) {
                $rid = (int) $row['raceID'];
                if (!isset($byRace[$rid])) {
                    $byRace[$rid] = [
                        'raceID'        => $rid,
                        'raceTitle'     => $row['raceTitle'],
                        'seatType'      => $row['seatType'],
                        'seatCount'     => (int) $row['seatCount'],
                        'maxSelectable' => (int) $row['maxSelectable'],
                        'facultyID'     => $row['facultyID'] !== null ? (int) $row['facultyID'] : null,
                        'facultyName'   => $row['facultyName'] ?? null,
                        'nominees'      => [],
                    ];
                }
                if (!empty($row['nomineeID'])) {
                    $byRace[$rid]['nominees'][] = [
                        'nomineeID'       => (int) $row['nomineeID'],
                        'fullName'        => $row['fullName'] ?? '(Unknown)',
                        'manifesto'       => $row['manifesto'] ?? '',
                        'profilePhotoURL' => $row['profilePhotoURL'] ?? '',
                    ];
                }
            }

            return array_values($byRace);
        } catch (PDOException $e) {
            error_log('getRacesWithNomineesForVoter error: ' . $e->getMessage());
            return [];
        }
    }

    /* -----------------------------  BALLOT  ------------------------------ */

    /** Generate unique 26-character ballotID (hex) */
    private function generateBallotID(): string
    {
        // 13 bytes => 26 hex chars, matches CHAR(26)
        return bin2hex(random_bytes(13));
    }

    /**
     * Submit ballot + selections in a single transaction.
     * $selections = [ raceID => [nomineeID, ...], ... ]
     */
public function submitBallot(
    int $voteSessionID,
    int $ballotEnvelopeID,
    array $selections
): bool {
    try {
        $this->db->beginTransaction();

        // Ensure envelope still ISSUED & belongs to the right session
        $check = $this->getActiveEnvelopeForId($ballotEnvelopeID);
        if (!$check || (int) $check['voteSessionID'] !== $voteSessionID) {
            $this->db->rollBack();
            return false;
        }

        $ballotID = $this->generateBallotID();

        // Ballot is always SUBMITTED once user presses "Submit"
        $sqlBallot = "
            INSERT INTO ballot (ballotID, ballotCreatedAt, ballotStatus, voteSessionID)
            VALUES (:id, NOW(), 'SUBMITTED', :sessionID)
        ";
        $stB = $this->db->prepare($sqlBallot);
        $stB->execute([
            ':id'        => $ballotID,
            ':sessionID' => $voteSessionID,
        ]);

        // Only insert selections if there are any.
        // If empty => blank ballot / undervote.
        if (!empty($selections)) {
            $sqlSel = "
                INSERT INTO ballotselection (selectedAt, raceID, nomineeID, ballotID)
                VALUES (NOW(), :raceID, :nomineeID, :ballotID)
            ";
            $stS = $this->db->prepare($sqlSel);

            foreach ($selections as $raceID => $nomineeIds) {
                foreach ($nomineeIds as $nomineeID) {
                    $stS->execute([
                        ':raceID'    => $raceID,
                        ':nomineeID' => $nomineeID,
                        ':ballotID'  => $ballotID,
                    ]);
                }
            }
        }

        // Envelope becomes SUBMITTED regardless of blank / non-blank
        if (!$this->markEnvelopeSubmitted($ballotEnvelopeID, 'SUBMITTED')) {
            $this->db->rollBack();
            return false;
        }

        $this->db->commit();
        return true;
    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log('submitBallot error: ' . $e->getMessage());
        return false;
    }
}

    /** Envelope lookup by ID (used inside submitBallot) */
    private function getActiveEnvelopeForId(int $ballotEnvelopeID): ?array
    {
        try {
            $sql = "
                SELECT *
                FROM ballotenvelope
                WHERE ballotEnvelopeID = :id
                  AND ballotEnvelopeStatus = 'ISSUED'
                LIMIT 1
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':id' => $ballotEnvelopeID]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getActiveEnvelopeForId error: ' . $e->getMessage());
            return null;
        }
    }

    public function getResultsBySession(int $voteSessionID): array
{
    try {
        $sql = "
            SELECT
                r.raceID,
                r.raceTitle,
                r.seatType,
                r.facultyID,
                f.facultyName,
                n.nomineeID,
                acc.fullName,
                COALESCE(COUNT(bs.selectionID), 0) AS votes
            FROM race r
            JOIN nominee n
              ON n.raceID = r.raceID
            JOIN account acc
              ON acc.accountID = n.accountID
            LEFT JOIN faculty f
              ON f.facultyID = r.facultyID
            LEFT JOIN ballotselection bs
              ON bs.raceID = r.raceID
             AND bs.nomineeID = n.nomineeID
            WHERE r.voteSessionID = :sessionID
            GROUP BY
                r.raceID,
                r.raceTitle,
                r.seatType,
                r.facultyID,
                f.facultyName,
                n.nomineeID,
                acc.fullName
            ORDER BY
                CASE r.seatType WHEN 'FACULTY_REP' THEN 1 ELSE 2 END,
                r.raceTitle,
                acc.fullName
        ";

        $st = $this->db->prepare($sql);
        $st->execute([':sessionID' => $voteSessionID]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log('getResultsBySession error: ' . $e->getMessage());
        return [];
    }
}

public function expireUnsubmittedEnvelopesForSession(int $voteSessionID): int
{
    try {
        $this->db->beginTransaction();

        // 1. Find all ISSUED envelopes for this session
        $sql = "
            SELECT ballotEnvelopeID
            FROM ballotenvelope
            WHERE voteSessionID = :sessionID
              AND ballotEnvelopeStatus = 'ISSUED'
            FOR UPDATE
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':sessionID' => $voteSessionID]);
        $envelopeIds = $st->fetchAll(PDO::FETCH_COLUMN);

        if (!$envelopeIds) {
            $this->db->commit();
            return 0;
        }

        // 2. Prepare statements
        $stUpdateEnv = $this->db->prepare("
            UPDATE ballotenvelope
            SET ballotEnvelopeStatus = 'EXPIRED'
            WHERE ballotEnvelopeID = :id
        ");

        $stInsertBallot = $this->db->prepare("
            INSERT INTO ballot (ballotID, ballotCreatedAt, ballotStatus, voteSessionID)
            VALUES (:ballotID, NOW(), 'VOID', :sessionID)
        ");

        $count = 0;

        foreach ($envelopeIds as $envId) {
            // Mark envelope as EXPIRED
            $stUpdateEnv->execute([':id' => (int)$envId]);

            // Create a VOID ballot with no selections
            $ballotID = $this->generateBallotID();
            $stInsertBallot->execute([
                ':ballotID'  => $ballotID,
                ':sessionID' => $voteSessionID,
            ]);

            $count++;
        }

        $this->db->commit();
        return $count;
    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log('expireUnsubmittedEnvelopesForSession error: ' . $e->getMessage());
        return 0;
    }
}


}
