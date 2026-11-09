<?php
session_start();
require_once 'db.php';

/* Business Owner Information */
$ownerID = (int) ($_SESSION['business_owner_id'] ?? 0);
$ownerName = $_SESSION['business_owner_name'] ?? 'My Account';

$totalReviews = 0;
$averageRating = 0;
$totalFavorites = 0;
$monthlyReviews = [];
$recentReviews = [];
$dbError = '';

try {

    if ($ownerID > 0) {

        /* Total Reviews and Average Rating */
        $stmt = $conn->prepare("
            SELECT
                COUNT(pr.Place_Review_id),
                COALESCE(AVG(pr.Rating), 0)
            FROM places p
            LEFT JOIN place_reviews pr
                ON p.Place_ID = pr.Place_ID
            WHERE p.Business_Owner_ID = ?
        ");

        $stmt->bind_param("i", $ownerID);
        $stmt->execute();
        $stmt->bind_result($totalReviews, $averageRating);
        $stmt->fetch();
        $stmt->close();

        $averageRating = round((float)$averageRating, 1);

        /* Total Favorites */
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM favorites f
            INNER JOIN places p
                ON f.Place_ID = p.Place_ID
            WHERE p.Business_Owner_ID = ?
        ");

        $stmt->bind_param("i", $ownerID);
        $stmt->execute();
        $stmt->bind_result($totalFavorites);
        $stmt->fetch();
        $stmt->close();

        /* Reviews During the Last Six Months */
        $stmt = $conn->prepare("
            SELECT
                DATE_FORMAT(pr.Create_at, '%Y-%m') AS review_month,
                COUNT(*) AS review_count
            FROM place_reviews pr
            INNER JOIN places p
                ON pr.Place_ID = p.Place_ID
            WHERE p.Business_Owner_ID = ?
              AND pr.Create_at >= DATE_SUB(
                  DATE_FORMAT(CURDATE(), '%Y-%m-01'),
                  INTERVAL 5 MONTH
              )
            GROUP BY DATE_FORMAT(pr.Create_at, '%Y-%m')
            ORDER BY review_month ASC
        ");

        $stmt->bind_param("i", $ownerID);
        $stmt->execute();

        $result = $stmt->get_result();

        $reviewsByMonth = [];

        while ($row = $result->fetch_assoc()) {
            $reviewsByMonth[$row['review_month']] =
                (int)$row['review_count'];
        }

        $stmt->close();

        /* Prepare Six Months */
        for ($i = 5; $i >= 0; $i--) {

            $date = new DateTime('first day of this month');
            $date->modify("-$i months");

            $monthKey = $date->format('Y-m');
            $monthName = $date->format('M');

            $monthlyReviews[] = [
                'month' => $monthName,
                'total' => $reviewsByMonth[$monthKey] ?? 0
            ];
        }

        /* Recent Reviews */
        $stmt = $conn->prepare("
            SELECT
                pr.Comment,
                pr.Rating,
                pr.Create_at,
                p.Name
            FROM place_reviews pr
            INNER JOIN places p
                ON pr.Place_ID = p.Place_ID
            WHERE p.Business_Owner_ID = ?
            ORDER BY pr.Create_at DESC
            LIMIT 5
        ");

        $stmt->bind_param("i", $ownerID);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $recentReviews[] = $row;
        }

        $stmt->close();
    }

} catch (Throwable $e) {

    error_log($e->getMessage());
    $dbError = 'Unable to load dashboard statistics. Please check the database table and column names.';
}

/* Prepare Chart Scale */
$maxMonthlyReviews = 1;

foreach ($monthlyReviews as $month) {
    if ($month['total'] > $maxMonthlyReviews) {
        $maxMonthlyReviews = $month['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Business Owner Dashboard | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f5f8f4;
            color: #263b32;
            font-family: Arial, sans-serif;
        }

        .dashboard-content {
            flex: 1;
            width: 100%;
            padding-top: 100px;
            padding-bottom: 45px;
        }

        .dashboard-container {
            width: min(1150px, 92%);
            margin: auto;
        }

        .dashboard-heading {
            margin-bottom: 30px;
        }

        .dashboard-heading h1 {
            color: #075b45;
            font-size: 30px;
            margin: 0 0 10px;
        }

        .dashboard-heading p {
            color: #687779;
            line-height: 1.7;
            margin: 0;
        }

        /* Statistics cards */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2eae3;
            border-radius: 17px;
            padding: 25px;
            box-shadow: 0 5px 18px rgba(30, 70, 48, 0.05);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            background: #e6f1e9;
            color: #075b45;
            font-size: 24px;
            margin-bottom: 18px;
        }

        .stat-card h3 {
            color: #687779;
            font-size: 15px;
            font-weight: normal;
            margin: 0 0 10px;
        }

        .stat-number {
            color: #075b45;
            font-size: 32px;
            font-weight: bold;
        }

        .stat-note {
            color: #89968e;
            font-size: 13px;
            margin-top: 8px;
        }

        /* Dashboard panels */

        .dashboard-panel {
            background: white;
            border: 1px solid #e2eae3;
            border-radius: 17px;
            padding: 27px;
            margin-bottom: 28px;
            box-shadow: 0 5px 18px rgba(30, 70, 48, 0.04);
        }

        .panel-heading {
            color: #075b45;
            font-size: 21px;
            margin: 0 0 8px;
        }

        .panel-description {
            color: #687779;
            font-size: 14px;
            line-height: 1.7;
            margin: 0 0 25px;
        }

        /* Monthly performance */

        .chart-list {
            display: grid;
            gap: 19px;
        }

        .chart-row {
            display: grid;
            grid-template-columns: 65px 1fr 35px;
            align-items: center;
            gap: 13px;
        }

        .chart-month {
            color: #52645a;
            font-size: 14px;
            font-weight: bold;
        }

        .chart-track {
            height: 13px;
            background: #edf2ed;
            border-radius: 20px;
            overflow: hidden;
        }

        .chart-bar {
            height: 100%;
            background: #075b45;
            border-radius: 20px;
            min-width: 0;
        }

        .chart-value {
            color: #075b45;
            font-size: 14px;
            font-weight: bold;
            text-align: right;
        }

        /* Recent reviews */

        .review-list {
            display: grid;
            gap: 15px;
        }

        .review-item {
            padding: 19px;
            background: #f7faf7;
            border: 1px solid #e8eee8;
            border-radius: 13px;
        }

        .review-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .review-business {
            color: #075b45;
            font-weight: bold;
            font-size: 15px;
        }

        .review-stars {
            color: #e6aa24;
            font-size: 17px;
            letter-spacing: 2px;
        }

        .review-comment {
            color: #596b61;
            line-height: 1.7;
            margin: 0 0 10px;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .review-date {
            color: #89968e;
            font-size: 12px;
        }

        .empty-message {
            text-align: center;
            color: #89968e;
            padding: 25px 10px;
            line-height: 1.7;
        }

        /* Tips */

        .tips-section {
            background: #eaf2eb;
            border-radius: 17px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .tips-section h2 {
            color: #075b45;
            margin: 0 0 20px;
            font-size: 21px;
        }

        .tips-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .tip-card {
            background: white;
            border-radius: 13px;
            padding: 20px;
        }

        .tip-card h3 {
            color: #075b45;
            font-size: 16px;
            margin: 0 0 10px;
        }

        .tip-card p {
            color: #687779;
            font-size: 14px;
            line-height: 1.7;
            margin: 0;
        }

        .dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .dashboard-action {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 25px;
            background: #075b45;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .dashboard-action:hover {
            background: #08775a;
        }

        .database-error {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 12px;
            background: #fff2ed;
            color: #a23d28;
            line-height: 1.7;
        }

        @media (max-width: 800px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .tips-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 550px) {
            .dashboard-content {
                padding-top: 85px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-panel {
                padding: 20px;
            }

            .dashboard-heading h1 {
                font-size: 25px;
            }

            .chart-row {
                grid-template-columns: 45px 1fr 25px;
                gap: 8px;
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

            <a href="business-owner-index.php">
                <span>⌂</span> Home
            </a>

            <a href="business-owner-dashboard.php">
                <span>⌂</span> Dashboard
            </a>

            <a href="business-owner-my-business.php">
                <span>▣</span> My Business
            </a>

            <a href="business-owner-edit.php">
                <span>✎</span> Edit Business Information
            </a>

            <a href="business-owner-photos.php">
                <span>▧</span> Manage Photos
            </a>

            <a href="business-owner-account.php">
                <span>♙</span> My Account
            </a>

            <a href="index.php">
                <span>⌂</span> Back to Home
            </a>

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
                <?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?>
            </a>

            <a href="business-owner-index.php" class="home-small-logo">
                <img src="images/logo.png" alt="Logo">
            </a>

        </div>

    </header>

    <!-- Dashboard Content -->
    <main class="dashboard-content">

        <div class="dashboard-container">

            <div class="dashboard-heading">
                <h1>Business Dashboard</h1>
                <p>
                    Welcome, <?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?>!
                    Here you can monitor your business performance and visitor feedback.
                </p>
            </div>

            <?php if ($dbError !== ''): ?>
                <div class="database-error">
                    <?php echo htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-icon">★</div>
                    <h3>Average Rating</h3>
                    <div class="stat-number">
                        <?php echo number_format($averageRating, 1); ?>
                        <span style="font-size:20px;">/ 5</span>
                    </div>
                    <div class="stat-note">Based on visitor ratings</div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">✎</div>
                    <h3>Total Reviews</h3>
                    <div class="stat-number">
                        <?php echo (int)$totalReviews; ?>
                    </div>
                    <div class="stat-note">Reviews received by your business</div>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">♡</div>
                    <h3>Total Favorites</h3>
                    <div class="stat-number">
                        <?php echo (int)$totalFavorites; ?>
                    </div>
                    <div class="stat-note">Visitors who saved your business</div>
                </article>

            </section>

            <!-- Monthly Performance -->
            <section class="dashboard-panel">

                <h2 class="panel-heading">Monthly Performance</h2>

                <p class="panel-description">
                    Number of reviews received during the last six months.
                </p>

                <div class="chart-list">

                    <?php foreach ($monthlyReviews as $month): ?>

                        <?php
                        $barWidth = ($month['total'] / $maxMonthlyReviews) * 100;
                        ?>

                        <div class="chart-row">

                            <span class="chart-month">
                                <?php echo htmlspecialchars($month['month']); ?>
                            </span>

                            <div class="chart-track">
                                <div class="chart-bar"
                                     style="width: <?php echo $barWidth; ?>%;">
                                </div>
                            </div>

                            <span class="chart-value">
                                <?php echo (int)$month['total']; ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

            <!-- Recent Reviews -->
            <section class="dashboard-panel">

                <h2 class="panel-heading">Recent Reviews</h2>

                <p class="panel-description">
                    See what visitors are saying about your business.
                </p>

                <?php if (count($recentReviews) > 0): ?>

                    <div class="review-list">

                        <?php foreach ($recentReviews as $review): ?>

                            <article class="review-item">

                                <div class="review-top">

                                    <span class="review-business">
                                        <?php echo htmlspecialchars($review['Name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                    <span class="review-stars">
                                        <?php
                                        $rating = max(0, min(5, (int)$review['Rating']));
                                        echo str_repeat('★', $rating);
                                        echo str_repeat('☆', 5 - $rating);
                                        ?>
                                    </span>

                                </div>

                                <p class="review-comment">
                                    <?php
                                    echo htmlspecialchars(
                                        $review['Comment'] ?? 'No comment provided.',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </p>

                                <div class="review-date">
                                    <?php
                                    echo htmlspecialchars(
                                        date('d M Y', strtotime($review['Create_at'])),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-message">
                        No reviews available yet. Your visitor reviews will appear here.
                    </div>

                <?php endif; ?>

            </section>

            <!-- Quick Actions -->
            <section class="dashboard-panel">

                <h2 class="panel-heading">Quick Actions</h2>

                <p class="panel-description">
                    Manage your business information and profile.
                </p>

                <div class="dashboard-actions">

                    <a href="business-owner-my-business.php"
                       class="dashboard-action">
                        View My Business
                    </a>

                    <a href="business-owner-edit.php"
                       class="dashboard-action">
                        Edit Business Information
                    </a>

                    <a href="business-owner-photos.php"
                       class="dashboard-action">
                        Manage Photos
                    </a>

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

    <!-- AI Assistant -->
    <button
        class="chat-button"
        onclick="openAI()"
        aria-label="Open AI Assistant"
        title="AI Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js"></script>

</body>
</html>