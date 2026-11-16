
<?php

session_start();

/* Clear all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Prevent browser from caching admin pages */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

/* Redirect to Admin Login */
header("Location: index.php");
exit;

?>
