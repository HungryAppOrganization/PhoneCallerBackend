<?php
/*
Template Name: twiML
*/
?>
<?php
require '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/apps/wordpress/conf/t-conf.php';
use Twilio\Rest\Client;

global $wpdb;

function getSID(){
	chdir($ROOT_LOC);
	$file = "messageRepeatTrack";
	$handle = fopen($file, "r");
	if ($handle) {
		while (($buffer = fgets($handle)) !== false) {
			list($sid, $goToFile) = explode("=>", $buffer);
			if ($sid === $_REQUEST['CallSid']){
				fclose($handle);
				return array($sid,$goToFile);
			}
		}
		fclose($handle);
	}
}

function logTwil($str){
	//time at utc +0
	chdir($LOG);
	$date = getdate();
	$file = $date['month'].$date["mday"].$date['year']."twilio";
	$handle = fopen($file, "a");
	fwrite($handle, $date['hours']."-".$date["minutes"]."-".$date['seconds']."=>\t".$str."\n");
	fclose($handle);
}

$orderRecord = getSID()[1];

//get dtmf response and accordingly run action, 1 repeat(redirect to page), 9 confirm order
if (!empty($_REQUEST["Digits"])){
	if($_REQUEST['Digits'] == '9')
	{
		//customer confirmed order
		$orderRecord = substr($orderRecord, 0,15);

		//$wpdb->update( $table, $data, $where, $format = null, $where_format = null );
		// there should only be one order per customer, therefore wpdb update should only return 1
		if (1 == $wpdb->update('order_request', array('confirm'=>1), array('order_id'=>$orderRecord))){
			header("content-type: text/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<Response><Say>Thank you for confirming order. Have a nice day.</Say></Response>';

			// send confirmtion message to customer once order is complete
			$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);

			$sql = 'SELECT customer.phone FROM order_request LEFT JOIN customer ON order_request.customer_id = customer.cus_id LEFT JOIN restaurant ON order_request.bus_id = restaurant.bus_id WHERE order_request.order_id = "'.$orderRecord.'"';
			$result = $wpdb->get_results($sql, "ARRAY_A");
			$custNum = $result[0]["phone"];

			try {
				$message = $client->messages->create($custNum, array('From' => '+18654844364','Body' => "Hey, Hungry? here, your order was confirmed by the restaurant."));
				logTwil("Confirmation message sent: " . $message->sid);
			} 
			catch (Exception $e) {
				logTwil("Confirmation message error: " . $e->getMessage());
			}
		}
		else{
			header("content-type: text/xml");
			echo '<?xml version="1.0" encoding="UTF-8"?>';
			echo '<Response><Say>Error occured, pleased contact Hungry.</Say></Response>';
		}
	}
	elseif ($_REQUEST['Digits'] == '1') {
		//business want message repeated
		header("content-type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<Response><Redirect method="POST">https://www.swipetobites.com/wp-content/uploads/twilio/'.$orderRecord.'</Redirect></Response>';
	}
	else{//if ($_REQUEST['Digits'] == '7'){
		//no response within 15 seconds, number of times called triedmmmmm
		chdir("log");
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
		header("content-type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<Response><Hangup/></Response>';			
	}
}
?>