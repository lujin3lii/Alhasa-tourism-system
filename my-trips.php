<?php
require_once __DIR__ . '/trip-common.php';
require_once __DIR__ . '/db.php';

trip_require_tourist();

$touristId = (int)($_SESSION['tourist_id'] ?? 0);

/* CSRF protection */
if (empty($_SESSION['trip_delete_token'])) {
    $_SESSION['trip_delete_token'] = bin2hex(random_bytes(32));
}

/* Flash message */
$deleteMessage = $_SESSION['trip_delete_message'] ?? '';
unset($_SESSION['trip_delete_message']);

/* =========================================
   SAFE DELETE - SELECTED TRIP ONLY
   Deletes from:
   1. itinerary_items
   2. itinerary

   Does NOT delete or modify:
   Places, survey, survey_answers,
   or any other project tables.
========================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_trip'
) {
    $postedToken = $_POST['csrf_token'] ?? '';
    $tripId = filter_input(INPUT_POST, 'itinerary_id', FILTER_VALIDATE_INT);

    if (
        !is_string($postedToken)
        || !hash_equals($_SESSION['trip_delete_token'], $postedToken)
        || !$tripId
        || $tripId < 1
    ) {
        $_SESSION['trip_delete_message'] =
            'Invalid request. Please try again.';
    } else {

        $transactionStarted = false;

        try {

            if (!$conn->begin_transaction()) {
                throw new Exception('Could not start transaction.');
            }

            $transactionStarted = true;

            /* Verify ownership and lock the selected trip */
            $checkStmt = $conn->prepare("
                SELECT itinerary_id
                FROM itinerary
                WHERE itinerary_id = ?
                  AND tourist_id = ?
                LIMIT 1
                FOR UPDATE
            ");

            if (!$checkStmt) {
                throw new Exception('Could not verify trip ownership.');
            }

            $checkStmt->bind_param("ii", $tripId, $touristId);

            if (!$checkStmt->execute()) {
                throw new Exception('Could not verify trip.');
            }

            $tripData = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if (!$tripData) {
                throw new Exception('Trip not found or not owned by this tourist.');
            }

            /* Delete only this trip's itinerary items */
            $itemsStmt = $conn->prepare("
                DELETE FROM itinerary_items
                WHERE itinerary_id = ?
            ");

            if (!$itemsStmt) {
                throw new Exception('Could not prepare item deletion.');
            }

            $itemsStmt->bind_param("i", $tripId);

            if (!$itemsStmt->execute()) {
                throw new Exception('Could not delete trip items.');
            }

            $itemsStmt->close();

            /* Delete only the selected itinerary */
            $itineraryStmt = $conn->prepare("
                DELETE FROM itinerary
                WHERE itinerary_id = ?
                  AND tourist_id = ?
            ");

            if (!$itineraryStmt) {
                throw new Exception('Could not prepare trip deletion.');
            }

            $itineraryStmt->bind_param("ii", $tripId, $touristId);

            if (!$itineraryStmt->execute()) {
                throw new Exception('Could not delete trip.');
            }

            if ($itineraryStmt->affected_rows !== 1) {
                throw new Exception('Trip was not deleted.');
            }

            $itineraryStmt->close();

            /* No other tables are explicitly deleted or modified */

            if (!$conn->commit()) {
                throw new Exception('Could not complete deletion.');
            }

            $transactionStarted = false;

            /* Clear latest-trip session only if it is the deleted trip */
            if (
                (int)($_SESSION['latest_itinerary_id'] ?? 0) === $tripId
            ) {
                unset(
                    $_SESSION['latest_itinerary_id'],
                    $_SESSION['latest_itinerary_title'],
                    $_SESSION['latest_itinerary_intro']
                );
            }

            /* Rotate CSRF token after successful deletion */
            $_SESSION['trip_delete_token'] =
                bin2hex(random_bytes(32));

            $_SESSION['trip_delete_message'] =
                'Your trip has been deleted successfully.';

        } catch (Throwable $e) {

            if ($transactionStarted) {
                $conn->rollback();
            }

            error_log('Trip deletion error: ' . $e->getMessage());

            $_SESSION['trip_delete_message'] =
                'Sorry, we could not delete this trip. Please try again.';
        }
    }

    header('Location: my-trips.php');
    exit;
}

/* =========================================
   GET ALL TRIPS BELONGING TO THIS TOURIST
========================================= */

$sql = "
    SELECT
        i.itinerary_id,
        i.days,
        i.create_at,
        COUNT(DISTINCT ii.item_id) AS places_count,
        GROUP_CONCAT(
            DISTINCT p.Name
            ORDER BY ii.day_number, ii.sequence_number
            SEPARATOR ' · '
        ) AS place_names
    FROM itinerary i
    LEFT JOIN itinerary_items ii
        ON ii.itinerary_id = i.itinerary_id
    LEFT JOIN Places p
        ON p.Place_ID = ii.place_id
    WHERE i.tourist_id = ?
    GROUP BY i.itinerary_id, i.days, i.create_at
    ORDER BY i.create_at DESC, i.itinerary_id DESC
";

$stmt = $conn->prepare($sql);
$trips = [];

if ($stmt) {
    $stmt->bind_param("i", $touristId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $trips[] = $row;
    }

    $stmt->close();
}

/* Total trips for automatic display numbering */
$totalTrips = count($trips);

trip_page_header('my-trips');
?>

<style>
/* Trips layout */
.trip-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 22px;
    align-items: stretch;
}

.trip-step {
    position: relative;
    border-radius: 15px;
    padding: 24px;
    height: 100%;
    box-sizing: border-box;
}

/* Trip number */
.trip-step .step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    height: 38px;
    padding: 0 8px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14px;
    margin-bottom: 12px;
}

/* Trip actions */
.trip-delete-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 18px;
    align-items: center;
}

.trip-delete-actions .trip-primary-btn {
    flex: 1;
    text-align: center;
}

.trip-delete-btn {
    border: 1px solid #c96b68;
    color: #b94a48;
    background: transparent;
    padding: 11px 18px;
    border-radius: 9px;
    cursor: pointer;
    font: inherit;
    font-size: 14px;
    transition: 0.2s ease;
}

.trip-delete-btn:hover {
    background: #fff1f0;
}

/* Delete confirmation */
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

.trip-delete-message {
    padding: 13px 17px;
    margin-bottom: 22px;
    border-radius: 10px;
    background: #f0f6f1;
    color: #315c43;
    font-size: 14px;
}

/* Mobile */
@media (max-width: 600px) {
    .trip-steps {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .trip-step {
        padding: 20px;
    }

    .trip-delete-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .trip-delete-actions .trip-primary-btn,
    .trip-delete-btn {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }
}
</style>

<section class="trip-page">

    <div class="trip-section-heading">
        <span class="eyebrow">YOUR JOURNEYS</span>
        <h2>My Trips</h2>
        <p>All your saved Al-Ahsa journeys in one place.</p>
    </div>

    <?php if (!empty($deleteMessage)): ?>
        <div class="trip-delete-message">
            <?= htmlspecialchars($deleteMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (empty($trips)): ?>

        <div class="empty-plan">
            <div class="step-icon">⌖</div>

            <h3>You haven't saved any trips yet</h3>

            <p>
                Start planning your own journey or explore our suggested trips.
            </p>

            <div class="result-actions">
                <a class="trip-primary-btn" href="itinerary.php">
                    Explore Trip Options <span>→</span>
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="trip-steps">

            <?php foreach ($trips as $index => $trip): ?>

                <?php
                /* Newest trip gets the highest display number */
                $tripNumber = $totalTrips - $index;
                ?>

                <article class="trip-step">

                    <span class="step-number">
                        <?= str_pad(
                            (string)$tripNumber,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) ?>
                    </span>

                    <div class="step-icon">✈️</div>

                    <h3>
                        Trip #<?= $tripNumber ?>
                    </h3>

                    <p>
                        <?= (int)$trip['days'] ?>
                        <?= (int)$trip['days'] === 1 ? 'Day' : 'Days' ?>
                        ·
                        <?= (int)$trip['places_count'] ?>
                        <?= (int)$trip['places_count'] === 1 ? 'Place' : 'Places' ?>
                    </p>

                    <p>
                        <strong>Created:</strong>
                        <?= htmlspecialchars(
                            date('M d, Y', strtotime($trip['create_at'])),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <?php if (!empty($trip['place_names'])): ?>
                        <p>
                            <?= htmlspecialchars(
                                mb_strimwidth(
                                    $trip['place_names'],
                                    0,
                                    130,
                                    '...'
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>
                    <?php endif; ?>

                    <div class="trip-delete-actions">

                        <a
                            class="trip-primary-btn"
                            href="my-itinerary.php?id=<?= (int)$trip['itinerary_id'] ?>"
                        >
                            View Trip <span>→</span>
                        </a>

                        <button
                            type="button"
                            class="trip-delete-btn"
                            onclick="openTripDeleteModal(<?= (int)$trip['itinerary_id'] ?>)"
                        >
                            Delete Trip
                        </button>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <div class="result-actions">
        <a class="trip-primary-btn" href="itinerary.php">
            Back to Trip Options <span>→</span>
        </a>
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
            Only this trip and its saved itinerary details will be deleted.
            Your places and other project data will not be intentionally deleted.
            This action cannot be undone.
        </p>

        <form method="POST" action="my-trips.php">

            <input type="hidden" name="action" value="delete_trip">

            <input
                type="hidden"
                name="itinerary_id"
                id="deleteTripId"
                value=""
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
function openTripDeleteModal(tripId) {
    document.getElementById('deleteTripId').value = tripId;

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