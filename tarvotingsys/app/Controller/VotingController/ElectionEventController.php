<?php

namespace Controller\VotingController;

use Model\VotingModel\ElectionEventModel;
use FileHelper;

class ElectionEventController
{
    private $electionEventModel;
    private $fileHelper;

    public function __construct()
    {
        $this->electionEventModel = new ElectionEventModel();
        $this->fileHelper = new FileHelper("election_event");
    }

    public function listElectionEvents()
    {
        $electionEvents = $this->electionEventModel->getAllElectionEvents(); // ensure method name matches your model
        $filePath = $this->fileHelper->getFilePath('ElectionEventList');

        if ($filePath && file_exists($filePath)) {
            include $filePath;
        } else {
            echo "View file not found.";
        }
    }
    

}
