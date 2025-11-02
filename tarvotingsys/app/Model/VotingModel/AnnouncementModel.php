<?php

namespace Model\VotingModel;

use PDO;
use PDOException;
use Database;

class AnnouncementModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // public function getAllAnnouncements()
    // {
    //     try {
    //         $stmt = $this->db->prepare("SELECT * FROM announcement ORDER BY announcementPublishedAt DESC");
    //         $stmt->execute();
    //         return $stmt->fetchAll(PDO::FETCH_ASSOC);
    //     } catch (PDOException $e) {
    //         error_log("Error in getAllAnnouncements: " . $e->getMessage());
    //         return [];
    //     }
    // }

    public function listForAdmin(): array
    {
        try {
            $sql = "SELECT a.announcementID,
                           a.announcementTitle   AS title,
                           a.announcementContent AS content,
                           a.announcementCreatedAt   AS createdAt,
                           a.announcementPublishedAt AS publishedAt,
                            a.announcementStatus,
                            a.accountID,   
                           ac.fullName AS senderName
                    FROM announcement a
                    LEFT JOIN account ac ON ac.accountID = a.accountID
                    ORDER BY CASE WHEN a.announcementStatus='DRAFT' THEN 0 ELSE 1 END,
                             COALESCE(a.announcementPublishedAt, a.announcementCreatedAt) DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error in list draft announcements for admin: ".$e->getMessage());
            return [];
        }
    }

      /** Public list: only published */
    public function listPublished(): array
    {
        try {
            $sql = "SELECT a.announcementID,
                           a.announcementTitle   AS title,
                           a.announcementContent AS content,
                           a.announcementPublishedAt AS publishedAt,
                           ac.fullName AS senderName
                    FROM announcement a
                    LEFT JOIN account ac ON ac.accountID = a.accountID
                    WHERE a.announcementStatus='PUBLISHED'
                    ORDER BY a.announcementPublishedAt DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Error in list published announcements for admin: ".$e->getMessage());
            return [];
        }
    }

  /** Create draft */
    public function createDraft(string $title, string $content, int $accountID): ?int
    {
        try {
            $sql = "INSERT INTO announcement
                      (announcementTitle, announcementContent, announcementStatus, accountID)
                    VALUES (?, ?, 'DRAFT', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $content, $accountID]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error in create announcement draft for admin: ".$e->getMessage());
            return null;
        }
    }

    /** Update only while draft (lock after publish) */
    public function updateDraft(int $id, string $title, string $content, int $accountID): bool
    {
        try {
            $sql = "UPDATE announcement
                       SET announcementTitle=?, announcementContent=?
                     WHERE announcementID=? AND announcementStatus='DRAFT' AND accountID=?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$title, $content, $id, $accountID]);
        } catch (PDOException $e) {
            error_log("Error in update announcement draft for admin: ".$e->getMessage());
            return false;
        }
    }

 /** Publish (sets date & locks) */
    public function publish(int $id, int $accountID): bool
    {
        try {
            $sql = "UPDATE announcement
                       SET status='PUBLISHED', AnnouncementPublishedAt=NOW()
                     WHERE announcementID=? AND announcementStatus='DRAFT' AND accountID=?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id, $accountID]);
        } catch (PDOException $e) {
            error_log("Error in update announcement status for admin: ".$e->getMessage());
            return false;
        }
    }


     /** Details */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT a.announcementID,
                           a.announcementTitle   AS title,
                           a.announcementContent AS content,
                           a.announcementCreatedAt   AS createdAt,
                           a.announcementPublishedAt AS publishedAt,
                           a.announcementStatus,
                           ac.fullName AS senderName
                           a.accountID
                    FROM announcement a
                    LEFT JOIN account ac ON ac.accountID = a.accountID
                    WHERE a.AnnouncementID=?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log("Error in view the announcement details:  ".$e->getMessage());
            return null;
        }
    }

    public function deleteAnnouncement(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM announcement WHERE announcementID = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error in deleteAnnouncement: " . $e->getMessage());
            return false;
        }
    }
}