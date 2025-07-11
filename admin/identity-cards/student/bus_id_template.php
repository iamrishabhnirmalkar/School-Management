<?php
require_once '../../config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../../logout.php");
    exit;
}

// Check if student ID is provided
if (!isset($_GET['id'])) {
    die("Student ID not provided");
}

$student_id = intval($_GET['id']);

// Fetch student data with bus information
$student = $conn->query("
    SELECT s.*, u.full_name, u.email, u.phone, c.class_name, c.section,
           b.bus_number, b.route_name, ba.stop_name, ba.pickup_time, ba.drop_time
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN bus_allocations ba ON ba.student_id = s.user_id
    LEFT JOIN buses b ON ba.bus_id = b.id
    WHERE s.user_id = $student_id
")->fetch_assoc();

if (!$student || !$student['bus_number']) {
    die("Student not found or not assigned to a bus");
}

// Include the TCPDF library
require_once('../../lib/tcpdf/tcpdf.php');

// Create new PDF document (vertical layout - 85mm x 54mm standard ID card size)
$pdf = new TCPDF('P', 'mm', array(54, 85), true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('School ERP');
$pdf->SetAuthor('Mother\'s Pride School');
$pdf->SetTitle('Bus ID Card - ' . $student['full_name']);
$pdf->SetSubject('Bus Identity Card');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(3, 3, 3);

// Add a page
$pdf->AddPage();

// Yellow background for bus card
$pdf->SetFillColor(255, 255, 153);
$pdf->Rect(0, 0, 54, 85, 'F', array(), array(255, 255, 153));

// School logo (replace with your actual logo path)
$pdf->Image('../../assets/img/logo/logo.png', 15, 3, 24, 24, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);

// School information
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(0, 28);
$pdf->Cell(54, 4, 'MOTHER\'S PRIDE SR. SEC. SCHOOL', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(54, 3, 'Affiliated to C.B.S.E. Board (CBSE-3330112)', 0, 1, 'C');
$pdf->Cell(54, 3, 'Khanhariya Tah-Patan, Durg (C.G.) 491111', 0, 1, 'C');

// Card title
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(54, 6, 'BUS ID CARD', 0, 1, 'C');

// Student photo (right-aligned)
$photo_path = !empty($student['photo']) ? '../../' . $student['photo'] : '../../assets/img/default-student.jpg';
$pdf->Image($photo_path, 35, 47, 15, 18, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);

// Student information (left-aligned)
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(5, 47);
$pdf->Cell(30, 4, strtoupper($student['full_name']), 0, 1);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Class: ' . $student['class_name'] . ' ' . $student['section'], 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Roll No: ' . $student['roll_number'], 0, 1);

// Bus information
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(5, 65);
$pdf->Cell(30, 4, 'BUS DETAILS', 0, 1);

$pdf->SetFont('helvetica', '', 7);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Bus No: ' . $student['bus_number'], 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Route: ' . $student['route_name'], 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Stop: ' . $student['stop_name'], 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Pickup: ' . date('h:i A', strtotime($student['pickup_time'])), 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Drop: ' . date('h:i A', strtotime($student['drop_time'])), 0, 1);

// Footer with validity
$pdf->SetFont('helvetica', 'I', 6);
$pdf->SetXY(3, 85);
$pdf->Cell(0, 3, 'Valid until: ' . date('d/m/Y', strtotime('+1 year')), 0, 0, 'R');

// Output the PDF
if (isset($_GET['download'])) {
    $pdf->Output('bus_id_card_' . $student_id . '.pdf', 'D'); // Force download
} else {
    $pdf->Output('bus_id_card_' . $student_id . '.pdf', 'I'); // View in browser
}
?>