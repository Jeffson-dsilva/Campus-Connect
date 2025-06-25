<?php
require_once 'auth_check.php';
$auth = verifySession();

$isFaculty = ($auth['type'] === 'faculty');
$isStudent = ($auth['type'] === 'student');

// Only students can access this page
if ($auth['type'] !== 'student') {
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


// Get enrolled classes for the current user (for sidebar)
$userClasses = [];
if ($isStudent) {
    $stmt = $conn->prepare("
        SELECT c.class_id, c.class_code, c.title, c.section, c.description, c.faculty_empid, c.thumbnail_url 
        FROM classops_classes c
        JOIN classops_enrollments e ON c.class_id = e.class_id
        WHERE e.student_usn = ?
    ");
    $stmt->bind_param("s", $auth['id']);
} elseif ($isFaculty) {
    $stmt = $conn->prepare("
        SELECT class_id, class_code, title, section, description, faculty_empid, thumbnail_url 
        FROM classops_classes 
        WHERE faculty_empid = ?
    ");
    $stmt->bind_param("s", $auth['id']);
}

$stmt->execute();
$result = $stmt->get_result();
$userClasses = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all assignments for the student across all enrolled classes
$assignments = [];
$stmt = $conn->prepare("
    SELECT 
        p.post_id, 
        p.title as assignment_title, 
        p.content as assignment_description,
        p.due_date,
        p.posted_at,
        c.class_id,
        c.title as class_title,
        f.name as faculty_name,
        s.submission_id,
        s.submitted_at,
        s.grade,
        s.remarks as feedback
    FROM 
        classops_posts p
    JOIN 
        classops_classes c ON p.class_id = c.class_id
    JOIN 
        faculty f ON c.faculty_empid = f.employee_id
    LEFT JOIN 
        classops_submissions s ON p.post_id = s.post_id AND s.student_usn = ?
    JOIN
        classops_enrollments e ON c.class_id = e.class_id AND e.student_usn = ?
    WHERE 
        p.post_type = 'assignment'
    ORDER BY 
        p.due_date ASC, c.title ASC
");
$stmt->bind_param("ss", $auth['id'], $auth['id']);
$stmt->execute();
$result = $stmt->get_result();
$assignments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title>My Assignments - ClassOps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans&amp;display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Google Sans', Arial, sans-serif;
        }
        
        .assignment-card {
            transition: all 0.2s ease;
        }
        
        .assignment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .due-soon {
            border-left: 4px solid #f59e0b;
        }
        
        .overdue {
            border-left: 4px solid #ef4444;
        }
        
        .submitted {
            border-left: 4px solid #10b981;
        }
        
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

        .comment-form textarea {
            transition: height 0.2s ease;
            min-height: 38px;
            max-height: 120px;
        }

        .comment-form textarea:focus {
            min-height: 60px;
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
        }

        #mainContent {
            position: fixed;
            top: 56px;
            left: 16rem;
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

<body class="bg-gradient-to-br from-blue-100 to-indigo-400">
    <!-- Header -->
    <header class="flex items-center justify-between px-4 py-2 border-b border-gray-300 bg-white">
        <div class="flex items-center space-x-2">
            <a href="classops_dashboard.php" class="flex items-center space-x-2">
                <i class="fas fa-arrow-left text-gray-700 text-xl"></i>
                <img alt="ClassOps logo" class="w-6 h-6"
                    src="https://storage.googleapis.com/a1aa/image/2b935d19-0d52-4a18-00cf-ad6246526972.jpg" />
                <span class="text-gray-800 text-lg select-none">ClassOps</span>
            </a>
        </div>
        <div class="flex items-center space-x-6">
            <div class="relative">
                <button aria-label="User" class="text-gray-700 text-2xl font-light leading-none focus:outline-none">
                    <i class="fas fa-user text-xl text-gray-600 px-2 py-2 hover:bg-gray-100 rounded-lg focus:outline-none"
                        id="user-icon"></i>
                </button>
                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50">
                    <div class="py-1 px-4">
                        <p class="text-sm text-gray-700"><?php echo htmlspecialchars($auth['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($auth['email']); ?></p>
                        <p class="text-xs text-gray-500 mt-1">
                            Student: <?php echo htmlspecialchars($auth['id']); ?>
                        </p>
                    </div>
                    <div class="border-t border-gray-200"></div>
                    <a href="login.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-500 hover:text-white">Sign out</a>
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


    <!-- Main Content -->
     
    <div id="mainContent" class="transition-all duration-300 bg-gradient-to-br from-blue-100 to-indigo-400">
    <main class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                My Assignments
            </h1>
            <p class="text-sm text-gray-900 mt-1">
                All assignments from your enrolled classes
            </p>
        </div>

        <!-- Assignments List -->
        <div class="space-y-4">
            <?php if (empty($assignments)): ?>
                <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                    <i class="fas fa-tasks text-4xl text-gray-400 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-900">No assignments found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        You don't have any assignments in your enrolled classes
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($assignments as $assignment): ?>
                    <?php
                    // Determine assignment status
                    $statusClass = '';
                    $statusText = '';
                    $dueDate = new DateTime($assignment['due_date']);
                    $now = new DateTime();
                    
                    if ($assignment['submitted_at']) {
                        $statusClass = 'submitted';
                        $statusText = 'Submitted';
                    } elseif ($now > $dueDate) {
                        $statusClass = 'overdue';
                        $statusText = 'Overdue';
                    } elseif ($dueDate->diff($now)->days <= 3) {
                        $statusClass = 'due-soon';
                        $statusText = 'Due soon';
                    } else {
                        $statusText = 'Pending';
                    }
                    ?>
                    <div class="bg-white rounded-lg shadow-sm assignment-card <?php echo $statusClass; ?>">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($assignment['assignment_title']); ?>
                                    </h2>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Class: <?php echo htmlspecialchars($assignment['class_title']); ?> | 
                                        Faculty: <?php echo htmlspecialchars($assignment['faculty_name']); ?>
                                    </p>
                                    <p class="text-sm text-gray-700 mt-2">
                                        <?php echo nl2br(htmlspecialchars($assignment['assignment_description'])); ?>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $statusClass === 'submitted' ? 'bg-green-100 text-green-800' : ''; ?>
                                        <?php echo $statusClass === 'overdue' ? 'bg-red-100 text-red-800' : ''; ?>
                                        <?php echo $statusClass === 'due-soon' ? 'bg-yellow-100 text-yellow-800' : ''; ?>
                                        <?php echo empty($statusClass) ? 'bg-gray-100 text-gray-800' : ''; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                    <p class="text-sm text-gray-500 mt-2">
                                        Due: <?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Submission Status -->
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <?php if ($assignment['submitted_at']): ?>
                                    <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                            <span class="text-sm font-medium text-green-800">
                                                Submitted on <?php echo date('M j, Y g:i A', strtotime($assignment['submitted_at'])); ?>
                                            </span>
                                        </div>
                                        <?php if ($assignment['grade']): ?>
                                            <div class="mt-2 flex items-center">
                                                <span class="text-sm font-medium text-gray-700 mr-2">Grade:</span>
                                                <span class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($assignment['grade']); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($assignment['feedback']): ?>
                                            <div class="mt-2">
                                                <span class="text-sm font-medium text-gray-700">Feedback:</span>
                                                <p class="text-sm text-gray-900 mt-1">
                                                    <?php echo nl2br(htmlspecialchars($assignment['feedback'])); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <a href="class_view.php?id=<?php echo $assignment['class_id']; ?>#post-<?php echo $assignment['post_id']; ?>"
                                            class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-500">
                                            View assignment
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-between items-center">
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 flex-1 mr-4">
                                            <p class="text-sm text-yellow-800">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                You haven't submitted this assignment yet
                                            </p>
                                        </div>
                                        <a href="class_view.php?id=<?php echo $assignment['class_id']; ?>#post-<?php echo $assignment['post_id']; ?>"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                            Submit Assignment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
    <script>
        // User dropdown toggle
        document.getElementById('user-icon').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', function() {
            document.getElementById('user-dropdown').classList.add('hidden');
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
    </script>
</body>

</html>