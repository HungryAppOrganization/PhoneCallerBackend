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
use Twilio\Rest\Client;

/*define( "ABS_PATH", getcwd() . DIRECTORY_SEPARATOR );
define( "LOG_FILE", ABS_PATH . "log.txt" );*/

chdir("wp-content/uploads/twilio");

global $wpdb;
global $call;
global $result;

function logTwil($str){
	//time at utc +0
	chdir("log");
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
	$sql = 'SELECT order_request.order_id, customer.fname, customer.phone, restaurant.name, restaurant.bus_phone, order_request.menu_item FROM order_request LEFT JOIN customer ON order_request.customer_id = customer.cus_id LEFT JOIN restaurant ON order_request.bus_id = restaurant.bus_id WHERE order_request.order_id = "'.$ordid.'"';
	$result = $wpdb->get_results($sql, "ARRAY_A");
	
	//If the query returned results, loop through each result
	if($result)	{
		// echo "<p>id: ".$result[0]["order_id"]
		// . "<br>Customer name: " . $result[0]["fname"]
		// . "<br>Customer phone: " . $result[0]["phone"]
		// . "<br>Restaurant name: " . $result[0]["name"]
		// . "<br>Restaurant phone: " . $result[0]["bus_phone"]
		// . "<br>Item Purchased: " . $result[0]["menu_item"]."</p>";
		
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