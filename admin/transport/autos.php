<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'driver_name';
$order = $_GET['order'] ?? 'ASC';

// Base query
$query = "SELECT * FROM buses WHERE vehicle_type = 'auto_rickshaw'";

// Add filters
if (!empty($search)) {
    $query .= " AND (driver_name LIKE '%$search%' OR driver_phone LIKE '%$search%')";
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute query
$result = $conn->query($query);
$autos = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Rickshaw Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <!-- Same header as index.php -->
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <!-- Same sidebar as index.php -->
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Filter and Search Bar -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                    <h2 class="text-xl font-bold text-gray-800">Auto Rickshaw Management</h2>
                    <a href="add.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Auto
                    </a>
                </div>

                <form method="get" class="mb-6">
                    <div class="flex">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by driver name or phone"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Auto Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'driver_name', 'order' => $sort == 'driver_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Driver Name
                                        <?php if ($sort == 'driver_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vehicle Info
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($autos)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No auto rickshaws found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($autos as $auto): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($auto['driver_name']) ?></div>
                                            <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($auto['id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($auto['driver_phone']) ?></div>
                                            <?php if (!empty($auto['registration_number'])): ?>
                                                <div class="text-sm text-gray-500">Reg: <?= htmlspecialchars($auto['registration_number']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($auto['model'])): ?>
                                                <div class="text-sm text-gray-900"><?= htmlspecialchars($auto['model']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($auto['year'])): ?>
                                                <div class="text-sm text-gray-500">Year: <?= htmlspecialchars($auto['year']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($auto['tracking_enabled']): ?>
                                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Active</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded-full">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="view.php?id=<?= $auto['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?= $auto['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $auto['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this auto?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>