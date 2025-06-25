<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname,$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$inputData = json_decode(file_get_contents('php://input'), true);

if (isset($inputData['type'])) {
    $type = $inputData['type'];

    if (isset($inputData['checkData']) && $inputData['checkData'] === true) {
        if ($type == 'students') {
            $query = "SELECT COUNT(*) AS total FROM students";
        } elseif ($type == 'faculty') {
            $query = "SELECT COUNT(*) AS total FROM faculty";
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid type specified.']);
            exit;
        }

        $result = $conn->query($query);
        $row = $result->fetch_assoc();
        if ($row['total'] > 0) {
            echo json_encode(['success' => true, 'found' => true]);
        } else {
            echo json_encode(['success' => true, 'found' => false]);
        }
        exit;
    }

    if ($type == 'students') {
        $query = "DELETE FROM students";
    } elseif ($type == 'faculty') {
        $query = "DELETE FROM faculty";
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid type specified.']);
        exit;
    }

    if ($conn->query($query) === TRUE) {
        echo json_encode(['success' => true, 'message' => "All {$type} data has been deleted."]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting records: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Type not specified.']);
}

$conn->close();
?>
