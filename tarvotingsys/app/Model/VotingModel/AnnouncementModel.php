<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;
use Library\SimplePager;

class AnnouncementModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Publish all SCHEDULED announcements whose publish time is in the past or now.
    public function autoPublishDue(): int
    {
        try {
            $sql = "UPDATE announcement
                SET announcementStatus = 'PUBLISHED'
                WHERE announcementStatus = 'SCHEDULED'
                  AND announcementPublishedAt IS NOT NULL
                  AND announcementPublishedAt <= NOW()";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Error in auto publish announcement: ' . $e->getMessage());
            return 0;
        }
    }

    public function getPagedAnnouncementsForAdmin(int $page, int $limit = 10, string $search = ''): SimplePager
    {
        try {
            $sql = "
            SELECT 
                a.announcementID,
                a.announcementTitle       AS title,
                a.announcementContent     AS content,
                a.announcementCreatedAt   AS createdAt,
                a.announcementPublishedAt AS publishedAt,
                a.announcementStatus      AS announcementStatus,
                a.accountID,
                ac.fullName               AS senderName
            FROM announcement a
            LEFT JOIN account ac 
                ON ac.accountID = a.accountID
            WHERE 1
        ";

            $params = [];

            if ($search !== '') {
                $sql .= " AND a.announcementTitle LIKE :q";
                $params[':q'] = '%' . $search . '%';
            }

            $sql .= "
            ORDER BY
                CASE a.announcementStatus
                    WHEN 'DRAFT'     THEN 1
                    WHEN 'SCHEDULED' THEN 2
                    WHEN 'PUBLISHED' THEN 3
                    ELSE 4
                END,
                COALESCE(a.announcementPublishedAt, a.announcementCreatedAt) DESC
        ";

            // Let SimplePager fetch the current page
            $pager = new SimplePager($this->db, $sql, $params, $limit, $page);

            $countSql = "
            SELECT COUNT(*) AS cnt
            FROM announcement a
            WHERE 1
        ";
            $countParams = [];

            if ($search !== '') {
                $countSql .= " AND a.announcementTitle LIKE :q";
                $countParams[':q'] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($countParams);
            $total = (int) $stmt->fetchColumn();

            // Override SimplePager’s wrong total with the real one
            $pager->item_count = $total;
            $pager->page_count = (int) ceil($total / $pager->limit);

            return $pager;

        } catch (PDOException $e) {
            error_log("Error in getPagedAnnouncementsForAdmin: " . $e->getMessage());

            // Fallback: empty pager so controller/view still work
            $fallbackSql = "
            SELECT 
                a.announcementID,
                a.announcementTitle       AS title,
                a.announcementContent     AS content,
                a.announcementCreatedAt   AS createdAt,
                a.announcementPublishedAt AS publishedAt,
                a.announcementStatus      AS announcementStatus,
                a.accountID,
                '' AS senderName
            FROM announcement a
            WHERE 1 = 0
        ";

            return new SimplePager($this->db, $fallbackSql, [], $limit, $page);
        }
    }

    // Create a new announcement
    public function createAnnouncement(array $data): ?int
    {
        try {
            $sql = "INSERT INTO announcement
                (AnnouncementTitle, AnnouncementContent, AnnouncementCreatedAt, AnnouncementPublishedAt, AnnouncementStatus, AccountID)
                VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['title'],
                $data['content'],
                $data['createdAt'],
                $data['publishedAt'] ?? null,
                $data['status'],
                $data['createdBy']
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in create announcement: " . $e->getMessage());
            return null;
        }
    }

    // Insert a single attachment row linked to an announcement.
    public function addAttachment(int $announcementId, array $file): bool
    {
        try {
            $sql = "INSERT INTO attachment
                    (originalFileName, storedFileName, fileUrl, fileType, fileSize, attachmentUploadedAt, announcementID)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $this->db->prepare($sql);

            $url = '/uploads/announcements/' . $file['stored']; // public URL path
            return $stmt->execute([
                $file['original'],
                $file['stored'],
                $url,
                $file['mime'],
                $file['size'],
                $announcementId
            ]);
        } catch (PDOException $e) {
            error_log("Error in adding attachment: " . $e->getMessage());
            return false;
        }
    }

    // Revert a future SCHEDULED announcement back to DRAFT (owner-only).
    public function revertScheduledToDraft(int $id, int $accountId): bool
    {
        try {
            $sql = "UPDATE announcement
                SET announcementStatus = 'DRAFT',
                    announcementPublishedAt = NULL
                WHERE announcementID = :id
                  AND accountID = :owner
                  AND announcementStatus = 'SCHEDULED'
                  AND announcementPublishedAt IS NOT NULL
                  AND announcementPublishedAt > NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':owner' => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('Error in revert scheduled to draft: ' . $e->getMessage());
            return false;
        }
    }

    public function publishNowDraft(int $id, int $accountId): bool
    {
        try {
            $sql = "UPDATE announcement
                   SET announcementStatus = 'PUBLISHED',
                       announcementPublishedAt = NOW()
                 WHERE announcementID = :id
                   AND accountID = :owner
                   AND announcementStatus = 'DRAFT'";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $id,
                ':owner' => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('publishNowDraft error: ' . $e->getMessage());
            return false;
        }
    }

    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT
                    a.announcementID,
                    a.announcementTitle        AS title,
                    a.announcementContent      AS content,
                    a.announcementCreatedAt    AS createdAt,
                    a.announcementPublishedAt  AS publishedAt,
                    a.announcementStatus       AS announcementStatus,  -- 👈 match controller key
                    a.accountID                AS accountID,           -- 👈 ensure owner id is present
                    ac.fullName                AS senderName
                FROM announcement a
                LEFT JOIN account ac ON ac.accountID = a.accountID
                WHERE a.announcementID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row)
                return null;

            // attachments
            $row['attachments'] = $this->getAttachmentsByAnnouncement($id);
            return $row;
        } catch (PDOException $e) {
            error_log("getById error: " . $e->getMessage());
            return null;
        }
    }

    public function getAttachmentsByAnnouncement(int $announcementId): array
    {
        try {
            $sql = "SELECT attachmentID,
                       originalFileName AS original,
                       storedFileName   AS stored,
                       fileUrl,
                       fileType,
                       fileSize,
                       attachmentUploadedAt AS uploadedAt
                FROM attachment
                WHERE announcementID = ?
                ORDER BY attachmentUploadedAt ASC, attachmentID ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$announcementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getAttachmentsByAnnouncement error: ' . $e->getMessage());
            return [];
        }
    }

    // Update title/content of a DRAFT owned by admin 
    public function updateDraft(int $id, string $title, string $content, int $ownerId): int
    {
        try {
            $sql = "UPDATE announcement
                   SET announcementTitle = ?, announcementContent = ?
                 WHERE announcementID = ? AND announcementStatus = 'DRAFT' AND accountID = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $content, $id, $ownerId]);
            return $stmt->rowCount(); // 0 when identical content, >0 when actually changed
        } catch (PDOException $e) {
            error_log("updateDraft error: " . $e->getMessage());
            return -1; // signal error
        }
    }

    // Check if an attachment with the same original name and size already exists 
    public function attachmentExists(int $announcementId, string $originalName, int $size): bool
    {
        try {
            $sql = "SELECT 1
                  FROM attachment
                 WHERE announcementID = ?
                   AND originalFileName = ?
                   AND fileSize = ?
                 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$announcementId, $originalName, $size]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("attachmentExists error: " . $e->getMessage());
            return false;
        }
    }

    // Schedule a DRAFT to publish at a future datetime
    public function scheduleDraft(int $id, int $ownerId, string $publishAt): bool
    {
        try {
            $sql = "UPDATE announcement
                       SET announcementStatus='SCHEDULED', announcementPublishedAt = ?
                     WHERE announcementID = ? AND accountID = ? AND announcementStatus='DRAFT'";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$publishAt, $id, $ownerId]);
        } catch (PDOException $e) {
            error_log("scheduleDraft error: " . $e->getMessage());
            return false;
        }
    }

    // Delete a single attachment only if admin owns the parent announcement
    public function deleteAttachmentSecure(int $attachmentId, int $ownerId): bool
    {
        try {
            $sql = "DELETE att FROM attachment att
                    JOIN announcement a ON a.announcementID = att.announcementID
                    WHERE att.attachmentID = ? AND a.accountID = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$attachmentId, $ownerId]);
        } catch (PDOException $e) {
            error_log("deleteAttachmentSecure error: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAnnouncementSecure(int $announcementId, int $ownerId): bool
    {
        try {
            $this->db->beginTransaction();

            // Ensure the announcement exists and belongs to this owner
            $check = $this->db->prepare("
            SELECT announcementID 
            FROM announcement 
            WHERE announcementID = ? AND accountID = ?
            LIMIT 1
        ");
            $check->execute([$announcementId, $ownerId]);
            if (!$check->fetchColumn()) {
                $this->db->rollBack();
                return false;
            }

            // Delete attachment rows first (file deletion is done in controller)
            $delAtt = $this->db->prepare("DELETE FROM attachment WHERE announcementID = ?");
            $delAtt->execute([$announcementId]);

            // Delete the announcement
            $delAnn = $this->db->prepare("DELETE FROM announcement WHERE announcementID = ? AND accountID = ?");
            $delAnn->execute([$announcementId, $ownerId]);

            $ok = ($delAnn->rowCount() > 0);
            if ($ok) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            error_log("deleteAnnouncementCascadeSecure error: " . $e->getMessage());
            if ($this->db->inTransaction())
                $this->db->rollBack();
            return false;
        }
    }

    public function getPagedPublishedForPortal(int $page, int $limit = 10, string $search = ''): SimplePager
    {
        try {
            // 1) Base list query (what we actually show)
            $sql = "
            SELECT 
                a.announcementID,
                a.announcementTitle       AS title,
                a.announcementContent     AS content,
                a.announcementPublishedAt AS publishedAt,
                ac.fullName               AS senderName
            FROM announcement a
            LEFT JOIN account ac 
                ON ac.accountID = a.accountID
            WHERE a.announcementStatus = 'PUBLISHED'
        ";

            $params = [];

            if ($search !== '') {
                $sql .= " AND a.announcementTitle LIKE :q";
                $params[':q'] = '%' . $search . '%';
            }

            $sql .= "
            ORDER BY a.announcementPublishedAt DESC
        ";

            $pager = new SimplePager($this->db, $sql, $params, $limit, $page);

            $countSql = "
            SELECT COUNT(*) AS cnt
            FROM announcement a
            WHERE a.announcementStatus = 'PUBLISHED'
        ";
            $countParams = [];

            if ($search !== '') {
                $countSql .= " AND a.announcementTitle LIKE :q";
                $countParams[':q'] = '%' . $search . '%';
            }

            $stmt = $this->db->prepare($countSql);
            $stmt->execute($countParams);
            $total = (int) $stmt->fetchColumn();

            // Override SimplePager's total with the correct value
            $pager->item_count = $total;
            $pager->page_count = ($limit > 0) ? (int) ceil($total / $limit) : 1;

            return $pager;

        } catch (PDOException $e) {
            error_log("getPagedPublishedForPortal error: " . $e->getMessage());

            // Fallback: empty pager so controller/view still work
            $fallbackSql = "
            SELECT 
                a.announcementID,
                a.announcementTitle       AS title,
                a.announcementContent     AS content,
                a.announcementPublishedAt AS publishedAt,
                '' AS senderName
            FROM announcement a
            WHERE 1 = 0
        ";

            return new SimplePager($this->db, $fallbackSql, [], $limit, $page);
        }
    }

    public function getPublishedById(int $id): ?array
    {
        try {
            $sql = "SELECT a.announcementID,
                       a.announcementTitle       AS title,
                       a.announcementContent     AS content,
                       a.announcementPublishedAt AS publishedAt,
                       a.announcementStatus      AS status,
                       ac.fullName               AS senderName
                  FROM announcement a
             LEFT JOIN account ac ON ac.accountID = a.accountID
                 WHERE a.announcementID = ? AND a.announcementStatus = 'PUBLISHED'
                 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row)
                return null;
            $row['attachments'] = $this->getAttachmentsByAnnouncement($id); // include files
            return $row;
        } catch (PDOException $e) {
            error_log("getPublishedById error: " . $e->getMessage());
            return null;
        }
    }

}