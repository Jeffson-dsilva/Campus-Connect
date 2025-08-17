<?php
require_once __DIR__ . '/core/config.php';
$error = '';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST['role'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // If the role is 'student', we also need to validate the USN
    $usn = isset($_POST['usn']) ? $_POST['usn'] : '';

    // Validate role selection
    if ($role === "Select Role") {
        $error = "Please select a role.";
    } else {
        // Validate credentials against the database based on role
        if ($role === 'student') {
            $query = "SELECT * FROM students WHERE usn = ? AND email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $usn, $email);
        } else {
            $table = $role === 'faculty' ? 'faculty' : 'hod';
            $query = "SELECT * FROM $table WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            
            // Check both hashed and plaintext password
            $passwordMatch = false;
            
            // First try password_verify for hashed passwords
            if (isset($row['password']) && password_verify($password, $row['password'])) {
                $passwordMatch = true;
            }
            // If not matched, check plaintext (for backward compatibility)
            elseif (isset($row['password']) && $password === $row['password']) {
                $passwordMatch = true;
            }
            
            if ($passwordMatch) {
                if ($role === 'student') {
                    // Set all required session variables for ClassOps
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'student';
                    $_SESSION['email'] = $email;
                    $_SESSION['usn'] = $row['usn'];
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['employee_id'] = ''; // Empty for students
                    $_SESSION['dept_code'] = $row['dept_code'];

                    header("Location: " . BASE_URL . "app/modules/dashboards/stDashboard.php");
                    exit();
                } elseif ($role === 'faculty') {
                    // Set faculty-specific session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'faculty';
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['employee_id'] = $row['employee_id'];
                    $_SESSION['usn'] = ''; // Empty for faculty
                    $_SESSION['dept_code'] = $row['dept_code'];

                    header("Location: " . BASE_URL . "app/modules/dashboards/ftDashboard.php");
                    exit();
                } elseif ($role === 'hod') {
                    // Set HOD-specific session variables
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'hod'; // Or 'faculty' if HOD should have same access
                    $_SESSION['email'] = $email;
                    $_SESSION['name'] = $row['name'];
                    $_SESSION['employee_id'] = $row['employee_id'];
                    $_SESSION['usn'] = ''; // Empty for HOD
                    $_SESSION['dept_code'] = $row['dept'];

                    header("Location: " . BASE_URL . "app/modules/dashboards/hodDashboard.php");
                    exit();
                }
            } else {
                $error = "Email or Password incorrect.";
            }
        } else {
            $error = "Email or Password incorrect.";
        }
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>St. Joseph Engineering College Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc; /* Fallback color */
        }
        .main-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?= BASE_URL ?>images/college1.jpg') no-repeat center center;
            background-size: cover;
            z-index: -1;
            margin-top: 80px; /* Space below header */
        }
        .overlay {
            position: fixed;
            top: 80px; /* Space below header */
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.3);
            z-index: -1;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .input-transparent {
            background: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .input-transparent:focus {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(4, 82, 165, 0.5);
            box-shadow: 0 0 0 3px rgba(4, 82, 165, 0.1);
        }
        .input-transparent::placeholder {
            color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm z-10">
        <div class="max-w-6xl mx-auto flex items-center justify-center space-x-4 py-4 px-6">
            <img src="<?= BASE_URL ?>images/logo.png" alt="College Logo" class="h-12 w-auto">
            <div class="text-center">
                <h1 class="text-2xl font-bold text-[#0452a5]">Campus Connect</h1>
                <p class="text-sm text-gray-600">St. Joseph Engineering College</p>
            </div>
        </div>
    </header>

    <!-- Background Image (positioned below header) -->
    <div class="main-background"></div>
    <div class="overlay"></div>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-4 mt-4">
        <div class="w-full max-w-md form-container overflow-hidden">
            <div class="p-8">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Welcome</h2>
                    <p class="text-gray-600 mt-1">Please login to your account</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="mb-4 p-3 bg-red-50/80 text-red-600 rounded-lg text-sm border border-red-100">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" onsubmit="return validateForm()" class="space-y-4">
                    <!-- Role Selection -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Select Role</label>
                        <select id="role" name="role" required onchange="toggleUSNField()"
                            class="w-full px-4 py-2.5 rounded-lg input-transparent focus:ring-2 focus:ring-[#0452a5] focus:border-[#0452a5]">
                            <option value="Select Role">Select Role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                            <option value="hod">HOD</option>
                        </select>
                        <span class="mt-1 text-sm text-red-500" id="roleError"></span>
                    </div>

                    <!-- USN Field (Conditional) -->
                    <div id="usnField" class="hidden">
                        <label for="usn" class="block text-sm font-medium text-gray-700 mb-1">USN</label>
                        <input type="text" id="usn" name="usn" placeholder="Enter your USN"
                            class="w-full px-4 py-2.5 rounded-lg input-transparent focus:ring-2 focus:ring-[#0452a5] focus:border-[#0452a5]">
                        <span class="mt-1 text-sm text-red-500" id="usnError"></span>
                    </div>

                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required
                            class="w-full px-4 py-2.5 rounded-lg input-transparent focus:ring-2 focus:ring-[#0452a5] focus:border-[#0452a5]">
                        <span class="mt-1 text-sm text-red-500" id="emailError"></span>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" placeholder="Enter your password"
                                class="w-full px-4 py-2.5 rounded-lg input-transparent focus:ring-2 focus:ring-[#0452a5] focus:border-[#0452a5] pr-10">
                            <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none toggle-password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="mt-1 text-sm text-red-500" id="passwordError"></span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full py-3 px-4 bg-[#0452a5] hover:bg-[#034188] text-white font-medium rounded-lg transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-[#0452a5] focus:ring-offset-2 mt-2">
                        Login
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // [All JavaScript functions remain exactly the same ...]
        function toggleUSNField() {
            const role = document.getElementById('role').value;
            const usnField = document.getElementById('usnField');
            usnField.classList.toggle('hidden', role !== 'student');
        }

        document.querySelector('.toggle-password').addEventListener('click', function (e) {
            e.preventDefault();
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        function validateForm() {
            let valid = true;
            const role = document.getElementById('role').value;
            const roleError = document.getElementById('roleError');
            roleError.textContent = '';

            if (role === 'Select Role') {
                roleError.textContent = 'Please select a role.';
                valid = false;
            }

            const email = document.getElementById('email').value;
            const emailError = document.getElementById('emailError');
            emailError.textContent = '';
            const emailPattern = /^[a-zA-Z0-9._%+-]+@sjec\.ac\.in$/;

            if (!emailPattern.test(email)) {
                emailError.textContent = 'Please enter a valid SJEC email.';
                valid = false;
            }

            const password = document.getElementById('password').value;
            const passwordError = document.getElementById('passwordError');
            const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

            passwordError.textContent = '';

            if (!passwordRegex.test(password)) {
                passwordError.textContent = 'Password must contain at least 8 characters, including uppercase, lowercase, number, and special character.';
                valid = false;
            }

            return valid;
        }
    </script>
</body>
</html>