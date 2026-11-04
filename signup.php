<?php
session_start();
require_once 'db.php';

// Select the project database

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {

        $errorMessage = "Passwords do not match.";

    } else {

        // Check if the email already exists
        $check = $conn->prepare(
            "SELECT `Id` FROM `tourist` WHERE `Email` = ? LIMIT 1"
        );

        if (!$check) {

            $errorMessage = "Database error: " . $conn->error;

        } else {

            $check->bind_param("s", $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {

                $errorMessage = "This email is already registered.";

            } else {

                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert the new tourist
                $stmt = $conn->prepare(
                    "INSERT INTO `tourist`
                    (`Name`, `Email`, `Password`, `Create_at`)
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)"
                );

                if (!$stmt) {

                    $errorMessage = "Database error: " . $conn->error;

                } else {

                    $stmt->bind_param("sss", $name, $email, $hashedPassword);

                    if ($stmt->execute()) {

                        // Start the tourist session
                        session_regenerate_id(true);

                        $_SESSION['tourist_id'] = $stmt->insert_id;
                        $_SESSION['tourist_name'] = $name;
                        $_SESSION['tourist_email'] = $email;

                        // Redirect to homepage
                        header("Location: index.php");
                        exit;

                    } else {

                        $errorMessage = "Unable to create your account: " . $stmt->error;

                    }

                    $stmt->close();
                }
            }

            $check->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Al-Ahsa Vision Hub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">

    <main class="auth-card">

        <img src="images/logo.png"
             alt="Project Logo"
             class="small-logo">

        <h1>Create Account</h1>
        <p class="auth-subtitle">Start exploring Al-Ahsa</p>

        <form action="signup.php" method="post" id="signup-form">

            <label for="fullname">Full Name</label>
            <input type="text"
                   id="fullname"
                   name="fullname"
                   placeholder="Enter your full name"
                   required>

            <label for="email">Email</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="Enter your email"
                   required>

            <label for="password">Password</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="Create a password"
                   required>

            <label for="confirm-password">Confirm Password</label>
            <input type="password"
                   id="confirm-password"
                   name="confirm_password"
                   placeholder="Confirm your password"
                   required>

            <p id="password-message"
               style="display:none; color:#c0392b; font-size:13px; margin-top:5px;">
                Passwords do not match.
            </p>

            <button type="submit" class="auth-button" id="create-account-button">
                Create Account
            </button>

        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

        <a href="index.php" class="back-link">
            Back to Home Page
        </a>

    </main>

    <button class="chat-button"
            onclick="openAI()"
            aria-label="AI Assistant">
    </button>

    <script src="script.js"></script>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const passwordMessage = document.getElementById('password-message');
        const signupForm = document.getElementById('signup-form');
        const createButton = document.getElementById('create-account-button');

        function checkPasswords() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword === '') {
                passwordMessage.style.display = 'none';
                createButton.disabled = false;
                return true;
            }

            if (password !== confirmPassword) {
                passwordMessage.style.display = 'block';
                createButton.disabled = true;
                return false;
            }

            passwordMessage.style.display = 'none';
            createButton.disabled = false;
            return true;
        }

        passwordInput.addEventListener('input', checkPasswords);
        confirmPasswordInput.addEventListener('input', checkPasswords);

        signupForm.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                event.preventDefault();
                passwordMessage.style.display = 'block';
                createButton.disabled = true;
            }
        });
    </script>

    <?php if ($errorMessage): ?>
        <script>
            alert(<?= json_encode($errorMessage) ?>);
        </script>
    <?php endif; ?>

</body>
</html> 