<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;

class VoteSessionModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function autoRollStatuses(): void
    {
        try {
            // SCHEDULED -> OPEN
            $sqlOpen = "
                UPDATE votesession
                   SET voteSessionStatus = 'OPEN'
                 WHERE voteSessionStatus = 'SCHEDULED'
                   AND voteSessionStartAt IS NOT NULL
                   AND voteSessionEndAt   IS NOT NULL
                   AND voteSessionStartAt <= NOW()
                   AND NOW() < voteSessionEndAt
            ";
            $this->db->prepare($sqlOpen)->execute();

            // OPEN -> CLOSED
            $sqlClose = "
                UPDATE votesession
                   SET voteSessionStatus = 'CLOSED'
                 WHERE voteSessionStatus = 'OPEN'
                   AND voteSessionEndAt IS NOT NULL
                   AND voteSessionEndAt <= NOW()
            ";
            $this->db->prepare($sqlClose)->execute();
        } catch (PDOException $e) {
            error_log('autoRollStatuses error: ' . $e->getMessage());
        }
    }

    public function listForAdmin(): array
    {
        try {
            $sql = "
            SELECT
              vs.voteSessionID        AS VoteSessionID,
              vs.voteSessionName      AS VoteSessionName,
              vs.voteSessionType      AS VoteSessionType,
              vs.voteSessionStartAt   AS StartAt,
              vs.voteSessionEndAt     AS EndAt,
              vs.voteSessionStatus    AS VoteSessionStatus,
              vs.electionID           AS ElectionID,
              COALESCE(e.title, '(Unknown)') AS ElectionTitle
            FROM votesession vs
            LEFT JOIN electionevent e ON e.electionID = vs.electionID
            ORDER BY
              vs.voteSessionStartAt IS NULL,
              vs.voteSessionStartAt ASC,
              vs.voteSessionID DESC
        ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // attach computed capability flags
            foreach ($rows as &$r) {
                $status = strtoupper($r['VoteSessionStatus'] ?? '');
                $r['VisibleToVoters'] = $this->isVisibleToVoters($status);
                $r['VotingAllowed'] = $this->isVotingAllowed($status);
                $r['Editable'] = $this->isEditable($status);
                $r['Deletable'] = $this->isDeletable($status);
            }
            return $rows;
        } catch (PDOException $e) {
            error_log('listForAdmin (VoteSession) error: ' . $e->getMessage());
            return [];
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT vs.*,
                       e.title AS ElectionTitle,
                       e.electionStartDate AS electionStartDate,
                       e.electionEndDate AS electionEndDate
                  FROM votesession vs
                  JOIN electionevent e ON e.electionID = vs.electionID
                 WHERE vs.voteSessionID = ?
                 LIMIT 1
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getById (VoteSession) error: ' . $e->getMessage());
            return null;
        }
    }

    private function isVisibleToVoters(string $status): bool
    {
        return in_array($status, ['OPEN', 'CLOSED'], true);
    }
    private function isVotingAllowed(string $status): bool
    {
        // Only OPEN
        return $status === 'OPEN';
    }
    private function isEditable(string $status): bool
    {
        // Only DRAFT is truly editable
        return $status === 'DRAFT';
    }

    private function isDeletable(string $status): bool
    {
        // Only DRAFT can be hard deleted
        return $status === 'DRAFT';
    }

    public function updateStatus(int $voteSessionId, string $newStatus): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE votesession
               SET voteSessionStatus = :status
             WHERE voteSessionID = :id
        ");
            return $stmt->execute([
                ':status' => $newStatus,
                ':id' => $voteSessionId,
            ]);
        } catch (PDOException $e) {
            error_log('updateStatus (VoteSession) error: ' . $e->getMessage());
            return false;
        }
    }

    public function listElectionsForSession(): array
    {
        try {
            $st = $this->db->query("
            SELECT electionID, title, electionStartDate AS start, electionEndDate AS end, status
            FROM electionevent
            WHERE status = 'ONGOING'
            ORDER BY electionStartDate DESC
        ");
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    public function listFaculties(): array
    {
        try {
            $st = $this->db->query("SELECT facultyID, facultyName FROM faculty ORDER BY facultyName");
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getElectionWindow(int $electionID): ?array
    {
        $st = $this->db->prepare("SELECT electionStartDate AS start, electionEndDate AS end FROM electionevent WHERE electionID=?");
        $st->execute([$electionID]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function overlapsExisting(int $electionID, ?int $excludeId, string $start, string $end): bool
    {
        $sql = "
      SELECT 1
      FROM votesession
      WHERE electionID = ?
        AND voteSessionStatus IN ('SCHEDULED','OPEN')
        AND (? IS NULL OR voteSessionID <> ?)
        AND NOT( ? <= voteSessionStartAt OR ? >= voteSessionEndAt )
      LIMIT 1
    ";
        $st = $this->db->prepare($sql);
        $st->execute([$electionID, $excludeId, $excludeId, $end, $start]);
        return (bool) $st->fetchColumn();
    }

    public function insertVoteSession(array $d): ?int
    {
        try {
            $st = $this->db->prepare("
          INSERT INTO votesession
            (voteSessionName, voteSessionType, voteSessionStartAt, voteSessionEndAt, voteSessionStatus, electionID)
          VALUES (?,?,?,?,?,?)
        ");
            $st->execute([$d['name'], $d['type'], $d['startAt'], $d['endAt'], $d['status'], $d['electionID']]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log('insertVoteSession: ' . $e->getMessage());
            return null;
        }
    }

    public function insertRace(array $data): ?int
    {
        try {
            $stmt = $this->db->prepare("
            INSERT INTO race (
                raceTitle,
                seatType,
                seatCount,
                maxSelectable,
                electionID,
                facultyID,
                voteSessionID
            )
            VALUES (
                :title,
                :seatType,
                :seatCount,
                :maxSelectable,
                :electionID,
                :facultyID,
                :voteSessionID
            )
        ");

            $stmt->execute([
                ':title' => $data['title'],
                ':seatType' => $data['seatType'],
                ':seatCount' => $data['seatCount'],
                ':maxSelectable' => $data['maxSelectable'],
                ':electionID' => $data['electionID'],
                ':facultyID' => $data['facultyID'],
                ':voteSessionID' => $data['voteSessionID'],
            ]);

            return (int) $this->db->lastInsertId() ?: null;
        } catch (PDOException $e) {
            error_log('insertRace error: ' . $e->getMessage());
            return null;
        }
    }

    // Fetch the races for a given voting session
    public function getRacesBySessionId(int $sessionId): array
    {
        try {
            $stmt = $this->db->prepare("
            SELECT 
                r.raceID,
                r.raceTitle AS title,
                r.seatType AS seatType,
                r.facultyID,
                f.facultyName,
                r.seatCount AS seatCount,
                r.maxSelectable AS maxSelectable
            FROM race r
            LEFT JOIN faculty f ON r.facultyID = f.facultyID
            WHERE r.voteSessionID = :sessionId
        ");
            $stmt->bindParam(':sessionId', $sessionId, PDO::PARAM_INT);
            $stmt->execute();
            $races = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$races) {
                error_log('No races found for session ID ' . $sessionId);
            }

            return $races ?: [];
        } catch (PDOException $e) {
            error_log('Error fetching races for session: ' . $e->getMessage());
            return [];
        }
    }

    public function updateVoteSession(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE votesession
                SET voteSessionName = :sessionName, voteSessionType = :sessionType, 
                    voteSessionStartAt = :startAt, voteSessionEndAt = :endAt, electionID = :electionID
                WHERE voteSessionID = :voteSessionID
            ");
            $stmt->execute($data);
            return true;
        } catch (PDOException $e) {
            error_log('Error updating voting session: ' . $e->getMessage());
            return false;
        }
    }

    public function updateRace(array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
            UPDATE race
            SET raceTitle = :title, seatType = :seatType, facultyID = :facultyID, 
                seatCount = :seatCount, maxSelectable = :maxSelectable
            WHERE raceID = :raceID
        ");
            $stmt->execute($data);
            return true;
        } catch (PDOException $e) {
            error_log('Error updating race: ' . $e->getMessage());
            return false;
        }
    }

    // Delete races for the editing votesession
    public function deleteRacesNotIn(int $voteSessionID, array $keepIds): void
    {
        // If no keepIds, delete all races of this session
        if (empty($keepIds)) {
            $stmt = $this->db->prepare("DELETE FROM race WHERE voteSessionID = :sid");
            $stmt->execute([':sid' => $voteSessionID]);
            return;
        }

        $in = implode(',', array_map('intval', $keepIds));
        $sql = "DELETE FROM race WHERE voteSessionID = :sid AND raceID NOT IN ($in)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sid' => $voteSessionID]);
    }

    // Delete race and votesession securely
    public function deleteSecure(int $voteSessionId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1) Delete child rows that depend on this session (races)
            $stR = $this->db->prepare("DELETE FROM race WHERE voteSessionID = ?");
            $stR->execute([$voteSessionId]);

            // 2) Delete the votesession itself
            $stS = $this->db->prepare("DELETE FROM votesession WHERE voteSessionID = ?");
            $stS->execute([$voteSessionId]);

            $this->db->commit();

            // Ensure something was actually deleted
            return $stS->rowCount() > 0;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('deleteSecure (VoteSession) error: ' . $e->getMessage());
            return false;
        }
    }

    public function listForStudentNominee(): array
    {
        try {
            $sql = "
            SELECT
              vs.voteSessionID        AS VoteSessionID,
              vs.voteSessionName      AS VoteSessionName,
              vs.voteSessionType      AS VoteSessionType,
              vs.voteSessionStartAt   AS StartAt,
              vs.voteSessionEndAt     AS EndAt,
              vs.voteSessionStatus    AS VoteSessionStatus,
              vs.electionID           AS ElectionID,
              COALESCE(e.title, '(Unknown)') AS ElectionTitle,
              e.status                AS ElectionStatus
            FROM votesession vs
            JOIN electionevent e ON e.electionID = vs.electionID
            -- Only sessions relevant to voters
            WHERE vs.voteSessionStatus IN ('OPEN', 'CLOSED')
              AND e.status IN ('ONGOING', 'COMPLETED')
            ORDER BY
              vs.voteSessionStartAt IS NULL,
              vs.voteSessionStartAt ASC,
              vs.voteSessionID DESC
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$r) {
                $status = strtoupper($r['VoteSessionStatus'] ?? '');
                $r['VisibleToVoters'] = $this->isVisibleToVoters($status); // OPEN/CLOSED = true
                $r['VotingAllowed'] = $this->isVotingAllowed($status);   // only OPEN = true
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('listForVoters (VoteSession) error: ' . $e->getMessage());
            return [];
        }
    }
}