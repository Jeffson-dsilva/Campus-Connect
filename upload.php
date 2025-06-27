<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$type = $data['type'];
$jsonData = $data['data'];

if ($type == 'students') {
    $tableName = 'students';
    $fields = ['Name', 'USN', 'Email', 'Password', 'dept_code'];
    $uniqueField = 'USN';
} else {
    $tableName = 'faculty';
    $fields = ['Employee_ID', 'Name', 'Email', 'Password', 'dept_code'];
    $uniqueField = 'Employee_ID';
}

$duplicates = 0;
$uploaded = 0;
$invalidDept = 0;

foreach ($jsonData as $row) {
    // Validate required fields including department
    $missingFields = false;
    foreach ($fields as $field) {
        if (!isset($row[$field == 'dept_code' ? 'Department' : $field])) {
            $missingFields = true;
            break;
        }
    }
    if ($missingFields) continue;

    // Check if department exists
    $deptCheck = $conn->prepare("SELECT dept_code FROM departments WHERE dept_code = ?");
    $deptCheck->bind_param("s", $row['Department']);
    $deptCheck->execute();
    $deptCheck->store_result();
    
    if ($deptCheck->num_rows == 0) {
        $invalidDept++;
        continue;
    }

    // Prepare values for insertion
    $values = [
        $row['Name'],
        $type == 'students' ? $row['USN'] : $row['Employee_ID'],
        $row['Email'],
        password_hash($row['Password'], PASSWORD_DEFAULT),
        $row['Department']
    ];

    // Check for duplicates before inserting
    $checkQuery = "SELECT * FROM $tableName WHERE $uniqueField = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("s", $values[1]);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $insertQuery = "INSERT INTO $tableName (name, " . ($type == 'students' ? 'usn' : 'employee_id') . ", email, password, dept_code) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssss", ...$values);
        if ($stmt->execute()) {
            $uploaded++;
        }
    } else {
        $duplicates++;
    }
}

$response = [
    'success' => true,
    'message' => "$uploaded records uploaded successfully! " . 
                 ($duplicates > 0 ? "$duplicates duplicate entries ignored. " : "") . 
                 ($invalidDept > 0 ? "$invalidDept records skipped due to invalid department." : "")
];

echo json_encode($response);
?>