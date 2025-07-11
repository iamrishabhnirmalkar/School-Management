<?php
require_once '../../../config.php';
session_start();
// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../logout.php");
    exit;
}
// Check if student ID is provided
if (!isset($_GET['id'])) {
    die("Student ID not provided");
}
$student_id = intval($_GET['id']);
// Fetch student data
$student = $conn->query("
    SELECT s.*, u.full_name, u.email, u.phone, c.class_name, c.section
    FROM students s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.user_id = $student_id
")->fetch_assoc();
if (!$student) {
    die("Student not found");
}
// Include the TCPDF library
require_once('../../lib/tcpdf/tcpdf.php');
// Create new PDF document (vertical layout - 85mm x 54mm standard ID card size)
$pdf = new TCPDF('P', 'mm', array(54, 85), true, 'UTF-8', false);
// Set document information
$pdf->SetCreator('School ERP');
$pdf->SetAuthor('Mother\'s Pride School');
$pdf->SetTitle('Student ID Card - ' . $student['full_name']);
$pdf->SetSubject('Student Identity Card');
// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
// Set margins
$pdf->SetMargins(3, 3, 3);
// Add a page
$pdf->AddPage();
// School logo
$pdf->Image('../../assets/images/logo.png', 15, 3, 24, 24, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
// School information
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(0, 28);
$pdf->Cell(54, 4, 'MOTHER\'S PRIDE SR. SEC. SCHOOL', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 6);
$pdf->Cell(54, 3, 'Affiliated to C.B.S.E. Board (CBSE-3330112)', 0, 1, 'C');
$pdf->Cell(54, 3, 'Khanhariya Tah-Patan, Durg (C.G.) 491111', 0, 1, 'C');
// Card title
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(54, 6, 'STUDENT ID CARD', 0, 1, 'C');
// Student photo
$photo_path = !empty($student['photo']) ? '../../' . $student['photo'] : '../../assets/img/default-student.jpg';
$pdf->Image($photo_path, 35, 47, 15, 18, 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
// Student information
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetXY(5, 47);
$pdf->Cell(30, 4, strtoupper($student['full_name']), 0, 1);
$pdf->SetFont('helvetica', '', 7);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Class: ' . ($student['class_name'] ?? '') . ' ' . ($student['section'] ?? ''), 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Roll No: ' . ($student['roll_number'] ?? ''), 0, 1);
$pdf->SetX(5);
$pdf->Cell(30, 4, 'Parent Phone: ' . ($student['parent_phone'] ?? ''), 0, 1);
// Footer with validity
$pdf->SetFont('helvetica', 'I', 6);
$pdf->SetXY(3, 85);
$pdf->Cell(0, 3, 'Valid until: ' . date('d/m/Y', strtotime('+1 year')), 0, 0, 'R');
// Output the PDF
if (isset($_GET['download'])) {
    $pdf->Output('student_id_card_' . $student_id . '.pdf', 'D'); // Force download
} else {
    $pdf->Output('student_id_card_' . $student_id . '.pdf', 'I'); // View in browser
} 