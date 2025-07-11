<?php
// db.php - Database connection for School ERP
$host = 'localhost';
$user = 'root';
$pass = 'password';
$dbname = 'school_erp';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
