<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname,$port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'];
$jsonData = $data['data'];

if ($type == 'students') {
    $tableName = 'students';
    $fields = ['Name', 'USN', 'Email', 'Password'];
} else {
    $tableName = 'faculty';
    $fields = ['Employee_ID', 'Name', 'Email', 'Password'];
}

$duplicates = 0;
$uploaded = 0;

foreach ($jsonData as $row) {
    $values = [];
    foreach ($fields as $field) {
        if (isset($row[$field])) {
            $values[] = $row[$field];
        }
    }
    
    // Check if any required field is missing
    if (count($values) !== count($fields)) {
        continue;
    }

    // Check for duplicates before inserting
    $emailField = $type == 'students' ? 'Email' : 'email';
    $checkQuery = "SELECT * FROM $tableName WHERE $emailField = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $values[2]);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $insertQuery = "INSERT INTO $tableName (" . implode(',', $fields) . ") VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssss", ...$values);
        if ($stmt->execute()) {
            $uploaded++;
        }
    } else {
        $duplicates++;
    }
}

$response = [
    'success' => true,
    'message' => "$uploaded records uploaded successfully! $duplicates duplicate entries ignored."
];

echo json_encode($response);
?>
