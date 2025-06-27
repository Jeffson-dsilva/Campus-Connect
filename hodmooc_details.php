<?php
require_once 'config.php';
require_once 'hodheader.php';

$usn = isset($_GET['usn']) ? $conn->real_escape_string($_GET['usn']) : '';

// Fetch student details
$student_sql = "SELECT name, usn FROM students WHERE usn = '$usn'";
$student_result = $conn->query($student_sql);
$student = $student_result->fetch_assoc();

// Fetch ALL MOOC courses for this student
$courses_sql = "SELECT * FROM mooc_courses WHERE usn = '$usn' ORDER BY start_date DESC";
$courses_result = $conn->query($courses_sql);

$title = "MOOC Courses - " . htmlspecialchars($student['name'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-50">
    <main class="mx-auto px-4 py-8 lg:w-3/4 xl:w-3/4 2xl:w-3/4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Card Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-white">MOOC Course Details</h2>
                        <p class="text-indigo-100 text-sm mt-1">All MOOC courses for <?php echo htmlspecialchars($student['name'] ?? ''); ?></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="hodview_mooc.php" class="text-white hover:text-indigo-200 transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <?php if ($student && $courses_result->num_rows > 0) : ?>
                    <!-- Student Information -->
                    <div class="bg-indigo-50 rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-medium text-indigo-800 mb-4 border-b border-indigo-200 pb-2">
                            <i class="fas fa-user-graduate mr-2"></i>Student Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Name</p>
                                <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">USN</p>
                                <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['usn']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Courses List -->
                    <h3 class="text-lg font-medium text-indigo-800 mb-4 border-b border-indigo-200 pb-2">
                        <i class="fas fa-book-open mr-2"></i>Courses (<?php echo $courses_result->num_rows; ?>)
                    </h3>
                    
                    <div class="space-y-6">
                        <?php while ($course = $courses_result->fetch_assoc()) : ?>
                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Course Information -->
                                    <div>
                                        <h4 class="text-md font-medium text-indigo-700 mb-3">Course Details</h4>
                                        <div class="space-y-3">
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">Course Name</p>
                                                <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">Platform</p>
                                                <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['platform']); ?></p>
                                            </div>
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-500">Start Date</p>
                                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['start_date']); ?></p>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-500">End Date</p>
                                                    <p class="mt-1 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course['end_date']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Certificate Information -->
                                    <div>
                                        <h4 class="text-md font-medium text-indigo-700 mb-3">Certificate</h4>
                                        <div class="space-y-3">
                                            <?php if (!empty($course['certificate_link'])) : ?>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-500">Certificate Link</p>
                                                    <a href="<?php echo htmlspecialchars($course['certificate_link']); ?>" 
                                                       target="_blank" 
                                                       class="mt-1 text-sm font-medium text-blue-600 hover:text-blue-800 break-all">
                                                        <?php echo htmlspecialchars($course['certificate_link']); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($course['certificate'])) : ?>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-500">Certificate File</p>
                                                    <button onclick="viewCertificate(<?php echo $course['id']; ?>)" 
                                                            class="mt-1 inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                        <i class="fas fa-eye mr-1"></i> View Certificate
                                                    </button>
                                                </div>
                                            <?php else : ?>
                                                <p class="text-sm text-gray-500">No certificate uploaded</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                <?php elseif ($student) : ?>
                    <div class="text-center py-12">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <i class="fas fa-book-open text-gray-400"></i>
                        </div>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No courses found</h3>
                        <p class="mt-1 text-sm text-gray-500">No MOOC courses found for <?php echo htmlspecialchars($student['name']); ?>.</p>
                    </div>
                <?php else : ?>
                    <div class="text-center py-12">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <i class="fas fa-exclamation-circle text-gray-400"></i>
                        </div>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">Student not found</h3>
                        <p class="mt-1 text-sm text-gray-500">No student found with the provided USN.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificate Modal -->
        <div id="certificateModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Course Certificate
                            </h3>
                            <button id="closeModal" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mt-4" id="certificateContent">
                            <!-- Certificate content will be loaded here via AJAX -->
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button id="closeModalBtn" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Function to view certificate
        function viewCertificate(courseId) {
            fetch(`get_certificate.php?course_id=${courseId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('certificateContent').innerHTML = data;
                    document.getElementById('certificateModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
        }

        // Close modal functionality
        const closeModal = document.getElementById('closeModal');
        const closeModalBtn = document.getElementById('closeModalBtn');

        const closeModalFunc = () => {
            document.getElementById('certificateModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        };

        closeModal?.addEventListener('click', closeModalFunc);
        closeModalBtn?.addEventListener('click', closeModalFunc);

        window.addEventListener('click', (e) => {
            if (e.target === document.getElementById('certificateModal')) {
                closeModalFunc();
            }
        });
    </script>
</body>

</html>