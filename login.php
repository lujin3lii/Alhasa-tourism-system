<?php
session_start();
require_once 'db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT `Id`, `Name`, `Email`, `Password`
         FROM `tourist`
         WHERE `Email` = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $tourist = $result->fetch_assoc();

    if ($tourist) {

        $storedPassword = $tourist['Password'];

        // Support previously stored plain passwords and hashed passwords
        $validPassword = password_verify($password, $storedPassword);

        if (!$validPassword && hash_equals((string)$storedPassword, $password)) {

            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $update = $conn->prepare(
                "UPDATE `tourist` SET `Password` = ? WHERE `Id` = ?"
            );

            $update->bind_param("si", $newHash, $tourist['Id']);
            $update->execute();
            $update->close();

            $validPassword = true;
        }

        if ($validPassword) {

            session_regenerate_id(true);

            $_SESSION['tourist_id'] = $tourist['Id'];
            $_SESSION['tourist_name'] = $tourist['Name'];
            $_SESSION['tourist_email'] = $tourist['Email'];

            header("Location: index.php");
            exit;

        } else {
            $errorMessage = "Incorrect email or password.";
        }

    } else {
        $errorMessage = "Incorrect email or password.";
    }

    $stmt->close();
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
        <p class="auth-subtitle">Continue your journey in Al-Ahsa</p>

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

    <?php if ($errorMessage): ?>
        <script>
            alert(<?= json_encode($errorMessage) ?>);
        </script>
    <?php endif; ?>

</body>
</html>