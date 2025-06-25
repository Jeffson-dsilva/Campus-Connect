<?php
require_once 'config.php';
$title = "MOOC Course Details";
require_once 'header.php';

// Fetch user data from session
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$query = "SELECT name, usn FROM students WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($name, $usn);
$stmt->fetch();
$stmt->close();

if (!$name || !$usn) {
    header("Location: login.php");
    exit();
}

$submitted = false; // Initialize submission flag

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and fetch form data
    $name = trim($_POST['name']);
    $usn = trim($_POST['USN']);
    $course_name = trim($_POST['course-name']);
    $platform = trim($_POST['platform']);
    $start_date = trim($_POST['start-date']);
    $end_date = trim($_POST['end-date']);
    $certificate_link = trim($_POST['certificate-link']);

    $uploaded_file = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file = file_get_contents($_FILES['certificate']['tmp_name']);
    }

    $stmt = $conn->prepare(
        "INSERT INTO mooc_courses (name, usn, course_name, platform, start_date, end_date, certificate_link, certificate) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssss", $name, $usn, $course_name, $platform, $start_date, $end_date, $certificate_link, $uploaded_file);

    if ($stmt->execute()) {
        $submitted = true; // Set flag instead of redirecting
    } else {
        echo "<script>alert('Error saving course details: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
}
?>

<main class="min-h-screen  bg-gradient-to-br from-blue-100 to-indigo-400 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Form Card with Glass Morphism Effect -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl overflow-hidden border border-white/20">
            <!-- Form Header with Gradient -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-full mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">MOOC Course Details</h1>
                <p class="text-blue-100 font-medium">Share your online learning experience with us</p>
            </div>

            <!-- Form Content -->
            <div class="p-8 md:p-10">
                <form id="courseForm" action="course.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <p class="text-sm text-gray-500 font-medium">Fields marked with <span class="text-red-500">*</span>
                        are required</p>

                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Personal
                            Information</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="name" name="name" type="text" placeholder="Your full name"
                                        value="<?php echo htmlspecialchars($name); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="name-error"></p>
                            </div>

                            <!-- USN Field -->
                            <div class="space-y-2">
                                <label for="USN" class="block text-sm font-medium text-gray-700">USN <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="USN" name="USN" type="text" placeholder="Your USN"
                                        value="<?php echo htmlspecialchars($usn); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="USN-error"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Course Details Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Course Details
                        </h2>

                        <!-- Add these new fields right after the section header -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Course Name Field -->
                            <div class="space-y-2">
                                <label for="course-name" class="block text-sm font-medium text-gray-700">Course Name
                                    <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="course-name" name="course-name" type="text"
                                        placeholder="Name of the MOOC course"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="course-name-error"></p>
                            </div>
                            <!-- Platform Field -->
                            <div class="space-y-2">
                                <label for="platform" class="block text-sm font-medium text-gray-700">Platform <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="platform" name="platform" type="text"
                                        placeholder="e.g., Coursera, edX, Udemy"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="platform-error"></p>
                            </div>
                            <!-- Date Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date
                                        <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input id="start-date" name="start-date" type="date"
                                            class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                            required>
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="start-date-error"></p>
                                </div>
                                <div class="space-y-2">
                                    <label for="end-date" class="block text-sm font-medium text-gray-700">End Date <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input id="end-date" name="end-date" type="date"
                                            class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                            required>
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600 hidden" id="end-date-error"></p>
                                </div>
                            </div>

                            <!-- Certificate Link Field -->
                            <div class="space-y-2">
                                <label for="certificate-link"
                                    class="block text-sm font-medium text-gray-700">Certificate Link <span
                                        class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="certificate-link" name="certificate-link" type="text"
                                        placeholder="Enter certificate URL"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="certificate-link-error"></p>
                            </div>

                            <!-- Certificate Upload Field -->
                            <div class="space-y-2">
                                <label for="certificate" class="block text-sm font-medium text-gray-700">Certificate
                                    File <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="certificate" name="certificate" type="file"
                                        accept=".pdf,.doc,.docx,.jpg,.png" onchange="validateFile(this)"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                        required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, DOC, DOCX, JPG, PNG (Max
                                    200KB)</p>
                                <p class="mt-1 text-sm text-red-600 hidden" id="file-error"></p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6">
                            <button type="submit"
                                class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 transform hover:scale-[1.01] focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                                Submit Course Details
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline ml-2 -mr-1"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>
</main>

<!-- Success Modal -->
<?php if ($submitted): ?>
    <div id="successModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-md w-full animate-zoom-in">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/20 rounded-full mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Success!</h2>
                <p class="text-green-100">Your course details have been submitted</p>
            </div>
            <div class="p-6 text-center">
                <p class="text-gray-600 mb-6">Thank you for sharing your MOOC course details with us.</p>
                <button onclick="closeModal()"
                    class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Return to Dashboard
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Update the JavaScript section in course.php -->
<script>
    // Form validation and submission handling
    document.getElementById('courseForm').addEventListener('submit', function (event) {
        event.preventDefault();
        let isValid = true;
        clearErrors();

        // Add validation for new fields
        const courseName = document.getElementById('course-name').value.trim();
        if (!courseName) {
            document.getElementById('course-name-error').textContent = 'Course name is required';
            document.getElementById('course-name-error').classList.remove('hidden');
            isValid = false;
        }

        const platform = document.getElementById('platform').value;
        if (!platform) {
            document.getElementById('platform-error').textContent = 'Platform is required';
            document.getElementById('platform-error').classList.remove('hidden');
            isValid = false;
        }


        // Validate name
        const name = document.getElementById('name').value.trim();
        if (!name) {
            document.getElementById('name-error').textContent = 'Name is required';
            document.getElementById('name-error').classList.remove('hidden');
            isValid = false;
        }

        // Validate USN
        const usn = document.getElementById('USN').value.trim();
        if (!usn) {
            document.getElementById('USN-error').textContent = 'USN is required';
            document.getElementById('USN-error').classList.remove('hidden');
            isValid = false;
        }

        // Validate start date
        const startDate = document.getElementById('start-date').value;
        if (!startDate) {
            document.getElementById('start-date-error').textContent = 'Start date is required';
            document.getElementById('start-date-error').classList.remove('hidden');
            isValid = false;
        }

        // Validate end date
        const endDate = document.getElementById('end-date').value;
        if (!endDate) {
            document.getElementById('end-date-error').textContent = 'End date is required';
            document.getElementById('end-date-error').classList.remove('hidden');
            isValid = false;
        } else if (startDate && new Date(endDate) < new Date(startDate)) {
            document.getElementById('end-date-error').textContent = 'End date cannot be earlier than start date';
            document.getElementById('end-date-error').classList.remove('hidden');
            isValid = false;
        }

        // Validate certificate link
        const certificateLink = document.getElementById('certificate-link').value.trim();
        if (!certificateLink) {
            document.getElementById('certificate-link-error').textContent = 'Certificate link is required';
            document.getElementById('certificate-link-error').classList.remove('hidden');
            isValid = false;
        }

        // Validate file upload
        const certificateFile = document.getElementById('certificate').files[0];
        if (!certificateFile) {
            document.getElementById('file-error').textContent = 'Certificate file is required';
            document.getElementById('file-error').classList.remove('hidden');
            isValid = false;
        } else {
            const validTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
            const maxSize = 200 * 1024; // 200KB
            const fileExtension = certificateFile.name.split('.').pop().toLowerCase();

            if (!validTypes.includes(fileExtension)) {
                document.getElementById('file-error').textContent = 'Invalid file type. Please upload a valid file (PDF, DOC, DOCX, JPG, PNG).';
                document.getElementById('file-error').classList.remove('hidden');
                isValid = false;
            } else if (certificateFile.size > maxSize) {
                document.getElementById('file-error').textContent = 'File size exceeds the maximum limit of 200KB.';
                document.getElementById('file-error').classList.remove('hidden');
                isValid = false;
            }
        }

        // If form is valid, submit the form
        if (isValid) {
            this.submit(); // Submit the form
        }
    });

    // Close success modal and redirect
    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
        window.location.href = 'stDashboard.php';
    }

    // Clear error messages
    function clearErrors() {
        const errorMessages = document.querySelectorAll('[id$="-error"]');
        errorMessages.forEach(msg => {
            msg.textContent = '';
            msg.classList.add('hidden');
        });
    }

    // Validate file upload
    function validateFile(input) {
        const file = input.files[0];
        const fileError = document.getElementById('file-error');
        const validTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        const maxSize = 200 * 1024; // 200KB

        if (file) {
            const fileExtension = file.name.split('.').pop().toLowerCase();
            if (!validTypes.includes(fileExtension)) {
                fileError.textContent = 'Invalid file type. Please upload a valid file (PDF, DOC, DOCX, JPG, PNG).';
                fileError.classList.remove('hidden');
                input.value = '';
            } else if (file.size > maxSize) {
                fileError.textContent = 'File size exceeds the maximum limit of 200KB.';
                fileError.classList.remove('hidden');
                input.value = '';
            } else {
                fileError.textContent = '';
                fileError.classList.add('hidden');
            }
        }
    }
</script>

<style>
    /* Add this to your existing styles */
    .border-red-500 {
        border-color: #ef4444 !important;
    }

    @keyframes zoom-in {
        0% {
            transform: scale(0.95);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .animate-zoom-in {
        animation: zoom-in 0.3s ease-out forwards;
    }
</style>

</body>

</html>