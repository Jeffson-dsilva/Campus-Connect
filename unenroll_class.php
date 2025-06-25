<?php
session_start();
header('Content-Type: application/json');

require_once 'auth_check.php';
$auth = verifySession();

// Database connection
$config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'dbname' => 'college_ipm_system',
    'port' => 3307
];

$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname'], $config['port']);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "error" => "Database connection failed"]));
}

$response = ["success" => false];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $userId = $_POST['user_id'] ?? '';
    $isFaculty = $_POST['is_faculty'] === 'true';

    if (!$classId || empty($userId)) {
        throw new Exception("Invalid parameters");
    }

    // Verify the user is enrolled
    $column = $isFaculty ? "faculty_empid" : "student_usn";
    $checkSql = "SELECT id FROM classops_enrollments WHERE class_id = ? AND $column = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("is", $classId, $userId);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        throw new Exception("You are not enrolled in this class");
    }
    $checkStmt->close();

    // Delete the enrollment record
    $deleteSql = "DELETE FROM classops_enrollments WHERE class_id = ? AND $column = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("is", $classId, $userId);

    if ($deleteStmt->execute()) {
        $response["success"] = true;
    } else {
        throw new Exception("Failed to unenroll: " . $deleteStmt->error);
    }

    $deleteStmt->close();
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>