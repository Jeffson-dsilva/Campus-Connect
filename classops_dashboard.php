<?php
require_once 'auth_check.php';
$auth = verifySession();
// Check if user is student or faculty
$isFaculty = ($auth['type'] === 'faculty');
$isStudent = ($auth['type'] === 'student');

// Get user identifier
$userIdentifier = $auth['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "college_ipm_system";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get enrolled classes for the current user
$enrolledClasses = [];
if ($isStudent) {
    $stmt = $conn->prepare("
        SELECT c.class_id, c.class_code, c.title, c.section, c.description, c.faculty_empid, c.thumbnail_url 
        FROM classops_classes c
        JOIN classops_enrollments e ON c.class_id = e.class_id
        WHERE e.student_usn = ?
    ");
    $stmt->bind_param("s", $userIdentifier);
} elseif ($isFaculty) {
    $stmt = $conn->prepare("
        SELECT class_id, class_code, title, section, description, faculty_empid, thumbnail_url 
        FROM classops_classes 
        WHERE faculty_empid = ?
    ");
    $stmt->bind_param("s", $userIdentifier);
}

$stmt->execute();
$result = $stmt->get_result();
$enrolledClasses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$userClasses = $enrolledClasses;

?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Classroom</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans&amp;display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Google Sans', Arial, sans-serif;
            background-color: #a2a8d3
        }

        .active-tab.bg-blue-100 {
            background-color: #ebf8ff !important;
        }

        .collapsed .menu-text,
        .collapsed #enrolledArrow {
            display: none;
        }

        .collapsed #enrolledMenu {
            display: none !important;
        }

        #sidebar .fas {
            min-width: 1.25rem;
            text-align: center;
        }

        /* Loading spinner */
        .btn-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        #sidebar {

            transition: 0.6s ease-in-out;
        }

        #maincontent {

            transition: 0.6s ease-in-out;
        }

        /* Button transitions */
        button {
            transition: background-color 0.2s ease;
        }

        /* Disabled button state */
        button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Add this to your existing style section */
        [id^="classMenu"] {
            right: 0;
            top: 100%;
            margin-top: 0.25rem;
        }

        .class-menu-btn {
            padding: 0.25rem;
            margin-right: -0.25rem;
            /* Adjust for better alignment */
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-400 min-h-screen overflow-x-hidden">
    <header class="flex items-center justify-between px-4 py-2 border-b border-gray-300 bg-white">
        <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-2">
                <button aria-label="Menu" class="p-2 focus:outline-none" id="menuToggle">
                    <i class="fas fa-bars text-gray-700 text-xl"></i>
                </button>
                <img alt="Google Classroom logo" class="w-6 h-6" height="24"
                    src="https://storage.googleapis.com/a1aa/image/2b935d19-0d52-4a18-00cf-ad6246526972.jpg"
                    width="24" />
                <span class="text-gray-800 text-lg select-none">ClassOps</span>
            </div>
        </div>
        <div class="flex items-center space-x-6">
            <!-- Only show create class option for faculty -->
            <div class="relative inline-block text-left">
                <button id="plusBtn"
                    class="text-gray-600 text-3xl px-2 py-2 font-bold hover:bg-gray-100 rounded-lg focus:outline-none"
                    aria-label="Add">
                    +
                </button>
                <div id="plusDropdown"
                    class="hidden origin-top-right absolute right-0 mt-2 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50">
                    <div class="py-1 text-gray-700">
                        <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100" onclick="joinClass()">Join
                            Class</a>

                        <?php if ($isFaculty): ?>
                            <a href="#" class="block px-4 py-2 text-sm hover:bg-gray-100"
                                onclick="openModal('createClassModal')">Create Class</a>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button aria-label="User" class="text-gray-700 text-2xl font-light leading-none focus:outline-none">
                    <i class="fas fa-user text-xl text-gray-600 px-2 py-2 hover:bg-gray-100 rounded-lg focus:outline-none"
                        id="user-icon"></i>
                </button>
                <div id="user-dropdown" class="hidden absolute right-0 mt-4 w-48 bg-white rounded-md shadow-lg z-50">
                    <div class="py-1 px-4">
                        <p class="text-sm text-gray-700"><?php echo htmlspecialchars($auth['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($auth['email']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo $isFaculty ? 'Faculty' : 'Student'; ?>:
                            <?php echo htmlspecialchars($auth['id']); ?>
                        </p>
                    </div>
                    <div class="border-t border-gray-200"></div>
                    <a href="login.php"
                        class="block px-4 py-2 text-sm text-center text-gray-700 hover:bg-green-500 hover:text-white">Sign
                        out</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside id="sidebar"
        class="fixed top-14 left-0 h-[calc(100%-3.5rem)] bg-white shadow-lg transition-all duration-300 z-40 w-64 overflow-hidden">
        <nav class="p-4 text-gray-800 space-y-1 text-lg">
            <!-- Home -->
            <a href="#" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe] active-tab">
                <i class="fas fa-home w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Home</span>
            </a>

            <!-- Enrolled Courses -->
            <div>
                <button id="enrolledToggle"
                    class="w-full flex items-center justify-between px-4 py-2 rounded hover:bg-[#e8f0fe] focus:outline-none">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-book w-5 text-gray-600 text-2xl"></i>
                        <span class="menu-text"><?php echo $isFaculty ? 'My Classes' : 'Enrolled Courses'; ?></span>
                    </div>
                    <i id="enrolledArrow" class="fas fa-chevron-down text-xs text-gray-600"></i>
                </button>
                <div id="enrolledMenu" class="ml-8 mt-1 space-y-1 hidden">
                    <?php foreach ($userClasses as $c): ?>
                        <a href="class_view.php?id=<?php echo $c['class_id']; ?>"
                            class="block px-2 py-1 rounded hover:bg-[#e8f0fe] text-sm"><?php echo htmlspecialchars($c['title']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Assignments -->
            <a href="student_assignments.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
                <i class="fas fa-tasks w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Assignments</span>
            </a>

            <!-- Campus Connect -->
            <a href="<?php echo $isFaculty ? 'ftDashboard.php' : 'stDashboard.php'; ?>"
                class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
                <i class="fas fa-university w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Campus Connect</span>
            </a>
        </nav>
    </aside>

    <div id="mainContent" class="transition-all duration-300 ml-64">
        <main class="flex">
            <section class="p-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 flex-1">
                <?php foreach ($userClasses as $class):
                    $descriptionParts = explode(" - ", $class['description']);
                    $semester = $descriptionParts[0] ?? '';
                    $subject = $descriptionParts[1] ?? $class['title'];

                    $facultyName = $class['faculty_empid'];
                    $initial = !empty($facultyName) ? strtoupper(substr($facultyName, 0, 1)) : '?';

                    $gradientColors = [
                        "linear-gradient(90deg, #00897B 0%, #00796B 100%)",
                        "linear-gradient(90deg, #546E7A 0%, #455A64 100%)",
                        "linear-gradient(90deg, #388E3C 0%, #2E7D32 100%)",
                        "linear-gradient(90deg, #2C54C1 0%, #1A237E 100%)",
                        "linear-gradient(90deg,rgb(148, 68, 11) 0%,rgb(246, 139, 9) 100%)",
                        "linear-gradient(90deg,rgb(213, 196, 12) 0%,rgb(20, 111, 6) 100%)",
                        "linear-gradient(90deg,rgb(234, 9, 140) 0%,rgb(43, 16, 30) 100%)"
                    ];
                    $gradient = $gradientColors[array_rand($gradientColors)];
                    ?>
                    <!-- Wrap the entire card in a link, but exclude the dropdown menu -->
                    <div class="relative">
                        <article class="bg-white rounded-lg shadow-sm max-w-xs w-full hover:shadow-md transition-shadow">
                            <a href="class_view.php?id=<?php echo $class['class_id']; ?>" class="block no-underline">
                                <div class="relative rounded-t-lg p-4" style="background: <?php echo $gradient; ?>">
                                    <h2 class="text-white font-semibold text-lg leading-tight truncate max-w-[11rem]">
                                        <?php echo htmlspecialchars($class['title']); ?>
                                    </h2>
                                    <p class="text-white text-xs mt-1 font-semibold">
                                        <?php echo htmlspecialchars($semester); ?> -
                                        <?php echo htmlspecialchars($class['section']); ?>
                                    </p>
                                    <p class="text-white text-sm mt-1 font-normal">
                                        <?php echo $isFaculty ? 'Your Class' : htmlspecialchars($facultyName); ?>
                                    </p>
                                    <img alt="Class thumbnail"
                                        class="absolute top-0 right-0 rounded-tr-lg rounded-bl-lg w-20 h-20 object-cover"
                                        src="<?php echo htmlspecialchars($class['thumbnail_url']); ?>" />
                                </div>
                            </a>
                            <footer class="flex justify-end p-3 border-t border-gray-200">
                                <!-- Move the dropdown menu to the footer -->
                                <div class="relative">
                                    <button aria-label="More options"
                                        class="text-gray-600 hover:text-gray-900 focus:outline-none class-menu-btn"
                                        data-class-id="<?php echo $class['class_id']; ?>"
                                        onclick="event.preventDefault(); event.stopPropagation(); toggleClassMenu(this)">
                                        <i class="fas fa-ellipsis-v text-lg"></i>
                                    </button>
                                    <div id="classMenu<?php echo $class['class_id']; ?>"
                                        class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                        <div class="py-1">
                                            <?php if ($isFaculty && $class['faculty_empid'] === $auth['id']): ?>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#e8f0fe]"
                                                    onclick="event.preventDefault(); return confirm('Are you sure? This will permanently delete the class!') && deleteClass(<?php echo $class['class_id']; ?>)">
                                                    Delete Class
                                                </a>
                                            <?php else: ?>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-[#e8f0fe]"
                                                    onclick="event.preventDefault(); return confirm('Are you sure you want to unenroll?') && unenrollFromClass(<?php echo $class['class_id']; ?>, '<?php echo $auth['id']; ?>', <?php echo $isFaculty ? 'true' : 'false'; ?>)">
                                                    Unenroll
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </footer>
                        </article>

                    </div>
                <?php endforeach; ?>
            </section>
        </main>
    </div>

    <?php if ($isFaculty): ?>
        <!-- CREATE CLASS MODAL (Only for faculty) -->
        <div id="createClassModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 relative">
                <!-- Modal Header -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Create Class</h2>
                    <button onclick="closeModal('createClassModal')"
                        class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
                </div>

                <!-- Modal Form -->
                <form id="createClassForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Class Name</label>
                            <input type="text" name="class_name"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Section</label>
                            <input type="text" name="section"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Semester</label>
                            <select name="semester"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                                <option value="">Select Semester</option>
                                <option value="1st Sem">1st Sem</option>
                                <option value="2nd Sem">2nd Sem</option>
                                <option value="3rd Sem">3rd Sem</option>
                                <option value="4th Sem">4th Sem</option>
                                <option value="5th Sem">5th Sem</option>
                                <option value="6th Sem">6th Sem</option>
                                <option value="7th Sem">7th Sem</option>
                                <option value="8th Sem">8th Sem</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject</label>
                            <input type="text" name="subject"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end mt-6 space-x-3">
                        <button type="button" onclick="closeModal('createClassModal')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                        <button type="button" onclick="generateClassCode()"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Create</button>
                    </div>

                    <!-- Success Message -->
                    <div id="successMessage" class="hidden mt-4 p-3 bg-green-50 text-green-800 rounded">
                        <p class="font-medium">Class created successfully!</p>
                        <p class="text-sm mt-1">Share this code or link to invite people to your class.</p>
                    </div>
                </form>

                <!-- Generated Info -->
                <div class="flex items-center mt-4 space-x-3">
                    <div class="text-lg font-mono text-blue-800 bg-blue-50 px-4 py-2 rounded" id="classCodeDisplay">ABC123
                    </div>
                    <button onclick="copyClassCode()"
                        class="text-md px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">Copy</button>
                    <span id="copySuccess" class="text-green-600 text-sm hidden">Copied!</span>
                </div>

                <!-- Class Link -->
                <div class="mt-3">
                    <a id="classLinkDisplay" href="#" target="_blank"
                        class="text-sm text-blue-600 underline break-words block"></a>
                </div>

                <!-- Share/Copy Buttons -->
                <div class="mt-3 space-y-2">
                    <!-- Mobile Share Button -->
                    <button onclick="shareClassLink()"
                        class="block md:hidden flex items-center text-sm px-3 py-2 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        <i class="fas fa-share-alt mr-2"></i> Share
                    </button>

                    <!-- Desktop Copy Link Button -->
                    <button onclick="copyLinkToClipboard()"
                        class="hidden md:inline-block text-sm px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
                        Copy Link
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- JOIN CLASS MODAL (For students) -->
    <div id="joinClassModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Join Class</h2>
                <button onclick="closeModal('joinClassModal')"
                    class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
            </div>

            <!-- Info -->
            <p class="text-sm text-gray-600 mb-4">
                Ask your teacher for the class code, then enter it below.
            </p>

            <!-- Join Form -->
            <form id="joinClassForm">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Class Code</label>
                    <input type="text" name="class_code" id="joinClassCode" placeholder="e.g. A8K29f"
                        class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400 uppercase"
                        required>
                </div>

                <div class="text-sm text-gray-500 mb-2">
                    - Class codes are 6-10 characters long, and can contain letters and numbers.<br>
                    - Codes don't contain spaces or special characters.
                </div>

                <!-- Buttons -->
                <div class="flex justify-end mt-6 space-x-3">
                    <button type="button" onclick="closeModal('joinClassModal')"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Join</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // DOM Elements
        const plusBtn = document.getElementById('plusBtn');
        const plusDropdown = document.getElementById('plusDropdown');
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const enrolledToggle = document.getElementById('enrolledToggle');
        const enrolledMenu = document.getElementById('enrolledMenu');
        const enrolledArrow = document.getElementById('enrolledArrow');
        const mainContent = document.getElementById('mainContent');
        const isFaculty = <?php echo $isFaculty ? 'true' : 'false'; ?>;

        // Add this to your existing JavaScript
        document.getElementById('user-icon').addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            document.getElementById('user-dropdown').classList.add('hidden');
        });

        // Event Listeners
        plusBtn.addEventListener('click', () => {
            plusDropdown.classList.toggle('hidden');
        });

        document.addEventListener('click', (event) => {
            if (!plusBtn.contains(event.target) && !plusDropdown.contains(event.target)) {
                plusDropdown.classList.add('hidden');
            }
        });

        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('w-64');
            sidebar.classList.toggle('w-16');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('ml-64');
            mainContent.classList.toggle('ml-16');
        });

        enrolledToggle.addEventListener('click', () => {
            enrolledMenu.classList.toggle('hidden');
            enrolledArrow.classList.toggle('rotate-180');
        });

        // Modal Functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            if (id === 'createClassModal') {
                document.getElementById("createClassForm").reset();
                document.getElementById("successMessage").classList.add("hidden");
            }
        }

        // Only include create class functions for faculty
        <?php if ($isFaculty): ?>
            let isSubmitting = false;

            async function generateClassCode() {
                if (isSubmitting) return;
                isSubmitting = true;

                try {
                    const createBtn = document.querySelector('#createClassModal button[onclick*="generateClassCode"]');
                    const originalBtnText = createBtn.textContent;
                    const originalBtnHTML = createBtn.innerHTML;

                    // Show loading spinner
                    createBtn.disabled = true;
                    createBtn.innerHTML = '<span class="btn-spinner"></span> Creating...';

                    // Generate random class code
                    const code = Array.from({ length: 6 }, () =>
                        'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'[Math.floor(Math.random() * 62)]
                    ).join('');

                    // Get form values
                    const classForm = document.getElementById('createClassForm');
                    const getValue = (name) => {
                        const input = classForm?.querySelector(`[name="${name}"]`);
                        return input?.value.trim() || '';
                    };

                    const formData = new URLSearchParams();
                    formData.append('class_code', code);
                    formData.append('class_name', getValue('class_name'));
                    formData.append('section', getValue('section'));
                    formData.append('semester', getValue('semester'));
                    formData.append('subject', getValue('subject'));
                    formData.append('faculty_empid', "<?php echo $_SESSION['employee_id']; ?>");
                    formData.append('thumbnail_url', "https://storage.googleapis.com/a1aa/image/45b21e3f-62a0-4f43-de13-71ec87110a96.jpg");

                    // Simulate processing (replace with actual fetch in production)
                    await new Promise(resolve => setTimeout(resolve, 2000));

                    const response = await fetch('create_class.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.error || "Failed to create class");
                    }

                    // Update UI
                    document.getElementById("classCodeDisplay").textContent = code;
                    document.getElementById("classLinkDisplay").textContent = `https://campusconnect/classroom/${code}`;
                    document.getElementById("classLinkDisplay").href = `https://campusconnect/classroom/${code}`;
                    document.getElementById("successMessage").classList.remove("hidden");

                    // Change button to Close
                    createBtn.textContent = 'Close';
                    createBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    createBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    createBtn.onclick = () => {
                        closeModal('createClassModal');
                        // Reset button after closing
                        setTimeout(() => {
                            createBtn.innerHTML = originalBtnHTML;
                            createBtn.textContent = originalBtnText;
                            createBtn.onclick = generateClassCode;
                            createBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                            createBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                            createBtn.disabled = false;
                        }, 300);
                    };

                } catch (error) {
                    console.error("Error:", error);
                    alert(error.message);
                    // Reset button on error
                    const createBtn = document.querySelector('#createClassModal button[onclick*="generateClassCode"]');
                    if (createBtn) {
                        createBtn.disabled = false;
                        createBtn.textContent = 'Create';
                        createBtn.onclick = generateClassCode;
                    }
                } finally {
                    isSubmitting = false;
                }
            }

            function copyClassCode() {
                const code = document.getElementById("classCodeDisplay").textContent;
                navigator.clipboard.writeText(code).then(() => {
                    const successMsg = document.getElementById("copySuccess");
                    successMsg.classList.remove("hidden");
                    setTimeout(() => successMsg.classList.add("hidden"), 1500);
                });
            }

            function copyLinkToClipboard() {
                const link = document.getElementById("classLinkDisplay").href;
                navigator.clipboard.writeText(link).then(() => {
                    document.getElementById("copySuccess").classList.remove("hidden");
                    setTimeout(() => {
                        document.getElementById("copySuccess").classList.add("hidden");
                    }, 2000);
                });
            }

            function shareClassLink() {
                const title = "Join My Class on Campus Connect";
                const text = "Click the link below to join:";
                const url = document.getElementById("classLinkDisplay").href;

                if (navigator.share) {
                    navigator.share({ title, text, url }).catch(console.error);
                } else {
                    copyLinkToClipboard();
                    alert("Link copied to clipboard. You can now share it manually.");
                }
            }
        <?php endif; ?>

        function joinClass() {
            // Remove the faculty check - now both can join
            openModal('joinClassModal');
        }

        document.getElementById('joinClassForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const codeInput = document.getElementById('joinClassCode');
            const code = codeInput.value.trim().toUpperCase();
            const joinBtn = this.querySelector('button[type="submit"]');

            // Save original button state
            const originalBtnText = joinBtn.textContent;

            try {
                // Validate input
                if (!code || code.length < 6 || code.length > 10 || !/^[A-Z0-9]+$/.test(code)) {
                    throw new Error('Class code must be 6-10 alphanumeric characters');
                }

                // Show loading state
                joinBtn.disabled = true;
                joinBtn.innerHTML = '<span class="btn-spinner"></span> Joining...';

                // Make the API request
                const response = await fetch('join_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_code=${encodeURIComponent(code)}`
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Failed to join class');
                }

                // Success - show message and refresh
                const userType = data.user_type || 'user';
                alert(`Successfully joined class as ${userType}!`);
                closeModal('joinClassModal');

                // Refresh after 1 second to show new class
                setTimeout(() => location.reload(), 1000);

            } catch (error) {
                // Show error to user
                alert(error.message);

                // Highlight the input field
                codeInput.focus();
                codeInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                setTimeout(() => {
                    codeInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                }, 3000);

            } finally {
                // Restore button state
                joinBtn.disabled = false;
                joinBtn.textContent = originalBtnText;
            }
        });

        function toggleClassMenu(button) {
            // Close all other menus first
            document.querySelectorAll('[id^="classMenu"]').forEach(menu => {
                if (menu.id !== button.nextElementSibling.id) {
                    menu.classList.add('hidden');
                }
            });

            // Toggle the menu for this button
            const menu = button.nextElementSibling;
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking elsewhere
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.class-menu-btn') && !e.target.closest('[id^="classMenu"]')) {
                document.querySelectorAll('[id^="classMenu"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        async function unenrollFromClass(classId, userId, isFaculty) {
            try {
                const response = await fetch('unenroll_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_id=${classId}&user_id=${userId}&is_faculty=${isFaculty}`
                });

                const data = await response.json();

                if (data.success) {
                    alert('Successfully unenrolled from class');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to unenroll'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while unenrolling');
            }
        }

        async function deleteClass(classId) {
            try {
                const response = await fetch('delete_class.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `class_id=${classId}`
                });

                const data = await response.json();

                if (data.success) {
                    alert('Class deleted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete class'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while deleting the class');
            }
        }
    </script>
</body>

</html>