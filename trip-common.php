<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
function trip_e($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function trip_header($pageTitle, $active = '') { ?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= trip_e($pageTitle) ?> | Al-Ahsa Vision Hub</title><link rel="stylesheet" href="css/style.css">
<style>
/* Styles are limited to the trip-planning pages; the shared sidebar and topbar are untouched. */
.trip-page-content{padding:34px clamp(16px,3vw,38px) 48px;max-width:1440px;margin:0 auto;}
.trip-page-content .about-hero{margin-top:10px;margin-bottom:34px;}
.trip-page-content .about-section-block,.trip-page-content .about-content-section{margin-top:28px;margin-bottom:34px;}
.trip-page-content .about-intro-card{padding:clamp(22px,3vw,38px);border-radius:22px;}
.trip-page-content .about-info-grid{gap:22px;}
.trip-page-content .about-info-card{padding:28px;border-radius:20px;display:flex;flex-direction:column;align-items:flex-start;}
.trip-page-content .trip-action{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:48px;padding:13px 25px;border:0;border-radius:12px;background:#075b45;color:#fff!important;text-decoration:none!important;font-weight:700;font-size:14px;cursor:pointer;transition:transform .18s,background .18s;}
.trip-page-content .trip-action:hover{background:#064633;transform:translateY(-2px);}
.trip-page-content .trip-action.secondary{background:#e9f0eb;color:#075b45!important;border:1px solid #cbdacf;}
.trip-page-content .trip-actions{display:flex;justify-content:space-between;align-items:center;gap:14px;margin:32px auto 8px;max-width:620px;}
.trip-page-content .trip-actions.center{justify-content:center;}
.trip-page-content .trip-question{padding:22px 0;border-bottom:1px solid #e6ece8;}
.trip-page-content .trip-question:last-child{border-bottom:0;}
.trip-page-content .trip-question h2{font-size:19px;margin:5px 0 16px;}
.trip-page-content .trip-question-options{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;}
.trip-page-content .trip-option{display:flex;gap:10px;align-items:center;padding:14px 16px;border:1px solid #dce6df;border-radius:13px;background:#fff;cursor:pointer;line-height:1.5;}
.trip-page-content .trip-option:has(input:checked){border-color:#367b59;background:#f0f6f1;}
.trip-page-content .trip-option input{accent-color:#075b45;}
.trip-page-content .trip-day{margin:26px 0;padding:22px;border:1px solid #e1e9e3;border-radius:20px;background:#fbfcfb;}
.trip-page-content .trip-day-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:17px;}
.trip-page-content .trip-day-heading h2{margin:0;font-size:22px;}
.trip-page-content .trip-place-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:15px;}
.trip-page-content .trip-place{display:grid;grid-template-columns:104px 1fr;gap:14px;align-items:center;padding:12px;border:1px solid #e4ebe6;border-radius:15px;background:#fff;}
.trip-page-content .trip-place img{width:104px;height:92px;object-fit:cover;border-radius:11px;background:#edf2ee;}
.trip-page-content .trip-place h3{font-size:16px;margin:0 0 6px;}
.trip-page-content .trip-place p{font-size:13px;line-height:1.6;margin:0;color:#687779;}
.trip-page-content .trip-place .trip-time{font-weight:700;color:#075b45;margin-top:7px;}
.trip-page-content .trip-place-select{display:flex;gap:9px;align-items:flex-start;}
.trip-page-content .trip-place-select input{margin-top:4px;accent-color:#075b45;}
.trip-page-content .trip-review{max-width:720px;margin:38px auto 10px;padding:28px;border:1px solid #e2e9e4;border-radius:20px;background:#f8faf8;}
.trip-page-content .trip-review label{display:block;margin:15px 0 8px;font-weight:700;color:#26383b;}
.trip-page-content .trip-review select,.trip-page-content .trip-review textarea{width:100%;padding:13px 14px;border:1px solid #d4dfd7;border-radius:11px;background:#fff;font:inherit;}
.trip-page-content .trip-review textarea{min-height:115px;resize:vertical;}
.trip-page-content .trip-rating{display:flex;gap:10px;flex-wrap:wrap;}
.trip-page-content .trip-rating label{margin:0;}
.trip-page-content .trip-rating input{accent-color:#075b45;}
.trip-page-content .trip-empty{padding:24px;border-radius:14px;background:#f5f8f5;color:#52685a;}
@media(max-width:650px){.trip-page-content{padding:24px 14px 36px}.trip-page-content .trip-place{grid-template-columns:82px 1fr}.trip-page-content .trip-place img{width:82px;height:82px}.trip-page-content .trip-actions{gap:10px}.trip-page-content .trip-action{padding:12px 17px;min-height:45px}}
</style></head>
<body class="about-page">
<button class="menu-button" onclick="toggleMenu()" aria-label="Open menu">☰</button><div class="sidebar-backdrop" onclick="toggleMenu()"></div>
<nav class="sidebar" id="sidebar"><div class="sidebar-brand"><a href="index.php"><img src="images/logo.png" alt="Al-Ahsa Vision Hub" class="sidebar-logo"></a><h2>Al-Ahsa <span>Vision Hub</span></h2><p>Discover the beauty of Al-Ahsa</p></div>
<div class="sidebar-links">
<a href="index.php"><span>⌂</span> Home</a><a href="explore-al-ahsa.php"><span>⌖</span> Explore Al-Ahsa</a><a href="places.php"><span>⌘</span> Tourist Places</a><a href="seasons.php"><span>⌘</span> Seasons</a><a href="restaurants.php"><span>♨</span> Restaurants</a><a href="cafes.php"><span>☕</span> Cafes</a><a href="markets.php"><span>♧</span> Markets</a><a href="settings.php"><span>⚙</span> Settings</a><a href="my-account.php"><span>♙</span> My Account</a><a href="About.php"><span>ⓘ</span> About Us</a>
</div><div class="sidebar-decoration"><span>🌴</span><p>THE HEART OF AL-AHSA</p></div></nav>
<main class="home-main about-main"><header class="home-topbar" style="margin-bottom:25px;">
<div class="home-location"><span class="location-dot"></span> DISCOVER AL-AHSA, SAUDI ARABIA</div><div class="home-account-links">
<?php if(isset($_SESSION['tourist_id'])): ?><a href="my-account.php" class="home-signup" id="account-button"><?= trip_e($_SESSION['tourist_name'] ?? 'My Account') ?></a><?php else: ?><a href="role-selection.html" class="home-signup" id="account-button">Log In</a><?php endif; ?>
<a href="index.php" class="home-small-logo"><img src="images/logo.png" alt="Logo"></a></div></header>
<div class="trip-page-content">
<?php }
function trip_footer(){ ?>
</div></main><button class="chat-button" onclick="openAI()" aria-label="Open assistant" title="Assistant"></button>
<footer class="site-footer"><div class="site-footer-main"><div class="site-footer-brand"><h3>Al-Ahsa Vision Hub</h3><p>Discover the beauty, heritage, and experiences of Al-Ahsa.</p></div><div class="site-footer-links"><h4>Explore Al-Ahsa</h4><p>Discover places, restaurants, cafés, and seasonal events.</p></div><div class="site-footer-contact"><h4>Our Vision</h4><p>Making your journey through Al-Ahsa memorable.</p></div><div class="site-footer-contact"><h4>Contact Us</h4><p>05123546798</p><p><a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">◎ Instagram</a></p><p><a href="https://x.com/" target="_blank" rel="noopener noreferrer">𝕏 X (Twitter)</a></p></div></div><div class="site-footer-bottom"><p>©️ 2026 Al-Ahsa Vision Hub. All rights reserved.</p><p>Explore Al-Ahsa, experience its beauty.</p></div></footer><script src="script.js"></script></body></html>
<?php } ?>
