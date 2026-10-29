<?php
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'hospital');
}

$con = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

if (!$con) {
    die('Failed to connect to MySQL.');
}

mysqli_set_charset($con, 'utf8mb4');

require_once __DIR__ . '/hms_helpers.php';
?>
