<?php
namespace Model\CampaignHandlingModel;

use PDO;
use PDOException;
use Database;

class CampaignMaterialModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllCampaignMaterials(): array
    {
        try {
            $sql = "
                SELECT
                    cma.materialsApplicationID,
                    cma.materialsTitle,
                    cma.materialsApplicationStatus,
                    a.fullName,
                    ee.title AS electionEventTitle
                FROM campaignmaterialsapplication AS cma
                INNER JOIN nominee n ON n.nomineeID = cma.nomineeID
                INNER JOIN account a ON a.accountID = n.accountID
                INNER JOIN electionevent ee ON ee.electionID = cma.electionID
                ORDER BY cma.materialsApplicationID DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getAllCampaignMaterials error: " . $e->getMessage());
            return [];
        }
    }

    public function getEligibleElections(): array
    {
        try {
            $sql = "
                SELECT DISTINCT ee.electionID, ee.title
                FROM electionevent ee
                INNER JOIN registrationform rf ON rf.electionID = ee.electionID
                INNER JOIN nomineeapplication na ON na.electionID = ee.electionID
                WHERE rf.registerEndDate < NOW()
                  AND na.applicationStatus = 'PUBLISHED'
                ORDER BY ee.title ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getEligibleElections error: " . $e->getMessage());
            return [];
        }
    }

    public function getEligibleNomineesByElection(int $electionID): array
    {
        try {
            $sql = "
                SELECT DISTINCT
                    n.nomineeID,
                    a.fullName,
                    s.studentID
                FROM nomineeapplication na
                INNER JOIN student s ON s.studentID = na.studentID
                INNER JOIN account a ON a.accountID = s.accountID
                INNER JOIN nominee n ON n.accountID = a.accountID
                WHERE na.electionID = :eid
                  AND na.applicationStatus = 'PUBLISHED'
                ORDER BY a.fullName ASC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':eid', $electionID, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getEligibleNomineesByElection error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert header only, return new materialsApplicationID.
     * $data keys: electionID, nomineeID, materialsTitle, materialsType, materialsDesc, materialsQuantity, adminID?
     */
    public function createCampaignMaterial(array $data): int
    {
        try {
            $this->db->beginTransaction();

            $sql = "
                INSERT INTO campaignmaterialsapplication
                    (materialsTitle, materialsType, materialsDesc, materialsQuantity,
                     materialsApplicationStatus, adminID, nomineeID, electionID)
                VALUES
                    (:title, :type, :desc, :qty, 'PENDING', :adminID, :nomineeID, :electionID)
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':title'      => $data['materialsTitle'],
                ':type'       => $data['materialsType'],
                ':desc'       => $data['materialsDesc'] ?? null,
                ':qty'        => $data['materialsQuantity'],
                ':adminID'    => $data['adminID'] ?? null,
                ':nomineeID'  => $data['nomineeID'],
                ':electionID' => $data['electionID'],
            ]);

            $appID = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $appID;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("createCampaignMaterial error: " . $e->getMessage());
            return 0;
        }
    }

    /** Insert one document row per saved server filename */
    public function insertDocument(int $materialsApplicationID, string $materialsFilename): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO campaignmaterialsdocument (materialsFilename, materialsApplicationID)
                VALUES (:fname, :mid)
            ");
            return $stmt->execute([
                ':fname' => $materialsFilename,
                ':mid'   => $materialsApplicationID,
            ]);
        } catch (\PDOException $e) {
            error_log('insertDocument error: ' . $e->getMessage());
            return false;
        }
    }

    // Header + joined names for display
    public function getCampaignMaterialById(int $id): ?array
    {
        try {
            $sql = "
                SELECT
                    cma.materialsApplicationID,
                    cma.electionID,
                    ee.title AS electionEventTitle,
                    cma.nomineeID,
                    a.fullName AS nomineeFullName,
                    cma.materialsTitle,
                    cma.materialsType,
                    cma.materialsDesc,
                    cma.materialsQuantity,
                    cma.materialsApplicationStatus,
                    cma.adminID
                FROM campaignmaterialsapplication cma
                INNER JOIN electionevent ee ON ee.electionID = cma.electionID
                INNER JOIN nominee n ON n.nomineeID = cma.nomineeID
                INNER JOIN account a ON a.accountID = n.accountID
                WHERE cma.materialsApplicationID = :id
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("getCampaignMaterialById error: " . $e->getMessage());
            return null;
        }
    }

    public function getDocumentsByApplication(int $materialsApplicationID): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT materialsID, materialsFilename, materialsApplicationID
                FROM campaignmaterialsdocument
                WHERE materialsApplicationID = :mid
                ORDER BY materialsID ASC
            ");
            $stmt->execute([':mid' => $materialsApplicationID]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("getDocumentsByApplication error: " . $e->getMessage());
            return [];
        }
    }

    public function updateCampaignMaterial(int $id, array $data): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE campaignmaterialsapplication
                SET materialsTitle = :title,
                    materialsType = :type,
                    materialsDesc = :desc,
                    materialsQuantity = :qty,
                    adminID = :adminID
                WHERE materialsApplicationID = :id
            ");
            return $stmt->execute([
                ':title'   => $data['materialsTitle'],
                ':type'    => $data['materialsType'],
                ':desc'    => $data['materialsDesc'],
                ':qty'     => $data['materialsQuantity'],
                ':adminID' => $data['adminID'] ?? null,
                ':id'      => $id,
            ]);
        } catch (PDOException $e) {
            error_log("updateCampaignMaterial error: " . $e->getMessage());
            return false;
        }
    }

    public function findDocument(int $materialsID): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT materialsID, materialsFilename, materialsApplicationID
                FROM campaignmaterialsdocument
                WHERE materialsID = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $materialsID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("findDocument error: " . $e->getMessage());
            return null;
        }
    }

    public function deleteDocument(int $materialsID): bool
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM campaignmaterialsdocument
                WHERE materialsID = :id
            ");
            return $stmt->execute([':id' => $materialsID]);
        } catch (PDOException $e) {
            error_log("deleteDocument error: " . $e->getMessage());
            return false;
        }
    }

    // Return just the filenames already recorded for this application
public function getDocumentNamesByApplication(int $materialsApplicationID): array
{
    try {
        $stmt = $this->db->prepare("
            SELECT materialsFilename
            FROM campaignmaterialsdocument
            WHERE materialsApplicationID = :mid
        ");
        $stmt->execute([':mid' => $materialsApplicationID]);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'materialsFilename');
    } catch (PDOException $e) {
        error_log("getDocumentNamesByApplication error: " . $e->getMessage());
        return [];
    }
}

/** Insert many filenames (skip if empty array) */
public function insertDocumentsBulk(int $materialsApplicationID, array $filenames): void
{
    if (empty($filenames)) return;
    try {
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("
            INSERT INTO campaignmaterialsdocument (materialsFilename, materialsApplicationID)
            VALUES (:fname, :mid)
        ");
        foreach ($filenames as $fn) {
            $stmt->execute([':fname' => $fn, ':mid' => $materialsApplicationID]);
        }
        $this->db->commit();
    } catch (PDOException $e) {
        $this->db->rollBack();
        error_log("insertDocumentsBulk error: " . $e->getMessage());
    }
}


}
