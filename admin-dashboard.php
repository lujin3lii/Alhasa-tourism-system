<?php
session_start();
require_once 'db.php'; 

// 1. Fetch total visits
$total_visits_query = $conn->query("SELECT COUNT(*) AS total FROM site_visits");
$total_visits = $total_visits_query->fetch_assoc()['total'];

// 2. Fetch guest visits
$guest_visits_query = $conn->query("SELECT COUNT(*) AS total FROM site_visits WHERE Tourist_ID IS NULL");
$guest_visits = $guest_visits_query->fetch_assoc()['total'];

// 3. Fetch total places
$total_places_query = $conn->query("SELECT COUNT(*) AS total FROM places");
$total_places = $total_places_query->fetch_assoc()['total'];

// 4. Fetch total business owners
$total_owners_query = $conn->query("SELECT COUNT(*) AS total FROM business_owners");
$total_owners = $total_owners_query->fetch_assoc()['total'];
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

    <!-- Menu Button -->
    <button class="menu-button" onclick="toggleMenu()" aria-label="Open menu">
        ☰
    </button>

    <div class="sidebar-backdrop" onclick="toggleMenu()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <a href="index.php">
                <img src="images/logo.png" alt="Al-Ahsa Vision Hub" class="sidebar-logo">
            </a>
            <h2>Al-Ahsa <span>Vision Hub</span></h2>
            <p>Admin Dashboard</p>
        </div>

        <div class="sidebar-links">
            <!-- Links updated for Admin -->
            <a href="admin-dashboard.php" class="active"><span>⚙️</span> Dashboard</a>
            <a href="admin-places.php"><span>⌘</span> Manage Places</a>
            <a href="admin-business-owners.php"><span>♙</span> Business Owners</a>
            <a href="admin-tourists.php"><span>♧</span> Tourists</a>
            <a href="logout.php"><span>🚪</span> Logout</a>
        </div>

        <div class="sidebar-decoration">
            <span>🌴</span>
            <p>THE HEART OF AL-AHSA</p>
        </div>

    </nav>


    <!-- Main Content -->
    <main class="home-main">

        <!-- Top Bar (Same as your code) -->
        <header class="home-topbar">
            <div class="home-location">
                <span class="location-dot"></span>
                DISCOVER AL-AHSA, SAUDI ARABIA
            </div>

            <div class="home-account-links">
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <a href="#" class="home-signup" id="account-button">
                        <?= htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php else: ?>
                    <a href="role-selection.html" class="home-signup" id="account-button">
                        Log In
                    </a>
                <?php endif; ?>

                <a href="index.php" class="home-small-logo">
                    <img src="images/logo.png" alt="Logo">
                </a>
            </div>
        </header>

        <!-- Page Content Starts Here -->
        <div style="padding: 30px; max-width: 1200px; margin: 0 auto;">
            
            <h1 style="color: #1a4d2e; margin-bottom: 5px;">Admin Dashboard</h1>
            <p style="color: #666; margin-bottom: 30px;">Welcome back! Here is an overview of the system's activity.</p>

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

            <!-- Note: The Admin Table has been removed as requested -->

        </div>
    </main>

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