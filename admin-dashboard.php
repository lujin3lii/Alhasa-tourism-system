<?php
session_start();
require_once 'db.php';

/* حماية صفحة الأدمن */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

/* جلب اسم الأدمن من قاعدة البيانات */
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminID = (int) $_SESSION['admin_id'];

$stmt = $conn->prepare("
    SELECT Name
    FROM Admin
    WHERE Admin_ID = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $adminID);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $adminName = $row['Name'];
        }
    }

    $stmt->close();
}

function getCount($conn, $sql) {
    $result = $conn->query($sql);

    if (!$result) {
        error_log("Dashboard query error: " . $conn->error);
        return 0;
    }

    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

function getRows($conn, $sql) {
    $result = $conn->query($sql);

    if (!$result) {
        error_log("Dashboard query error: " . $conn->error);
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   1. Statistics Cards
========================= */

$totalVisits = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM site_visits
");

$guestVisits = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM site_visits
    WHERE Tourist_ID IS NULL
");

$registeredVisits = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM site_visits
    WHERE Tourist_ID IS NOT NULL
");

$totalTourists = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM Tourist
");

$totalOwners = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM business_owners
");

$totalPlaces = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM Places
");

$placeReviewsCount = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM Place_Reviews
");

$itineraryReviewsCount = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM itinerary_reviews
");

$systemReviewsCount = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM system_reviews
");

$totalReviews = $placeReviewsCount
              + $itineraryReviewsCount
              + $systemReviewsCount;

$totalItineraries = getCount($conn, "
    SELECT COUNT(*) AS total
    FROM itinerary
");

/* =========================
   2. Monthly Site Visits
========================= */

$monthlyVisits = [];

for ($i = 5; $i >= 0; $i--) {
    $monthDate = new DateTime("first day of -$i month");

    $monthKey = $monthDate->format('Y-m');
    $monthLabel = $monthDate->format('M Y');

    $monthlyVisits[$monthKey] = [
        'label' => $monthLabel,
        'total' => 0
    ];
}

$monthlyResult = getRows($conn, "
    SELECT
        DATE_FORMAT(Visit_Date, '%Y-%m') AS visit_month,
        COUNT(*) AS total
    FROM site_visits
    WHERE Visit_Date >= DATE_FORMAT(
        DATE_SUB(CURDATE(), INTERVAL 5 MONTH),
        '%Y-%m-01'
    )
    GROUP BY DATE_FORMAT(Visit_Date, '%Y-%m')
    ORDER BY visit_month ASC
");

foreach ($monthlyResult as $row) {
    $monthKey = $row['visit_month'];

    if (isset($monthlyVisits[$monthKey])) {
        $monthlyVisits[$monthKey]['total'] = (int)$row['total'];
    }
}

$maxMonthlyVisits = 1;

foreach ($monthlyVisits as $month) {
    if ($month['total'] > $maxMonthlyVisits) {
        $maxMonthlyVisits = $month['total'];
    }
}

/* =========================
   3. Recent Reviews
========================= */

$recentReviews = [];

/* تقييمات الأماكن */

$placeReviews = getRows($conn, "
    SELECT
        pr.Rating,
        pr.Comment,
        pr.Create_at AS review_date,
        t.Name AS tourist_name,
        p.Name AS item_name
    FROM Place_Reviews pr
    LEFT JOIN Tourist t
        ON pr.Tourist_ID = t.Tourist_ID
    LEFT JOIN Places p
        ON pr.Place_ID = p.Place_ID
    ORDER BY pr.Create_at DESC
    LIMIT 5
");

foreach ($placeReviews as $review) {
    $review['review_type'] = 'Place Review';
    $recentReviews[] = $review;
}

/* تقييمات الرحلات */

$itineraryReviews = getRows($conn, "
    SELECT
        ir.Rating,
        ir.Comment,
        ir.Created_at AS review_date,
        t.Name AS tourist_name,
        CONCAT('Itinerary #', ir.Itinerary_ID) AS item_name
    FROM itinerary_reviews ir
    LEFT JOIN Tourist t
        ON ir.Tourist_ID = t.Tourist_ID
    ORDER BY ir.Created_at DESC
    LIMIT 5
");

foreach ($itineraryReviews as $review) {
    $review['review_type'] = 'Itinerary Review';
    $recentReviews[] = $review;
}

/* تقييمات النظام / التجربة */

$systemReviews = getRows($conn, "
    SELECT
        sr.Rating,
        sr.Comment,
        sr.Created_at AS review_date,
        t.Name AS tourist_name,
        'System Experience' AS item_name
    FROM system_reviews sr
    LEFT JOIN Tourist t
        ON sr.Tourist_ID = t.Tourist_ID
    ORDER BY sr.Created_at DESC
    LIMIT 5
");

foreach ($systemReviews as $review) {
    $review['review_type'] = 'System Review';
    $recentReviews[] = $review;
}

/* ترتيب التقييمات من الأحدث إلى الأقدم */

usort($recentReviews, function ($a, $b) {
    return strtotime($b['review_date'] ?? '')
         <=> strtotime($a['review_date'] ?? '');
});

$recentReviews = array_slice($recentReviews, 0, 5);

/* =========================
   4. Recent Itineraries
========================= */

$recentItineraries = getRows($conn, "
    SELECT
        i.itinerary_id,
        i.Create_at,
        t.Name AS tourist_name
    FROM itinerary i
    LEFT JOIN Tourist t
        ON i.Tourist_ID = t.Tourist_ID
    ORDER BY i.Create_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Al-Ahsa Vision Hub</title>

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

        /* Dashboard Content */

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

        /* Statistics Cards */

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

        .stat-details {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 13px;
            font-size: 12px;
            color: #718078;
        }

        /* Dashboard Panels */

        .dashboard-panel {
            background: white;
            border: 1px solid #e2eae3;
            border-radius: 17px;
            padding: 27px;
            margin-bottom: 28px;
            box-shadow: 0 5px 18px rgba(30, 70, 48, 0.04);
            min-width: 0;
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

        /* Monthly Site Visits Chart */

        .chart {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 12px;
            height: 205px;
            padding-top: 15px;
            border-bottom: 1px solid #e8eee9;
        }

        .chart-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            min-width: 0;
        }

        .chart-value {
            font-size: 11px;
            color: #718078;
            margin-bottom: 6px;
        }

        .chart-bar {
            width: 65%;
            max-width: 42px;
            min-height: 3px;
            background: linear-gradient(to top, #075b45, #70b99a);
            border-radius: 7px 7px 0 0;
        }

        .chart-label {
            font-size: 11px;
            color: #718078;
            margin-top: 9px;
            white-space: nowrap;
        }

        /* Reviews and Itineraries */

        .review-list,
        .itinerary-list {
            display: grid;
            gap: 15px;
        }

        .review-item,
        .itinerary-item {
            padding: 19px;
            background: #f7faf7;
            border: 1px solid #e8eee8;
            border-radius: 13px;
        }

        .item-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .item-title {
            color: #075b45;
            font-weight: bold;
            font-size: 15px;
        }

        .item-type {
            color: #89968e;
            font-size: 12px;
        }

        .item-date {
            color: #89968e;
            font-size: 12px;
            margin-top: 10px;
        }

        .review-stars {
            color: #e6aa24;
            font-size: 17px;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .review-comment {
            color: #596b61;
            line-height: 1.7;
            margin: 0;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .empty-message {
            text-align: center;
            color: #89968e;
            padding: 25px 10px;
            line-height: 1.7;
        }

        /* Dashboard Grid */

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            align-items: start;
        }

        /* Quick Actions */

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .quick-action {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 25px;
            background: #075b45;
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            transition: 0.2s;
        }

        .quick-action:hover {
            background: #08775a;
        }

        /* Responsive */

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
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

            .chart {
                gap: 5px;
            }

            .chart-label {
                font-size: 9px;
            }

            .chart-value {
                font-size: 10px;
            }

            .quick-actions {
                flex-direction: column;
            }

            .quick-action {
                text-align: center;
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

            <a href="admin-dashboard.php" class="active">
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

            <a href="admin-reviews.php">
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

    <!-- Top Bar -->

    <header class="home-topbar">

        <div class="home-location">
            <span class="location-dot"></span>
            DISCOVER AL-AHSA, SAUDI ARABIA
        </div>

        <div class="home-account-links">

            <a href="admin-dashboard.php"
               class="home-signup"
               id="account-button">
                <?= e($adminName) ?>
            </a>

            <a href="admin-dashboard.php" class="home-small-logo">
                <img src="images/logo.png" alt="Logo">
            </a>

        </div>

    </header>

    <!-- Dashboard Content -->

    <main class="dashboard-content">

        <div class="dashboard-container">

            <div class="dashboard-heading">

                <h1>Admin Dashboard</h1>

                <p>
                    Welcome, <?= e($adminName) ?>!
                    Monitor and manage Al-Ahsa Vision Hub.
                </p>

            </div>

            <!-- Statistics -->

            <section class="stats-grid">

                <article class="stat-card">

                    <div class="stat-icon">◉</div>

                    <h3>Total Site Visits</h3>

                    <div class="stat-number">
                        <?= $totalVisits ?>
                    </div>

                    <div class="stat-details">
                        <span>Guests: <?= $guestVisits ?></span>
                        <span>Registered: <?= $registeredVisits ?></span>
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">♙</div>

                    <h3>Total Tourists</h3>

                    <div class="stat-number">
                        <?= $totalTourists ?>
                    </div>

                    <div class="stat-note">
                        Registered tourists
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">▣</div>

                    <h3>Business Owners</h3>

                    <div class="stat-number">
                        <?= $totalOwners ?>
                    </div>

                    <div class="stat-note">
                        Registered business owners
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">⌖</div>

                    <h3>Total Places</h3>

                    <div class="stat-number">
                        <?= $totalPlaces ?>
                    </div>

                    <div class="stat-note">
                        Places in the system
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">☆</div>

                    <h3>Total Reviews</h3>

                    <div class="stat-number">
                        <?= $totalReviews ?>
                    </div>

                    <div class="stat-note">
                        Reviews from all sections
                    </div>

                </article>

                <article class="stat-card">

                    <div class="stat-icon">▤</div>

                    <h3>Total Itineraries</h3>

                    <div class="stat-number">
                        <?= $totalItineraries ?>
                    </div>

                    <div class="stat-note">
                        Created itineraries
                    </div>

                </article>

            </section>

            <!-- Monthly Visits and Recent Reviews -->

            <section class="dashboard-grid">

                <!-- Monthly Site Visits -->

                <div class="dashboard-panel">

                    <h2 class="panel-heading">
                        Monthly Site Visits
                    </h2>

                    <p class="panel-description">
                        Website visits during the last six months.
                    </p>

                    <div class="chart">

                        <?php foreach ($monthlyVisits as $month): ?>

                            <?php
                                $barHeight = ($month['total'] / $maxMonthlyVisits) * 150;
                            ?>

                            <div class="chart-column">

                                <span class="chart-value">
                                    <?= $month['total'] ?>
                                </span>

                                <div
                                    class="chart-bar"
                                    style="height: <?= $barHeight ?>px;"
                                    title="<?= e($month['label']) ?>: <?= $month['total'] ?> visits">
                                </div>

                                <span class="chart-label">
                                    <?= e($month['label']) ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                </div>

                <!-- Recent Reviews -->

                <div class="dashboard-panel">

                    <h2 class="panel-heading">
                        Recent Reviews
                    </h2>

                    <p class="panel-description">
                        Latest feedback submitted by tourists.
                    </p>

                    <?php if (empty($recentReviews)): ?>

                        <div class="empty-message">
                            No reviews available yet.
                        </div>

                    <?php else: ?>

                        <div class="review-list">

                            <?php foreach ($recentReviews as $review): ?>

                                <article class="review-item">

                                    <div class="item-top">

                                        <span class="item-title">
                                            <?= e($review['item_name'] ?? 'Review') ?>
                                        </span>

                                        <span class="item-type">
                                            <?= e($review['review_type']) ?>
                                        </span>

                                    </div>

                                    <div class="item-type">
                                        By <?= e($review['tourist_name'] ?? 'Unknown Tourist') ?>
                                    </div>

                                    <div class="review-stars">

                                        <?php
                                            $rating = max(
                                                0,
                                                min(5, (int)($review['Rating'] ?? 0))
                                            );

                                            echo str_repeat('★', $rating);
                                            echo str_repeat('☆', 5 - $rating);
                                        ?>

                                    </div>

                                    <p class="review-comment">
                                        <?= e($review['Comment'] ?? 'No comment') ?>
                                    </p>

                                    <div class="item-date">
                                        <?= e($review['review_date'] ?? '') ?>
                                    </div>

                                </article>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>

            </section>

            <!-- Recent Itineraries -->

            <section class="dashboard-panel">

                <h2 class="panel-heading">
                    Recent Itineraries
                </h2>

                <p class="panel-description">
                    Latest itineraries created by tourists.
                </p>

                <?php if (empty($recentItineraries)): ?>

                    <div class="empty-message">
                        No itineraries available yet.
                    </div>

                <?php else: ?>

                    <div class="itinerary-list">

                        <?php foreach ($recentItineraries as $itinerary): ?>

                            <article class="itinerary-item">

                                <div class="item-top">

                                    <span class="item-title">
                                        Itinerary #<?= e($itinerary['itinerary_id']) ?>
                                    </span>

                                    <span class="item-date">
                                        <?= e($itinerary['Create_at']) ?>
                                    </span>

                                </div>

                                <div class="item-type">
                                    Tourist:
                                    <?= e($itinerary['tourist_name'] ?? 'Unknown Tourist') ?>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- Quick Actions -->

            <section class="dashboard-panel">

                <h2 class="panel-heading">
                    Quick Actions
                </h2>

                <p class="panel-description">
                    Manage the main sections of your system.
                </p>

                <div class="quick-actions">

                    <a href="admin-tourists.php" class="quick-action">
                        Manage Tourists
                    </a>

                    <a href="admin-business-owners.php" class="quick-action">
                        Manage Business Owners
                    </a>

                    <a href="admin-places.php" class="quick-action">
                        Manage Places
                    </a>

                    <a href="admin-reviews.php" class="quick-action">
                        Manage Reviews
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
            <p>
                ©️ 2026 Al-Ahsa Vision Hub. All rights reserved.
            </p>

            <p>
                Explore Al-Ahsa, experience its beauty.
            </p>
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