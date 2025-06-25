<?php
// header.php
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

// Check if user is faculty
$isFaculty = false;

// Get current page for active tab highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Campus Connect'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        .nav-shadow {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .active-tab {
            background-color: #3b82f6;
            color: white;
        }

        .active-tab:hover {
            background-color: #2563eb !important;
            color: white !important;

        }

        .opacity-0 {
            opacity: 0;
        }

        .scale-95 {
            transform: scale(0.95);
        }

        .transform {
            transform: translateZ(0);
        }

        .transition-all {
            transition-property: all;
        }

        .duration-200 {
            transition-duration: 200ms;
        }
    </style>
</head>

<body class="min-h-screen font-sans bg-gradient-to-br from-blue-100 to-indigo-400">
    <!-- Enhanced Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 nav-shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Section -->
                <div class="flex-shrink-0 flex items-center">
                    <div class="flex items-center space-x-3">
                        <img class="h-10 w-auto" src="images/logo.png" alt="College Logo">
                        <div class="md:block">
                            <h1 class="text-xl md:text-md font-bold text-blue-600">Campus Connect</h1>
                            <p class="text-xs text-gray-600">St. Joseph Engineering College</p>
                        </div>
                    </div>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-4">
                    <div class="flex space-x-1">
                        <a href="stDashboard.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'stDashboard.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="internship.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'internship.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-briefcase mr-1"></i> Internship
                        </a>
                        <a href="project.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'project.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-project-diagram mr-1"></i> Projects
                        </a>
                        <a href="course.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'course.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-book-open mr-1"></i> Courses
                        </a>
                        <a href="mentor.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'mentor.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-user-friends mr-1"></i> Mentorship
                        </a>
                        <a href="classops_dashboard.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'classops_dashboard.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-chalkboard-teacher mr-1"></i> Class Ops
                        </a>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative ml-4">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none group">
                            <div
                                class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white group-hover:bg-blue-700 transition-colors">
                                <?php echo strtoupper(substr($name, 0, 1)); ?>
                            </div>
                            <span
                                class="text-sm font-medium text-gray-700 hidden lg:inline"><?php echo explode(' ', $name)[0]; ?></span>
                            <i
                                class="fas fa-chevron-down text-xs text-gray-500 group-hover:text-gray-700 transition-colors"></i>
                        </button>

                       <div id="user-dropdown" class="hidden opacity-0 scale-95 absolute right-0 mt-4 w-56 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200 transform transition-all duration-200 origin-top-right">
                        <div class="py-2 px-4 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($name); ?>
                                </p>
                                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($email); ?></p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $isFaculty ? 'Faculty' : 'Student'; ?>:
                                    <span class="font-medium"><?php echo htmlspecialchars($usn); ?></span>
                                </p>
                            </div>
                            <a href="stupdatePassword.php"
                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-150">
                                <i class="fas fa-key mr-2 text-blue-500"></i> Update Password
                            </a>
                            <button onclick="logout()"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors duration-150">
                                <i class="fas fa-sign-out-alt mr-2 text-blue-500"></i> Sign out
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center space-x-3">
                    <button id="mobile-menu-button"
                        class="p-2 rounded-md text-gray-600 hover:bg-blue-50 hover:text-blue-600 focus:outline-none transition-colors">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu"
            class="hidden md:hidden bg-white border-t border-gray-200 transform transition-all duration-300 ease-in-out">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="stDashboard.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'stDashboard.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="internship.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'internship.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-briefcase mr-2"></i> Internship
                </a>
                <a href="project.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'project.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-project-diagram mr-2"></i> Projects
                </a>
                <a href="course.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'course.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-book-open mr-2"></i> Courses
                </a>
                <a href="mentor.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'mentor.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-user-friends mr-2"></i> Mentorship
                </a>
                <a href="classops_dashboard.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'classops_dashboard.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-chalkboard-teacher mr-2"></i> Class Ops
                </a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center px-4">
                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                        <?php echo strtoupper(substr($name, 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($name); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($usn); ?></p>
                    </div>
                </div>
                <div class="mt-3 px-2 space-y-1">
                    <a href="stupdatePassword.php"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                        <i class="fas fa-key mr-2 text-blue-500"></i> Update Password
                    </a>
                    <button onclick="logout()"
                        class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2 text-blue-500"></i> Sign out
                    </button>
                </div>
            </div>
        </div>
    </header>

    <script>
        // Toggle mobile menu with animation
        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('opacity-0');
        });

        // Toggle user dropdown with animation
        document.getElementById('user-menu-button').addEventListener('click', function (e) {
            e.stopPropagation(); // Prevent this click from triggering the document click listener
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('hidden');
            dropdown.classList.toggle('opacity-0');
            dropdown.classList.toggle('scale-95');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (event) {
            const userButton = document.getElementById('user-menu-button');
            const dropdown = document.getElementById('user-dropdown');

            const mobileButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (!userButton.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
                dropdown.classList.add('opacity-0');
                dropdown.classList.add('scale-95');
            }

            if (!mobileButton.contains(event.target) && !mobileMenu.contains(event.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.add('opacity-0');
            }
        });

        function logout() {
            // You might want to add a proper logout handler here
            window.location.href = 'login.php';
        }
    </script>