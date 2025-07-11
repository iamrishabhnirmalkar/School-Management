<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../logout.php");
    exit;
}

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM library_books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) {
    echo '<div class="text-center py-8 text-red-600 font-bold">Book not found.</div>';
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $publisher = trim($_POST['publisher']);
    $edition = trim($_POST['edition']);
    $quantity = intval($_POST['quantity']);
    $category = trim($_POST['category']);
    $available = intval($_POST['available']);
    if ($title && $author && $quantity > 0 && $available >= 0 && $available <= $quantity) {
        $stmt = $conn->prepare("UPDATE library_books SET title=?, author=?, isbn=?, publisher=?, edition=?, quantity=?, available=?, category=? WHERE id=?");
        $stmt->bind_param("sssssiisi", $title, $author, $isbn, $publisher, $edition, $quantity, $available, $category, $book_id);
        if ($stmt->execute()) {
            $success = 'Book updated successfully!';
            header("Location: index.php");
            exit;
        } else {
            $error = 'Error updating book.';
        }
    } else {
        $error = 'Title, author, quantity, and available are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Edit Book</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Library
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <main class="container mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-xl mx-auto">
            <h2 class="text-xl font-bold mb-4 text-gray-800">Edit Book</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Author *</label>
                    <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ISBN</label>
                    <input type="text" name="isbn" value="<?= htmlspecialchars($book['isbn']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publisher</label>
                    <input type="text" name="publisher" value="<?= htmlspecialchars($book['publisher']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Edition</label>
                    <input type="text" name="edition" value="<?= htmlspecialchars($book['edition']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" min="1" value="<?= htmlspecialchars($book['quantity']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Available *</label>
                    <input type="number" name="available" min="0" max="<?= htmlspecialchars($book['quantity']) ?>" value="<?= htmlspecialchars($book['available']) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($book['category']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                </div>
                <div class="flex justify-end space-x-2">
                    <a href="index.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update Book</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 