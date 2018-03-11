<?php
/*
Template Name: outgoingcalls
*/
?>
<?php
/**
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.0
 */

?>
<?php
// Use the REST API Client to make requests to the Twilio REST API
require '/opt/bitnami/php/composer/vendor/autoload.php';
require '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Rest\Client;

chdir($ROOT_LOC);

global $wpdb;
global $call;
global $result;

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

function createXML($filename, $cusname, $busname, $menu, $cusphone){
	//Create the XML file
	$dom = new DOMDocument('1.0','UTF-8');
	$dom->formatOutput = true;
	$root = $dom->createElement('Response');
	$dom->appendChild($root);

	$pause = $dom->createElement('Pause');
	$pause->setAttribute('length',1);
	$gather = $dom->createElement('Gather');
	$gather->setAttribute('action',"https://www.swipetobites.com/twilio-menu/");
	$gather->setAttribute('method','POST');
	$gather->setAttribute('timeout',15);
	$gather->setAttribute('numDigits',1);
	$gather->appendChild($dom->createElement('Say', 'Are you ready for their order? Press 1 if yes, Press 2 if you need this message repeated.'));

	$root->appendChild($pause);
	$root->appendChild($dom->createElement('Say', 'Hello, this is a call from the Hungry app,​​ the customers name is '.$cusname.' and they would like to place an order to come pick up. Their phone number is '.num_to_text($cusphone).'.'));
	$root->appendChild($gather);
	$root->appendChild($dom->createElement('Redirect', "https://www.swipetobites.com/twilio-menu/?Digits=2"));

	$dom->save($filename) or (logTwil('Create XML error: XML file Create Error') and die());

	//create menu xml file
	$domMen = new DOMDocument('1.0','UTF-8');
	$domMen->formatOutput = true;
	$rootMen = $domMen->createElement('Response');
	$domMen->appendChild($rootMen);

	$pause = $domMen->createElement('Pause');
	$pause->setAttribute('length',1);
	$gather = $domMen->createElement('Gather');
	$gather->setAttribute('action',"https://www.swipetobites.com/twiliores/");
	$gather->setAttribute('method','POST');
	$gather->setAttribute('timeout',15);
	$gather->setAttribute('numDigits',1);

	$rootMen->appendChild($pause);
	$rootMen->appendChild($domMen->createElement('Say', $cusname.' wants to order​ '.$menu.'.'));
	$gather->appendChild($domMen->createElement('Say', 'Did you get all that? Press 1 if yes, press 2 if you need this message repeated.'));
	$rootMen->appendChild($gather);
	$domMen->save(substr($filename, 0, 15).'Menu.xml');
}

//convert twilio number into text
function num_to_text($num){
	$text = '';
	for ($i = 1; $i <= 11; $i++) {
		$text = $text.$num[$i].',,,';
	}
	return $text;
}

//essential for implementing repeat message and voicemail
function createCallRecord($filename, $num, $sid){
	$uniCall = 'messageRepeatTrack';
	$handle = fopen($uniCall, 'a') or (logTwil('Create call record error: Cannot open file '.$uniCall) and die());
	$data = $sid.'=>'.$filename.PHP_EOL;
	fwrite($handle, $data);
	fclose($handle);
}

function makeCall($filename, $cusphone){
	global $TWIL_ACC_SID;
	global $TWIL_TOKEN;
	global $TWIL_NUM;
	$client = new Client($TWIL_ACC_SID, $TWIL_TOKEN);
	try {
		$call = $client->calls->create($cusphone, $TWIL_NUM, array(
			"url" => "https://www.swipetobites.com/checkvm", 
			"machineDetection" => "Enable", 
			"MachineDetectionTimeout" => "15"));
		createCallRecord($filename, $cusphone, $call->sid);
        logTwil("Started call: " . $call->sid);
    } catch (Exception $e) {
		logTwil("Error: " . $e->getMessage());
		die();
    }
}

$ordid = $_REQUEST["ord"];

if (!empty($ordid)){
	//Check entry
	if (strlen($ordid)!=15){
		logTwil("HTTP POST Error: Length requirement not met");
		die();
	}
	elseif (substr($ordid, 0,3) != "ord"){
		logTwil("HTTP POST Error: Begin requirements not met");
		die();
	}
	
	//Set up SQL and query database
	$sql = $SQL_STATEMENT.$ordid.'"';
	$result = $wpdb->get_results($sql, "ARRAY_A");
	
	if($result)	{		
		$filename = $ordid.".xml";	
		createXML($filename, $result[0]["fname"], $result[0]["name"], $result[0]["menu_item"], $result[0]["phone"]);
		//in final deployment change to restaurant/business phone
		makeCall($filename, $result[0]["phone"]);
	}
}
else{
 	logTwil("NO ORDER ID PROVIDED!");
}
?>