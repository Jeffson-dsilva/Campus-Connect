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
    if (!$classId) {
        throw new Exception("Invalid class ID");
    }

    // Verify the requesting faculty owns this class
    $checkSql = "SELECT faculty_empid FROM classops_classes WHERE class_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $classId);
    $checkStmt->execute();
    $checkStmt->bind_result($facultyEmpId);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($auth['type'] !== 'faculty' || $facultyEmpId !== $auth['id']) {
        throw new Exception("You don't have permission to delete this class");
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Prepare all statements
        $queries = [
            "DELETE cs FROM classops_submissions cs JOIN classops_posts cp ON cs.post_id = cp.post_id WHERE cp.class_id = ?",
            "DELETE cc FROM classops_comments cc JOIN classops_posts cp ON cc.post_id = cp.post_id WHERE cp.class_id = ?",
            "DELETE cpf FROM classops_post_files cpf JOIN classops_posts cp ON cpf.post_id = cp.post_id WHERE cp.class_id = ?",
            "DELETE FROM classops_posts WHERE class_id = ?",
            "DELETE FROM classops_enrollments WHERE class_id = ?",
            "DELETE FROM classops_classes WHERE class_id = ?"
        ];

        foreach ($queries as $query) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Database error preparing statement: " . $conn->error);
            }
            $stmt->bind_param("i", $classId);
            if (!$stmt->execute()) {
                throw new Exception("Database error executing query: " . $stmt->error);
            }
            $stmt->close();
        }

        $conn->commit();
        $response["success"] = true;
    } catch (Exception $e) {
        $conn->rollback();
        throw new Exception("Failed to delete class: " . $e->getMessage());
    }
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>