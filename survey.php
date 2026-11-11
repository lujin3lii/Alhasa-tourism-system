<?php
require_once 'db.php'; require_once 'trip-common.php';
if(!isset($_SESSION['tourist_id'])){header('Location: role-selection.html');exit;}
$questions=[];$res=$conn->query("SELECT Question_ID,Question_Text,Question_Type,Is_Required,Display_Order FROM survey_questions WHERE Display_Order BETWEEN 1 AND 8 ORDER BY Display_Order");
if(!$res){die('Unable to load survey questions. Check survey_questions.');} while($r=$res->fetch_assoc()){$questions[]=$r;}
$optionsByQuestion=[];$or=$conn->query("SELECT Option_ID,Question_ID,Option_Text,Display_Order FROM survey_options ORDER BY Question_ID,Display_Order");
if(!$or){die('Unable to load survey options. Check survey_options.');}while($o=$or->fetch_assoc()){$optionsByQuestion[(int)$o['Question_ID']][]=$o;}
$missing=[];foreach($questions as $q){if(empty($optionsByQuestion[(int)$q['Question_ID']]))$missing[]=(int)$q['Display_Order'];}
trip_header('Create My Own Trip','itinerary'); ?>
<section class="about-hero"><span class="about-label">CREATE YOUR OWN TRIP</span><h1>Tell Us About Your Visit</h1><p>Your answers will help us prepare a suggested itinerary for Al-Ahsa.</p></section>
<section class="about-content-section"><div class="about-intro-card">
<?php if(count($questions)<8): ?><div class="trip-empty">The database needs all eight trip-preference questions (Display_Order 1–8). Currently found: <?=count($questions)?>.</div>
<?php else: ?><form action="generate-itinerary.php" method="POST">
<?php foreach($questions as $q): $qid=(int)$q['Question_ID']; $opts=$optionsByQuestion[$qid]??[]; ?>
<div class="trip-question"><span class="about-label">QUESTION <?= (int)$q['Display_Order'] ?> OF 8</span><h2><?=trip_e($q['Question_Text'])?></h2>
<?php if($opts): ?><div class="trip-question-options"><?php foreach($opts as $op): ?><label class="trip-option"><input type="radio" name="answers[<?=$qid?>]" value="<?=(int)$op['Option_ID']?>" <?=((int)$q['Is_Required']===1?'required':'')?>><span><?=trip_e($op['Option_Text'])?></span></label><?php endforeach;?></div>
<?php else: ?><div class="trip-empty">Options for this question have not been added yet. Add its choices to survey_options to enable this question.</div><?php endif;?></div>
<?php endforeach; ?>
<?php if(!$missing): ?><div class="trip-actions center"><a href="itinerary.php" class="trip-action secondary">← Back</a><button type="submit" class="trip-action">Generate My Trip <span>→</span></button></div><?php else: ?><p class="trip-empty">Missing options for question(s): <?=trip_e(implode(', ',$missing))?>. Add these options in the database before generating a trip.</p><div class="trip-actions center"><a href="itinerary.php" class="trip-action secondary">← Back to Planning</a></div><?php endif; ?>
</form><?php endif;?></div></section>
<?php trip_footer(); ?>
