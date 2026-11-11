<?php
session_start();
require_once 'db.php'; 

// Fetch all tourists from the database
$tourists_query = $conn->query("SELECT * FROM tourist ORDER BY Create_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tourists - Al-Ahsa Vision Hub</title>
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
            <a href="admin-dashboard.php"><span>⚙️</span> Dashboard</a>
            <a href="admin-places.php"><span>⌘</span> Manage Places</a>
            <a href="admin-business-owners.php"><span>♙</span> Business Owners</a>
            <a href="admin-tourists.php" class="active"><span>♧</span> Tourists</a>
            <a href="logout.php"><span>🚪</span> Logout</a>
        </div>

        <div class="sidebar-decoration">
            <span>🌴</span>
            <p>THE HEART OF AL-AHSA</p>
        </div>
    </nav>


    <!-- Main Content -->
    <main class="home-main">
        <!-- Top Bar -->
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
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1 style="color: #1a4d2e; margin-bottom: 5px;">Tourist Management</h1>
                    <p style="color: #666; margin: 0;">View and manage all registered tourists.</p>
                </div>
            </div>

            <!-- Table Section -->
            <div style="background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f0f0f0; color: #666; font-size: 14px;">
                                <th style="padding: 15px; font-weight: 600;">ID</th>
                                <th style="padding: 15px; font-weight: 600;">Name</th>
                                <th style="padding: 15px; font-weight: 600;">Email</th>
                                <th style="padding: 15px; font-weight: 600;">Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($tourists_query && $tourists_query->num_rows > 0) {
                                while($row = $tourists_query->fetch_assoc()): 
                            ?>
                            <tr style="border-bottom: 1px solid #f9f9f9; font-size: 14px;">
                                <td style="padding: 15px; color: #555;"><?php echo $row['Tourist_id']; ?></td>
                                <td style="padding: 15px; color: #333; font-weight: 600;"><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td style="padding: 15px; color: #666;"><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td style="padding: 15px; color: #666;"><?php echo $row['Create_at']; ?></td>
                            </tr>
                            <?php 
                                endwhile; 
                            } else {
                                echo "<tr><td colspan='4' style='padding: 20px; text-align: center; color: #999;'>No tourists found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

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