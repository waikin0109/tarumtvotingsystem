<?php

namespace Controller\AdminController;

use Model\AdminModel\AdminModel;
use FileHelper;

class AdminController
{
    private $adminModel;
    private $fileHelper;

    public function __construct()
    {
        $this->adminModel = new AdminModel();
        $this->fileHelper = new FileHelper("admin");
    }

    
}