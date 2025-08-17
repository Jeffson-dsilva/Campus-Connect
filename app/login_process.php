<?php
session_start();
require 'db.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usn = $_POST['usn'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // Get the selected role

    // Prepare the SQL statement based on the role
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE usn = ? AND email = ? AND role = ?");
    $stmt->bind_param("sss", $usn, $email, $role); // Bind parameters

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Successful login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['usn'] = $user['usn'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($role === 'student') {
                header("Location: student_dashboard.php");
            } elseif ($role === 'faculty') {
                header("Location: faculty_dashboard.php");
            } elseif ($role === 'admin') {
                header("Location: admin_dashboard.php");
            }
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found or role mismatch.";
    }
    
    // Close statement and connection
    $stmt->close();
}
?>
