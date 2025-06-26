<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if auth_check.php exists
if (!file_exists('auth_check.php')) {
    die("Error: auth_check.php file not found");
}

require_once 'auth_check.php';

try {
    $auth = verifySession();
    
    if ($auth['type'] !== 'faculty') {
        header("Location: classops_dashboard.php");
        exit();
    }

    $postId = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    if (!$postId) {
        header("Location: classops_dashboard.php");
        exit();
    }

    // Database connection with error handling
    $conn = new mysqli("localhost", "root", "", "college_ipm_system", $port=3307);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get post details
    $stmt = $conn->prepare("
        SELECT p.post_id, p.title, p.due_date, c.class_id, c.title as class_title
        FROM classops_posts p
        JOIN classops_classes c ON p.class_id = c.class_id
        WHERE p.post_id = ? AND p.faculty_empid = ?
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("is", $postId, $auth['id']);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        header("Location: classops_dashboard.php");
        exit();
    }

    // Get submissions
    $stmt = $conn->prepare("
        SELECT s.submission_id, s.student_usn, s.submitted_at, s.grade, s.remarks, 
               s.submission_file_name, s.submission_file_type,
               st.name as student_name
        FROM classops_submissions s
        LEFT JOIN students st ON s.student_usn = st.usn
        WHERE s.post_id = ?
        ORDER BY s.submitted_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $postId);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    // Log the error and show a user-friendly message
    error_log("Error in assignment_submission.php: " . $e->getMessage());
    die("An error occurred while processing your request. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Submissions for <?php echo htmlspecialchars($post['title']); ?> | ClassOps</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans&amp;display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Google Sans', Arial, sans-serif;
            background-color: #a2a8d3;
        }

        .submission-card {
            transition: all 0.2s ease;
        }
        .submission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .file-pill {
            transition: all 0.2s ease;
        }
        .file-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
    </style>
</head>
<body class="bg-gradient-to-br from-blue-100 to-indigo-400 min-h-screen overflow-x-hidden">

<!-- Header -->
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
                <a href="class_view.php?id=<?php echo $post['class_id']; ?>"
                    class="block px-2 py-1 rounded hover:[#e8f0fe] text-sm"><?php echo htmlspecialchars($post['class_title']); ?></a>
            </div>
        </div>

        <!-- Assignments -->
        <a href="#" class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe] bg-blue-100">
            <i class="fas fa-tasks w-5 text-gray-600 text-2xl"></i>
            <span class="menu-text">Assignments</span>
        </a>

        <!-- Campus Connect -->
        <a href="ftDashboard.php"
            class="flex items-center space-x-2 px-4 py-2 rounded hover:bg-[#e8f0fe]">
            <i class="fas fa-university w-5 text-gray-600 text-2xl"></i>
            <span class="menu-text">Campus Connect</span>
        </a>
    </nav>
</aside>

<!-- Main Content -->
<main id="maincontent" class="transition-all duration-300 ml-64">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Assignment Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="flex items-center flex-wrap gap-2 mb-2">
                        <span class="text-gray-600"><?php echo htmlspecialchars($post['class_title']); ?></span>
                        <span class="text-gray-400">•</span>
                        <?php if (!empty($post['post_type'])): ?>
                        <span class="text-sm px-2.5 py-0.5 rounded-full 
                        <?php echo $post['post_type'] === 'assignment' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo ucfirst($post['post_type']); ?>
                        </span>
                        <?php endif; ?>

                        <?php if ($post['due_date']): ?>
                            <span class="text-gray-400">•</span>
                            <span class="<?php echo (strtotime($post['due_date']) < time() ? 'text-red-600' : 'text-gray-600'); ?>">
                                <i class="far fa-calendar-alt mr-1"></i>
                                Due: <?php echo date('M j, Y g:i A', strtotime($post['due_date'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-users mr-1"></i>
                    <?php echo count($submissions); ?> submission<?php echo count($submissions) !== 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>

        <!-- Submissions Grid -->
        <div class="space-y-4">
            <?php if (empty($submissions)): ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center border border-gray-100">
                    <i class="far fa-folder-open text-4xl text-gray-300 mb-3"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-1">No submissions yet</h3>
                    <p class="text-gray-500">Students haven't submitted any work for this assignment yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <div class="submission-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                        <div class="p-6">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <!-- Student Info -->
                                <div class="flex-1">
                                    <div class="flex items-start gap-4">
                                        <div class="h-12 w-12 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600">
                                            <i class="fas fa-user-graduate"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($submission['student_name'] ?? $submission['student_usn']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($submission['student_email'] ?? ''); ?></p>
                                            <p class="text-xs text-gray-400 mt-1">
                                                <i class="far fa-clock mr-1"></i>
                                                Submitted on <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Submission Content -->
                                    <div class="mt-4 pl-16">
                                        <?php if (!empty($submission['submission_file_name'])): ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Submitted Work</h4>
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="preview_submission.php?id=<?php echo $submission['submission_id']; ?>" 
                                                       target="_blank"
                                                       class="file-pill bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 flex items-center gap-2">
                                                        <?php if (strpos($submission['submission_file_type'], 'image/') === 0): ?>
                                                            <i class="fas fa-image text-blue-500"></i>
                                                        <?php elseif (strpos($submission['submission_file_type'], 'application/pdf') === 0): ?>
                                                            <i class="fas fa-file-pdf text-red-500"></i>
                                                        <?php elseif (strpos($submission['submission_file_type'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0): ?>
                                                            <i class="fas fa-file-word text-blue-600"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-file-alt text-gray-500"></i>
                                                        <?php endif; ?>
                                                        <span class="text-sm font-medium truncate max-w-xs"><?php echo htmlspecialchars($submission['submission_file_name']); ?></span>
                                                        <span class="text-xs text-gray-500">
                                                            <?php echo isset($submission['submission_file_size']) ? round($submission['submission_file_size'] / 1024, 1) . ' KB' : ''; ?>
                                                        </span>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($submission['remarks']): ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Student Notes</h4>
                                                <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm text-gray-700">
                                                    <?php echo nl2br(htmlspecialchars($submission['remarks'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Grade Section -->
                                <div class="sm:w-48 flex flex-col items-end">
                                    <div class="text-right">
                                        <?php if ($submission['grade']): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1.5"></i>
                                                Graded: <?php echo htmlspecialchars($submission['grade']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                                Needs grading
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <button onclick="openGradeModal(<?php echo $submission['submission_id']; ?>, '<?php echo htmlspecialchars($submission['grade'] ?? ''); ?>', `<?php echo str_replace('`', '\\`', htmlspecialchars($submission['remarks'] ?? '')); ?>`)"
                                            class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-500 inline-flex items-center">
                                        <i class="fas fa-edit mr-1.5"></i>
                                        <?php echo $submission['grade'] ? 'Edit Grade' : 'Add Grade'; ?>
                                    </button>

                                    <div class="mt-auto pt-4">
                                        <a href="download_submission.php?id=<?php echo $submission['submission_id']; ?>" 
                                           class="text-sm font-medium text-gray-700 hover:text-gray-900 inline-flex items-center">
                                            <i class="fas fa-download mr-1.5"></i>
                                            Download Submission
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Grade Modal -->
<div id="gradeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 p-4" aria-modal="true" role="dialog">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md transform transition-all">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Grade Submission</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="gradeForm">
                <input type="hidden" name="action" value="grade">
                <input type="hidden" name="submission_id" id="gradeSubmissionId">
                
                <div class="space-y-4">
                    <div>
                        <label for="gradeInput" class="block text-sm font-medium text-gray-700 mb-1">Grade</label>
                        <input type="text" name="grade" id="gradeInput" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="A, 95/100, Pass, etc.">
                    </div>
                    
                    <div>
                        <label for="gradeFeedback" class="block text-sm font-medium text-gray-700 mb-1">Feedback</label>
                        <textarea name="feedback" id="gradeFeedback" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Provide constructive feedback for the student..."></textarea>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Sidebar toggle functionality
const sidebar = document.getElementById('sidebar');
const menuToggle = document.getElementById('menuToggle');
const enrolledToggle = document.getElementById('enrolledToggle');
const enrolledMenu = document.getElementById('enrolledMenu');
const enrolledArrow = document.getElementById('enrolledArrow');
const maincontent = document.getElementById('maincontent');

menuToggle.addEventListener('click', () => {
    sidebar.classList.toggle('w-64');
    sidebar.classList.toggle('w-16');
    sidebar.classList.toggle('collapsed');
    maincontent.classList.toggle('ml-64');
    maincontent.classList.toggle('ml-16');
});

enrolledToggle.addEventListener('click', () => {
    enrolledMenu.classList.toggle('hidden');
    enrolledArrow.classList.toggle('rotate-180');
});

// User dropdown functionality
document.getElementById('user-icon').addEventListener('click', function (e) {
    e.stopPropagation();
    document.getElementById('user-dropdown').classList.toggle('hidden');
});

document.addEventListener('click', function () {
    document.getElementById('user-dropdown').classList.add('hidden');
});

// Plus button dropdown
const plusBtn = document.getElementById('plusBtn');
const plusDropdown = document.getElementById('plusDropdown');

plusBtn.addEventListener('click', () => {
    plusDropdown.classList.toggle('hidden');
});

document.addEventListener('click', (event) => {
    if (!plusBtn.contains(event.target) && !plusDropdown.contains(event.target)) {
        plusDropdown.classList.add('hidden');
    }
});

function joinClass() {
    alert("Please use the dashboard to join classes");
}

// Grade modal functions
function openGradeModal(submissionId, grade, feedback) {
    document.getElementById('gradeSubmissionId').value = submissionId;
    document.getElementById('gradeInput').value = grade || '';
    document.getElementById('gradeFeedback').value = feedback || '';
    document.getElementById('gradeModal').classList.remove('hidden');
    document.getElementById('gradeInput').focus();
}

function closeModal() {
    document.getElementById('gradeModal').classList.add('hidden');
}

document.getElementById('gradeForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
        <span class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        </span>
    `;
    
    try {
        const response = await fetch('classops_submissions.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            location.reload();
        } else {
            throw new Error(result.error || "Failed to save grade");
        }
    } catch (error) {
        alert(error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});

// Close modal when clicking outside
document.getElementById('gradeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('gradeModal').classList.contains('hidden')) {
        closeModal();
    }
});
</script>
</body>
</html>