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
<body style="background-color: #f4f7f6; margin: 0; font-family: Arial, sans-serif;">

    <!-- Sidebar -->
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

    <!-- Main Content Wrapper (Margin to avoid sidebar overlap) -->
    <div style="margin-left: 250px; padding: 20px;">
        
        <!-- Header -->
        <div style="background: #ffffff; padding: 20px 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; color: #1a4d2e; font-size: 24px;">Admin Dashboard</h1>
                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">Welcome back! Here is an overview of the system's activity.</p>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="color: #333; font-weight: bold;">Hi, <?php echo isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin'; ?></span>
                <div style="width: 40px; height: 40px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1a4d2e; font-weight: bold;">A</div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
            
            <!-- Card 1 -->
            <div style="flex: 1; min-width: 200px; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #666; font-size: 14px; font-weight: 600;">Total Site Visits</h3>
                    <span style="background: #e8f5e9; color: #1a4d2e; padding: 8px; border-radius: 8px;">👁️</span>
                </div>
                <h2 style="margin: 0; color: #111; font-size: 28px;"><?php echo $total_visits; ?></h2>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">Overall traffic</p>
            </div>

            <!-- Card 2 -->
            <div style="flex: 1; min-width: 200px; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #666; font-size: 14px; font-weight: 600;">Guest Visits</h3>
                    <span style="background: #e8f5e9; color: #1a4d2e; padding: 8px; border-radius: 8px;">👤</span>
                </div>
                <h2 style="margin: 0; color: #111; font-size: 28px;"><?php echo $guest_visits; ?></h2>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">Unregistered visitors</p>
            </div>

            <!-- Card 3 -->
            <div style="flex: 1; min-width: 200px; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #666; font-size: 14px; font-weight: 600;">Total Places</h3>
                    <span style="background: #e8f5e9; color: #1a4d2e; padding: 8px; border-radius: 8px;">📍</span>
                </div>
                <h2 style="margin: 0; color: #111; font-size: 28px;"><?php echo $total_places; ?></h2>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">Registered locations</p>
            </div>

            <!-- Card 4 -->
            <div style="flex: 1; min-width: 200px; background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: #666; font-size: 14px; font-weight: 600;">Business Owners</h3>
                    <span style="background: #e8f5e9; color: #1a4d2e; padding: 8px; border-radius: 8px;">💼</span>
                </div>
                <h2 style="margin: 0; color: #111; font-size: 28px;"><?php echo $total_owners; ?></h2>
                <p style="margin: 10px 0 0 0; color: #999; font-size: 12px;">Registered accounts</p>
            </div>

        </div>

        <!-- Admin Table Section -->
        <div style="background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h2 style="margin: 0 0 20px 0; color: #1a4d2e; font-size: 20px;">Admin Accounts</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid #f0f0f0; color: #666; font-size: 14px;">
                            <th style="padding: 15px; font-weight: 600;">ID</th>
                            <th style="padding: 15px; font-weight: 600;">Name</th>
                            <th style="padding: 15px; font-weight: 600;">Email</th>
                            <th style="padding: 15px; font-weight: 600;">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($admins_result && $admins_result->num_rows > 0) {
                            while($row = $admins_result->fetch_assoc()): 
                        ?>
                        <tr style="border-bottom: 1px solid #f9f9f9; font-size: 14px;">
                            <td style="padding: 15px; color: #555;"><?php echo $row['Admin_ID']; ?></td>
                            <td style="padding: 15px; color: #333; font-weight: 600;"><?php echo $row['Name']; ?></td>
                            <td style="padding: 15px; color: #666;"><?php echo $row['Email']; ?></td>
                            <td style="padding: 15px; color: #666;"><?php echo $row['Created_at']; ?></td>
                        </tr>
                        <?php 
                            endwhile; 
                        } else {
                            echo "<tr><td colspan='4' style='padding: 15px; text-align: center; color: #999;'>No admins found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- AI Button -->
    <button class="chat-button" onclick="openAI()" aria-label="Open AI Assistant" title="AI Assistant"></button>

    <!-- Footer -->
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