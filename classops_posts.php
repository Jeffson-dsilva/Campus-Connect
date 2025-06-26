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

// Increase MySQL packet size to handle larger files
$conn->query("SET GLOBAL max_allowed_packet=52428800"); // 50MB
$conn->query("SET SESSION wait_timeout=600"); // 10 minutes

$response = ["success" => false];

try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Invalid request method");
    }

    // Validate user is faculty
    if ($auth['type'] !== 'faculty') {
        throw new Exception("Only faculty can manage posts");
    }

    $action = $_POST['action'] ?? '';
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $postType = $_POST['post_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $dueDate = $_POST['due_date'] ?? null;

    // Verify faculty owns the class (for create and update)
    if (in_array($action, ['create', 'update']) && $classId) {
        $checkSql = "SELECT class_id FROM classops_classes WHERE class_id = ? AND faculty_empid = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("is", $classId, $auth['id']);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            throw new Exception("You don't have permission to manage this class");
        }
        $checkStmt->close();
    }

    if ($action === 'create') {
        // Only validate required fields here
        if (!$classId || empty($title) || !in_array($postType, ['announcement', 'material', 'assignment'])) {
            throw new Exception("Invalid parameters for creating post");
        }

        $conn->begin_transaction();

        try {
            $sql = "INSERT INTO classops_posts 
                    (class_id, faculty_empid, post_type, title, content, due_date) 
                    VALUES (?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $classId, $auth['id'], $postType, $title, $content, $dueDate);

            if (!$stmt->execute()) {
                throw new Exception("Failed to create post: " . $stmt->error);
            }

            $postId = $stmt->insert_id;

            // Handle file uploads if any
            if (!empty($_FILES['files']['tmp_name'][0])) {
                $maxFileSize = 50 * 1024 * 1024; // 50MB
                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain'
                ];

                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload error: " . $_FILES['files']['error'][$key]);
                    }

                    if ($_FILES['files']['size'][$key] > $maxFileSize) {
                        throw new Exception("File size exceeds maximum limit of 50MB");
                    }

                    if (!in_array($_FILES['files']['type'][$key], $allowedTypes)) {
                        throw new Exception("File type not allowed");
                    }

                    $fileName = $conn->real_escape_string($_FILES['files']['name'][$key]);
                    $fileType = $conn->real_escape_string($_FILES['files']['type'][$key]);
                    $fileData = file_get_contents($tmpName);

                    $fileSql = "INSERT INTO classops_post_files 
                            (post_id, file_name, file_type, file_data) 
                            VALUES (?, ?, ?, ?)";

                    $fileStmt = $conn->prepare($fileSql);
                    $null = null;
                    $fileStmt->bind_param("issb", $postId, $fileName, $fileType, $null);

                    $handle = fopen($tmpName, 'rb');
                    while (!feof($handle)) {
                        $chunk = fread($handle, 1024 * 1024); // 1MB chunks
                        $fileStmt->send_long_data(3, $chunk);
                    }
                    fclose($handle);

                    if (!$fileStmt->execute()) {
                        throw new Exception("Failed to upload file: " . $fileStmt->error);
                    }

                    $fileStmt->close();
                }
            }

            $conn->commit();
            $response["success"] = true;
            $response["post_id"] = $postId;
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

        $stmt->close();
    } elseif ($action === 'update' && $postId) {
        // Validate required fields
        if (empty($title) || empty($postType) || !in_array($postType, ['announcement', 'material', 'assignment'])) {
            throw new Exception("Invalid parameters for updating post");
        }

        if ($postType === 'assignment' && !empty($dueDate)) {
            $dueDateTime = strtotime($dueDate);
            if ($dueDateTime === false) {
                throw new Exception("Invalid due date format");
            }
        } else {
            $dueDate = null;
        }

        $conn->begin_transaction();

        try {
            $checkSql = "SELECT class_id, post_type FROM classops_posts WHERE post_id = ? AND faculty_empid = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("is", $postId, $auth['id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows === 0) {
                throw new Exception("You don't have permission to edit this post or it doesn't exist");
            }

            $postData = $checkResult->fetch_assoc();
            $checkStmt->close();

            $sql = "UPDATE classops_posts 
                    SET post_type = ?, title = ?, content = ?, due_date = ?
                    WHERE post_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $postType, $title, $content, $dueDate, $postId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update post: " . $stmt->error);
            }

            // Handle file uploads
            if (!empty($_FILES['files']['tmp_name'][0])) {
                $maxFileSize = 50 * 1024 * 1024;
                $allowedTypes = [
                    'image/jpeg', 'image/png', 'image/gif', 'application/pdf',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'text/plain'
                ];

                foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                    if (empty($tmpName)) continue;

                    if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload error: " . $_FILES['files']['error'][$key]);
                    }

                    if ($_FILES['files']['size'][$key] > $maxFileSize) {
                        throw new Exception("File size exceeds 50MB");
                    }

                    if (!in_array($_FILES['files']['type'][$key], $allowedTypes)) {
                        throw new Exception("File type not allowed");
                    }

                    $fileName = $conn->real_escape_string($_FILES['files']['name'][$key]);
                    $fileType = $conn->real_escape_string($_FILES['files']['type'][$key]);

                    $fileSql = "INSERT INTO classops_post_files 
                            (post_id, file_name, file_type, file_data) 
                            VALUES (?, ?, ?, ?)";

                    $fileStmt = $conn->prepare($fileSql);
                    $null = null;
                    $fileStmt->bind_param("issb", $postId, $fileName, $fileType, $null);

                    $handle = fopen($tmpName, 'rb');
                    while (!feof($handle)) {
                        $chunk = fread($handle, 1024 * 1024);
                        $fileStmt->send_long_data(3, $chunk);
                    }
                    fclose($handle);

                    if (!$fileStmt->execute()) {
                        throw new Exception("File upload failed: " . $fileStmt->error);
                    }

                    $fileStmt->close();
                }
            }

            $conn->commit();
            $response = [
                "success" => true,
                "post_id" => $postId,
                "message" => "Post updated successfully"
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    } elseif ($action === 'delete' && $postId) {
        if (!is_numeric($postId) || $postId <= 0) {
            throw new Exception("Invalid post ID");
        }

        $conn->begin_transaction();
        try {
            $checkSql = "SELECT post_id FROM classops_posts WHERE post_id = ? AND faculty_empid = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("is", $postId, $auth['id']);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows === 0) {
                throw new Exception("Post not found or you don't have permission to delete it");
            }
            $checkStmt->close();

            $deleteOrder = [
                "submissions" => "DELETE FROM classops_submissions WHERE post_id = ?",
                "files" => "DELETE FROM classops_post_files WHERE post_id = ?",
                "comments" => "DELETE FROM classops_comments WHERE post_id = ?",
                "post" => "DELETE FROM classops_posts WHERE post_id = ?"
            ];

            $counts = [];
            foreach ($deleteOrder as $type => $sql) {
                if ($type === "submissions") {
                    $result = $conn->query("SELECT 1 FROM classops_posts WHERE post_id = $postId AND post_type = 'assignment'");
                    if ($result->num_rows === 0) continue;
                }

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $postId);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete $type: " . $stmt->error);
                }
                $counts["deleted_$type"] = $stmt->affected_rows;
                $stmt->close();
            }

            $conn->commit();
            $response = [
                "success" => true,
                "message" => "Post and related content deleted successfully",
                "post_id" => $postId,
                "deleted_counts" => $counts
            ];
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception("Invalid action or missing parameters");
    }
} catch (Exception $e) {
    $response["success"] = false;
    $response["error"] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>
