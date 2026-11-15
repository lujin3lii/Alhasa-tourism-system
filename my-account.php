
<?php
session_start();
require_once 'db.php';

// Tourist Login Protection
if (!isset($_SESSION['tourist_id'])) {
    header("Location: login.php");
    exit();
}

$tourist_id = $_SESSION['tourist_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Fetch Tourist Information
$tourist = [
    'Name' => '',
    'Email' => ''
];

$stmt = $conn->prepare("
    SELECT Name, Email
    FROM Tourist
    WHERE Tourist_ID = ?
");

$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $tourist = $result->fetch_assoc();
}

$stmt->close();

// Update Tourist Information
$update_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $update_message = "Please enter a valid name and email.";
    } else {

        $stmt = $conn->prepare("
            UPDATE Tourist
            SET Name = ?, Email = ?
            WHERE Tourist_ID = ?
        ");

        $stmt->bind_param("ssi", $name, $email, $tourist_id);

        if ($stmt->execute()) {
            $tourist['Name'] = $name;
            $tourist['Email'] = $email;

            $_SESSION['tourist_name'] = $name;
            $_SESSION['tourist_email'] = $email;

            $update_message = "Your information has been updated successfully.";
        } else {
            $update_message = "Unable to update your information.";
        }

        $stmt->close();
    }
}

// Fetch Favorite Places
$favorites = [];

$stmt = $conn->prepare("
    SELECT p.Place_ID, p.Name, p.Image
    FROM favorites f
    INNER JOIN Places p ON f.Place_ID = p.Place_ID
    WHERE f.Tourist_ID = ?
");

$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

$stmt->close();


// Fetch Tourist Accessibility
$accessibilities = [];

$stmt = $conn->prepare("
    SELECT
        ao.Accessibility_ID,
        ao.Accessibility_Name
    FROM tourist_accessibility ta
    INNER JOIN accessibility_options ao
        ON ta.Accessibility_ID = ao.Accessibility_ID
    WHERE ta.Tourist_ID = ?
");

$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    // Accessibility Images
    $image_names = [
        'Wheelchair User' => 'WheelchairUser.jpg',
        'Mobility Assistance' => 'MobilityAssistance.jpg',
        'Hearing Assistance' => 'HearingAssistance.jpg',
        'Visual Assistance' => 'VisualAssistance.jpg',
        'Sensory Friendly' => 'SensoryFriendly.jpg'
    ];

    $name = $row['Accessibility_Name'];

    $row['Image'] = isset($image_names[$name])
        ? 'images/' . $image_names[$name]
        : '';

    $accessibilities[] = $row;
}

$stmt->close();


// Fetch Tourist Trips
$trips = [];

$stmt = $conn->prepare("
    SELECT Itinerary_ID
    FROM itinerary
    WHERE Tourist_ID = ?
    ORDER BY Create_at DESC
");

$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $trips[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Al-Ahsa Vision Hub</title>
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

            <a href="index.php"><span>⌂</span> Home</a>
            <a href="explore-al-ahsa.php"><span>⌖</span> Explore Al-Ahsa</a>
            <a href="places.php"><span>⌘</span> Tourist Places</a>
            <a href="seasons.php"><span>⌘</span> Seasons</a>
            <a href="restaurants.php"><span>♨</span> Restaurants</a>
            <a href="cafes.php"><span>☕</span> Cafes</a>
            <a href="markets.php"><span>♧</span> Markets</a>
            <a href="hotels.php"><span>⌂</span> Hotels</a>
            <a href="settings.php"><span>⚙</span> Settings</a>

            <a href="my-account.php" class="active">
                <span>♙</span> My Account
            </a>

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

                <a href="my-account.php"
                   class="home-signup"
                   id="account-button">
                    <?= e($_SESSION['tourist_name'] ?? 'My Account') ?>
                </a>

                <a href="index.php" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>

            </div>

        </header>

        <!-- Account Content -->
        <section class="account-page">

            <h1>My Account</h1>
            <p>Manage your profile and travel information.</p>

            <!-- Personal Information -->
            <section class="account-profile">

                <h2>Personal Information</h2>

                <p>
                    <strong>Name:</strong>
                    <span id="user-name"><?= e($tourist['Name']) ?></span>
                </p>

                <p>
                    <strong>Email:</strong>
                    <span id="user-email"><?= e($tourist['Email']) ?></span>
                </p>

                <button type="button" onclick="showEditForm()">
                    Edit Information
                </button>

                <form id="edit-form" method="POST" hidden>

                    <label for="name">Name:</label>

                    <input type="text"
                           id="name"
                           name="name"
                           value="<?= e($tourist['Name']) ?>"
                           required>

                    <label for="email">Email:</label>

                    <input type="email"
                           id="email"
                           name="email"
                           value="<?= e($tourist['Email']) ?>"
                           required>

                    <button type="submit" name="update_profile">
                        Save Changes
                    </button>

                </form>

                <?php if ($update_message !== ""): ?>
                    <p><?= e($update_message) ?></p>
                <?php endif; ?>

            </section>

            <!-- Favorite Places -->
            <section class="account-section">

                <h2>Favorite Places</h2>
                <p>View the places you would like to visit.</p>

                <?php if (!empty($favorites)): ?>

                    <div class="favorite-places-list"
                         style="display:flex; flex-wrap:wrap; gap:12px; margin-top:15px;">

                        <?php foreach ($favorites as $place): ?>

                            <a href="place-details.php?id=<?= e($place['Place_ID']) ?>"
                               title="<?= e($place['Name']) ?>"
                               style="display:block; width:75px; height:75px;">

                                <?php if (!empty($place['Image'])): ?>

                                    <img src="<?= e($place['Image']) ?>"
                                         alt="<?= e($place['Name']) ?>"
                                         style="width:75px; height:75px; object-fit:cover; border-radius:10px;">

                                <?php else: ?>

                                    <div style="width:75px; height:75px; border-radius:10px; background:#eee; display:flex; align-items:center; justify-content:center;">
                                        <span>♡</span>
                                    </div>

                                <?php endif; ?>

                            </a>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p>You haven't added any favorite places yet.</p>
                    <a href="places.php">Explore Places</a>

                <?php endif; ?>

            </section>

            <!-- My Accessibility -->
            <section class="account-section">

                <h2>My Accessibility</h2>
                <p>View your selected accessibility preferences.</p>

                <?php if (!empty($accessibilities)): ?>

                    <div class="accessibility-list"
                         style="display:flex; flex-wrap:wrap; gap:12px; margin-top:15px;">

                        <?php foreach ($accessibilities as $accessibility): ?>

                            <?php if (!empty($accessibility['Image'])): ?>

                                <img src="<?= e($accessibility['Image']) ?>"
                                     alt="<?= e($accessibility['Accessibility_Name']) ?>"
                                     title="<?= e($accessibility['Accessibility_Name']) ?>"
                                     style="width:75px; height:75px; object-fit:cover; border-radius:10px;">

                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <p>No accessibility preferences added yet.</p>

                <?php endif; ?>

                <a href="accessibility.php"
                   style="display:inline-block; margin-top:15px; text-decoration:none;">

                    <span style="margin-right:5px;">✎</span>
                    Edit My Accessibility

                </a>

            </section>

            <!-- My Trips -->
            <section class="account-section">

                <h2>My Trips</h2>
                <p>View and manage your travel plans.</p>

                <?php if (!empty($trips)): ?>

                    <div class="my-trips-list" style="margin-top:15px;">

                        <?php
                        $trip_number = 1;
                        foreach ($trips as $trip):
                        ?>

                            <div class="trip-card"
                                 style="padding:18px; margin-bottom:15px; border-radius:12px; background:#f8f8f8;">

                                <a href="my-itinerary.php?id=<?= e($trip['Itinerary_ID']) ?>"
                                   style="text-decoration:none;">

                                    <strong>
                                        Trip <?= $trip_number ?>
                                    </strong>

                                </a>

                            </div>

                        <?php
                            $trip_number++;
                        endforeach;
                        ?>

                    </div>

                <?php else: ?>

                    <p>You haven't planned any trips yet.</p>
                    <a href="index.php">Plan a Trip</a>

                <?php endif; ?>

            </section>

        </section>

    </main>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js?v=2"></script>

    <!-- Account Editing -->
    <script>

        function showEditForm() {

            document.getElementById("edit-form").hidden = false;

            document.getElementById("name").value =
                document.getElementById("user-name").textContent.trim();

            document.getElementById("email").value =
                document.getElementById("user-email").textContent.trim();

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
