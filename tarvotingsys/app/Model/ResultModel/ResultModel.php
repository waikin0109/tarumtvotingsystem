<?php

namespace Model\ResultModel;

use PDO;
use PDOException;
use Database;

class ResultModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getElectionsForStats(bool $includeCompleted = false): array
    {
        try {
            $statusCondition = $includeCompleted
                ? "status IN ('ONGOING', 'COMPLETED')"
                : "status = 'ONGOING'";

            $sql = "
                SELECT electionID, title
                FROM electionevent
                WHERE $statusCondition
                ORDER BY electionStartDate DESC, electionID DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getElectionsForStats error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Vote sessions for a given election.
     *
     * - For LIVE turnout page: call with false → only OPEN sessions.
     * - For final results page: call with true  → OPEN + CLOSED.
     */
    public function getVoteSessionsForElection(int $electionID, bool $includeClosed = false): array
    {
        try {
            $statusCondition = $includeClosed
                ? "voteSessionStatus IN ('OPEN', 'CLOSED')"
                : "voteSessionStatus = 'OPEN'";

            $sql = "
                SELECT
                    voteSessionID,
                    voteSessionName,
                    voteSessionType,
                    voteSessionStatus,
                    voteSessionStartAt,
                    voteSessionEndAt
                FROM votesession
                WHERE electionID = :eid
                  AND $statusCondition
                ORDER BY voteSessionStartAt ASC, voteSessionID ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':eid' => $electionID]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getVoteSessionsForElection error: ' . $e->getMessage());
            return [];
        }
    }

    /** Races for a given vote session. */
    public function getRacesForSession(int $voteSessionID): array
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
                f.facultyName
            FROM votesession_race vsr
            INNER JOIN race r
                ON r.raceID = vsr.raceID
            LEFT JOIN faculty f
                ON f.facultyID = r.facultyID
            WHERE vsr.voteSessionID = :sid
            ORDER BY
                CASE r.seatType WHEN 'FACULTY_REP' THEN 1 ELSE 2 END,
                r.raceTitle
        ";
            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getRacesForSession error: ' . $e->getMessage());
            return [];
        }
    }

    public function getRaceVoteBreakdown(int $voteSessionID, int $raceID): array
    {
        try {
            $sql = "
            SELECT
                n.nomineeID,
                a.fullName,
                n.manifesto,
                COUNT(bs.selectionID) AS votes
            FROM votesession vs
            INNER JOIN votesession_race vsr
                ON vsr.voteSessionID = vs.voteSessionID
            INNER JOIN race r
                ON r.raceID = vsr.raceID
            INNER JOIN nominee n
                ON n.raceID     = r.raceID
               AND n.electionID = vs.electionID
            LEFT JOIN ballotselection bs
                ON bs.raceID    = r.raceID
               AND bs.nomineeID = n.nomineeID
            LEFT JOIN ballot b
                ON b.ballotID      = bs.ballotID
               AND b.voteSessionID = vs.voteSessionID
            LEFT JOIN account a
                ON a.accountID = n.accountID
            WHERE vs.voteSessionID = :sid
              AND r.raceID        = :rid
            GROUP BY
                n.nomineeID,
                a.fullName,
                n.manifesto
            ORDER BY
                votes DESC,
                a.fullName ASC
        ";

            $st = $this->db->prepare($sql);
            $st->execute([
                ':sid' => $voteSessionID,
                ':rid' => $raceID,
            ]);

            return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getRaceVoteBreakdown error: ' . $e->getMessage());
            return [];
        }
    }

    /* ------------------------------ TURNOUT STATS ------------------------------ */
    /**
     * Turnout statistics for a vote session + race.
     *
     * - For FACULTY_REP: count only accounts in that faculty.
     * - For campus-wide (other seatType): count all accounts.
     * - Roles counted as eligible: ADMIN, NOMINEE, STUDENT.
     */
    public function getTurnoutStats(
        int $voteSessionID,
        ?int $facultyID,
        string $seatType
    ): ?array {
        try {
            $seatType = strtoupper($seatType);

            if ($seatType === 'FACULTY_REP' && $facultyID !== null) {
                // Eligible: all accounts in this faculty
                $sqlEligible = "
                    SELECT COUNT(*)
                    FROM account
                    WHERE role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                      AND facultyID = :fid
                ";
                $st = $this->db->prepare($sqlEligible);
                $st->execute([':fid' => $facultyID]);
                $eligible = (int) $st->fetchColumn();

                // Submitted: envelopes from those accounts
                $sqlSubmitted = "
                    SELECT COUNT(*)
                    FROM ballotenvelope be
                    JOIN account acc ON acc.accountID = be.accountID
                    WHERE be.voteSessionID = :sid
                      AND be.ballotEnvelopeStatus = 'SUBMITTED'
                      AND acc.role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                      AND acc.facultyID = :fid
                ";
                $st = $this->db->prepare($sqlSubmitted);
                $st->execute([
                    ':sid' => $voteSessionID,
                    ':fid' => $facultyID,
                ]);
                $submitted = (int) $st->fetchColumn();
            } else {
                // Campus-wide or non-faculty seat
                $sqlEligible = "
                    SELECT COUNT(*)
                    FROM account
                    WHERE role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                ";
                $eligible = (int) $this->db->query($sqlEligible)->fetchColumn();

                $sqlSubmitted = "
                    SELECT COUNT(*)
                    FROM ballotenvelope be
                    JOIN account acc ON acc.accountID = be.accountID
                    WHERE be.voteSessionID = :sid
                      AND be.ballotEnvelopeStatus = 'SUBMITTED'
                      AND acc.role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                ";
                $st = $this->db->prepare($sqlSubmitted);
                $st->execute([':sid' => $voteSessionID]);
                $submitted = (int) $st->fetchColumn();
            }

            $turnoutPercent = ($eligible > 0)
                ? round(($submitted / $eligible) * 100, 2)
                : 0.0;

            return [
                'eligible' => $eligible,
                'ballotsSubmitted' => $submitted,
                'turnoutPercent' => $turnoutPercent,
            ];
        } catch (PDOException $e) {
            error_log('ResultModel::getTurnoutStats error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Turnout by faculty for a vote session (for the Chart.js bar chart).
     * Counts ADMIN, NOMINEE, STUDENT accounts in each faculty.
     */
    public function getTurnoutByFacultyForSession(int $voteSessionID): array
    {
        try {
            $sql = "
                SELECT
                    f.facultyID,
                    f.facultyName,
                    COUNT(DISTINCT acc.accountID) AS eligible,
                    COUNT(DISTINCT CASE
                        WHEN be.ballotEnvelopeStatus = 'SUBMITTED'
                        THEN be.ballotEnvelopeID
                    END) AS voted
                FROM faculty f
                LEFT JOIN account acc
                  ON acc.facultyID = f.facultyID
                 AND acc.role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                LEFT JOIN ballotenvelope be
                  ON be.accountID = acc.accountID
                 AND be.voteSessionID = :sid
                GROUP BY f.facultyID, f.facultyName
                ORDER BY f.facultyName
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$row) {
                $eligible = (int) ($row['eligible'] ?? 0);
                $voted = (int) ($row['voted'] ?? 0);

                $row['eligible'] = $eligible;
                $row['voted'] = $voted;
                $row['turnoutPercent'] = $eligible > 0
                    ? round(($voted / $eligible) * 100, 2)
                    : 0.0;
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('ResultModel::getTurnoutByFacultyForSession error: ' . $e->getMessage());
            return [];
        }
    }

    /* ------------------------ LAST UPDATED TIMESTAMP (LIVE) -------------------- */

    public function getLastBallotSubmittedAt(int $voteSessionID): ?string
    {
        try {
            $sql = "
                SELECT MAX(ballotCreatedAt)
                FROM ballot
                WHERE voteSessionID = :sid
                  AND ballotStatus = 'SUBMITTED'
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);
            $val = $st->fetchColumn();
            return $val ?: null;
        } catch (PDOException $e) {
            error_log('ResultModel::getLastBallotSubmittedAt error: ' . $e->getMessage());
            return null;
        }
    }

    public function getOverallTurnoutForSession(int $voteSessionID): array
    {
        try {
            // Eligible voters (campus-wide)
            $sqlEligible = "
            SELECT COUNT(*)
            FROM account
            WHERE role IN ('ADMIN', 'NOMINEE', 'STUDENT')
        ";
            $eligible = (int) $this->db->query($sqlEligible)->fetchColumn();

            // Ballots cast = all submitted envelopes
            $sqlSubmittedEnvelopes = "
            SELECT COUNT(*)
            FROM ballotenvelope be
            JOIN account acc ON acc.accountID = be.accountID
            WHERE be.voteSessionID = :sid
              AND be.ballotEnvelopeStatus = 'SUBMITTED'
              AND acc.role IN ('ADMIN', 'NOMINEE', 'STUDENT')
        ";
            $stSub = $this->db->prepare($sqlSubmittedEnvelopes);
            $stSub->execute([':sid' => $voteSessionID]);
            $ballotsCast = (int) $stSub->fetchColumn();

            // For now: all submitted envelopes are treated as valid
            $validBallots = $ballotsCast;

            $turnoutPercent = ($eligible > 0)
                ? round(($ballotsCast / $eligible) * 100, 2)
                : 0.0;

            return [
                'eligible' => $eligible,
                'ballotsCast' => $ballotsCast,
                'turnoutPercent' => $turnoutPercent,
                'validBallots' => $validBallots,
            ];
        } catch (PDOException $e) {
            error_log('ResultModel::getOverallTurnoutForSession error: ' . $e->getMessage());
            return [
                'eligible' => 0,
                'ballotsCast' => 0,
                'turnoutPercent' => 0.0,
                'validBallots' => 0,
            ];
        }
    }

    /**
     * Check if results already exist for a session.
     * This prevents duplicate inserts when helper is called multiple times.
     */
    public function hasResultsForSession(int $voteSessionID): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM result WHERE voteSessionID = :sid";
            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);
            return (int) $st->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('ResultModel::hasResultsForSession error: ' . $e->getMessage());
            return false;
        }
    }

    public function generateFinalResultsForSession(int $voteSessionID): int
    {
        try {
            // Do nothing if results already exist (protect from double-run).
            if ($this->hasResultsForSession($voteSessionID)) {
                return 0;
            }

            $this->db->beginTransaction();

            $sql = "
            INSERT INTO result (
                countTotal,
                resultStatus,
                voteSessionID,
                raceID,
                nomineeID
            )
            SELECT
                COUNT(bs.selectionID) AS countTotal,
                'FINAL'               AS resultStatus,
                vs.voteSessionID      AS voteSessionID,
                r.raceID              AS raceID,
                n.nomineeID           AS nomineeID
            FROM votesession vs
            JOIN votesession_race vsr
                ON vsr.voteSessionID = vs.voteSessionID
            JOIN race r
                ON r.raceID = vsr.raceID
            JOIN nominee n
                ON n.raceID     = r.raceID
               AND n.electionID = vs.electionID

            -- 1) limit ballots to THIS session
            LEFT JOIN ballot b
                ON b.voteSessionID = vs.voteSessionID

            -- 2) then only selections belonging to those ballots
            LEFT JOIN ballotselection bs
                ON bs.ballotID  = b.ballotID
               AND bs.raceID    = r.raceID
               AND bs.nomineeID = n.nomineeID

            WHERE vs.voteSessionID = :sid
            GROUP BY
                vs.voteSessionID,
                r.raceID,
                n.nomineeID
        ";

            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);

            $inserted = $st->rowCount();

            $this->db->commit();
            return $inserted;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('ResultModel::generateFinalResultsForSession error: ' . $e->getMessage());
            return 0;
        }
    }

}