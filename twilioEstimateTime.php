<?php
/*
Template Name: twilioEstimateTime
*/
?>
<?php
require '/opt/bitnami/php/composer/vendor/autoload.php';
require '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;
use Twilio\Twiml;

chdir($ROOT_LOC);

function getSID(){
    global $wpdb;
	global $ATT;
    global $ATT_id;
	global $ATT_tsid;
    
    $sql = 'SELECT '.$ATT_id.' FROM '.$ATT.' WHERE '.$ATT_tsid.' = "'.$_REQUEST['CallSid'].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return $result[0]['order_id'];
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

function twilioSendMes($time, $message){
    global $wpdb;
    global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
    global $TWIL_NUM;
    global $SQL_MSG;
    global $STAT;
    global $STAT_etime;
    global $STAT_ack;
    global $STAT_id;
    global $STAT_ctime;

    //customer confirmed order, get estimated time
    $orderRecord = getSID();

    // there should only be one order per customer, therefore wpdb update should only return 1
    if (1 == $wpdb->update('order_request', array('confirm' => ''.$time.''), array('order_id'=>$orderRecord))){
        $wpdb->update($STAT, array($STAT_etime => ''.$time.'', $STAT_ack => 'Y', $STAT_ctime => date('Y-m-d H:i:s')), array($STAT_id => $orderRecord));
        // send confirmtion message to customer once order is complete
        $client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);

        $sqlMes = $SQL_MSG.$orderRecord.'"';
        $result = $wpdb->get_results($sqlMes, "ARRAY_A");
        try {
            $message = $client->messages->create($result[0]["phone"], array('From' => $TWIL_NUM,'Body' => "Hey ".$result[0]["fname"].", Hungry here. Your order was confirmed by ".$result[0]["name"]." and will be ready ".$message.". Your order id is ".$orderRecord."."));
            logTwil("Order confirmation message sent: " . $message->sid);
        } 
        catch (Exception $e) {
            logTwil("Order confirmation message error: " . $e->getMessage());
        }

        //send message to custome of confirmation
        header('content-type: text/xml');
        $output = new TwiML();
        $output->say('Thank you for confirming order. '.$result[0]["fname"].' will be expecting their order in '.$time.' minutes. Have a nice day.',['voice' => 'alice']);
        echo $output;
    }
    else{
        logTwil('WPDB access confirmation message error: 0 records affected or returned flase.');
    }
    
}


//get dtmf response and accordingly run action, 1 repeat(redirect to page), 9 confirm order
if (!empty($_REQUEST['Digits'])){
    switch($_REQUEST['Digits']){
        case '1':
            twilioSendMes(15, 'in 15 minutes');
            break;
        case '2':
            twilioSendMes(30, 'in 30 minutes');
            break;
        case '3':
            twilioSendMes(45, 'in 45 minutes');
            break;
        case '4':
            twilioSendMes(60, 'in roughly an hour or more');
            break;
    }
    
}/*
	else{//if ($_REQUEST['Digits'] == '7'){
		//no response within 15 seconds, number of times called tried
		chdir($LOG);
		$filename= './noresponse'.substr($orderRecord, 0,15);
		if (file_exists($filename)) {
			$callCount = file_get_contents($filename, FILE_USE_INCLUDE_PATH);
			$callCount = intval($callCount)+1;
			$handle = fopen($filename, 'w');
			fwrite($handle,$callCount);
			fclose($handle);
		} else {
			$handle = fopen($filename, 'w');
			fwrite($handle,'1');
			fclose($handle);
		}
		header("content-type: text/xml; charset=utf-8");
		echo '<Response><Hangup/></Response>';			
	}
}*/
else{
	echo '<p>Digits empty</p>';
}
?>