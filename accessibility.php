
<?php
session_start();
require_once 'db.php';

// Tourist Login Protection
if (!isset($_SESSION['tourist_id'])) {
    header("Location: role-selection.html");
    exit();
}

$tourist_id = (int)$_SESSION['tourist_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Save Accessibility Preferences
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected_options = $_POST['accessibility_options'] ?? [];

    if (!is_array($selected_options)) {
        $selected_options = [];
    }

    $selected_options = array_unique(
        array_filter(
            array_map('intval', $selected_options),
            function ($id) {
                return $id > 0;
            }
        )
    );

    try {

        $conn->begin_transaction();

        // Delete Previous Preferences
        $stmtDelete = $conn->prepare("
            DELETE FROM tourist_accessibility
            WHERE Tourist_ID = ?
        ");

        $stmtDelete->bind_param("i", $tourist_id);
        $stmtDelete->execute();
        $stmtDelete->close();

        // Insert New Preferences
        if (!empty($selected_options)) {

            $stmtInsert = $conn->prepare("
                INSERT INTO tourist_accessibility
                (Tourist_ID, Accessibility_ID)
                VALUES (?, ?)
            ");

            foreach ($selected_options as $acc_id) {

                $stmtInsert->bind_param(
                    "ii",
                    $tourist_id,
                    $acc_id
                );

                $stmtInsert->execute();
            }

            $stmtInsert->close();
        }

        $conn->commit();

        $message = "Your accessibility preferences have been saved successfully!";
        $message_type = "success";

    } catch (Exception $e) {

        $conn->rollback();

        $message = "Unable to save your accessibility preferences. Please try again.";
        $message_type = "error";
    }
}

// Fetch All Accessibility Options
$options = [];

$optionsResult = $conn->query("
    SELECT Accessibility_ID, Accessibility_Name, Image
    FROM accessibility_options
    ORDER BY Accessibility_ID ASC
");

if ($optionsResult) {
    while ($row = $optionsResult->fetch_assoc()) {
        $options[] = $row;
    }
}

// Fetch Previously Saved Preferences
$savedOptions = [];

$stmtSaved = $conn->prepare("
    SELECT Accessibility_ID
    FROM tourist_accessibility
    WHERE Tourist_ID = ?
");

$stmtSaved->bind_param("i", $tourist_id);
$stmtSaved->execute();

$resSaved = $stmtSaved->get_result();

while ($row = $resSaved->fetch_assoc()) {
    $savedOptions[] = (int)$row['Accessibility_ID'];
}

$stmtSaved->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accessibility Services | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        /* Accessibility Page */

        .accessibility-container {
            max-width: 1150px;
            margin: 40px auto 70px;
            padding: 0 25px;
        }

        .accessibility-heading {
            margin-bottom: 25px;
        }

        .accessibility-heading h2 {
            color: #174b38;
            font-size: 30px;
            margin: 10px 0;
        }

        .accessibility-heading p {
            color: #777;
            font-size: 15px;
            line-height: 1.7;
            max-width: 650px;
        }

        /* Accessibility Grid */

        .accessibility-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        /* Accessibility Card */

        .acc-card {
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 2px solid #e8ece9;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        }

        .acc-card:hover {
            transform: translateY(-5px);
            border-color: #7cae96;
            box-shadow: 0 9px 22px rgba(0, 0, 0, 0.09);
        }

        .acc-card.selected {
            border-color: #08734d;
            background: #f5faf7;
            box-shadow: 0 0 0 2px rgba(8, 115, 77, 0.08);
        }

        /* Image */

        .acc-image {
            width: 100%;
            height: 205px;
            overflow: hidden;
            background: #f1f4f1;
        }

        .acc-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }

        .acc-card:hover .acc-image img {
            transform: scale(1.04);
        }

        /* Card Information */

        .acc-card-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 17px 18px;
            min-height: 75px;
        }

        .acc-card h3 {
            color: #29483a;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.5;
            margin: 0;
            text-align: left;
        }

        /* Checkbox */

        .acc-checkbox {
            flex-shrink: 0;
            appearance: none;
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            min-width: 24px;
            border: 2px solid #aebbb2;
            border-radius: 6px;
            background: #fff;
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
        }

        .acc-checkbox:checked {
            background: #08734d;
            border-color: #08734d;
        }

        .acc-checkbox:checked::after {
            content: "✓";
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -53%);
        }

        .acc-checkbox:focus-visible {
            outline: 3px solid rgba(8, 115, 77, 0.25);
            outline-offset: 3px;
        }

        /* Save Button */

        .save-accessibility-wrapper {
            text-align: center;
            margin-top: 35px;
        }

        .save-btn {
            background: #0b7653;
            color: #fff;
            border: none;
            padding: 14px 42px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 9px;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(11, 118, 83, 0.18);
        }

        .save-btn:hover {
            background: #075b3b;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(11, 118, 83, 0.25);
        }

        /* Messages */

        .accessibility-message {
            padding: 14px 18px;
            border-radius: 9px;
            margin: 20px 0;
            text-align: center;
            font-size: 14px;
        }

        .alert-success {
            background: #e4f3e9;
            color: #17613e;
            border: 1px solid #c4e4ce;
        }

        .alert-error {
            background: #fbe9e9;
            color: #a12b2b;
            border: 1px solid #efcaca;
        }

        .no-options {
            color: #777;
            padding: 20px 0;
        }

        /* Responsive Design */

        @media (max-width: 850px) {
            .accessibility-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 18px;
            }

            .acc-image {
                height: 180px;
            }
        }

        @media (max-width: 550px) {
            .accessibility-container {
                padding: 0 15px;
                margin-top: 25px;
            }

            .accessibility-heading h2 {
                font-size: 25px;
            }

            .accessibility-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .acc-image {
                height: 220px;
            }

            .save-btn {
                width: 100%;
                max-width: 300px;
            }
        }

    </style>

</head>

<body class="home-page">

    <!-- Menu Button -->
    <button class="menu-button"
            onclick="toggleMenu()"
            aria-label="Open menu">
        ☰
    </button>

    <!-- Sidebar Background -->
    <div class="sidebar-backdrop" onclick="toggleMenu()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">

        <div class="sidebar-brand">

            <a href="index.php">
                <img src="images/logo.png"
                     alt="Al-Ahsa Vision Hub"
                     class="sidebar-logo">
            </a>

            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Discover the beauty of Al-Ahsa</p>

        </div>

        <div class="sidebar-links">

            <a href="index.php"><span>⌂</span> Home</a>
            <a href="explore-al-ahsa.php"><span>⌖</span> Explore Al-Ahsa</a>
            <a href="places.php"><span>⌘</span> Tourist Places</a>
            <a href="seasons.php"><span>⌘</span> Seasons</a>
            <a href="restaurants.php"><span>♨</span> Restaurants</a>
            <a href="cafes.php"><span>☕</span> Cafes</a>
            <a href="markets.php"><span>♧</span> Markets</a>
            <a href="hotels.php"><span>⌂</span> Hotels</a>
            <a href="settings.php"><span>⚙</span> Settings</a>
            <a href="my-account.php"><span>♙</span> My Account</a>
            <a href="About.php"><span>ⓘ</span> About Us</a>

        </div>

        <div class="sidebar-decoration">
            <span>🌴</span>
            <p>THE HEART OF AL-AHSA</p>
        </div>

    </nav>

    <!-- Main Content -->
    <main class="home-main">

        <!-- Top Bar -->
        <header class="home-topbar">

            <div class="home-location">
                <span class="location-dot"></span>
                DISCOVER AL-AHSA, SAUDI ARABIA
            </div>

            <div class="home-account-links">

                <a href="my-account.php"
                   class="home-signup"
                   id="account-button">

                    <?= e($_SESSION['tourist_name'] ?? 'My Account') ?>

                </a>

                <a href="index.php" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>

            </div>

        </header>

        <!-- Accessibility Content -->
        <div class="accessibility-container">

            <div class="accessibility-heading">

                <div class="home-section-heading">
                    <div>

                        <span class="home-eyebrow">
                            A MORE COMFORTABLE VISIT
                        </span>

                        <h2>Accessibility Services</h2>

                        <p>
                            Select your accessibility requirements to customize
                            your travel experiences in Al-Ahsa.
                        </p>

                    </div>
                </div>

            </div>

            <!-- Save Message -->
            <?php if ($message !== ''): ?>

                <div class="accessibility-message <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>">
                    <?= e($message) ?>
                </div>

            <?php endif; ?>

            <!-- Accessibility Form -->
            <form method="POST" action="accessibility.php">

                <?php if (!empty($options)): ?>

                    <div class="accessibility-grid">

                        <?php foreach ($options as $row): ?>

                            <?php
                            $isChecked = in_array(
                                (int)$row['Accessibility_ID'],
                                $savedOptions,
                                true
                            );
                            ?>

                            <label class="acc-card <?= $isChecked ? 'selected' : '' ?>">

                                <div class="acc-image">

                                    <img
                                        src="<?= e($row['Image']) ?>"
                                        alt="<?= e($row['Accessibility_Name']) ?>"
                                        loading="lazy"
                                    >

                                </div>

                                <div class="acc-card-content">

                                    <h3>
                                        <?= e($row['Accessibility_Name']) ?>
                                    </h3>

                                    <input
                                        type="checkbox"
                                        name="accessibility_options[]"
                                        value="<?= (int)$row['Accessibility_ID'] ?>"
                                        class="acc-checkbox"
                                        <?= $isChecked ? 'checked' : '' ?>
                                        aria-label="Select <?= e($row['Accessibility_Name']) ?>"
                                    >

                                </div>

                            </label>

                        <?php endforeach; ?>

                    </div>

                    <div class="save-accessibility-wrapper">

                        <button type="submit" class="save-btn">
                            Save Accessibility
                        </button>

                    </div>

                <?php else: ?>

                    <p class="no-options">
                        No accessibility options found.
                    </p>

                <?php endif; ?>

            </form>

        </div>

    </main>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js?v=2"></script>

    <!-- Accessibility Card Selection -->
    <script>

        document.querySelectorAll('.acc-checkbox').forEach(function(checkbox) {

            checkbox.addEventListener('change', function() {

                const card = this.closest('.acc-card');

                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }

            });

        });

    </script>

    <!-- Footer -->
    <footer class="site-footer">

        <div class="site-footer-main">

            <div class="site-footer-brand">
                <h3>Al-Ahsa Vision Hub</h3>
                <p>
                    Discover the beauty, heritage, and experiences of Al-Ahsa.
                </p>
            </div>

            <div class="site-footer-links">
                <h4>Explore Al-Ahsa</h4>
                <p>
                    Discover places, restaurants, cafés, and seasonal events.
                </p>
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

</body>
</html>
