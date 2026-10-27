
<?php
session_start();
include 'db.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getPlaceValue($row, $names, $default = '') {
    foreach ($names as $name) {
        foreach ($row as $key => $value) {
            if (strcasecmp($key, $name) === 0) {
                return $value ?? $default;
            }
        }
    }
    return $default;
}

$placeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$place = null;
$reviews = [];
$averageRating = null;
$errorMessage = '';
$actionMessage = '';
$touristId = $_SESSION['Tourist_ID'] ?? null;
$isFavorite = false;

if (!$placeId || $placeId < 1) {
    $errorMessage = 'Please select a valid place.';
} else {
    $stmt = $conn->prepare(
        "SELECT * FROM Places
         WHERE Place_ID = ? AND Is_Published = 1"
    );

    if ($stmt) {
        $stmt->bind_param("i", $placeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $place = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$place) {
        $errorMessage = 'This place is not available.';
    }
}

/* Handle favorite and review submissions */
if (
    $place &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    $action = $_POST['action'] ?? '';

    if (!$touristId) {
        $actionMessage = 'Please log in to save favorites or submit a review.';
    } elseif ($action === 'favorite') {
        $stmt = $conn->prepare(
            "SELECT Place_ID FROM favorites
             WHERE Tourist_ID = ? AND Place_ID = ?"
        );

        if ($stmt) {
            $stmt->bind_param("ii", $touristId, $placeId);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($exists) {
                $stmt = $conn->prepare(
                    "DELETE FROM favorites
                     WHERE Tourist_ID = ? AND Place_ID = ?"
                );

                if ($stmt) {
                    $stmt->bind_param("ii", $touristId, $placeId);
                    $stmt->execute();
                    $stmt->close();
                    $actionMessage = 'Place removed from your favorites.';
                }
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO favorites (Tourist_ID, Place_ID)
                     VALUES (?, ?)"
                );

                if ($stmt) {
                    $stmt->bind_param("ii", $touristId, $placeId);

                    if ($stmt->execute()) {
                        $actionMessage = 'Place added to your favorites.';
                    } else {
                        $actionMessage = 'Could not save this favorite.';
                    }

                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'review') {
        $rating = filter_input(
            INPUT_POST,
            'rating',
            FILTER_VALIDATE_INT
        );
        $comment = trim($_POST['comment'] ?? '');

        if ($rating === false || $rating === null || $rating < 1 || $rating > 5) {
            $actionMessage = 'Please select a rating from 1 to 5.';
        } elseif ($comment === '') {
            $actionMessage = 'Please write a comment before submitting.';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO Place_Reviews
                 (Tourist_ID, Place_ID, Comment, Rating, Created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "iisi",
                    $touristId,
                    $placeId,
                    $comment,
                    $rating
                );

                if ($stmt->execute()) {
                    $actionMessage = 'Thank you! Your review has been submitted.';
                } else {
                    $actionMessage = 'Could not submit your review. Please check the database columns.';
                }

                $stmt->close();
            } else {
                $actionMessage = 'Could not prepare the review. Please check the database columns.';
            }
        }
    }
}

/* Check whether the place is already a favorite */
if ($place && $touristId) {
    $stmt = $conn->prepare(
        "SELECT Place_ID FROM favorites
         WHERE Tourist_ID = ? AND Place_ID = ?"
    );

    if ($stmt) {
        $stmt->bind_param("ii", $touristId, $placeId);
        $stmt->execute();
        $isFavorite = $stmt->get_result()->num_rows > 0;
        $stmt->close();
    }
}

/* Load reviews for this place */
if ($place) {
    $reviewStmt = $conn->prepare(
        "SELECT Rating, Comment
         FROM Place_Reviews
         WHERE Place_ID = ?"
    );

    if ($reviewStmt) {
        $reviewStmt->bind_param("i", $placeId);
        $reviewStmt->execute();
        $reviewResult = $reviewStmt->get_result();

        while ($review = $reviewResult->fetch_assoc()) {
            $reviews[] = $review;
        }

        $reviewStmt->close();
    }

    if (count($reviews) > 0) {
        $totalRating = 0;

        foreach ($reviews as $review) {
            $totalRating += (int)$review['Rating'];
        }

        $averageRating = $totalRating / count($reviews);
    }
}

/* Read place information */
$name = $place
    ? getPlaceValue($place, ['Name', 'Place_Name'], 'Place')
    : '';

$description = $place
    ? getPlaceValue(
        $place,
        ['Description', 'Full_Description'],
        getPlaceValue($place, ['Short_Description'])
    )
    : '';

$image = $place
    ? trim((string)getPlaceValue($place, ['Image', 'Image_Path']))
    : '';

$location = $place
    ? trim((string)getPlaceValue(
        $place,
        ['Location', 'Google_Maps_Link', 'Google_Maps_URL']
    ))
    : '';

$openingHours = $place
    ? getPlaceValue($place, ['Opening_Hours'])
    : '';

$website = $place
    ? trim((string)getPlaceValue($place, ['Website', 'Website_URL']))
    : '';

if (
    $image !== '' &&
    !preg_match('~^(https?://|/)~i', $image) &&
    stripos($image, 'images/') !== 0
) {
    $image = 'images/' . $image;
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($name ?: 'Place Details') ?> | Al-Ahsa Vision Hub</title>

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
            <a href="index.html"><span>⌂</span> Home</a>
            <a href="explore-al-ahsa.html"><span>⌖</span> Explore Al-Ahsa</a>
            <a href="places.php" class="active">
                <span>⌘</span> Tourist Places
            </a>
            <a href="restaurants.html"><span>♨</span> Restaurants</a>
            <a href="cafes.html"><span>☕</span> Cafes</a>
            <a href="markets.html"><span>♧</span> Markets</a>
            <a href="settings.html"><span>⚙</span> Settings</a>
            <a href="my-account.html"><span>♙</span> My Account</a>
            <a href="About.html"><span>ⓘ</span> About Us</a>
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
                <a href="role-selection.html"
                   class="home-signup"
                   id="account-button">
                    Log In
                </a>

                <a href="index.html" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>
            </div>

        </header>

        <!-- Place Details -->
        <section class="home-section details-section">

            

            <?php if ($errorMessage !== ''): ?>

                <div class="details-container details-message">
                    <h2><?= e($errorMessage) ?></h2>
                    <a href="places.php" class="details-link">
                        Browse Tourist Places
                    </a>
                </div>

            <?php else: ?>

                <article class="details-container">

                    <?php if ($image !== ''): ?>
                        <img
                            src="<?= e($image) ?>"
                            alt="<?= e($name) ?>"
                            class="details-image">
                    <?php endif; ?>

                    <div class="details-content">

                        <!-- Place Title and Favorite Button -->
                        <div class="details-title-row">

                            <h1 class="details-title">
                                <?= e($name) ?>
                            </h1>

                            <form method="post" class="favorite-form">
                                <input type="hidden" name="action" value="favorite">

                                <button
                                    type="submit"
                                    class="favorite-button <?= $isFavorite ? 'is-favorite' : '' ?>"
                                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                                    title="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                                    <?= $isFavorite ? '♥' : '♡' ?>
                                </button>
                            </form>

                        </div>

                        <?php if ($actionMessage !== ''): ?>
                            <p class="action-message" role="status">
                                <?= e($actionMessage) ?>
                            </p>
                        <?php endif; ?>

                        <div class="details-description">
                            <?= nl2br(e($description)) ?>
                        </div>

                        <!-- Location, Hours and Website -->
                        <div class="details-info">

                            <section class="details-info-card">
                                <h3>Location</h3>

                                <?php if ($location !== ''): ?>
                                    <p>Find this place on Google Maps.</p>

                                    <a
                                        href="<?= e($location) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="details-link">
                                        View on Google Maps
                                    </a>
                                <?php else: ?>
                                    <p>Location information is not available.</p>
                                <?php endif; ?>
                            </section>

                            <section class="details-info-card">
                                <h3>Opening Hours</h3>

                                <p>
                                    <?= $openingHours !== ''
                                        ? nl2br(e($openingHours))
                                        : 'Opening hours are not available.' ?>
                                </p>
                            </section>

                            <section class="details-info-card">
                                <h3>Website</h3>

                                <?php if ($website !== ''): ?>
                                    <a
                                        href="<?= e($website) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="details-link">
                                        Visit Website
                                    </a>
                                <?php else: ?>
                                    <p>No website is available.</p>
                                <?php endif; ?>
                            </section>

                        </div>

                        <!-- Visitor Reviews -->
                        <section class="details-reviews">

                            <h2>Visitor Reviews</h2>

                            <?php if ($averageRating !== null): ?>

                                <div class="details-rating-summary">
                                    <span class="details-stars">★</span>

                                    <strong>
                                        <?= number_format($averageRating, 1) ?>/5
                                    </strong>

                                    <span>
                                        (<?= count($reviews) ?> reviews)
                                    </span>
                                </div>

                            <?php else: ?>

                                <p class="details-rating-summary">
                                    No reviews yet.
                                </p>

                            <?php endif; ?>

                            <!-- Submit a Review -->
                            <div class="review-form-container">

                                <h3>Rate This Place</h3>
                                <p>Share your experience with other visitors.</p>

                                <form method="post" class="review-form">

                                    <input type="hidden"
                                           name="action"
                                           value="review">

                                    <fieldset class="rating-fieldset">
                                        <legend>Your Rating</legend>

                                        <div class="rating-options">

                                            <label>
                                                <input type="radio"
                                                       name="rating"
                                                       value="1"
                                                       required>
                                                <span>★</span>
                                            </label>

                                            <label>
                                                <input type="radio"
                                                       name="rating"
                                                       value="2">
                                                <span>★★</span>
                                            </label>

                                            <label>
                                                <input type="radio"
                                                       name="rating"
                                                       value="3">
                                                <span>★★★</span>
                                            </label>

                                            <label>
                                                <input type="radio"
                                                       name="rating"
                                                       value="4">
                                                <span>★★★★</span>
                                            </label>

                                            <label>
                                                <input type="radio"
                                                       name="rating"
                                                       value="5">
                                                <span>★★★★★</span>
                                            </label>

                                        </div>
                                    </fieldset>

                                    <label for="review-comment">
                                        Your Comment
                                    </label>

                                    <textarea
                                        id="review-comment"
                                        name="comment"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="Write your experience here..."
                                        required></textarea>

                                    <button type="submit"
                                            class="details-link review-submit">
                                        Submit Review
                                    </button>

                                </form>
                            </div>

                            <!-- Existing Reviews -->
                            <?php foreach ($reviews as $review): ?>

                                <div class="review-card">

                                    <div class="details-stars">
                                        <?= str_repeat(
                                            '★',
                                            max(0, min(5, (int)$review['Rating']))
                                        ) ?>

                                        <span>
                                            <?= (int)$review['Rating'] ?>/5
                                        </span>
                                    </div>

                                    <p class="review-comment">
                                        <?= nl2br(e($review['Comment'])) ?>
                                    </p>

                                </div>

                            <?php endforeach; ?>

                        </section>

                    </div>

                </article>

            <?php endif; ?>

        </section>

    </main>

    <!-- AI Assistant Button -->
    <button class="chat-button"
            onclick="openAI()"
            aria-label="Open AI Assistant"
            title="AI Assistant">
    </button>

    <!-- Shared JavaScript -->
    <script src="script.js"></script>

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
            <p>© 2026 Al-Ahsa Vision Hub. All rights reserved.</p>
            <p>Explore Al-Ahsa, experience its beauty.</p>
        </div>

    </footer>

</body>
</html>
