<?php
// api.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once 'db.php'; 

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'get_places') {
    $query = "SELECT Place_ID, Name, Place_Type, Short_Description, Image FROM places WHERE Is_Published = 1 LIMIT 10";
    $result = $conn->query($query);
    $places = array();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $places[] = $row;
        }
    }
    echo json_encode($places, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(["error" => "Invalid action"]);
}
?>