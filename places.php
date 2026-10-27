```php
<?php
include 'db.php';

// Fetch published entertainment places from the database
$sql = "SELECT *
        FROM Places
        WHERE Place_Type = 'entertainment'
        AND Is_Published = 1";

$result = $conn->query($sql);

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourist & Entertainment Places | Al-Ahsa Vision Hub</title>
    <link rel="stylesheet" href="css/style.css">
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

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <a href="index.html">
                <img src="images/logo.png"
                     alt="Al-Ahsa Vision Hub"
                     class="sidebar-logo">
            </a>
            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Discover the beauty of Al-Ahsa</p>
        </div>

        <div class="sidebar-links">
            <a href="index.html"><span>⌂</span> Home</a>
            <a href="explore-al-ahsa.html"><span>⌖</span> Explore Al-Ahsa</a>
            <a href="places.php" class="active"><span>⌘</span> Tourist Places</a>
            <a href="restaurants.html"><span>♨</span> Restaurants</a>
            <a href="cafes.html"><span>☕</span> Cafes</a>
            <a href="markets.html"><span>♧</span> Markets</a>
            <a href="hotels.html"><span>⌂</span> Hotels</a>
            <a href="settings.html"><span>⚙</span> Settings</a>
            <a href="my-account.html"><span>♙</span> My Account</a>
            <a href="About.html"><span>ⓘ</span> About Us</a>
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
                <a href="role-selection.html"
                   class="home-signup"
                   id="account-button">
                    Log In
                </a>

                <a href="index.html" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>
            </div>
        </header>

        <!-- Tourist Places Section -->
        <section class="home-section places-section">

            <!-- Green Banner and Image -->
            <div style="
                display: flex;
                align-items: stretch;
                min-height: 300px;
                margin-bottom: 40px;
                overflow: hidden;
                border-radius: 18px;
                background: #075b3b;
                box-shadow: 0 8px 24px rgba(25, 55, 40, 0.12);
            ">
                <div style="
                    flex: 1;
                    min-width: 0;
                    padding: 35px;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: flex-start;
                    text-align: left;
                    color: #ffffff;
                ">
                    <span style="
                        color: #d7eadc;
                        font-size: 12px;
                        font-weight: 700;
                        letter-spacing: 2px;
                        margin-bottom: 14px;
                    ">
                        EXPLORE THE HERITAGE OF AL-AHSA
                    </span>

                    <h1 style="
                        color: #ffffff;
                        font-size: clamp(28px, 3.5vw, 42px);
                        font-weight: 700;
                        line-height: 1.25;
                        margin: 0 0 16px;
                    ">
                        Tourist Entertainment Places
                    </h1>

                    <p style="
                        color: #ffffff;
                        font-size: 16px;
                        line-height: 1.8;
                        max-width: 380px;
                        margin: 0;
                    ">
                        Discover the beautiful parks, remarkable mountains,
                        and historic landmarks of Al-Ahsa.
                    </p>
                </div>

                <div style="
                    flex: 1.1;
                    min-width: 0;
                    min-height: 300px;
                    background-image:
                        linear-gradient(
                            to right,
                            #075b3b 0%,
                            rgba(7, 91, 59, 0.72) 12%,
                            rgba(7, 91, 59, 0.15) 42%,
                            rgba(7, 91, 59, 0) 65%
                        ),
                        url('images/Touristpic.jpg');
                    background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                "></div>
            </div>

            <!-- Tourist Places Grid -->
            <div class="places-grid">

                <?php if ($result && $result->num_rows > 0): ?>

                    <?php while ($place = $result->fetch_assoc()): ?>

                        <div class="about-card"
                             style="max-width: 100%; padding: 20px;">

                            <?php if (!empty($place['Image'])): ?>
                                <img
                                    src="<?= e($place['Image']) ?>"
                                    alt="<?= e($place['Name']) ?>"
                                    style="width: 100%; height: 180px; border-radius: 12px; object-fit: cover; margin-bottom: 15px;">
                            <?php endif; ?>

                            <h2 style="font-size: 24px; margin-bottom: 10px;">
                                <?= e($place['Name']) ?>
                            </h2>

                            <p style="font-size: 17px; line-height: 1.6; margin-bottom: 20px;">
                                <?= e($place['Short_Description']) ?>
                            </p>

                            <button
                                type="button"
                                onclick="window.location.href='place-details.php?id=<?= (int)$place['Place_ID'] ?>'"
                                style="background-color: #075b3b; color: #ffffff; border: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer;">
                                View Details
                            </button>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p>No published tourist places are available yet.</p>

                <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js"></script>

    <!-- Footer -->
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

</body>
</html>
```