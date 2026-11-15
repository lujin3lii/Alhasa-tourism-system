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
 * Fetch seasonal event dates.
 */
function suggested_get_seasonal_events($conn)
{
    $events = [];

    $result = $conn->query("
        SELECT Place_ID, Start_Date, End_Date
        FROM seasonal_events
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[(int)$row['Place_ID']][] = $row;
        }
    }

    return $events;
}

/*
 * Check whether a place is available during the trip date.
 */
function suggested_is_in_season($placeId, $date, $seasonalEvents)
{
    if (empty($seasonalEvents[$placeId])) {
        return true;
    }

    foreach ($seasonalEvents[$placeId] as $event) {

        $startDate = $event['Start_Date'] ?? '';
        $endDate = $event['End_Date'] ?? '';

        if (
            $startDate !== '' &&
            $endDate !== '' &&
            $date >= $startDate &&
            $date <= $endDate
        ) {
            return true;
        }
    }

    return false;
}

/*
 * Convert a time such as 04:00 PM or 16:00 into minutes.
 */
function suggested_time_minutes($time)
{
    $time = trim($time);

    if (!preg_match(
        '/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/i',
        $time,
        $matches
    )) {
        return null;
    }

    $hour = (int)$matches[1];
    $minute = (int)$matches[2];
    $period = strtoupper($matches[3] ?? '');

    if ($hour > 23 || $minute > 59) {
        return null;
    }

    if ($period === 'PM' && $hour < 12) {
        $hour += 12;
    } elseif ($period === 'AM' && $hour === 12) {
        $hour = 0;
    }

    return ($hour * 60) + $minute;
}

/*
 * Check whether a place is open during the proposed visit time.
 */
function suggested_is_open_at($openingHours, $date, $startTime, $endTime)
{
    $openingHours = trim((string)$openingHours);

    if ($openingHours === '') {
        return true;
    }

    $weekday = strtolower(date('l', strtotime($date)));

    $arabicWeekdays = [
        'sunday' => 'الأحد',
        'monday' => 'الإثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
        'saturday' => 'السبت'
    ];

    $weekdayName = $arabicWeekdays[$weekday] ?? '';

    $closedPatterns = [
        '/closed\s+(?:on\s+)?' . preg_quote($weekday, '/') . '/i',
        '/مغلق(?:ة)?\s+(?:يوم\s+)?' . preg_quote($weekdayName, '/') . '/u'
    ];

    foreach ($closedPatterns as $pattern) {
        if (preg_match($pattern, $openingHours)) {
            return false;
        }
    }

    if (
        preg_match('/closed\s+every\s+day/i', $openingHours) ||
        preg_match('/مغلق(?:ة)?\s+يومي[اً]/u', $openingHours)
    ) {
        return false;
    }

    preg_match_all(
        '/(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s*[-–]\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i',
        $openingHours,
        $matches,
        PREG_SET_ORDER
    );

    if (empty($matches)) {
        return true;
    }

    $visitStart = suggested_time_minutes($startTime);
    $visitEnd = suggested_time_minutes($endTime);

    if ($visitStart === null || $visitEnd === null) {
        return true;
    }

    foreach ($matches as $range) {

        $open = suggested_time_minutes($range[1]);
        $close = suggested_time_minutes($range[2]);

        if ($open === null || $close === null) {
            continue;
        }

        if ($close <= $open) {
            $close += 1440;
        }

        if ($visitEnd <= $visitStart) {
            $visitEnd += 1440;
        }

        if (
            $visitStart >= $open &&
            $visitEnd <= $close
        ) {
            return true;
        }

        if (
            $visitStart + 1440 >= $open &&
            $visitEnd + 1440 <= $close
        ) {
            return true;
        }
    }

    return false;
}

/*
 * Get a simple keyword score for each place.
 */
function suggested_place_score($place, $keywords)
{
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

    return $score;
}

/*
 * Build a varied daily plan.
 */
function suggested_build_plan(
    $allPlaces,
    $keywords,
    $numberOfDays,
    $seasonalEvents
) {
    $ranked = [];

    foreach ($allPlaces as $place) {

        $ranked[] = [
            'place' => $place,
            'score' => suggested_place_score($place, $keywords)
        ];
    }

    usort($ranked, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $times = [
        ['09:00:00', '10:30:00'],
        ['11:00:00', '12:30:00'],
        ['16:00:00', '17:30:00']
    ];

    $plan = [];
    $usedPlaces = [];

    for ($dayNumber = 1; $dayNumber <= $numberOfDays; $dayNumber++) {

        $dayDate = date(
            'Y-m-d',
            strtotime('+' . ($dayNumber - 1) . ' days')
        );

        $usedTypes = [];

        for ($slot = 0; $slot < 3; $slot++) {

            $startTime = $times[$slot][0];
            $endTime = $times[$slot][1];

            $selected = null;

            for ($pass = 0; $pass < 2 && !$selected; $pass++) {

                foreach ($ranked as $rankedPlace) {

                    $place = $rankedPlace['place'];

                    $placeId = (int)$place['Place_ID'];

                    if (isset($usedPlaces[$placeId])) {
                        continue;
                    }

                    if (!suggested_is_in_season(
                        $placeId,
                        $dayDate,
                        $seasonalEvents
                    )) {
                        continue;
                    }

                    if (!suggested_is_open_at(
                        $place['Opening_Hours'] ?? '',
                        $dayDate,
                        $startTime,
                        $endTime
                    )) {
                        continue;
                    }

                    $type = strtolower(trim($place['Place_Type'] ?? ''));

                    if ($pass === 0 && isset($usedTypes[$type])) {
                        continue;
                    }

                    $selected = $place;
                    break;
                }
            }

            if ($selected) {

                $placeId = (int)$selected['Place_ID'];

                $usedPlaces[$placeId] = true;

                $type = strtolower(trim($selected['Place_Type'] ?? ''));

                $usedTypes[$type] = true;

                $plan[$dayNumber][] = [
                    'place' => $selected,
                    'start_time' => $startTime,
                    'end_time' => $endTime
                ];
            }
        }
    }

    return $plan;
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
        $seasonalEvents = suggested_get_seasonal_events($conn);

        $plan = suggested_build_plan(
            $allPlaces,
            $trip['keywords'],
            $trip['days'],
            $seasonalEvents
        );

        $placesCount = 0;

        foreach ($plan as $dayItems) {
            $placesCount += count($dayItems);
        }

        if ($placesCount === 0) {

            $errorMessage = 'There are currently no suitable published places available for this trip. Please try again later.';

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

                foreach ($plan as $dayNumber => $dayItems) {

                    foreach ($dayItems as $index => $item) {

                        $place = $item['place'];

                        $placeId = (int)$place['Place_ID'];

                        $startTime = $item['start_time'];
                        $endTime = $item['end_time'];

                        $sequenceNumber = $index + 1;

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
$seasonalEvents = suggested_get_seasonal_events($conn);

/*
 * Build the selected trip preview.
 */
$plan = [];
$placesByDay = [];
$tripPlaces = [];

if ($selectedTrip) {

    $plan = suggested_build_plan(
        $allPlaces,
        $selectedTrip['keywords'],
        $selectedTrip['days'],
        $seasonalEvents
    );

    foreach ($plan as $dayNumber => $dayItems) {
        foreach ($dayItems as $item) {
            $placesByDay[$dayNumber][] = $item;
            $tripPlaces[] = $item['place'];
        }
    }
}

trip_page_header('suggested-trips');
?>

<section class="trip-page">

    <?php if ($selectedTrip): ?>

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

                <h3>No suitable published places available</h3>

                <p>
                    Please check again later.
                </p>

            </div>

        <?php else: ?>

            <?php foreach ($placesByDay as $dayNumber => $dayPlaces): ?>

                <div class="trip-section-heading">

                    <span class="eyebrow">YOUR JOURNEY</span>

                    <h2>Day <?= (int)$dayNumber ?></h2>

                </div>

                <div class="trip-steps">

                    <?php foreach ($dayPlaces as $index => $item): ?>

                        <?php $place = $item['place']; ?>

                        <?php

                        $startTime = date(
                            'h:i A',
                            strtotime($item['start_time'])
                        );

                        $endTime = date(
                            'h:i A',
                            strtotime($item['end_time'])
                        );

                        $timeLabel = $startTime . ' – ' . $endTime;

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
                                    $timeLabel,
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

        <?php endif; ?>

        <div class="result-actions">

            <a
                class="trip-primary-btn"
                href="suggested-trips.php"
            >
                ← Back to Suggested Trips
            </a>

            <?php if (!empty($tripPlaces)): ?>

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

            <?php endif; ?>

        </div>

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