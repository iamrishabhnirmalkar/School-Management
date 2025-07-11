<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Search and filter parameters
$search = $_GET['search'] ?? '';
$class_filter = $_GET['class'] ?? '';
$status_filter = $_GET['status'] ?? 'Active';
$sort = $_GET['sort'] ?? 'admission_number';
$order = $_GET['order'] ?? 'ASC';

// Base query
$query = "SELECT u.id, u.admission_number, u.full_name, u.email, u.phone, 
          c.class_name, c.section, s.roll_number, s.admission_date, s.status
          FROM users u
          JOIN students s ON u.id = s.user_id
          LEFT JOIN classes c ON s.class_id = c.id
          WHERE u.role = 'student'";

// Add filters
if (!empty($search)) {
    $query .= " AND (u.full_name LIKE '%$search%' OR u.admission_number LIKE '%$search%' OR u.email LIKE '%$search%')";
}
if (!empty($class_filter)) {
    $query .= " AND c.id = $class_filter";
}
if (!empty($status_filter)) {
    $query .= " AND s.status = '$status_filter'";
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute query
$result = $conn->query($query);
$students = $result->fetch_all(MYSQLI_ASSOC);

// Get classes for filter dropdown
$classes = $conn->query("SELECT id, class_name, section FROM classes")->fetch_all(MYSQLI_ASSOC);

// Export to Excel
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.xls"');

    echo "Admission No.\tName\tClass\tRoll No.\tAdmission Date\tStatus\tEmail\tPhone\n";
    foreach ($students as $student) {
        echo $student['admission_number'] . "\t";
        echo $student['full_name'] . "\t";
        echo ($student['class_name'] ?? '') . ($student['section'] ? ' ' . $student['section'] : '') . "\t";
        echo $student['roll_number'] . "\t";
        echo $student['admission_date'] . "\t";
        echo $student['status'] . "\t";
        echo $student['email'] . "\t";
        echo $student['phone'] . "\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header (same as dashboard) -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Student Management</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative group">
                        <div class="flex items-center space-x-2 cursor-pointer">
                            <img src="../../assets/img/admin-avatar.jpg" alt="Admin" class="w-8 h-8 rounded-full border-2 border-white">
                            <span><?= htmlspecialchars($_SESSION['user']['full_name']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-6 py-8 flex">
        <!-- Sidebar Navigation (same as dashboard) -->
        <aside class="w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../../admin/dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50 text-blue-700">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span>Back to Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-list w-5"></i>
                            <span>Student List</span>
                        </a>
                    </li>
                    <li>
                        <a href="create.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-user-plus w-5"></i>
                            <span>Add New Student</span>
                        </a>
                    </li>
                    <li>
                        <a href="import.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-file-import w-5"></i>
                            <span>Bulk Import</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Filter and Search Bar -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                    <h2 class="text-xl font-bold text-gray-800">Student Records</h2>

                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full md:w-auto">
                        <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i> Add Student
                        </a>
                        <a href="?export=1&<?= http_build_query($_GET) ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-excel mr-2"></i> Export
                        </a>
                    </div>
                </div>

                <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or Admission No."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $class_filter == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="Alumni" <?= $status_filter == 'Alumni' ? 'selected' : '' ?>>Alumni</option>
                            <option value="">All Statuses</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                    </div>
                </form>

                <!-- Student Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'admission_number', 'order' => $sort == 'admission_number' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Admission No.
                                        <?php if ($sort == 'admission_number'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'full_name', 'order' => $sort == 'full_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Name
                                        <?php if ($sort == 'full_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'class_name', 'order' => $sort == 'class_name' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Class
                                        <?php if ($sort == 'class_name'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Roll No.
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
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No students found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-blue-600"><?= htmlspecialchars($student['admission_number'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($student['full_name'] ?? '') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($student['email'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($student['class_name'] ?? '') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($student['section'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($student['roll_number'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_classes = [
                                                'Active' => 'bg-green-100 text-green-800',
                                                'Inactive' => 'bg-yellow-100 text-yellow-800',
                                                'Alumni' => 'bg-blue-100 text-blue-800'
                                            ];
                                            ?>
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $status_classes[$student['status'] ?? 'Active'] ?>">
                                                <?= htmlspecialchars($student['status'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="view.php?id=<?= $student['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?= $student['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $student['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this student?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination would go here -->
            </div>
        </main>
    </div>
</body>

</html>