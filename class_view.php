<?php
require_once 'auth_check.php';
$auth = verifySession();
$isFaculty = ($auth['type'] === 'faculty');
$isStudent = ($auth['type'] === 'student');

// Get class ID from URL
$classId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$classId) {
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

// Get current class details
$stmt = $conn->prepare("
    SELECT c.class_id, c.class_code, c.title, c.section, c.description, c.faculty_empid, 
           f.name as faculty_name, c.thumbnail_url 
    FROM classops_classes c
    LEFT JOIN faculty f ON c.faculty_empid = f.employee_id
    WHERE c.class_id = ?
");
$stmt->bind_param("i", $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    header("Location: classops_dashboard.php");
    exit();
}

// Verify user is enrolled in this class
if ($isFaculty) {
    $checkSql = "SELECT 1 FROM classops_classes WHERE class_id = ? AND faculty_empid = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("is", $classId, $auth['id']);
} else {
    $checkSql = "SELECT 1 FROM classops_enrollments WHERE class_id = ? AND student_usn = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("is", $classId, $auth['id']);
}

$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    header("Location: classops_dashboard.php");
    exit();
}
$checkStmt->close();

// Handle AJAX requests for loading more posts
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $posts_per_page = 10;
    $offset = ($page - 1) * $posts_per_page;

    // Get total post count for this class
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM classops_posts WHERE class_id = ?");
    $countStmt->bind_param("i", $classId);
    $countStmt->execute();
    $totalPosts = $countStmt->get_result()->fetch_row()[0];
    $countStmt->close();

    // Get paginated posts
    $stmt = $conn->prepare("
        SELECT p.post_id, p.post_type, p.title, p.content, p.due_date, p.posted_at,
               f.name as faculty_name, p.faculty_empid
        FROM classops_posts p
        LEFT JOIN faculty f ON p.faculty_empid = f.employee_id
        WHERE p.class_id = ?
        ORDER BY p.posted_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $classId, $posts_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $posts = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get additional data for each post
    foreach ($posts as &$post) {
        // Get files
        $stmt = $conn->prepare("
            SELECT file_id, file_name, file_type 
            FROM classops_post_files 
            WHERE post_id = ?
        ");
        $stmt->bind_param("i", $post['post_id']);
        $stmt->execute();
        $filesResult = $stmt->get_result();
        $post['files'] = $filesResult->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // If assignment, get submission info
        if ($post['post_type'] === 'assignment' && $isStudent) {
            $stmt = $conn->prepare("
                SELECT submission_id, submitted_at, grade, remarks
                FROM classops_submissions
                WHERE post_id = ? AND student_usn = ?
            ");
            $stmt->bind_param("is", $post['post_id'], $auth['id']);
            $stmt->execute();
            $subResult = $stmt->get_result();
            $post['submission'] = $subResult->fetch_assoc();
            $stmt->close();
        }

        // If assignment and faculty, get submission count
        if ($post['post_type'] === 'assignment' && $isFaculty) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as submission_count
                FROM classops_submissions
                WHERE post_id = ?
            ");
            $stmt->bind_param("i", $post['post_id']);
            $stmt->execute();
            $countResult = $stmt->get_result();
            $post['submission_count'] = $countResult->fetch_assoc()['submission_count'];
            $stmt->close();
        }

        // Fetch comments for this post
        $commentsStmt = $conn->prepare("
            SELECT c.*, 
                IF(c.commenter_type = 'faculty', f.name, s.name) as commenter_name
            FROM classops_comments c
            LEFT JOIN faculty f ON c.commenter_type = 'faculty' AND c.commenter_id = f.employee_id
            LEFT JOIN students s ON c.commenter_type = 'student' AND c.commenter_id = s.usn
            WHERE c.post_id = ?
            ORDER BY c.commented_at DESC
        ");
        $commentsStmt->bind_param("i", $post['post_id']);
        $commentsStmt->execute();
        $post['comments'] = $commentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $commentsStmt->close();
    }
    unset($post);

    echo json_encode([
        'posts' => $posts,
        'hasMore' => ($offset + $posts_per_page) < $totalPosts,
        'totalPosts' => $totalPosts,
        'currentPage' => $page
    ]);
    exit();
}

// For initial page load, get first page of posts
$posts_per_page = 10;
$offset = 0;

$stmt = $conn->prepare("
    SELECT p.post_id, p.post_type, p.title, p.content, p.due_date, p.posted_at,
           f.name as faculty_name, p.faculty_empid
    FROM classops_posts p
    LEFT JOIN faculty f ON p.faculty_empid = f.employee_id
    WHERE p.class_id = ?
    ORDER BY p.posted_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $classId, $posts_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get additional data for each post
foreach ($posts as &$post) {
    // Get files
    $stmt = $conn->prepare("
        SELECT file_id, file_name, file_type 
        FROM classops_post_files 
        WHERE post_id = ?
    ");
    $stmt->bind_param("i", $post['post_id']);
    $stmt->execute();
    $filesResult = $stmt->get_result();
    $post['files'] = $filesResult->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // If assignment, get submission info
    if ($post['post_type'] === 'assignment' && $isStudent) {
        $stmt = $conn->prepare("
            SELECT submission_id, submitted_at, grade, remarks
            FROM classops_submissions
            WHERE post_id = ? AND student_usn = ?
        ");
        $stmt->bind_param("is", $post['post_id'], $auth['id']);
        $stmt->execute();
        $subResult = $stmt->get_result();
        $post['submission'] = $subResult->fetch_assoc();
        $stmt->close();
    }

    // If assignment and faculty, get submission count
    if ($post['post_type'] === 'assignment' && $isFaculty) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as submission_count
            FROM classops_submissions
            WHERE post_id = ?
        ");
        $stmt->bind_param("i", $post['post_id']);
        $stmt->execute();
        $countResult = $stmt->get_result();
        $post['submission_count'] = $countResult->fetch_assoc()['submission_count'];
        $stmt->close();
    }

    // Fetch comments for this post
    $commentsStmt = $conn->prepare("
        SELECT c.*, 
            IF(c.commenter_type = 'faculty', f.name, s.name) as commenter_name
        FROM classops_comments c
        LEFT JOIN faculty f ON c.commenter_type = 'faculty' AND c.commenter_id = f.employee_id
        LEFT JOIN students s ON c.commenter_type = 'student' AND c.commenter_id = s.usn
        WHERE c.post_id = ?
        ORDER BY c.commented_at DESC
    ");
    $commentsStmt->bind_param("i", $post['post_id']);
    $commentsStmt->execute();
    $post['comments'] = $commentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $commentsStmt->close();
}
unset($post);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <title><?php echo htmlspecialchars($class['title']); ?> - ClassOps</title>
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

        .post-card {
            transition: all 0.2s ease;
        }

        .post-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .file-item:hover {
            background-color: #f3f4f6;
        }

        .assignment-due {
            color: #d32f2f;
            font-weight: 500;
        }

        .assignment-submitted {
            color: #388e3c;
            font-weight: 500;
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

        #loadMoreBtn {
            transition: all 0.2s ease;
            min-width: 180px;
        }

        #loadMoreBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .btn-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 8px;
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
            transition: 0.6s ease-in-out;
        }

        #mainContent {
            position: fixed;
            top: 128px;
            left: 16rem;
            right: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .class_head {
            position: fixed;
            top: 56px;
            left: 16rem;
            right: 0;
            z-index: 40;
            transition: 0.6s ease-in-out left;
        }

        /* When sidebar is collapsed */
        .collapsed+#mainContent {
            left: 4rem;
        }

        .collapsed+.class_head {
            left: 4rem;
            transition: 0.6s ease-in-out left;
        }
    </style>
</head>

<body class="bg-[#a2a8d3] min-h-screen overflow-x-hidden">
    <!-- Header -->
    <header class="flex items-center justify-between px-4 py-2 border-b border-gray-300 bg-white h-14">
        <div class="flex items-center space-x-2">
            <button aria-label="Menu" class="p-2 focus:outline-none" id="menuToggle">
                <i class="fas fa-bars text-gray-700 text-xl"></i>
            </button>
            <a href="classops_dashboard.php" class="flex items-center space-x-2">
                <img alt="ClassOps logo" class="w-6 h-6"
                    src="https://storage.googleapis.com/a1aa/image/2b935d19-0d52-4a18-00cf-ad6246526972.jpg" />
                <span class="text-gray-800 text-lg select-none">ClassOps</span>
            </a>
        </div>
        <div class="flex items-center space-x-6 z-[100]">
            <div class="relative">
                <button aria-label="User" class="text-gray-700 text-2xl font-light leading-none focus:outline-none">
                    <i class="fas fa-user text-xl text-gray-600 px-2 py-2 hover:bg-gray-100 rounded-lg focus:outline-none"
                        id="user-icon"></i>
                </button>
                <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-[120]">
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
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-green-500 hover:text-white">Sign out</a>
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

    <!-- Class Header -->
    <div class=" class_head bg-[#e8f0fe] shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        <?php echo htmlspecialchars($class['title']); ?>
                    </h1>
                    <div class="mt-1 flex flex-col sm:flex-row sm:flex-wrap sm:mt-0 sm:space-x-6">
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <i class="fas fa-user-tie mr-1"></i>
                            <?php echo htmlspecialchars($class['faculty_name'] ?? $class['faculty_empid']); ?>
                        </div>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <i class="fas fa-layer-group mr-1"></i>
                            <?php echo htmlspecialchars($class['section']); ?>
                        </div>
                        <?php if ($isFaculty): ?>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <i class="fas fa-id-card mr-1"></i>
                                <?php echo htmlspecialchars($class['class_code']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isFaculty): ?>
                    <div class="mt-4 flex md:mt-0 md:ml-4">
                        <button type="button" onclick="openModal('createPostModal')"
                            class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-plus mr-2"></i> Create Post
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="mainContent" class="transition-all duration-300">

        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <!-- Class Posts -->
            <div id="postsContainer" class="space-y-6">
                <?php if (empty($posts)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center">
                        <i class="fas fa-book-open text-4xl text-gray-400 mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900">No posts yet</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <?php echo $isFaculty ? 'Create your first post to get started' : 'Check back later for class materials'; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="bg-white rounded-lg shadow-sm post-card overflow-hidden">
                            <!-- Post Header -->
                            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-start">
                                <div>
                                    <div class="flex items-center">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php echo $post['post_type'] === 'announcement' ? 'bg-blue-100 text-blue-800' : ''; ?>
                                        <?php echo $post['post_type'] === 'material' ? 'bg-green-100 text-green-800' : ''; ?>
                                        <?php echo $post['post_type'] === 'assignment' ? 'bg-purple-100 text-purple-800' : ''; ?>">
                                            <?php echo ucfirst($post['post_type']); ?>
                                        </span>
                                        <?php if ($post['post_type'] === 'assignment' && $post['due_date']): ?>
                                            <?php
                                            $dueDate = new DateTime($post['due_date']);
                                            $now = new DateTime();
                                            $isOverdue = $now > $dueDate;
                                            ?>
                                            <span
                                                class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $isOverdue ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <i class="fas fa-clock mr-1"></i>
                                                Due: <?php echo htmlspecialchars($post['due_date']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h2 class="mt-1 text-lg font-medium text-gray-900">
                                        <?php echo htmlspecialchars($post['title']); ?>
                                    </h2>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Posted by <?php echo htmlspecialchars($post['faculty_name']); ?>
                                        on <?php echo date('M j, Y g:i A', strtotime($post['posted_at'])); ?>
                                    </p>
                                </div>
                                <?php if ($isFaculty && isset($post['faculty_empid']) && $post['faculty_empid'] === $auth['id']): ?>
                                    <div class="relative">
                                        <button onclick="togglePostMenu(this, <?php echo $post['post_id']; ?>)"
                                            class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="postMenu<?php echo $post['post_id']; ?>"
                                            class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                            <div class="py-1">
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    onclick="editPost(<?php echo $post['post_id']; ?>)">
                                                    Edit
                                                </a>
                                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                                    onclick="event.preventDefault(); return confirm('Are you sure? This will permanently delete this post!') && deletePost(<?php echo $post['post_id']; ?>)">
                                                    Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Post Content -->
                            <div class="px-6 py-4">
                                <div class="prose max-w-none">
                                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                                </div>

                                <!-- Files -->
                                <?php if (!empty($post['files'])): ?>
                                    <div class="mt-4 border-t border-gray-200 pt-4">
                                        <h3 class="text-sm font-medium text-gray-500 mb-2">Attachments</h3>
                                        <ul class="space-y-2">
                                            <?php foreach ($post['files'] as $file): ?>
                                                <li class="file-item">
                                                    <a href="download_file.php?file_id=<?php echo $file['file_id']; ?>"
                                                        class="flex items-center p-2 rounded hover:bg-gray-50">
                                                        <i class="fas 
                                                        <?php echo strpos($file['file_type'], 'image/') === 0 ? 'fa-image' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'audio/') === 0 ? 'fa-music' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'video/') === 0 ? 'fa-video' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'application/pdf') === 0 ? 'fa-file-pdf' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'application/msword') === 0 || strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0 ? 'fa-file-word' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'application/vnd.ms-excel') === 0 || strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === 0 ? 'fa-file-excel' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'application/vnd.ms-powerpoint') === 0 || strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.presentationml.presentation') === 0 ? 'fa-file-powerpoint' : ''; ?>
                                                        <?php echo strpos($file['file_type'], 'text/') === 0 ? 'fa-file-alt' : ''; ?>
                                                        <?php echo !in_array(true, [
                                                            strpos($file['file_type'], 'image/') === 0,
                                                            strpos($file['file_type'], 'audio/') === 0,
                                                            strpos($file['file_type'], 'video/') === 0,
                                                            strpos($file['file_type'], 'application/pdf') === 0,
                                                            strpos($file['file_type'], 'application/msword') === 0,
                                                            strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') === 0,
                                                            strpos($file['file_type'], 'application/vnd.ms-excel') === 0,
                                                            strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') === 0,
                                                            strpos($file['file_type'], 'application/vnd.ms-powerpoint') === 0,
                                                            strpos($file['file_type'], 'application/vnd.openxmlformats-officedocument.presentationml.presentation') === 0,
                                                            strpos($file['file_type'], 'text/') === 0
                                                        ]) ? 'fa-file' : ''; ?>
                                                        mr-2 text-gray-500"></i>
                                                        <span
                                                            class="text-sm text-gray-700 truncate"><?php echo htmlspecialchars($file['file_name']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <!-- Assignment Info -->
                                <?php if ($post['post_type'] === 'assignment'): ?>
                                    <div class="mt-4 border-t border-gray-200 pt-4">
                                        <?php if ($isFaculty): ?>
                                            <div class="flex justify-between items-center">
                                                <h3 class="text-sm font-medium text-gray-500">
                                                    Submissions: <?php echo $post['submission_count']; ?>
                                                </h3>
                                                <a href="assignment_submissions.php?post_id=<?php echo $post['post_id']; ?>"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                                    View all
                                                </a>
                                            </div>
                                        <?php elseif ($isStudent): ?>
                                            <h3 class="text-sm font-medium text-gray-500 mb-2">Your Work</h3>
                                            <?php if (isset($post['submission'])): ?>
                                                <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                                        <span class="text-sm font-medium text-green-800">
                                                            Submitted on
                                                            <?php echo date('M j, Y g:i A', strtotime($post['submission']['submitted_at'])); ?>
                                                        </span>
                                                    </div>
                                                    <?php if ($post['submission']['grade']): ?>
                                                        <div class="mt-2 flex items-center">
                                                            <span class="text-sm font-medium text-gray-700 mr-2">Grade:</span>
                                                            <span
                                                                class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($post['submission']['grade']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if ($post['submission']['remarks']): ?>
                                                        <div class="mt-2">
                                                            <span class="text-sm font-medium text-gray-700">Feedback:</span>
                                                            <p class="text-sm text-gray-900 mt-1">
                                                                <?php echo nl2br(htmlspecialchars($post['submission']['remarks'])); ?>
                                                            </p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <button onclick="openSubmissionModal(<?php echo $post['post_id']; ?>, true)"
                                                        class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-500">
                                                        Edit submission
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                                    <p class="text-sm text-yellow-800">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                                        You haven't submitted this assignment yet
                                                    </p>
                                                    <button onclick="openSubmissionModal(<?php echo $post['post_id']; ?>, false)"
                                                        class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                                        Submit Assignment
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Comments Section -->
                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <h3 class="text-sm font-medium text-gray-500 mb-2">Comments</h3>

                                    <!-- Comment Form -->
                                    <div class="mb-4">
                                        <form class="comment-form" data-post-id="<?php echo $post['post_id']; ?>">
                                            <div class="flex items-start space-x-2">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                                        <?php echo strtoupper(substr($auth['name'], 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <textarea name="comment_text" rows="1"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400 resize-none"
                                                        placeholder="Add a comment..."></textarea>
                                                </div>
                                                <button type="submit"
                                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                                    Post
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Comments List -->
                                    <div class="space-y-3" id="comments-<?php echo $post['post_id']; ?>">
                                        <?php foreach ($post['comments'] as $comment): ?>
                                            <div class="flex space-x-2">
                                                <div class="flex-shrink-0">
                                                    <div
                                                        class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-medium">
                                                        <?php echo strtoupper(substr($comment['commenter_name'], 0, 1)); ?>
                                                    </div>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="bg-gray-50 rounded-lg p-3">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($comment['commenter_name']); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-700 mt-1">
                                                            <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                                        </div>
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            <?php echo date('M j, Y g:i A', strtotime($comment['commented_at'])); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($posts) >= $posts_per_page): ?>
                        <div id="loadMoreContainer" class="text-center mt-6">
                            <button id="loadMoreBtn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                <span id="loadMoreText">Load More Posts</span>
                                <span id="loadMoreSpinner" class="hidden btn-spinner"></span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Create Post Modal (Faculty Only) -->
    <?php if ($isFaculty): ?>
        <div id="createPostModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 relative">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Create New Post</h2>
                    <button onclick="closeModal('createPostModal')"
                        class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
                </div>

                <form id="createPostForm">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="class_id" value="<?php echo $classId; ?>">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Post Type</label>
                            <div class="mt-1 grid grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="announcement" class="sr-only peer" checked>
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-bullhorn text-blue-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Announcement</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="material" class="sr-only peer">
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-green-500 peer-checked:bg-green-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-book text-green-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Material</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="assignment" class="sr-only peer">
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-tasks text-purple-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Assignment</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" required
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            <textarea name="content" rows="4"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"></textarea>
                        </div>

                        <div id="assignmentFields" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" name="due_date"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachments</label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="file-upload"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                            <span>Upload files</span>
                                            <input id="file-upload" name="files[]" type="file" multiple class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, DOCX, PPT, JPG, PNG up to 1MB</p>
                                </div>
                            </div>
                            <div id="fileList" class="mt-2 space-y-2"></div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-3">
                        <button type="button" onclick="closeModal('createPostModal')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <span id="createPostBtnText">Create Post</span>
                            <span id="createPostSpinner" class="hidden btn-spinner"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Post Modal (Faculty Only) -->
        <div id="editPostModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 relative">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Edit Post</h2>
                    <button onclick="closeModal('editPostModal')"
                        class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
                </div>

                <form id="editPostForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="post_id" id="editPostId">
                    <input type="hidden" name="class_id" value="<?php echo $classId; ?>">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Post Type</label>
                            <div class="mt-1 grid grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="announcement" class="sr-only peer"
                                        id="editTypeAnnouncement">
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-bullhorn text-blue-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Announcement</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="material" class="sr-only peer"
                                        id="editTypeMaterial">
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-green-500 peer-checked:bg-green-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-book text-green-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Material</span>
                                        </div>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="post_type" value="assignment" class="sr-only peer"
                                        id="editTypeAssignment">
                                    <div
                                        class="p-3 border border-gray-300 rounded-md peer-checked:border-purple-500 peer-checked:bg-purple-50">
                                        <div class="flex items-center">
                                            <i class="fas fa-tasks text-purple-500 mr-2"></i>
                                            <span class="text-sm font-medium text-gray-900">Assignment</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="editPostTitle" required
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            <textarea name="content" id="editPostContent" rows="4"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"></textarea>
                        </div>

                        <div id="editAssignmentFields" class="hidden">
                            <label class="block text-sm font-medium text-gray-700">Due Date</label>
                            <input type="date" name="due_date" id="editPostDueDate"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachments</label>
                            <div id="editFileList" class="mt-2 space-y-2"></div>
                            <div
                                class="mt-2 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="edit-file-upload"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                            <span>Upload more files</span>
                                            <input id="edit-file-upload" name="files[]" type="file" multiple
                                                class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, DOCX, PPT, JPG, PNG up to 1MB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-3">
                        <button type="button" onclick="closeModal('editPostModal')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <span id="editPostBtnText">Update Post</span>
                            <span id="editPostSpinner" class="hidden btn-spinner"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Submission Modal (Students Only) -->
    <?php if ($isStudent): ?>
        <div id="submissionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 relative">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800" id="submissionModalTitle">Submit Assignment</h2>
                    <button onclick="closeModal('submissionModal')"
                        class="text-gray-500 hover:text-red-600 text-2xl">&times;</button>
                </div>

                <form id="submissionForm">
                    <input type="hidden" name="action" value="submit">
                    <input type="hidden" name="post_id" id="submissionPostId">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Your Work</label>
                            <div
                                class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div class="flex text-sm text-gray-600">
                                        <label for="submission-upload"
                                            class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                            <span>Upload file</span>
                                            <input id="submission-upload" name="submission_file" type="file"
                                                class="sr-only">
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PDF, DOCX, PPT, JPG, PNG up to 1MB</p>
                                </div>
                            </div>
                            <div id="submissionFileInfo" class="mt-2 text-sm text-gray-700 hidden">
                                <i class="fas fa-file mr-1"></i>
                                <span id="submissionFileName"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Add a note (optional)</label>
                            <textarea name="remarks" id="submissionRemarks" rows="3"
                                class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-3">
                        <button type="button" onclick="closeModal('submissionModal')"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <span id="submitAssignmentBtnText">Submit Assignment</span>
                            <span id="submitAssignmentSpinner" class="hidden btn-spinner"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Pagination variables
        let currentPage = 1;
        let isLoading = false;
        let hasMorePosts = true;

        // Generate HTML for a single post
        function generatePostHtml(post) {
            // Format due date if assignment
            let dueDateHtml = '';
            if (post.post_type === 'assignment' && post.due_date) {
                const dueDate = new Date(post.due_date);
                const now = new Date();
                const isOverdue = now > dueDate;
                const dueDateClass = isOverdue ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';

                dueDateHtml = `
                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${dueDateClass}">
                        <i class="fas fa-clock mr-1"></i>
                        Due: ${dueDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}
                    </span>
                `;
            }

            // Generate files HTML if any
            let filesHtml = '';
            if (post.files && post.files.length > 0) {
                filesHtml = `
                    <div class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Attachments</h3>
                        <ul class="space-y-2">
                            ${post.files.map(file => `
                                <li class="file-item">
                                    <a href="download_file.php?file_id=${file.file_id}" class="flex items-center p-2 rounded hover:bg-gray-50">
                                        <i class="fas ${getFileIconClass(file.file_type)} mr-2 text-gray-500"></i>
                                        <span class="text-sm text-gray-700 truncate">${escapeHtml(file.file_name)}</span>
                                    </a>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
            }

            // Generate assignment info if assignment
            let assignmentHtml = '';
            if (post.post_type === 'assignment') {
                if (<?php echo $isFaculty ? 'true' : 'false'; ?>) {
                    assignmentHtml = `
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <div class="flex justify-between items-center">
                                <h3 class="text-sm font-medium text-gray-500">
                                    Submissions: ${post.submission_count || 0}
                                </h3>
                                <a href="assignment_submissions.php?post_id=${post.post_id}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                    View all
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    if (post.submission) {
                        assignmentHtml = `
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Your Work</h3>
                                <div class="bg-green-50 border border-green-200 rounded-md p-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        <span class="text-sm font-medium text-green-800">
                                            Submitted on ${new Date(post.submission.submitted_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'short',
                                                day: 'numeric',
                                                hour: '2-digit',
                                                minute: '2-digit'
                                            })}
                                        </span>
                                    </div>
                                    ${post.submission.grade ? `
                                        <div class="mt-2 flex items-center">
                                            <span class="text-sm font-medium text-gray-700 mr-2">Grade:</span>
                                            <span class="text-sm font-medium text-gray-900">${escapeHtml(post.submission.grade)}</span>
                                        </div>
                                    ` : ''}
                                    ${post.submission.remarks ? `
                                        <div class="mt-2">
                                            <span class="text-sm font-medium text-gray-700">Feedback:</span>
                                            <p class="text-sm text-gray-900 mt-1">
                                                ${escapeHtml(post.submission.remarks).replace(/\n/g, '<br>')}
                                            </p>
                                        </div>
                                    ` : ''}
                                    <button onclick="openSubmissionModal(${post.post_id}, true)" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-500">
                                        Edit submission
                                    </button>
                                </div>
                            </div>
                        `;
                    } else {
                        assignmentHtml = `
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-medium text-gray-500 mb-2">Your Work</h3>
                                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
                                    <p class="text-sm text-yellow-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>
                                        You haven't submitted this assignment yet
                                    </p>
                                    <button onclick="openSubmissionModal(${post.post_id}, false)" class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                        Submit Assignment
                                    </button>
                                </div>
                            </div>
                        `;
                    }
                }
            }

            // Generate comments HTML
            const commentsHtml = post.comments && post.comments.length > 0 ?
                post.comments.map(comment => `
                    <div class="flex space-x-2">
                        <div class="flex-shrink-0">
                            <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-medium">
                                ${comment.commenter_name ? comment.commenter_name.charAt(0).toUpperCase() : '?'}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <div class="text-sm font-medium text-gray-900">
                                    ${escapeHtml(comment.commenter_name || 'Unknown')}
                                </div>
                                <div class="text-sm text-gray-700 mt-1">
                                    ${escapeHtml(comment.comment_text).replace(/\n/g, '<br>')}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    ${new Date(comment.commented_at).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    })}
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('') : '';

            return `
                <div class="bg-white rounded-lg shadow-sm post-card overflow-hidden">
                    <!-- Post Header -->
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-start">
                        <div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPostTypeClass(post.post_type)}">
                                    ${post.post_type.charAt(0).toUpperCase() + post.post_type.slice(1)}
                                </span>
                                ${dueDateHtml}
                            </div>
                            <h2 class="mt-1 text-lg font-medium text-gray-900">
                                ${escapeHtml(post.title)}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                Posted by ${escapeHtml(post.faculty_name)}
                                on ${new Date(post.posted_at).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}
                            </p>
                        </div>
                        ${post.faculty_empid === '<?php echo $auth["id"]; ?>' ? `
                        <div class="relative">
                            <button onclick="togglePostMenu(this, ${post.post_id})" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div id="postMenu${post.post_id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                <div class="py-1">
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="editPost(${post.post_id})">
                                        Edit
                                    </a>
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="return confirm('Are you sure? This will permanently delete this post!') && deletePost(${post.post_id})">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>

                    <div class="px-6 py-4">
                        <div class="prose max-w-none">
                            ${escapeHtml(post.content).replace(/\n/g, '<br>')}
                        </div>
                        ${filesHtml}
                        ${assignmentHtml}
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Comments</h3>
                            <div class="mb-4">
                                <form class="comment-form" data-post-id="${post.post_id}">
                                    <div class="flex items-start space-x-2">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                                                ${'<?php echo strtoupper(substr($auth["name"], 0, 1)); ?>'}
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <textarea name="comment_text" rows="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:border-blue-400 resize-none" placeholder="Add a comment..."></textarea>
                                        </div>
                                        <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                            Post
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="space-y-3" id="comments-${post.post_id}">
                                ${commentsHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Helper functions
        function escapeHtml(unsafe) {
            return unsafe ? unsafe.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;") : '';
        }

        function getPostTypeClass(type) {
            const classes = {
                announcement: 'bg-blue-100 text-blue-800',
                material: 'bg-green-100 text-green-800',
                assignment: 'bg-purple-100 text-purple-800'
            };
            return classes[type] || '';
        }

        function getFileIconClass(fileType) {
            if (!fileType) return 'fa-file';

            if (fileType.startsWith('image/')) return 'fa-image';
            if (fileType.startsWith('audio/')) return 'fa-music';
            if (fileType.startsWith('video/')) return 'fa-video';
            if (fileType === 'application/pdf') return 'fa-file-pdf';
            if (fileType === 'application/msword' || fileType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') return 'fa-file-word';
            if (fileType === 'application/vnd.ms-excel' || fileType === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') return 'fa-file-excel';
            if (fileType === 'application/vnd.ms-powerpoint' || fileType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation') return 'fa-file-powerpoint';
            if (fileType.startsWith('text/')) return 'fa-file-alt';

            return 'fa-file';
        }

        // Load more posts function
        async function loadMorePosts() {
            if (isLoading || !hasMorePosts) return;

            isLoading = true;
            document.getElementById('loadMoreText').textContent = 'Loading...';
            document.getElementById('loadMoreSpinner').classList.remove('hidden');

            try {
                currentPage++;
                const response = await fetch(`class_view.php?id=<?php echo $classId; ?>&page=${currentPage}&ajax=1`);
                const data = await response.json();

                if (data.posts && data.posts.length > 0) {
                    const postsContainer = document.getElementById('postsContainer');
                    data.posts.forEach(post => {
                        const postHtml = generatePostHtml(post);
                        postsContainer.insertAdjacentHTML('beforeend', postHtml);
                    });

                    // Update hasMorePosts based on server response
                    hasMorePosts = data.hasMore;

                    // Hide button if no more posts
                    if (!hasMorePosts) {
                        document.getElementById('loadMoreContainer').remove();
                    }
                } else {
                    hasMorePosts = false;
                    document.getElementById('loadMoreContainer').remove();
                }
            } catch (error) {
                console.error('Error loading more posts:', error);
                document.getElementById('loadMoreText').textContent = 'Error - Click to Retry';
                currentPage--; // Retry same page on error
            } finally {
                isLoading = false;
                document.getElementById('loadMoreText').textContent = 'Load More Posts';
                document.getElementById('loadMoreSpinner').classList.add('hidden');
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Attach click handler to load more button
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMorePosts);
            }

            // Also implement infinite scroll
            window.addEventListener('scroll', () => {
                if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 1000) {
                    loadMorePosts();
                }
            });
        });

        // Create post form submission
        document.getElementById('createPostForm')?.addEventListener('submit', async function (e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = document.getElementById('createPostBtnText');
            const spinner = document.getElementById('createPostSpinner');

            try {
                submitBtn.disabled = true;
                btnText.textContent = 'Creating...';
                spinner.classList.remove('hidden');

                const response = await fetch('classops_posts.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    throw new Error(data.error || 'Failed to create post');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                submitBtn.disabled = false;
                btnText.textContent = 'Create Post';
                spinner.classList.add('hidden');
            }
        });

        // Toggle post menu
        function togglePostMenu(button, postId) {
            event.stopPropagation();
            const menu = document.getElementById(`postMenu${postId}`);
            document.querySelectorAll('[id^="postMenu"]').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking elsewhere
        document.addEventListener('click', function () {
            document.querySelectorAll('[id^="postMenu"]').forEach(menu => {
                menu.classList.add('hidden');
            });
        });

        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
            if (id === 'createPostModal') {
                document.getElementById('createPostForm').reset();
                document.getElementById('fileList').innerHTML = '';
                document.getElementById('assignmentFields').classList.add('hidden');
            } else if (id === 'editPostModal') {
                document.getElementById('editFileList').innerHTML = '';
            } else if (id === 'submissionModal') {
                document.getElementById('submissionForm').reset();
                document.getElementById('submissionFileInfo').classList.add('hidden');
            }
        }

        // Toggle assignment fields based on post type
        document.querySelectorAll('input[name="post_type"]').forEach(radio => {
            radio.addEventListener('change', function () {
                const assignmentFields = this.value === 'assignment'
                    ? document.getElementById('assignmentFields')
                    : document.getElementById('editAssignmentFields');
                if (assignmentFields) {
                    assignmentFields.classList.toggle('hidden', this.value !== 'assignment');
                }
            });
        });

        // File upload preview with size validation
        document.getElementById('file-upload')?.addEventListener('change', function (e) {
            const fileList = document.getElementById('fileList');
            fileList.innerHTML = '';
            let totalSize = 0;
            const maxTotalSize = 5 * 1024 * 1024; // 5MB

            Array.from(e.target.files).forEach(file => {
                totalSize += file.size;
                if (file.size > maxTotalSize) {
                    alert(`File ${file.name} exceeds maximum size of 5MB`);
                    e.target.value = ''; // Clear the file input
                    fileList.innerHTML = '';
                    return;
                }

                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center p-2 bg-gray-50 rounded';
                fileItem.innerHTML = `
                    <i class="fas fa-file mr-2 text-gray-500"></i>
                    <span class="text-sm text-gray-700 truncate flex-1">${file.name}</span>
                    <span class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                `;
                fileList.appendChild(fileItem);
            });

            if (totalSize > maxTotalSize) {
                alert('Total size of all files exceeds 5MB limit');
                e.target.value = ''; // Clear the file input
                fileList.innerHTML = '';
            }
        });

        // Edit file upload preview
        document.getElementById('edit-file-upload')?.addEventListener('change', function (e) {
            const fileList = document.getElementById('editFileList');

            Array.from(e.target.files).forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'flex items-center p-2 bg-gray-50 rounded';
                fileItem.innerHTML = `
                    <i class="fas fa-file mr-2 text-gray-500"></i>
                    <span class="text-sm text-gray-700 truncate flex-1">${file.name}</span>
                    <span class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</span>
                `;
                fileList.appendChild(fileItem);
            });
        });

        // Submission file upload preview
        document.getElementById('submission-upload')?.addEventListener('change', function (e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const fileInfo = document.getElementById('submissionFileInfo');
                document.getElementById('submissionFileName').textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                fileInfo.classList.remove('hidden');
            }
        });

        <?php if ($isFaculty): ?>
            // Edit post
            function editPost(postId) {
                // Find the post data
                const post = <?php echo json_encode($posts); ?>.find(p => p.post_id == postId);
                if (!post) return;

                // Set form values
                document.getElementById('editPostId').value = postId;
                document.getElementById('editPostTitle').value = post.title;
                document.getElementById('editPostContent').value = post.content;

                // Set post type
                document.getElementById(`editType${post.post_type.charAt(0).toUpperCase() + post.post_type.slice(1)}`).checked = true;

                // Set due date if assignment
                if (post.post_type === 'assignment') {
                    document.getElementById('editPostDueDate').value = post.due_date || '';
                    document.getElementById('editAssignmentFields').classList.remove('hidden');
                } else {
                    document.getElementById('editAssignmentFields').classList.add('hidden');
                }

                // Display existing files
                const fileList = document.getElementById('editFileList');
                fileList.innerHTML = '';

                post.files.forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'flex items-center justify-between p-2 bg-gray-50 rounded';
                    fileItem.innerHTML = `
                        <div class="flex items-center flex-1 min-w-0">
                            <i class="fas fa-file mr-2 text-gray-500"></i>
                            <span class="text-sm text-gray-700 truncate">${file.file_name}</span>
                        </div>
                        <button type="button" onclick="deleteFile(${file.file_id}, ${postId})" class="text-red-500 hover:text-red-700 ml-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    fileList.appendChild(fileItem);
                });

                // Open modal
                openModal('editPostModal');
            }

            // Edit post form submission
            document.getElementById('editPostForm')?.addEventListener('submit', async function (e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const btnText = document.getElementById('editPostBtnText');
                const spinner = document.getElementById('editPostSpinner');

                try {
                    submitBtn.disabled = true;
                    btnText.textContent = 'Updating...';
                    spinner.classList.remove('hidden');

                    const response = await fetch('classops_posts.php', {
                        method: 'POST',
                        body: formData
                    });

                    // First check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        throw new Error(`Server returned: ${text.substring(0, 100)}...`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.error || 'Failed to update post');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(`Error: ${error.message}`);
                } finally {
                    submitBtn.disabled = false;
                    btnText.textContent = 'Update Post';
                    spinner.classList.add('hidden');
                }
            });

            // Delete file with better error handling and UI feedback
            async function deleteFile(fileId, postId) {
                if (!confirm('Are you sure you want to permanently delete this file?')) return;

                try {
                    // Show loading state
                    const deleteButton = document.querySelector(`#editFileList button[onclick="deleteFile(${fileId}, ${postId})"]`);
                    if (deleteButton) {
                        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        deleteButton.disabled = true;
                    }

                    const response = await fetch('classops_post_files.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=delete&file_id=${fileId}&post_id=${postId}`
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Server returned an error');
                    }

                    if (data.success) {
                        // Remove file from UI with animation
                        const fileElement = deleteButton?.closest('.file-item');
                        if (fileElement) {
                            fileElement.style.transition = 'opacity 0.3s';
                            fileElement.style.opacity = '0';
                            setTimeout(() => fileElement.remove(), 300);
                        }
                    } else {
                        throw new Error(data.error || 'Failed to delete file');
                    }
                } catch (error) {
                    console.error('Delete file error:', error);
                    alert(`Error deleting file: ${error.message}`);

                    // Reset button state
                    const deleteButton = document.querySelector(`#editFileList button[onclick="deleteFile(${fileId}, ${postId})"]`);
                    if (deleteButton) {
                        deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteButton.disabled = false;
                    }
                }
            }

            // Delete post with better UX and error handling
            async function deletePost(postId) {
                if (!confirm('Are you sure you want to permanently delete this post and all its attachments?')) return;

                try {
                    // Show loading state
                    const deleteButton = document.querySelector(`[onclick*="deletePost(${postId})"]`);
                    const originalText = deleteButton?.innerHTML;
                    if (deleteButton) {
                        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                        deleteButton.disabled = true;
                    }

                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('post_id', postId);
                    formData.append('class_id', <?php echo $classId; ?>);

                    const response = await fetch('classops_posts.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Server returned an error');
                    }

                    if (data.success) {
                        // Visual feedback before reload
                        const postElement = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                        if (postElement) {
                            postElement.style.transition = 'opacity 0.5s';
                            postElement.style.opacity = '0';
                            setTimeout(() => {
                                location.reload();
                            }, 500);
                        } else {
                            location.reload();
                        }
                    } else {
                        throw new Error(data.error || 'Failed to delete post');
                    }
                } catch (error) {
                    console.error('Delete post error:', error);
                    alert(`Error deleting post: ${error.message}`);

                    // Reset button state
                    const deleteButton = document.querySelector(`[onclick*="deletePost(${postId})"]`);
                    if (deleteButton) {
                        deleteButton.innerHTML = originalText || 'Delete';
                        deleteButton.disabled = false;
                    }
                }
            }
        <?php endif; ?>

        <?php if ($isStudent): ?>
            // Open submission modal
            function openSubmissionModal(postId, isUpdate) {
                document.getElementById('submissionPostId').value = postId;
                document.getElementById('submissionModalTitle').textContent = isUpdate ? 'Edit Submission' : 'Submit Assignment';
                document.getElementById('submitAssignmentBtnText').textContent = isUpdate ? 'Update Submission' : 'Submit Assignment';

                // If updating, pre-fill remarks
                const post = <?php echo json_encode($posts); ?>.find(p => p.post_id == postId);
                if (isUpdate && post?.submission?.remarks) {
                    document.getElementById('submissionRemarks').value = post.submission.remarks;
                }

                openModal('submissionModal');
            }

            // Submission form
            document.getElementById('submissionForm').addEventListener('submit', async function (e) {
                e.preventDefault();
                const form = e.target;
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const btnText = document.getElementById('submitAssignmentBtnText');
                const spinner = document.getElementById('submitAssignmentSpinner');

                try {
                    submitBtn.disabled = true;
                    btnText.textContent = 'Submitting...';
                    spinner.classList.remove('hidden');

                    const response = await fetch('classops_submissions.php', {
                        method: 'POST',
                        body: formData
                    });

                    // First check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        const text = await response.text();
                        throw new Error(`Server returned: ${text.substring(0, 100)}...`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        alert('Assignment submitted successfully!');
                        closeModal('submissionModal');
                        location.reload();
                    } else {
                        throw new Error(data.error || 'Failed to submit assignment');
                    }
                } catch (error) {
                    console.error('Submission error:', error);
                    alert(`Error: ${error.message}`);
                } finally {
                    submitBtn.disabled = false;
                    btnText.textContent = document.getElementById('submissionModalTitle').textContent === 'Edit Submission'
                        ? 'Update Submission'
                        : 'Submit Assignment';
                    spinner.classList.add('hidden');
                }
            });
        <?php endif; ?>

        // Handle comment submission
        document.querySelectorAll('.comment-form').forEach(form => {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const postId = this.dataset.postId;
                const textarea = this.querySelector('textarea');
                const commentText = textarea.value.trim();

                if (!commentText) return;

                try {
                    const response = await fetch('classops_comments.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=create&post_id=${postId}&comment_text=${encodeURIComponent(commentText)}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Add the new comment to the list
                        const commentsContainer = document.getElementById(`comments-${postId}`);
                        const now = new Date();
                        const formattedDate = now.toLocaleString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const commentHtml = `
                            <div class="flex space-x-2">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-medium">
                                        ${'<?php echo strtoupper(substr($auth["name"], 0, 1)); ?>'}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            ${'<?php echo htmlspecialchars($auth["name"]); ?>'}
                                        </div>
                                        <div class="text-sm text-gray-700 mt-1">
                                            ${commentText.replace(/\n/g, '<br>')}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            ${formattedDate}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        commentsContainer.insertAdjacentHTML('afterbegin', commentHtml);
                        textarea.value = '';
                    } else {
                        throw new Error(data.error || 'Failed to post comment');
                    }
                } catch (error) {
                    alert(error.message);
                }
            });
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