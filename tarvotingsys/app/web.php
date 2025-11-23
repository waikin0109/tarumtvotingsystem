<?php

use Controller\VotingController\ElectionEventController;
use Controller\AdminController\LoginController;
use Controller\VotingController\AnnouncementController;
use Controller\VotingController\VoteSessionController;
use Controller\VotingController\BallotController;
use Controller\ResultController\ResultController;
use Controller\ResultController\ReportController;


Route::get('/election-events', [ElectionEventController::class, 'listElectionEvents']);

// Login routes
Route::get('/login', [LoginController::class, 'login']);
Route::post('/login', [LoginController::class, 'authenticate']);
Route::get('/logout', [LoginController::class, 'logout']);


// Example protected dashboards
Route::get('/admin/home',   [LoginController::class, 'adminHome']);
Route::get('/student/home', [LoginController::class, 'studentHome']);
Route::get('/nominee/home', [LoginController::class, 'nomineeHome']);

// Announcement routes
Route::get('/announcements', [AnnouncementController::class, 'listAnnouncements']);
Route::get('/announcement/create', [AnnouncementController::class,'createAnnouncement']);
Route::post('/announcement/store',           [AnnouncementController::class, 'storeAnnouncement']); // save draft
Route::post('/announcement/revert/{id}',           [AnnouncementController::class, 'revertAnnouncementToDraft']);
Route::post('/announcement/publish/{id}',           [AnnouncementController::class, 'publishAnnouncement']);
Route::get ('/announcement/edit/{id}',        [AnnouncementController::class, 'editAnnouncement']);
Route::post('/announcement/edit/{id}',        [AnnouncementController::class, 'editAnnouncement']); // update draft
Route::post('/announcement/attachment/delete', [AnnouncementController::class, 'deleteAttachment']);
Route::post('/announcement/delete', [AnnouncementController::class, 'deleteAnnouncement']);
Route::get('/announcements/public',            [AnnouncementController::class, 'viewAnnouncementForStudentAndNominee']); // STUDENT / NOMINEE
Route::get('/announcements/public/{id}',       [AnnouncementController::class, 'viewAnnouncementDetailsForStudentAndNominee']); // STUDENT / NOMINEE
Route::get ('/announcements/{id}',            [AnnouncementController::class, 'viewAnnouncementDetails']);

// Vote Session routes
Route::get('/vote-session', [VoteSessionController::class, 'listVoteSessions']);
Route::get('/vote-session/create', [VoteSessionController::class, 'createVoteSession']);
Route::post('/vote-session/store', [VoteSessionController::class, 'storeVoteSession']);
Route::get('/vote-session/edit/{id}', [VoteSessionController::class, 'editVoteSession']);
Route::post('/vote-session/edit/{id}', [VoteSessionController::class, 'updateVoteSession']);
Route::get ('/vote-session/details/{id}',            [VoteSessionController::class, 'viewVoteSessionDetails']);
Route::post('/vote-session/delete', [VoteSessionController::class, 'deleteVoteSession']);
Route::post('/vote-session/schedule', [VoteSessionController::class, 'scheduleVoteSession']);
Route::post('/vote-session/unschedule', [VoteSessionController::class, 'unscheduleVoteSession']);
Route::post('/vote-session/cancel',     [VoteSessionController::class, 'cancelVoteSession']);
Route::get('/vote-session/public',            [VoteSessionController::class, 'viewVoteSessionForStudentAndNominee']); // STUDENT / NOMINEE

// Route::get('/vote-session/results/{id}', [VoteSessionController::class, 'viewResults']);


// Ballot routes
Route::get('/ballot/start/{id}', [BallotController::class, 'startBallot']); 
Route::post('/ballot/start',  [BallotController::class, 'clickStartBallot']);
Route::get('/ballot/cast/{id}', [BallotController::class, 'showCastPage']);
Route::post('/ballot/cast/{id}', [BallotController::class, 'submitBallot']); 

// Result routes
Route::get('/statistics', [ResultController::class, 'viewStatisticalData']);

// // Live turnout (real-time) – shared page for all three roles
// Route::get('/admin/results/live',   [ResultController::class, 'viewStatisticalData']);
// Route::get('/student/results/live', [ResultController::class, 'viewStatisticalData']);
// Route::get('/nominee/results/live', [ResultController::class, 'viewStatisticalData']);

// // OFFICIAL FINAL RESULTS (ADMIN + PUBLIC)
// Route::get('/results',         [ResultController::class, 'viewFinalResultsAdmin']);
// Route::get('/results/public',  [ResultController::class, 'viewFinalResultsPublic']);

// Route::get('/vote-session/results/{id}', [ResultController::class, 'redirectFromVoteSession']);
Route::get('/results', [ResultController::class, 'viewFinalResults']);


// Report Routes
// Route::get('/admin/reports', [ReportController::class, 'reportGenerator']);
// Route::post('/admin/reports/generate', [ReportController::class, 'generateReport']);

Route::get('/admin/reports/generator',        [ReportController::class, 'showGenerator']);
Route::post('/admin/reports/generate',        [ReportController::class, 'generate']);

Route::get('/admin/reports/list',  [ReportController::class, 'reportListPage']);
Route::post('/admin/reports/delete', [ReportController::class, 'deleteReport']);

Route::get('/admin/reports/overall-turnout',  [ReportController::class, 'overallTurnoutPage']);
Route::get('/admin/reports/official-results', [ReportController::class, 'officialResultsPage']);
Route::get('/admin/reports/results-by-faculty', [ReportController::class, 'resultsByFacultyPage']);
Route::get('/admin/reports/early-vote-status', [ReportController::class, 'earlyVoteStatusPage']);