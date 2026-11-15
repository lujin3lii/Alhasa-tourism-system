

<?php
session_start();
require_once 'db.php';

// Admin Login Protection
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

// Admin Name
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Fetch All Reviews
$sql = "

    SELECT
        'Place' AS Review_Type,
        COALESCE(t.Name, 'Unknown Tourist') AS Reviewer,
        COALESCE(p.Name, CONCAT('Place #', pr.Place_ID)) AS Reviewed_Item,
        pr.Rating,
        pr.Comment
    FROM Place_Reviews pr
    LEFT JOIN Tourist t
        ON pr.Tourist_ID = t.Tourist_ID
    LEFT JOIN Places p
        ON pr.Place_ID = p.Place_ID

    UNION ALL

    SELECT
        'Itinerary' AS Review_Type,
        COALESCE(t.Name, 'Unknown Tourist') AS Reviewer,
        CONCAT('Itinerary #', ir.Itinerary_ID) AS Reviewed_Item,
        ir.Rating,
        ir.Comment
    FROM itinerary_reviews ir
    LEFT JOIN Tourist t
        ON ir.Tourist_ID = t.Tourist_ID
    LEFT JOIN itinerary i
        ON ir.Itinerary_ID = i.itinerary_id

    UNION ALL

    SELECT
        'System' AS Review_Type,
        COALESCE(t.Name, 'Unknown Tourist') AS Reviewer,
        'Al-Ahsa Vision Hub' AS Reviewed_Item,
        sr.Rating,
        sr.Comment
    FROM system_reviews sr
    LEFT JOIN Tourist t
        ON sr.Tourist_ID = t.Tourist_ID

";

$reviews_query = $conn->query($sql);

if (!$reviews_query) {
    die("Database query failed: " . htmlspecialchars($conn->error));
}

// Total Reviews
$totalReviews = $reviews_query->num_rows;

// Escape Output
function e($value) {
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Reviews - Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        body {
            background: #f4f7f6;
        }

        .reviews-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 35px 30px;
        }

        .reviews-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 28px;
        }

        .reviews-heading h1 {
            color: #075b45;
            margin: 0 0 8px;
            font-size: 28px;
        }

        .reviews-heading p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }

        .reviews-total-card {
            background: #fff;
            padding: 20px 25px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            min-width: 180px;
        }

        .reviews-total-card span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .reviews-total-card strong {
            color: #075b45;
            font-size: 27px;
        }

        .reviews-table-card {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .reviews-table-heading {
            margin-bottom: 20px;
        }

        .reviews-table-heading h2 {
            color: #174c3a;
            font-size: 18px;
            margin: 0;
        }

        .reviews-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .reviews-table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            text-align: left;
        }

        .reviews-table th {
            padding: 16px 14px;
            color: #777;
            font-size: 13px;
            font-weight: 600;
            background: #f8faf9;
            border-bottom: 2px solid #edf1ef;
            white-space: nowrap;
        }

        .reviews-table td {
            padding: 17px 14px;
            color: #555;
            font-size: 13px;
            border-bottom: 1px solid #f0f2f1;
            vertical-align: middle;
        }

        .reviews-table tbody tr:hover {
            background: #fafcfb;
        }

        .reviewer-name {
            color: #174c3a;
            font-weight: 600;
        }

        .review-type {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #edf5f1;
            color: #075b45;
            white-space: nowrap;
        }

        .review-item {
            color: #444;
            font-weight: 500;
        }

        .review-rating {
            white-space: nowrap;
        }

        .review-stars {
            color: #e5a900;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .review-rating-number {
            color: #555;
            font-size: 12px;
            margin-left: 4px;
        }

        .review-comment {
            max-width: 350px;
            min-width: 180px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .empty-comment {
            color: #999;
            font-style: italic;
        }

        .no-reviews {
            text-align: center;
            padding: 35px !important;
            color: #999 !important;
        }

        @media (max-width: 768px) {

            .reviews-content {
                padding: 25px 15px;
            }

            .reviews-heading h1 {
                font-size: 23px;
            }

            .reviews-table-card {
                padding: 15px;
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

    <!-- Admin Sidebar -->
    <nav class="sidebar" id="sidebar">

        <div class="sidebar-brand">

            <a href="admin-dashboard.php">
                <img src="images/logo.png"
                     alt="Al-Ahsa Vision Hub"
                     class="sidebar-logo">
            </a>

            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Admin Portal</p>

        </div>

        <div class="sidebar-links">

            <a href="admin-dashboard.php">
                <span>⌂</span> Dashboard
            </a>

            <a href="admin-tourists.php">
                <span>♙</span> Tourists
            </a>

            <a href="admin-business-owners.php">
                <span>▣</span> Business Owners
            </a>

            <a href="admin-places.php">
                <span>⌖</span> Places
            </a>

            <a href="admin-reviews.php" class="active">
                <span>☆</span> Reviews
            </a>

            <a href="admin-itineraries.php">
                <span>▤</span> Itineraries
            </a>

            <a href="admin-survey-accessibility.php">
                <span>☷</span> Survey & Accessibility
            </a>

            <a href="admin-logout.php">
                <span>⇥</span> Log Out
            </a>

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

                <a href="#" class="home-signup" id="account-button">
                    <?= e($adminName) ?>
                </a>

                <a href="admin-dashboard.php" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>

            </div>

        </header>

        <!-- Page Content -->
        <div class="reviews-content">

            <div class="reviews-heading">

                <div>
                    <h1>Reviews Management</h1>

                    <p>
                        View and monitor reviews submitted by tourists.
                    </p>
                </div>

                <div class="reviews-total-card">

                    <span>Total Reviews</span>

                    <strong>
                        <?= e($totalReviews) ?>
                    </strong>

                </div>

            </div>

            <!-- Reviews Table -->
            <section class="reviews-table-card">

                <div class="reviews-table-heading">
                    <h2>All Reviews</h2>
                </div>

                <div class="reviews-table-wrapper">

                    <table class="reviews-table">

                        <thead>

                            <tr>
                                <th>Reviewer</th>
                                <th>Review Type</th>
                                <th>Place / Itinerary</th>
                                <th>Rating</th>
                                <th>Comment</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($reviews_query->num_rows > 0): ?>

                                <?php while ($row = $reviews_query->fetch_assoc()): ?>

                                    <tr>

                                        <td class="reviewer-name">
                                            <?= e($row['Reviewer']) ?>
                                        </td>

                                        <td>
                                            <span class="review-type">
                                                <?= e($row['Review_Type']) ?>
                                            </span>
                                        </td>

                                        <td class="review-item">
                                            <?= e($row['Reviewed_Item']) ?>
                                        </td>

                                        <!-- Rating -->
                                        <td class="review-rating">

                                            <?php
                                            $rating = (int)$row['Rating'];
                                            $rating = max(0, min(5, $rating));
                                            ?>

                                            <span class="review-stars">

                                                <?php
                                                for ($star = 1; $star <= 5; $star++) {
                                                    echo $star <= $rating ? '★' : '☆';
                                                }
                                                ?>

                                            </span>

                                            <span class="review-rating-number">
                                                <?= e($rating) ?>/5
                                            </span>

                                        </td>

                                        <!-- Comment -->
                                        <td class="review-comment">

                                            <?php if (!empty($row['Comment'])): ?>

                                                <?= e($row['Comment']) ?>

                                            <?php else: ?>

                                                <span class="empty-comment">
                                                    No comment
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="no-reviews">
                                        No reviews found.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

    <!-- AI Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

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
                <p>
                    Making your journey through Al-Ahsa memorable.
                </p>
            </div>

            <div class="site-footer-contact">
                <h4>Contact Us</h4>

                <p>05123546798</p>

                <p>
                    <a href="https://www.instagram.com/" target="_blank">
                        ◎ Instagram
                    </a>
                </p>

                <p>
                    <a href="https://x.com/" target="_blank">
                        𝕏 X (Twitter)
                    </a>
                </p>

            </div>

        </div>

        <div class="site-footer-bottom">

            <p>&copy; 2026 Al-Ahsa Vision Hub. All rights reserved.</p>

            <p>Explore Al-Ahsa, experience its beauty.</p>

        </div>

    </footer>

<script src="script.js?v=2"></script>
</body>
</html>
