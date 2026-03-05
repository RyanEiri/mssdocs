<?php
// Copy this file to config.php and fill in your values.

// Database configuration
define("DB_HOST", "localhost");
define("DB_USER", "your_db_user");
define("DB_PASS", "your_db_password");
define("DB_NAME", "your_db_name");

// Site branding
define("SITE_NAME",        "Your Project Name");       // Full name — used in navbar
define("SITE_ABBR",        "YPN");                     // Abbreviation — used in page titles
define("SITE_DESCRIPTION", "A brief description of your project.");
define("COPYRIGHT_HOLDER", "Your Name or Organization");
define("COPYRIGHT_YEARS",  date('Y'));                 // Or a fixed string like "2020-2024"

// Security secrets — replace ALL of these before deploying.
// KEYMAKER_SECRET: used to sign backup download URLs. Generate with:
//   openssl rand -hex 64
define("KEYMAKER_SECRET", "replace-with-output-of-openssl-rand-hex-64");

// GATE_COOKIE_NAME / GATE_COOKIE_VALUE: used by .htaccess as a fast pre-PHP auth gate.
// Must match the values in your .htaccess (copy .htaccess.example → .htaccess and set the same values).
// Generate each with: openssl rand -hex 16
define("GATE_COOKIE_NAME",  "replace_with_random_string");
define("GATE_COOKIE_VALUE", "replace_with_random_string");

define("PROGRAM_MYSQL_CLASSES", CLASSES_DIR.'classes.php');
require_once(PROGRAM_MYSQL_CLASSES);

// Set the main database connection
$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
