<?php
require_once __DIR__ . '/trip-common.php';
trip_require_tourist();

$touristId = (int)$_SESSION['tourist_id'];

/* CSRF token */
if (empty($_SESSION['trip_delete_token'])) {
    $_SESSION['trip_delete_token'] = bin2hex(random_bytes(32));
}

if (empty($_SESSION['trip_review_token'])) {
    $_SESSION['trip_review_token'] = bin2hex(random_bytes(32));
}

$itineraryId = isset($_GET['id'])
    ? (int)$_GET['id']
    : (int)($_SESSION['latest_itinerary_id'] ?? 0);

if ($itineraryId < 1) {
    header('Location: itinerary.php');
    exit;
}

/* Load the itinerary belonging to the logged-in tourist */
$stmt = $conn->prepare("
    SELECT itinerary_id, days, create_at
    FROM itinerary
    WHERE itinerary_id = ? AND tourist_id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Could not load itinerary.');
}

$stmt->bind_param('ii', $itineraryId, $touristId);
$stmt->execute();

$itinerary = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$itinerary) {
    header('Location: my-trips.php');
    exit;
}

/* Review messages */
$reviewMessage = '';
$reviewMessageType = '';

$reviewFormRating = 0;
$reviewFormComment = '';

/* Save or update trip review using the existing itinerary_reviews table */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'save_trip_review'
) {
    $postedItineraryId = (int)($_POST['itinerary_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $token = $_POST['review_token'] ?? '';

    $reviewFormRating = $rating;
    $reviewFormComment = $comment;

    if (
        $postedItineraryId !== $itineraryId ||
        !hash_equals($_SESSION['trip_review_token'], $token)
    ) {
        $reviewMessage = 'Invalid request. Please try again.';
        $reviewMessageType = 'error';

    } elseif ($rating < 1 || $rating > 5) {
        $reviewMessage = 'Please select a rating from 1 to 5 stars.';
        $reviewMessageType = 'error';

    } elseif (mb_strlen($comment) > 2000) {
        $reviewMessage = 'Your comment must not exceed 2000 characters.';
        $reviewMessageType = 'error';

    } else {

        $stmt = $conn->prepare("
            INSERT INTO itinerary_reviews
                (Tourist_ID, Itinerary_ID, Rating, Comment)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                Rating = VALUES(Rating),
                Comment = VALUES(Comment)
        ");

        if ($stmt) {
            $stmt->bind_param(
                'iiis',
                $touristId,
                $itineraryId,
                $rating,
                $comment
            );

            if ($stmt->execute()) {
                $stmt->close();

                header(
                    'Location: my-itinerary.php?id=' .
                    $itineraryId .
                    '&review=success'
                );
                exit;
            }

            $stmt->close();
        }

        $reviewMessage = 'Could not save your review. Please try again.';
        $reviewMessageType = 'error';
    }
}

/* Load existing review for this itinerary and tourist */
$existingReview = null;

$stmt = $conn->prepare("
    SELECT Rating, Comment
    FROM itinerary_reviews
    WHERE Itinerary_ID = ? AND Tourist_ID = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param('ii', $itineraryId, $touristId);
    $stmt->execute();
    $existingReview = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* Keep existing review values unless the form has errors */
if ($reviewMessageType !== 'error') {
    if ($existingReview) {
        $reviewFormRating = (int)$existingReview['Rating'];
        $reviewFormComment = $existingReview['Comment'] ?? '';
    }
}

/* Load itinerary places */
$stmt = $conn->prepare("
    SELECT
        ii.day_number,
        ii.start_time,
        ii.end_time,
        ii.sequence_number,
        ii.reason_chosen,
        p.Place_ID,
        p.Name,
        p.Place_Type,
        p.Short_Description,
        p.Description,
        p.Location,
        p.Image,
        p.Opening_Hours,
        p.Website
    FROM itinerary_items ii
    INNER JOIN Places p ON p.Place_ID = ii.place_id
    WHERE ii.itinerary_id = ?
    ORDER BY ii.day_number, ii.sequence_number
");

if (!$stmt) {
    die('Could not load itinerary items.');
}

$stmt->bind_param('i', $itineraryId);
$stmt->execute();

$result = $stmt->get_result();
$days = [];

while ($row = $result->fetch_assoc()) {
    $days[(int)$row['day_number']][] = $row;
}

$stmt->close();

/* Use session title for the latest generated itinerary */
$isLatestItinerary =
    (int)($_SESSION['latest_itinerary_id'] ?? 0) === $itineraryId;

$title = $isLatestItinerary
    ? ($_SESSION['latest_itinerary_title'] ?? 'Your Al-Ahsa Journey')
    : 'Your Al-Ahsa Journey';

$intro = $isLatestItinerary
    ? ($_SESSION['latest_itinerary_intro'] ?? 'A personalized journey created from your preferences.')
    : 'Your saved personalized journey in Al-Ahsa.';

trip_page_header('my-itinerary');
?>

<style>
.trip-delete-btn {
    border: 1px solid #c96b68;
    color: #b94a48;
    background: transparent;
    padding: 12px 19px;
    border-radius: 9px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    transition: 0.2s ease;
}

.trip-delete-btn:hover {
    background: #fff1f0;
}

.trip-delete-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(25, 38, 35, 0.58);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.trip-delete-overlay.active {
    display: flex;
}

.trip-delete-modal {
    width: 100%;
    max-width: 420px;
    background: #ffffff;
    border-radius: 18px;
    padding: 30px 25px;
    text-align: center;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
    animation: tripModalIn 0.2s ease;
}

@keyframes tripModalIn {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.trip-delete-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 17px;
    border-radius: 50%;
    background: #fff0ef;
    color: #b94a48;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
}

.trip-delete-modal h3 {
    color: #26383b;
    margin: 0 0 12px;
    font-size: 21px;
}

.trip-delete-modal p {
    color: #687779;
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 24px;
}

.trip-delete-modal-actions {
    display: flex;
    gap: 12px;
}

.trip-modal-cancel,
.trip-modal-confirm {
    flex: 1;
    padding: 12px 15px;
    border-radius: 9px;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.trip-modal-cancel {
    border: 1px solid #e3e9e7;
    background: #f7f8f5;
    color: #26383b;
}

.trip-modal-confirm {
    border: none;
    background: #b94a48;
    color: #ffffff;
}

.trip-modal-confirm:hover {
    background: #a33e3c;
}

/* Trip review button */
.trip-review-open-btn {
    border: 1px solid #087f83;
    background: #087f83;
    color: #ffffff;
    padding: 12px 19px;
    border-radius: 9px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    transition: 0.2s ease;
}

.trip-review-open-btn:hover {
    background: #075b70;
}

/* Trip review modal */
.trip-review-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(25, 38, 35, 0.60);
    z-index: 100000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.trip-review-overlay.active {
    display: flex;
}

.trip-review-modal {
    width: 100%;
    max-width: 460px;
    background: #ffffff;
    border-radius: 18px;
    padding: 30px 25px;
    text-align: center;
    position: relative;
    box-shadow: 0 15px 50px rgba(0,0,0,0.2);
    animation: tripModalIn 0.2s ease;
}

.trip-review-close {
    position: absolute;
    top: 13px;
    right: 16px;
    border: none;
    background: transparent;
    color: #687779;
    font-size: 25px;
    cursor: pointer;
    line-height: 1;
}

.trip-review-icon {
    width: 58px;
    height: 58px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: #fff8e8;
    color: #D6A43B;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 27px;
}

.trip-review-modal h3 {
    color: #26383b;
    margin: 0 0 10px;
    font-size: 22px;
}

.trip-review-modal > p {
    color: #687779;
    line-height: 1.7;
    font-size: 14px;
    margin-bottom: 18px;
}

.trip-review-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: center;
    gap: 7px;
    margin: 20px 0 24px;
}

.trip-review-rating input {
    display: none;
}

.trip-review-rating label {
    font-size: 38px;
    color: #d9d9d9;
    cursor: pointer;
    transition: 0.2s ease;
}

.trip-review-rating label:hover,
.trip-review-rating label:hover ~ label,
.trip-review-rating input:checked ~ label {
    color: #D6A43B;
}

.trip-review-textarea {
    width: 100%;
    min-height: 115px;
    padding: 14px;
    border: 1px solid #e3e9e7;
    border-radius: 10px;
    resize: vertical;
    font: inherit;
    font-size: 14px;
    color: #26383b;
    box-sizing: border-box;
    outline: none;
}

.trip-review-textarea:focus {
    border-color: #087f83;
}

.trip-review-submit {
    width: 100%;
    margin-top: 18px;
    border: none;
    background: #087f83;
    color: #ffffff;
    padding: 13px 25px;
    border-radius: 9px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s ease;
}

.trip-review-submit:hover {
    background: #075b70;
}

.trip-review-message {
    padding: 12px 15px;
    border-radius: 9px;
    margin-bottom: 18px;
    font-size: 14px;
}

.trip-review-message.error {
    background: #fff1f0;
    color: #b94a48;
}

.trip-review-saved {
    color: #087f83;
    font-size: 13px;
    margin-top: 12px;
}

/* Main action buttons */
.result-actions {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
    margin-top: 30px;
}

.primary-actions {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 14px;
    width: 100%;
    max-width: 520px;
}

.primary-actions > a {
    flex: 1;
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    text-decoration: none;
    border-radius: 9px;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
}

.primary-actions .trip-secondary-btn {
    border: 1px solid #087f83;
    background: #ffffff;
    color: #087f83;
}

.primary-actions .trip-primary-btn {
    border: 1px solid #087f83;
    background: #087f83;
    color: #ffffff;
}

.primary-actions .trip-secondary-btn:hover {
    background: #edf7f5;
}

.primary-actions .trip-primary-btn:hover {
    background: #075b70;
}

/* Small icon actions */
.icon-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 13px;
}

.icon-action-btn {
    width: 43px;
    height: 43px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: #ffffff;
    cursor: pointer;
    font-size: 21px;
    transition: 0.2s ease;
}

.icon-action-btn:hover {
    transform: translateY(-2px);
}

.trip-download-btn {
    border: 1px solid #087f83;
    color: #087f83;
}

.trip-download-btn:hover {
    background: #edf7f5;
}

.trip-review-open-btn {
    border: 1px solid #e5c56c;
    color: #D6A43B;
    background: #fffaf0;
}

.trip-review-open-btn:hover {
    background: #fff3d7;
}

.trip-delete-btn {
    border: 1px solid #c96b68;
    color: #b94a48;
    background: #fff7f6;
}

.trip-delete-btn:hover {
    background: #fff0ef;
}

/* Print / PDF design */
@page {
    size: A4;
    margin: 13mm;
}

@media print {

    html,
    body {
        background: #ffffff !important;
        color: #26383b !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    body * {
        visibility: hidden !important;
    }

    .trip-page,
    .trip-page * {
        visibility: visible !important;
    }

    .trip-page {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 12px !important;
        background: linear-gradient(
            135deg,
            #f0f7f4 0%,
            #e5f2ef 50%,
            #edf5f5 100%
        ) !important;
        box-shadow: none !important;
        border-radius: 12px !important;
    }

    .back-link,
    .result-actions,
    .trip-review-open-btn,
    .trip-review-saved,
    .trip-trust,
    .trip-delete-overlay,
    .trip-review-overlay {
        display: none !important;
    }

    .result-heading {
        text-align: center !important;
        margin-bottom: 20px !important;
        padding: 18px 12px !important;
        border-bottom: 2px solid #087f83 !important;
        background: linear-gradient(
            120deg,
            #e4f1ed,
            #edf6f4
        ) !important;
        border-radius: 10px !important;
    }

    .result-heading h1 {
        color: #075b70 !important;
        font-size: 25px !important;
        margin-top: 10px !important;
    }

    .result-heading p {
        color: #687779 !important;
        font-size: 12px !important;
    }

    .result-meta {
        display: flex !important;
        justify-content: center !important;
        flex-wrap: wrap !important;
        gap: 10px !important;
        font-size: 11px !important;
    }

    .day-card {
        margin: 0 0 12px !important;
        padding: 15px !important;
        border: 1px solid #cfe2dc !important;
        border-radius: 12px !important;
        background: linear-gradient(
            135deg,
            #f5faf8 0%,
            #eaf4f1 100%
        ) !important;
        box-shadow: none !important;
        break-inside: auto !important;
        page-break-inside: auto !important;
    }

    /* Start each new day on a separate page */
    .day-card + .day-card {
        break-before: page !important;
        page-break-before: always !important;
    }

    .day-heading {
        border-bottom: 1px solid #cfe2dc !important;
        padding-bottom: 10px !important;
        margin-bottom: 12px !important;
    }

    .day-heading h2 {
        color: #075b70 !important;
        font-size: 21px !important;
    }

    .timeline {
        gap: 10px !important;
    }

    .timeline-item {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
        margin-bottom: 12px !important;
    }

    /* Keep place image beside its information */
    .place-result-card {
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 12px !important;
        padding: 10px !important;
        border: 1px solid #dce9e4 !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        box-shadow: none !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    .place-result-card img {
        display: block !important;
        flex: 0 0 115px !important;
        width: 115px !important;
        min-width: 115px !important;
        max-width: 115px !important;
        height: 90px !important;
        max-height: 90px !important;
        object-fit: cover !important;
        border-radius: 7px !important;
    }

    .place-placeholder {
        flex: 0 0 115px !important;
        width: 115px !important;
        height: 90px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        border-radius: 7px !important;
        background: #eaf4f1 !important;
    }

    .place-result-content {
        flex: 1 !important;
        min-width: 0 !important;
        padding: 0 !important;
    }

    .place-result-content h3 {
        color: #075b70 !important;
        font-size: 15px !important;
        margin: 4px 0 6px !important;
    }

    .place-result-content p {
        font-size: 11px !important;
        line-height: 1.5 !important;
        margin: 4px 0 !important;
    }

    .place-result-content small {
        font-size: 10px !important;
    }

    .ai-reason {
        background: #eaf4f1 !important;
        border-radius: 7px !important;
        padding: 8px !important;
        margin-top: 7px !important;
        font-size: 10px !important;
    }

    .place-website {
        color: #087f83 !important;
        font-size: 10px !important;
    }

    .empty-plan {
        display: block !important;
    }
}

@media (max-width: 600px) {
    .trip-review-modal {
        padding: 28px 18px;
    }

    .trip-review-rating label {
        font-size: 34px;
    }

    .primary-actions {
        gap: 9px;
    }

    .primary-actions > a {
        min-height: 46px;
        font-size: 13px;
        padding: 10px 8px;
    }

    .icon-actions {
        gap: 12px;
    }
}
</style>

<section class="trip-page result-page">

    <div class="result-heading">

        <span class="eyebrow">YOUR PERSONALIZED JOURNEY</span>

        <h1><?= trip_h($title) ?></h1>

        <p><?= trip_h($intro) ?></p>

        <div class="result-meta">
            <span>✦ AI generated</span>
            <span>◷ <?= (int)$itinerary['days'] ?> days</span>
            <span>⌖ Al-Ahsa, Saudi Arabia</span>
        </div>

        <div class="trip-trust">
            <span>✓</span>
            Your trip has been saved successfully.
        </div>

    </div>

    <?php if (!$days): ?>

        <div class="empty-plan">

            <h2>Your itinerary is saved.</h2>

            <p>
                No itinerary stops were returned.
                Please generate a new plan.
            </p>

            <a class="trip-primary-btn" href="survey.php">
                Create Another Trip
            </a>

        </div>

    <?php else: ?>

        <div class="itinerary-days">

            <?php foreach ($days as $dayNumber => $items): ?>

                <section class="day-card">

                    <div class="day-heading">

                        <div>
                            <span class="eyebrow">YOUR JOURNEY</span>
                            <h2>Day <?= (int)$dayNumber ?></h2>
                        </div>

                        <span class="day-count">
                            <?= count($items) ?>
                            <?= count($items) === 1 ? 'stop' : 'stops' ?>
                        </span>

                    </div>

                    <div class="timeline">

                        <?php foreach ($items as $item): ?>

                            <article class="timeline-item">

                                <div class="timeline-time">
                                    <?= trip_h(substr($item['start_time'], 0, 5)) ?>

                                    <span>
                                        <?= trip_h(substr($item['end_time'], 0, 5)) ?>
                                    </span>
                                </div>

                                <div class="timeline-dot"></div>

                                <div class="place-result-card">

                                    <?php if (!empty($item['Image'])): ?>

                                        <img
                                            src="<?= trip_h($item['Image']) ?>"
                                            alt="<?= trip_h($item['Name']) ?>"
                                            onerror="this.style.display='none'"
                                        >

                                    <?php else: ?>

                                        <div class="place-placeholder">⌖</div>

                                    <?php endif; ?>

                                    <div class="place-result-content">

                                        <span class="place-type">
                                            <?= trip_h(
                                                ucwords(
                                                    str_replace('_', ' ', $item['Place_Type'])
                                                )
                                            ) ?>
                                        </span>

                                        <h3>
                                            <?= trip_h($item['Name']) ?>
                                        </h3>

                                        <p>
                                            <?= trip_h(
                                                $item['Short_Description']
                                                ?: $item['Description']
                                            ) ?>
                                        </p>

                                        <?php if (!empty($item['Location'])): ?>

                                            <small>
                                                ⌖ <?= trip_h($item['Location']) ?>
                                            </small>

                                        <?php endif; ?>

                                        <?php if (!empty($item['reason_chosen'])): ?>

                                            <div class="ai-reason">
                                                <span>✦ Why this place?</span>
                                                <?= trip_h($item['reason_chosen']) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if (!empty($item['Website'])): ?>

                                            <a
                                                class="place-website"
                                                href="<?= trip_h($item['Website']) ?>"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                Visit website ↗️
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <!-- Main action buttons -->
    <div class="result-actions">

        <div class="primary-actions">

            <a class="trip-secondary-btn" href="my-trips.php">
                My Trips
            </a>

            <a class="trip-primary-btn" href="survey.php">
                Create Trip <span>→</span>
            </a>

        </div>

        <!-- Icon actions -->
        <div class="icon-actions">

            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">

                <button
                    type="button"
                    class="icon-action-btn trip-download-btn"
                    onclick="downloadTripPDF()"
                    title="Download PDF"
                    aria-label="Download PDF"
                >
                    <svg
                        width="25"
                        height="25"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        aria-hidden="true"
                    >
                        <path d="M12 3v12"></path>
                        <path d="m7 10 5 5 5-5"></path>
                        <path d="M5 17v3h14v-3"></path>
                    </svg>
                </button>

                <span style="font-size: 12px; color: #687779; text-align: center; white-space: nowrap;">
                    Download PDF
                </span>

            </div>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">

                <button
                    type="button"
                    class="icon-action-btn trip-review-open-btn"
                    onclick="openTripReviewModal()"
                    title="<?= $existingReview ? 'Edit Your Review' : 'Rate This Trip' ?>"
                    aria-label="<?= $existingReview ? 'Edit Your Review' : 'Rate This Trip' ?>"
                >
                    ★
                </button>

                <span style="font-size: 12px; color: #687779; text-align: center; white-space: nowrap;">
                    Rate Trip
                </span>

            </div>

            <div style="display: flex; flex-direction: column; align-items: center; gap: 6px;">

                <button
                    type="button"
                    class="icon-action-btn trip-delete-btn"
                    onclick="openTripDeleteModal()"
                    title="Delete This Trip"
                    aria-label="Delete This Trip"
                >
                    🗑
                </button>

                <span style="font-size: 12px; color: #687779; text-align: center; white-space: nowrap;">
                    Delete Trip
                </span>

            </div>

        </div>

    </div>

    <?php if ($existingReview): ?>
        <div class="trip-review-saved" style="text-align:center;">
            You have already reviewed this trip.
        </div>
    <?php endif; ?>

</section>

<!-- Trip review modal -->
<div
    class="trip-review-overlay"
    id="tripReviewOverlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tripReviewTitle"
    onclick="closeTripReviewModal(event)"
>

    <div class="trip-review-modal">

        <button
            type="button"
            class="trip-review-close"
            onclick="closeTripReviewModal()"
            aria-label="Close"
        >
            &times;
        </button>

        <div class="trip-review-icon">★</div>

        <h3 id="tripReviewTitle">
            <?= $existingReview ? 'Edit Your Review' : 'Rate Your Trip' ?>
        </h3>

        <p>
            How was your Al-Ahsa journey?
            Share your experience with us.
        </p>

        <?php if ($reviewMessageType === 'error'): ?>

            <div class="trip-review-message error">
                <?= trip_h($reviewMessage) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="my-itinerary.php?id=<?= (int)$itineraryId ?>">

            <input
                type="hidden"
                name="action"
                value="save_trip_review"
            >

            <input
                type="hidden"
                name="itinerary_id"
                value="<?= (int)$itineraryId ?>"
            >

            <input
                type="hidden"
                name="review_token"
                value="<?= htmlspecialchars(
                    $_SESSION['trip_review_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <div class="trip-review-rating">

                <?php for ($star = 5; $star >= 1; $star--): ?>

                    <input
                        type="radio"
                        id="trip-star-<?= $star ?>"
                        name="rating"
                        value="<?= $star ?>"
                        <?= $reviewFormRating === $star ? 'checked' : '' ?>
                        required
                    >

                    <label for="trip-star-<?= $star ?>" title="<?= $star ?> stars">
                        ★
                    </label>

                <?php endfor; ?>

            </div>

            <textarea
                class="trip-review-textarea"
                name="comment"
                maxlength="2000"
                placeholder="Write your experience about this trip..."
            ><?= trip_h($reviewFormComment) ?></textarea>

            <button
                type="submit"
                class="trip-review-submit"
            >
                <?= $existingReview ? 'Update Review' : 'Submit Review' ?>
            </button>

        </form>

    </div>

</div>

<!-- Delete confirmation modal -->
<div
    class="trip-delete-overlay"
    id="tripDeleteOverlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tripDeleteTitle"
    onclick="closeTripDeleteModal(event)"
>

    <div class="trip-delete-modal">

        <div class="trip-delete-icon">🗑</div>

        <h3 id="tripDeleteTitle">Delete This Trip?</h3>

        <p>
            Are you sure you want to delete this trip?
            Its saved itinerary details will also be deleted.
            This action cannot be undone.
        </p>

        <form method="POST" action="my-trips.php">

            <input
                type="hidden"
                name="action"
                value="delete_trip"
            >

            <input
                type="hidden"
                name="itinerary_id"
                value="<?= (int)$itineraryId ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['trip_delete_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <div class="trip-delete-modal-actions">

                <button
                    type="button"
                    class="trip-modal-cancel"
                    onclick="closeTripDeleteModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="trip-modal-confirm"
                >
                    Delete
                </button>

            </div>

        </form>

    </div>

</div>

<script>
function openTripDeleteModal() {
    document
        .getElementById('tripDeleteOverlay')
        .classList.add('active');

    document.body.style.overflow = 'hidden';
}

function closeTripDeleteModal(event) {
    if (
        event &&
        event.target !== document.getElementById('tripDeleteOverlay')
    ) {
        return;
    }

    document
        .getElementById('tripDeleteOverlay')
        .classList.remove('active');

    document.body.style.overflow = '';
}

function openTripReviewModal() {
    document
        .getElementById('tripReviewOverlay')
        .classList.add('active');

    document.body.style.overflow = 'hidden';
}

function closeTripReviewModal(event) {
    if (
        event &&
        event.target !== document.getElementById('tripReviewOverlay')
    ) {
        return;
    }

    document
        .getElementById('tripReviewOverlay')
        .classList.remove('active');

    document.body.style.overflow = '';
}

/* Download itinerary as PDF using the browser print dialog */
function downloadTripPDF() {
    const originalTitle = document.title;

    document.title = <?= json_encode(
        $title . ' - Al-Ahsa Trip',
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    ) ?>;

    window.print();

    window.addEventListener('afterprint', function restoreTitle() {
        document.title = originalTitle;
        window.removeEventListener('afterprint', restoreTitle);
    }, { once: true });
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTripDeleteModal();
        closeTripReviewModal();
    }
});

/* Reopen review modal if validation or saving failed */
<?php if ($reviewMessageType === 'error'): ?>
document.addEventListener('DOMContentLoaded', function() {
    openTripReviewModal();
});
<?php endif; ?>
</script>

<?php if (isset($_GET['review']) && $_GET['review'] === 'success'): ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const message = document.createElement('div');

        message.className = 'trip-review-message';
        message.textContent = 'Your trip review has been saved successfully.';
        message.style.position = 'fixed';
        message.style.top = '25px';
        message.style.left = '50%';
        message.style.transform = 'translateX(-50%)';
        message.style.zIndex = '100001';
        message.style.width = 'max-content';
        message.style.maxWidth = '90%';
        message.style.background = '#edf7f0';
        message.style.color = '#287344';
        message.style.boxShadow = '0 5px 20px rgba(0,0,0,0.12)';

        document.body.appendChild(message);

        setTimeout(function() {
            message.remove();
        }, 3500);
    });
    </script>

<?php endif; ?>

<?php trip_page_footer(); ?>