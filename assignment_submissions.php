<?php
require_once 'auth_check.php';
$auth = verifySession();

// Only faculty can access this page
if ($auth['type'] !== 'faculty') {
    header("Location: classops_dashboard.php");
    exit();
}

// Get post ID from URL
$postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
if (!$postId) {
    header("Location: classops_dashboard.php");
    exit();
}

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

// Get post details and verify faculty owns it
$stmt = $conn->prepare("
    SELECT p.post_id, p.title, p.due_date, c.class_id, c.title as class_title
    FROM classops_posts p
    JOIN classops_classes c ON p.class_id = c.class_id
    WHERE p.post_id = ? AND p.faculty_empid = ?
");
$stmt->bind_param("is", $postId, $auth['id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    header("Location: classops_dashboard.php");
    exit();
}

// Get all submissions for this assignment
$submissions = [];
$stmt = $conn->prepare("
    SELECT s.submission_id, s.student_usn, s.submitted_at, s.grade, s.remarks, 
           st.name as student_name
    FROM classops_submissions s
    LEFT JOIN students st ON s.student_usn = st.usn
    WHERE s.post_id = ?
    ORDER BY s.submitted_at DESC
");
$stmt->bind_param("i", $postId);
$stmt->execute();
$result = $stmt->get_result();
$submissions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get enrolled classes for the current user (for sidebar)
$userClasses = [];
$stmt = $conn->prepare("
    SELECT class_id, class_code, title, section, description, faculty_empid, thumbnail_url 
    FROM classops_classes 
    WHERE faculty_empid = ?
");
$stmt->bind_param("s", $auth['id']);
$stmt->execute();
$result = $stmt->get_result();
$userClasses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>Submissions for <?php echo htmlspecialchars($post['title']); ?> - ClassOps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans&amp;display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Google Sans', Arial, sans-serif;
            overflow-x: hidden;
        }

        .active-tab {
            background-color: #ebf8ff !important;
        }

        /* Sidebar styles */
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

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 50;
        }

        #sidebar {
            position: fixed;
            top: 56px;
            bottom: 0;
            overflow-y: auto;
            transition: 0.6s ease-in-out;
        }

        #mainContent {
            position: fixed;
            top: 56px;
            left: 8rem;
            right: 0;
            bottom: 0;
            overflow-y: auto;
        }

        /* When sidebar is collapsed */
        .collapsed+#mainContent {
            left: 4rem;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-400 min-h-screen overflow-x-hidden">
    <!-- Header -->
    <!-- Header -->
    <header class="flex items-center justify-between px-4 py-2 border-b border-gray-300 bg-white h-14 z-[100]">
        <div class="flex items-center space-x-4">
            <button aria-label="Menu" class="p-2 focus:outline-none" id="menuToggle">
                <i class="fas fa-bars text-gray-700 text-xl"></i>
            </button>
            <a href="class_view.php?id=<?php echo $post['class_id']; ?>" class="flex items-center space-x-2 mr-4">
                <i class="fas fa-arrow-left text-gray-700 text-xl"></i>
            </a>
            <a href="classops_dashboard.php" class="flex items-center space-x-2">
                <img alt="ClassOps logo" class="w-6 h-6"
                    src="https://storage.googleapis.com/a1aa/image/2b935d19-0d52-4a18-00cf-ad6246526972.jpg" />
                <span class="text-gray-800 text-lg select-none">ClassOps</span>
            </a>
        </div>
        <div class="flex items-center space-x-6">
            <div class="relative z-[110]">
                <button aria-label="User" class="text-gray-700 text-2xl font-light leading-none focus:outline-none">
                    <i class="fas fa-user text-xl text-gray-600 px-2 py-2 hover:bg-gray-100 rounded-lg focus:outline-none"
                        id="user-icon"></i>
                </button>
                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-[120]">
                    <div class="py-1 px-4">
                        <p class="text-sm text-gray-700"><?php echo htmlspecialchars($auth['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($auth['email']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            Faculty: <?php echo htmlspecialchars($auth['id']); ?>
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
            <a href="classops_dashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
                <i class="fas fa-home w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Home</span>
            </a>

            <!-- Enrolled Courses -->
            <div>
                <button id="enrolledToggle"
                    class="w-full flex items-center justify-between px-4 py-2 rounded hover:bg-[#e8f0fe] focus:outline-none">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-book w-5 text-gray-600 text-2xl"></i>
                        <span class="menu-text">My Classes</span>
                    </div>
                    <i id="enrolledArrow" class="fas fa-chevron-down text-xs text-gray-600"></i>
                </button>
                <div id="enrolledMenu" class="ml-8 mt-1 space-y-1 hidden">
                    <?php foreach ($userClasses as $class): ?>
                        <a href="class_view.php?id=<?php echo $class['class_id']; ?>"
                            class="block px-2 py-1 rounded hover:bg-[#e8f0fe] text-sm"><?php echo htmlspecialchars($class['title']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Assignments -->
            <a href="student_assignments.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
                <i class="fas fa-tasks w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Assignments</span>
            </a>

            <!-- Campus Connect -->
            <a href="ftDashboard.php" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
                <i class="fas fa-university w-5 text-gray-600 text-2xl"></i>
                <span class="menu-text">Campus Connect</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <div id="mainContent" class="transition-all duration-300">
        <main class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">
                    Submissions for: <?php echo htmlspecialchars($post['title']); ?>
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Class: <?php echo htmlspecialchars($post['class_title']); ?>
                    <?php if ($post['due_date']): ?>
                        | Due: <?php echo htmlspecialchars($post['due_date']); ?>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Submissions List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <ul class="divide-y divide-gray-200">
                    <?php if (empty($submissions)): ?>
                        <li class="px-6 py-4 text-center text-gray-500">
                            No submissions yet
                        </li>
                    <?php else: ?>
                        <?php foreach ($submissions as $submission): ?>
                            <li class="px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center min-w-0">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                <?php echo htmlspecialchars($submission['student_name'] ?? $submission['student_usn']); ?>
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                Submitted on
                                                <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                            </p>
                                            <?php if ($submission['remarks']): ?>
                                                <p class="text-sm text-gray-700 mt-1">
                                                    <?php echo nl2br(htmlspecialchars($submission['remarks'])); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex flex-col items-end">
                                        <?php if ($submission['grade']): ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Grade: <?php echo htmlspecialchars($submission['grade']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Not graded
                                            </span>
                                        <?php endif; ?>
                                        <button
                                            onclick="openGradeModal(<?php echo $submission['submission_id']; ?>, '<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>', `<?php echo str_replace('`', '\`', htmlspecialchars($submission['remarks'] ?? '')); ?>`)"
                                            class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-500">
                                            <?php echo $submission['grade'] ? 'Edit grade' : 'Add grade'; ?>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </main>
    </div>

    <!-- Grade Submission Modal -->
    <div id="gradeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Grade Submission</h2>
                <button onclick="closeModal('gradeModal')"
                    class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
            </div>

            <form id="gradeForm">
                <input type="hidden" name="action" value="grade">
                <input type="hidden" name="submission_id" id="gradeSubmissionId">

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Grade</label>
                        <input type="text" name="grade" id="gradeInput"
                            class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"
                            placeholder="e.g. A, 95/100, Pass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Feedback</label>
                        <textarea name="feedback" id="gradeFeedback" rows="4"
                            class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"></textarea>
                    </div>
                </div>

                <div class="flex justify-end mt-6 space-x-3">
                    <button type="button" onclick="closeModal('gradeModal')"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        <span id="gradeBtnText">Save Grade</span>
                        <span id="gradeSpinner" class="hidden btn-spinner"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal control functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Grade modal handling
        function openGradeModal(submissionId, grade, feedback) {
            document.getElementById('gradeSubmissionId').value = submissionId;
            document.getElementById('gradeInput').value = grade || '';
            document.getElementById('gradeFeedback').value = feedback || '';
            openModal('gradeModal');
        }

        // Grade form submission
        document.getElementById('gradeForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = document.getElementById('gradeBtnText');
            const spinner = document.getElementById('gradeSpinner');

            try {
                submitBtn.disabled = true;
                btnText.textContent = 'Saving...';
                spinner.classList.remove('hidden');

                const response = await fetch('classops_submissions.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });

                const data = await response.json();

                if (data.success) {
                    closeModal('gradeModal');
                    location.reload();
                } else {
                    throw new Error(data.error || 'Failed to save grade');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Save Grade';
                spinner.classList.add('hidden');
            }
        });

        // Sidebar toggle functionality
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menuToggle');
        const enrolledToggle = document.getElementById('enrolledToggle');
        const enrolledMenu = document.getElementById('enrolledMenu');
        const enrolledArrow = document.getElementById('enrolledArrow');
        const mainContent = document.getElementById('mainContent');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('w-64');
                sidebar.classList.toggle('w-16');
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('ml-64', false);
                mainContent.classList.toggle('ml-16', false);
            });
        }

        if (enrolledToggle) {
            enrolledToggle.addEventListener('click', () => {
                enrolledMenu.classList.toggle('hidden');
                enrolledArrow.classList.toggle('rotate-180');
            });
        }

        // User dropdown functionality
        document.getElementById('user-icon').addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', function () {
            document.getElementById('user-dropdown').classList.add('hidden');
        });
    </script>
</body>

</html>