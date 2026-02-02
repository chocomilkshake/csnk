# CSNK Admin System

A comprehensive admin management system for maid and nanny applicants. Built with pure PHP, Bootstrap, and MySQL.

## Features

### Dashboard
- Real-time statistics
- Recent applicants overview
- Quick action buttons
- System status monitoring

### Applicants Management
- Complete CRUD operations (Create, Read, Update, Delete)
- Applicant list with filtering
- Status management (Pending, On Process, Approved)
- Soft delete with restore functionality
- Excel export functionality
- Document upload and management

### Settings
- Admin accounts management
- User profile management
- Password change functionality
- Avatar upload

### Security
- Secure authentication system
- Password hashing
- Session management
- Activity logging
- File upload validation

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- XAMPP (recommended for local development)

## Installation Instructions

### 1. Extract Files

Extract all files to your XAMPP htdocs directory:
```
C:\xampp\htdocs\CSNK\
```

### 2. Create Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Click on "Import" tab
3. Choose the `CSNK.sql` file from the project root
4. Click "Go" to import the database

Alternatively, you can create the database manually:
- Create a new database named `CSNK`
- Import the `CSNK.sql` file

### 3. Configure Database Connection

Open `includes/config.php` and update the database credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'CSNK');
```

### 4. Set Permissions

Make sure the `uploads` directory is writable:
- Right-click on the `uploads` folder
- Properties > Security
- Give write permissions to the web server user

On Linux/Mac:
```bash
chmod -R 755 uploads/
```

### 5. Access the System

Open your web browser and navigate to:
```
http://localhost/CSNK/
```

### Default Login Credentials

- **Username:** admin
- **Password:** admin123

**IMPORTANT:** Change the default password immediately after first login!

## Directory Structure

```
CSNK/
├── includes/
│   ├── config.php          # Configuration settings
│   ├── Database.php        # Database connection class
│   ├── Auth.php           # Authentication class
│   ├── Applicant.php      # Applicant management class
│   ├── Admin.php          # Admin management class
│   ├── functions.php      # Helper functions
│   ├── header.php         # Page header template
│   └── footer.php         # Page footer template
├── uploads/
│   ├── applicants/        # Applicant photos
│   ├── documents/         # Applicant documents
│   └── avatars/           # Admin avatars
├── login.php              # Login page
├── logout.php             # Logout handler
├── dashboard.php          # Dashboard page
├── applicants.php         # Applicants list
├── add-applicant.php      # Add new applicant
├── edit-applicant.php     # Edit applicant
├── view-applicant.php     # View applicant details
├── on-process.php         # On process applicants
├── pending.php            # Pending applicants
├── deleted.php            # Deleted applicants
├── export-excel.php       # Excel export handler
├── accounts.php           # Admin accounts management
├── profile.php            # User profile
├── CSNK.sql              # Database schema
└── README.md             # This file
```

## Usage Guide

### Adding a New Applicant

1. Navigate to "Applicants" > "List of Applicants"
2. Click "Add New Applicant" button
3. Fill in all required fields:
   - First Name, Last Name (required)
   - Phone Number (required)
   - Date of Birth (required)
   - Address (required)
4. Upload profile picture and documents (optional)
5. Click "Save Applicant"

### Managing Applicant Status

Applicants can have three statuses:
- **Pending:** Newly added applicants awaiting review
- **On Process:** Applicants currently being hired
- **Approved:** Applicants who have been approved

To change status:
1. Click "Edit" on the applicant row
2. Change the "Status" dropdown
3. Click "Update Applicant"

### Exporting to Excel

1. Navigate to any applicant list page
2. Click "Export Excel" button
3. The file will download automatically
4. Open with Microsoft Excel or compatible software

### Adding Admin Accounts

1. Navigate to "Settings" > "Accounts"
2. Click "Add New Account"
3. Fill in the required information
4. Select appropriate role:
   - **Employee:** Basic access
   - **Admin:** Full access
   - **Super Admin:** Full system access
5. Click "Create Account"

### Updating Your Profile

1. Navigate to "Settings" > "Profile"
2. Update your information
3. Upload a new avatar (optional)
4. Click "Update Profile"

### Changing Password

1. Navigate to "Settings" > "Profile"
2. Scroll to "Change Password" section
3. Enter current password
4. Enter new password (minimum 6 characters)
5. Confirm new password
6. Click "Change Password"

## Security Best Practices

1. **Change default password immediately**
2. Use strong passwords (mix of letters, numbers, symbols)
3. Don't share admin credentials
4. Regularly backup your database
5. Keep PHP and MySQL updated
6. Use HTTPS in production environment

## Troubleshooting

### Cannot login
- Check database connection in `includes/config.php`
- Verify database is imported correctly
- Clear browser cookies and cache

### File upload not working
- Check `uploads/` directory permissions
- Verify PHP upload settings in `php.ini`:
  - `upload_max_filesize = 10M`
  - `post_max_size = 10M`
- Restart Apache after changing settings

### Images not displaying
- Check file paths in `includes/config.php`
- Verify `APP_URL` matches your local setup
- Check file permissions on `uploads/` directory

### Database connection error
- Verify MySQL service is running
- Check database credentials in `includes/config.php`
- Ensure CSNK database exists

## Support

For issues or questions:
1. Check this README file
2. Review error messages in browser console
3. Check Apache error logs in `C:\xampp\apache\logs\error.log`

## License

This project is for educational and internal use only.

## Version

Version 1.0.0 - Initial Release
