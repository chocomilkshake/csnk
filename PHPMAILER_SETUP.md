# PHPMailer Setup Guide for CSNK Contact Form

## Installation Complete ✓

PHPMailer v6.12.0 has been installed and is ready to use.

## Configuration Steps

### 1. **Gmail SMTP Setup** (Recommended)

If you're using Gmail, follow these steps:

#### Step 1: Enable 2-Factor Authentication

- Go to [Google Account](https://myaccount.google.com/)
- Navigate to **Security** (left sidebar)
- Enable **2-Step Verification** if not already enabled

#### Step 2: Generate App Password

- Go to [App Passwords](https://myaccount.google.com/apppasswords)
- Select **Mail** and **Windows Computer** (or your device)
- Click **Generate**
- Copy the 16-character password provided

#### Step 3: Update Configuration

Edit `view/contactUs.php` and update the CONFIG array:

```php
$CONFIG = [
    'to_email'      => 'csnkmanila@gmail.com',      // Recipient email
    'from_email'    => 'csnkmanila@gmail.com',     // Your Gmail address
    'from_name'     => 'CSNK Manpower Agency',
    'subject'       => 'CSNK Contact Form Submission',
    'max_message'   => 500,
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,
    'smtp_user'     => 'csnkmanila@gmail.com',     // Your Gmail address
    'smtp_pass'     => 'hqyp ljaf kwyd fkzo',        // Paste your 16-char App Password here
    'smtp_encrypt'  => PHPMailer::ENCRYPTION_STARTTLS,
    'enable_mail'   => true,
];
```

### 2. **Using Environment Variables** (More Secure)

Instead of hardcoding credentials, use `.env` file:

1. Install `vlucas/phpdotenv`:

   ```bash
   composer require vlucas/phpdotenv
   ```

2. Update your `.env` file with:

   ```
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=csnkmanila06@gmail.com
   MAIL_PASSWORD=xxxx xxxx xxxx xxxx
   MAIL_FROM_EMAIL=csnkmanila06@gmail.com
   MAIL_FROM_NAME=CSNK Manpower Agency
   MAIL_TO_EMAIL=CSNKSupport@gmail.com
   MAIL_ENCRYPTION=tls
   ```

3. Create a config file `includes/mail-config.php`:

   ```php
   <?php
   require_once __DIR__ . '/../vendor/autoload.php';

   $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
   $dotenv->load();

   return [
       'smtp_host'     => $_ENV['MAIL_HOST'],
       'smtp_port'     => (int)$_ENV['MAIL_PORT'],
       'smtp_user'     => $_ENV['MAIL_USERNAME'],
       'smtp_pass'     => $_ENV['MAIL_PASSWORD'],
       'from_email'    => $_ENV['MAIL_FROM_EMAIL'],
       'from_name'     => $_ENV['MAIL_FROM_NAME'],
       'to_email'      => $_ENV['MAIL_TO_EMAIL'],
       'smtp_encrypt'  => PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
   ];
   ```

### 3. **Alternative SMTP Services**

**Sendgrid:**

```php
'smtp_host'     => 'smtp.sendgrid.net',
'smtp_port'     => 587,
'smtp_user'     => 'apikey',
'smtp_pass'     => 'your_sendgrid_api_key',
```

**Mailtrap (Testing):**

```php
'smtp_host'     => 'smtp.mailtrap.io',
'smtp_port'     => 2525,
'smtp_user'     => 'your_mailtrap_user',
'smtp_pass'     => 'your_mailtrap_password',
```

## Features Implemented

✓ **PHPMailer Integration** - Secure SMTP email sending
✓ **CSRF Protection** - Token-based form validation
✓ **Input Validation** - All fields validated on both client & server
✓ **Honeypot Field** - Bot protection
✓ **Character Limit** - Message limited to 500 characters
✓ **Error Handling** - User-friendly error messages
✓ **Email Validation** - Proper email format checking
✓ **Phone Validation** - Philippine phone number support
✓ **Success Toast** - Visual feedback on successful submission
✓ **Loading State** - Submit button shows loading indicator
✓ **IME Support** - Proper handling of Asian input methods

## Testing

To test without sending actual emails:

In `contactUs.php`, change:

```php
'enable_mail'   => false,  // Set to false for testing
```

This will simulate successful submissions without sending emails.

## Troubleshooting

| Issue                   | Solution                                                 |
| ----------------------- | -------------------------------------------------------- |
| "Authentication failed" | Check your Gmail App Password is correct (16 characters) |
| "Connection refused"    | Verify SMTP host and port are correct for your service   |
| "SMTP not working"      | Ensure 2-Factor Authentication is enabled on Gmail       |
| Emails not received     | Check spam folder, verify recipient email is correct     |
| SSL/TLS errors          | Ensure PHP OpenSSL extension is enabled in php.ini       |

## Security Notes

⚠️ **Never commit credentials to Git!**

1. Add to `.gitignore`:

   ```
   .env
   vendor/
   composer.lock
   ```

2. Use environment variables in production
3. Rotate App Passwords regularly
4. Monitor CSNK Gmail account for suspicious activity

## Support

For issues with PHPMailer, visit: https://github.com/PHPMailer/PHPMailer
