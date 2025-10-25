<?php

use Controller\VotingController\ElectionEventController;

Route::get('/election-events', [ElectionEventController::class, 'listElectionEvents']);