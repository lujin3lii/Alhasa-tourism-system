<?php
session_start();
require_once 'db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $ownerName = trim($_POST['owner_name'] ?? '');
    $businessName = trim($_POST['business_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $businessType = trim($_POST['business_type'] ?? '');
    $businessSize = trim($_POST['business_size'] ?? '');
    $businessLocation = trim($_POST['business_location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        $ownerName === '' || $businessName === '' ||
        $email === '' || $phone === '' ||
        $businessType === '' || $businessSize === '' ||
        $businessLocation === '' || $password === ''
    ) {
        $errorMessage = 'Please fill in all required fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';

    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters.';

    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';

    } else {

        // Check existing email
        $checkStmt = $conn->prepare(
            "SELECT Business_Owner_ID
             FROM business_owners
             WHERE Email = ?
             LIMIT 1"
        );

        if (!$checkStmt) {
            $errorMessage = 'Database error: ' . $conn->error;
        } else {

            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $errorMessage = 'This email is already registered.';
            }

            $checkStmt->close();
        }

        if ($errorMessage === '') {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            try {

                $conn->begin_transaction();

                // Save business owner information
                $ownerStmt = $conn->prepare(
                    "INSERT INTO business_owners
                    (Name, Email, Password, Phone)
                    VALUES (?, ?, ?, ?)"
                );

                if (!$ownerStmt) {
                    throw new Exception($conn->error);
                }

                $ownerStmt->bind_param(
                    "ssss",
                    $ownerName,
                    $email,
                    $hashedPassword,
                    $phone
                );

                if (!$ownerStmt->execute()) {
                    throw new Exception($ownerStmt->error);
                }

                $businessOwnerID = $conn->insert_id;
                $ownerStmt->close();

                // Save business information
                $placeStmt = $conn->prepare(
                    "INSERT INTO places
                    (Name, Place_Type, Description, Location,
                     Business_Owner_ID, Business_Size)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                if (!$placeStmt) {
                    throw new Exception($conn->error);
                }

                $placeStmt->bind_param(
                    "ssssis",
                    $businessName,
                    $businessType,
                    $description,
                    $businessLocation,
                    $businessOwnerID,
                    $businessSize
                );

                if (!$placeStmt->execute()) {
                    throw new Exception($placeStmt->error);
                }

                $placeStmt->close();

                // Save session
                $_SESSION['business_owner_id'] = $businessOwnerID;
                $_SESSION['business_owner_name'] = $ownerName;
                $_SESSION['business_owner_email'] = $email;
                $_SESSION['business_owner_phone'] = $phone;

                $conn->commit();

                // Redirect to Business Owner Home
                header("Location: business-owner-index.php");
                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                error_log(
                    "Business Registration Error: " .
                    $e->getMessage()
                );

                $errorMessage = 'Registration failed: ' .
                    $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Registration | Al-Ahsa Vision Hub</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
        .business-registration-page {
            min-height: 100vh;
            padding: 42px 18px 60px;
            color: #263b32;
            background: linear-gradient(135deg, #ffffff, #f5f8f4, #eaf2eb);
            font-family: inherit;
        }
        .business-registration-page * {
            box-sizing: border-box;
        }
        .registration-wrap {
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
        .back-home-container {
            margin-bottom: 16px;
            text-align: left;
        }
        .back-home-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #075b45;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .back-home-link:hover {
            color: #08775a;
            text-decoration: underline;
        }
        .registration-card {
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
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 19px 18px;
        }
        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 0;
        }
        .field.full-width {
            grid-column: 1 / -1;
        }
        .field label {
            color: #263b32;
            font-size: 14px;
            font-weight: 600;
        }
        .required {
            color: #b54747;
        }
        .field input,
        .field select,
        .field textarea {
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
        .field textarea {
            min-height: 100px;
            resize: vertical;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #08775a;
            box-shadow: 0 0 0 3px rgba(8, 119, 90, 0.12);
        }
        .field input::placeholder,
        .field textarea::placeholder {
            color: #9aa79d;
        }
        .hint {
            color: #7b8a7f;
            font-size: 12px;
            line-height: 1.5;
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
        .login-note a {
            color: #075b45;
            font-weight: bold;
            text-decoration: none;
        }
        .login-note a:hover {
            text-decoration: underline;
        }
        .registration-error {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 9px;
            background: #fff0f0;
            border: 1px solid #f0cccc;
            color: #a83232;
            font-size: 14px;
            overflow-wrap: anywhere;
        }
        @media (max-width: 600px) {
            .business-registration-page {
                padding: 28px 14px 42px;
            }
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .field.full-width {
                grid-column: auto;
            }
            .registration-card {
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

<main class="business-registration-page">

    <div class="back-home-container">
        <a href="business-owner-index.php" class="back-home-link">
            &larr; Back to Home
        </a>
    </div>

    <div class="registration-wrap">

        <header class="brand-heading">
            <img
                src="images/logo.png"
                alt="Al-Ahsa Vision Hub Logo"
                class="project-logo"
            >
            <h1>Al-Ahsa Vision Hub</h1>
            <p>
                Join our community and share your business
                with visitors of Al-Ahsa.
            </p>
        </header>

        <section class="registration-card">

            <div class="form-intro">
                <h2>Business Owner Registration</h2>
                <p>
                    Enter your details and business information
                    to create an account.
                </p>
            </div>

            <?php if ($errorMessage !== ''): ?>
                <div class="registration-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <form
                id="businessRegistrationForm"
                method="POST"
                action="business-owner-register.php"
            >

                <div class="form-grid">

                    <div class="field">
                        <label for="ownerName">
                            Owner Full Name <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="ownerName"
                            name="owner_name"
                            placeholder="Enter your full name"
                            autocomplete="name"
                            value="<?php echo htmlspecialchars($_POST['owner_name'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessName">
                            Business Name <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="businessName"
                            name="business_name"
                            placeholder="Enter your business name"
                            value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>"
                            required
                        >
                    </div>

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
                        <label for="phone">
                            Phone Number <span class="required">*</span>
                        </label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            placeholder="Enter your phone number"
                            autocomplete="tel"
                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="businessType">
                            Business Type <span class="required">*</span>
                        </label>
                        <select id="businessType" name="business_type" required>
                            <option value="" disabled
                                <?php echo empty($_POST['business_type']) ? 'selected' : ''; ?>>
                                Select business type
                            </option>
                            <option value="local-food-products"
                                <?php echo (($_POST['business_type'] ?? '') === 'local-food-products') ? 'selected' : ''; ?>>
                                Local Food &amp; Products
                            </option>
                            <option value="handicrafts-artisans"
                                <?php echo (($_POST['business_type'] ?? '') === 'handicrafts-artisans') ? 'selected' : ''; ?>>
                                Handicrafts &amp; Artisans
                            </option>
                            <option value="tourism-experiences"
                                <?php echo (($_POST['business_type'] ?? '') === 'tourism-experiences') ? 'selected' : ''; ?>>
                                Tourism Experiences
                            </option>
                            <option value="events-activities"
                                <?php echo (($_POST['business_type'] ?? '') === 'events-activities') ? 'selected' : ''; ?>>
                                Events &amp; Activities
                            </option>
                            <option value="other"
                                <?php echo (($_POST['business_type'] ?? '') === 'other') ? 'selected' : ''; ?>>
                                Other
                            </option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="businessSize">
                            Business Size <span class="required">*</span>
                        </label>
                        <select id="businessSize" name="business_size" required>
                            <option value="" disabled
                                <?php echo empty($_POST['business_size']) ? 'selected' : ''; ?>>
                                Select business size
                            </option>
                            <option value="small"
                                <?php echo (($_POST['business_size'] ?? '') === 'small') ? 'selected' : ''; ?>>
                                Small
                            </option>
                            <option value="medium"
                                <?php echo (($_POST['business_size'] ?? '') === 'medium') ? 'selected' : ''; ?>>
                                Medium
                            </option>
                            <option value="large"
                                <?php echo (($_POST['business_size'] ?? '') === 'large') ? 'selected' : ''; ?>>
                                Large
                            </option>
                        </select>
                    </div>

                    <div class="field full-width">
                        <label for="businessLocation">
                            Business Location <span class="required">*</span>
                        </label>
                        <input
                            type="text"
                            id="businessLocation"
                            name="business_location"
                            placeholder="Enter the city or address in Al-Ahsa"
                            value="<?php echo htmlspecialchars($_POST['business_location'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="field full-width">
                        <label for="description">
                            Business Description
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            placeholder="Briefly describe your products or services"
                        ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <span class="hint">
                            A short description helps visitors understand your business.
                        </span>
                    </div>

                    <div class="field">
                        <label for="password">
                            Password <span class="required">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Create a password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                        <span class="hint">
                            Use at least 8 characters.
                        </span>
                    </div>

                    <div class="field">
                        <label for="confirmPassword">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirm_password"
                            placeholder="Re-enter your password"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                </div>

                <div class="form-actions">
                    <button class="submit-button" type="submit">
                        Create Business Account
                    </button>
                </div>

            </form>

            <p class="login-note">
                Already have an account?
                <a href="business-owner-login.php">Log In</a>
            </p>

        </section>
    </div>
</main>

<button
    class="chat-button"
    onclick="openAI()"
    aria-label="Open AI Assistant"
    title="AI Assistant">
</button>

<script src="script.js"></script>

<script>
    document
        .getElementById("businessRegistrationForm")
        .addEventListener("submit", function (event) {

            const password = document.getElementById("password");
            const confirmPassword =
                document.getElementById("confirmPassword");

            if (password.value !== confirmPassword.value) {
                event.preventDefault();

                confirmPassword.setCustomValidity(
                    "Passwords do not match."
                );

                confirmPassword.reportValidity();
                return;
            }

            confirmPassword.setCustomValidity("");
        });

    document
        .getElementById("confirmPassword")
        .addEventListener("input", function () {
            this.setCustomValidity("");
        });
</script>

</body>
</html>