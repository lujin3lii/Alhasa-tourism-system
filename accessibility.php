<?php
session_start();
require_once 'db.php';

// معالجة حفظ خيارات السائح
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // التأكد من تسجيل دخول السائح
    if (!isset($_SESSION['tourist_id'])) {
        header("Location: role-selection.html");
        exit();
    }

    $tourist_id = $_SESSION['tourist_id'];
    $selected_options = $_POST['accessibility_options'] ?? [];

    // 1. مسح الخيارات القديمة لعدم التكرار
    $stmtDelete = $conn->prepare("DELETE FROM tourist_accessibility WHERE Tourist_ID = ?");
    $stmtDelete->bind_param("i", $tourist_id);
    $stmtDelete->execute();
    $stmtDelete->close();

    // 2. إضافة الخيارات الجديدة
    if (!empty($selected_options)) {
        $stmtInsert = $conn->prepare("
            INSERT INTO tourist_accessibility
            (Tourist_ID, Accessibility_ID)
            VALUES (?, ?)
        ");

        foreach ($selected_options as $acc_id) {
            $acc_id = (int)$acc_id;

            $stmtInsert->bind_param("ii", $tourist_id, $acc_id);
            $stmtInsert->execute();
        }

        $stmtInsert->close();
    }

    $message = "تم حفظ تفضيلات الوصول بنجاح!";
}

// جلب كافة خدمات وخيارات الوصول المتاحة
$optionsResult = $conn->query("SELECT * FROM accessibility_options");

// جلب التفضيلات المحفوظة سابقاً للسائح الحالي
$savedOptions = [];

if (isset($_SESSION['tourist_id'])) {
    $tourist_id = $_SESSION['tourist_id'];

    $stmtSaved = $conn->prepare("
        SELECT Accessibility_ID
        FROM tourist_accessibility
        WHERE Tourist_ID = ?
    ");

    $stmtSaved->bind_param("i", $tourist_id);
    $stmtSaved->execute();

    $resSaved = $stmtSaved->get_result();

    while ($row = $resSaved->fetch_assoc()) {
        $savedOptions[] = $row['Accessibility_ID'];
    }

    $stmtSaved->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accessibility Services | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        .accessibility-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .accessibility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .acc-card {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 2px solid #eee;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .acc-card:hover {
            border-color: #075b3b;
            transform: translateY(-5px);
        }

        .acc-card img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .acc-card h3 {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .acc-checkbox {
            width: 20px;
            height: 20px;
            accent-color: #075b3b;
            cursor: pointer;
        }

        .save-btn {
            background-color: #075b3b;
            color: white;
            border: none;
            padding: 12px 35px;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 30px;
            transition: background 0.3s ease;
        }

        .save-btn:hover {
            background-color: #05442c;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
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

            <!-- Account Button -->
            <div class="home-account-links">

                <?php if (isset($_SESSION['tourist_id'])): ?>

                    <a href="my-account.php"
                       class="home-signup"
                       id="account-button">

                        <?= htmlspecialchars(
                            $_SESSION['tourist_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </a>

                <?php else: ?>

                    <a href="role-selection.html"
                       class="home-signup"
                       id="account-button">
                        Log In
                    </a>

                <?php endif; ?>

                <a href="index.php" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>

            </div>

        </header>

        <!-- Accessibility Content Area -->
        <div class="accessibility-container">

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

            <?php if (!empty($message)): ?>
                <div class="alert-success">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="accessibility.php">

                <div class="accessibility-grid">

                    <?php if ($optionsResult && $optionsResult->num_rows > 0): ?>

                        <?php while ($row = $optionsResult->fetch_assoc()): ?>

                            <?php
                            $isChecked = in_array(
                                $row['Accessibility_ID'],
                                $savedOptions
                            ) ? 'checked' : '';
                            ?>

                            <label class="acc-card">

                                <img
                                    src="<?= htmlspecialchars($row['Image']) ?>"
                                    alt="<?= htmlspecialchars($row['Accessibility_Name']) ?>"
                                >

                                <h3>
                                    <?= htmlspecialchars($row['Accessibility_Name']) ?>
                                </h3>

                                <input
                                    type="checkbox"
                                    name="accessibility_options[]"
                                    value="<?= (int)$row['Accessibility_ID'] ?>"
                                    class="acc-checkbox"
                                    <?= $isChecked ?>
                                >

                            </label>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <p>No accessibility options found.</p>

                    <?php endif; ?>

                </div>

                <div style="text-align: center;">
                    <button type="submit" class="save-btn">
                        Save Preferences
                    </button>
                </div>

            </form>

        </div>

    </main>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open assistant"
            title="Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js"></script>

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