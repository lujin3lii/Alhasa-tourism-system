<?php
require_once __DIR__ . '/trip-common.php';
trip_require_tourist();
require_once __DIR__ . '/config/ai-config.php';

/* Redirect back to survey with an error message. */
function trip_fail($message) {
    $_SESSION['trip_error'] = $message;
    header('Location: survey.php');
    exit;
}

/* Convert Arabic-Indic digits to English digits. */
function trip_normalize_digits($value) {
    return strtr((string)$value, [
        '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4',
        '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9'
    ]);
}

/* Identify the trip duration from the selected survey option. */
function trip_get_days($questionText, $optionText) {
    $question = mb_strtolower($questionText, 'UTF-8');
    $option = mb_strtolower(trip_normalize_digits($optionText), 'UTF-8');

    $isDurationQuestion =
        strpos($question, 'duration') !== false ||
        strpos($question, 'how many days') !== false ||
        strpos($question, 'stay') !== false ||
        strpos($question, 'days') !== false ||
        mb_strpos($question, 'مدة') !== false ||
        mb_strpos($question, 'كم يوم') !== false ||
        mb_strpos($question, 'الإقامة') !== false ||
        mb_strpos($question, 'الاقامة') !== false;

    if (!$isDurationQuestion) return null;

    if (
        strpos($option, 'more than a week') !== false ||
        strpos($option, 'more than one week') !== false ||
        mb_strpos($option, 'أكثر من أسبوع') !== false ||
        mb_strpos($option, 'اكثر من اسبوع') !== false
    ) return 10;

    if (
        strpos($option, 'week') !== false ||
        mb_strpos($option, 'أسبوع') !== false ||
        mb_strpos($option, 'اسبوع') !== false
    ) return 7;

    if (preg_match('/(\d+)\s*[-–]\s*(\d+)/u', $option, $range)) {
        return (int)round(((int)$range[1] + (int)$range[2]) / 2);
    }

    if (preg_match('/(\d+)/u', $option, $number)) {
        $days = (int)$number[1];
        if ($days >= 1 && $days <= 14) return $days;
    }

    $arabicNumbers = [
        'يوم واحد' => 1, 'يومين' => 2,
        'ثلاثة أيام' => 3, 'ثلاث ايام' => 3,
        'أربعة أيام' => 4, 'اربعة ايام' => 4,
        'خمسة أيام' => 5, 'خمسة ايام' => 5,
        'ستة أيام' => 6, 'ست ايام' => 6
    ];

    foreach ($arabicNumbers as $word => $number) {
        if (mb_strpos($option, $word) !== false) return $number;
    }

    return null;
}

/* Convert a 12-hour or 24-hour time into minutes after midnight. */
function trip_time_minutes($time) {
    $time = trim(strtoupper(str_replace('.', '', $time)));

    if (!preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)?$/', $time, $m)) {
        return null;
    }

    $hour = (int)$m[1];
    $minute = (int)$m[2];
    $ampm = $m[3] ?? '';

    if ($minute > 59) return null;

    if ($ampm !== '') {
        if ($hour < 1 || $hour > 12) return null;
        if ($ampm === 'AM') {
            if ($hour === 12) $hour = 0;
        } else {
            if ($hour !== 12) $hour += 12;
        }
    } elseif ($hour > 23) {
        return null;
    }

    return ($hour * 60) + $minute;
}

/*
 * Check whether a place is open during the proposed visit.
 * Recognized examples:
 * 12:00 AM - 6:00 AM, 4:00 PM - 12:00 AM
 * Closed on Friday
 *
 * Unknown opening-hours formats are left eligible rather than
 * incorrectly excluding a place.
 */
function trip_place_is_open($openingHours, $date, $start, $end) {
    $hours = trim((string)$openingHours);

    if ($hours === '') return true;

    $dayName = strtolower(date('l', strtotime($date)));

    $dayNames = [
        'sunday' => ['sunday', 'الأحد'],
        'monday' => ['monday', 'الاثنين', 'الإثنين'],
        'tuesday' => ['tuesday', 'الثلاثاء'],
        'wednesday' => ['wednesday', 'الأربعاء', 'الاربعاء'],
        'thursday' => ['thursday', 'الخميس'],
        'friday' => ['friday', 'الجمعة'],
        'saturday' => ['saturday', 'السبت']
    ];

    $todayNames = $dayNames[$dayName] ?? [];

    foreach ($todayNames as $name) {
        if (preg_match('/closed\s+on\s+' . preg_quote($name, '/') . '/iu', $hours)) {
            return false;
        }

        if (preg_match('/' . preg_quote($name, '/') . '\s*:\s*closed/iu', $hours)) {
            return false;
        }

        if (preg_match('/' . preg_quote($name, '/') . '\s*-\s*closed/iu', $hours)) {
            return false;
        }

        if (preg_match('/' . preg_quote($name, '/') . '\s+مغلق/iu', $hours)) {
            return false;
        }
    }

    $startMinutes = trip_time_minutes($start);
    $endMinutes = trip_time_minutes($end);

    if ($startMinutes === null || $endMinutes === null) return true;

    preg_match_all(
        '/(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s*[-–]\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i',
        $hours,
        $matches,
        PREG_SET_ORDER
    );

    if (!$matches) return true;

    foreach ($matches as $match) {
        $open = trip_time_minutes($match[1]);
        $close = trip_time_minutes($match[2]);

        if ($open === null || $close === null) continue;

        /* Midnight closing time. */
        if ($close === 0 && $open > 0) $close = 1440;

        if ($startMinutes >= $open && $endMinutes <= $close) {
            return true;
        }
    }

    return false;
}

/* Check if a seasonal place has an event active on the visit date. */
function trip_place_is_in_season($place, $date) {
    if (empty($place['seasonal_events'])) return true;

    foreach ($place['seasonal_events'] as $event) {
        $startDate = $event['start_date'] ?? '';
        $endDate = $event['end_date'] ?? '';

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

/* Only accept POST requests. */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: survey.php');
    exit;
}

/* Check PHP cURL. */
if (!function_exists('curl_init')) {
    trip_fail('PHP cURL is not enabled. Enable the curl extension in XAMPP php.ini and restart Apache.');
}

/* Read answers submitted by survey.php. */
$submittedAnswers = $_POST['answers'] ?? [];

if (!is_array($submittedAnswers) || empty($submittedAnswers)) {
    trip_fail('Please complete the survey before generating your itinerary.');
}

/* Load the actual questions from the database. */
$questions = [];

$questionResult = $conn->query("
    SELECT Question_ID, Question_Text, Question_Type,
           Is_Required, Display_Order
    FROM survey_questions
    ORDER BY Display_Order ASC
");

if (!$questionResult) {
    trip_fail('Could not load survey questions: ' . $conn->error);
}

while ($row = $questionResult->fetch_assoc()) {
    $questions[(int)$row['Question_ID']] = $row;
}

if (!$questions) {
    trip_fail('No survey questions were found in the database.');
}

/* Load all available survey options. */
$options = [];

$optionResult = $conn->query("
    SELECT option_id, question_id, option_text
    FROM survey_options
    ORDER BY display_order ASC
");

if (!$optionResult) {
    trip_fail('Could not load survey options: ' . $conn->error);
}

while ($row = $optionResult->fetch_assoc()) {
    $options[(int)$row['option_id']] = $row;
}

/* Validate and prepare the answers. */
$answersForAI = [];
$answersToSave = [];
$days = null;

foreach ($questions as $questionId => $question) {
    $questionText = trim($question['Question_Text']);
    $questionType = strtolower(trim($question['Question_Type']));

    $isMultiple =
        strpos($questionType, 'multiple') !== false ||
        strpos($questionType, 'checkbox') !== false ||
        strpos($questionType, 'multi') !== false;

    $submitted = $submittedAnswers[$questionId] ?? [];

    if ($isMultiple) {
        if (!is_array($submitted)) $submitted = [];
    } else {
        if (is_array($submitted)) {
            $submitted = array_slice($submitted, 0, 1);
        } elseif ($submitted !== '' && $submitted !== null) {
            $submitted = [$submitted];
        } else {
            $submitted = [];
        }
    }

    $submitted = array_values(array_unique(array_filter(
        $submitted,
        function ($value) {
            return $value !== '' && $value !== null;
        }
    )));

    if ((int)$question['Is_Required'] === 1 && empty($submitted)) {
        trip_fail('Please answer all required survey questions.');
    }

    if (!$submitted) continue;

    $selectedTexts = [];

    foreach ($submitted as $selectedId) {
        if (!is_scalar($selectedId) || !ctype_digit((string)$selectedId)) {
            trip_fail('Invalid survey answer. Please submit the survey again.');
        }

        $optionId = (int)$selectedId;

        if (
            !isset($options[$optionId]) ||
            (int)$options[$optionId]['question_id'] !== $questionId
        ) {
            trip_fail('One of your selected answers is invalid. Please try again.');
        }

        $optionText = $options[$optionId]['option_text'];
        $selectedTexts[] = $optionText;

        $answersToSave[] = [
            'question_id' => $questionId,
            'option_id' => $optionId
        ];

        $detectedDays = trip_get_days($questionText, $optionText);

        if ($detectedDays !== null) $days = $detectedDays;
    }

    $answersForAI[] = [
        'question' => $questionText,
        'answers' => $selectedTexts
    ];
}

/* Validate trip duration. */
if (!$days || $days < 1 || $days > 14) {
    trip_fail('Could not identify the trip duration from your survey answers. Please check the duration question and try again.');
}

if (!$answersToSave) {
    trip_fail('No valid survey answers were received.');
}

/* Save preferences in the session. */
$preferences = [
    'days' => $days,
    'answers' => $answersForAI
];

$_SESSION['trip_survey'] = $preferences;

/*
 * Use a submitted trip date if one exists.
 * The current survey does not require a new database question.
 * If no date is submitted, the trip starts today.
 */
$tripStartDate = $_POST['trip_start_date'] ?? date('Y-m-d');

$dateObject = DateTime::createFromFormat('Y-m-d', $tripStartDate);

if (!$dateObject || $dateObject->format('Y-m-d') !== $tripStartDate) {
    $tripStartDate = date('Y-m-d');
}

/* Load real published places only. */
$sql = "
    SELECT Place_ID, Name, Place_Type, Description,
           Short_Description, Location, Image,
           Opening_Hours, Website
    FROM Places
    WHERE Is_Published = 1
    ORDER BY Place_Type, Name
";

$result = $conn->query($sql);

if (!$result) {
    trip_fail('Could not load published places from the Places table: ' . $conn->error);
}

$places = [];

while ($row = $result->fetch_assoc()) {
    $places[] = [
        'place_id' => (int)$row['Place_ID'],
        'name' => $row['Name'],
        'type' => $row['Place_Type'],
        'description' => $row['Short_Description'] ?: $row['Description'],
        'location' => $row['Location'],
        'opening_hours' => $row['Opening_Hours'],
        'website' => $row['Website'],
        'seasonal_events' => []
    ];
}

if (!$places) {
    trip_fail('There are no published places yet. Publish places in the Places table and try again.');
}

/* Load seasonal event dates using the existing seasonal_events table. */
$seasonalResult = $conn->query("
    SELECT Place_ID, Start_Date, End_Date
    FROM seasonal_events
");

if (!$seasonalResult) {
    trip_fail('Could not load seasonal event dates: ' . $conn->error);
}

$seasonalMap = [];

while ($event = $seasonalResult->fetch_assoc()) {
    $eventPlaceId = (int)$event['Place_ID'];

    $seasonalMap[$eventPlaceId][] = [
        'start_date' => $event['Start_Date'],
        'end_date' => $event['End_Date']
    ];
}

foreach ($places as &$place) {
    if (isset($seasonalMap[$place['place_id']])) {
        $place['seasonal_events'] = $seasonalMap[$place['place_id']];
    }
}
unset($place);

/* Build the actual date for each itinerary day. */
$dayDates = [];

for ($day = 1; $day <= $days; $day++) {
    $dayDates[$day] = date(
        'Y-m-d',
        strtotime('+' . ($day - 1) . ' days', strtotime($tripStartDate))
    );
}

/* Build fallback candidates based on survey preferences. */
$rankedPlaces = $places;

$keywords = [];

foreach ($answersForAI as $answer) {
    foreach ($answer['answers'] as $text) {
        $words = preg_split('/[\s،,؛;]+/u', (string)$text);

        foreach ($words as $word) {
            $word = trim($word);

            if (mb_strlen($word, 'UTF-8') >= 3) {
                $keywords[] = mb_strtolower($word, 'UTF-8');
            }
        }
    }
}

foreach ($rankedPlaces as &$place) {
    $placeText = mb_strtolower(
        $place['name'] . ' ' .
        $place['type'] . ' ' .
        $place['description'] . ' ' .
        $place['location'],
        'UTF-8'
    );

    $score = 0;

    foreach ($keywords as $keyword) {
        if (mb_strpos($placeText, $keyword) !== false) $score++;
    }

    $place['_score'] = $score;
}
unset($place);

usort($rankedPlaces, function ($a, $b) {
    return $b['_score'] <=> $a['_score'];
});

/*
 * Local fallback:
 * Spread different place types across each day.
 * Only select places that are in season and open at the proposed time.
 */
$createFallbackPlan = function () use (
    $rankedPlaces,
    $days,
    $dayDates
) {
    $dayPlans = [];

    $times = [
        ['09:00', '10:30'],
        ['11:00', '12:30'],
        ['16:00', '17:30']
    ];

    $usedPlaces = [];

    for ($day = 1; $day <= $days; $day++) {
        $dayPlans[$day] = [
            'day_number' => $day,
            'items' => []
        ];

        $usedTypes = [];

        foreach ($times as $time) {
            $chosen = null;

            /* Prefer a different place type in the same day. */
            foreach ([true, false] as $preferDifferentType) {
                foreach ($rankedPlaces as $place) {
                    $id = $place['place_id'];
                    $type = strtolower(trim((string)$place['type']));

                    if (isset($usedPlaces[$id])) continue;

                    if (
                        $preferDifferentType &&
                        $type !== '' &&
                        isset($usedTypes[$type])
                    ) {
                        continue;
                    }

                    if (!trip_place_is_in_season($place, $dayDates[$day])) {
                        continue;
                    }

                    if (!trip_place_is_open(
                        $place['opening_hours'],
                        $dayDates[$day],
                        $time[0],
                        $time[1]
                    )) {
                        continue;
                    }

                    $chosen = $place;
                    break 2;
                }
            }

            if ($chosen === null) continue;

            $id = $chosen['place_id'];
            $type = strtolower(trim((string)$chosen['type']));

            $dayPlans[$day]['items'][] = [
                'place_id' => $id,
                'start_time' => $time[0],
                'end_time' => $time[1],
                'reason_chosen' =>
                    'Selected based on your survey preferences, opening hours and seasonal availability.'
            ];

            $usedPlaces[$id] = true;

            if ($type !== '') $usedTypes[$type] = true;
        }
    }

    return [
        'title' => 'Your Personalized Al-Ahsa Trip',
        'intro' => 'A trip planned using your survey answers, available published places and their opening times.',
        'days' => array_values($dayPlans)
    ];
};

/* Try Gemini once with a short timeout. */
$plan = null;

$geminiReady =
    defined('GEMINI_API_KEY') &&
    trim(GEMINI_API_KEY) !== '' &&
    GEMINI_API_KEY !== 'PASTE_YOUR_GEMINI_API_KEY_HERE' &&
    GEMINI_API_KEY !== 'ضعي_مفتاح_Gemini_هنا' &&
    defined('GEMINI_MODEL') &&
    defined('GEMINI_API_URL');

if ($geminiReady) {
    $prompt = "
You are an expert Al-Ahsa tourism trip planner.

Create a personalized itinerary using ONLY the published database places.

Rules:
- Create exactly {$days} days.
- Use only supplied place_id values.
- Never invent places or IDs.
- Do not repeat places.
- Respect the survey answers.
- Use 24-hour HH:MM times.
- Respect the opening_hours of each place.
- Do not schedule a place outside its opening hours.
- Seasonal places may ONLY be scheduled on dates inside their supplied seasonal event Start_Date and End_Date.
- Do not schedule seasonal events before they start or after they end.
- Each day should have a logical mix of different place types when possible.
- Do not put all cafes, all restaurants, or all activities on one day when other types are available.
- Arrange visits in a sensible order: morning, midday and afternoon/evening.
- If a place is not suitable for a particular day, choose another suitable place.
- Return valid JSON only.
- Keep the response concise.

Trip start date: {$tripStartDate}

Trip day dates:
" . json_encode($dayDates, JSON_UNESCAPED_UNICODE) . "

Required JSON:
{
  \"title\": \"Trip title\",
  \"intro\": \"Short introduction\",
  \"days\": [
    {
      \"day_number\": 1,
      \"items\": [
        {
          \"place_id\": 123,
          \"start_time\": \"09:00\",
          \"end_time\": \"10:30\",
          \"reason_chosen\": \"Reason\"
        }
      ]
    }
  ]
}

Trip duration: {$days} days.

Survey answers:
" . json_encode($answersForAI, JSON_UNESCAPED_UNICODE) . "

Published places:
" . json_encode($places, JSON_UNESCAPED_UNICODE);

    $payload = [
        'systemInstruction' => [
            'parts' => [
                [
                    'text' => 'You are an Al-Ahsa tourism planner. Use only supplied database places and return valid JSON.'
                ]
            ]
        ],
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'temperature' => 0.4,
            'maxOutputTokens' => 2500
        ]
    ];

    $apiUrl = rtrim(GEMINI_API_URL, '/') .
        '/' . rawurlencode(GEMINI_MODEL) .
        ':generateContent';

    $requestBody = json_encode($payload, JSON_UNESCAPED_UNICODE);

    if ($requestBody !== false) {
        $ch = curl_init($apiUrl);

        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . GEMINI_API_KEY
                ],
                CURLOPT_POSTFIELDS => $requestBody,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 15
            ]);

            $raw = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($raw !== false && $httpCode >= 200 && $httpCode < 300) {
                $api = json_decode($raw, true);
                $outputText = '';

                foreach (($api['candidates'][0]['content']['parts'] ?? []) as $part) {
                    if (isset($part['text'])) {
                        $outputText .= $part['text'];
                    }
                }

                $outputText = trim($outputText);
                $outputText = preg_replace('/^```(?:json)?\s*/i', '', $outputText);
                $outputText = preg_replace('/\s*```$/', '', $outputText);

                $decodedPlan = json_decode($outputText, true);

                if (
                    is_array($decodedPlan) &&
                    !empty($decodedPlan['days']) &&
                    is_array($decodedPlan['days'])
                ) {
                    $plan = $decodedPlan;
                }
            }
        }
    }
}

/* Use the local plan if Gemini is unavailable or fails. */
if ($plan === null) {
    $plan = $createFallbackPlan();
}

/* Validate all selected places against the database. */
$placeMap = [];

foreach ($places as $place) {
    $placeMap[$place['place_id']] = $place;
}

/* Check that Gemini selected at least one real published place. */
$hasValidPlanPlace = false;

foreach ($plan['days'] as $day) {
    foreach (($day['items'] ?? []) as $item) {
        $placeId = (int)($item['place_id'] ?? 0);

        if (isset($placeMap[$placeId])) {
            $hasValidPlanPlace = true;
            break 2;
        }
    }
}

if (!$hasValidPlanPlace) {
    $plan = $createFallbackPlan();
}

/*
 * Clean the Gemini plan:
 * - Reject invalid IDs and duplicate places.
 * - Reject places outside their seasonal dates.
 * - Reject places closed at the proposed visit time.
 * - Avoid repeating the same type during a day when alternatives exist.
 */
$cleanDays = [];
$used = [];

foreach ($plan['days'] as $day) {
    $dayNumber = (int)($day['day_number'] ?? 0);

    if ($dayNumber < 1 || $dayNumber > $days) continue;

    $cleanItems = [];
    $usedTypesToday = [];

    $items = $day['items'] ?? [];

    usort($items, function ($a, $b) {
        return strcmp(
            (string)($a['start_time'] ?? '09:00'),
            (string)($b['start_time'] ?? '09:00')
        );
    });

    foreach ($items as $item) {
        $placeId = (int)($item['place_id'] ?? 0);

        if (
            !isset($placeMap[$placeId]) ||
            isset($used[$placeId])
        ) {
            continue;
        }

        $start = (string)($item['start_time'] ?? '09:00');
        $end = (string)($item['end_time'] ?? '10:00');

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start)) {
            $start = '09:00';
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)) {
            $end = '10:00';
        }

        if ($end <= $start) {
            $end = date('H:i', strtotime($start . ' +1 hour'));
        }

        $place = $placeMap[$placeId];

        if (!trip_place_is_in_season($place, $dayDates[$dayNumber])) {
            continue;
        }

        if (!trip_place_is_open(
            $place['opening_hours'],
            $dayDates[$dayNumber],
            $start,
            $end
        )) {
            continue;
        }

        $type = strtolower(trim((string)$place['type']));

        /*
         * Prefer variety. If another place of a different type is
         * available, the fallback step below can fill the slot.
         */
        $cleanItems[] = [
            'place_id' => $placeId,
            'start_time' => $start,
            'end_time' => $end,
            'reason_chosen' => mb_substr(
                (string)($item['reason_chosen'] ?? 'Selected based on your interests.'),
                0,
                500
            )
        ];

        $used[$placeId] = true;

        if ($type !== '') $usedTypesToday[$type] = true;
    }

    if ($cleanItems) {
        $cleanDays[$dayNumber] = $cleanItems;
    }
}

/*
 * Fill missing days or rejected visits using suitable places.
 * This also helps balance place types across the day.
 */
$standardTimes = [
    ['09:00', '10:30'],
    ['11:00', '12:30'],
    ['16:00', '17:30']
];

for ($dayNumber = 1; $dayNumber <= $days; $dayNumber++) {
    if (!isset($cleanDays[$dayNumber])) {
        $cleanDays[$dayNumber] = [];
    }

    $existingCount = count($cleanDays[$dayNumber]);

    if ($existingCount >= 3) continue;

    $usedTypesToday = [];

    foreach ($cleanDays[$dayNumber] as $existingItem) {
        $existingPlace = $placeMap[$existingItem['place_id']] ?? null;

        if ($existingPlace) {
            $existingType = strtolower(trim((string)$existingPlace['type']));
            if ($existingType !== '') $usedTypesToday[$existingType] = true;
        }
    }

    foreach ($standardTimes as $timeSlot) {
        if (count($cleanDays[$dayNumber]) >= 3) break;

        $slotAlreadyUsed = false;

        foreach ($cleanDays[$dayNumber] as $existingItem) {
            if ($existingItem['start_time'] === $timeSlot[0]) {
                $slotAlreadyUsed = true;
                break;
            }
        }

        if ($slotAlreadyUsed) continue;

        $chosen = null;

        foreach ([true, false] as $preferDifferentType) {
            foreach ($rankedPlaces as $candidate) {
                $candidateId = $candidate['place_id'];
                $candidateType = strtolower(trim((string)$candidate['type']));

                if (isset($used[$candidateId])) continue;

                if (
                    $preferDifferentType &&
                    $candidateType !== '' &&
                    isset($usedTypesToday[$candidateType])
                ) {
                    continue;
                }

                if (!trip_place_is_in_season($candidate, $dayDates[$dayNumber])) {
                    continue;
                }

                if (!trip_place_is_open(
                    $candidate['opening_hours'],
                    $dayDates[$dayNumber],
                    $timeSlot[0],
                    $timeSlot[1]
                )) {
                    continue;
                }

                $chosen = $candidate;
                break 2;
            }
        }

        if ($chosen === null) continue;

        $candidateId = $chosen['place_id'];
        $candidateType = strtolower(trim((string)$chosen['type']));

        $cleanDays[$dayNumber][] = [
            'place_id' => $candidateId,
            'start_time' => $timeSlot[0],
            'end_time' => $timeSlot[1],
            'reason_chosen' =>
                'Added based on your survey preferences, opening hours and seasonal availability.'
        ];

        $used[$candidateId] = true;

        if ($candidateType !== '') {
            $usedTypesToday[$candidateType] = true;
        }
    }

    usort($cleanDays[$dayNumber], function ($a, $b) {
        return strcmp($a['start_time'], $b['start_time']);
    });
}

ksort($cleanDays);

/* Remove empty days from the saved itinerary. */
$cleanDays = array_filter($cleanDays, function ($items) {
    return !empty($items);
});

if (!$cleanDays) {
    trip_fail('No suitable published places could be added to this itinerary. Please check the opening hours and seasonal event dates, then try again.');
}

/* Save survey, survey answers, itinerary and itinerary items. */
$conn->begin_transaction();

try {
    $touristId = (int)$_SESSION['tourist_id'];

    /* Save survey. */
    $stmt = $conn->prepare("
        INSERT INTO survey (tourist_id, create_at)
        VALUES (?, NOW())
    ");

    if (!$stmt) {
        throw new Exception('Could not prepare survey insert: ' . $conn->error);
    }

    $stmt->bind_param('i', $touristId);

    if (!$stmt->execute()) {
        throw new Exception('Could not save survey: ' . $stmt->error);
    }

    $surveyId = (int)$conn->insert_id;
    $stmt->close();

    /* Save every selected answer. */
    $answerStmt = $conn->prepare("
        INSERT INTO survey_answers (survey_id, question_id, option_id)
        VALUES (?, ?, ?)
    ");

    if (!$answerStmt) {
        throw new Exception('Could not prepare survey answers insert: ' . $conn->error);
    }

    foreach ($answersToSave as $answer) {
        $questionId = $answer['question_id'];
        $optionId = $answer['option_id'];

        $answerStmt->bind_param(
            'iii',
            $surveyId,
            $questionId,
            $optionId
        );

        if (!$answerStmt->execute()) {
            throw new Exception('Could not save a survey answer: ' . $answerStmt->error);
        }
    }

    $answerStmt->close();

    /* Save a new itinerary. */
    $stmt = $conn->prepare("
        INSERT INTO itinerary (tourist_id, survey_id, days, create_at)
        VALUES (?, ?, ?, NOW())
    ");

    if (!$stmt) {
        throw new Exception('Could not prepare itinerary insert: ' . $conn->error);
    }

    $stmt->bind_param('iii', $touristId, $surveyId, $days);

    if (!$stmt->execute()) {
        throw new Exception('Could not save itinerary: ' . $stmt->error);
    }

    $itineraryId = (int)$conn->insert_id;
    $stmt->close();

    /* Save itinerary places. */
    $insertItem = $conn->prepare("
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

    if (!$insertItem) {
        throw new Exception('Could not prepare itinerary items insert: ' . $conn->error);
    }

    foreach ($cleanDays as $dayNumber => $items) {
        $sequence = 0;

        foreach ($items as $item) {
            $sequence++;

            $placeId = $item['place_id'];
            $start = $item['start_time'] . ':00';
            $end = $item['end_time'] . ':00';
            $reason = $item['reason_chosen'];

            $insertItem->bind_param(
                'iiissis',
                $itineraryId,
                $dayNumber,
                $placeId,
                $start,
                $end,
                $sequence,
                $reason
            );

            if (!$insertItem->execute()) {
                throw new Exception('Could not save an itinerary item: ' . $insertItem->error);
            }
        }
    }

    $insertItem->close();

    /* Commit all database changes. */
    $conn->commit();

    $_SESSION['latest_itinerary_id'] = $itineraryId;

    $_SESSION['latest_itinerary_title'] =
        $plan['title'] ?? 'Your Al-Ahsa Journey';

    $_SESSION['latest_itinerary_intro'] =
        $plan['intro'] ?? 'A personalized itinerary created from your survey answers.';

    unset($_SESSION['trip_error']);

    header('Location: my-itinerary.php?id=' . $itineraryId);
    exit;

} catch (Throwable $e) {
    $conn->rollback();

    trip_fail(
        'Could not save your itinerary. Please check the database tables and columns. Details: ' .
        $e->getMessage()
    );
}