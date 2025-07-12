<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}

// Get report parameters
$reportType = $_GET['type'] ?? 'collection';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$classFilter = $_GET['class'] ?? '';
$feeTypeFilter = $_GET['fee_type'] ?? '';

// Fetch classes for filter
$classes = $conn->query("SELECT id, class_name, section FROM classes ORDER BY class_name, section")->fetch_all(MYSQLI_ASSOC);

// Fetch fee types for filter
$feeTypes = $conn->query("SELECT DISTINCT fee_type FROM fees ORDER BY fee_type")->fetch_all(MYSQLI_ASSOC);

// Generate reports based on type
$reportData = [];
$reportTitle = '';

switch ($reportType) {
    case 'collection':
        $reportTitle = 'Fee Collection Report';
        $whereConditions = ["f.status = 'paid'", "f.paid_date BETWEEN '$startDate' AND '$endDate'"];
        $params = [];
        
        if (!empty($classFilter)) {
            $whereConditions[] = "s.class_id = ?";
            $params[] = $classFilter;
        }
        
        if (!empty($feeTypeFilter)) {
            $whereConditions[] = "f.fee_type = ?";
            $params[] = $feeTypeFilter;
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        $query = "
            SELECT f.*, u.full_name as student_name, s.roll_number, c.class_name, c.section,
                   DATE_FORMAT(f.paid_date, '%Y-%m-%d') as payment_date
            FROM fees f
            JOIN users u ON f.student_id = u.id
            JOIN students s ON f.student_id = s.user_id
            JOIN classes c ON s.class_id = c.id
            WHERE $whereClause
            ORDER BY f.paid_date DESC, c.class_name, u.full_name
        ";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'overdue':
        $reportTitle = 'Overdue Fees Report';
        $whereConditions = ["f.status = 'unpaid'", "f.due_date < CURDATE()"];
        $params = [];
        
        if (!empty($classFilter)) {
            $whereConditions[] = "s.class_id = ?";
            $params[] = $classFilter;
        }
        
        if (!empty($feeTypeFilter)) {
            $whereConditions[] = "f.fee_type = ?";
            $params[] = $feeTypeFilter;
        }
        
        $whereClause = implode(" AND ", $whereConditions);
        
        $query = "
            SELECT f.*, u.full_name as student_name, s.roll_number, c.class_name, c.section,
                   DATEDIFF(CURDATE(), f.due_date) as days_overdue
            FROM fees f
            JOIN users u ON f.student_id = u.id
            JOIN students s ON f.student_id = s.user_id
            JOIN classes c ON s.class_id = c.id
            WHERE $whereClause
            ORDER BY f.due_date ASC, c.class_name, u.full_name
        ";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
        
    case 'summary':
        $reportTitle = 'Fee Summary Report';
        
        $query = "
            SELECT 
                c.class_name, c.section,
                f.fee_type,
                COUNT(*) as total_students,
                COUNT(CASE WHEN f.status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN f.status = 'unpaid' THEN 1 END) as unpaid_count,
                COUNT(CASE WHEN f.status = 'unpaid' AND f.due_date < CURDATE() THEN 1 END) as overdue_count,
                SUM(f.amount) as total_amount,
                SUM(CASE WHEN f.status = 'paid' THEN f.amount ELSE 0 END) as collected_amount,
                SUM(CASE WHEN f.status = 'unpaid' THEN f.amount ELSE 0 END) as pending_amount
            FROM fees f
            JOIN students s ON f.student_id = s.user_id
            JOIN classes c ON s.class_id = c.id
            WHERE 1=1
        ";
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($classFilter)) {
            $whereConditions[] = "s.class_id = ?";
            $params[] = $classFilter;
        }
        
        if (!empty($feeTypeFilter)) {
            $whereConditions[] = "f.fee_type = ?";
            $params[] = $feeTypeFilter;
        }
        
        if (!empty($whereConditions)) {
            $query .= " AND " . implode(" AND ", $whereConditions);
        }
        
        $query .= " GROUP BY c.id, f.fee_type ORDER BY c.class_name, c.section, f.fee_type";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $reportData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
}

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="fee_report_' . $reportType . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add headers based on report type
    if ($reportType === 'collection') {
        fputcsv($output, ['Student Name', 'Roll Number', 'Class', 'Fee Type', 'Amount', 'Payment Date', 'Status']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['student_name'],
                $row['roll_number'],
                $row['class_name'] . ' ' . $row['section'],
                $row['fee_type'],
                $row['amount'],
                $row['payment_date'],
                'Paid'
            ]);
        }
    } elseif ($reportType === 'overdue') {
        fputcsv($output, ['Student Name', 'Roll Number', 'Class', 'Fee Type', 'Amount', 'Due Date', 'Days Overdue']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['student_name'],
                $row['roll_number'],
                $row['class_name'] . ' ' . $row['section'],
                $row['fee_type'],
                $row['amount'],
                $row['due_date'],
                $row['days_overdue']
            ]);
        }
    } elseif ($reportType === 'summary') {
        fputcsv($output, ['Class', 'Fee Type', 'Total Students', 'Paid', 'Unpaid', 'Overdue', 'Total Amount', 'Collected', 'Pending']);
        foreach ($reportData as $row) {
            fputcsv($output, [
                $row['class_name'] . ' ' . $row['section'],
                $row['fee_type'],
                $row['total_students'],
                $row['paid_count'],
                $row['unpaid_count'],
                $row['overdue_count'],
                $row['total_amount'],
                $row['collected_amount'],
                $row['pending_amount']
            ]);
        }
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Reports - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-blue-700 text-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <img src="../../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
                    <div>
                        <h1 class="text-2xl font-bold">School ERP System</h1>
                        <p class="text-blue-200">Fee Reports</p>
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
        <!-- Sidebar Navigation -->
        <aside class="w-64 flex-shrink-0">
            <nav class="bg-white rounded-lg shadow-md p-4 sticky top-4">
                <ul class="space-y-2">
                    <li>
                        <a href="../dashboard.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-tachometer-alt w-5"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-money-bill-wave w-5"></i>
                            <span>Fee Overview</span>
                        </a>
                    </li>
                    <li>
                        <a href="collect.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cash-register w-5"></i>
                            <span>Collect Fee</span>
                        </a>
                    </li>
                    <li>
                        <a href="structure.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-list-alt w-5"></i>
                            <span>Fee Structure</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="flex items-center space-x-3 p-3 rounded-lg bg-blue-50 text-blue-700">
                            <i class="fas fa-chart-bar w-5"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-blue-50">
                            <i class="fas fa-cog w-5"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 ml-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Fee Reports</h2>
                <div class="flex space-x-3">
                    <a href="reports.php?export=csv&type=<?= $reportType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&class=<?= $classFilter ?>&fee_type=<?= $feeTypeFilter ?>" 
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-download mr-2"></i> Export CSV
                    </a>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                        <select id="type" name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="collection" <?= $reportType === 'collection' ? 'selected' : '' ?>>Collection Report</option>
                            <option value="overdue" <?= $reportType === 'overdue' ? 'selected' : '' ?>>Overdue Report</option>
                            <option value="summary" <?= $reportType === 'summary' ? 'selected' : '' ?>>Summary Report</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="class" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                        <select id="class" name="class" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>" <?= $classFilter == $class['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($class['class_name']) ?> <?= htmlspecialchars($class['section']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="fee_type" class="block text-sm font-medium text-gray-700 mb-1">Fee Type</label>
                        <select id="fee_type" name="fee_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Fee Types</option>
                            <?php foreach ($feeTypes as $feeType): ?>
                                <option value="<?= $feeType['fee_type'] ?>" <?= $feeTypeFilter === $feeType['fee_type'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($feeType['fee_type']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            <i class="fas fa-search mr-2"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Report Results -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900"><?= $reportTitle ?></h3>
                    <p class="text-sm text-gray-600">
                        <?php if ($reportType === 'collection'): ?>
                            Showing fee collections from <?= date('d M Y', strtotime($startDate)) ?> to <?= date('d M Y', strtotime($endDate)) ?>
                        <?php elseif ($reportType === 'overdue'): ?>
                            Showing overdue fees as of <?= date('d M Y') ?>
                        <?php else: ?>
                            Showing fee summary for selected period
                        <?php endif; ?>
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <?php if ($reportType === 'collection'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No collection data found for the selected period</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                                <div class="text-sm text-gray-500">Roll: <?= htmlspecialchars($row['roll_number']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['class_name']) ?> <?= htmlspecialchars($row['section']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['fee_type']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($row['amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d M Y', strtotime($row['payment_date'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($reportType === 'overdue'): ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No overdue fees found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['student_name']) ?></div>
                                                <div class="text-sm text-gray-500">Roll: <?= htmlspecialchars($row['roll_number']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['class_name']) ?> <?= htmlspecialchars($row['section']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['fee_type']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($row['amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('d M Y', strtotime($row['due_date'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                <?= $row['days_overdue'] ?> days
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Students</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unpaid</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overdue</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">No summary data found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $row): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($row['class_name']) ?> <?= htmlspecialchars($row['section']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= htmlspecialchars($row['fee_type']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= $row['total_students'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <?= $row['paid_count'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <?= $row['unpaid_count'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?= $row['overdue_count'] ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                ₹<?= number_format($row['total_amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                                ₹<?= number_format($row['collected_amount'], 2) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                ₹<?= number_format($row['pending_amount'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-submit form when report type changes
        document.getElementById('type').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>

</html> 