<?php
require_once 'db.php'; require_once 'trip-common.php';
if(!isset($_SESSION['tourist_id'])){header('Location: role-selection.html');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'||empty($_POST['answers'])||!is_array($_POST['answers'])){header('Location: survey.php');exit;}
$answers=[];foreach($_POST['answers'] as $qid=>$oid){$qid=filter_var($qid,FILTER_VALIDATE_INT);$oid=filter_var($oid,FILTER_VALIDATE_INT);if($qid&&$oid)$answers[$qid]=$oid;}
$qres=$conn->query("SELECT Question_ID,Display_Order FROM survey_questions WHERE Display_Order BETWEEN 1 AND 8");$required=[];while($q=$qres->fetch_assoc())$required[(int)$q['Question_ID']]=(int)$q['Display_Order'];
if(count($required)!==8||count(array_intersect_key($answers,$required))!==8){header('Location: survey.php');exit;}
function trip_option_text($conn,$optionId,$questionId){$s=$conn->prepare("SELECT Option_Text FROM survey_options WHERE Option_ID=? AND Question_ID=?");$s->bind_param('ii',$optionId,$questionId);$s->execute();$r=$s->get_result()->fetch_assoc();$s->close();return $r['Option_Text']??'';}
$byOrder=[];foreach($required as $qid=>$order){$byOrder[$order]=trip_option_text($conn,$answers[$qid],$qid);if($byOrder[$order]===''){header('Location: survey.php');exit;}}
$daysText=strtolower($byOrder[1]);$days=1;if(strpos($daysText,'more than a week')!==false)$days=8;elseif(strpos($daysText,'week')!==false)$days=7;elseif(preg_match('/\b([1-6])\b/',$daysText,$m))$days=(int)$m[1];
$time=strtolower($byOrder[2]);$placePref=strtolower($byOrder[5]);$restaurantPref=strtolower($byOrder[6]);$cafePref=strtolower($byOrder[7]);$shoppingPref=strtolower($byOrder[8]);
$types=['entertainment'];if(strpos($restaurantPref,'not interested')===false)$types[]='restaurants';if(strpos($cafePref,'not interested')===false)$types[]='cafes';if(strpos($shoppingPref,'yes')!==false)$types[]='markets';
// Hotel records are shown separately as accommodation suggestions, not as timed sightseeing stops.
$placeRows=[];$marks=implode(',',array_fill(0,count($types),'?'));$sql="SELECT Place_ID,Name,Place_Type,Location,Image,Short_Description FROM places WHERE Is_Published=1 AND Place_Type IN ($marks) ORDER BY Place_Type,Name";
$stmt=$conn->prepare($sql);$bind=str_repeat('s',count($types));$stmt->bind_param($bind,...$types);$stmt->execute();$res=$stmt->get_result();while($p=$res->fetch_assoc())$placeRows[]=$p;$stmt->close();
if(!$placeRows){trip_header('Trip Generation');echo '<div class="trip-empty">There are no published places matching this trip yet. Please publish places first.</div><div class="trip-actions center"><a class="trip-action secondary" href="survey.php">Back to Survey</a></div>';trip_footer();exit;}
// Keep the selection balanced: no more than three activities per day.
shuffle($placeRows);$placeRows=array_slice($placeRows,0,min(count($placeRows),$days*3));
$slots=['morning'=>[['09:00:00','10:30:00'],['11:00:00','12:30:00'],['13:00:00','14:30:00']],'afternoon'=>[['12:00:00','13:30:00'],['14:00:00','15:30:00'],['16:00:00','17:00:00']],'evening'=>[['17:00:00','18:30:00'],['19:00:00','20:30:00'],['21:00:00','22:00:00']],'all'=>[['09:00:00','10:30:00'],['14:00:00','15:30:00'],['18:00:00','19:30:00']]];
$slotKey='all';if(strpos($time,'morning')!==false)$slotKey='morning';elseif(strpos($time,'afternoon')!==false)$slotKey='afternoon';elseif(strpos($time,'evening')!==false)$slotKey='evening';
$placeReason='Selected according to your trip preferences: '.$byOrder[5].'; dining: '.$byOrder[6].'.';
$conn->begin_transaction();try{
$touristId=(int)$_SESSION['tourist_id'];$s=$conn->prepare("INSERT INTO survey (tourist_id,Create_at) VALUES (?,NOW())");if(!$s)throw new Exception($conn->error);$s->bind_param('i',$touristId);if(!$s->execute())throw new Exception($s->error);$surveyId=$conn->insert_id;$s->close();
$s=$conn->prepare("INSERT INTO survey_answers (Survey_ID,Question_ID,Option_ID) VALUES (?,?,?)");if(!$s)throw new Exception($conn->error);foreach($answers as $qid=>$oid){$s->bind_param('iii',$surveyId,$qid,$oid);if(!$s->execute())throw new Exception($s->error);}$s->close();
$s=$conn->prepare("INSERT INTO itinerary (tourist_id,Survey_id,Days,Create_at) VALUES (?,?,?,NOW())");if(!$s)throw new Exception($conn->error);$s->bind_param('iii',$touristId,$surveyId,$days);if(!$s->execute())throw new Exception($s->error);$itineraryId=$conn->insert_id;$s->close();
$s=$conn->prepare("INSERT INTO itinerary_items (itinerary_id,Day_Number,Place_ID,Start_Time,End_Time,Sequence_Number,Reason_Chosen) VALUES (?,?,?,?,?,?,?)");if(!$s)throw new Exception($conn->error);
foreach($placeRows as $i=>$p){$day=(int)floor($i/3)+1;$seq=($i%3)+1;$start=$slots[$slotKey][$seq-1][0];$end=$slots[$slotKey][$seq-1][1];$pid=(int)$p['Place_ID'];$s->bind_param('iiissis',$itineraryId,$day,$pid,$start,$end,$seq,$placeReason);if(!$s->execute())throw new Exception($s->error);}$s->close();$conn->commit();header('Location: my-itinerary.php?id='.$itineraryId);exit;
}catch(Throwable $e){$conn->rollback();trip_header('Trip Generation');echo '<div class="trip-empty">We could not generate your trip: '.trip_e($e->getMessage()).'</div><div class="trip-actions center"><a class="trip-action secondary" href="survey.php">Back to Survey</a></div>';trip_footer();exit;}
