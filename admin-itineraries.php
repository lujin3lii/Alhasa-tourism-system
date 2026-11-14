
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
        $adminResult = $stmt->get_result();

        if ($row = $adminResult->fetch_assoc()) {
            $adminName = $row['Name'];
        }
    }

    $stmt->close();
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================
   1. Total Itineraries
========================= */

$totalItineraries = 0;

$countResult = $conn->query("
    SELECT COUNT(*) AS total
    FROM itinerary
");

if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    $totalItineraries = (int)($countRow['total'] ?? 0);
}

/* =========================
   2. Fetch Itineraries
========================= */

$sql = "
    SELECT
        i.itinerary_id,
        i.Days,
        t.Name AS tourist_name,
        places.selected_places,
        latest_review.Rating AS trip_rating,
        latest_review.Comment AS review_comment

    FROM itinerary i

    LEFT JOIN Tourist t
        ON i.Tourist_ID = t.Tourist_ID

    LEFT JOIN (
        SELECT
            ii.Itinerary_ID,

            GROUP_CONCAT(
                DISTINCT p.Name
                ORDER BY p.Name
                SEPARATOR '|||'
            ) AS selected_places

        FROM Itinerary_Items ii

        LEFT JOIN Places p
            ON ii.Place_ID = p.Place_ID

        GROUP BY ii.Itinerary_ID
    ) places
        ON places.Itinerary_ID = i.itinerary_id

    LEFT JOIN (
        SELECT
            r1.Itinerary_ID,
            r1.Rating,
            r1.Comment

        FROM itinerary_reviews r1

        INNER JOIN (
            SELECT
                Itinerary_ID,
                MAX(Itinerary_Review_ID) AS latest_review_id

            FROM itinerary_reviews
            GROUP BY Itinerary_ID
        ) r2
            ON r1.Itinerary_Review_ID = r2.latest_review_id

    ) latest_review
        ON latest_review.Itinerary_ID = i.itinerary_id

    ORDER BY i.itinerary_id DESC
";

$result = $conn->query($sql);

if (!$result) {
    error_log("Itineraries query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Itineraries Management | Al-Ahsa Vision Hub</title>

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 28px;
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

        /* Total Itineraries Card - Same as Tourists Page */

        .tourists-total-card {
            background: #fff;
            padding: 20px 25px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            min-width: 180px;
        }

        .tourists-total-card span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .tourists-total-card strong {
            color: #075b45;
            font-size: 27px;
        }

        /* Statistics Cards */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-bottom: 28px;
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

        /* Itineraries Panel */

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

        /* Itineraries Table */

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .itineraries-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .itineraries-table th {
            background: #f7faf7;
            color: #075b45;
            font-size: 13px;
            font-weight: bold;
            padding: 16px 14px;
            text-align: left;
            border-bottom: 1px solid #e2eae3;
            white-space: nowrap;
        }

        .itineraries-table td {
            padding: 17px 14px;
            color: #596b61;
            font-size: 13px;
            line-height: 1.7;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #edf1ed;
        }

        .itineraries-table tbody tr:hover {
            background: #fafcf9;
        }

        .places-list {
            min-width: 150px;
        }

        .place-name {
            margin-bottom: 4px;
        }

        /* Rating Stars */

        .rating-stars {
            color: #e6aa24;
            font-size: 19px;
            letter-spacing: 2px;
            white-space: nowrap;
        }

        .rating-number {
            display: block;
            color: #89968e;
            font-size: 12px;
            margin-top: 4px;
        }

        /* Review Comment */

        .review-comment {
            min-width: 180px;
            max-width: 260px;
            overflow-wrap: anywhere;
            line-height: 1.7;
        }

        .no-data {
            color: #89968e;
            font-size: 13px;
        }

        .empty-message {
            text-align: center;
            color: #89968e;
            padding: 30px 10px;
            line-height: 1.7;
        }

        /* Responsive */

        @media (max-width: 900px) {
            .dashboard-container {
                width: 94%;
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

            <a href="admin-reviews.php">
                <span>☆</span> Reviews
            </a>

            <a href="admin-itineraries.php" class="active">
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

    <!-- Itineraries Content -->

    <main class="dashboard-content">

        <div class="dashboard-container">

            <div class="dashboard-heading">

                <div>
                    <h1>Itineraries Management</h1>

                    <p>
                        View and monitor the travel plans created by tourists.
                    </p>
                </div>

                <!-- Total Itineraries - Same Design as Tourists Page -->

                <div class="tourists-total-card">
                    <span>Total Itineraries</span>
                    <strong><?= e($totalItineraries) ?></strong>
                </div>

            </div>

            <!-- Itineraries Table -->

            <section class="dashboard-panel">

                <h2 class="panel-heading">
                    All Itineraries
                </h2>

                <p class="panel-description">
                    View itinerary owners, selected places, and their latest reviews.
                </p>

                <div class="table-wrapper">

                    <table class="itineraries-table">

                        <thead>
                            <tr>
                                <th>Trip Owner</th>
                                <th>Number of Days</th>
                                <th>Selected Places</th>
                                <th>Trip Rating</th>
                                <th>Review Comment</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($result && $result->num_rows > 0): ?>

                            <?php while ($row = $result->fetch_assoc()): ?>

                                <?php
                                $placesList = !empty($row['selected_places'])
                                    ? explode('|||', $row['selected_places'])
                                    : [];

                                $rating = $row['trip_rating'] !== null
                                    ? max(0, min(5, (int)$row['trip_rating']))
                                    : null;
                                ?>

                                <tr>

                                    <!-- Trip Owner -->

                                    <td>
                                        <?= e($row['tourist_name'] ?? 'Unknown Tourist') ?>
                                    </td>

                                    <!-- Number of Days -->

                                    <td>
                                        <?= e($row['Days'] ?? '—') ?> Days
                                    </td>

                                    <!-- Selected Places -->

                                    <td class="places-list">

                                        <?php if (!empty($placesList)): ?>

                                            <?php foreach ($placesList as $place): ?>

                                                <div class="place-name">
                                                    <?= e($place) ?>
                                                </div>

                                            <?php endforeach; ?>

                                        <?php else: ?>

                                            <span class="no-data">
                                                No places selected
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- Latest Trip Rating -->

                                    <td>

                                        <?php if ($rating !== null): ?>

                                            <div class="rating-stars">

                                                <?php
                                                echo str_repeat('★', $rating);
                                                echo str_repeat('☆', 5 - $rating);
                                                ?>

                                            </div>

                                            <span class="rating-number">
                                                <?= $rating ?>/5
                                            </span>

                                        <?php else: ?>

                                            <span class="no-data">
                                                Not rated
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <!-- Latest Review Comment -->

                                    <td class="review-comment">

                                        <?php if (!empty($row['review_comment'])): ?>

                                            <?= nl2br(e($row['review_comment'])) ?>

                                        <?php else: ?>

                                            <span class="no-data">
                                                No comment
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="empty-message">
                                    No itineraries available yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

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
