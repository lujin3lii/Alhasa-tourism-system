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
    $option = mb_strtolower(
        trip_normalize_digits($optionText),
        'UTF-8'
    );

    $isDurationQuestion =
        strpos($question, 'duration') !== false ||
        strpos($question, 'how many days') !== false ||
        strpos($question, 'stay') !== false ||
        strpos($question, 'days') !== false ||
        mb_strpos($question, 'مدة') !== false ||
        mb_strpos($question, 'كم يوم') !== false ||
        mb_strpos($question, 'الإقامة') !== false ||
        mb_strpos($question, 'الاقامة') !== false;

    if (!$isDurationQuestion) {
        return null;
    }

    if (
        strpos($option, 'more than a week') !== false ||
        strpos($option, 'more than one week') !== false ||
        mb_strpos($option, 'أكثر من أسبوع') !== false ||
        mb_strpos($option, 'اكثر من اسبوع') !== false
    ) {
        return 10;
    }

    if (
        strpos($option, 'week') !== false ||
        mb_strpos($option, 'أسبوع') !== false ||
        mb_strpos($option, 'اسبوع') !== false
    ) {
        return 7;
    }

    if (preg_match('/(\d+)\s*[-–]\s*(\d+)/u', $option, $range)) {
        return (int)round(((int)$range[1] + (int)$range[2]) / 2);
    }

    if (preg_match('/(\d+)/u', $option, $number)) {
        $days = (int)$number[1];

        if ($days >= 1 && $days <= 14) {
            return $days;
        }
    }

    $arabicNumbers = [
        'يوم واحد' => 1,
        'يومين' => 2,
        'ثلاثة أيام' => 3,
        'ثلاث ايام' => 3,
        'أربعة أيام' => 4,
        'اربعة ايام' => 4,
        'خمسة أيام' => 5,
        'خمسة ايام' => 5,
        'ستة أيام' => 6,
        'ست ايام' => 6
    ];

    foreach ($arabicNumbers as $word => $number) {
        if (mb_strpos($option, $word) !== false) {
            return $number;
        }
    }

    return null;
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
        if (!is_array($submitted)) {
            $submitted = [];
        }
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

    if (!$submitted) {
        continue;
    }

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

        if ($detectedDays !== null) {
            $days = $detectedDays;
        }
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
        'website' => $row['Website']
    ];
}

if (!$places) {
    trip_fail('There are no published places yet. Publish places in the Places table and try again.');
}

/*
 * Local fallback:
 * Create an itinerary from published database places
 * when Gemini is unavailable.
 */
$createFallbackPlan = function () use ($places, $days, $answersForAI) {

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

    $rankedPlaces = $places;

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
            if (mb_strpos($placeText, $keyword) !== false) {
                $score++;
            }
        }

        $place['_score'] = $score;
    }

    unset($place);

    usort($rankedPlaces, function ($a, $b) {
        return $b['_score'] <=> $a['_score'];
    });

    $dayPlans = [];

    for ($day = 1; $day <= $days; $day++) {
        $dayPlans[] = [
            'day_number' => $day,
            'items' => []
        ];
    }

    $times = [
        ['09:00', '10:30'],
        ['11:00', '12:30'],
        ['16:00', '17:30']
    ];

    $selectedPlaces = array_slice($rankedPlaces, 0, $days * 3);

    foreach ($selectedPlaces as $index => $place) {

        $dayIndex = (int)floor($index / 3);
        $timeIndex = $index % 3;

        $dayPlans[$dayIndex]['items'][] = [
            'place_id' => $place['place_id'],
            'start_time' => $times[$timeIndex][0],
            'end_time' => $times[$timeIndex][1],
            'reason_chosen' =>
                'Selected from published Al-Ahsa places based on your survey preferences.'
        ];
    }

    return [
        'title' => 'Your Personalized Al-Ahsa Trip',
        'intro' => 'A trip planned using your survey answers and available published places.',
        'days' => $dayPlans
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
- Return valid JSON only.
- Keep the response concise.

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

$cleanDays = [];
$used = [];

foreach ($plan['days'] as $day) {

    $dayNumber = (int)($day['day_number'] ?? 0);

    if ($dayNumber < 1 || $dayNumber > $days) {
        continue;
    }

    $cleanItems = [];

    foreach (($day['items'] ?? []) as $item) {

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
    }

    if ($cleanItems) {
        $cleanDays[$dayNumber] = $cleanItems;
    }
}

ksort($cleanDays);

if (!$cleanDays) {
    trip_fail('No suitable published places could be added to this itinerary. Please publish more places and try again.');
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