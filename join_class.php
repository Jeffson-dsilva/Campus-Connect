<?php
session_start();
header('Content-Type: application/json');

// Database configuration
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname' => 'college_ipm_system',
    'port' => 3307
];

// Initialize response
$response = ['success' => false, 'error' => ''];

try {
    // Validate student session
    if (!isset($_SESSION['usn'])) {
        throw new Exception('Student not logged in');
    }

    $studentUsn = $_SESSION['usn'];

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate class code
    $classCode = isset($_POST['class_code']) ? strtoupper(trim($_POST['class_code'])) : '';
    
    if (empty($classCode)) {
        throw new Exception('Class code is required');
    }

    if (!preg_match('/^[A-Z0-9]{6,10}$/', $classCode)) {
        throw new Exception('Class code must be 6-10 alphanumeric characters');
    }

    // Database connection
    $conn = new mysqli(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['dbname'],
        $config['port']
    );

    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Check if class exists
    $stmt = $conn->prepare("SELECT class_id FROM classops_classes WHERE class_code = ?");
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $classCode);
    $stmt->execute();
    $stmt->bind_result($classId);
    $stmt->fetch();
    $stmt->close();

    if (!$classId) {
        throw new Exception('Class not found with this code');
    }

    // Check if already enrolled
    $checkStmt = $conn->prepare("
        SELECT id FROM classops_enrollments 
        WHERE class_id = ? AND student_usn = ?
    ");
    if (!$checkStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $checkStmt->bind_param("is", $classId, $studentUsn);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        throw new Exception('You are already enrolled in this class');
    }
    $checkStmt->close();

    // Enroll the student
    $insertStmt = $conn->prepare("
        INSERT INTO classops_enrollments 
        (class_id, student_usn) 
        VALUES (?, ?)
    ");
    
    if (!$insertStmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

    $insertStmt->bind_param("is", $classId, $studentUsn);
    
    if (!$insertStmt->execute()) {
        if ($conn->errno == 1452) { // Foreign key constraint fails
            throw new Exception('Student not found in database');
        }
        throw new Exception('Failed to enroll: ' . $insertStmt->error);
    }

    // Success
    $response = [
        'success' => true,
        'class_id' => $classId,
        'message' => 'Successfully enrolled in class'
    ];

    $insertStmt->close();
    $conn->close();

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

// Send JSON response
echo json_encode($response);
?>