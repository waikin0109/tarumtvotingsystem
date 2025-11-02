<?php

use Controller\VotingController\ElectionEventController;
use Controller\VotingController\RuleController;

use Controller\NomineeHandlingController\RegistrationFormController;

use Controller\NomineeController\NomineeApplicationController;



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

// Election Registration Form Routes
Route::get('/election-registration-form', [RegistrationFormController::class, 'listRegistrationForms']);
Route::get('/election-registration-form/create', [RegistrationFormController::class, 'createRegistrationForm']);
Route::post('/election-registration-form/create', [RegistrationFormController::class, 'storeRegistrationForm']);
Route::get('/election-registration-form/edit/{id}', [RegistrationFormController::class, 'editRegistrationForm']);
Route::post('/election-registration-form/edit/{id}', [RegistrationFormController::class, 'editStoreRegistrationForm']);
Route::get('/election-registration-form/view/{id}', [RegistrationFormController::class, 'viewRegistrationForm']);
Route::post('/election-registration-form/delete/{id}', [RegistrationFormController::class, 'deleteRegistrationForm']);

// Nominee Application Routes
Route::get('/nominee-application', [NomineeApplicationController::class, 'listNomineeApplications']);
Route::get('/nominee-application/create', [NomineeApplicationController::class, 'createNomineeApplication']);
Route::post('/nominee-application/create', [NomineeApplicationController::class, 'storeNomineeApplication']);
Route::get('/nominee-application/edit/{id}', [NomineeApplicationController::class, 'editNomineeApplication']);
Route::post('/nominee-application/edit/{id}', [NomineeApplicationController::class, 'editStoreNomineeApplication']);
Route::get('/nominee-application/view/{id}', [NomineeApplicationController::class, 'viewNomineeApplication']);
Route::post('/nominee-application/accept/{id}', [NomineeApplicationController::class, 'acceptNomineeApplication']);
Route::post('/nominee-application/reject/{id}', [NomineeApplicationController::class, 'rejectNomineeApplication']);