
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

// Fetch Tourists with Statistics and Accessibility Options
$sql = "
    SELECT
        t.Tourist_ID,
        t.Name,
        t.Email,
        t.Create_at,

        COALESCE(f.favorite_count, 0) AS favorite_count,
        COALESCE(r.review_count, 0) AS review_count,
        r.average_rating,
        COALESCE(i.itinerary_count, 0) AS itinerary_count,

        GROUP_CONCAT(
            DISTINCT ao.Accessibility_Name
            ORDER BY ao.Accessibility_Name
            SEPARATOR ', '
        ) AS accessibility_options

    FROM Tourist t

    LEFT JOIN (
        SELECT Tourist_ID, COUNT(*) AS favorite_count
        FROM favorites
        GROUP BY Tourist_ID
    ) f ON t.Tourist_ID = f.Tourist_ID

    LEFT JOIN (
        SELECT
            Tourist_ID,
            COUNT(*) AS review_count,
            AVG(Rating) AS average_rating
        FROM Place_Reviews
        GROUP BY Tourist_ID
    ) r ON t.Tourist_ID = r.Tourist_ID

    LEFT JOIN (
        SELECT Tourist_ID, COUNT(*) AS itinerary_count
        FROM itinerary
        GROUP BY Tourist_ID
    ) i ON t.Tourist_ID = i.Tourist_ID

    LEFT JOIN tourist_accessibility ta
        ON t.Tourist_ID = ta.Tourist_ID

    LEFT JOIN accessibility_options ao
        ON ta.Accessibility_ID = ao.Accessibility_ID

    GROUP BY
        t.Tourist_ID,
        t.Name,
        t.Email,
        t.Create_at,
        f.favorite_count,
        r.review_count,
        r.average_rating,
        i.itinerary_count

    ORDER BY t.Create_at DESC
";

$tourists_query = $conn->query($sql);

if (!$tourists_query) {
    die("Database query failed: " . htmlspecialchars($conn->error));
}

// Total Tourists
$totalTourists = $conn->query("
    SELECT COUNT(*) AS total
    FROM Tourist
")->fetch_assoc()['total'] ?? 0;

// Escape Output
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Tourists - Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>
        body {
            background: #f4f7f6;
        }

        .tourists-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 35px 30px;
        }

        .tourists-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 28px;
        }

        .tourists-heading h1 {
            color: #075b45;
            margin: 0 0 8px;
            font-size: 28px;
        }

        .tourists-heading p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }

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

        .tourists-table-card {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .tourists-table-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .tourists-table-heading h2 {
            color: #174c3a;
            font-size: 18px;
            margin: 0;
        }

        .tourists-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .tourists-table {
            width: 100%;
            min-width: 1150px;
            border-collapse: collapse;
            text-align: left;
        }

        .tourists-table th {
            padding: 16px 13px;
            color: #777;
            font-size: 13px;
            font-weight: 600;
            background: #f8faf9;
            border-bottom: 2px solid #edf1ef;
            white-space: nowrap;
        }

        .tourists-table td {
            padding: 17px 13px;
            color: #555;
            font-size: 13px;
            border-bottom: 1px solid #f0f2f1;
            vertical-align: middle;
        }

        .tourists-table tbody tr:hover {
            background: #fafcfb;
        }

        .tourist-name {
            color: #174c3a;
            font-weight: 600;
        }

        .tourist-stat {
            color: #075b45;
            font-weight: 600;
        }

        .rating-cell {
            white-space: nowrap;
        }

        .rating-stars {
            color: #e5a900;
            letter-spacing: 1px;
            font-size: 15px;
        }

        .rating-number {
            color: #555;
            font-size: 12px;
            margin-left: 4px;
        }

        .no-rating {
            color: #999;
        }

        .accessibility-tags {
            color: #427b68;
            line-height: 1.7;
            max-width: 230px;
        }

        .no-accessibility {
            color: #999;
        }

        .no-tourists {
            text-align: center;
            padding: 35px !important;
            color: #999 !important;
        }

        @media (max-width: 768px) {
            .tourists-content {
                padding: 25px 15px;
            }

            .tourists-heading h1 {
                font-size: 23px;
            }

            .tourists-table-card {
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

    <div class="sidebar-backdrop" onclick="toggleMenu()"></div>

    
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

        <a href="admin-tourists.php" class="active">
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
        <div class="tourists-content">

            <div class="tourists-heading">

                <div>
                    <h1>Tourist Management</h1>
                    <p>View registered tourists and their activity on the platform.</p>
                </div>

                <div class="tourists-total-card">
                    <span>Total Registered Tourists</span>
                    <strong><?= e($totalTourists) ?></strong>
                </div>

            </div>

            <!-- Tourists Table -->
            <section class="tourists-table-card">

                <div class="tourists-table-heading">
                    <h2>Registered Tourists</h2>
                </div>

                <div class="tourists-table-wrapper">

                    <table class="tourists-table">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Favorites</th>
                                <th>Reviews</th>
                                <th>Average Rating Given</th>
                                <th>Itineraries</th>
                                <th>Accessibility Options</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if ($tourists_query->num_rows > 0): ?>

                            <?php while ($row = $tourists_query->fetch_assoc()): ?>

                                <tr>

                                    <td><?= e($row['Tourist_ID']) ?></td>

                                    <td class="tourist-name">
                                        <?= e($row['Name']) ?>
                                    </td>

                                    <td><?= e($row['Email']) ?></td>

                                    <td><?= e($row['Create_at']) ?></td>

                                    <td class="tourist-stat">
                                        <?= e($row['favorite_count']) ?>
                                    </td>

                                    <td class="tourist-stat">
                                        <?= e($row['review_count']) ?>
                                    </td>

                                    <!-- Average Rating -->
                                    <td class="rating-cell">

                                        <?php if ($row['average_rating'] !== null): ?>

                                            <?php
                                            $averageRating = round(
                                                (float)$row['average_rating'],
                                                1
                                            );

                                            $fullStars = (int)round($averageRating);
                                            ?>

                                            <span class="rating-stars">
                                                <?php
                                                for ($star = 1; $star <= 5; $star++) {
                                                    echo $star <= $fullStars ? '★' : '☆';
                                                }
                                                ?>
                                            </span>

                                            <span class="rating-number">
                                                <?= e(number_format($averageRating, 1)) ?>/5
                                            </span>

                                        <?php else: ?>

                                            <span class="no-rating">
                                                No ratings yet
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td class="tourist-stat">
                                        <?= e($row['itinerary_count']) ?>
                                    </td>

                                    <!-- Accessibility Options -->
                                    <td>

                                        <?php if (!empty($row['accessibility_options'])): ?>

                                            <span class="accessibility-tags">
                                                <?= e($row['accessibility_options']) ?>
                                            </span>

                                        <?php else: ?>

                                            <span class="no-accessibility">
                                                None selected
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="no-tourists">
                                    No tourists found.
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
