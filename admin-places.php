
<?php
session_start();
require_once 'db.php';

/* Admin Protection */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminID = (int) $_SESSION['admin_id'];

/* Get Admin Name */
$stmt = $conn->prepare("
    SELECT Name
    FROM Admin
    WHERE Admin_ID = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $adminID);

    if ($stmt->execute()) {
        $resultAdmin = $stmt->get_result();

        if ($row = $resultAdmin->fetch_assoc()) {
            $adminName = $row['Name'];
        }
    }

    $stmt->close();
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Fetch Places with Average Rating and Total Reviews */
$sql = "
    SELECT
        p.Place_ID,
        p.Name,
        p.Place_Type,
        p.Location,
        p.Opening_Hours,
        COALESCE(r.total_reviews, 0) AS total_reviews,
        COALESCE(r.average_rating, 0) AS average_rating
    FROM Places p
    LEFT JOIN (
        SELECT
            Place_ID,
            COUNT(Place_Review_id) AS total_reviews,
            AVG(Rating) AS average_rating
        FROM Place_Reviews
        GROUP BY Place_ID
    ) r ON p.Place_ID = r.Place_ID
    ORDER BY p.Place_ID DESC
";

$result = $conn->query($sql);

if (!$result) {
    die("Places query error: " . $conn->error);
}

$totalPlaces = $result->num_rows;

/* Place Type Labels */
function getPlaceTypeLabel($type) {
    $types = [
        'entertainment' => 'Entertainment',
        'restaurants' => 'Restaurants',
        'cafes' => 'Cafes',
        'markets' => 'Markets',
        'seasonal events' => 'Seasonal Events'
    ];

    $key = strtolower(trim($type));
    return $types[$key] ?? $type;
}

/* Location Display */
function getLocationDisplay($location) {
    if (empty($location)) {
        return '<span class="no-data">Not available</span>';
    }

    $safeLocation = e($location);

    if (filter_var($location, FILTER_VALIDATE_URL)) {
        $parsedUrl = parse_url($location);

        if (
            isset($parsedUrl['scheme']) &&
            in_array(strtolower($parsedUrl['scheme']), ['http', 'https'])
        ) {
            return '<a class="location-link" href="' . $safeLocation . '" target="_blank" rel="noopener noreferrer">View Location ↗</a>';
        }
    }

    return $safeLocation;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Places | Admin Dashboard</title>

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

        /* Places Content */

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

        /* Total Places Card - Same as Tourists Page */

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

        /* Places Table Panel */

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

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .places-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .places-table th {
            background: #f5f8f4;
            color: #52675d;
            font-size: 13px;
            font-weight: bold;
            text-align: left;
            padding: 16px 14px;
            white-space: nowrap;
        }

        .places-table td {
            padding: 17px 14px;
            border-bottom: 1px solid #e8eee9;
            color: #596b61;
            font-size: 13px;
            vertical-align: middle;
            line-height: 1.6;
        }

        .places-table tr:last-child td {
            border-bottom: none;
        }

        .place-name {
            color: #075b45 !important;
            font-weight: bold;
            min-width: 140px;
        }

        .type-badge {
            display: inline-block;
            background: #e6f1e9;
            color: #075b45;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            white-space: nowrap;
        }

        .location-link {
            color: #075b45;
            text-decoration: none;
            font-weight: bold;
            white-space: nowrap;
        }

        .location-link:hover {
            text-decoration: underline;
        }

        .no-data {
            color: #a0aaa4;
        }

        .rating-cell {
            white-space: nowrap;
        }

        .rating-stars {
            color: #e6aa24;
            font-size: 16px;
            letter-spacing: 1px;
        }

        .rating-number {
            color: #596b61;
            font-size: 12px;
            font-weight: bold;
            margin-left: 4px;
        }

        .review-count {
            color: #075b45;
            font-weight: bold;
            text-align: center;
        }

        .empty-message {
            text-align: center;
            color: #89968e;
            padding: 30px 10px;
            line-height: 1.7;
        }

        /* Responsive */

        @media (max-width: 900px) {
            .table-wrapper {
                overflow-x: auto;
            }
        }

        @media (max-width: 550px) {
            .dashboard-content {
                padding-top: 85px;
            }

            .dashboard-heading h1 {
                font-size: 25px;
            }

            .dashboard-panel {
                padding: 20px;
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

            <a href="admin-places.php" class="active">
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

    <!-- Places Content -->
    <main class="dashboard-content">

        <div class="dashboard-container">

            <div class="dashboard-heading">

                <div>
                    <h1>Places Management</h1>

                    <p>
                        View and monitor all places registered in Al-Ahsa Vision Hub.
                    </p>
                </div>

                <!-- Total Places - Same Design as Tourists Page -->

                <div class="tourists-total-card">
                    <span>Total Places</span>
                    <strong><?= e($totalPlaces) ?></strong>
                </div>

            </div>

            <!-- All Places -->
            <section class="dashboard-panel">

                <h2 class="panel-heading">All Places</h2>

                <p class="panel-description">
                    Place information, opening hours, and tourist ratings.
                </p>

                <div class="table-wrapper">

                    <table class="places-table">

                        <thead>
                            <tr>
                                <th>Place Name</th>
                                <th>Place Type</th>
                                <th>Location</th>
                                <th>Opening Hours</th>
                                <th>Average Rating</th>
                                <th>Total Reviews</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($result->num_rows > 0): ?>

                                <?php while ($place = $result->fetch_assoc()): ?>

                                    <?php
                                        $averageRating = (float) $place['average_rating'];
                                        $roundedRating = max(0, min(5, (int) round($averageRating)));

                                        $stars = str_repeat('★', $roundedRating)
                                               . str_repeat('☆', 5 - $roundedRating);
                                    ?>

                                    <tr>

                                        <td class="place-name">
                                            <?= e($place['Name']) ?>
                                        </td>

                                        <td>
                                            <span class="type-badge">
                                                <?= e(getPlaceTypeLabel($place['Place_Type'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= getLocationDisplay($place['Location']) ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($place['Opening_Hours'])): ?>
                                                <?= e($place['Opening_Hours']) ?>
                                            <?php else: ?>
                                                <span class="no-data">Not available</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="rating-cell">

                                            <span class="rating-stars">
                                                <?= $stars ?>
                                            </span>

                                            <span class="rating-number">
                                                <?php
                                                    echo $averageRating > 0
                                                        ? number_format($averageRating, 1) . ' / 5'
                                                        : 'No ratings';
                                                ?>
                                            </span>

                                        </td>

                                        <td class="review-count">
                                            <?= (int) $place['total_reviews'] ?>
                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" class="empty-message">
                                        No places available yet.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </section>

        </div>

    </main>

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
<script src="script.js?v=2"></script>
</body>
</html>
