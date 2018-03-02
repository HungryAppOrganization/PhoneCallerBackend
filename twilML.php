<?php
/*
Template Name: twiML
*/
?>
<?php
require '/opt/bitnami/php/composer/vendor/autoload.php';
use Twilio\Rest\Client;

global $wpdb;

function getSID(){
	//get main folder structure/replace/
	//file to use/replace/
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
	chdir("log");
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
			//message confirmation/replace/
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
		//log/replace/
		//what if no respons/replace/
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