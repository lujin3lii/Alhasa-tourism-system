
<?php
$hash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Admin Password</title>
</head>
<body>

    <h2>Generate Admin Password Hash</h2>

    <form method="POST">
        <label>Enter Admin Password:</label>
        <input
            type="password"
            name="password"
            required
            autocomplete="new-password"
        >

        <button type="submit">Generate Hash</button>
    </form>

    <?php if ($hash !== ''): ?>

        <h3>Your Encrypted Password:</h3>

        <textarea rows="4" cols="65" readonly><?= htmlspecialchars($hash) ?></textarea>

        <p>Copy this value and save it in the Admin table.</p>

    <?php endif; ?>

</body>
</html>