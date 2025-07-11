<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM notices WHERE id = $id");
    header("Location: index.php");
    exit;
}

// Fetch all notices
$notices = $conn->query("SELECT n.*, u.full_name as author FROM notices n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notices - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                <div>
                    <h1 class="text-2xl font-bold">School ERP System</h1>
                    <p class="text-blue-200">Notices</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../../admin/dashboard.php" class="bg-white text-blue-700 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                </a>
                <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
            </div>
        </div>
    </header>
    <div class="container mx-auto px-6 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800">All Notices</h2>
            <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Create Notice</a>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <?php if (empty($notices)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-bell-slash text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">No notices found.</p>
                </div>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($notices as $notice): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap font-semibold text-blue-700"><?= htmlspecialchars($notice['title']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-700"><?= nl2br(htmlspecialchars($notice['content'])) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($notice['author']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?= htmlspecialchars($notice['created_at']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <a href="?delete=<?= $notice['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this notice?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
