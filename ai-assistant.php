<?php
/* =====================================
   AL-AHSA AI ASSISTANT
   Database Search
===================================== */

header("Content-Type: application/json; charset=UTF-8");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function sendResponse($success, $reply, $results = []) {
    echo json_encode([
        "success" => $success,
        "reply" => $reply,
        "results" => $results
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    /* =====================================
       DATABASE CONNECTION
    ===================================== */

    require_once "db.php";

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception("Database connection is not available.");
    }

    $conn->set_charset("utf8mb4");


    /* =====================================
       RECEIVE USER QUESTION
    ===================================== */

    $input = json_decode(file_get_contents("php://input"), true);

    $question = trim($input["question"] ?? "");

    if ($question === "") {
        sendResponse(false, "Please enter your question.");
    }

    $text = mb_strtolower($question, "UTF-8");


    /* =====================================
       DETECT EVENTS AND SEASONS
    ===================================== */

    $isEventQuestion = false;

    $eventKeywords = [
        "event",
        "events",
        "season",
        "seasons",
        "festival",
        "festivals",
        "فعاليات",
        "فعالية",
        "موسم",
        "مواسم",
        "المواسم",
        "الفعاليات"
    ];

    foreach ($eventKeywords as $keyword) {
        if (mb_strpos($text, $keyword) !== false) {
            $isEventQuestion = true;
            break;
        }
    }


    /* =====================================
       DETECT PLACE CATEGORY
    ===================================== */

    $categories = [

        "restaurants" => [
            "restaurant",
            "restaurants",
            "food",
            "dining",
            "مطعم",
            "مطاعم",
            "اكل",
            "أكل",
            "مأكولات"
        ],

        "cafes" => [
            "cafe",
            "cafes",
            "coffee",
            "كافيه",
            "كافيهات",
            "مقهى",
            "مقاهي",
            "قهوة",
            "كوفي"
        ],

        "entertainment" => [
            "entertainment",
            "attraction",
            "attractions",
            "tourist",
            "tourism",
            "places",
            "place",
            "سياحي",
            "سياحية",
            "سياحيه",
            "أماكن",
            "اماكن",
            "مكان",
            "ترفيه",
            "ترفيهية",
            "معالم",
            "معلم"
        ],

        "markets" => [
            "market",
            "markets",
            "shopping",
            "souq",
            "سوق",
            "أسواق",
            "اسواق",
            "تسوق"
        ],

        "hotels" => [
            "hotel",
            "hotels",
            "stay",
            "accommodation",
            "فندق",
            "فنادق",
            "إقامة",
            "اقامة",
            "سكن"
        ]
    ];

    $selectedCategory = null;

    foreach ($categories as $category => $keywords) {

        foreach ($keywords as $keyword) {

            if (mb_strpos($text, $keyword) !== false) {
                $selectedCategory = $category;
                break 2;
            }
        }
    }


    /* =====================================
       SEARCH SEASONAL EVENTS
    ===================================== */

    if ($isEventQuestion) {

        $sql = "
            SELECT
                p.Place_ID,
                p.Name,
                p.Short_Description,
                p.Description,
                p.Location,
                p.image,
                p.Website,
                se.Start_Date,
                se.End_Date,
                se.Ticket_URL

            FROM seasonal_events se

            INNER JOIN Places p
                ON se.Place_ID = p.Place_ID

            WHERE p.Is_Published = 1
              AND se.End_Date >= CURDATE()

            ORDER BY se.Start_Date ASC

            LIMIT 10
        ";

        $result = $conn->query($sql);

        $results = [];

        while ($row = $result->fetch_assoc()) {

            $description = $row["Short_Description"]
                ?: $row["Description"];

            $results[] = [
                "id" => $row["Place_ID"],
                "name" => $row["Name"],
                "description" => $description,
                "location" => $row["Location"],
                "image" => $row["image"],
                "url" => $row["Ticket_URL"] ?: $row["Website"],
                "start_date" => $row["Start_Date"],
                "end_date" => $row["End_Date"]
            ];
        }

        if (count($results) > 0) {
            sendResponse(
                true,
                "Here are the available and upcoming events and seasons in Al-Ahsa:",
                $results
            );
        }

        sendResponse(
            true,
            "There are currently no upcoming events available in the database."
        );
    }


    /* =====================================
       SEARCH PLACES
    ===================================== */

    if ($selectedCategory !== null) {

        $sql = "
            SELECT
                Place_ID,
                Name,
                Place_Type,
                Short_Description,
                Description,
                Location,
                image,
                Opening_Hours,
                Website

            FROM Places

            WHERE Place_Type = ?
              AND Is_Published = 1

            ORDER BY Name ASC

            LIMIT 10
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param("s", $selectedCategory);

    } else {

        /* Search all published categories */
        $searchTerm = "%" . $question . "%";

        $sql = "
            SELECT
                Place_ID,
                Name,
                Place_Type,
                Short_Description,
                Description,
                Location,
                image,
                Opening_Hours,
                Website

            FROM Places

            WHERE Is_Published = 1
              AND (
                    Name LIKE ?
                    OR Description LIKE ?
                    OR Short_Description LIKE ?
                    OR Location LIKE ?
                    OR Place_Type LIKE ?
              )

            ORDER BY Name ASC

            LIMIT 10
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssss",
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm,
            $searchTerm
        );
    }


    /* =====================================
       GET DATABASE RESULTS
    ===================================== */

    $stmt->execute();

    $result = $stmt->get_result();

    $results = [];

    while ($row = $result->fetch_assoc()) {

        $description = $row["Short_Description"]
            ?: $row["Description"];

        $results[] = [
            "id" => $row["Place_ID"],
            "name" => $row["Name"],
            "category" => $row["Place_Type"],
            "description" => $description,
            "location" => $row["Location"],
            "image" => $row["image"],
            "opening_hours" => $row["Opening_Hours"],
            "url" => $row["Website"]
        ];
    }

    $stmt->close();


    /* =====================================
       SEND RESPONSE
    ===================================== */

    if (count($results) > 0) {

        $reply = "Here are some results from Al-Ahsa Vision Hub:";

        if ($selectedCategory !== null) {

            $categoryNames = [
                "restaurants" => "restaurants",
                "cafes" => "cafes",
                "entertainment" => "tourist and entertainment places",
                "markets" => "markets",
                "hotels" => "hotels"
            ];

            $reply = "Here are the " .
                $categoryNames[$selectedCategory] .
                " available in Al-Ahsa:";
        }

        sendResponse(true, $reply, $results);
    }

    sendResponse(
        true,
        "Sorry, I couldn't find matching published places in the database. Try asking about restaurants, cafes, hotels, markets, or tourist attractions."
    );


} catch (Throwable $e) {

    error_log("AI Assistant Error: " . $e->getMessage());

    sendResponse(
        false,
        "Sorry, something went wrong while searching the database. Please try again."
    );
}
?>