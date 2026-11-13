<?php
require_once __DIR__ . '/trip-common.php';
require_once __DIR__ . '/db.php';

trip_require_tourist();

$touristId = (int)($_SESSION['tourist_id'] ?? 0);

$suggestedTrips = [
    'oasis' => [
        'title' => 'Oasis & Nature Escape',
        'subtitle' => 'Discover the natural beauty of Al-Ahsa',
        'description' => 'Enjoy three days exploring green spaces, natural scenery, beautiful farms and outdoor experiences.',
        'days' => 3,
        'icon' => '🌴',
        'keywords' => [
            'nature', 'oasis', 'park', 'garden', 'mountain',
            'natural', 'farm', 'عين', 'واحة', 'حديقة', 'جبل', 'مزرعة'
        ],
        'reason' => 'Selected for your nature and outdoor experience.'
    ],

    'heritage' => [
        'title' => 'Heritage & Culture Journey',
        'subtitle' => 'Explore the history and culture of Al-Ahsa',
        'description' => 'Spend five days discovering cultural attractions, heritage locations, museums and traditional places.',
        'days' => 5,
        'icon' => '🏛️',
        'keywords' => [
            'heritage', 'history', 'culture', 'museum',
            'historic', 'traditional', 'تراث', 'تاريخ',
            'ثقافة', 'متحف', 'قصر', 'تاريخي'
        ],
        'reason' => 'Selected for a heritage and cultural experience.'
    ],

    'family' => [
        'title' => 'Family Fun Adventure',
        'subtitle' => 'Enjoy memorable moments with your family',
        'description' => 'Enjoy a full seven-day family adventure with entertaining attractions, activities and enjoyable experiences.',
        'days' => 7,
        'icon' => '🎡',
        'keywords' => [
            'family', 'entertainment', 'fun', 'play',
            'amusement', 'kids', 'ألعاب', 'ترفيه',
            'عائلي', 'أطفال', 'تسلية'
        ],
        'reason' => 'Selected for a fun and entertaining family experience.'
    ],

    'flavors' => [
        'title' => 'Local Flavors & Markets',
        'subtitle' => 'Taste, shop and discover local experiences',
        'description' => 'Enjoy a one-day experience exploring local restaurants, cafés, traditional markets and shopping destinations.',
        'days' => 1,
        'icon' => '☕',
        'keywords' => [
            'restaurant', 'cafe', 'coffee', 'market',
            'food', 'shopping', 'مطعم', 'مقهى',
            'قهوة', 'سوق', 'تسوق', 'مطاعم'
        ],
        'reason' => 'Selected for local food and shopping experiences.'
    ]
];

/*
 * Fetch published places from the database.
 */
function suggested_get_places($conn)
{
    $places = [];

    $sql = "
        SELECT
            Place_ID,
            Name,
            Place_Type,
            Short_Description,
            Description,
            Location,
            Image,
            Opening_Hours,
            Website
        FROM Places
        WHERE Is_Published = 1
    ";

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $places[] = $row;
        }
    }

    return $places;
}

/*
 * Select places according to the selected trip theme.
 * Matching places appear first.
 * Other published places fill any remaining slots.
 */
function suggested_select_places($allPlaces, $keywords, $limit)
{
    $ranked = [];

    foreach ($allPlaces as $place) {

        $searchText = implode(' ', [
            $place['Name'] ?? '',
            $place['Place_Type'] ?? '',
            $place['Short_Description'] ?? '',
            $place['Description'] ?? ''
        ]);

        $score = 0;

        foreach ($keywords as $keyword) {
            if (
                $keyword !== '' &&
                mb_stripos($searchText, $keyword) !== false
            ) {
                $score++;
            }
        }

        $ranked[] = [
            'place' => $place,
            'score' => $score
        ];
    }

    usort($ranked, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $selected = [];

    foreach ($ranked as $item) {

        if (count($selected) >= $limit) {
            break;
        }

        $selected[] = $item['place'];
    }

    return $selected;
}

/*
 * Get selected trip from URL.
 */
$tripKey = $_GET['trip'] ?? '';
$selectedTrip = $suggestedTrips[$tripKey] ?? null;

$errorMessage = '';

/*
 * Save selected suggested trip.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedKey = $_POST['trip_key'] ?? '';

    if (!isset($suggestedTrips[$postedKey])) {

        $errorMessage = 'The selected trip is not available.';

    } else {

        $trip = $suggestedTrips[$postedKey];

        $allPlaces = suggested_get_places($conn);

        // 3 places per day.
        $placesToSave = suggested_select_places(
            $allPlaces,
            $trip['keywords'],
            $trip['days'] * 3
        );

        if (empty($placesToSave)) {

            $errorMessage = 'There are currently no published places available. Please try again later.';

        } else {

            try {

                $conn->begin_transaction();

                /*
                 * Create a survey record for the suggested trip.
                 */
                $surveyStmt = $conn->prepare("
                    INSERT INTO survey (tourist_id, create_at)
                    VALUES (?, NOW())
                ");

                if (!$surveyStmt) {
                    throw new Exception('Could not create survey record.');
                }

                $surveyStmt->bind_param("i", $touristId);
                $surveyStmt->execute();

                $surveyId = $conn->insert_id;
                $surveyStmt->close();

                /*
                 * Create itinerary.
                 */
                $itineraryStmt = $conn->prepare("
                    INSERT INTO itinerary
                        (tourist_id, survey_id, days, create_at)
                    VALUES (?, ?, ?, NOW())
                ");

                if (!$itineraryStmt) {
                    throw new Exception('Could not create itinerary.');
                }

                $days = (int)$trip['days'];

                $itineraryStmt->bind_param(
                    "iii",
                    $touristId,
                    $surveyId,
                    $days
                );

                $itineraryStmt->execute();

                $itineraryId = $conn->insert_id;
                $itineraryStmt->close();

                /*
                 * Add places to itinerary.
                 */
                $itemStmt = $conn->prepare("
                    INSERT INTO itinerary_items
                    (
                        itinerary_id,
                        day_number,
                        place_id,
                        start_time,
                        end_time,
                        sequence_number,
                        reason_chosen
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$itemStmt) {
                    throw new Exception('Could not prepare itinerary items.');
                }

                $times = [
                    ['09:00:00', '10:30:00'],
                    ['11:00:00', '12:30:00'],
                    ['16:00:00', '17:30:00']
                ];

                foreach ($placesToSave as $index => $place) {

                    $dayNumber = (int)floor($index / 3) + 1;

                    $sequenceNumber = ($index % 3) + 1;

                    $placeId = (int)$place['Place_ID'];

                    $startTime = $times[$index % 3][0];

                    $endTime = $times[$index % 3][1];

                    $reason = $trip['reason'];

                    $itemStmt->bind_param(
                        "iiissis",
                        $itineraryId,
                        $dayNumber,
                        $placeId,
                        $startTime,
                        $endTime,
                        $sequenceNumber,
                        $reason
                    );

                    $itemStmt->execute();
                }

                $itemStmt->close();

                $conn->commit();

                /*
                 * Save latest trip details in session.
                 */
                $_SESSION['latest_itinerary_id'] = $itineraryId;

                $_SESSION['latest_itinerary_title'] = $trip['title'];

                $_SESSION['latest_itinerary_intro'] = $trip['description'];

                /*
                 * Open saved trip.
                 */
                header(
                    'Location: my-itinerary.php?id=' . $itineraryId
                );

                exit;

            } catch (Throwable $e) {

                $conn->rollback();

                error_log(
                    'Suggested trip save error: ' . $e->getMessage()
                );

                $errorMessage = 'Sorry, we could not save this trip. Please try again.';
            }
        }
    }
}

/*
 * Load published places for displaying trip details.
 */
$allPlaces = suggested_get_places($conn);

trip_page_header('suggested-trips');
?>

<section class="trip-page">

    <?php if ($selectedTrip): ?>

        <?php

        $tripPlaces = suggested_select_places(
            $allPlaces,
            $selectedTrip['keywords'],
            $selectedTrip['days'] * 3
        );

        $placesByDay = [];

        foreach ($tripPlaces as $index => $place) {

            $day = (int)floor($index / 3) + 1;

            $placesByDay[$day][] = $place;
        }

        ?>

        <div class="trip-section-heading">

            <span class="eyebrow">SUGGESTED TRIP DETAILS</span>

            <h2>
                <?= htmlspecialchars(
                    $selectedTrip['title'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </h2>

            <p>
                <?= htmlspecialchars(
                    $selectedTrip['description'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </p>

        </div>

        <div class="trip-trust">

            <span>✦</span>

            <?= (int)$selectedTrip['days'] ?>

            <?= (int)$selectedTrip['days'] === 1 ? 'Day' : 'Days' ?>

            · <?= count($tripPlaces) ?> Places

        </div>

        <?php if (empty($tripPlaces)): ?>

            <div class="empty-plan">

                <h3>No published places available yet</h3>

                <p>Please check again later.</p>

            </div>

        <?php else: ?>

            <?php foreach ($placesByDay as $dayNumber => $dayPlaces): ?>

                <div class="trip-section-heading">

                    <span class="eyebrow">YOUR JOURNEY</span>

                    <h2>Day <?= (int)$dayNumber ?></h2>

                </div>

                <div class="trip-steps">

                    <?php foreach ($dayPlaces as $index => $place): ?>

                        <?php

                        $timeLabels = [
                            '09:00 AM – 10:30 AM',
                            '11:00 AM – 12:30 PM',
                            '04:00 PM – 05:30 PM'
                        ];

                        $timeIndex = $index % 3;

                        ?>

                        <article class="trip-step">

                            <span class="step-number">

                                <?= str_pad(
                                    (string)($index + 1),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>

                            </span>

                            <div class="step-icon">⌖</div>

                            <h3>
                                <?= htmlspecialchars(
                                    $place['Name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $timeLabels[$timeIndex],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                            <?php if (!empty($place['Short_Description'])): ?>

                                <p>
                                    <?= htmlspecialchars(
                                        $place['Short_Description'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            <?php elseif (!empty($place['Description'])): ?>

                                <p>
                                    <?= htmlspecialchars(
                                        mb_strimwidth(
                                            $place['Description'],
                                            0,
                                            150,
                                            '...'
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($place['Location'])): ?>

                                <p>
                                    <strong>Location:</strong>

                                    <?= htmlspecialchars(
                                        $place['Location'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($place['Opening_Hours'])): ?>

                                <p>
                                    <strong>Opening Hours:</strong>

                                    <?= htmlspecialchars(
                                        $place['Opening_Hours'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>

                            <?php endif; ?>

                            <?php if (!empty($place['Website'])): ?>

                                <p>
                                    <a
                                        href="<?= htmlspecialchars(
                                            $place['Website'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Visit Website →
                                    </a>
                                </p>

                            <?php endif; ?>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endforeach; ?>

            <div class="result-actions">

                <a
                    class="trip-primary-btn"
                    href="suggested-trips.php"
                >
                    ← Back to Suggested Trips
                </a>

                <form
                    method="POST"
                    action="suggested-trips.php"
                >

                    <input
                        type="hidden"
                        name="trip_key"
                        value="<?= htmlspecialchars(
                            $tripKey,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <button
                        class="trip-primary-btn"
                        type="submit"
                    >
                        Save to My Trips <span>→</span>
                    </button>

                </form>

            </div>

        <?php endif; ?>

    <?php else: ?>

        <div class="trip-section-heading">

            <span class="eyebrow">EXPLORE AL-AHSA</span>

            <h2>Suggested Trips</h2>

            <p>
                Choose a journey that matches your interests and discover
                beautiful experiences around Al-Ahsa.
            </p>

        </div>

        <?php if ($errorMessage): ?>

            <div class="empty-plan">

                <p>
                    <?= htmlspecialchars(
                        $errorMessage,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </p>

            </div>

        <?php endif; ?>

        <div class="trip-steps">

            <?php $number = 1; ?>

            <?php foreach ($suggestedTrips as $key => $trip): ?>

                <article class="trip-step">

                    <span class="step-number">

                        <?= str_pad(
                            (string)$number,
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) ?>

                    </span>

                    <div class="step-icon">
                        <?= $trip['icon'] ?>
                    </div>

                    <h3>
                        <?= htmlspecialchars(
                            $trip['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars(
                            $trip['subtitle'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        <?= htmlspecialchars(
                            $trip['description'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p>
                        <strong>

                            <?= (int)$trip['days'] ?>

                            <?= (int)$trip['days'] === 1
                                ? 'Day'
                                : 'Days' ?>

                        </strong>
                    </p>

                    <a
                        class="trip-primary-btn"
                        href="suggested-trips.php?trip=<?= urlencode($key) ?>"
                    >
                        View Trip <span>→</span>
                    </a>

                </article>

                <?php $number++; ?>

            <?php endforeach; ?>

        </div>

        <div class="result-actions">

            <a
                class="trip-primary-btn"
                href="itinerary.php"
            >
                ← Back to Trip Options
            </a>

            <a
                class="trip-primary-btn"
                href="my-trips.php"
            >
                My Trips <span>→</span>
            </a>

        </div>

    <?php endif; ?>

</section>

<?php trip_page_footer(); ?>