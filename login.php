<?php
session_start();
require_once 'db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $errorMessage = "Please enter your email and password.";

    } else {

        // Find tourist account
        $stmt = $conn->prepare(
            "SELECT `Tourist_ID`, `Name`, `Email`, `Password`
             FROM `tourist`
             WHERE `Email` = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $errorMessage = "Database error: " . $conn->error;

        } else {

            $stmt->bind_param("s", $email);

            if (!$stmt->execute()) {

                $errorMessage = "Database error: " . $stmt->error;

            } else {

                $result = $stmt->get_result();
                $tourist = $result->fetch_assoc();

                if ($tourist) {

                    $storedPassword = $tourist['Password'];

                    // Verify encrypted password
                    $validPassword = password_verify(
                        $password,
                        $storedPassword
                    );

                    // Support old plain-text passwords
                    if (
                        !$validPassword &&
                        hash_equals((string)$storedPassword, $password)
                    ) {

                        $newHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $update = $conn->prepare(
                            "UPDATE `tourist`
                             SET `Password` = ?
                             WHERE `Tourist_ID` = ?"
                        );

                        if ($update) {

                            $update->bind_param(
                                "si",
                                $newHash,
                                $tourist['Tourist_ID']
                            );

                            if ($update->execute()) {
                                $validPassword = true;
                            }

                            $update->close();
                        }
                    }

                    if ($validPassword) {

                        // Start a new session after successful login
                        session_regenerate_id(true);

                        $_SESSION['tourist_id'] = $tourist['Tourist_ID'];
                        $_SESSION['tourist_name'] = $tourist['Name'];
                        $_SESSION['tourist_email'] = $tourist['Email'];

                        $stmt->close();

                        // Redirect to homepage
                        header("Location: index.php");
                        exit();

                    } else {

                        $errorMessage = "Incorrect email or password.";
                    }

                } else {

                    $errorMessage = "Incorrect email or password.";
                }
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="auth-page">

    <main class="auth-card">

        <img src="images/logo.png"
             alt="Project Logo"
             class="small-logo">

        <h1>Welcome Back</h1>

        <p class="auth-subtitle">
            Continue your journey in Al-Ahsa
        </p>

        <form action="login.php" method="post">

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
                   placeholder="Enter your password"
                   required>

            <button type="submit" class="auth-button">
                Login
            </button>

        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="signup.php">Create Account</a>
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

    <?php if ($errorMessage !== ''): ?>
        <script>
            alert(<?= json_encode($errorMessage) ?>);
        </script>
    <?php endif; ?>

</body>
</html>