<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get student ID from URL
$student_id = $_GET['id'] ?? 0;

// Check if student exists
$student = $conn->query("SELECT * FROM users WHERE id = $student_id AND role = 'student'")->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: index.php");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Delete from students table first (due to foreign key constraint)
        $conn->query("DELETE FROM students WHERE user_id = $student_id");

        // Then delete from users table
        $conn->query("DELETE FROM users WHERE id = $student_id");

        $conn->commit();
        $_SESSION['success'] = "Student deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }
}

header("Location: index.php");
exit;
