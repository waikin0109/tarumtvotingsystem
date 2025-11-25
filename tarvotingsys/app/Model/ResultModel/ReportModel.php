<?php

namespace Model\ResultModel;

use PDO;
use PDOException;
use Database;

class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Completed elections only (status = 'COMPLETED')
    public function getCompletedElections(): array
    {
        $sql = "
            SELECT electionID, title
            FROM electionevent
            WHERE status = 'COMPLETED'
            ORDER BY electionStartDate DESC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // All CLOSED vote sessions, but only for COMPLETED elections
    public function getClosedVoteSessionsForCompletedElections(): array
    {
        $sql = "
            SELECT
                vs.voteSessionID,
                vs.voteSessionName,
                vs.voteSessionType,
                vs.voteSessionStatus,
                vs.electionID
            FROM votesession vs
            INNER JOIN electionevent ee ON ee.electionID = vs.electionID
            WHERE ee.status = 'COMPLETED'
              AND vs.voteSessionStatus = 'CLOSED'
            ORDER BY ee.electionStartDate, vs.voteSessionStartAt
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getElectionById(int $electionID): ?array
    {
        $stmt = $this->db->prepare("
            SELECT electionID, title, electionStartDate, electionEndDate, status
            FROM electionevent
            WHERE electionID = :id
        ");
        $stmt->execute([':id' => $electionID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Races for closed elections (with electionID + voteSessionID)
    public function getAllRaces(): array
    {
        $sql = "
        SELECT
            r.raceID,
            r.raceTitle AS raceName,
            r.seatType,
            r.seatCount,
            r.electionID,
            vsr.voteSessionID
        FROM votesession_race vsr
        INNER JOIN race r
            ON r.raceID = vsr.raceID
        INNER JOIN electionevent ee
            ON ee.electionID = r.electionID
        WHERE ee.status = 'COMPLETED'
        ORDER BY r.electionID, r.raceTitle
    ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Check that a voteSessionID belongs to this election (or null)
    public function isVoteSessionInElection(int $voteSessionID, int $electionID): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM votesession
            WHERE voteSessionID = :vs AND electionID = :el
        ");
        $stmt->execute([':vs' => $voteSessionID, ':el' => $electionID]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Check that race belongs to election and (optionally) to that session
    public function isRaceInElectionAndSession(int $raceID, int $electionID, ?int $voteSessionID): bool
    {
        // Case 1: no session specified – just check race belongs to election
        if (empty($voteSessionID)) {
            $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM race
            WHERE raceID = :race
              AND electionID = :el
        ");
            $stmt->execute([
                ':race' => $raceID,
                ':el' => $electionID,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        }

        // Case 2: session specified – check via votesession_race + votesession
        $stmt = $this->db->prepare("
        SELECT COUNT(*)
        FROM votesession_race vsr
        INNER JOIN race r
            ON r.raceID = vsr.raceID
        INNER JOIN votesession vs
            ON vs.voteSessionID = vsr.voteSessionID
        WHERE r.raceID      = :race
          AND vs.voteSessionID = :vs
          AND vs.electionID = :el
    ");
        $stmt->execute([
            ':race' => $raceID,
            ':vs' => $voteSessionID,
            ':el' => $electionID,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /* =========================================================
     *  OVERALL TURNOUT SUMMARY
     * ======================================================= */
    /**
     * @param int      $electionID
     * @param int|null $voteSessionID specific session or null = all
     */
    public function getOverallTurnoutSummary(
        int $electionID,
        ?int $voteSessionID = null,
        ?string $seatType = null,
        ?int $facultyID = null
    ): ?array {
        $election = $this->getElectionById($electionID);
        if (!$election) {
            return null;
        }

        // ----------------- 1) Eligible voters -----------------
        $seatTypeNorm = $seatType ? strtoupper($seatType) : null;

        if ($seatTypeNorm === 'FACULTY_REP' && $facultyID !== null) {
            // Faculty rep: only that faculty, but all ADMIN / NOMINEE / STUDENT
            $eligibleSql = "
                SELECT COUNT(*) AS eligibleTotal
                FROM account
                WHERE role IN ('ADMIN','NOMINEE','STUDENT')
                  AND facultyID = :fid
            ";
            $stElig = $this->db->prepare($eligibleSql);
            $stElig->execute([':fid' => $facultyID]);
            $eligibleTotal = (int) $stElig->fetchColumn();
        } else {
            // Campus-wide: all faculties, all ADMIN / NOMINEE / STUDENT
            $eligibleSql = "
                SELECT COUNT(*) AS eligibleTotal
                FROM account
                WHERE role IN ('ADMIN','NOMINEE','STUDENT')
            ";
            $eligibleTotal = (int) $this->db->query($eligibleSql)->fetchColumn();
        }

        // ----------------- 2) Ballots cast -----------------
        $params = [':electionID' => $electionID];

        $ballotSql = "
            SELECT COUNT(*) AS ballotsCast
            FROM ballotenvelope be
            INNER JOIN votesession vs ON vs.voteSessionID = be.voteSessionID
            INNER JOIN account acc    ON acc.accountID    = be.accountID
            WHERE vs.electionID = :electionID
              AND be.ballotEnvelopeStatus = 'SUBMITTED'
              AND acc.role IN ('ADMIN','NOMINEE','STUDENT')
        ";

        // If we are in faculty-rep mode, restrict ballots to that faculty too
        if ($seatTypeNorm === 'FACULTY_REP' && $facultyID !== null) {
            $ballotSql .= " AND acc.facultyID = :fid";
            $params[':fid'] = $facultyID;
        }

        // Optionally filter to one specific session
        if (!empty($voteSessionID)) {
            $ballotSql .= " AND vs.voteSessionID = :voteSessionID";
            $params[':voteSessionID'] = $voteSessionID;
        }

        $stBallot = $this->db->prepare($ballotSql);
        $stBallot->execute($params);
        $ballotsCast = (int) $stBallot->fetchColumn();

        $turnoutPercent = $eligibleTotal > 0
            ? round(($ballotsCast / $eligibleTotal) * 100, 2)
            : 0.0;

        return [
            'electionID' => (int) $election['electionID'],
            'electionTitle' => $election['title'],
            'electionStart' => $election['electionStartDate'],
            'electionEnd' => $election['electionEndDate'],
            'electionStatus' => $election['status'],
            'voteSessionID' => $voteSessionID,
            'seatType' => $seatTypeNorm,   // null or FACULTY_REP
            'facultyID' => $facultyID,      // null for campus-wide
            'eligibleTotal' => $eligibleTotal,
            'ballotsCast' => $ballotsCast,
            'turnoutPercent' => $turnoutPercent,
        ];
    }

    public function getTurnoutByFaculty(int $electionID, ?int $voteSessionID = null): array
    {
        // 1) Eligible per faculty
        $eligSql = "
            SELECT
                f.facultyID,
                f.facultyCode,
                f.facultyName,
                COUNT(a.accountID) AS eligible
            FROM faculty f
            LEFT JOIN account a
              ON a.facultyID = f.facultyID
             AND a.role IN ('ADMIN','NOMINEE','STUDENT')
            GROUP BY f.facultyID, f.facultyCode, f.facultyName
            ORDER BY f.facultyCode
        ";
        $eligRows = $this->db->query($eligSql)->fetchAll(PDO::FETCH_ASSOC);

        $byFaculty = [];
        foreach ($eligRows as $row) {
            $fid = (int) $row['facultyID'];
            $byFaculty[$fid] = [
                'facultyID' => $fid,
                'facultyCode' => $row['facultyCode'],
                'facultyName' => $row['facultyName'],
                'eligible' => (int) $row['eligible'],
                'ballotsCast' => 0,
                'earlyCast' => 0,
                'mainCast' => 0,
                'turnoutPercent' => 0.0,
                'earlyPercent' => 0.0,
                'mainPercent' => 0.0,
            ];
        }

        // 2) Ballots by faculty + EARLY/MAIN
        $params = [':electionID' => $electionID];
        $ballotSql = "
            SELECT
                acc.facultyID,
                SUM(CASE WHEN vs.voteSessionType = 'EARLY' THEN 1 ELSE 0 END) AS earlyCast,
                SUM(CASE WHEN vs.voteSessionType = 'MAIN'  THEN 1 ELSE 0 END) AS mainCast,
                COUNT(*) AS ballotsCast
            FROM ballotenvelope be
            INNER JOIN votesession vs ON vs.voteSessionID = be.voteSessionID
            INNER JOIN account acc    ON acc.accountID    = be.accountID
            WHERE vs.electionID = :electionID
              AND be.ballotEnvelopeStatus = 'SUBMITTED'
              AND acc.role IN ('ADMIN','NOMINEE','STUDENT')
        ";

        if (!empty($voteSessionID)) {
            $ballotSql .= " AND vs.voteSessionID = :voteSessionID";
            $params[':voteSessionID'] = $voteSessionID;
        }

        $ballotSql .= " GROUP BY acc.facultyID";

        $st = $this->db->prepare($ballotSql);
        $st->execute($params);
        $ballotRows = $st->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ballotRows as $row) {
            $fid = (int) $row['facultyID'];
            if (!isset($byFaculty[$fid])) {
                continue;
            }
            $earlyCast = (int) $row['earlyCast'];
            $mainCast = (int) $row['mainCast'];
            $ballots = (int) $row['ballotsCast'];
            $eligible = max(0, (int) $byFaculty[$fid]['eligible']);

            $byFaculty[$fid]['earlyCast'] = $earlyCast;
            $byFaculty[$fid]['mainCast'] = $mainCast;
            $byFaculty[$fid]['ballotsCast'] = $ballots;

            if ($eligible > 0) {
                $byFaculty[$fid]['turnoutPercent'] = round(($ballots / $eligible) * 100, 2);
                $byFaculty[$fid]['earlyPercent'] = round(($earlyCast / $eligible) * 100, 2);
                $byFaculty[$fid]['mainPercent'] = round(($mainCast / $eligible) * 100, 2);
            }
        }

        // Return only faculties where there is at least 1 eligible or 1 cast, sorted
        return array_values(array_filter($byFaculty, function ($row) {
            return $row['eligible'] > 0 || $row['ballotsCast'] > 0;
        }));
    }

    public function getTurnoutTimeline(int $electionID, ?int $voteSessionID = null): array
    {
        $params = [':electionID' => $electionID];

        $sql = "
            SELECT
                DATE_FORMAT(be.ballotEnvelopeSubmittedAt, '%Y-%m-%d %H:00') AS timeSlot,
                COUNT(*) AS ballotsCast
            FROM ballotenvelope be
            INNER JOIN votesession vs ON vs.voteSessionID = be.voteSessionID
            WHERE vs.electionID = :electionID
              AND be.ballotEnvelopeStatus = 'SUBMITTED'
        ";

        if (!empty($voteSessionID)) {
            $sql .= " AND vs.voteSessionID = :voteSessionID";
            $params[':voteSessionID'] = $voteSessionID;
        }

        $sql .= "
            GROUP BY timeSlot
            ORDER BY timeSlot
        ";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     *  OFFICIAL RESULTS BY RACE (ALL RACES)
     * ======================================================= */

    public function getOfficialResultsAll(int $electionID, ?int $voteSessionID = null): array
    {
        $params = [':electionID' => $electionID];

        $sql = "
            SELECT
                r.raceID,
                r.raceTitle,
                r.seatType,
                r.seatCount,
                vs.voteSessionID,
                vs.voteSessionName,
                vs.voteSessionType,
                res.countTotal,
                res.resultStatus,
                n.nomineeID,
                a.fullName,
                f.facultyCode,
                f.facultyName
            FROM result res
            INNER JOIN race r         ON r.raceID = res.raceID
            INNER JOIN votesession vs ON vs.voteSessionID = res.voteSessionID
            INNER JOIN nominee n      ON n.nomineeID = res.nomineeID
            INNER JOIN account a      ON a.accountID = n.accountID
            LEFT  JOIN faculty f      ON f.facultyID = a.facultyID
            WHERE vs.electionID = :electionID
              AND res.resultStatus IN ('FINAL','PUBLISHED')
        ";

        if (!empty($voteSessionID)) {
            $sql .= " AND vs.voteSessionID = :voteSessionID";
            $params[':voteSessionID'] = $voteSessionID;
        }

        $sql .= "
            ORDER BY r.raceID, res.countTotal DESC, a.fullName
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byRace = [];
        foreach ($rows as $row) {
            $raceID = (int) $row['raceID'];

            if (!isset($byRace[$raceID])) {
                $byRace[$raceID] = [
                    'raceID' => $raceID,
                    'raceTitle' => $row['raceTitle'],
                    'seatType' => $row['seatType'],
                    'seatCount' => (int) $row['seatCount'],
                    'voteSessionID' => (int) $row['voteSessionID'],
                    'voteSessionName' => $row['voteSessionName'],
                    'voteSessionType' => $row['voteSessionType'],
                    'candidates' => [],
                ];
            }

            $byRace[$raceID]['candidates'][] = [
                'nomineeID' => (int) $row['nomineeID'],
                'fullName' => $row['fullName'],
                'facultyCode' => $row['facultyCode'],
                'facultyName' => $row['facultyName'],
                'votes' => (int) $row['countTotal'],
                'isWinner' => false,
            ];
        }

        foreach ($byRace as &$race) {
            $seatCount = $race['seatCount'];
            $i = 0;
            foreach ($race['candidates'] as &$cand) {
                if ($i < $seatCount) {
                    $cand['isWinner'] = true;
                }
                $i++;
            }
            unset($cand);
        }
        unset($race);

        return array_values($byRace);
    }

    /* =========================================================
     *  RESULTS BY FACULTY / CAMPUS
     * ======================================================= */

    public function getResultsByFaculty(int $electionID, int $raceID): array
    {
        $stmt = $this->db->prepare("
            SELECT
                f.facultyCode,
                f.facultyName,
                r.raceID,
                r.raceTitle,
                r.seatType,
                r.seatCount,
                n.nomineeID,
                a.fullName,
                res.countTotal AS votes
            FROM result res
            INNER JOIN race r         ON r.raceID = res.raceID
            INNER JOIN votesession vs ON vs.voteSessionID = res.voteSessionID
            INNER JOIN nominee n      ON n.nomineeID = res.nomineeID
            INNER JOIN account a      ON a.accountID = n.accountID
            LEFT  JOIN faculty f      ON f.facultyID = a.facultyID
            WHERE vs.electionID = :electionID
              AND r.raceID      = :raceID
              AND res.resultStatus IN ('FINAL','PUBLISHED')
            ORDER BY f.facultyCode, res.countTotal DESC, a.fullName
        ");
        $stmt->execute([
            ':electionID' => $electionID,
            ':raceID' => $raceID,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
     *  REPORT LOGGING (table `report`)
     * ======================================================= */

    public function mapGeneratorTypeToEnum(string $generatorType): string
    {
        switch ($generatorType) {
            case 'overall_turnout':
                return 'TURNOUT';
            case 'official_results_all':
                return 'RESULTS_SUMMARY';
            case 'results_by_faculty':
                return 'RACE_BREAKDOWN';
            case 'early_vote_status':
                return 'EARLY_VOTE_STATUS';
            default:
                return 'TURNOUT';
        }
    }

    public function insertReportRecord(
        int $electionID,
        string $reportName,
        string $reportTypeEnum,
        string $reportUrl
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO report
                (reportName, reportType, reportUrl, reportGeneratedAt, electionID)
            VALUES
                (:name, :type, :url, NOW(), :electionID)
        ");
        $stmt->execute([
            ':name' => $reportName,
            ':type' => $reportTypeEnum,
            ':url' => $reportUrl,
            ':electionID' => $electionID,
        ]);

        return (int) $this->db->lastInsertId();
    }

    // List all generated reports with their election titles
    public function getAllReports(): array
    {
        try {
            $sql = "
                SELECT
                    r.reportID,
                    r.reportName,
                    r.reportType,
                    r.reportUrl,
                    r.reportGeneratedAt,
                    r.electionID,
                    ee.title AS electionTitle
                FROM report r
                LEFT JOIN electionevent ee
                       ON ee.electionID = r.electionID
                ORDER BY r.reportGeneratedAt DESC, r.reportID DESC
            ";

            $st = $this->db->prepare($sql);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('ReportModel::getAllReports error: ' . $e->getMessage());
            return [];
        }
    }

    // Delete a report record (and optionally you can also delete the file on disk in controller).
    public function deleteReportById(int $reportID): bool
    {
        try {
            $st = $this->db->prepare("DELETE FROM report WHERE reportID = :id");
            return $st->execute([':id' => $reportID]);
        } catch (PDOException $e) {
            error_log('ReportModel::deleteReportById error: ' . $e->getMessage());
            return false;
        }
    }

}