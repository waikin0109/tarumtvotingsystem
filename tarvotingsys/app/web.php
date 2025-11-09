<?php

use Controller\VotingController\ElectionEventController;
use Controller\AdminController\LoginController;
use Controller\VotingController\AnnouncementController;

use Controller\VotingController\RuleController;
use Controller\NomineeHandlingController\RegistrationFormController;
use Controller\NomineeController\NomineeApplicationController;
use Controller\CampaignHandlingController\CampaignMaterialController;
use Controller\CampaignHandlingController\ScheduleLocationController;


// TH Part Routes
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





// WK Part Routes
// Election Event Routes
Route::get('/admin/election-event', [ElectionEventController::class, 'listElectionEvents']);
Route::get('/admin/election-event/create', [ElectionEventController::class, 'CreateElectionEvent']);
Route::post('/admin/election-event/create', [ElectionEventController::class, 'storeElectionEvent']);
Route::get('/admin/election-event/edit/{id}', [ElectionEventController::class, 'editElectionEvent']);
Route::post('/admin/election-event/edit/{id}', [ElectionEventController::class, 'editStoreElectionEvent']);
Route::get('/admin/election-event/view/{id}', [ElectionEventController::class, 'viewElectionEvent']);
Route::post('/admin/election-event/delete/{id}', [ElectionEventController::class, 'deleteElectionEvent']);

// Rules and Regulations Routes
// Admin Sided
Route::get('/admin/rule', [RuleController::class, 'listRules']);
Route::get('/admin/rule/create', [RuleController::class, 'createRule']);
Route::post('/admin/rule/create', [RuleController::class, 'storeRule']);
Route::get('/admin/rule/edit/{id}', [RuleController::class, 'editRule']);
Route::post('/admin/rule/edit/{id}', [RuleController::class, 'editStoreRule']);
Route::get('/admin/rule/view/{id}', [RuleController::class, 'viewRule']);
Route::post('/admin/rule/delete/{id}', [RuleController::class, 'deleteRule']);

// Student Sided
Route::get('/student/rule', [RuleController::class, 'listRulesStudent']);
Route::get('/student/rule/view/{id}', [RuleController::class, 'viewRuleStudent']);

// Nominee Sided
Route::get('/nominee/rule', [RuleController::class, 'listRulesNominee']);
Route::get('/nominee/rule/view/{id}', [RuleController::class, 'viewRuleNominee']);




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
Route::get('/nominee-application/publish', [NomineeApplicationController::class, 'publishNomineeApplications']);
Route::post('/nominee-application/publish', [NomineeApplicationController::class, 'publishStoreNomineeApplications']);
Route::get('/nominee-application/publish/{id}', [NomineeApplicationController::class, 'finalizePublishNomineeApplications']);

// Campaign Material Routes
Route::get('/campaign-material', [CampaignMaterialController::class, 'listCampaignMaterials']);
Route::get('/campaign-material/create', [CampaignMaterialController::class, 'createCampaignMaterial']);
Route::post('/campaign-material/create', [CampaignMaterialController::class, 'storeCreateCampaignMaterial']);
Route::get('/campaign-material/edit/{id}', [CampaignMaterialController::class, 'editCampaignMaterial']);
Route::post('/campaign-material/edit/{id}', [CampaignMaterialController::class, 'storeEditCampaignMaterial']);
Route::get('/campaign-material/view/{id}', [CampaignMaterialController::class, 'viewCampaignMaterial']);
Route::post('/campaign-material/accept/{id}', [CampaignMaterialController::class, 'acceptCampaignMaterial']);
Route::post('/campaign-material/reject/{id}', [CampaignMaterialController::class, 'rejectCampaignMaterial']);

// Schedule Location Routes
Route::get('/schedule-location', [ScheduleLocationController::class, 'listScheduleLocations']);
Route::get('/schedule-location/create', [ScheduleLocationController::class, 'createScheduleLocation']);
Route::post('/schedule-location/create', [ScheduleLocationController::class, 'storeCreateScheduleLocation']);
Route::get('/schedule-location/edit/{id}', [ScheduleLocationController::class, 'editScheduleLocation']);
Route::post('/schedule-location/edit/{id}', [ScheduleLocationController::class, 'storeEditScheduleLocation']);
Route::get('/schedule-location/view/{id}', [ScheduleLocationController::class, 'viewScheduleLocation']);

Route::get('/schedule-location/schedule', [ScheduleLocationController::class, 'scheduleBoard']);
Route::get('/schedule-location/locations-at', [ScheduleLocationController::class, 'locationsAt']); // optional ajax
Route::post('/schedule-location/accept/{id}', [ScheduleLocationController::class, 'scheduleAccept']);
Route::post('/schedule-location/reject/{id}', [ScheduleLocationController::class, 'scheduleReject']);
Route::get('/schedule-location/calendar-feed', [ScheduleLocationController::class, 'calendarFeed']);
Route::post('/schedule-location/reject-accepted/{id}', [ScheduleLocationController::class, 'scheduleRejectAccepted']);
Route::post('/schedule-location/accept-back/{id}', [ScheduleLocationController::class, 'scheduleAcceptBack']);
Route::post('/schedule-location/unschedule/{id}', [ScheduleLocationController::class, 'scheduleUnschedule']);
Route::get('/schedule-location/view-schedule', [ScheduleLocationController::class, 'viewCampaignSchedule']);
