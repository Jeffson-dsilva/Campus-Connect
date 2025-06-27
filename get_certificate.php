<?php
require_once 'config.php';

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Fetch certificate data
$sql = "SELECT certificate FROM mooc_courses WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();
$course = $result->fetch_assoc();

if ($course && !empty($course['certificate'])) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $fileType = finfo_buffer($finfo, $course['certificate']);
    finfo_close($finfo);

    // Handle different file types
    switch(true) {
        case strpos($fileType, 'image/') === 0: // JPG, PNG
            echo '<div class="text-center">';
            echo '<img src="data:'.$fileType.';base64,'.base64_encode($course['certificate']).'" class="w-full h-auto max-h-[70vh] object-contain">';
            echo '</div>';
            break;
            
        case $fileType === 'application/pdf':
            echo '<embed src="data:application/pdf;base64,'.base64_encode($course['certificate']).'" type="application/pdf" width="100%" height="600px">';
            break;
            
        case $fileType === 'application/msword': // DOC
            echo '<div class="text-center p-8">';
            echo '<i class="fas fa-file-word text-blue-500 text-5xl mb-4"></i>';
            echo '<p class="text-gray-700 mb-4">Word document (DOC) cannot be previewed directly.</p>';
            echo '<a href="data:application/msword;base64,'.base64_encode($course['certificate']).'" download="certificate.doc" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">';
            echo '<i class="fas fa-download mr-2"></i> Download Certificate (.doc)';
            echo '</a>';
            echo '</div>';
            break;
            
        case $fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // DOCX
            echo '<div class="text-center p-8">';
            echo '<i class="fas fa-file-word text-blue-500 text-5xl mb-4"></i>';
            echo '<p class="text-gray-700 mb-4">Word document (DOCX) cannot be previewed directly.</p>';
            echo '<a href="data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,'.base64_encode($course['certificate']).'" download="certificate.docx" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">';
            echo '<i class="fas fa-download mr-2"></i> Download Certificate (.docx)';
            echo '</a>';
            echo '</div>';
            break;
            
        default:
            // For any other unsupported formats
            echo '<div class="text-center p-8">';
            echo '<i class="fas fa-file text-gray-500 text-5xl mb-4"></i>';
            echo '<p class="text-gray-700 mb-4">This file format cannot be previewed.</p>';
            echo '<a href="data:application/octet-stream;base64,'.base64_encode($course['certificate']).'" download="certificate" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">';
            echo '<i class="fas fa-download mr-2"></i> Download Certificate';
            echo '</a>';
            echo '</div>';
    }
} else {
    echo '<p class="text-gray-500">Certificate not found.</p>';
}
?>