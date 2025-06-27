<?php
require_once 'config.php';
require_once 'ftheader.php';

// Initialize variables
$usn = '';
$name = '';
$courses = [];

// Check if the USN is passed as a query parameter
if (isset($_GET['usn'])) {
    $usn = $conn->real_escape_string($_GET['usn']);

    // Query to fetch student details
    $student_sql = "SELECT name FROM students WHERE usn = '$usn'";
    $student_result = $conn->query($student_sql);
    
    if ($student_result->num_rows > 0) {
        $student = $student_result->fetch_assoc();
        $name = $student['name'];
    }

    // Query to fetch ALL MOOC courses for this student
    $courses_sql = "SELECT * FROM mooc_courses WHERE usn = '$usn' ORDER BY start_date DESC";
    $courses_result = $conn->query($courses_sql);
    
    if ($courses_result->num_rows > 0) {
        while ($row = $courses_result->fetch_assoc()) {
            $courses[] = $row;
        }
    }
} else {
    echo "<p class='text-red-500'>Invalid access. No USN provided.</p>";
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOOC Course Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            overflow: auto;
            backdrop-filter: blur(3px);
            transition: all 0.3s ease;
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 2% auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            border: none;
            animation: modalFadeIn 0.3s ease-out;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        
        .close {
            color: #94a3b8;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .close:hover {
            color: #64748b;
            transform: scale(1.1);
        }
        
        /* Certificate container */
        .certificate-container {
            margin-top: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            min-height: 60vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8fafc;
            position: relative;
            overflow: hidden;
        }
        
        .certificate-container img {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        
        .certificate-container iframe {
            width: 100%;
            height: 70vh;
            border: none;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .safe-back-btn {
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
        }
        
        .safe-back-btn:hover {
            background-color: #2563eb;
        }
        
        .safe-back-btn i {
            margin-right: 0.5rem;
        }

        /* Link styling */
        .certificate-link {
            color: #3b82f6;
            text-decoration: none;
            word-break: break-all;
        }
        
        .certificate-link:hover {
            text-decoration: underline;
        }
        
        /* Course card styling */
        .course-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background-color: #f8fafc;
        }
        
        .course-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .course-card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e40af;
        }
        
        .course-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .course-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .course-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400">
    <!-- Header is included from ftheader.php -->
    
    <main class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-md overflow-hidden p-6 mb-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-book-open mr-2 text-blue-600"></i> MOOC Course Details
                </h1>
                <div class="flex space-x-2">
                    <a href="ftmooc.php" class="safe-back-btn">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="bg-green-50 p-6 rounded-lg mb-6">
                <h2 class="text-lg font-semibold text-blue-800 mb-4">
                    <i class="fas fa-user-graduate mr-2"></i> Student Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">USN</label>
                        <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                            <?php echo htmlspecialchars($usn); ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Name</label>
                        <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                            <?php echo htmlspecialchars($name); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Courses List -->
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-blue-800 mb-4">
                    <i class="fas fa-list-ul mr-2"></i> Courses (<?php echo count($courses); ?>)
                </h2>
                
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-card-header">
                                <h3 class="course-card-title">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </h3>
                                <span class="course-badge">
                                    <?php echo htmlspecialchars($course['platform']); ?>
                                </span>
                            </div>
                            
                            <div class="course-details-grid">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">Start Date</label>
                                    <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                        <?php echo htmlspecialchars($course['start_date']); ?>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600">End Date</label>
                                    <div class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
                                        <?php echo htmlspecialchars($course['end_date']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h4 class="text-sm font-medium text-gray-600 mb-2">Certificate</h4>
                                <div class="space-y-3">
                                    <?php if (!empty($course['certificate_link'])): ?>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-600">Certificate Link</label>
                                            <a href="<?php echo htmlspecialchars($course['certificate_link']); ?>" 
                                               target="_blank" 
                                               class="certificate-link">
                                                <?php echo htmlspecialchars($course['certificate_link']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($course['certificate'])): ?>
                                        <div>
                                            <button onclick="viewCertificate('<?php echo $course['id']; ?>')" 
                                                    class="px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                                <i class="fas fa-eye mr-1"></i> View Certificate
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500">No certificate uploaded</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <i class="fas fa-book-open text-gray-400"></i>
                        </div>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No courses found</h3>
                        <p class="mt-1 text-sm text-gray-500">No MOOC courses found for this student.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Certificate Modal -->
    <div id="certificateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-alt mr-2 text-blue-600"></i> Course Certificate
                </h3>
                <span class="close">&times;</span>
            </div>

            <div id="certificateContainer" class="certificate-container">
                <div class="flex flex-col items-center justify-center py-8">
                    <div class="loading-spinner"></div>
                    <p class="mt-3 text-gray-600">Loading certificate...</p>
                </div>
            </div>

            <div class="modal-actions">
                <button onclick="downloadCertificate()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    <i class="fas fa-download mr-2"></i> Download
                </button>
                <button onclick="printCertificate()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById("certificateModal");
        const span = document.getElementsByClassName("close")[0];
        let currentCourseId = null;

        function viewCertificate(courseId) {
            currentCourseId = courseId;
            modal.style.display = "block";
            
            fetch(`get_certificate.php?course_id=${courseId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    document.getElementById('certificateContainer').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching certificate:', error);
                    document.getElementById('certificateContainer').innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-500 mb-4"></i>
                            <p class="text-red-500 font-medium">Error loading certificate</p>
                            <p class="text-gray-600 mt-2">${error.message}</p>
                        </div>`;
                });
        }

        function downloadCertificate() {
            if (!currentCourseId) return;
            
            window.location.href = `download_certificate.php?course_id=${currentCourseId}`;
        }

        function printCertificate() {
            if (!currentCourseId) return;
            
            const printWindow = window.open(`get_certificate.php?course_id=${currentCourseId}&print=1`, '_blank');
            printWindow.onload = function() {
                printWindow.print();
            };
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>