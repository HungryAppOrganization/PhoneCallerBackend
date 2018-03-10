<?php
/*
Template Name: checkVM
*/
?>
<?php
//handle answered by voicemail

require_once '/opt/bitnami/apps/wordpress/conf/t-conf.php';

function getORD(){
	chdir($ROOT_LOC);
	$file = "messageRepeatTrack";
	$handle = fopen($file, "r");
	if ($handle) {
		while (($buffer = fgets($handle)) !== false) {
			list($sid, $goToFile) = explode("=>", $buffer);
			if ($sid === $_REQUEST['CallSid']){
				fclose($handle);
				return array($sid,$goToFile, getcwd());
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

if ($_REQUEST['AnsweredBy'] == 'human'){
	//answered by human
    $ord = getORD()[1];
    header("content-type: text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Redirect method="POST">https://www.swipetobites.com/wp-content/uploads/twilio/'.$ord.'</Redirect>';
    echo '</Response>';
}
else{
	//answered by machine or other
    header("content-type: text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Response>';
    echo '<Say>A customer wanted to make a order. Please call Hungry.</Say>';
    echo '<Redirect method="POST">https://www.swipetobites.com/twiliores/?Digits=7</Redirect>';
    echo '</Response>';
    logTwil("Received voicemail with callID: ".getORD()[0]);
}
?>