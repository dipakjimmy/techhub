<?php
// ====== configuration ======
// Please fill these values with your database credentials BEFORE uploading.
// DB_HOST: e.g. 'localhost' or 'mysql.hostinger.com'
// DB_USER: your database username
// DB_PASS: your database password
// DB_NAME: your database name
$DB_HOST = "DB_HOST_HERE";
$DB_USER = "DB_USER_HERE";
$DB_PASS = "DB_PASS_HERE";
$DB_NAME = "DB_NAME_HERE";

// Admin credentials (do not change variable names)
// Username:
$ADMIN_USER = "dipak";
// For security we recommend storing a password hash here.
// Generate a hash using the instructions in README.md and paste it below:
$ADMIN_PASS_HASH = "PASTE_YOUR_PASSWORD_HASH_HERE";

// ====== DO NOT EDIT BELOW UNLESS YOU KNOW WHAT YOU'RE DOING ======
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
// set charset
$conn->set_charset("utf8mb4");
?>
