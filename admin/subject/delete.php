<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$subject_id = $_GET['id'];

// Check if subject exists
$stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "Subject not found!";
    header("Location: index.php");
    exit;
}

// Delete subject
$stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
$stmt->bind_param("i", $subject_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Subject deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting subject: " . $conn->error;
}

header("Location: index.php");
exit;
