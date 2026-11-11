<?php
require_once 'db.php';require_once 'trip-common.php';
$trips=[
'discover'=>['title'=>'Al-Ahsa Highlights','description'=>'A balanced visit with popular attractions, local dining, cafés and shopping.','types'=>['entertainment','restaurants','cafes','markets'],'icon'=>'✦'],
'family'=>['title'=>'Family Day Out','description'=>'A relaxed plan with entertainment, family dining and café stops.','types'=>['entertainment','restaurants','cafes'],'icon'=>'⌂'],
'culture'=>['title'=>'Heritage & Culture','description'=>'Discover Al-Ahsa attractions and enjoy local food and traditional markets.','types'=>['entertainment','restaurants','markets'],'icon'=>'⌖'],
'shopping'=>['title'=>'Shopping & Dining','description'=>'A simple route combining markets, restaurants and cafés.','types'=>['markets','restaurants','cafes'],'icon'=>'♧']
];
trip_header('Ready-Made Trips','itinerary'); ?>
<section class="about-hero"><span class="about-label">READY-MADE IDEAS</span><h1>Choose Your Trip</h1><p>Choose a trip style, then select the places you would like to visit each day.</p></section>
<section class="about-section-block"><div class="about-info-grid">
<?php foreach($trips as $key=>$trip):?><article class="about-info-card"><div class="about-card-icon"><?=trip_e($trip['icon'])?></div><h3><?=trip_e($trip['title'])?></h3><p><?=trip_e($trip['description'])?></p><a class="trip-action" href="trip-details.php?trip=<?=trip_e($key)?>&step=choose">Choose This Trip <span>→</span></a></article><?php endforeach;?>
</div></section>
<div class="trip-actions center"><a class="trip-action secondary" href="itinerary.php">← Back to Planning</a></div>
<?php trip_footer();?>
