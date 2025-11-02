<?php

use Controller\VotingController\ElectionEventController;
use Controller\AdminController\LoginController;
use Controller\VotingController\AnnouncementController;


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
Route::post('/announcement/create',           [AnnouncementController::class, 'createAnnouncement']);     // save draft
Route::get ('/announcement/edit/{id}',        [AnnouncementController::class, 'editAnnouncement']);       // form
Route::post('/announcement/edit/{id}',        [AnnouncementController::class, 'editAnnouncement']);       // update draft
Route::post('/announcement/publish/{id}',     [AnnouncementController::class, 'publishAnnouncement']);    // publish
Route::post('/announcement/delete/{id}',      [AnnouncementController::class, 'deleteAnnouncement']);     // delete

// STUDENT / NOMINEE
Route::get ('/announcements/public',          [AnnouncementController::class, 'publishedList']);          // list published
Route::get ('/announcements/{id}',            [AnnouncementController::class, 'viewAnnouncement']);       // details