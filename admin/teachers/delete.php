<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get teacher ID from URL
$teacher_id = $_GET['id'] ?? 0;

// Check if teacher exists
$teacher = $conn->query("SELECT * FROM users WHERE id = $teacher_id AND role = 'teacher'")->fetch_assoc();

if (!$teacher) {
    $_SESSION['error'] = "Teacher not found";
    header("Location: index.php");
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Start transaction
        $conn->begin_transaction();

        // First remove as class teacher if assigned
        $conn->query("UPDATE classes SET class_teacher_id = NULL WHERE class_teacher_id = $teacher_id");

        // Delete from teachers table first (due to foreign key constraint)
        $conn->query("DELETE FROM teachers WHERE user_id = $teacher_id");

        // Then delete from users table
        $conn->query("DELETE FROM users WHERE id = $teacher_id");

        $conn->commit();
        $_SESSION['success'] = "Teacher deleted successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting teacher: " . $e->getMessage();
    }
}

header("Location: index.php");
exit;
