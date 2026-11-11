<?php

$conn = new mysqli("localhost", "root", "", "", 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->select_db("al-ahsa vision hub");

if ($conn->connect_error) {
    die("Database selection failed: " . $conn->connect_error);
}

?>
