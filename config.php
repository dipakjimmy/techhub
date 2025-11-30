<?php
// config.php - put real credentials here

// === Database (MySQL) ===
define('DB_HOST', '127.0.0.1');    // or 'localhost'
define('DB_USER', 'root');         // default XAMPP root user
define('DB_PASS', '');             // default XAMPP root has empty password
define('DB_NAME', 'contact_db');   // database we'll create

// === SMTP (PHPMailer) ===
// Example: Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'dipak.zagade8@gmail.com');   // your SMTP username (email)
define('SMTP_PASS', 'jdie mnxa uump inne');    // Gmail App Password (16 chars) or SMTP password
define('SMTP_PORT', 587);                         // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls');                     // 'tls' or 'ssl'

// ---- ADMIN LOGIN ----
// Username:
define('ADMIN_USER', 'dipak');    

// Recipient (where site messages are sent)
define('RECIPIENT_EMAIL', 'dipak.zagade8@gmail.com');
define('RECIPIENT_NAME', 'Dipak / ASISA');
define('ADMIN_PASS_HASH', '$2y$10$xSeFWYpZrA0rKH5z1auIY.6U/63/1NNcjiFSCc.W9Me06wDb6vIFa');
