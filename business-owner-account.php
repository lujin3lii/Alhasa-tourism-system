<?php
session_start();

/* Handle Business Owner Logout */
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    unset(
        $_SESSION['business_owner_id'],
        $_SESSION['business_owner_name'],
        $_SESSION['business_owner_email']
    );

    session_write_close();
    header("Location: index.php");
    exit;
}

/* Enable MySQLi error reporting */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/db.php';

/* Check Business Owner Login */
if (empty($_SESSION['business_owner_id'])) {
    header("Location: business-owner-login.php");
    exit;
}

$ownerId = (int) $_SESSION['business_owner_id'];

$successMessage = '';
$errorMessage = '';

$ownerName = $_SESSION['business_owner_name'] ?? 'My Account';
$ownerEmail = $_SESSION['business_owner_email'] ?? '';
$ownerPhone = '';

/* Safely prepare SQL statements */
if (!function_exists('accountPrepare')) {
    function accountPrepare($conn, $sql) {
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new RuntimeException(
                'SQL prepare failed: ' . $conn->error
            );
        }

        return $stmt;
    }
}

/* Escape output */
if (!function_exists('accountEscape')) {
    function accountEscape($value) {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/* CSRF Protection */
if (empty($_SESSION['account_csrf_token'])) {
    $_SESSION['account_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['account_csrf_token'];

/* Handle Form Submissions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($csrfToken, $submittedToken)) {

        $errorMessage = 'Invalid request. Please refresh the page and try again.';

    } else {

        $formType = $_POST['form_type'] ?? '';

        /* =====================================
           UPDATE PROFILE
        ===================================== */

        if ($formType === 'profile') {

            $name = trim($_POST['owner_name'] ?? '');
            $email = trim($_POST['owner_email'] ?? '');
            $phone = trim($_POST['owner_phone'] ?? '');

            if ($name === '' || $email === '') {

                $errorMessage = 'Please enter your name and email address.';

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                $errorMessage = 'Please enter a valid email address.';

            } else {

                try {

                    /* Check whether another owner uses this email */
                    $checkEmail = accountPrepare(
                        $conn,
                        "SELECT Business_Owner_ID
                         FROM business_owners
                         WHERE Email = ?
                         AND Business_Owner_ID <> ?
                         LIMIT 1"
                    );

                    $checkEmail->bind_param(
                        "si",
                        $email,
                        $ownerId
                    );

                    $checkEmail->execute();
                    $checkEmail->store_result();

                    if ($checkEmail->num_rows > 0) {

                        $errorMessage = 'This email address is already registered.';

                    } else {

                        $updateProfile = accountPrepare(
                            $conn,
                            "UPDATE business_owners
                             SET Name = ?, Email = ?, Phone = ?
                             WHERE Business_Owner_ID = ?"
                        );

                        $updateProfile->bind_param(
                            "sssi",
                            $name,
                            $email,
                            $phone,
                            $ownerId
                        );

                        $updateProfile->execute();

                        $_SESSION['business_owner_name'] = $name;
                        $_SESSION['business_owner_email'] = $email;

                        $ownerName = $name;
                        $ownerEmail = $email;
                        $ownerPhone = $phone;

                        $successMessage = 'Your profile has been updated successfully.';

                        $updateProfile->close();
                    }

                    $checkEmail->close();

                } catch (Throwable $e) {

                    error_log(
                        'Business owner profile update error: ' .
                        $e->getMessage()
                    );

                    $errorMessage = 'Unable to update your profile. Please check the database configuration and column names.';
                }
            }
        }

        /* =====================================
           CHANGE PASSWORD
        ===================================== */

        elseif ($formType === 'password') {

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (
                $currentPassword === '' ||
                $newPassword === '' ||
                $confirmPassword === ''
            ) {

                $errorMessage = 'Please fill in all password fields.';

            } elseif (strlen($newPassword) < 8) {

                $errorMessage = 'The new password must be at least 8 characters long.';

            } elseif ($newPassword !== $confirmPassword) {

                $errorMessage = 'The new password and confirmation do not match.';

            } elseif ($currentPassword === $newPassword) {

                $errorMessage = 'Your new password must be different from your current password.';

            } else {

                try {

                    /* Get current password */
                    $getPassword = accountPrepare(
                        $conn,
                        "SELECT Password
                         FROM business_owners
                         WHERE Business_Owner_ID = ?
                         LIMIT 1"
                    );

                    $getPassword->bind_param("i", $ownerId);
                    $getPassword->execute();

                    $getPassword->bind_result($storedPassword);

                    if (!$getPassword->fetch()) {

                        $errorMessage = 'Business owner account was not found.';

                        $getPassword->close();

                    } else {

                        $getPassword->close();

                        /* Support hashed and existing plain-text passwords */
                        $passwordInfo = password_get_info(
                            (string) $storedPassword
                        );

                        if (!empty($passwordInfo['algo'])) {

                            $passwordIsCorrect = password_verify(
                                $currentPassword,
                                $storedPassword
                            );

                        } else {

                            $passwordIsCorrect = hash_equals(
                                (string) $storedPassword,
                                $currentPassword
                            );
                        }

                        if (!$passwordIsCorrect) {

                            $errorMessage = 'Your current password is incorrect.';

                        } else {

                            /* Hash the new password securely */
                            $hashedPassword = password_hash(
                                $newPassword,
                                PASSWORD_DEFAULT
                            );

                            $updatePassword = accountPrepare(
                                $conn,
                                "UPDATE business_owners
                                 SET Password = ?
                                 WHERE Business_Owner_ID = ?"
                            );

                            $updatePassword->bind_param(
                                "si",
                                $hashedPassword,
                                $ownerId
                            );

                            $updatePassword->execute();

                            $successMessage = 'Your password has been changed successfully.';

                            $updatePassword->close();
                        }
                    }

                } catch (Throwable $e) {

                    error_log(
                        'Business owner password update error: ' .
                        $e->getMessage()
                    );

                    $errorMessage = 'Unable to change your password. Please check the database configuration.';
                }
            }
        }
    }
}

/* =====================================
   FETCH LATEST ACCOUNT INFORMATION
===================================== */

try {

    $getOwner = accountPrepare(
        $conn,
        "SELECT Name, Email, Phone
         FROM business_owners
         WHERE Business_Owner_ID = ?
         LIMIT 1"
    );

    $getOwner->bind_param("i", $ownerId);
    $getOwner->execute();

    $getOwner->bind_result(
        $databaseName,
        $databaseEmail,
        $databasePhone
    );

    if ($getOwner->fetch()) {

        $ownerName = $databaseName ?? 'My Account';
        $ownerEmail = $databaseEmail ?? '';
        $ownerPhone = $databasePhone ?? '';

        $_SESSION['business_owner_name'] = $ownerName;
        $_SESSION['business_owner_email'] = $ownerEmail;

    } else {

        $getOwner->close();

        session_unset();
        session_destroy();

        header("Location: business-owner-login.php");
        exit;
    }

    $getOwner->close();

} catch (Throwable $e) {

    error_log(
        'Business owner account fetch error: ' .
        $e->getMessage()
    );

    if ($errorMessage === '') {
        $errorMessage = 'Some account information could not be loaded. Please check the database configuration.';
    }

    $ownerName = $_SESSION['business_owner_name'] ?? 'My Account';
    $ownerEmail = $_SESSION['business_owner_email'] ?? '';
    $ownerPhone = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Account | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .business-page {
            min-height: 100vh;
            padding: 120px 24px 50px;
            color: #263b32;
            background: linear-gradient(135deg, #ffffff, #f5f8f4, #eaf2eb);
            font-family: inherit;
        }

        .business-page * {
            box-sizing: border-box;
        }

        .business-container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .business-heading {
            margin-bottom: 28px;
        }

        .business-label {
            display: inline-block;
            margin-bottom: 12px;
            padding: 7px 13px;
            border-radius: 20px;
            background: #e3f0e6;
            color: #075b45;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .business-heading h1 {
            margin: 0 0 10px;
            color: #075b45;
            font-size: 30px;
            line-height: 1.4;
        }

        .business-heading p {
            margin: 0;
            color: #68796e;
            font-size: 15px;
            line-height: 1.8;
        }

        .business-back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #075b45;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .business-back-link:hover {
            color: #08775a;
            text-decoration: underline;
        }

        .business-card {
            overflow: hidden;
            margin-bottom: 25px;
            border: 1px solid #dfe8e1;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 16px 42px rgba(32,72,51,.08);
        }

        .business-card-header {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 28px;
            background: linear-gradient(135deg,#eaf3ec,#f7faf7);
            border-bottom: 1px solid #dfe8e1;
        }

        .business-icon {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 18px;
            background: #fff;
            color: #075b45;
            font-size: 30px;
        }

        .business-title h2 {
            margin: 0 0 7px;
            color: #075b45;
            font-size: 23px;
        }

        .business-title p {
            margin: 0;
            color: #68796e;
            font-size: 14px;
            line-height: 1.7;
        }

        .business-card-body {
            padding: 28px;
        }

        .details-heading {
            margin: 0 0 20px;
            color: #263b32;
            font-size: 19px;
        }

        .business-form-grid {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            gap: 18px;
        }

        .business-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }

        .business-field.full-width {
            grid-column: 1 / -1;
        }

        .business-field label {
            color: #68796e;
            font-size: 13px;
            font-weight: 600;
        }

        .business-field input,
        .business-field select,
        .business-field textarea {
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid #dfe8e1;
            border-radius: 10px;
            background: #fbfdfb;
            color: #263b32;
            font: inherit;
            font-size: 14px;
        }

        .business-field textarea {
            min-height: 120px;
            resize: vertical;
        }

        .business-field input:focus,
        .business-field select:focus,
        .business-field textarea:focus {
            outline: 2px solid #b9d9c2;
            border-color: #08775a;
        }

        .business-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
        }

        .business-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 47px;
            padding: 12px 20px;
            border: 1px solid #075b45;
            border-radius: 10px;
            background: linear-gradient(135deg,#075b45,#08775a);
            color: #fff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: .2s;
            cursor: pointer;
        }

        .business-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(7,91,69,.17);
        }

        .business-button.secondary {
            border-color: #d7e1d9;
            background: #fff;
            color: #075b45;
        }

        .business-button.secondary:hover {
            background: #f1f7f2;
        }

        .business-note {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            margin-top: 25px;
            padding: 20px;
            border: 1px solid #dfe8e1;
            border-radius: 14px;
            background: rgba(255,255,255,.85);
        }

        .business-note-icon {
            color: #08775a;
            font-size: 21px;
        }

        .business-note h3 {
            margin: 0 0 7px;
            color: #075b45;
            font-size: 15px;
        }

        .business-note p {
            margin: 0;
            color: #68796e;
            font-size: 13px;
            line-height: 1.8;
        }

        .photo-upload-box {
            padding: 25px;
            border: 1px dashed #b9cdbd;
            border-radius: 14px;
            background: #fbfdfb;
        }

        .photo-upload-box p {
            color: #68796e;
            font-size: 14px;
            line-height: 1.8;
        }

        .photo-preview-grid {
            display: grid;
            grid-template-columns: repeat(3,minmax(0,1fr));
            gap: 15px;
        }

        .photo-preview {
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 1px solid #e1e9e2;
            border-radius: 13px;
            background: #fbfdfb;
            color: #68796e;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .photo-preview img {
            display: block;
            max-width: 100%;
            max-height: 200px;
            margin: 0 auto 8px;
            border-radius: 9px;
        }

        .account-message {
            margin-bottom: 22px;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 14px;
            line-height: 1.7;
        }

        .account-message.success {
            color: #075b45;
            background: #eaf5ed;
            border: 1px solid #b9d9c2;
        }

        .account-message.error {
            color: #9b2525;
            background: #fff0f0;
            border: 1px solid #edc4c4;
        }

        @media (max-width:600px) {
            .business-page {
                padding: 120px 15px 35px;
            }

            .business-heading h1 {
                font-size: 26px;
            }

            .business-card-header {
                padding: 22px 18px;
            }

            .business-icon {
                width: 56px;
                height: 56px;
                font-size: 25px;
            }

            .business-title h2 {
                font-size: 19px;
            }

            .business-card-body {
                padding: 20px 16px;
            }

            .business-form-grid,
            .photo-preview-grid {
                grid-template-columns: 1fr;
                gap: 13px;
            }

            .business-field.full-width {
                grid-column: auto;
            }

            .business-actions {
                flex-direction: column;
            }

            .business-button {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Menu Button -->
    <button class="menu-button"
            onclick="toggleMenu()"
            aria-label="Open menu">
        ☰
    </button>

    <!-- Sidebar Background -->
    <div class="sidebar-backdrop" onclick="toggleMenu()"></div>

    <!-- Business Owner Sidebar -->
    <nav class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <a href="business-owner-index.php">
                <img src="images/logo.png"
                     alt="Al-Ahsa Vision Hub"
                     class="sidebar-logo">
            </a>

            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Business Owner Portal</p>
        </div>

        <div class="sidebar-links">
            <a href="business-owner-index.php"><span>⌂</span> Home</a>
            <a href="business-owner-dashboard.php"><span>⌂</span> Dashboard</a>
            <a href="business-owner-my-business.php"><span>▣</span> My Business</a>
            <a href="business-owner-edit.php"><span>✎</span> Edit Business Information</a>
            <a href="business-owner-photos.php"><span>▧</span> Manage Photos</a>
            <a href="business-owner-account.php"><span>♙</span> My Account</a>
            <a href="index.php"><span>⌂</span> Back to Home</a>
        </div>

        <div class="sidebar-decoration">
            <span>🌴</span>
            <p>THE HEART OF AL-AHSA</p>
        </div>

    </nav>

    <!-- Top Bar -->
    <header class="home-topbar">

        <div class="home-location">
            <span class="location-dot"></span>
            DISCOVER AL-AHSA, SAUDI ARABIA
        </div>

        <div class="home-account-links">

            <a href="business-owner-account.php"
               class="home-signup"
               id="account-button">
                <?php echo accountEscape($ownerName); ?>
            </a>

            <a href="business-owner-index.php" class="home-small-logo">
                <img src="images/logo.png" alt="Logo">
            </a>

        </div>

    </header>

    <!-- Main Content -->
    <main class="business-page">

        <div class="business-container">

            <header class="business-heading">
                <span class="business-label">BUSINESS OWNER PORTAL</span>

                <h1>My Account</h1>

                <p>
                    Review your account details and manage your personal information.
                </p>
            </header>

            <?php if ($successMessage !== ''): ?>
                <div class="account-message success">
                    <?php echo accountEscape($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="account-message error">
                    <?php echo accountEscape($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Account Information -->
            <section class="business-card">

                <div class="business-card-header">

                    <div class="business-icon" aria-hidden="true">♙</div>

                    <div class="business-title">
                        <h2>Account Information</h2>
                        <p>Keep your contact details up to date.</p>
                    </div>

                </div>

                <div class="business-card-body">

                    <h3 class="details-heading">Personal Details</h3>

                    <form id="account-form"
                          method="POST"
                          action="business-owner-account.php">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo accountEscape($csrfToken); ?>">

                        <input type="hidden"
                               name="form_type"
                               value="profile">

                        <div class="business-form-grid">

                            <div class="business-field">
                                <label for="owner-name">Full Name</label>

                                <input type="text"
                                       id="owner-name"
                                       name="owner_name"
                                       value="<?php echo accountEscape($ownerName === 'My Account' ? '' : $ownerName); ?>"
                                       placeholder="Enter your full name"
                                       required>
                            </div>

                            <div class="business-field">
                                <label for="owner-email">Email Address</label>

                                <input type="email"
                                       id="owner-email"
                                       name="owner_email"
                                       value="<?php echo accountEscape($ownerEmail); ?>"
                                       placeholder="Enter your email address"
                                       required>
                            </div>

                            <div class="business-field">
                                <label for="owner-phone">Phone Number</label>

                                <input type="tel"
                                       id="owner-phone"
                                       name="owner_phone"
                                       value="<?php echo accountEscape($ownerPhone); ?>"
                                       placeholder="Enter your phone number">
                            </div>

                        </div>

                        <div class="business-actions">
                            <button type="submit" class="business-button">
                                ✓ Save Profile
                            </button>
                        </div>

                    </form>

                </div>

            </section>

            <!-- Change Password -->
            <section class="business-card">

                <div class="business-card-header">

                    <div class="business-icon" aria-hidden="true">⌑</div>

                    <div class="business-title">
                        <h2>Change Password</h2>
                        <p>Keep your account secure by updating your password.</p>
                    </div>

                </div>

                <div class="business-card-body">

                    <form id="password-form"
                          method="POST"
                          action="business-owner-account.php">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo accountEscape($csrfToken); ?>">

                        <input type="hidden"
                               name="form_type"
                               value="password">

                        <div class="business-form-grid">

                            <div class="business-field full-width">
                                <label for="current-password">Current Password</label>

                                <input type="password"
                                       id="current-password"
                                       name="current_password"
                                       autocomplete="current-password"
                                       placeholder="Enter current password"
                                       required>
                            </div>

                            <div class="business-field">
                                <label for="new-password">New Password</label>

                                <input type="password"
                                       id="new-password"
                                       name="new_password"
                                       autocomplete="new-password"
                                       minlength="8"
                                       placeholder="Enter new password"
                                       required>
                            </div>

                            <div class="business-field">
                                <label for="confirm-password">Confirm New Password</label>

                                <input type="password"
                                       id="confirm-password"
                                       name="confirm_password"
                                       autocomplete="new-password"
                                       minlength="8"
                                       placeholder="Confirm new password"
                                       required>
                            </div>

                        </div>

                        <div class="business-actions">
                            <button type="submit" class="business-button">
                                Update Password
                            </button>
                        </div>

                    </form>

                </div>

            </section>

            <!-- Protect Your Account -->
            <section class="business-note">

                <div class="business-note-icon" aria-hidden="true">ⓘ</div>

                <div>
                    <h3>Protect Your Account</h3>

                    <p>
                        Never share your password with anyone.
                        Your account information is saved securely when you submit the forms.
                    </p>
                </div>

            </section>

            <!-- Log Out -->
            <section class="business-card" style="margin-top: 25px;">

                <div class="business-card-body">

                    <h3 class="details-heading">Log Out</h3>

                    <p style="color: #68796e; font-size: 14px; line-height: 1.8;">
                        Sign out of your business owner account and return to the main website.
                    </p>

                    <div class="business-actions">

                        <a href="business-owner-account.php?logout=1"
                           class="business-button">
                            ↪ Log Out
                        </a>

                    </div>

                </div>

            </section>

        </div>

    </main>

    <!-- Al-Ahsa Vision Hub Footer -->
    <footer class="site-footer">

        <div class="site-footer-main">

            <div class="site-footer-brand">
                <h3>Al-Ahsa Vision Hub</h3>
                <p>Discover the beauty, heritage, and experiences of Al-Ahsa.</p>
            </div>

            <div class="site-footer-links">
                <h4>Explore Al-Ahsa</h4>
                <p>Discover places, restaurants, cafés, and seasonal events.</p>
            </div>

            <div class="site-footer-contact">
                <h4>Our Vision</h4>
                <p>Making your journey through Al-Ahsa memorable.</p>
            </div>

            <div class="site-footer-contact">
                <h4>Contact Us</h4>
                <p>05123546798</p>

                <p>
                    <a href="https://www.instagram.com/"
                       target="_blank"
                       rel="noopener noreferrer">
                        ◎ Instagram
                    </a>
                </p>

                <p>
                    <a href="https://x.com/"
                       target="_blank"
                       rel="noopener noreferrer">
                        𝕏 X (Twitter)
                    </a>
                </p>
            </div>

        </div>

        <div class="site-footer-bottom">
            <p>©️ 2026 Al-Ahsa Vision Hub. All rights reserved.</p>
            <p>Explore Al-Ahsa, experience its beauty.</p>
        </div>

    </footer>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

    <!-- Main JavaScript -->
<script src="script.js?v=2"></script>
</body>
</html>