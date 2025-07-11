<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'student') {
    header("Location: ../logout.php");
    exit;
}

$student_id = $_SESSION['user']['id'];

// Get fee records
$fees = [];
$result = $conn->query("SELECT id, fee_type, amount, due_date, paid_date, status 
                        FROM fees 
                        WHERE student_id = $student_id
                        ORDER BY due_date DESC");
while ($row = $result->fetch_assoc()) {
    $fees[] = $row;
}

// Calculate fee summary
$summary = [
    'total' => 0,
    'paid' => 0,
    'unpaid' => 0,
    'overdue' => 0
];
foreach ($fees as $fee) {
    $summary['total'] += $fee['amount'];
    if ($fee['status'] === 'paid') {
        $summary['paid'] += $fee['amount'];
    } else {
        $summary['unpaid'] += $fee['amount'];
        if (strtotime($fee['due_date']) < time()) {
            $summary['overdue'] += $fee['amount'];
        }
    }
}

// Get payment receipts
$receipts = [];
$result = $conn->query("SELECT id, title, issued_date 
                        FROM student_documents 
                        WHERE student_id = $student_id AND document_type = 'fee_receipt'
                        ORDER BY issued_date DESC");
while ($row = $result->fetch_assoc()) {
    $receipts[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment - School ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<!-- Header -->
<header class="bg-green-600 text-white shadow-lg">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <img src="../assets/img/logo/logo.png" alt="Logo" class="w-10 h-10">
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
            <h1 class="text-2xl font-bold mb-6 text-gray-800">Fee Payment</h1>
            
            <!-- Fee Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-green-600">Total Paid</p>
                    <p class="text-2xl font-bold">₹<?= number_format($summary['paid'], 2) ?></p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <p class="text-sm text-yellow-600">Pending Payment</p>
                    <p class="text-2xl font-bold">₹<?= number_format($summary['unpaid'], 2) ?></p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-red-600">Overdue Amount</p>
                    <p class="text-2xl font-bold">₹<?= number_format($summary['overdue'], 2) ?></p>
                </div>
            </div>

            <!-- Fee Details -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Fee Details</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($fees)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No fee records found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fees as $fee): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($fee['fee_type']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            ₹<?= number_format($fee['amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($fee['due_date'])) ?>
                                            <?php if ($fee['status'] !== 'paid' && strtotime($fee['due_date']) < time()): ?>
                                                <span class="text-xs text-red-500">(Overdue)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?= $fee['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                                   ($fee['status'] === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                                <?= ucfirst($fee['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($fee['status'] !== 'paid'): ?>
                                                <a href="pay_fee.php?id=<?= $fee['id'] ?>" class="text-blue-600 hover:text-blue-800">Pay Now</a>
                                            <?php else: ?>
                                                <a href="download.php?type=receipt&id=<?= $fee['id'] ?>" class="text-green-600 hover:text-green-800">Download Receipt</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment Receipts -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Payment Receipts</h2>
                <?php if (empty($receipts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-invoice-dollar text-2xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">No payment receipts available</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($receipts as $receipt): ?>
                            <div class="border rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-file-pdf text-red-500 text-2xl mr-3"></i>
                                    <div>
                                        <h3 class="font-medium"><?= htmlspecialchars($receipt['title']) ?></h3>
                                        <p class="text-sm text-gray-500">Issued: <?= date('M d, Y', strtotime($receipt['issued_date'])) ?></p>
                                    </div>
                                </div>
                                <a href="download.php?id=<?= $receipt['id'] ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>