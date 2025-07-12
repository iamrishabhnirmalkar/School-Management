<?php
session_start();
require_once '../../../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../../logout.php");
    exit;
}

$mode = $_POST['mode'] ?? 'manual';

if ($mode === 'bus') {
    // Handle bus selection mode
    $bus_id = $_POST['bus_id'] ?? 0;
    
    // Fetch bus data from database
    $bus = $conn->query("
        SELECT bus_number, route_name, driver_name, driver_phone, registration_number, model, year
        FROM buses 
        WHERE id = $bus_id
    ")->fetch_assoc();
    
    if (!$bus) {
        die("Bus not found");
    }
    
    $driver_name = $bus['driver_name'] ?? '';
    $bus_number = $bus['bus_number'];
    $route_name = $bus['route_name'];
    $driver_id = $_POST['driver_id'] ?? '';
    $driver_phone = $bus['driver_phone'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    $registration_number = $bus['registration_number'] ?? '';
    $signature = $_POST['signature'] ?? '';
    $address = $_POST['address'] ?? '';
    $model = $bus['model'] ?? '';
    $year = $bus['year'] ?? '';
    
    // Handle photo
    $targetPath = '';
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // New photo uploaded
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
            die("Failed to upload image.");
        }
    } else {
        die("Photo is required for bus driver ID card.");
    }
} else {
    // Handle manual entry mode
    $driver_name = $_POST['driver_name'] ?? '';
    $bus_number = $_POST['bus_number'] ?? '';
    $route_name = $_POST['route_name'] ?? '';
    $driver_id = $_POST['driver_id'] ?? '';
    $driver_phone = $_POST['driver_phone'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    $registration_number = $_POST['registration_number'] ?? '';
    $signature = $_POST['signature'] ?? '';
    $address = $_POST['address'] ?? '';
    $model = $_POST['model'] ?? '';
    $year = $_POST['year'] ?? '';

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $targetPath)) {
        die("Failed to upload image.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Bus Driver ID Card</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>

<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen px-4 py-10 space-y-6">

    <!-- Bus Driver ID Card Wrapper -->
    <div id="busIdCard" class="w-[350px] bg-white rounded-xl shadow-xl border border-gray-300 p-4 text-sm font-sans relative">

        <!-- Header -->
        <div class="text-center">
            <h1 class="text-blue-700 font-extrabold text-lg leading-5">MOTHER'S PRIDE SR. SEC. SCHOOL</h1>
            <p class="text-[11px] text-gray-700 font-medium">Affiliated to C.B.S.E. Board (CBSE-3300112)</p>
            <p class="text-[11px] text-gray-600">Khamhariya Tah-Patan, Durg (C.G.) 491111</p>
            <p class="text-[10px] text-gray-500">Tel: 0771-2241907 | Email: mpskhamhariya@gmail.com</p>
            <p class="text-blue-600 font-semibold text-xs mt-1">Bus Driver Identity Card 2024 - 25</p>
            <p class="text-blue-700 font-semibold text-xs mt-1">Session: <?= htmlspecialchars($academic_year ?? '2024-25') ?></p>
        </div>

        <!-- Photo -->
        <div class="mt-4 flex justify-center">
            <div class="w-[100px] h-[120px] border-4 border-blue-500 p-1 rounded-md shadow-md">
                <img src="<?= $targetPath ?>" alt="Driver" class="w-full h-full object-cover rounded-sm" />
            </div>
        </div>

        <!-- Driver Info -->
        <div class="mt-3 text-center">
            <p class="text-blue-700 font-bold text-sm uppercase"><?= htmlspecialchars($driver_name) ?></p>
            <p class="text-black text-sm font-semibold">Driver ID: <?= htmlspecialchars($driver_id) ?></p>
        </div>

        <!-- Bus and Vehicle Info -->
        <div class="mt-3 text-[12px] leading-5 px-2">
            <p><span class="font-bold text-blue-600">Bus Number:</span> <?= htmlspecialchars($bus_number) ?></p>
            <p><span class="font-bold text-blue-600">Route:</span> <?= htmlspecialchars($route_name) ?></p>
            <p><span class="font-bold text-blue-600">Registration:</span> <?= htmlspecialchars($registration_number) ?></p>
            <?php if (!empty($model)): ?>
                <p><span class="font-bold text-blue-600">Vehicle Model:</span> <?= htmlspecialchars($model) ?></p>
            <?php endif; ?>
            <?php if (!empty($year)): ?>
                <p><span class="font-bold text-blue-600">Year:</span> <?= htmlspecialchars($year) ?></p>
            <?php endif; ?>
        </div>

        <!-- Contact and License Info -->
        <div class="mt-3 text-[12px] leading-5 px-2">
            <p><span class="font-bold text-blue-600">Phone:</span> <?= htmlspecialchars($driver_phone) ?></p>
            <p><span class="font-bold text-blue-600">License No:</span> <?= htmlspecialchars($license_number) ?></p>
            <p><span class="font-bold text-blue-600">Address:</span> <?= htmlspecialchars($address) ?></p>
        </div>

        <!-- Signature -->
        <div class="text-right mt-4 px-2">
            <p class="text-blue-600 font-semibold text-sm"><?= htmlspecialchars($signature) ?></p>
            <div class="w-16 h-[1px] border-t border-blue-600 mt-[-2px]"></div>
            <p class="text-xs">Signature</p>
        </div>

        <!-- Validity Notice -->
        <div class="mt-3 text-center">
            <p class="text-[10px] text-gray-500">This ID card is valid for the academic year 2024-25</p>
            <p class="text-[10px] text-gray-500">Issued by School Transport Department</p>
        </div>
    </div>

    <!-- Download Button -->
    <button onclick="downloadBusIdCard()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
        Download Bus Driver ID Card
    </button>

    <script>
        function downloadBusIdCard() {
            const card = document.getElementById("busIdCard");
            html2canvas(card).then(canvas => {
                const link = document.createElement("a");
                link.download = "Bus-Driver-ID-Card.png";
                link.href = canvas.toDataURL("image/png");
                link.click();
            });
        }
    </script>

</body>

</html> 