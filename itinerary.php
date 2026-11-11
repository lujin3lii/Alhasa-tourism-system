<?php
require_once 'db.php'; require_once 'trip-common.php';
trip_header('Plan Your Trip','itinerary'); ?>
<section class="about-hero"><span class="about-label">PLAN YOUR VISIT</span><h1>Plan Your Trip to Al-Ahsa</h1><p>Choose the planning method that suits your visit.</p></section>
<section class="about-section-block"><div class="about-section-heading"><span>YOUR JOURNEY STARTS HERE</span><h2>How would you like to plan?</h2></div>
<div class="about-info-grid">
<article class="about-info-card"><div class="about-card-icon">✦</div><h3>Create My Own Trip</h3><p>Answer a few questions and receive a suggested itinerary based on your interests and visit preferences.</p><a class="trip-action" href="survey.php">Create My Own Trip <span>→</span></a></article>
<article class="about-info-card"><div class="about-card-icon">⌖</div><h3>Choose a Ready-Made Trip</h3><p>Explore four trip themes, choose the places you like, and arrange your own days.</p><a class="trip-action" href="ready-made-trips.php">Explore Ready-Made Trips <span>→</span></a></article>
</div></section>
<?php trip_footer(); ?>
