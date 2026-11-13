<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function trip_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function trip_require_tourist() {
    if (empty($_SESSION['tourist_id'])) {
        header('Location: role-selection.html');
        exit;
    }
}

function trip_page_header($active = '') {
    $active = (string)$active;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Trip Planner | Al-Ahsa Vision Hub</title>
        <link rel="stylesheet" href="css/style.css">
        <link rel="stylesheet" href="css/trip-planner.css">
    </head>
    <body>
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

            <div class="home-account-links">
                <?php if (isset($_SESSION['tourist_id'])): ?>
                    <a href="my-account.php"
                       class="home-signup"
                       id="account-button">
                        <?= htmlspecialchars($_SESSION['tourist_name'] ?? 'My Account', ENT_QUOTES, 'UTF-8') ?>
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
    <?php
}

function trip_page_footer() {
    ?>
        </main>
        <script src="script.js"></script>
    </body>
    </html>
    <?php 
}
