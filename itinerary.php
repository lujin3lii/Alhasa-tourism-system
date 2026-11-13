<?php
require_once __DIR__ . '/trip-common.php';
trip_page_header('itinerary');
?>

<section class="trip-page">

    <div class="trip-hero">
        <div class="trip-hero-copy">
            <span class="eyebrow">YOUR AL-AHSA EXPERIENCE</span>

            <h1>Plan a Trip That Feels Like Yours</h1>

            <p>
                Tell us what you enjoy, how much time you have, and your budget.
                Our AI trip planner will build a personalized itinerary using
                real places from Al-Ahsa.
            </p>

            <div class="trip-trust">
                <span>✦</span>
                Personalized by AI · Based on places in our database
            </div>
        </div>

        <div class="trip-hero-art" aria-hidden="true">
            <div class="sun"></div>
            <div class="palm palm-one">🌴</div>
            <div class="palm palm-two">🌴</div>

            <div class="oasis-card">
                <span>AL-AHSA</span>
                <strong>Your next story<br>starts here.</strong>
                <small>Discover · Explore · Enjoy</small>
            </div>
        </div>
    </div>


    <div class="trip-section-heading">
        <span class="eyebrow">YOUR TRIP JOURNEY</span>
        <h2>What would you like to do?</h2>
    </div>


    <div class="trip-steps">

        <!-- Create My Own Trip -->
        <article class="trip-step">
            <span class="step-number">01</span>

            <div class="step-icon">✎</div>

            <h3>Create My Own Trip</h3>

            <p>
                Choose your trip duration, budget, interests, and preferred
                experiences to get your personalized AI itinerary.
            </p>

            <a class="trip-primary-btn" href="survey.php">
                Start Planning <span>→</span>
            </a>
        </article>


        <!-- Suggested Trips -->
        <article class="trip-step">
            <span class="step-number">02</span>

            <div class="step-icon">✧</div>

            <h3>Suggested Trips</h3>

            <p>
                Discover ready-made trip ideas and explore suggested
                experiences around Al-Ahsa.
            </p>

            <a class="trip-primary-btn" href="suggested-trips.php">
                Explore Trips <span>→</span>
            </a>
        </article>


        <!-- My Trips -->
        <article class="trip-step">
            <span class="step-number">03</span>

            <div class="step-icon">⌖</div>

            <h3>My Trips</h3>

            <p>
                View your previously created trips and return to
                your saved itineraries whenever you want.
            </p>

            <a class="trip-primary-btn" href="my-trips.php">
                View My Trips <span>→</span>
            </a>
        </article>

    </div>

</section>

<?php trip_page_footer(); ?>