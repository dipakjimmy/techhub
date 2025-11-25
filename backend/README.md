# Backend Package (Contact Form + Admin)

## What this package includes
- backend/config.php          (contains placeholders for DB + admin hash)
- backend/save_message.php   (endpoint that stores incoming form data)
- backend/admin/login.php    (admin login page)
- backend/admin/dashboard.php(admin dashboard, shows messages)
- backend/admin/logout.php   (logout handler)
- backend/create_table.sql   (SQL script to create the messages table)

## Important: Add your database credentials
Open `backend/config.php` and replace the placeholders:

```
$DB_HOST = "DB_HOST_HERE";
$DB_USER = "DB_USER_HERE";
$DB_PASS = "DB_PASS_HERE";
$DB_NAME = "DB_NAME_HERE";
```

Example:
```
$DB_HOST = "localhost";
$DB_USER = "myuser";
$DB_PASS = "mypassword";
$DB_NAME = "mydatabase";
```

## How to generate a secure password hash for admin
**Do not store plain passwords.** Use PHP's `password_hash()` to create a hash and paste it into `backend/config.php` as `$ADMIN_PASS_HASH`.

Using CLI (if you have PHP installed locally):
```bash
php -r "echo password_hash('YourChosenPassword', PASSWORD_DEFAULT) . PHP_EOL;"
```
Example:
```bash
php -r "echo password_hash('Dipak!Secure', PASSWORD_DEFAULT) . PHP_EOL;"
```
Copy the output (it starts with `$2y$...`) and paste into `backend/config.php`:
```php
$ADMIN_PASS_HASH = '$2y$10$exampleHashedString...';
```

Alternatively, create a small PHP file `hash.php`:
```php
<?php
echo password_hash('Dipak!Secure', PASSWORD_DEFAULT);
?>
```
Open it in browser (only temporarily), copy hash, then delete `hash.php`.

## SQL: create table
Import `backend/create_table.sql` into your database (phpMyAdmin or CLI).

## Where to upload
Place the `backend/` folder inside your site document root (usually `public_html/` or `www/`):
```
public_html/backend/
```
Your admin URL will be:
```
https://yourdomain.com/backend/admin/login.php
```
The form endpoint (frontend) should post to:
```
https://yourdomain.com/backend/save_message.php
```

## Test flow
1. Upload files
2. Update `backend/config.php` with DB credentials and paste `$ADMIN_PASS_HASH`
3. Import `create_table.sql`
4. Open admin login, log in with username `dipak` and the password you chose (use the hash you generated)
5. Submit frontend form to `save_message.php` and messages will appear in admin

## Notes & security
- After setup, delete any temporary `hash.php` files used to generate the hash.
- Keep `config.php` permissions restricted on the server (default 644 is usually fine).
- Consider enabling HTTPS, and using a more advanced auth (2FA) for production.
