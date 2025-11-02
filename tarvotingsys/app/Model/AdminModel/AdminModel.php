<?php

namespace Model\AdminModel;

use PDO;
use PDOException;
use Database;

class AdminModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    
}