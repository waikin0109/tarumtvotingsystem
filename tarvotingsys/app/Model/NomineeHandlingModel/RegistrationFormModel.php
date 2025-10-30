<?php

namespace Model\NomineeHandlingModel;

use PDO;
use PDOException;
use Database;

class RegistrationFormModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllRegistrationForms()
    {
        try {
            $stmt = $this->db->prepare("
                SELECT rf.*, e.title AS event_name
                FROM registrationform rf
                LEFT JOIN electionevent e ON rf.electionID = e.electionID
                ORDER BY rf.registrationFormID ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllRegistrationForms: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert form + attributes as one transaction.
     * @return int|false The new registrationFormID on success, false on failure.
     */
    public function createRegistrationFormWithAttributes($data, $attributes)
    {
        // allow only known attribute keys
        $whitelist = ['cgpa','reason','achievements','behaviorReport'];
        $attributes = array_values(array_intersect($attributes, $whitelist));

        try {
            $this->db->beginTransaction();

            // Insert the form (columns per your schema)
            $stmt = $this->db->prepare("
                INSERT INTO registrationform
                (registrationFormTitle, registerStartDate, registerEndDate, dateCreated, electionID, adminID)
                VALUES
                (?, ?, ?, NOW(), ?, 1)
            ");
            $stmt->execute([
                $data['registrationFormTitle'],
                $data['registerStartDateTime'], // Y-m-d H:i:s
                $data['registerEndDateTime'],   // Y-m-d H:i:s
                $data['electionID']
            ]);

            $formId = (int)$this->db->lastInsertId();

            // Insert selected attributes
            if (!empty($attributes)) {
                $stmtAttr = $this->db->prepare("
                    INSERT INTO registrationformattribute (attributeName, registrationFormID)
                    VALUES (?, ?)
                ");
                foreach ($attributes as $attr) {
                    $stmtAttr->execute([$attr, $formId]);
                }
            }

            $this->db->commit();
            return $formId;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in createRegistrationFormWithAttributes: ".$e->getMessage());
            return false;
        }
    }

    public function existsForElection($electionID)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM registrationform WHERE electionID = ? LIMIT 1");
        $stmt->execute([$electionID]);
        return (bool) $stmt->fetchColumn();
    }



    public function getAllRegistrationFormAttributes()
    {
        // Hardcode for now (or load from a table)
        return [
            ['key' => 'cgpa',            'label' => 'CGPA'],
            ['key' => 'reason',          'label' => 'Reason for Participation'],
            ['key' => 'achievements',    'label' => 'Achievements / Awards'],
            ['key' => 'behaviorReport',  'label' => 'Behavior Report'],
        ];
    }


    // -------------------------------- Get Registration Form Attributes ----------------------------------------- //
    // Get attributes for a certain ID of registration form
    public function getAttributesByFormId($formId) {
        $stmt = $this->db->prepare("SELECT attributeName FROM registrationformattribute WHERE registrationFormID = ?");
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    // Fetch a registration form by ID
    public function getRegistrationFormById($formId)
    {
        $stmt = $this->db->prepare("
            SELECT rf.*, e.title AS event_name
            FROM registrationform rf
            LEFT JOIN electionevent e ON rf.electionID = e.electionID
            WHERE rf.registrationFormID = ?
            LIMIT 1
        ");
        $stmt->execute([$formId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    // Update form + replace its attributes
    public function updateRegistrationFormWithAttributes($data, $attributes)
    {
        $whitelist = ['cgpa','reason','achievements','behaviorReport'];
        $attributes = array_values(array_intersect($attributes, $whitelist));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE registrationform
                SET registrationFormTitle = ?,
                    registerStartDate = ?,
                    registerEndDate = ?,
                    electionID = ?
                WHERE registrationFormID = ?
            ");
            $stmt->execute([
                $data['registrationFormTitle'],
                $data['registerStartDateTime'],
                $data['registerEndDateTime'],
                $data['electionID'],
                $data['registrationFormID']
            ]);

            // Here we use delete not update is because user might remove some attributes e.g 4->3
            // Delete old attributes
            $stmtDel = $this->db->prepare("DELETE FROM registrationformattribute WHERE registrationFormID = ?");
            $stmtDel->execute([$data['registrationFormID']]);

            // Insert new attributes
            if (!empty($attributes)) {
                $stmtAttr = $this->db->prepare("
                    INSERT INTO registrationformattribute (attributeName, registrationFormID)
                    VALUES (?, ?)
                ");
                foreach ($attributes as $attr) {
                    $stmtAttr->execute([$attr, $data['registrationFormID']]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Error in updateRegistrationFormWithAttributes: ".$e->getMessage());
            return false;
        }
    }

    public function existsForOtherElection(int $electionID, int $excludeFormID): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 
            FROM registrationform 
            WHERE electionID = ? 
            AND registrationFormID != ?
            LIMIT 1
        ");
        $stmt->execute([$electionID, $excludeFormID]);
        return (bool) $stmt->fetchColumn();
    }

    //-------------------------------- Delete Registration Form ----------------------------------------- //
    public function deleteRegistrationForm($formId)
    {
        try {
            $stmtAttr = $this->db->prepare("DELETE FROM registrationformattribute WHERE registrationFormID = :id");
            $stmt = $this->db->prepare("DELETE FROM registrationform WHERE registrationFormID = :id");
            $stmtAttr->bindParam(':id', $formId, PDO::PARAM_INT);
            $stmt->bindParam(':id', $formId, PDO::PARAM_INT);
            return $stmtAttr->execute() && $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error in deleteRegistrationForm: " . $e->getMessage());
            return false;
        }
    }

}


