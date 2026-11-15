
<?php
session_start();
require_once 'db.php';

mysqli_report(MYSQLI_REPORT_OFF);

/* Admin Login Protection */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

/* Escape Output */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Fetch Admin Name */
$adminName = "Admin";

$adminID = (int) $_SESSION['admin_id'];

$adminQuery = $conn->prepare("
    SELECT Name
    FROM Admin
    WHERE Admin_ID = ?
");

if ($adminQuery) {
    $adminQuery->bind_param("i", $adminID);
    $adminQuery->execute();

    $adminResult = $adminQuery->get_result();

    if ($adminResult && $adminResult->num_rows > 0) {
        $adminData = $adminResult->fetch_assoc();
        $adminName = $adminData['Name'] ?? "Admin";
    }

    $adminQuery->close();
}


/* =====================================
   TOTAL COMPLETED SURVEYS
===================================== */

$totalSurveys = 0;

$totalSurveyQuery = $conn->query("
    SELECT COUNT(*) AS total
    FROM survey
");

if ($totalSurveyQuery) {
    $totalSurveys = $totalSurveyQuery->fetch_assoc()['total'] ?? 0;
}


/* =====================================
   ACCESSIBILITY DATA
===================================== */

$accessibilityQuery = "
    SELECT
        ao.Accessibility_ID,
        ao.Accessibility_Name,
        COUNT(DISTINCT ta.Tourist_ID) AS total_tourists

    FROM accessibility_options ao

    LEFT JOIN tourist_accessibility ta
        ON ao.Accessibility_ID = ta.Accessibility_ID

    GROUP BY
        ao.Accessibility_ID,
        ao.Accessibility_Name

    ORDER BY ao.Accessibility_ID ASC
";

$accessibilityResult = $conn->query($accessibilityQuery);


/* =====================================
   SURVEY QUESTIONS AND OPTIONS
===================================== */

$surveyQuery = "
    SELECT
        q.Question_ID,
        q.Question_Text,
        q.Question_Type,
        q.Display_Order,

        o.Option_ID,
        o.Option_Text,
        o.Display_Order AS Option_Display_Order,

        COUNT(DISTINCT s.Tourist_id) AS total_selections

    FROM survey_questions q

    LEFT JOIN survey_options o
        ON q.Question_ID = o.Question_ID

    LEFT JOIN survey_answers sa
        ON o.Option_ID = sa.Option_ID
        AND q.Question_ID = sa.Question_ID

    LEFT JOIN survey s
        ON sa.Survey_ID = s.Survey_id

    GROUP BY
        q.Question_ID,
        q.Question_Text,
        q.Question_Type,
        q.Display_Order,
        o.Option_ID,
        o.Option_Text,
        o.Display_Order

    ORDER BY
        q.Display_Order ASC,
        o.Display_Order ASC
";

$surveyResult = $conn->query($surveyQuery);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Survey & Accessibility | Al-Ahsa Vision Hub</title>

    <link rel="stylesheet" href="css/style.css">

    <style>

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f5f8f4;
            font-family: Arial, sans-serif;
            color: #333;
        }

        /* Main Content */

        .survey-admin-main {
    width: min(1200px, calc(100% - 50px));
    margin: 110px auto 40px;
    flex: 1;
}

        /* Page Heading */

        .survey-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 35px;
        }

        .survey-heading h1 {
            color: #075b45;
            font-size: 28px;
            margin: 0 0 10px;
        }

        .survey-heading p {
            color: #777;
            font-size: 14px;
            margin: 0;
        }

        /* Total Card - Same as Tourists Page */

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

        /* Sections */

        .survey-section {
            margin-bottom: 40px;
        }

        .survey-section h2 {
            color: #075b45;
            font-size: 21px;
            margin: 0 0 8px;
        }

        .survey-section-description {
            color: #888;
            font-size: 13px;
            margin: 0 0 20px;
        }

        /* Table Container */

        .survey-table-container {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow-x: auto;
            width: 100%;
        }

        .survey-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .survey-table th {
            background: #075b45;
            color: #fff;
            padding: 16px 18px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .survey-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
            color: #555;
            vertical-align: middle;
        }

        .survey-table tbody tr:last-child td {
            border-bottom: none;
        }

        .survey-table tbody tr:hover {
            background: #f8fbf8;
        }

        .survey-table td:first-child {
            color: #333;
            font-weight: 600;
        }

        .selection-count {
            color: #075b45;
            font-weight: bold;
            font-size: 14px;
        }

        .empty-message {
            text-align: center;
            color: #888 !important;
            padding: 25px !important;
            font-weight: normal !important;
        }

        /* Responsive */

        @media (max-width: 600px) {

            .survey-admin-main {
                width: calc(100% - 30px);
                margin: 30px auto;
            }

            .survey-heading h1 {
                font-size: 23px;
            }

            .tourists-total-card {
                width: 100%;
                box-sizing: border-box;
            }

            .survey-section h2 {
                font-size: 19px;
            }
        }

    </style>

</head>

<body>


<!-- =========================
     TOPBAR
========================= -->

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


<!-- =========================
     SIDEBAR
========================= -->

<button class="menu-button"
        onclick="toggleMenu()"
        aria-label="Open menu">
    ☰
</button>

<div class="sidebar-backdrop" onclick="toggleMenu()"></div>

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

        <a href="admin-itineraries.php">
            <span>▤</span> Itineraries
        </a>

        <a href="admin-survey-accessibility.php" class="active">
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


<!-- =========================
     MAIN CONTENT
========================= -->

<main class="survey-admin-main">


    <!-- PAGE HEADING -->

    <div class="survey-heading">

        <div>

            <h1>Survey & Accessibility</h1>

            <p>
                View tourists' accessibility needs and survey responses.
            </p>

        </div>

        <div class="tourists-total-card">

            <span>Total Completed Surveys</span>

            <strong><?= e($totalSurveys) ?></strong>

        </div>

    </div>



    <!-- =========================
         ACCESSIBILITY SECTION
    ========================= -->

    <section class="survey-section">

        <h2>Accessibility</h2>

        <p class="survey-section-description">
            View the number of tourists who selected each accessibility option.
        </p>


        <div class="survey-table-container">

            <table class="survey-table">

                <thead>

                    <tr>
                        <th>Accessibility Name</th>
                        <th>Number of Tourists</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if ($accessibilityResult && $accessibilityResult->num_rows > 0): ?>

                        <?php while ($row = $accessibilityResult->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= e($row['Accessibility_Name']) ?>
                                </td>

                                <td>
                                    <span class="selection-count">
                                        <?= e($row['total_tourists']) ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="2" class="empty-message">
                                No accessibility data available.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =========================
         SURVEY SECTION
    ========================= -->

    <section class="survey-section">

        <h2>Survey Results</h2>

        <p class="survey-section-description">
            View survey questions, their options, and the number of tourists who selected each option.
        </p>


        <div class="survey-table-container">

            <table class="survey-table">

                <thead>

                    <tr>
                        <th>Survey Question</th>
                        <th>Survey Option</th>
                        <th>Number of Selections</th>
                    </tr>

                </thead>


                <tbody>

                    <?php if ($surveyResult && $surveyResult->num_rows > 0): ?>

                        <?php while ($row = $surveyResult->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= e($row['Question_Text']) ?>
                                </td>

                                <td>
                                    <?= $row['Option_Text'] !== null
                                        ? e($row['Option_Text'])
                                        : '—' ?>
                                </td>

                                <td>
                                    <span class="selection-count">
                                        <?= e($row['total_selections']) ?>
                                    </span>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="3" class="empty-message">
                                No survey questions available.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


</main>



<!-- =========================
     FOOTER
========================= -->

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

        <p>©️ 2026 Al-Ahsa Vision Hub. All rights reserved.</p>

        <p>Explore Al-Ahsa, experience its beauty.</p>

    </div>

</footer>



<!-- AI Assistant Button -->

<button
    class="chat-button"
    onclick="openAI()"
    aria-label="Open AI Assistant"
    title="AI Assistant">
</button>


<script src="script.js?v=2"></script>
</body>
</html>
