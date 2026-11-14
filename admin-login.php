
<?php
session_start();
require_once 'db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {

        $errorMessage = 'Please enter your email and password.';

    } else {

        $stmt = $conn->prepare(
            "SELECT Admin_ID, Name, Email, Password
             FROM Admin
             WHERE Email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $errorMessage = 'Something went wrong. Please try again.';
        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {

                $admin = $result->fetch_assoc();

                if (password_verify($password, $admin['Password'])) {

                    // Save Admin Session
                    session_regenerate_id(true);

                    $_SESSION['admin_id'] = $admin['Admin_ID'];
                    $_SESSION['admin_name'] = $admin['Name'];
                    $_SESSION['admin_email'] = $admin['Email'];

                    // Go to Admin Dashboard
                    header("Location: admin-dashboard.php");
                    exit;

                } else {
                    $errorMessage = 'Incorrect email or password.';
                }

            } else {
                $errorMessage = 'Incorrect email or password.';
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        .admin-login-page {
            min-height: 100vh;
            padding: 42px 18px 60px;
            color: #263b32;
            background: linear-gradient(135deg, #ffffff, #f5f8f4, #eaf2eb);
            font-family: inherit;
        }

        .admin-login-page * {
            box-sizing: border-box;
        }

        .admin-login-wrap {
            width: 100%;
            max-width: 760px;
            margin: 0 auto;
        }

        .brand-heading {
            text-align: center;
            margin-bottom: 26px;
        }

        .project-logo {
            display: block;
            width: 125px;
            height: 125px;
            object-fit: contain;
            margin: 0 auto 14px;
        }

        .brand-heading h1 {
            margin: 0 0 8px;
            color: #075b45;
            font-size: 30px;
        }

        .brand-heading p {
            margin: 0;
            color: #68796e;
            font-size: 15px;
            line-height: 1.7;
        }

        .back-role-container {
            margin-bottom: 16px;
            text-align: left;
        }

        .back-role-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #075b45;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .back-role-link:hover {
            color: #08775a;
            text-decoration: underline;
        }

        .admin-login-card {
            padding: 32px;
            background: #ffffff;
            border: 1px solid #dfe8e1;
            border-radius: 20px;
            box-shadow: 0 16px 42px rgba(32, 72, 51, 0.09);
        }

        .form-intro {
            margin-bottom: 25px;
        }

        .form-intro h2 {
            margin: 0 0 7px;
            color: #263b32;
            font-size: 22px;
        }

        .form-intro p {
            margin: 0;
            color: #68796e;
            font-size: 14px;
            line-height: 1.7;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 19px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .field label {
            color: #263b32;
            font-size: 14px;
            font-weight: 600;
        }

        .required {
            color: #b54747;
        }

        .field input {
            width: 100%;
            min-height: 47px;
            padding: 12px 14px;
            border: 1px solid #d7e1d9;
            border-radius: 10px;
            background: #ffffff;
            color: #263b32;
            font: inherit;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        .field input:focus {
            border-color: #08775a;
            box-shadow: 0 0 0 3px rgba(8, 119, 90, 0.12);
        }

        .field input::placeholder {
            color: #9aa79d;
        }

        .form-actions {
            margin-top: 26px;
        }

        .submit-button {
            width: 100%;
            min-height: 49px;
            padding: 13px 18px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #075b45, #08775a);
            color: #ffffff;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .submit-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(7, 91, 69, 0.2);
        }

        .login-note {
            margin-top: 18px;
            text-align: center;
            color: #68796e;
            font-size: 13px;
        }

        .registration-error {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 9px;
            background: #fff0f0;
            border: 1px solid #f0cccc;
            color: #a83232;
            font-size: 14px;
        }

        @media (max-width: 600px) {

            .admin-login-page {
                padding: 28px 14px 42px;
            }

            .admin-login-card {
                padding: 22px 18px;
            }

            .project-logo {
                width: 100px;
                height: 100px;
            }
        }

    </style>

</head>

<body>

<main class="admin-login-page">

    <!-- Back to Select Role -->
    <div class="back-role-container">
        <a href="role-selection.html" class="back-role-link">
            &larr; Back to Select Role
        </a>
    </div>

    <div class="admin-login-wrap">

        <!-- Project Logo and Name -->
        <header class="brand-heading">

            <img
                src="images/logo.png"
                alt="Al-Ahsa Vision Hub Logo"
                class="project-logo"
            >

            <h1>Al-Ahsa Vision Hub</h1>

            <p>
                Welcome back! Access the administration
                dashboard and manage the tourism system.
            </p>

        </header>

        <!-- Admin Login Form -->
        <section class="admin-login-card">

            <div class="form-intro">

                <h2>Admin Login</h2>

                <p>
                    Enter your email and password to access the admin dashboard.
                </p>

            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="registration-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form
                id="adminLoginForm"
                method="POST"
                action="admin-login.php"
            >

                <div class="form-grid">

                    <div class="field">

                        <label for="email">
                            Email Address <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="name@example.com"
                            autocomplete="email"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            required
                        >

                    </div>

                    <div class="field">

                        <label for="password">
                            Password <span class="required">*</span>
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                    </div>

                </div>

                <div class="form-actions">

                    <button class="submit-button" type="submit">
                        Log In
                    </button>

                </div>

            </form>

        </section>

    </div>

</main>

</body>
</html>
