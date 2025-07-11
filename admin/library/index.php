<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

// Fetch all books
$books = $conn->query("SELECT * FROM library_books ORDER BY title")->fetch_all(MYSQLI_ASSOC);
// Fetch all issued books with student info
$issued = $conn->query("SELECT b.title, b.author, u.full_name as student, bi.issue_date, bi.due_date, bi.return_date, bi.status, bi.id as issue_id FROM book_issues bi JOIN library_books b ON bi.book_id = b.id JOIN users u ON bi.student_id = u.id WHERE bi.status != 'returned' ORDER BY bi.due_date")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/images/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Library Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Books Catalog</h2>
                <a href="add.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus mr-2"></i>Add Book
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Title</th>
                            <th class="px-4 py-2">Author</th>
                            <th class="px-4 py-2">Category</th>
                            <th class="px-4 py-2">Available</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td class="px-4 py-2 font-semibold text-blue-700"><?= htmlspecialchars($book['title']) ?></td>
                            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($book['author']) ?></td>
                            <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($book['category']) ?></td>
                            <td class="px-4 py-2 text-gray-900"><?= $book['available'] ?>/<?= $book['quantity'] ?></td>
                            <td class="px-4 py-2">
                                <a href="edit.php?id=<?= $book['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                <a href="delete.php?id=<?= $book['id'] ?>" class="text-red-600 hover:text-red-900 mr-2" onclick="return confirm('Delete this book?')">Delete</a>
                                <a href="issue.php?id=<?= $book['id'] ?>" class="text-green-600 hover:text-green-900">Issue</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Currently Issued Books</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2">Book</th>
                            <th class="px-4 py-2">Student</th>
                            <th class="px-4 py-2">Issue Date</th>
                            <th class="px-4 py-2">Due Date</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($issued as $row): ?>
                        <tr>
                            <td class="px-4 py-2 font-semibold text-blue-700"><?= htmlspecialchars($row['title']) ?> <span class="text-xs text-gray-400">by <?= htmlspecialchars($row['author']) ?></span></td>
                            <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($row['student']) ?></td>
                            <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($row['issue_date']) ?></td>
                            <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($row['due_date']) ?></td>
                            <td class="px-4 py-2">
                                <?php if ($row['status'] === 'overdue' || (strtotime($row['due_date']) < time() && !$row['return_date'])): ?>
                                    <span class="inline-flex items-center px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">Overdue</span>
                                <?php elseif ($row['status'] === 'issued'): ?>
                                    <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Issued</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Returned</span>
                                <?php endif; ?>
                                <?php if ($row['status'] !== 'returned'): ?>
                                    <a href="return.php?id=<?= $row['issue_id'] ?? $row['id'] ?>" class="ml-2 text-blue-600 hover:text-blue-900 underline">Return</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html> 