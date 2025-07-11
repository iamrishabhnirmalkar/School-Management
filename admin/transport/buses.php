<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'bus_number';
$order = $_GET['order'] ?? 'ASC';

// Base query
$query = "SELECT * FROM buses WHERE vehicle_type IN ('bus', 'minibus')";

// Add filters
if (!empty($search)) {
    $query .= " AND (bus_number LIKE '%$search%' OR route_name LIKE '%$search%' OR driver_name LIKE '%$search%')";
}
if (!empty($type_filter)) {
    $query .= " AND vehicle_type = '$type_filter'";
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute query
$result = $conn->query($query);
$buses = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bus Management - School ERP</title>
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
                    <h2 class="text-xl font-bold text-gray-800">Bus Fleet Management</h2>
                    <a href="add.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Bus
                    </a>
                </div>

                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Bus No. or Route or Driver"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Types</option>
                            <option value="bus" <?= $type_filter == 'bus' ? 'selected' : '' ?>>Regular Bus</option>
                            <option value="minibus" <?= $type_filter == 'minibus' ? 'selected' : '' ?>>Minibus</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <!-- Bus Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'bus_number', 'order' => $sort == 'bus_number' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Bus Number
                                        <?php if ($sort == 'bus_number'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Route
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Driver
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Capacity
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($buses)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No buses found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($buses as $bus): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-blue-600"><?= htmlspecialchars($bus['bus_number']) ?></div>
                                            <?php if ($bus['tracking_enabled']): ?>
                                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Tracking</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($bus['route_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars(substr($bus['stops'], 0, 30)) ?>...</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($bus['driver_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($bus['driver_phone']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($bus['capacity']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $type_classes = [
                                                'bus' => 'bg-blue-100 text-blue-800',
                                                'minibus' => 'bg-purple-100 text-purple-800'
                                            ];
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $type_classes[$bus['vehicle_type']] ?>">
                                                <?= ucfirst($bus['vehicle_type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="view.php?id=<?= $bus['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?= $bus['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $bus['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this bus?')"><i class="fas fa-trash"></i></a>
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