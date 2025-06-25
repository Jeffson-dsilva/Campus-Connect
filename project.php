<?php
require_once 'config.php';
$title = "Project Details";
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
    $usn = trim($_POST['usn']);
    $project_role = trim($_POST['role']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $start_date = trim($_POST['start-date']);
    $end_date = trim($_POST['end-date']);
    $languages_used = trim($_POST['languages-used']);
    $project_title = trim($_POST['project-title']);
    $project_domain = trim($_POST['project-domain']);
    $project_description = trim($_POST['project-description']);
    $features = trim($_POST['features']);
    $problem_statement = trim($_POST['problem-statement']);
    $proposed_solution = trim($_POST['solution']);
    $github_link = trim($_POST['github-link']);
    
    // Handle file upload (as binary data)
    $uploaded_file_content = null;
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $uploaded_file_content = file_get_contents($_FILES['certificate']['tmp_name']); // Read file content
    }

    // Prepare SQL statement
    $stmt = $conn->prepare(
        "INSERT INTO project (name, usn, project_role, phone, location, start_date, end_date, 
        languages_used, project_title, project_domain, project_description, features, 
        problem_statement, proposed_solution, github_link, uploaded_file_name, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters (binary data for uploaded_file_name)
    $stmt->bind_param(
        "ssssssssssssssss",
        $name,
        $usn,
        $project_role,
        $phone,
        $location,
        $start_date,
        $end_date,
        $languages_used,
        $project_title,
        $project_domain,
        $project_description,
        $features,
        $problem_statement,
        $proposed_solution,
        $github_link,
        $uploaded_file_content
    );

    // Execute the statement
    if ($stmt->execute()) {
        $submitted = true; // Set flag instead of redirecting
    } else {
        echo "<script>alert('Error saving project details: " . htmlspecialchars($stmt->error) . "');</script>";
    }
    $stmt->close();
}
?>

<main class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <!-- Form Card with Glass Morphism Effect -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl shadow-xl overflow-hidden border border-white/20">
            <!-- Form Header with Gradient -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white/10 rounded-full mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Project Details</h1>
                <p class="text-blue-100 font-medium">Share your academic project with us</p>
            </div>

            <!-- Form Content -->
            <div class="p-8 md:p-10">
                <form id="project-form" action="project.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <p class="text-sm text-gray-500 font-medium">Fields marked with <span class="text-red-500">*</span> are required</p>

                    <!-- Personal Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Personal Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="name" name="name" type="text" placeholder="Your full name" value="<?php echo htmlspecialchars($name); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="name-error"></p>
                            </div>

                            <!-- USN Field -->
                            <div class="space-y-2">
                                <label for="usn" class="block text-sm font-medium text-gray-700">USN <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="usn" name="usn" type="text" placeholder="Your USN" value="<?php echo htmlspecialchars($usn); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="usn-error"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Project Details Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Project Details</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Role Field -->
                            <div class="space-y-2">
                                <label for="role" class="block text-sm font-medium text-gray-700">Project Role <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="role" name="role" type="text" placeholder="Your role in the project"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="role-error"></p>
                            </div>

                            <!-- Phone Field -->
                            <div class="space-y-2">
                                <label for="phone" class="block text-sm font-medium text-gray-700">Contact No <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="phone" name="phone" type="tel" placeholder="Your contact number"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="phone-error"></p>
                            </div>
                        </div>

                        <!-- Location Field -->
                        <div class="space-y-2">
                            <label for="location" class="block text-sm font-medium text-gray-700">Location <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="location" name="location" type="text" placeholder="Project location"
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-red-600 hidden" id="location-error"></p>
                        </div>

                        <!-- Date Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="start-date" class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="start-date" name="start-date" type="date"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="start-date-error"></p>
                            </div>
                            <div class="space-y-2">
                                <label for="end-date" class="block text-sm font-medium text-gray-700">End Date <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="end-date" name="end-date" type="date"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="end-date-error"></p>
                            </div>
                        </div>

                        <!-- Languages Field -->
                        <div class="space-y-2">
                            <label for="languages-used" class="block text-sm font-medium text-gray-700">Languages/Technologies Used <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="languages-used" name="languages-used" type="text" placeholder="Java, Python, React etc."
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-red-600 hidden" id="languages-used-error"></p>
                        </div>

                        <!-- Project Title Field -->
                        <div class="space-y-2">
                            <label for="project-title" class="block text-sm font-medium text-gray-700">Project Title <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="project-title" name="project-title" type="text" placeholder="Your project title"
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-red-600 hidden" id="project-title-error"></p>
                        </div>

                        <!-- Project Domain Field -->
                        <div class="space-y-2">
                            <label for="project-domain" class="block text-sm font-medium text-gray-700">Project Domain <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="project-domain" name="project-domain" type="text" placeholder="Web Development, Data Science etc."
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-sm text-red-600 hidden" id="project-domain-error"></p>
                        </div>

                        <!-- Project Description Field -->
                        <div class="space-y-2">
                            <label for="project-description" class="block text-sm font-medium text-gray-700">Project Description <span class="text-red-500">*</span></label>
                            <textarea id="project-description" name="project-description" rows="4" placeholder="Describe your project objectives and outcomes"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required></textarea>
                            <p class="mt-1 text-sm text-red-600 hidden" id="project-description-error"></p>
                        </div>

                        <!-- Features Field -->
                        <div class="space-y-2">
                            <label for="features" class="block text-sm font-medium text-gray-700">Project Features/Modules <span class="text-red-500">*</span></label>
                            <textarea id="features" name="features" rows="4" placeholder="List the main features or modules developed"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required></textarea>
                            <p class="mt-1 text-sm text-red-600 hidden" id="features-error"></p>
                        </div>

                        <!-- Problem Statement Field -->
                        <div class="space-y-2">
                            <label for="problem-statement" class="block text-sm font-medium text-gray-700">Problem Statement <span class="text-red-500">*</span></label>
                            <textarea id="problem-statement" name="problem-statement" rows="4" placeholder="Describe the problem the project addresses"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required></textarea>
                            <p class="mt-1 text-sm text-red-600 hidden" id="problem-statement-error"></p>
                        </div>

                        <!-- Solution Field -->
                        <div class="space-y-2">
                            <label for="solution" class="block text-sm font-medium text-gray-700">Proposed Solution <span class="text-red-500">*</span></label>
                            <textarea id="solution" name="solution" rows="4" placeholder="Explain the solution provided by the project"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required></textarea>
                            <p class="mt-1 text-sm text-red-600 hidden" id="solution-error"></p>
                        </div>

                        <!-- GitHub Link Field -->
                        <div class="space-y-2">
                            <label for="github-link" class="block text-sm font-medium text-gray-700">Source Code Repository</label>
                            <div class="relative">
                                <input id="github-link" name="github-link" type="url" placeholder="https://github.com/your-project"
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Certificate Field -->
                        <div class="space-y-2">
                            <label for="certificate" class="block text-sm font-medium text-gray-700">Upload File <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input id="certificate" name="certificate" type="file" accept=".pdf,.doc,.docx,.jpg,.png" onchange="validateFile(this)"
                                    class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, DOC, JPG, PNG (Max 200KB)</p>
                            <p class="mt-1 text-sm text-red-600 hidden" id="file-error"></p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 transform hover:scale-[1.01] focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                            Submit Project Details
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline ml-2 -mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-white mb-2">Success!</h2>
                <p class="text-green-100">Your project details have been submitted</p>
            </div>
            <div class="p-6 text-center">
                <p class="text-gray-600 mb-6">Thank you for sharing your project with us.</p>
                <button onclick="closeModal()" class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Return to Dashboard
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Update the JavaScript section in project.php -->
<script>
// Form validation and submission handling
document.getElementById('project-form').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent default form submission

    let isValid = true;

    // Clear any previous error messages
    clearErrors();

    // Validate name
    const name = document.getElementById('name').value.trim();
    if (!name) {
        document.getElementById('name-error').textContent = 'Name is required';
        document.getElementById('name-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate USN
    const usn = document.getElementById('usn').value.trim();
    if (!usn) {
        document.getElementById('usn-error').textContent = 'USN is required';
        document.getElementById('usn-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate role
    const role = document.getElementById('role').value.trim();
    if (!role) {
        document.getElementById('role-error').textContent = 'Project role is required';
        document.getElementById('role-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate phone
    const phone = document.getElementById('phone').value.trim();
    const phoneRegex = /^[0-9]{10}$/;
    if (!phone) {
        document.getElementById('phone-error').textContent = 'Contact number is required';
        document.getElementById('phone-error').classList.remove('hidden');
        isValid = false;
    } else if (!phoneRegex.test(phone)) {
        document.getElementById('phone-error').textContent = 'Enter a valid 10-digit phone number';
        document.getElementById('phone-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate location
    const location = document.getElementById('location').value.trim();
    if (!location) {
        document.getElementById('location-error').textContent = 'Location is required';
        document.getElementById('location-error').classList.remove('hidden');
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

    // Validate languages used
    const languagesUsed = document.getElementById('languages-used').value.trim();
    if (!languagesUsed) {
        document.getElementById('languages-used-error').textContent = 'Languages/Technologies used are required';
        document.getElementById('languages-used-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate project title
    const projectTitle = document.getElementById('project-title').value.trim();
    if (!projectTitle) {
        document.getElementById('project-title-error').textContent = 'Project title is required';
        document.getElementById('project-title-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate project domain
    const projectDomain = document.getElementById('project-domain').value.trim();
    if (!projectDomain) {
        document.getElementById('project-domain-error').textContent = 'Project domain is required';
        document.getElementById('project-domain-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate project description
    const projectDescription = document.getElementById('project-description').value.trim();
    if (!projectDescription) {
        document.getElementById('project-description-error').textContent = 'Project description is required';
        document.getElementById('project-description-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate features
    const features = document.getElementById('features').value.trim();
    if (!features) {
        document.getElementById('features-error').textContent = 'Project features are required';
        document.getElementById('features-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate problem statement
    const problemStatement = document.getElementById('problem-statement').value.trim();
    if (!problemStatement) {
        document.getElementById('problem-statement-error').textContent = 'Problem statement is required';
        document.getElementById('problem-statement-error').classList.remove('hidden');
        isValid = false;
    }

    // Validate solution
    const solution = document.getElementById('solution').value.trim();
    if (!solution) {
        document.getElementById('solution-error').textContent = 'Solution is required';
        document.getElementById('solution-error').classList.remove('hidden');
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