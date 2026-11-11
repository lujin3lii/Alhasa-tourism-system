<?php
session_start();
require_once 'db.php'; 

$total_visits_query = $conn->query("SELECT COUNT(*) AS total FROM site_visits");
$total_visits = $total_visits_query->fetch_assoc()['total'];

$guest_visits_query = $conn->query("SELECT COUNT(*) AS total FROM site_visits WHERE Tourist_ID IS NULL");
$guest_visits = $guest_visits_query->fetch_assoc()['total'];

$total_places_query = $conn->query("SELECT COUNT(*) AS total FROM places");
$total_places = $total_places_query->fetch_assoc()['total'];

$total_owners_query = $conn->query("SELECT COUNT(*) AS total FROM business_owners");
$total_owners = $total_owners_query->fetch_assoc()['total'];

$admins_result = $conn->query("SELECT * FROM admin");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Al-Ahsa Vision Hub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-links">
            <a href="admin-home.php"><span>⌂</span> Admin Home</a>
            <a href="admin-dashboard.php" class="active"><span>⚙️</span> Dashboard</a>
            <a href="admin-places.php"><span>⌘</span> Manage Places</a>
            <a href="admin-business-owners.php"><span>♙</span> Business Owners</a>
            <a href="admin-tourists.php"><span>♧</span> Tourists</a>
            <a href="logout.php"><span>🚪</span> Logout</a>
        </div>
    </div>

    <div>
        <h1>Admin Dashboard</h1>
        
        <h3>Total Site Visits: <?php echo $total_visits; ?></h3>
        <h3>Guest Visits: <?php echo $guest_visits; ?></h3>
        <h3>Total Places: <?php echo $total_places; ?></h3>
        <h3>Business Owners: <?php echo $total_owners; ?></h3>

        <hr>

        <h2>Admin Accounts</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($admins_result && $admins_result->num_rows > 0) {
                    while($row = $admins_result->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $row['Admin_ID']; ?></td>
                    <td><?php echo $row['Name']; ?></td>
                    <td><?php echo $row['Email']; ?></td>
                    <td><?php echo $row['Created_at']; ?></td>
                </tr>
                <?php 
                    endwhile; 
                } else {
                    echo "<tr><td colspan='4'>No admins found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <button class="chat-button" onclick="openAI()" aria-label="Open AI Assistant" title="AI Assistant"></button>

    <footer class="site-footer">
        <div class="site-footer-main">
            <div class="site-footer-brand">
                <h3>Al-Ahsa Vision Hub</h3>
                <p>Discover the beauty, heritage, and experiences of Al-Ahsa.</p>
            </div>
            <div class="site-footer-links">
                <h4>Explore Al-Ahsa</h4>
                <p>Discover places, restaurants, cafés, and seasonal events.</p>
            </div>
            <div class="site-footer-contact">
                <h4>Our Vision</h4>
                <p>Making your journey through Al-Ahsa memorable.</p>
            </div>
            <div class="site-footer-contact">
                <h4>Contact Us</h4>
                <p>05123546798</p>
                <p><a href="https://www.instagram.com/" target="_blank">◎ Instagram</a></p>
                <p><a href="https://x.com/" target="_blank">𝕏 X (Twitter)</a></p>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; 2026 Al-Ahsa Vision Hub. All rights reserved.</p>
            <p>Explore Al-Ahsa, experience its beauty.</p>
        </div>
    </footer>

</body>
</html>