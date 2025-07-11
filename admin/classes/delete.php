<?php
// admin/classes/delete.php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get class ID from URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$classId = $_GET['id'];

// Check if class has students
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
$stmt->bind_param("i", $classId);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

if ($count > 0) {
    $_SESSION['error'] = "Cannot delete class with students! Move students to another class first.";
    header("Location: index.php");
    exit;
}

// Delete class
$stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
$stmt->bind_param("i", $classId);

if ($stmt->execute()) {
    $_SESSION['success'] = "Class deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting class: " . $conn->error;
}

header("Location: index.php");
exit;
