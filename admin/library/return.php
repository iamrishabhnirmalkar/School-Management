<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid+issue+ID');
    exit;
}
$issue_id = intval($_GET['id']);

// Fetch issue info
$issue = $conn->query("SELECT * FROM book_issues WHERE id=$issue_id AND status!='returned'")->fetch_assoc();
if (!$issue) {
    header('Location: index.php?error=Issue+not+found+or+already+returned');
    exit;
}
$book_id = $issue['book_id'];

// Mark as returned
if ($conn->query("UPDATE book_issues SET status='returned', return_date=NOW() WHERE id=$issue_id") &&
    $conn->query("UPDATE library_books SET available = available + 1 WHERE id=$book_id")) {
    header('Location: index.php?success=Book+returned+successfully');
    exit;
} else {
    header('Location: index.php?error=Failed+to+return+book');
    exit;
} 