<?php
/*
Template Name: twilioMenu
*/
?>
<?php
require_once '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;
use Twilio\Twiml;

global $wpdb;

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
	chdir($LOG);
	$date = getdate();
	$file = $date['month'].$date["mday"].$date['year']."twilio";
	$handle = fopen($file, "a");
	fwrite($handle, $date['hours']."-".$date["minutes"]."-".$date['seconds']."=>\t".$str."\n");
	fclose($handle);
}

function noResponseMsg($order_id){
    global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
    global $TWIL_NUM;
    global $SQL_MSG2;
    global $ORD_cnum;
    global $ORD_rname;
    global $wpdb;


    $ordID = getOrdID();

    //send meesage to customer that restaurant didnt respond (got voicemail)
	$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);
    $result = $wpdb->get_results($SQL_MSG2.$ordID.'"', "ARRAY_A");
	try {
		$message = $client->messages->create($result[0][$ORD_cnum], array('From' => $TWIL_NUM, 'Body' => $result[0][$ORD_rname]." gave no response for order id: ".$order_id."."));
		logTwil($_REQUEST['CallSid'].": Got voicemail");
	} 
	catch (Exception $e) {
		logTwil("Error delivering message with Twilio. " . $e->getMessage());
	}
	
}


$sql = 'SELECT '.$STAT_id.', '.$STAT_count.' FROM '.$STAT.' WHERE '.$STAT_tsid.' = "'.$_REQUEST['CallSid'].'"';
$result = $wpdb->get_results($sql, "ARRAY_A");
$order = $result[0][$STAT_id];
$count = $result[0][$STAT_count];

if (is_null($order)){
    logTwil('TwilioMenu: No CallSid provided or matched');
    die();
}
logTwil($_REQUEST['CallSid'].": twilioMenu, digit received is ".$_REQUEST['Digits']);
header('content-type: text/xml');
if ($_REQUEST['Digits'] == 1){
    // continue to say menu
    $order= substr($order, 0,15).'Menu.xml';
}
elseif ($_REQUEST['Digits'] == 99){
	// initial instruction will repeat twice, if no response then taken as voicemail and hangs up
	if ($count >= 1){
        //means it as already repeated twice, there fore taken as voicemail
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
		noResponseMsg($order);
		echo '<Response><Hangup/></Response>';
		die();
	}
	else{
        //repeat message once more
		$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
	}
	$order= substr($order, 0,15).'.xml';
}
elseif ($_REQUEST['Digits'] == 3) {
	echo '<Response><Hangup/></Response>';
	$wpdb->update($STAT, array($STAT_count => ++$count), array($STAT_tsid => $_REQUEST['CallSid']));
	restDoesNotDel($order);
}
else {
    // repeat initial message
    $order= substr($order, 0,15).'.xml';
}

$output = new TwiML();
$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order, ['method'=>'POST']);
echo $output;
?>