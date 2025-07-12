<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=Invalid+book+ID');
    exit;
}
$book_id = intval($_GET['id']);

// Optional: Check if the book exists
$result = $conn->query("SELECT * FROM library_books WHERE id=$book_id");
if ($result->num_rows === 0) {
    header('Location: index.php?error=Book+not+found');
    exit;
}

// Delete the book
if ($conn->query("DELETE FROM library_books WHERE id=$book_id")) {
    header('Location: index.php?success=Book+deleted+successfully');
    exit;
} else {
    header('Location: index.php?error=Failed+to+delete+book');
    exit;
} 