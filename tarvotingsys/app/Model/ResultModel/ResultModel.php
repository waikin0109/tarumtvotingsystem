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


      /* ------------------------ ELECTION / SESSION / RACE ------------------------ */

    /** Elections that can appear in statistics (ongoing or completed). */
    public function getElectionsForStats(): array
    {
        try {
            $sql = "
                SELECT electionID, title
                FROM electionevent
                WHERE status IN ('ONGOING', 'COMPLETED')
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

    /** Vote sessions for a given election (only OPEN or CLOSED for stats). */
    public function getVoteSessionsForElection(int $electionID): array
    {
        try {
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
                  AND voteSessionStatus IN ('OPEN', 'CLOSED')
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
                    r.facultyID,
                    f.facultyName
                FROM race r
                LEFT JOIN faculty f ON f.facultyID = r.facultyID
                WHERE r.voteSessionID = :sid
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

    /* ----------------------------- RACE STATISTICS ----------------------------- */

    /**
     * Votes by nominee for a race in a given vote session.
     */
    public function getRaceVoteBreakdown(int $voteSessionID, int $raceID): array
    {
        try {
            $sql = "
                SELECT
                    n.nomineeID,
                    acc.fullName,
                    COALESCE(COUNT(bs.selectionID), 0) AS votes
                FROM race r
                JOIN nominee n
                  ON n.raceID = r.raceID
                JOIN account acc
                  ON acc.accountID = n.accountID
                LEFT JOIN ballotselection bs
                  ON bs.raceID     = r.raceID
                 AND bs.nomineeID = n.nomineeID
                WHERE r.voteSessionID = :sid
                  AND r.raceID        = :rid
                GROUP BY
                    n.nomineeID,
                    acc.fullName
                ORDER BY votes DESC, acc.fullName ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute([
                ':sid' => $voteSessionID,
                ':rid' => $raceID,
            ]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getRaceVoteBreakdown error: ' . $e->getMessage());
            return [];
        }
    }

    /* ------------------------------ TURNOUT STATS ------------------------------ */

    /**
     * Turnout statistics for a vote session + race.
     *
     * - For FACULTY_REP: count only STUDENT accounts in that faculty.
     * - For campus-wide (other seatType): count all STUDENT accounts.
     *
     * Assumes:
     *   account(role, facultyID)
     *   ballotenvelope(voteSessionID, accountID, ballotEnvelopeStatus)
     */
    public function getTurnoutStats(
        int $voteSessionID,
        ?int $facultyID,
        string $seatType
    ): ?array {
        try {
            $seatType = strtoupper($seatType);

            if ($seatType === 'FACULTY_REP' && $facultyID !== null) {
                // Eligible: all students in this faculty
                $sqlEligible = "
                    SELECT COUNT(*)
                    FROM account
                    WHERE role IN ('ADMIN', 'NOMINEE', 'STUDENT')
                      AND facultyID = :fid
                ";
                $st = $this->db->prepare($sqlEligible);
                $st->execute([':fid' => $facultyID]);
                $eligible = (int) $st->fetchColumn();

                // Submitted: ballots from students in this faculty
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
                // Campus-wide or any non-faculty seat: all students across faculties
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
                'eligible'         => $eligible,
                'ballotsSubmitted' => $submitted,
                'turnoutPercent'   => $turnoutPercent,
            ];
        } catch (PDOException $e) {
            error_log('ResultModel::getTurnoutStats error: ' . $e->getMessage());
            return null;
        }
    }

    /* ----------------------- BALLOTS OVER TIME (TIMELINE) ---------------------- */

    public function getBallotsSubmittedOverTime(int $voteSessionID): array
    {
        try {
            $sql = "
                SELECT
                    DATE_FORMAT(ballotCreatedAt, '%Y-%m-%d %H:00:00') AS bucket,
                    COUNT(*) AS count
                FROM ballot
                WHERE voteSessionID = :sid
                  AND ballotStatus = 'SUBMITTED'
                GROUP BY bucket
                ORDER BY bucket ASC
            ";
            $st = $this->db->prepare($sql);
            $st->execute([':sid' => $voteSessionID]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ResultModel::getBallotsSubmittedOverTime error: ' . $e->getMessage());
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
}