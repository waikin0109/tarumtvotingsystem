<?php

use Controller\VotingController\ElectionEventController;
use Controller\VotingController\RuleController;



// Election Event Routes
Route::get('/election-event', [ElectionEventController::class, 'listElectionEvents']);
Route::get('/election-event/create', [ElectionEventController::class, 'CreateElectionEvent']);
Route::post('/election-event/create', [ElectionEventController::class, 'storeElectionEvent']);
Route::get('/election-event/edit/{id}', [ElectionEventController::class, 'editElectionEvent']);
Route::post('/election-event/edit/{id}', [ElectionEventController::class, 'editStoreElectionEvent']);
Route::get('/election-event/view/{id}', [ElectionEventController::class, 'viewElectionEvent']);
Route::post('/election-event/delete/{id}', [ElectionEventController::class, 'deleteElectionEvent']);

// Rules and Regulations Routes
Route::get('/rule', [RuleController::class, 'listRules']);
Route::get('/rule/create', [RuleController::class, 'createRule']);
Route::post('/rule/create', [RuleController::class, 'storeRule']);
Route::get('/rule/edit/{id}', [RuleController::class, 'editRule']);
Route::post('/rule/edit/{id}', [RuleController::class, 'editStoreRule']);
Route::get('/rule/view/{id}', [RuleController::class, 'viewRule']);
Route::post('/rule/delete/{id}', [RuleController::class, 'deleteRule']);
