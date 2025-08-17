<?php
require_once __DIR__ . '/../../core/config.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$sql = "SELECT certificate FROM project WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$result = $stmt->get_result();
$project = $result->fetch_assoc();

if ($project && !empty($project['certificate'])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_buffer($finfo, $project['certificate']);
    finfo_close($finfo);

    if ($fileType === 'application/pdf') {
        header('Content-Type: application/pdf');
        echo $project['certificate'];
        exit;
    }

    echo '<img src="data:' . htmlspecialchars($fileType) . ';base64,' . base64_encode($project['certificate']) . '" style="max-width:100%" />';
} else {
    echo 'Certificate not found.';
}

