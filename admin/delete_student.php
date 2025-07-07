<?php
require_once '../db.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Optionally, delete photo file here if you want
    $conn->query("DELETE FROM students WHERE id = $id");
    $_SESSION['success'] = 'Student deleted successfully!';
}
header('Location: students.php');
exit(); 