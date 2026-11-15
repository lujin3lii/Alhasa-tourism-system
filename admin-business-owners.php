
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

// Fetch Business Owners and Their Businesses
$sql = "
    SELECT
        bo.Business_Owner_ID,
        bo.Name AS Owner_Name,
        bo.Phone,
        p.Name AS Business_Name,
        p.Place_Type,
        p.Business_Size

    FROM business_owners bo

    LEFT JOIN Places p
        ON bo.Business_Owner_ID = p.Business_Owner_ID

    ORDER BY bo.Business_Owner_ID DESC
";

$owners_query = $conn->query($sql);

if (!$owners_query) {
    die("Database query failed: " . htmlspecialchars($conn->error));
}

// Total Business Owners
$totalOwners = $conn->query("
    SELECT COUNT(*) AS total
    FROM business_owners
")->fetch_assoc()['total'] ?? 0;

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

    <title>Manage Business Owners - Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        body {
            background: #f4f7f6;
        }

        .owners-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 35px 30px;
        }

        .owners-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 28px;
        }

        .owners-heading h1 {
            color: #075b45;
            margin: 0 0 8px;
            font-size: 28px;
        }

        .owners-heading p {
            color: #777;
            margin: 0;
            font-size: 14px;
        }

        .owners-total-card {
            background: #fff;
            padding: 20px 25px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            min-width: 180px;
        }

        .owners-total-card span {
            display: block;
            color: #777;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .owners-total-card strong {
            color: #075b45;
            font-size: 27px;
        }

        .owners-table-card {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .owners-table-heading {
            margin-bottom: 20px;
        }

        .owners-table-heading h2 {
            color: #174c3a;
            font-size: 18px;
            margin: 0;
        }

        .owners-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .owners-table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
            text-align: left;
        }

        .owners-table th {
            padding: 16px 14px;
            color: #777;
            font-size: 13px;
            font-weight: 600;
            background: #f8faf9;
            border-bottom: 2px solid #edf1ef;
            white-space: nowrap;
        }

        .owners-table td {
            padding: 17px 14px;
            color: #555;
            font-size: 13px;
            border-bottom: 1px solid #f0f2f1;
            vertical-align: middle;
        }

        .owners-table tbody tr:hover {
            background: #fafcfb;
        }

        .owner-name {
            color: #174c3a;
            font-weight: 600;
        }

        .owner-phone {
            color: #555;
            white-space: nowrap;
        }

        .business-name {
            color: #174c3a;
            font-weight: 500;
        }

        .business-type,
        .business-size {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #edf5f1;
            color: #075b45;
            white-space: nowrap;
        }

        .no-business {
            color: #999;
            font-style: italic;
        }

        .no-owners {
            text-align: center;
            padding: 35px !important;
            color: #999 !important;
        }

        @media (max-width: 768px) {

            .owners-content {
                padding: 25px 15px;
            }

            .owners-heading h1 {
                font-size: 23px;
            }

            .owners-table-card {
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

            <a href="admin-business-owners.php" class="active">
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
        <div class="owners-content">

            <div class="owners-heading">

                <div>
                    <h1>Business Owners Management</h1>

                    <p>
                        View registered business owners and their business information.
                    </p>
                </div>

                <div class="owners-total-card">

                    <span>Total Business Owners</span>

                    <strong>
                        <?= e($totalOwners) ?>
                    </strong>

                </div>

            </div>

            <!-- Business Owners Table -->
            <section class="owners-table-card">

                <div class="owners-table-heading">
                    <h2>Registered Business Owners</h2>
                </div>

                <div class="owners-table-wrapper">

                    <table class="owners-table">

                        <thead>

                            <tr>
                                <th>Owner Name</th>
                                <th>Phone Number</th>
                                <th>Business Name</th>
                                <th>Business Type</th>
                                <th>Business Size</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($owners_query->num_rows > 0): ?>

                                <?php while ($row = $owners_query->fetch_assoc()): ?>

                                    <tr>

                                        <td class="owner-name">
                                            <?= e($row['Owner_Name']) ?>
                                        </td>

                                        <td class="owner-phone">
                                            <?= !empty($row['Phone'])
                                                ? e($row['Phone'])
                                                : 'Not provided' ?>
                                        </td>

                                        <td class="business-name">

                                            <?php if (!empty($row['Business_Name'])): ?>

                                                <?= e($row['Business_Name']) ?>

                                            <?php else: ?>

                                                <span class="no-business">
                                                    No business registered
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if (!empty($row['Place_Type'])): ?>

                                                <span class="business-type">
                                                    <?= e(ucwords(str_replace('_', ' ', $row['Place_Type']))) ?>
                                                </span>

                                            <?php else: ?>

                                                <span class="no-business">
                                                    -
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <?php if (!empty($row['Business_Size'])): ?>

                                                <span class="business-size">
                                                    <?= e($row['Business_Size']) ?>
                                                </span>

                                            <?php else: ?>

                                                <span class="no-business">
                                                    -
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="no-owners">
                                        No business owners found.
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
