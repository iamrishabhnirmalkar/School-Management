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
$status_filter = $_GET['status'] ?? 'Active';
$sort = $_GET['sort'] ?? 'full_name';
$order = $_GET['order'] ?? 'ASC';

// Base query
$query = "SELECT u.id, u.login_id, u.full_name, u.email, u.phone, 
          t.joining_date, t.qualification_type, t.specialization,
          (SELECT COUNT(*) FROM classes WHERE class_teacher_id = u.id) as is_class_teacher
          FROM users u
          JOIN teachers t ON u.id = t.user_id
          WHERE u.role = 'teacher'";

// Add filters
if (!empty($search)) {
    $query .= " AND (u.full_name LIKE '%$search%' OR u.login_id LIKE '%$search%' OR u.email LIKE '%$search%')";
}

// Add sorting
$query .= " ORDER BY $sort $order";

// Execute query
$result = $conn->query($query);
$teachers = $result->fetch_all(MYSQLI_ASSOC);

// Export to Excel
if (isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="teachers_' . date('Y-m-d') . '.xls"');

    echo "Login ID\tName\tEmail\tPhone\tQualification\tSpecialization\tJoining Date\tClass Teacher\n";
    foreach ($teachers as $teacher) {
        echo $teacher['login_id'] . "\t";
        echo $teacher['full_name'] . "\t";
        echo $teacher['email'] . "\t";
        echo $teacher['phone'] . "\t";
        echo $teacher['qualification_type'] ?? '' . "\t";
        echo $teacher['specialization'] ?? '' . "\t";
        echo $teacher['joining_date'] . "\t";
        echo ($teacher['is_class_teacher'] > 0 ? 'Yes' : 'No') . "\n";
    }
    exit;
}
?>

<?php
$pageTitle = 'Teacher Management';
$activePage = 'teachers';
include '../_layout.php';
?>
        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Filter and Search Bar -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                    <h2 class="text-xl font-bold text-gray-800">Teacher Records</h2>

                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2 w-full md:w-auto">
                        <a href="create.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i> Add Teacher
                        </a>
                        <a href="?export=1&<?= http_build_query($_GET) ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-excel mr-2"></i> Export
                        </a>
                    </div>
                </div>

                <form method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Name or Login ID"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select name="sort" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="full_name" <?= $sort == 'full_name' ? 'selected' : '' ?>>Name</option>
                            <option value="joining_date" <?= $sort == 'joining_date' ? 'selected' : '' ?>>Joining Date</option>
                            <option value="login_id" <?= $sort == 'login_id' ? 'selected' : '' ?>>Login ID</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md w-full">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                    </div>
                </form>

                <!-- Teacher Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'login_id', 'order' => $sort == 'login_id' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Login ID
                                        <?php if ($sort == 'login_id'): ?>
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
                                    Qualification
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'joining_date', 'order' => $sort == 'joining_date' && $order == 'ASC' ? 'DESC' : 'ASC'])) ?>">
                                        Joining Date
                                        <?php if ($sort == 'joining_date'): ?>
                                            <i class="fas fa-sort-<?= $order == 'ASC' ? 'up' : 'down' ?> ml-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-sort ml-1"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Class Teacher
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($teachers)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">No teachers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-blue-600"><?= htmlspecialchars($teacher['login_id']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($teacher['full_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($teacher['email']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($teacher['qualification_type'] ?? '') ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($teacher['specialization'] ?? '') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d M, Y', strtotime($teacher['joining_date'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($teacher['is_class_teacher'] > 0): ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Yes</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="view.php?id=<?= $teacher['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="edit.php?id=<?= $teacher['id'] ?>" class="text-yellow-600 hover:text-yellow-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="delete.php?id=<?= $teacher['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this teacher?')"><i class="fas fa-trash"></i></a>
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