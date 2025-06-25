<?php
require_once 'config.php';
$title = "Update Password";
require_once 'header.php';
?>

<?php
// Check if the user is logged in (i.e., email is stored in the session)
if (!isset($_SESSION['email'])) {
    header("Location: login.php");  // Redirect to login if not logged in
    exit();
}

// Initialize variables for error messages and success flag
$emailError = $newPasswordError = $confirmPasswordError = "";
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $newPassword = trim($_POST['new-password']);
    $confirmPassword = trim($_POST['confirm-password']);

    // Validate email
    $stmt = $conn->prepare("SELECT email FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $emailError = "Incorrect email. Please enter a valid email.";
    }

    // Validate new password
    $passwordRegex = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    if (!preg_match($passwordRegex, $newPassword)) {
        $newPasswordError = "Password must be at least 8 characters, including uppercase, lowercase, number, and special character.";
    }

    // Validate confirm password
    if ($newPassword !== $confirmPassword) {
        $confirmPasswordError = "Passwords do not match.";
    }

    // If no errors, update password
    if (empty($emailError) && empty($newPasswordError) && empty($confirmPasswordError)) {
        $hashedPassword = $newPassword; 
        $updateStmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $email);

        if ($updateStmt->execute()) {
            $success = true;
        } else {
            $emailError = "Error updating password. Please try again later.";
        }

        $updateStmt->close();
    }

    $stmt->close();
}
?>

<main class="max-h-screen bg-gradient-to-br from-blue-100 to-indigo-400 py-7 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
        <div class="p-8">
            <div class="flex flex-col items-center justify-center mb-8">
                <div class="bg-blue-100 p-4 rounded-full mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Update Password</h2>
            </div>

            <form id="updatePasswordForm" method="POST" class="space-y-6">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required 
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <?php if (!empty($emailError)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $emailError; ?></p>
                    <?php endif; ?>
                </div>

                <!-- New Password Field -->
                <div>
                    <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <input type="password" id="new-password" name="new-password" placeholder="Enter new password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all pr-10">
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($newPasswordError)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $newPasswordError; ?></p>
                    <?php endif; ?>
                    <p class="mt-2 text-xs text-gray-500">
                        Password must contain at least 8 characters, including uppercase, lowercase, number and special character.
                    </p>
                </div>

                <!-- Confirm Password Field -->
                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm new password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all pr-10">
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <?php if (!empty($confirmPasswordError)): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $confirmPasswordError; ?></p>
                    <?php endif; ?>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<!-- Success Modal -->
<?php if ($success): ?>
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-sm w-full mx-4 text-center">
            <div class="bg-green-100 p-4 rounded-full inline-flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-2">Password Updated Successfully!</h2>
            <p class="text-gray-600 mb-6">Your password has been changed successfully.</p>
            <button onclick="closeModal()" class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                Close
            </button>
        </div>
    </div>
<?php endif; ?>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(function(button) {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.classList.toggle('fa-eye-slash');
        });
    });

    // Close the modal
    function closeModal() {
        document.getElementById('successModal').style.display = 'none';
        window.location.href = 'stDashboard.php'; // Redirect to dashboard after success
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('successModal');
        if (event.target === modal) {
            closeModal();
        }
    });
</script>

</body>
</html>