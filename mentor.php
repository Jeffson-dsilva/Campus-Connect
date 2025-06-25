<?php
require_once 'config.php';
$title = "Mentorship Details";
require_once 'header.php';

// Fetch user data from session
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$query = "SELECT name, usn, email FROM students WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($name, $usn, $email);
$stmt->fetch();
$stmt->close();

if (!$name || !$usn) {
    header("Location: login.php");
    exit();
}

$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name = trim($_POST['student_name'] ?? '');
    $usn = trim($_POST['usn'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $career = trim($_POST['career'] ?? '');

    if (!$student_name || !$usn || !$email) {
        die("Name, USN, and Email are required.");
    }

    // Update or insert mentor_form
    $query = "INSERT INTO mentor_form (usn, student_name, email, phone, career) 
              VALUES (?, ?, ?, ?, ?) 
              ON DUPLICATE KEY UPDATE 
              student_name = VALUES(student_name), 
              email = VALUES(email), 
              phone = VALUES(phone)";
    if (!empty($career)) {
        $query .= ", career = VALUES(career), submitted_at = CURRENT_TIMESTAMP";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $usn, $student_name, $email, $phone, $career);
    $stmt->execute();
    $stmt->close();

    // Hobbies
    if (!empty($_POST['hobbies']) && is_array($_POST['hobbies'])) {
        foreach ($_POST['hobbies'] as $hobby) {
            $hobby = trim($hobby);
            if ($hobby !== '') {
                $check = $conn->prepare("SELECT id FROM student_hobbies WHERE usn = ? AND hobby = ?");
                $check->bind_param("ss", $usn, $hobby);
                $check->execute();
                $check->store_result();
                if ($check->num_rows === 0) {
                    $insert = $conn->prepare("INSERT INTO student_hobbies (usn, hobby) VALUES (?, ?)");
                    $insert->bind_param("ss", $usn, $hobby);
                    $insert->execute();
                    $insert->close();
                }
                $check->close();
            }
        }
    }

    // Achievements
    if (!empty($_POST['achievements']) && is_array($_POST['achievements'])) {
        foreach ($_POST['achievements'] as $achievement) {
            $achievement = trim($achievement);
            if ($achievement !== '') {
                $check = $conn->prepare("SELECT id FROM student_achievements WHERE usn = ? AND achievement = ?");
                $check->bind_param("ss", $usn, $achievement);
                $check->execute();
                $check->store_result();
                if ($check->num_rows === 0) {
                    $insert = $conn->prepare("INSERT INTO student_achievements (usn, achievement) VALUES (?, ?)");
                    $insert->bind_param("ss", $usn, $achievement);
                    $insert->execute();
                    $insert->close();
                }
                $check->close();
            }
        }
    }

    // Internal marks
    for ($sem = 1; $sem <= 6; $sem++) {
        foreach ([1, 2] as $internal_number) {
            $subjects_key = "sem{$sem}_internal{$internal_number}_subject";
            $marks_key = "sem{$sem}_internal{$internal_number}_marks";
            $subjects = $_POST[$subjects_key] ?? [];
            $marks = $_POST[$marks_key] ?? [];

            if (is_array($subjects) && is_array($marks)) {
                for ($i = 0; $i < count($subjects); $i++) {
                    $subject = trim($subjects[$i]);
                    $mark = intval($marks[$i]);

                    if ($subject !== '' && $mark >= 0 && $mark <= 100) {
                        // Check if the entry exists
                        $check = $conn->prepare("SELECT id FROM internal_marks WHERE usn = ? AND semester = ? AND internal_number = ? AND subject_code = ?");
                        $check->bind_param("siis", $usn, $sem, $internal_number, $subject);
                        $check->execute();
                        $check->store_result();

                        if ($check->num_rows > 0) {
                            $update = $conn->prepare("UPDATE internal_marks SET marks = ? WHERE usn = ? AND semester = ? AND internal_number = ? AND subject_code = ?");
                            $update->bind_param("isiis", $mark, $usn, $sem, $internal_number, $subject);
                            $update->execute();
                            $update->close();
                        } else {
                            $insert = $conn->prepare("INSERT INTO internal_marks (usn, semester, internal_number, subject_code, marks) VALUES (?, ?, ?, ?, ?)");
                            $insert->bind_param("siisi", $usn, $sem, $internal_number, $subject, $mark);
                            $insert->execute();
                            $insert->close();
                        }
                        $check->close();
                    }
                }
            }
        }
    }

    $submitted = true;
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Mentorship Details</h1>
                <p class="text-blue-100 font-medium">Share your academic and personal details with your mentor</p>
            </div>

            <!-- Form Content -->
            <div class="p-8 md:p-10">
                <form id="mentorForm" method="POST" action="mentor.php" class="space-y-6">
                    <!-- Student Information Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Student Information</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name Field -->
                            <div class="space-y-2">
                                <label for="student_name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="student_name" name="student_name" type="text" value="<?php echo htmlspecialchars($name); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="nameError"></p>
                            </div>

                            <!-- USN Field -->
                            <div class="space-y-2">
                                <label for="usn" class="block text-sm font-medium text-gray-700">USN <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="usn" name="usn" type="text" value="<?php echo htmlspecialchars($usn); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="usnError"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Email Field -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="emailError"></p>
                            </div>

                            <!-- Phone Field -->
                            <div class="space-y-2">
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <div class="relative">
                                    <input id="phone" name="phone" type="tel" placeholder="Enter your phone number"
                                        class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-red-600 hidden" id="phoneError"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Internal Marks Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Internal Marks</h2>
                        
                        <div class="space-y-2">
                            <label for="semesterSelect" class="block text-sm font-medium text-gray-700">Select Semester <span class="text-red-500">*</span></label>
                            <select id="semesterSelect" onchange="showSemesterSection()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="">-- Select Semester --</option>
                                <?php for ($sem = 1; $sem <= 6; $sem++): ?>
                                    <option value="sem<?php echo $sem; ?>">Semester <?php echo $sem; ?></option>
                                <?php endfor; ?>
                            </select>
                            <p class="mt-1 text-sm text-red-600 hidden" id="semesterError"></p>
                        </div>

                        <?php for ($sem = 1; $sem <= 6; $sem++): ?>
                            <div class="semester-block bg-gray-50 p-4 rounded-lg border border-gray-200" id="sem<?php echo $sem; ?>-section" style="display: none;">
                                <h3 class="text-lg font-medium text-gray-800 mb-4">Semester <?php echo $sem; ?></h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Internal 1 -->
                                    <div class="space-y-4">
                                        <h4 class="text-md font-medium text-gray-700">Internal 1</h4>
                                        <div id="sem<?php echo $sem; ?>-internal1" class="space-y-3">
                                            <div class="marks-entry flex gap-1">
                                                <input type="text" name="sem<?php echo $sem; ?>_internal1_subject[]" placeholder="Subject name" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                                <input type="number" name="sem<?php echo $sem; ?>_internal1_marks[]" placeholder="Marks" min="0" max="100" class="w-24 px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                        </div>
                                        <button type="button" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg" onclick="addMarks('sem<?php echo $sem; ?>-internal1', 'sem<?php echo $sem; ?>_internal1_subject[]', 'sem<?php echo $sem; ?>_internal1_marks[]')">
                                            + Add Subject
                                        </button>
                                    </div>

                                    <!-- Internal 2 -->
                                    <div class="space-y-4">
                                        <h4 class="text-md font-medium text-gray-700">Internal 2</h4>
                                        <div id="sem<?php echo $sem; ?>-internal2" class="space-y-3">
                                            <div class="marks-entry flex gap-1">
                                                <input type="text" name="sem<?php echo $sem; ?>_internal2_subject[]" placeholder="Subject name" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                                <input type="number" name="sem<?php echo $sem; ?>_internal2_marks[]" placeholder="Marks" min="0" max="100" class="w-24 px-3 py-2 border border-gray-300 rounded-lg">
                                            </div>
                                        </div>
                                        <button type="button" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg" onclick="addMarks('sem<?php echo $sem; ?>-internal2', 'sem<?php echo $sem; ?>_internal2_subject[]', 'sem<?php echo $sem; ?>_internal2_marks[]')">
                                            + Add Subject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Hobbies Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Hobbies</h2>
                        
                        <div id="hobbies-section" class="space-y-3">
                            <div class="relative">
                                <input type="text" name="hobbies[]" placeholder="Enter your hobby" class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" onclick="addHobby()" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            + Add Another Hobby
                        </button>
                    </div>

                    <!-- Achievements Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Achievements</h2>
                        
                        <div id="achievements-section" class="space-y-3">
                            <div class="relative">
                                <input type="text" name="achievements[]" placeholder="Enter your achievement" class="w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" onclick="addAchievement()" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            + Add Another Achievement
                        </button>
                    </div>

                    <!-- Career Aspirations Section -->
                    <div class="space-y-6">
                        <h2 class="text-xl font-semibold text-gray-800 border-b border-gray-200 pb-2">Career Aspirations</h2>
                        
                        <div class="space-y-2">
                            <textarea id="career" name="career" rows="5" placeholder="Describe your career goals and aspirations..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg shadow-lg transition-all duration-300 transform hover:scale-[1.01] focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                            Save Mentorship Details
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
                <p class="text-green-100">Your mentorship details have been saved</p>
            </div>
            <div class="p-6 text-center">
                <p class="text-gray-600 mb-6">Your mentor will review your details and get in touch with you.</p>
                <button onclick="closeModal()" class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Return to Dashboard
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Form validation function
function validateForm(event) {
    let isValid = true;

    // Clear previous errors
    document.querySelectorAll('[id$="Error"]').forEach(function(el) {
        el.textContent = '';
        el.classList.add('hidden');
    });

    // Name validation
    const name = document.getElementById('student_name').value.trim();
    if (name === '') {
        isValid = false;
        document.getElementById('nameError').textContent = 'Name is required.';
        document.getElementById('nameError').classList.remove('hidden');
    }

    // USN validation
    const usn = document.getElementById('usn').value.trim();
    if (usn === '') {
        isValid = false;
        document.getElementById('usnError').textContent = 'USN is required.';
        document.getElementById('usnError').classList.remove('hidden');
    }

    // Email validation
    const email = document.getElementById('email').value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === '') {
        document.getElementById('emailError').textContent = 'Email is required.';
        document.getElementById('emailError').classList.remove('hidden');
        isValid = false;
    } else if (!emailRegex.test(email)) {
        document.getElementById('emailError').textContent = 'Enter a valid email address.';
        document.getElementById('emailError').classList.remove('hidden');
        isValid = false;
    }

    // Semester selection validation
    const semesterSelect = document.getElementById('semesterSelect').value;
    if (semesterSelect === '') {
        document.getElementById('semesterError').textContent = 'Please select a semester.';
        document.getElementById('semesterError').classList.remove('hidden');
        isValid = false;
    }

    if (!isValid) {
        event.preventDefault();
    }

    return isValid;
}

// Add event listener for form submission
document.getElementById('mentorForm').addEventListener('submit', validateForm);

function showSemesterSection() {
    const selected = document.getElementById("semesterSelect").value;
    for (let i = 1; i <= 6; i++) {
        const section = document.getElementById("sem" + i + "-section");
        section.style.display = (selected === "sem" + i) ? "block" : "none";
    }
}

function addMarks(containerId, subjectName, marksName) {
    const container = document.getElementById(containerId);
    const div = document.createElement("div");
    div.className = "marks-entry flex gap-3 mt-3";

    const subjectInput = document.createElement("input");
    subjectInput.type = "text";
    subjectInput.name = subjectName;
    subjectInput.placeholder = "Subject name";
    subjectInput.className = "flex-1 px-3 py-2 border border-gray-300 rounded-lg";

    const marksInput = document.createElement("input");
    marksInput.type = "number";
    marksInput.name = marksName;
    marksInput.placeholder = "Marks";
    marksInput.min = "0";
    marksInput.max = "100";
    marksInput.className = "w-24 px-3 py-2 border border-gray-300 rounded-lg";

    div.appendChild(subjectInput);
    div.appendChild(marksInput);
    container.appendChild(div);
}

function addHobby() {
    const section = document.getElementById('hobbies-section');
    const div = document.createElement("div");
    div.className = "relative";

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'hobbies[]';
    input.placeholder = 'Enter your hobby';
    input.className = 'w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all';

    const icon = document.createElement("div");
    icon.className = "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none";
    icon.innerHTML = '<svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>';

    div.appendChild(input);
    div.appendChild(icon);
    section.appendChild(div);
}

function addAchievement() {
    const section = document.getElementById('achievements-section');
    const div = document.createElement("div");
    div.className = "relative";

    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'achievements[]';
    input.placeholder = 'Enter your achievement';
    input.className = 'w-full px-4 py-3 pl-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all';

    const icon = document.createElement("div");
    icon.className = "absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none";
    icon.innerHTML = '<svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';

    div.appendChild(input);
    div.appendChild(icon);
    section.appendChild(div);
}

function closeModal() {
    document.getElementById('successModal').style.display = 'none';
    window.location.href = 'stDashboard.php';
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