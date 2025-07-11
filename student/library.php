<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get issued books
$issued_books = [];
$result = $conn->query("SELECT b.title, b.author, bi.issue_date, bi.due_date, 
                        DATEDIFF(bi.due_date, CURDATE()) as days_remaining
                        FROM book_issues bi
                        JOIN library_books b ON bi.book_id = b.id
                        WHERE bi.student_id = $student_id AND bi.status = 'issued'");
while ($row = $result->fetch_assoc()) {
    $issued_books[] = $row;
}

// Get book return history
$return_history = [];
$result = $conn->query("SELECT b.title, b.author, bi.issue_date, bi.return_date, 
                        bi.due_date, bi.status
                        FROM book_issues bi
                        JOIN library_books b ON bi.book_id = b.id
                        WHERE bi.student_id = $student_id AND bi.status != 'issued'
                        ORDER BY bi.return_date DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $return_history[] = $row;
}

// Get available books
$available_books = [];
$result = $conn->query("SELECT id, title, author, category 
                        FROM library_books 
                        WHERE available > 0
                        ORDER BY title LIMIT 20");
while ($row = $result->fetch_assoc()) {
    $available_books[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
 <!-- Header -->
 <header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/images/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-xl font-bold">School ERP</h1>
                    <p class="text-green-200">Student Marks</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="bg-white text-green-600 px-4 py-2 rounded-lg font-semibold hover:bg-green-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Panel
                </a>
                <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg">
                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                </a>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Library Management</h1>
            
            <!-- Currently Issued Books -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">My Issued Books</h2>
                <?php if (empty($issued_books)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book-open text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">No books currently issued</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued On</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Remaining</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($issued_books as $book): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($book['title']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($book['author']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['issue_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['due_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $book['days_remaining'] > 3 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $book['days_remaining'] > 0 ? $book['days_remaining'] . ' days' : 'Overdue' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Return History -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Return History</h2>
                <?php if (empty($return_history)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">No return history available</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued On</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned On</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($return_history as $book): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($book['title']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($book['author']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['issue_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($book['return_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $book['status'] === 'returned' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst($book['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Books -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Available Books</h2>
                <?php if (empty($available_books)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-book text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">No books currently available</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($available_books as $book): ?>
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <h3 class="font-medium text-lg"><?= htmlspecialchars($book['title']) ?></h3>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($book['author']) ?></p>
                                <p class="text-xs text-gray-500 mt-1">Category: <?= htmlspecialchars($book['category']) ?></p>
                                <form method="post" action="request_issue.php" class="inline">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button type="submit" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-plus-circle mr-1"></i> Request Issue
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4 text-center">
                        <a href="library_catalog.php" class="text-green-600 hover:text-green-800 text-sm font-medium">
                            View Full Catalog <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>