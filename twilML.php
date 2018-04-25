<?php
/*
Template Name: twilML
*/
?>
<?php
require_once '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Twiml;

chdir($ROOT_LOC);

function getOrdID(){
	global $wpdb;
	global $STAT;
    global $STAT_id;
	global $STAT_tsid;
    
    $sql = 'SELECT '.$STAT_id.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return $result[0][$STAT_id];
}

function logTwil($str){
	global $LOG;
	//time at utc +0
	$date = getdate();
	$file = $date['month'].$date["mday"].$date['year']."twilio";
	$handle = fopen($LOG."/".$file, "a");
	fwrite($handle, $date['hours']."-".$date["minutes"]."-".$date['seconds']."=>\t".$str."\n");
	fclose($handle);
}

logTwil($_REQUEST['CallSid'].": twilML, digit received is ".$_REQUEST['Digits']);
//get dtmf response and accordingly run action
if (!empty($_REQUEST['Digits'])){
	header('content-type: text/xml');
	if($_REQUEST['Digits'] == '1')
	{	
		//business wants to confirm receipt of order
		$output = new TwiML();
		$output->say('When should the customer expect to come pick up the food?',['voice' => 'alice']);
		$gather = $output->gather(['action'=> 'https://www.swipetobites.com/twilioesttime/', 'method'=>'POST', 'timeout' => '15', 'numDigits'=>'1']);
		$gather->say('Press 0 if this order connot be completed,, 1 if can be picked up in 15 minutes,, 2 if 20 to 30 minutes,, 3 for 35 to 45 minutes,, or 4 if roughly an hour or more.',['voice' => 'alice']);
		$output->redirect('https://www.swipetobites.com/twilioesttime',['method'=>'POST']);
		echo $output;
	}
	else{
		$sql = 'SELECT '.$STAT_id.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
		$result = $wpdb->get_results($sql, "ARRAY_A");
		$order = getOrdID();

		//business want menu repeated 
		$output = new TwiML();
		$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order.'Menu.xml', ['method'=>'POST']);
		echo $output;
	}
}
else{
	echo '<p>Digits empty</p>';
}
?>