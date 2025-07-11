<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header('Location: ../logout.php');
    exit;
}
$student_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_id']) || !is_numeric($_POST['book_id'])) {
    header('Location: library.php?error=Invalid+request');
    exit;
}
$book_id = intval($_POST['book_id']);

// Check if already issued
$already_issued = $conn->query("SELECT 1 FROM book_issues WHERE book_id=$book_id AND student_id=$student_id AND status='issued'")->num_rows > 0;
if ($already_issued) {
    header('Location: library.php?error=Book+already+issued+to+you');
    exit;
}
// Check if request already exists and is pending
$pending = $conn->query("SELECT 1 FROM book_issue_requests WHERE book_id=$book_id AND student_id=$student_id AND status='pending'")->num_rows > 0;
if ($pending) {
    header('Location: library.php?error=You+already+requested+this+book');
    exit;
}
// Insert request
$stmt = $conn->prepare("INSERT INTO book_issue_requests (book_id, student_id, request_date, status) VALUES (?, ?, NOW(), 'pending')");
$stmt->bind_param('ii', $book_id, $student_id);
if ($stmt->execute()) {
    header('Location: library.php?success=Request+sent+to+admin');
    exit;
} else {
    header('Location: library.php?error=Failed+to+send+request');
    exit;
} 