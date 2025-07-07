<?php
// db.php - Database connection for School ERP
$host = 'localhost';
$user = 'root';
$pass = 'password';
$dbname = 'school_erp';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
// Now $conn can be included and used in other files 