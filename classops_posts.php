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
        throw new Exception("Only faculty can create posts");
    }

    $action = $_POST['action'] ?? '';
    $classId = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $postType = $_POST['post_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $dueDate = $_POST['due_date'] ?? null;

    if (!$classId || empty($title) || !in_array($postType, ['announcement', 'material', 'assignment'])) {
        throw new Exception("Invalid parameters");
    }

    // Verify faculty owns the class
    $checkSql = "SELECT class_id FROM classops_classes WHERE class_id = ? AND faculty_empid = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("is", $classId, $auth['id']);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        throw new Exception("You don't have permission to post in this class");
    }
    $checkStmt->close();

    if ($action === 'create') {
        $conn->begin_transaction();

        try {
            // Create new post
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
            // Handle file uploads if any
            if (isset($_FILES['files'])) {
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

                // Only process if files were actually uploaded
                if (!empty($_FILES['files']['name'][0])) {
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

                        if ($fileData === false) {
                            throw new Exception("Failed to read file contents");
                        }

                        $fileSql = "INSERT INTO classops_post_files 
                    (post_id, file_name, file_type, file_data) 
                    VALUES (?, ?, ?, ?)";

                        $fileStmt = $conn->prepare($fileSql);
                        $null = null;
                        $fileStmt->bind_param("issb", $postId, $fileName, $fileType, $null);
                        $fileStmt->send_long_data(3, $fileData);

                        if (!$fileStmt->execute()) {
                            throw new Exception("Failed to upload file: " . $fileStmt->error);
                        }

                        $fileStmt->close();
                    }
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
        // Validate all required parameters
        if (empty($title) || empty($postType) || !in_array($postType, ['announcement', 'material', 'assignment'])) {
            throw new Exception("Invalid parameters: Title, post type, and valid post type are required");
        }

        // Validate due date if this is an assignment
        if ($postType === 'assignment' && !empty($dueDate)) {
            $dueDateTime = strtotime($dueDate);
            if ($dueDateTime === false) {
                throw new Exception("Invalid due date format");
            }
        } else {
            $dueDate = null; // Clear due date if not an assignment
        }

        $conn->begin_transaction();

        try {
            // Verify faculty owns the post and get current post data
            $checkSql = "SELECT class_id, post_type FROM classops_posts WHERE post_id = ? AND faculty_empid = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("is", $postId, $auth['id']);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows === 0) {
                throw new Exception("You don't have permission to edit this post or post doesn't exist");
            }

            $postData = $checkResult->fetch_assoc();
            $checkStmt->close();

            // Update post
            $sql = "UPDATE classops_posts 
                SET post_type = ?, title = ?, content = ?, due_date = ?
                WHERE post_id = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("ssssi", $postType, $title, $content, $dueDate, $postId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to update post: " . $stmt->error);
            }

            // Handle file uploads if any
            // Handle file uploads if any
            if (isset($_FILES['files']) && is_array($_FILES['files']['tmp_name'])) {
                $maxFileSize = 50 * 1024 * 1024; // 50MB
                $allowedTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'application/vnd.ms-excel' => 'xls',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                    'application/vnd.ms-powerpoint' => 'ppt',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                    'text/plain' => 'txt'
                ];

                // Only process if files were actually uploaded
                if (!empty($_FILES['files']['name'][0])) {
                    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
                        // Skip empty file inputs
                        if (empty($tmpName))
                            continue;

                        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                            throw new Exception("File upload error: " . $_FILES['files']['error'][$key]);
                        }

                        if ($_FILES['files']['size'][$key] > $maxFileSize) {
                            throw new Exception("File '{$_FILES['files']['name'][$key]}' exceeds maximum size of 50MB");
                        }

                        $fileType = $_FILES['files']['type'][$key];
                        if (!array_key_exists($fileType, $allowedTypes)) {
                            throw new Exception("File type '{$fileType}' not allowed for '{$_FILES['files']['name'][$key]}'");
                        }

                        // Sanitize filename
                        $fileName = preg_replace("/[^a-zA-Z0-9\.\-_]/", "", $_FILES['files']['name'][$key]);
                        $fileType = $conn->real_escape_string($fileType);

                        // Read file in chunks
                        $handle = fopen($tmpName, 'rb');
                        if ($handle === false) {
                            throw new Exception("Failed to open uploaded file '{$fileName}'");
                        }

                        $fileSql = "INSERT INTO classops_post_files 
                (post_id, file_name, file_type, file_data) 
                VALUES (?, ?, ?, ?)";

                        $fileStmt = $conn->prepare($fileSql);
                        if (!$fileStmt) {
                            fclose($handle);
                            throw new Exception("Prepare failed for file upload: " . $conn->error);
                        }

                        $null = null;
                        $fileStmt->bind_param("issb", $postId, $fileName, $fileType, $null);

                        // Send data in chunks
                        while (!feof($handle)) {
                            $chunk = fread($handle, 1024 * 1024); // 1MB chunks
                            if ($chunk === false) {
                                fclose($handle);
                                throw new Exception("Failed to read file chunk from '{$fileName}'");
                            }
                            $fileStmt->send_long_data(3, $chunk);
                        }

                        fclose($handle);

                        if (!$fileStmt->execute()) {
                            $fileStmt->close();
                            throw new Exception("Failed to upload file '{$fileName}': " . $fileStmt->error);
                        }

                        $fileStmt->close();
                    }
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
            $response = [
                "success" => false,
                "error" => $e->getMessage(),
                "post_id" => $postId
            ];
        } finally {
            if (isset($stmt)) {
                $stmt->close();
            }
        }

    } elseif ($action === 'delete' && $postId) {
        // Validate parameters
        if (!is_numeric($postId)) {
            throw new Exception("Invalid post ID format");
        }

        $postId = (int) $postId;
        if ($postId <= 0) {
            throw new Exception("Post ID must be positive integer");
        }

        $conn->begin_transaction();
        try {
            // Verify faculty owns the post and get additional post info
            $checkSql = "SELECT p.post_id, p.title, 
                        (SELECT COUNT(*) FROM classops_post_files WHERE post_id = p.post_id) as file_count,
                        (SELECT COUNT(*) FROM classops_comments WHERE post_id = p.post_id) as comment_count
                        FROM classops_posts p
                        WHERE p.post_id = ? AND p.faculty_empid = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("is", $postId, $auth['id']);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Post not found or you don't have permission to delete it");
            }

            $postInfo = $result->fetch_assoc();
            $checkStmt->close();

            // First delete all files associated with the post
            if ($postInfo['file_count'] > 0) {
                $deleteFilesSql = "DELETE FROM classops_post_files WHERE post_id = ?";
                $deleteFilesStmt = $conn->prepare($deleteFilesSql);
                $deleteFilesStmt->bind_param("i", $postId);

                if (!$deleteFilesStmt->execute()) {
                    throw new Exception("Failed to delete post files: " . $deleteFilesStmt->error);
                }
                $deleteFilesStmt->close();
            }

            // Delete all comments
            if ($postInfo['comment_count'] > 0) {
                $deleteCommentsSql = "DELETE FROM classops_comments WHERE post_id = ?";
                $deleteCommentsStmt = $conn->prepare($deleteCommentsSql);
                $deleteCommentsStmt->bind_param("i", $postId);

                if (!$deleteCommentsStmt->execute()) {
                    throw new Exception("Failed to delete post comments: " . $deleteCommentsStmt->error);
                }
                $deleteCommentsStmt->close();
            }

            // Then delete the post itself
            $deletePostSql = "DELETE FROM classops_posts WHERE post_id = ?";
            $deletePostStmt = $conn->prepare($deletePostSql);
            $deletePostStmt->bind_param("i", $postId);

            if (!$deletePostStmt->execute()) {
                throw new Exception("Failed to delete post: " . $deletePostStmt->error);
            }

            $conn->commit();

            $response = [
                "success" => true,
                "message" => "Post and all associated content deleted successfully",
                "post_id" => $postId,
                "deleted_files" => $postInfo['file_count'],
                "deleted_comments" => $postInfo['comment_count']
            ];
        } catch (Exception $e) {
            $conn->rollback();
            $response = [
                "success" => false,
                "error" => $e->getMessage(),
                "post_id" => $postId
            ];
        } finally {
            if (isset($deletePostStmt)) {
                $deletePostStmt->close();
            }
        }
    } // This is the ONLY closing brace needed for the delete section
} catch (Exception $e) {
    $response["error"] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>