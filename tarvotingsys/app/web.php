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

// Profile

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
// Admin Sided
Route::get('/admin/election-registration-form', [RegistrationFormController::class, 'listRegistrationForms']);
Route::get('/admin/election-registration-form/create', [RegistrationFormController::class, 'createRegistrationForm']);
Route::post('/admin/election-registration-form/create', [RegistrationFormController::class, 'storeRegistrationForm']);
Route::get('/admin/election-registration-form/edit/{id}', [RegistrationFormController::class, 'editRegistrationForm']);
Route::post('/admin/election-registration-form/edit/{id}', [RegistrationFormController::class, 'editStoreRegistrationForm']);
Route::get('/admin/election-registration-form/view/{id}', [RegistrationFormController::class, 'viewRegistrationForm']);
Route::post('/admin/election-registration-form/delete/{id}', [RegistrationFormController::class, 'deleteRegistrationForm']);

// Student Sided
Route::get('/student/election-registration-form', [RegistrationFormController::class, 'listRegistrationFormsStudent']);
Route::get('/student/election-registration-form/register/{id}', [NomineeApplicationController::class, 'applyFormStudent']);
Route::post('/student/election-registration-form/register/{id}', [NomineeApplicationController::class, 'applyStoreStudent']);

Route::get('/student/election-registration-form/view/{id}', [NomineeApplicationController::class, 'viewNomineeApplicationStudent']);

// Nominee Sided
Route::get('/nominee/election-registration-form', [RegistrationFormController::class, 'listRegistrationFormsNominee']);
Route::get('/nominee/election-registration-form/register/{id}', [NomineeApplicationController::class, 'applyFormNominee']);
Route::post('/nominee/election-registration-form/register/{id}', [NomineeApplicationController::class, 'applyStoreNominee']);
Route::get('/nominee/election-registration-form/view/{id}', [NomineeApplicationController::class, 'viewNomineeApplicationNominee']);


// Nominee Application Routes
// Admin Sided
Route::get('/admin/nominee-application', [NomineeApplicationController::class, 'listNomineeApplications']);
Route::get('/admin/nominee-application/create', [NomineeApplicationController::class, 'createNomineeApplication']);
Route::post('/admin/nominee-application/create', [NomineeApplicationController::class, 'storeNomineeApplication']);
Route::get('/admin/nominee-application/edit/{id}', [NomineeApplicationController::class, 'editNomineeApplication']);
Route::post('/admin/nominee-application/edit/{id}', [NomineeApplicationController::class, 'editStoreNomineeApplication']);
Route::get('/admin/nominee-application/view/{id}', [NomineeApplicationController::class, 'viewNomineeApplication']);
Route::post('/admin/nominee-application/accept/{id}', [NomineeApplicationController::class, 'acceptNomineeApplication']);
Route::post('/admin/nominee-application/reject/{id}', [NomineeApplicationController::class, 'rejectNomineeApplication']);
Route::get('/admin/nominee-application/publish', [NomineeApplicationController::class, 'publishNomineeApplications']);
Route::post('/admin/nominee-application/publish', [NomineeApplicationController::class, 'publishStoreNomineeApplications']);
Route::get('/admin/nominee-application/publish/{id}', [NomineeApplicationController::class, 'finalizePublishNomineeApplications']);

// Student Sided
Route::get('/student/nominee-final-list', [NomineeApplicationController::class, 'listNomineeApplicationsStudent']);
Route::get('/student/nominee-final-list/view/{id}', [NomineeApplicationController::class, 'finalizePublishNomineeApplicationsStudent']);

// Nominee Sided
Route::get('/nominee/nominee-final-list', [NomineeApplicationController::class, 'listNomineeApplicationsNominee']);
Route::get('/nominee/nominee-final-list/view/{id}', [NomineeApplicationController::class, 'finalizePublishNomineeApplicationsNominee']);



// Campaign Material Routes
// Admin Sided
Route::get('/admin/campaign-material', [CampaignMaterialController::class, 'listCampaignMaterials']);
Route::get('/admin/campaign-material/create', [CampaignMaterialController::class, 'createCampaignMaterial']);
Route::post('/admin/campaign-material/create', [CampaignMaterialController::class, 'storeCreateCampaignMaterial']);
Route::get('/admin/campaign-material/edit/{id}', [CampaignMaterialController::class, 'editCampaignMaterial']);
Route::post('/admin/campaign-material/edit/{id}', [CampaignMaterialController::class, 'storeEditCampaignMaterial']);
Route::get('/admin/campaign-material/view/{id}', [CampaignMaterialController::class, 'viewCampaignMaterial']);
Route::post('/admin/campaign-material/accept/{id}', [CampaignMaterialController::class, 'acceptCampaignMaterial']);
Route::post('/admin/campaign-material/reject/{id}', [CampaignMaterialController::class, 'rejectCampaignMaterial']);

// Nominee Sided
Route::get('/nominee/campaign-material', [CampaignMaterialController::class, 'listCampaignMaterialsNominee']);
Route::get('/nominee/campaign-material/create', [CampaignMaterialController::class, 'createCampaignMaterialNominee']);
Route::post('/nominee/campaign-material/create', [CampaignMaterialController::class, 'storeCreateCampaignMaterialNominee']);
Route::get('/nominee/campaign-material/view/{id}', [CampaignMaterialController::class, 'viewCampaignMaterialNominee']);

// Schedule Location Routes
Route::get('/admin/schedule-location', [ScheduleLocationController::class, 'listScheduleLocations']);
Route::get('/admin/schedule-location/create', [ScheduleLocationController::class, 'createScheduleLocation']);
Route::post('/admin/schedule-location/create', [ScheduleLocationController::class, 'storeCreateScheduleLocation']);
Route::get('/admin/schedule-location/edit/{id}', [ScheduleLocationController::class, 'editScheduleLocation']);
Route::post('/admin/schedule-location/edit/{id}', [ScheduleLocationController::class, 'storeEditScheduleLocation']);
Route::get('/admin/schedule-location/view/{id}', [ScheduleLocationController::class, 'viewScheduleLocation']);
// Schedule Part
Route::get('/admin/schedule-location/schedule', [ScheduleLocationController::class, 'scheduleBoard']);
Route::post('/admin/schedule-location/accept/{id}', [ScheduleLocationController::class, 'scheduleAccept']);
Route::post('/admin/schedule-location/reject/{id}', [ScheduleLocationController::class, 'scheduleReject']);
Route::get('/admin/schedule-location/calendar-feed', [ScheduleLocationController::class, 'calendarFeed']);
Route::post('/admin/schedule-location/reject-accepted/{id}', [ScheduleLocationController::class, 'scheduleRejectAccepted']);
Route::post('/admin/schedule-location/accept-back/{id}', [ScheduleLocationController::class, 'scheduleAcceptBack']);
Route::post('/admin/schedule-location/unschedule/{id}', [ScheduleLocationController::class, 'scheduleUnschedule']);
Route::get('/admin/schedule-location/view-schedule', [ScheduleLocationController::class, 'viewCampaignSchedule']);

// Student Sided
Route::get('/student/schedule-location', [ScheduleLocationController::class, 'listScheduleLocationsStudent']);
Route::get('/student/schedule-location/schedule/view/{id}', [ScheduleLocationController::class, 'viewCampaignScheduleStudent']);
Route::get('/student/schedule-location/calendar-feed', [ScheduleLocationController::class, 'calendarFeed']);
Route::get('/student/schedule-location/view/{id}', [ScheduleLocationController::class, 'viewScheduleLocationStudent']);

// Nominee Sided
Route::get('/nominee/schedule-location', [ScheduleLocationController::class, 'listScheduleLocationsNominee']);
Route::get('/nominee/schedule-location/schedule/view/{id}', [ScheduleLocationController::class, 'viewCampaignScheduleNominee']);
Route::get('/nominee/schedule-location/calendar-feed', [ScheduleLocationController::class, 'calendarFeed']);
Route::get('/nominee/schedule-location/view/{id}', [ScheduleLocationController::class, 'viewScheduleLocationNominee']);
Route::get('/nominee/schedule-location/create', [ScheduleLocationController::class, 'createScheduleLocationNominee']);
Route::post('/nominee/schedule-location/create', [ScheduleLocationController::class, 'storeCreateScheduleLocationNominee']);

