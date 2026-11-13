<?php
require_once __DIR__ . '/trip-common.php';
trip_require_tourist();

$touristId = (int)$_SESSION['tourist_id'];

/* CSRF token */
if (empty($_SESSION['trip_delete_token'])) {
    $_SESSION['trip_delete_token'] = bin2hex(random_bytes(32));
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
</style>

<section class="trip-page result-page">

    <div class="result-heading">

        <a class="back-link" href="itinerary.php">
            ← Trip Options
        </a>

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

    <div class="result-actions">

        <a class="trip-secondary-btn" href="my-trips.php">
            My Trips
        </a>

        <a class="trip-secondary-btn" href="itinerary.php">
            Trip Options
        </a>

        <a class="trip-primary-btn" href="survey.php">
            Create Another Trip <span>→</span>
        </a>

        <button
            type="button"
            class="trip-delete-btn"
            onclick="openTripDeleteModal()"
        >
            Delete This Trip
        </button>

    </div>

</section>

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

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTripDeleteModal();
    }
});
</script>

<?php trip_page_footer(); ?>