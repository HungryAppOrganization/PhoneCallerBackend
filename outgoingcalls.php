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

function createXML($filename, $cusname, $busname, $menu){
	//Create the XML file
	$dom = new DOMDocument('1.0','UTF-8');
	$dom->formatOutput = true;
	$root = $dom->createElement('Response');
	$dom->appendChild($root);

	$pause = $dom->createElement('Pause');
	$pause->setAttribute('length',2);
	$gather = $dom->createElement('Gather');
	$gather->setAttribute('action',"https://www.swipetobites.com/twiliores/");
	$gather->setAttribute('method','POST');
	$gather->setAttribute('timeout',15);
	$gather->setAttribute('numDigits',1);
	$gather->appendChild($dom->createElement('Say', 'To repeat message press 1. To confirm order press 9'));

	$root->appendChild($pause);
	$root->appendChild($dom->createElement('Say', 'Hello '.$busname.'. '.$cusname.' would like to make a order.'));
	$root->appendChild($pause);
	$root->appendchild($dom->createElement('Say', $menu."."));
	$root->appendChild($gather);
	$root->appendChild($dom->createElement('Redirect', 'https://www.swipetobites.com/twiliores/?Digits=7'));

	//save in file
	$dom->save($filename) or (logTwil('Create XML error: XML file Create Error') and die());
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
		createXML($filename, $result[0]["fname"], $result[0]["name"], $result[0]["menu_item"]);
		//in final deployment change to restaurant/business phone
		makeCall($filename, $result[0]["phone"]);
	}
}
else{
 	logTwil("NO ORDER ID PROVIDED!");
}
?>