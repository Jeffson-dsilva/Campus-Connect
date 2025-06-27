<?php
// hodheader.php
require_once 'config.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['name']) || !isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$hodName = $_SESSION['name'];
$empId = $_SESSION['employee_id'];

if (!isset($_SESSION['dept'])) {
    // Get department code from database if not in session
    $query = "SELECT dept FROM hod WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $_SESSION['employee_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $hodData = $result->fetch_assoc();
    $_SESSION['dept'] = $hodData['dept']; // Store department in session
}

// Get current page for active tab highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'HOD Portal'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

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

        /* Mobile menu styles */
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
        }

        #mobile-menu.show {
            max-height: 1000px;
        }

        /* User dropdown styles */
        #user-dropdown {
            opacity: 0;
            transform: translateY(-10px);
            visibility: hidden;
            transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
        }

        #user-dropdown.show {
            opacity: 1;
            transform: translateY(0);
            visibility: visible;
        }

        .dept-badge {
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
    </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-100 to-indigo-400">
    <!-- HOD Header -->
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
                        <a href="hodDashboard.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'hodDashboard.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                        <a href="hodviewinternship.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'hodviewinternship.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-briefcase mr-1"></i> Internship
                        </a>
                        <a href="hodviewproject.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'hodviewproject.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-project-diagram mr-1"></i> Projects
                        </a>
                        <a href="hodview_mooc.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'hodview_mooc.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-book-open mr-1"></i> Courses
                        </a>
                        <a href="uploadFile.php"
                            class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?php echo $current_page == 'uploadFile.php' ? 'active-tab' : 'text-gray-600 hover:bg-blue-100 hover:text-blue-700'; ?>">
                            <i class="fas fa-upload mr-1"></i> Upload Files
                        </a>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative ml-4">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none group">
                            <div
                                class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center text-white group-hover:bg-blue-700 transition-colors">
                                <?php
                                $nameParts = explode(' ', $hodName);
                                $initial = count($nameParts) > 1 ? $nameParts[1][0] : $hodName[0];
                                echo strtoupper($initial);
                                ?>
                            </div>
                            <span
                                class="text-sm font-medium text-gray-700 hidden lg:inline"><?php echo explode(' ', $hodName)[1]; ?></span>
                            <i
                                class="fas fa-chevron-down text-xs text-gray-500 group-hover:text-gray-700 transition-colors"></i>
                        </button>

                        <div id="user-dropdown"
                            class="absolute right-0 mt-4 w-56 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                            <div class="py-2 px-4 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    <?php echo htmlspecialchars($hodName); ?>
                                </p>
                                <p class="text-xs text-gray-500 truncate">
                                    <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                                <div class="flex items-center mt-1">
                                    <p class="text-xs text-gray-500 mr-2">
                                        HOD ID: <span class="font-medium"><?php echo htmlspecialchars($empId); ?></span>
                                    </p>
                                </div>
                                <div class="flex items-center mt-1">
                                    <p class="text-xs text-gray-500 mr-1">Dept:</p>
                                    <span class="dept-badge"><?php echo htmlspecialchars($_SESSION['dept']); ?></span>
                                </div>
                            </div>
                            <a href="hodupdatePassword.php"
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
        <div id="mobile-menu" class="md:hidden bg-white border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="hodDashboard.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'hodDashboard.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="hodviewinternship.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'hodviewinternship.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-briefcase mr-2"></i> Internship
                </a>
                <a href="hodviewproject.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'hodviewproject.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-project-diagram mr-2"></i> Projects
                </a>
                <a href="hodview_mooc.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'hodview_mooc.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-book-open mr-2"></i> Courses
                </a>
                <a href="uploadFile.php"
                    class="block px-3 py-2 rounded-md text-base font-medium transition-colors <?php echo $current_page == 'uploadFile.php' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'; ?>">
                    <i class="fas fa-upload mr-2"></i> Upload Files
                </a>
            </div>
            <div class="pt-4 pb-3 border-t border-gray-200">
                <div class="flex items-center px-4">
                    <div class="h-10 w-10 rounded-full bg-blue-600 flex items-center justify-center text-white">
                        <?php echo strtoupper(substr($hodName, 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($hodName); ?></p>
                        <div class="flex items-center">
                            <p class="text-xs text-gray-500 mr-2"><?php echo htmlspecialchars($empId); ?></p>
                            <span class="dept-badge"><?php echo htmlspecialchars($_SESSION['dept']); ?></span>
                        </div>
                    </div>
                </div>
                <div class="mt-3 px-2 space-y-1">
                    <a href="hodupdatePassword.php"
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
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('show');

            // Close user dropdown if open
            document.getElementById('user-dropdown').classList.remove('show');
        });

        // User dropdown toggle
        document.getElementById('user-menu-button').addEventListener('click', function (e) {
            e.stopPropagation();
            document.getElementById('user-dropdown').classList.toggle('show');

            // Close mobile menu if open
            document.getElementById('mobile-menu').classList.remove('show');
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (e) {
            if (!document.getElementById('user-menu-button').contains(e.target) &&
                !document.getElementById('user-dropdown').contains(e.target)) {
                document.getElementById('user-dropdown').classList.remove('show');
            }

            if (!document.getElementById('mobile-menu-button').contains(e.target) &&
                !document.getElementById('mobile-menu').contains(e.target)) {
                document.getElementById('mobile-menu').classList.remove('show');
            }
        });

        function logout() {
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>