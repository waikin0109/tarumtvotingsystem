================================================================================
TARUMT VOTING SYSTEM - README
================================================================================

PROJECT OVERVIEW
----------------
The TARUMT Voting System is a comprehensive web-based application designed to manage
and conduct student elections. It supports multiple user roles (Admin, Student, Nominee),
manages voting sessions, candidate applications, campaign materials, and generates
election results and reports.

SYSTEM REQUIREMENTS
-------------------
- PHP 7.4 or higher
- MySQL/MariaDB Database
- Apache Web Server (with mod_rewrite enabled)
- Composer (PHP Package Manager)
- Modern Web Browser (Chrome, Firefox, Safari, Edge)

INSTALLATION STEPS
------------------
1. Clone or extract the project to your web server directory
2. Navigate to the project root directory
3. Run: composer install
4. Configure your database connection in the Database.php file
5. Import the database schema (if provided)
6. Set proper file permissions for uploads/ and images/ directories
7. Access the application via: http://localhost/tarumtvotingsystem/

PROJECT STRUCTURE
-----------------
/app/
├── Controller/           - Route handlers and business logic
│   ├── AdminController/  - Admin-related operations
│   ├── NomineeController/ - Nominee profile and settings
│   ├── StudentController/ - Student profile and voting
│   ├── VotingController/ - Election events, voting, results
│   ├── ResultController/ - Statistics and reports
│   ├── CampaignHandlingController/ - Campaign materials & scheduling
│   └── NomineeHandlingController/  - Registration forms
├── Model/               - Database interaction layer
├── View/                - HTML templates and UI components
├── Library/             - Third-party utilities (PHPMailer, SimplePager, etc.)
├── Utilities/           - Helper functions (functions.php)
├── Service/             - Business logic services
├── public/
│   ├── index.php        - Main entry point
│   ├── css/             - Stylesheets
│   ├── js/              - JavaScript files
│   └── uploads/         - User-uploaded files
├── web.php              - Route definitions
├── Route.php            - Route handler class
├── Database.php         - Database connection class
├── FileHelper.php       - File handling utilities
├── SessionHelper.php    - Session management
└── autoload.php         - PSR-4 autoloader

KEY FEATURES
------------
1. AUTHENTICATION & USER ROLES
   - Admin: System administration and election management
   - Student: Voting and candidate information viewing
   - Nominee: Candidate profile management and application

2. ELECTION MANAGEMENT
   - Create and manage election events
   - Schedule voting sessions with date/time constraints
   - Set election rules and regulations
   - Manage vote sessions and ballots

3. NOMINEE MANAGEMENT
   - Student registration as nominees
   - Nominee application approval/rejection
   - Publish final nominee list
   - Manifesto and profile management

4. CAMPAIGN MANAGEMENT
   - Upload and manage campaign materials
   - Schedule campaign events and locations
   - Approve/reject campaign materials
   - View campaign timetables

5. VOTING SYSTEM
   - Secure ballot casting
   - Vote session scheduling
   - Real-time voting participation
   - Fraud prevention measures

6. RESULTS & REPORTING
   - View statistical voting data
   - Generate official election results
   - Faculty-based result breakdowns
   - Early voting status reports
   - Comprehensive election reports

MAIN ROUTES
-----------
LOGIN & AUTHENTICATION:
  GET/POST  /login              - User login
  GET       /logout             - User logout

DASHBOARDS:
  GET       /admin/home         - Admin dashboard
  GET       /student/home       - Student dashboard
  GET       /nominee/home       - Nominee dashboard

ELECTION EVENTS (Admin):
  GET       /admin/election-event           - List events
  GET/POST  /admin/election-event/create    - Create event
  GET/POST  /admin/election-event/edit/{id} - Edit event
  POST      /admin/election-event/delete/{id} - Delete event

RULES & REGULATIONS:
  GET       /admin/rule              - Admin: List rules
  GET       /student/rule            - Student: View rules
  GET       /nominee/rule            - Nominee: View rules
  GET/POST  /admin/rule/create       - Admin: Create rule
  GET/POST  /admin/rule/edit/{id}    - Admin: Edit rule

VOTING SESSIONS:
  GET       /vote-session            - List sessions
  GET/POST  /vote-session/create     - Create session
  GET/POST  /vote-session/edit/{id}  - Edit session
  POST      /vote-session/schedule   - Schedule session
  GET       /ballot/start/{id}       - Start voting
  POST      /ballot/cast/{id}        - Cast vote

NOMINEES & APPLICATIONS:
  GET       /admin/nominee-application        - Admin: List applications
  GET       /student/election-registration-form - Student: Register as nominee
  GET       /nominee/election-registration-form - Nominee: Manage registration
  POST      /admin/nominee-application/accept/{id} - Approve nominee

CAMPAIGN MATERIALS:
  GET       /nominee/campaign-material           - Nominee: Manage materials
  GET/POST  /nominee/campaign-material/create    - Create material
  POST      /admin/campaign-material/accept/{id} - Approve material

SCHEDULE & LOCATIONS:
  GET       /nominee/schedule-location          - Nominee: Manage schedule
  GET/POST  /nominee/schedule-location/create   - Create schedule
  GET       /student/schedule-location          - Student: View campaign timetable
  POST      /admin/schedule-location/accept/{id} - Approve schedule

RESULTS & REPORTS:
  GET       /statistics              - View voting statistics
  GET       /results                 - View final results
  GET       /admin/reports/generator - Generate reports
  GET       /admin/reports/list      - List all reports

ANNOUNCEMENTS:
  GET       /announcements           - List announcements
  GET/POST  /announcement/create     - Create announcement
  GET       /announcements/public    - Public announcements (Student/Nominee)

PROFILES:
  GET       /admin/profile           - Admin profile
  GET       /student/profile         - Student profile
  GET       /nominee/profile         - Nominee profile
  POST      /*/profile/update-password - Update password
  POST      /*/profile/update-photo    - Update profile photo

DATABASE SCHEMA
---------------
The system uses multiple tables to store:
- User credentials (admins, students, nominees)
- Election events and configurations
- Voting sessions and ballots
- Nominee applications and data
- Campaign materials and schedules
- Rules and regulations
- Announcements and messages
- Uploaded files and media

(Refer to database migration/schema files for detailed table structures)

FILE UPLOAD DIRECTORIES
-----------------------
/public/uploads/
├── academic_document/   - Nominee academic documents
├── campaign_material/   - Campaign promotional materials
└── [other uploads]

Ensure these directories have proper write permissions (755 or 775).

CONFIGURATION
-------------
Database Connection: Configure in Database.php
- Host: localhost (or your DB server)
- Database: tarumtvs
- Username: root (or your DB user)
- Password: (your password)

Session Configuration: Configure in SessionHelper.php
- Session timeout duration
- Cookie settings
- CSRF token handling

Mail Configuration: Configure in library/PHPMailer.php
- SMTP settings for election notifications
- Email templates

SECURITY FEATURES
-----------------
1. User Authentication & Session Management
2. Role-Based Access Control (RBAC)
3. Input Validation & Sanitization
4. SQL Injection Prevention (Prepared Statements)
5. CSRF Token Protection
6. Secure Password Hashing
7. File Upload Validation
8. XSS Protection

USAGE GUIDELINES
----------------
ADMINS:
  1. Login with admin credentials
  2. Create and manage election events
  3. Set rules and regulations
  4. Manage nominee applications
  5. Approve campaign materials and schedules
  6. Monitor voting sessions
  7. Generate reports

STUDENTS:
  1. Login with student credentials
  2. View announcements and rules
  3. Apply to be a nominee (if eligible)
  4. View candidate information
  5. Participate in voting
  6. View election results

NOMINEES:
  1. Login with nominee credentials
  2. Apply for a position in election
  3. Upload campaign materials
  4. Schedule campaign events
  5. View voting progress
  6. Monitor election results

TROUBLESHOOTING
---------------
1. "Database Connection Error"
   - Check Database.php configuration
   - Verify MySQL server is running
   - Confirm database exists

2. "Permission Denied" on file uploads
   - Check file permissions on /public/uploads/
   - Ensure web server user has write access
   - Run: chmod -R 755 public/uploads/

3. "Session Expired"
   - Check SessionHelper.php timeout settings
   - Clear browser cookies and login again
   - Verify PHP session directory permissions

4. "Route Not Found"
   - Ensure mod_rewrite is enabled on Apache
   - Check .htaccess file exists in /public/
   - Verify web.php route definitions

DEPENDENCIES
------------
- PHPMailer - Email sending
- SimplePager - Pagination handling
- SimpleImage - Image processing
- Composer Autoloader - PSR-4 class loading

SUPPORT & MAINTENANCE
---------------------
For bugs, feature requests, or support, please contact:
[Your support email/contact information]

Last Updated: November 26, 2025
Version: 1.0

================================================================================
