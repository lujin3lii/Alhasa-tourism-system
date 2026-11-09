<?php
session_start();

// Log out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: index.php");
    exit;
}

// Database connection
require_once 'db.php';

// Feedback variables
$feedbackMessage = '';
$feedbackMessageType = '';
$feedbackRating = '';
$feedbackComment = '';
$showFeedbackModal = false;

// Save feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_submit'])) {

    $showFeedbackModal = true;

    $feedbackRating = $_POST['rating'] ?? '';
    $feedbackComment = trim($_POST['comment'] ?? '');

    // Check if tourist is logged in
    if (!isset($_SESSION['tourist_id'])) {

        $feedbackMessage = 'Please log in to submit your feedback.';
        $feedbackMessageType = 'error';

    } elseif (
        !filter_var($feedbackRating, FILTER_VALIDATE_INT) ||
        $feedbackRating < 1 ||
        $feedbackRating > 5
    ) {

        $feedbackMessage = 'Please select a star rating first.';
        $feedbackMessageType = 'error';

    } elseif ($feedbackComment === '') {

        $feedbackMessage = 'Please write your comments or suggestions.';
        $feedbackMessageType = 'error';

    } else {

        $touristId = (int) $_SESSION['tourist_id'];
        $rating = (int) $feedbackRating;
        $comment = $feedbackComment;

        // Insert new feedback or update the existing feedback
        $sql = "INSERT INTO `system_reviews`
                (`Tourist_ID`, `Rating`, `Comment`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                `Rating` = VALUES(`Rating`),
                `Comment` = VALUES(`Comment`)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("iis", $touristId, $rating, $comment);

            if ($stmt->execute()) {

                $feedbackMessage = 'Thank you! Your feedback has been submitted successfully.';
                $feedbackMessageType = 'success';

            } else {

                $feedbackMessage = 'Something went wrong. Please try again.';
                $feedbackMessageType = 'error';

            }

            $stmt->close();

        } else {

            $feedbackMessage = 'Unable to submit your feedback. Please try again.';
            $feedbackMessageType = 'error';

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Settings | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        /* Settings page only */

        .settings-page {
            max-width: 1000px;
            margin: 40px auto;
            padding: 25px;
        }

        .settings-page > h1 {
            color: #386D73;
            margin-bottom: 8px;
        }

        .settings-intro {
            color: #687779;
            margin-bottom: 30px;
        }

        .settings-section {
            background: #ffffff;
            border: 1px solid #e3e9e7;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 3px 12px rgba(7, 91, 70, 0.045);
            transition: box-shadow 0.25s ease, transform 0.25s ease;
        }

        .settings-section:hover {
            box-shadow: 0 6px 18px rgba(7, 91, 70, 0.08);
            transform: translateY(-2px);
        }

        .settings-section h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 19px;
            color: #386D73;
            margin-top: 0;
            margin-bottom: 12px;
        }

        .settings-section p {
            color: #687779;
            margin-bottom: 16px;
        }

        .settings-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border-radius: 12px;
            color: #386D73;
        }

        .settings-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Unified style for all Settings action buttons */

        .settings-page .settings-action,
        .settings-page .settings-action:link,
        .settings-page .settings-action:visited,
        .settings-page .settings-action:hover,
        .settings-page .settings-action:focus,
        .settings-page .settings-action:active,
        .settings-page .settings-logout-button,
        .settings-page .settings-logout-button:hover,
        .feedback-submit,
        .feedback-submit:hover {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 17px;
            border: 1px solid #386D73 !important;
            border-radius: 8px;
            background: #386D73 !important;
            color: #ffffff !important;
            text-decoration: none !important;
            cursor: pointer;
            font: inherit;
            font-weight: 500;
            opacity: 1 !important;
            box-shadow: none;
            transition: none;
        }

        .settings-page .settings-action .settings-icon {
            width: 30px;
            height: 30px;
            min-width: 30px;
            background: transparent !important;
            color: #ffffff !important;
        }

        .settings-page .settings-action .settings-icon svg {
            width: 20px;
            height: 20px;
            stroke: #ffffff !important;
        }

        .settings-page .settings-logout-button {
            padding: 10px 17px;
        }

        .feedback-submit {
            width: 100%;
            margin-top: 15px;
            padding: 12px;
        }

        /* Feedback popup */

        .feedback-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 2000;
            background: rgba(0, 0, 0, 0.55);
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .feedback-modal.show {
            display: flex;
        }

        .feedback-modal-content {
            width: 100%;
            max-width: 480px;
            background: #ffffff;
            border-radius: 16px;
            padding: 25px;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }

        .feedback-close {
            position: absolute;
            top: 12px;
            right: 16px;
            border: none;
            background: transparent;
            font-size: 26px;
            color: #687779;
            cursor: pointer;
        }

        .feedback-modal-content h2 {
            color: #386D73;
            margin-top: 0;
            padding-right: 25px;
        }

        .feedback-modal-content p {
            color: #687779;
        }

        .feedback-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .feedback-star {
            border: none;
            background: transparent;
            color: #c8c8c8;
            font-size: 32px;
            cursor: pointer;
            padding: 2px;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .feedback-star.selected {
            color: #D6A43B;
        }

        .feedback-star:hover {
            transform: scale(1.12);
        }

        .feedback-modal-content textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #e3e9e7;
            border-radius: 9px;
            font: inherit;
            resize: vertical;
            box-sizing: border-box;
        }

        .feedback-message {
            margin-top: 12px;
            font-size: 14px;
            color: #386D73;
        }

        .feedback-message.error {
            color: #c0392b;
        }

        .feedback-message.success {
            color: #386D73;
        }

        @media (max-width: 600px) {
            .settings-page {
                margin: 20px auto;
                padding: 15px;
            }

            .settings-section {
                padding: 17px;
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
        <a href="hotels.php" class="active"><span>⌂</span> Hotels</a>
        <a href="markets.php"><span>♧</span> Markets</a>
        <a href="settings.php" class="active"><span>⚙</span> Settings</a>
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

                <a href="my-account.html"
                   class="home-signup"
                   id="account-button">
                    <?= htmlspecialchars($_SESSION['tourist_name'], ENT_QUOTES, 'UTF-8') ?>
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

</main>

<!-- Settings Content -->
<main class="settings-page">

    <h1>Settings</h1>

    <p class="settings-intro">
        Manage your account and explore available services.
    </p>

    <!-- My Account -->
    <section class="settings-section">

        <h2>My Account</h2>

        <p>
            View and manage your personal account information.
        </p>

        <a href="my-account.html" class="settings-action">

            <span class="settings-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M5 21v-2a7 7 0 0 1 14 0v2"></path>
                </svg>
            </span>

            My Account

        </a>

    </section>

    <!-- Accessibility Services -->
    <section class="settings-section">

        <h2>
            <span class="settings-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="4" r="2"></circle>
                    <path d="M4 8h16"></path>
                    <path d="M12 8v13"></path>
                    <path d="m8 21 4-7 4 7"></path>
                </svg>
            </span>
            Accessibility Services
        </h2>

        <p>
            Explore accessibility services and options available on our website.
        </p>

        <a href="accessibility.php" class="settings-action">
            Learn More
        </a>

    </section>

    <!-- About Us -->
    <section class="settings-section">

        <h2>
            <span class="settings-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 11v5"></path>
                    <path d="M12 8h.01"></path>
                </svg>
            </span>
            About Us
        </h2>

        <p>
            Learn more about Al-Ahsa Vision Hub and our project.
        </p>

        <a href="About.html" class="settings-action">
            About Al-Ahsa Vision Hub
        </a>

    </section>

    <!-- Feedback & Rating -->
    <section class="settings-section">

        <h2>
            <span class="settings-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9L12 3Z"></path>
                </svg>
            </span>
            Feedback &amp; Rating
        </h2>

        <p>
            Share your experience and help us improve our website.
        </p>

        <button type="button"
                class="settings-action"
                onclick="openFeedback()">
            Give Feedback
        </button>

    </section>

    <!-- Log Out -->
    <section class="settings-section">

        <h2>
            <span class="settings-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M10 17l5-5-5-5"></path>
                    <path d="M15 12H3"></path>
                    <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"></path>
                </svg>
            </span>
            Log Out
        </h2>

        <p>
            Sign out of your account.
        </p>

        <form method="POST" action="settings.php">
            <button type="submit"
                    name="logout"
                    value="1"
                    class="settings-logout-button">
                Log Out
            </button>
        </form>

    </section>

</main>

<!-- Feedback Popup -->
<div class="feedback-modal"
     id="feedbackModal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="feedbackTitle">

    <div class="feedback-modal-content">

        <button type="button"
                class="feedback-close"
                onclick="closeFeedback()"
                aria-label="Close">
            &times;
        </button>

        <h2 id="feedbackTitle">Feedback &amp; Rating</h2>

        <p>
            How would you rate your experience with Al-Ahsa Vision Hub?
        </p>

        <form id="feedbackForm" method="POST" action="settings.php">

            <div class="feedback-stars" aria-label="Choose a rating">

                <button type="button" class="feedback-star" data-rating="1"
                        aria-label="1 star">★</button>

                <button type="button" class="feedback-star" data-rating="2"
                        aria-label="2 stars">★</button>

                <button type="button" class="feedback-star" data-rating="3"
                        aria-label="3 stars">★</button>

                <button type="button" class="feedback-star" data-rating="4"
                        aria-label="4 stars">★</button>

                <button type="button" class="feedback-star" data-rating="5"
                        aria-label="5 stars">★</button>

            </div>

            <input type="hidden"
                   id="feedbackRating"
                   name="rating"
                   value="<?= htmlspecialchars((string)$feedbackRating, ENT_QUOTES, 'UTF-8') ?>">

            <label for="feedbackComment">
                Share your comments or suggestions:
            </label>

            <textarea id="feedbackComment"
                      name="comment"
                      placeholder="Write your feedback here..."
                      required><?= htmlspecialchars($feedbackComment, ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit"
                    name="feedback_submit"
                    value="1"
                    class="feedback-submit">
                Submit Feedback
            </button>

            <?php if ($feedbackMessage !== ''): ?>
                <p id="feedbackMessage"
                   class="feedback-message <?= htmlspecialchars($feedbackMessageType, ENT_QUOTES, 'UTF-8') ?>"
                   aria-live="polite">
                    <?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php else: ?>
                <p id="feedbackMessage"
                   class="feedback-message"
                   aria-live="polite"></p>
            <?php endif; ?>

        </form>

    </div>

</div>

<!-- AI Assistant Button -->
<button class="chat-button"
        onclick="openAI()"
        aria-label="Open AI Assistant"
        title="AI Assistant">
</button>

<!-- Shared JavaScript -->
<script src="script.js"></script>

<script>
    // Open feedback popup
    function openFeedback() {
        document.getElementById("feedbackModal").classList.add("show");
    }

    // Close feedback popup
    function closeFeedback() {
        document.getElementById("feedbackModal").classList.remove("show");
    }

    // Open popup automatically after submitting feedback
    <?php if ($showFeedbackModal): ?>
        openFeedback();
    <?php endif; ?>

    // Close popup when clicking outside it
    document.getElementById("feedbackModal").addEventListener("click", function(event) {
        if (event.target === this) {
            closeFeedback();
        }
    });

    // Star rating selection
    const ratingButtons = document.querySelectorAll(".feedback-star");
    const ratingInput = document.getElementById("feedbackRating");

    function updateStars(selectedRating) {
        ratingButtons.forEach(function(star) {
            const starRating = Number(star.dataset.rating);

            star.classList.toggle(
                "selected",
                starRating <= selectedRating
            );
        });
    }

    ratingButtons.forEach(function(button) {

        button.addEventListener("click", function() {

            const selectedRating = Number(this.dataset.rating);

            ratingInput.value = selectedRating;

            updateStars(selectedRating);

            const message = document.getElementById("feedbackMessage");
            message.textContent = "";
            message.className = "feedback-message";

        });

    });

    // Restore selected stars after form submission
    if (ratingInput.value) {
        updateStars(Number(ratingInput.value));
    }
</script>

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