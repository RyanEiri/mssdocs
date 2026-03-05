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

define("PROGRAM_MYSQL_CLASSES", CLASSES_DIR.'classes.php');
require_once(PROGRAM_MYSQL_CLASSES);

// Set the main database connection
$db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
?>
