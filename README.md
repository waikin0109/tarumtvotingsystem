# TARUMT Voting System

A comprehensive web-based application designed to manage and conduct student elections with support for multiple user roles (Admin, Student, Nominee), voting sessions, candidate applications, campaign materials, and election results reporting.

## Table of Contents

- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [Key Features](#key-features)
- [Main Routes](#main-routes)
- [Configuration](#configuration)
- [Security Features](#security-features)
- [Usage Guidelines](#usage-guidelines)
- [Troubleshooting](#troubleshooting)
- [Dependencies](#dependencies)

---

## System Requirements

- **PHP** 7.4 or higher
- **MySQL/MariaDB** Database
- **Apache Web Server** (with mod_rewrite enabled)
- **Composer** (PHP Package Manager)
- **Modern Web Browser** (Chrome, Firefox, Safari, Edge)

---

## Installation

1. Clone or extract the project to your web server directory
2. Navigate to the project root directory
   ```bash
   cd tarumtvotingsystem/app
   ```
3. Run the following command:
   ```bash
   composer install
   ```
4. Configure your database connection in `Database.php`
5. Import the database schema (if provided)
6. Set proper file permissions for uploads and images directories:
   ```bash
   chmod -R 755 public/uploads/
   chmod -R 755 images/
   ```
7. Access the application via: `http://localhost/tarumtvotingsystem/`

---

## Project Structure

```
/app/
├── Controller/                          # Route handlers and business logic
│   ├── AdminController/                # Admin-related operations
│   ├── NomineeController/              # Nominee profile and settings
│   ├── StudentController/              # Student profile and voting
│   ├── VotingController/               # Election events, voting, results
│   ├── ResultController/               # Statistics and reports
│   ├── CampaignHandlingController/     # Campaign materials & scheduling
│   └── NomineeHandlingController/      # Registration forms
├── Model/                              # Database interaction layer
│   ├── AdminModel/
│   ├── NomineeModel/
│   ├── StudentModel/
│   ├── VotingModel/
│   ├── ResultModel/
│   ├── CampaignHandlingModel/
│   └── NomineeHandlingModel/
├── View/                               # HTML templates and UI components
│   ├── AdminView/
│   ├── NomineeView/
│   ├── StudentView/
│   ├── VotingView/
│   ├── ResultView/
│   ├── CampaignHandlingView/
│   ├── NomineeHandlingView/
│   ├── LoginView/
│   └── NomineeView/
├── Library/                            # Third-party utilities
│   ├── PHPMailer.php
│   ├── SMTP.php
│   ├── SimplePager.php
│   └── SimpleImage.php
├── Utilities/                          # Helper functions
│   └── functions.php
├── Service/                            # Business logic services
├── public/                             # Web-accessible directory
│   ├── index.php                       # Main entry point
│   ├── css/                            # Stylesheets
│   ├── js/                             # JavaScript files
│   └── uploads/                        # User-uploaded files
│       ├── academic_document/
│       └── campaign_material/
├── images/                             # Application images
├── web.php                             # Route definitions
├── Route.php                           # Route handler class
├── Database.php                        # Database connection class
├── FileHelper.php                      # File handling utilities
├── SessionHelper.php                   # Session management
├── _base.php                           # Base configuration
└── autoload.php                        # PSR-4 autoloader
```

---

## Key Features

### 1. Authentication & User Roles
- **Admin**: System administration and election management
- **Student**: Voting and candidate information viewing
- **Nominee**: Candidate profile management and application

### 2. Election Management
- Create and manage election events
- Schedule voting sessions with date/time constraints
- Set election rules and regulations
- Manage vote sessions and ballots

### 3. Nominee Management
- Student registration as nominees
- Nominee application approval/rejection
- Publish final nominee list
- Manifesto and profile management

### 4. Campaign Management
- Upload and manage campaign materials
- Schedule campaign events and locations
- Approve/reject campaign materials
- View campaign timetables

### 5. Voting System
- Secure ballot casting
- Vote session scheduling
- Real-time voting participation
- Fraud prevention measures

### 6. Results & Reporting
- View statistical voting data
- Generate official election results
- Faculty-based result breakdowns
- Early voting status reports
- Comprehensive election reports

---

## Main Routes

### Login & Authentication
| Method | Route | Description |
|--------|-------|-------------|
| GET/POST | `/login` | User login |
| GET | `/logout` | User logout |

### Dashboards
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/home` | Admin dashboard |
| GET | `/student/home` | Student dashboard |
| GET | `/nominee/home` | Nominee dashboard |

### Election Events (Admin)
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/election-event` | List events |
| GET/POST | `/admin/election-event/create` | Create event |
| GET/POST | `/admin/election-event/edit/{id}` | Edit event |
| GET | `/admin/election-event/view/{id}` | View event |
| POST | `/admin/election-event/delete/{id}` | Delete event |

### Rules & Regulations
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/rule` | Admin: List rules |
| GET | `/student/rule` | Student: View rules |
| GET | `/nominee/rule` | Nominee: View rules |
| GET/POST | `/admin/rule/create` | Admin: Create rule |
| GET/POST | `/admin/rule/edit/{id}` | Admin: Edit rule |
| GET | `/admin/rule/view/{id}` | Admin: View rule |
| POST | `/admin/rule/delete/{id}` | Admin: Delete rule |

### Voting Sessions
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/vote-session` | List voting sessions |
| GET/POST | `/vote-session/create` | Create session |
| GET/POST | `/vote-session/edit/{id}` | Edit session |
| GET | `/vote-session/details/{id}` | View session details |
| POST | `/vote-session/schedule` | Schedule session |
| POST | `/vote-session/cancel` | Cancel session |
| GET | `/ballot/start/{id}` | Start voting |
| POST | `/ballot/cast/{id}` | Cast vote |

### Nominees & Applications
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/admin/nominee-application` | Admin: List applications |
| GET/POST | `/admin/nominee-application/create` | Admin: Create application |
| GET/POST | `/admin/nominee-application/edit/{id}` | Admin: Edit application |
| POST | `/admin/nominee-application/accept/{id}` | Admin: Approve nominee |
| POST | `/admin/nominee-application/reject/{id}` | Admin: Reject nominee |
| GET | `/student/election-registration-form` | Student: View forms |
| GET/POST | `/student/election-registration-form/register/{id}` | Student: Register |
| GET | `/nominee/election-registration-form` | Nominee: Manage registration |

### Campaign Materials
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/nominee/campaign-material` | Nominee: List materials |
| GET/POST | `/nominee/campaign-material/create` | Nominee: Create material |
| GET | `/admin/campaign-material` | Admin: List materials |
| POST | `/admin/campaign-material/accept/{id}` | Admin: Approve material |
| POST | `/admin/campaign-material/reject/{id}` | Admin: Reject material |

### Schedule & Locations
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/nominee/schedule-location` | Nominee: Manage schedule |
| GET/POST | `/nominee/schedule-location/create` | Nominee: Create schedule |
| GET | `/student/schedule-location` | Student: View campaign timetable |
| GET | `/admin/schedule-location` | Admin: Manage schedules |
| GET/POST | `/admin/schedule-location/create` | Admin: Create schedule |

### Results & Reports
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/statistics` | View voting statistics |
| GET | `/results` | View final results |
| GET | `/admin/reports/generator` | Generate reports |
| GET | `/admin/reports/list` | List all reports |

### Announcements
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/announcements` | Admin: List announcements |
| GET/POST | `/announcement/create` | Admin: Create announcement |
| GET | `/announcements/public` | Student/Nominee: View announcements |

### User Profiles
| Method | Route | Description |
|--------|-------|-------------|
| GET | `/{role}/profile` | View profile |
| POST | `/{role}/profile/update-password` | Update password |
| POST | `/{role}/profile/update-photo` | Update profile photo |

---

## Configuration

### Database Connection
Edit `Database.php` with your database credentials:
```php
$host = 'localhost';
$database = 'tarumtvs';
$username = 'root';
$password = 'your_password';
```

### Session Configuration
Configure in `SessionHelper.php`:
- Session timeout duration
- Cookie settings
- CSRF token handling

### Mail Configuration
Configure in `library/PHPMailer.php`:
- SMTP settings for election notifications
- Email templates

---

## Database Schema

The system uses multiple tables to manage:
- User credentials (admins, students, nominees)
- Election events and configurations
- Voting sessions and ballots
- Nominee applications and data
- Campaign materials and schedules
- Rules and regulations
- Announcements and messages
- Uploaded files and media

*Refer to database migration/schema files for detailed table structures.*

---

## File Upload Directories

```
/public/uploads/
├── academic_document/   # Nominee academic documents
└── campaign_material/   # Campaign promotional materials
```

**Important**: Ensure these directories have proper write permissions:
```bash
chmod -R 755 public/uploads/
```

---

## Security Features

1. **User Authentication & Session Management**
2. **Role-Based Access Control (RBAC)**
3. **Input Validation & Sanitization**
4. **SQL Injection Prevention** (Prepared Statements)
5. **CSRF Token Protection**
6. **Secure Password Hashing**
7. **File Upload Validation**
8. **XSS Protection**

---

## Usage Guidelines

### For Admins
1. Login with admin credentials
2. Create and manage election events
3. Set rules and regulations
4. Manage nominee applications (accept/reject)
5. Approve campaign materials and schedules
6. Monitor voting sessions in real-time
7. Generate and view election reports

### For Students
1. Login with student credentials
2. View announcements and election rules
3. Apply to be a nominee (if eligible)
4. View candidate information and manifestos
5. Participate in voting during scheduled sessions
6. View election results after voting ends

### For Nominees
1. Login with nominee credentials
2. Apply for a position in the election
3. Upload campaign materials for approval
4. Schedule campaign events and locations
5. View voting progress and participation rates
6. Monitor and view final election results

---

## Troubleshooting

### Issue: Database Connection Error
**Solution**:
- Check `Database.php` configuration
- Verify MySQL server is running
- Confirm database `tarumtvs` exists
- Check username and password credentials

### Issue: Permission Denied on File Uploads
**Solution**:
- Check file permissions on `/public/uploads/`
- Ensure web server user has write access
- Run: `chmod -R 755 public/uploads/`

### Issue: Session Expired
**Solution**:
- Check `SessionHelper.php` timeout settings
- Clear browser cookies and login again
- Verify PHP session directory permissions

### Issue: Route Not Found (404)
**Solution**:
- Ensure mod_rewrite is enabled on Apache
- Check `.htaccess` file exists in `/public/`
- Verify route definitions in `web.php`
- Restart Apache web server

### Issue: Large File Upload Fails
**Solution**:
- Check PHP `php.ini` settings:
  ```ini
  upload_max_filesize = 50M
  post_max_size = 50M
  ```
- Ensure `/public/uploads/` directory permissions are correct

---

## Dependencies

- **PHPMailer** - Email sending for notifications
- **SimplePager** - Pagination handling for lists
- **SimpleImage** - Image processing and manipulation
- **Composer Autoloader** - PSR-4 class loading

---

## Support & Maintenance

For bugs, feature requests, or support, please contact:  
[Your support email/contact information]

---

**Last Updated**: November 26, 2025  
**Version**: 1.0  

---

*TARUMT Voting System - Student Election Management Platform*
