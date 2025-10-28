<?php

use Controller\VotingController\ElectionEventController;



// Election Event Routes
Route::get('/election-event', [ElectionEventController::class, 'listElectionEvents']);
Route::get('/election-event/create', [ElectionEventController::class, 'CreateElectionEvent']);
Route::post('/election-event/create', [ElectionEventController::class, 'storeElectionEvent']);
Route::get('/election-event/edit/{id}', [ElectionEventController::class, 'editElectionEvent']);
Route::post('/election-event/edit/{id}', [ElectionEventController::class, 'editStoreElectionEvent']);
Route::get('/election-event/view/{id}', [ElectionEventController::class, 'viewElectionEvent']);
Route::post('/election-event/delete/{id}', [ElectionEventController::class, 'deleteElectionEvent']);
