<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;
use Library\SimplePager;

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

    public function getPagedVoteSessionsForAdmin(int $page, int $limit = 10, string $search = ''): SimplePager
    {
        try {
            // Base list query (same columns as listForAdmin)
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
            WHERE 1
        ";

            $params = [];

            if ($search !== '') {
                // Search by session name only
                $sql .= " AND vs.voteSessionName LIKE :q";
                $params[':q'] = '%' . $search . '%';
            }

            $sql .= "
            ORDER BY
              vs.voteSessionStartAt IS NULL,
              vs.voteSessionStartAt ASC,
              vs.voteSessionID DESC
        ";

            // Fetch current page
            $pager = new SimplePager($this->db, $sql, $params, $limit, $page);

            $rows = $pager->result ?? [];
            foreach ($rows as &$r) {
                $status = strtoupper($r['VoteSessionStatus'] ?? '');
                $r['VisibleToVoters'] = $this->isVisibleToVoters($status);
                $r['VotingAllowed'] = $this->isVotingAllowed($status);
                $r['Editable'] = $this->isEditable($status);
                $r['Deletable'] = $this->isDeletable($status);
            }
            unset($r);
            $pager->result = $rows;

            $countSql = "
            SELECT COUNT(*) AS cnt
            FROM votesession vs
            LEFT JOIN electionevent e ON e.electionID = vs.electionID
            WHERE 1
        ";
            $countParams = [];

            if ($search !== '') {
                $countSql .= " AND vs.voteSessionName LIKE :q";
                $countParams[':q'] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($countParams);
            $total = (int) $stmt->fetchColumn();

            $pager->item_count = $total;
            $pager->page_count = ($limit > 0) ? (int) ceil($total / $limit) : 1;

            return $pager;
        } catch (PDOException $e) {
            error_log('getPagedVoteSessionsForAdmin error: ' . $e->getMessage());

            // Fallback: empty pager
            $fallbackSql = "
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
            WHERE 1 = 0
        ";
            return new SimplePager($this->db, $fallbackSql, [], $limit, $page);
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
                facultyID
            )
            VALUES (
                :title,
                :seatType,
                :seatCount,
                :maxSelectable,
                :electionID,
                :facultyID
            )
        ");
            $stmt->execute([
                ':title' => $data['title'],
                ':seatType' => $data['seatType'],
                ':seatCount' => $data['seatCount'],
                ':maxSelectable' => $data['maxSelectable'],
                ':electionID' => $data['electionID'],
                ':facultyID' => $data['facultyID'] ?: null,
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
                r.seatType  AS seatType,
                r.facultyID,
                f.facultyName,
                r.seatCount AS seatCount,
                r.maxSelectable AS maxSelectable
            FROM votesession_race vsr
            INNER JOIN race r
                ON r.raceID = vsr.raceID
            LEFT JOIN faculty f
                ON f.facultyID = r.facultyID
            WHERE vsr.voteSessionID = :sessionId
            ORDER BY
                CASE r.seatType WHEN 'FACULTY_REP' THEN 1 ELSE 2 END,
                r.raceTitle
        ");
            $stmt->execute([':sessionId' => $sessionId]);
            $races = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$races) {
                error_log('No races found for session ID ' . $sessionId);
            }

            return $races ?: [];
        } catch (PDOException $e) {
            error_log('Error fetching races for session ID ' . $sessionId . ': ' . $e->getMessage());
            return [];
        }
    }

    public function findOrCreateRace(array $data): ?int
    {
        try {
            // Normalise facultyID ('' or 0 => NULL)
            $facultyID = $data['facultyID'] ?? null;
            if ($facultyID === '' || $facultyID === 0) {
                $facultyID = null;
            }

            // Try to find an existing race in this election with same key fields
            $stmt = $this->db->prepare("
            SELECT raceID
            FROM race
            WHERE electionID = :electionID
              AND seatType   = :seatType
              AND (
                    (facultyID IS NULL AND :facultyID IS NULL)
                    OR facultyID = :facultyID
                  )
            LIMIT 1
        ");
            $stmt->execute([
                ':electionID' => $data['electionID'],
                ':seatType' => $data['seatType'],
                ':facultyID' => $facultyID,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['raceID'])) {
                // Reuse existing election-level race.
                // We deliberately do NOT update raceTitle/seatCount/maxSelectable here,
                // so renaming in one session does not create a new seat.
                return (int) $row['raceID'];
            }

            // Not found, insert a new election-level race
            return $this->insertRace([
                'title' => $data['title'],
                'seatType' => $data['seatType'],
                'seatCount' => $data['seatCount'],
                'maxSelectable' => $data['maxSelectable'],
                'electionID' => $data['electionID'],
                'facultyID' => $facultyID,
            ]);
        } catch (PDOException $e) {
            error_log('findOrCreateRace error: ' . $e->getMessage());
            return null;
        }
    }

    public function addRaceToSession(int $voteSessionID, int $raceID): bool
    {
        try {
            $stmt = $this->db->prepare("
            INSERT IGNORE INTO votesession_race (voteSessionID, raceID)
            VALUES (:sessionID, :raceID)
        ");

            return $stmt->execute([
                ':sessionID' => $voteSessionID,
                ':raceID' => $raceID,
            ]);
        } catch (PDOException $e) {
            error_log('addRaceToSession error: ' . $e->getMessage());
            return false;
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
        try {
            // If no keepIds, remove all links for this session
            if (empty($keepIds)) {
                $stmt = $this->db->prepare("
                DELETE FROM votesession_race
                WHERE voteSessionID = :sid
            ");
                $stmt->execute([':sid' => $voteSessionID]);
                return;
            }

            // Delete only links whose raceID is NOT in $keepIds
            $placeholders = implode(',', array_fill(0, count($keepIds), '?'));

            $sql = "
            DELETE FROM votesession_race
            WHERE voteSessionID = ?
              AND raceID NOT IN ($placeholders)
        ";

            $params = array_merge([$voteSessionID], array_map('intval', $keepIds));

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('deleteRacesNotIn error: ' . $e->getMessage());
        }
    }

    // Delete race and votesession securely
    public function deleteSecure(int $voteSessionId): bool
    {
        try {
            $this->db->beginTransaction();

            // 1) Delete links from pivot table
            $stLink = $this->db->prepare("
            DELETE FROM votesession_race
            WHERE voteSessionID = ?
        ");
            $stLink->execute([$voteSessionId]);

            // 2) Delete the vote session itself
            $stS = $this->db->prepare("
            DELETE FROM votesession
            WHERE voteSessionID = ?
        ");
            $stS->execute([$voteSessionId]);

            $this->db->commit();

            return $stS->rowCount() > 0;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('deleteSecure (VoteSession) error: ' . $e->getMessage());
            return false;
        }
    }

    public function getPagedVoteSessionsForStudentNominee(int $page, int $limit = 10, string $search = ''): SimplePager
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
        ";

            $params = [];

            if ($search !== '') {
                // Search by session name only
                $sql .= " AND vs.voteSessionName LIKE :q";
                $params[':q'] = '%' . $search . '%';
            }

            $sql .= "
            ORDER BY
              vs.voteSessionStartAt IS NULL,
              vs.voteSessionStartAt ASC,
              vs.voteSessionID DESC
        ";

            // Use SimplePager for this query
            $pager = new SimplePager($this->db, $sql, $params, $limit, $page);

            $rows = $pager->result ?? [];
            foreach ($rows as &$r) {
                $status = strtoupper($r['VoteSessionStatus'] ?? '');
                $r['VisibleToVoters'] = $this->isVisibleToVoters($status); // OPEN/CLOSED = true
                $r['VotingAllowed'] = $this->isVotingAllowed($status);   // only OPEN = true
            }
            unset($r);
            $pager->result = $rows;

            // Count for pager footer
            $countSql = "
            SELECT COUNT(*) AS cnt
            FROM votesession vs
            JOIN electionevent e ON e.electionID = vs.electionID
            WHERE vs.voteSessionStatus IN ('OPEN', 'CLOSED')
              AND e.status IN ('ONGOING', 'COMPLETED')
        ";
            $countParams = [];

            if ($search !== '') {
                $countSql .= " AND vs.voteSessionName LIKE :q";
                $countParams[':q'] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($countParams);
            $total = (int) $stmt->fetchColumn();

            $pager->item_count = $total;
            $pager->page_count = ($limit > 0) ? (int) ceil($total / $limit) : 1;

            return $pager;
        } catch (PDOException $e) {
            error_log('getPagedVoteSessionsForStudentNominee error: ' . $e->getMessage());

            $fallbackSql = "
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
            WHERE 1 = 0
        ";

            return new SimplePager($this->db, $fallbackSql, [], $limit, $page);
        }
    }

    public function getAvailableRacesForNominee(int $electionID, int $facultyID): array
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
                e.electionID,
                e.title  AS electionTitle,
                e.status AS electionStatus
            FROM race r
            INNER JOIN electionevent e
                ON e.electionID = r.electionID
            LEFT JOIN faculty f
                ON f.facultyID = r.facultyID
            WHERE r.electionID = :electionID
              AND e.status = 'ONGOING'
              AND (
                    r.seatType = 'CAMPUS_WIDE'
                    OR (r.seatType = 'FACULTY_REP' AND r.facultyID = :facultyID)
                  )
            ORDER BY
                r.seatType,
                r.raceID
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':electionID' => $electionID,
                ':facultyID' => $facultyID,
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // --- DEDUPE: only keep one FACULTY_REP and one CAMPUS_WIDE ---
            $unique = [];

            foreach ($rows as $row) {
                $seatType = strtoupper($row['seatType'] ?? '');

                if ($seatType === 'FACULTY_REP') {
                    // one faculty rep per faculty
                    $key = 'FACULTY_REP_' . (int) ($row['facultyID'] ?? 0);
                } elseif ($seatType === 'CAMPUS_WIDE') {
                    // one campus wide for the whole election
                    $key = 'CAMPUS_WIDE';
                } else {
                    // fallback (in case of other seatTypes in future)
                    $key = $seatType . '_' . (int) ($row['facultyID'] ?? 0);
                }

                if (!isset($unique[$key])) {
                    $unique[$key] = $row;   // keep the first one only
                }
            }

            return array_values($unique);
        } catch (PDOException $e) {
            error_log('getAvailableRacesForNominee error: ' . $e->getMessage());
            return [];
        }
    }

    public function getRaceSetsBySessionTypeForElection(int $electionID): array
    {
        try {
            $sql = "
            SELECT DISTINCT
                vs.voteSessionType AS sessionType,
                r.raceID           AS raceID
            FROM votesession vs
            INNER JOIN votesession_race vsr
                ON vsr.voteSessionID = vs.voteSessionID
            INNER JOIN race r
                ON r.raceID = vsr.raceID
            WHERE vs.electionID = :electionID
              AND vs.voteSessionStatus IN ('DRAFT','SCHEDULED','OPEN','CLOSED')
        ";

            $st = $this->db->prepare($sql);
            $st->execute([':electionID' => $electionID]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $early = [];
            $main = [];

            foreach ($rows as $row) {
                $type = strtoupper($row['sessionType'] ?? '');
                $raceID = (int) ($row['raceID'] ?? 0);
                if ($raceID <= 0) {
                    continue;
                }

                if ($type === 'EARLY') {
                    $early[$raceID] = true;
                } elseif ($type === 'MAIN') {
                    $main[$raceID] = true;
                }
            }

            return [
                'earlyRaceIDs' => array_keys($early),
                'mainRaceIDs' => array_keys($main),
            ];
        } catch (PDOException $e) {
            error_log('getRaceSetsBySessionTypeForElection error: ' . $e->getMessage());
            return [
                'earlyRaceIDs' => [],
                'mainRaceIDs' => [],
            ];
        }
    }

    public function isRaceUsedInActiveSession(int $raceID): bool
    {
        try {
            $sql = "
            SELECT 1
            FROM votesession_race vsr
            JOIN votesession vs 
              ON vs.voteSessionID = vsr.voteSessionID
            WHERE vsr.raceID = :rid
              AND vs.voteSessionStatus IN ('SCHEDULED','OPEN','CLOSED')
            LIMIT 1
        ";
            $st = $this->db->prepare($sql);
            $st->execute([':rid' => $raceID]);
            return (bool) $st->fetchColumn();
        } catch (PDOException $e) {
            error_log('isRaceUsedInActiveSession error: ' . $e->getMessage());
            return false;
        }
    }

    public function getRaceById(int $raceID): ?array
    {
        try {
            $st = $this->db->prepare("
            SELECT raceID, raceTitle, seatType, facultyID, seatCount, maxSelectable
            FROM race
            WHERE raceID = :id
            LIMIT 1
        ");
            $st->execute([':id' => $raceID]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('getRaceById error: ' . $e->getMessage());
            return null;
        }
    }

    public function isRaceLocked(int $raceID): bool
    {
        try {
            $stmt = $this->db->prepare("
            SELECT 1
            FROM votesession_race vsr
            JOIN votesession vs ON vsr.voteSessionID = vs.voteSessionID
            WHERE vsr.raceID = ?
              AND vs.voteSessionStatus IN ('SCHEDULED','OPEN','CLOSED')
            LIMIT 1
        ");
            $stmt->execute([$raceID]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('isRaceLocked error: ' . $e->getMessage());
            return false;
        }
    }

    public function getExistingRaceMeta(int $electionID, string $seatType, ?int $facultyID): ?array
    {
        try {
            $sql = "
            SELECT
                r.raceID,
                r.raceTitle,
                EXISTS (
                    SELECT 1
                    FROM votesession_race vsr
                    INNER JOIN votesession vs
                        ON vs.voteSessionID = vsr.voteSessionID
                    WHERE vsr.raceID = r.raceID
                      AND vs.voteSessionStatus IN ('SCHEDULED','OPEN','CLOSED')
                ) AS inUse
            FROM race r
            WHERE r.electionID = :electionID
              AND r.seatType   = :seatType
              AND (
                    (r.facultyID IS NULL AND :facultyID IS NULL)
                    OR r.facultyID = :facultyID
                  )
            LIMIT 1
        ";

            $st = $this->db->prepare($sql);
            $st->execute([
                ':electionID' => $electionID,
                ':seatType' => $seatType,
                ':facultyID' => $facultyID,
            ]);

            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

            $row['raceID'] = (int) $row['raceID'];
            $row['inUse'] = (bool) $row['inUse'];

            return $row;
        } catch (PDOException $e) {
            error_log('getExistingRaceMeta error: ' . $e->getMessage());
            return null;
        }
    }

}