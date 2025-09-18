<?php
// Database configuration for INFINITY Bookshop
// WAMP Server settings

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'infinity_bookshop');

// Site configuration
define('SITE_NAME', 'INFINITY Bookshop');
define('SITE_DESCRIPTION', 'Educational Books & Stationery');
define('CURRENCY', 'Rs.');

// Session configuration
session_start();

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Colombo');

// Base URL (adjust according to your WAMP setup)
$base_url = 'http://localhost/infinity_bookshop/';
define('BASE_URL', $base_url);
?>