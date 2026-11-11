<?php
require_once 'db.php';require_once 'trip-common.php';if(!isset($_SESSION['tourist_id'])){header('Location: role-selection.html');exit;}
$touristId=(int)$_SESSION['tourist_id'];$itineraryId=isset($_GET['id'])?(int)$_GET['id']:0;
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['rating'])){
 $target=(int)($_POST['itinerary_id']??0);$rating=max(1,min(5,(int)$_POST['rating']));$comment=trim($_POST['comment']??'');
 $check=$conn->prepare("SELECT itinerary_id FROM itinerary WHERE itinerary_id=? AND tourist_id=?");$check->bind_param('ii',$target,$touristId);$check->execute();$owned=$check->get_result()->fetch_assoc();$check->close();
 if($owned){$find=$conn->prepare("SELECT Itinerary_Review_ID FROM itinerary_reviews WHERE Tourist_ID=? AND Itinerary_ID=? LIMIT 1");$find->bind_param('ii',$touristId,$target);$find->execute();$old=$find->get_result()->fetch_assoc();$find->close();if($old){$s=$conn->prepare("UPDATE itinerary_reviews SET Rating=?,Comment=? WHERE Itinerary_Review_ID=?");$s->bind_param('isi',$rating,$comment,$old['Itinerary_Review_ID']);}else{$s=$conn->prepare("INSERT INTO itinerary_reviews (Tourist_ID,Itinerary_ID,Rating,Comment) VALUES (?,?,?,?)");$s->bind_param('iiis',$touristId,$target,$rating,$comment);}if($s->execute()){$_SESSION['trip_feedback_saved']=true;}else{$_SESSION['trip_feedback_error']='We could not save your review. Please try again.';}$s->close();}
 header('Location: itinerary.php');exit;
}
if($itineraryId>0){$s=$conn->prepare("SELECT itinerary_id,Days,Create_at FROM itinerary WHERE itinerary_id=? AND tourist_id=?");$s->bind_param('ii',$itineraryId,$touristId);}else{$s=$conn->prepare("SELECT itinerary_id,Days,Create_at FROM itinerary WHERE tourist_id=? ORDER BY itinerary_id DESC LIMIT 1");$s->bind_param('i',$touristId);}$s->execute();$trip=$s->get_result()->fetch_assoc();$s->close();
$items=[];if($trip){$s=$conn->prepare("SELECT ii.Day_Number,ii.Start_Time,ii.End_Time,ii.Sequence_Number,p.Name,p.Place_Type,p.Location,p.Image,p.Short_Description FROM itinerary_items ii JOIN places p ON p.Place_ID=ii.Place_ID WHERE ii.itinerary_id=? ORDER BY ii.Day_Number,ii.Sequence_Number");$s->bind_param('i',$trip['itinerary_id']);$s->execute();$r=$s->get_result();while($x=$r->fetch_assoc())$items[]=$x;$s->close();}
$hotels=[];$hr=$conn->query("SELECT Name,Location,Image,Short_Description FROM places WHERE Is_Published=1 AND Place_Type='hotels' ORDER BY Name LIMIT 6");if($hr){while($h=$hr->fetch_assoc())$hotels[]=$h;}
$feedbackSaved=!empty($_SESSION['trip_feedback_saved']);unset($_SESSION['trip_feedback_saved']);$feedbackError=$_SESSION['trip_feedback_error']??'';unset($_SESSION['trip_feedback_error']);
trip_header('My Itinerary','itinerary'); ?>
<section class="about-hero"><span class="about-label">YOUR PERSONAL PLAN</span><h1>My Itinerary</h1><p>Your suggested journey through Al-Ahsa, arranged day by day.</p></section>
<section class="about-content-section"><div class="about-intro-card">
<?php if($feedbackSaved):?><div class="trip-empty">Thank you! Your review has been submitted. You are back at trip planning.</div><?php endif;?>
<?php if($feedbackError):?><div class="trip-empty"><?=trip_e($feedbackError)?></div><?php endif;?>
<?php if(!$trip):?><h2>No itinerary found</h2><p>Create your trip by completing the preferences survey.</p><a class="trip-action" href="survey.php">Create My Trip →</a>
<?php else:?><h2>Your <?= (int)$trip['Days']?>-day trip</h2><p>Created: <?=trip_e($trip['Create_at'])?></p>
<?php $byDay=[];foreach($items as $item)$byDay[(int)$item['Day_Number']][]=$item;for($d=1;$d<=(int)$trip['Days'];$d++):?>
<div class="trip-day"><div class="trip-day-heading"><h2>Day <?=$d?></h2><span class="about-label"><?=count($byDay[$d]??[])?> PLACES</span></div>
<?php if(empty($byDay[$d])):?><div class="trip-empty">No places were assigned for this day.</div><?php else:?><div class="trip-place-list"><?php foreach($byDay[$d] as $item):?><article class="trip-place">
<?php if(!empty($item['Image'])):?><img src="<?=trip_e($item['Image'])?>" alt="<?=trip_e($item['Name'])?>"><?php else:?><div style="width:104px;height:92px;border-radius:11px;background:#edf2ee;display:grid;place-items:center;">🌴</div><?php endif;?>
<div><h3><?=trip_e($item['Name'])?></h3><p><?=trip_e($item['Short_Description']?:$item['Place_Type'])?></p><p class="trip-time"><?=trip_e(date('g:i A',strtotime($item['Start_Time'])))?> – <?=trip_e(date('g:i A',strtotime($item['End_Time'])))?></p></div></article><?php endforeach;?></div><?php endif;?></div>
<?php endfor;?>
<div class="trip-actions center"><a class="trip-action secondary" href="itinerary.php">← Back to Planning</a><a class="trip-action" href="survey.php">Create Another Trip →</a></div>
<div class="trip-review"><span class="about-label">SHARE YOUR EXPERIENCE</span><h2>How was your trip plan?</h2><p>Your feedback helps us improve the suggested itineraries.</p>
<form method="POST"><input type="hidden" name="itinerary_id" value="<?=(int)$trip['itinerary_id']?>"><label for="rating">Your rating</label><div class="trip-rating"><?php for($i=1;$i<=5;$i++):?><label><input type="radio" name="rating" value="<?=$i?>" required> <?=$i?> ★</label><?php endfor;?></div><label for="comment">Comments or suggestions</label><textarea id="comment" name="comment" placeholder="Tell us what you liked or what we can improve..."></textarea><div class="trip-actions center"><button class="trip-action" type="submit">Submit Review <span>→</span></button></div></form></div>
<?php endif;?></div></section>
<?php if($hotels):?><section class="about-section-block"><div class="about-section-heading"><span>STAY IN AL-AHSA</span><h2>Accommodation Options</h2><p>Published hotels listed in Al-Ahsa Vision Hub.</p></div><div class="trip-place-list"><?php foreach($hotels as $h):?><article class="trip-place"><?php if($h['Image']):?><img src="<?=trip_e($h['Image'])?>" alt="<?=trip_e($h['Name'])?>"><?php else:?><div style="width:104px;height:92px;border-radius:11px;background:#edf2ee;display:grid;place-items:center;">🏨</div><?php endif;?><div><h3><?=trip_e($h['Name'])?></h3><p><?=trip_e($h['Short_Description']?:$h['Location'])?></p></div></article><?php endforeach;?></div></section><?php endif;?>
<?php trip_footer();?>
