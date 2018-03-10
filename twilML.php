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

function getSID(){
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

//get dtmf response and accordingly run action, 1 repeat(redirect to page), 9 confirm order
if (!empty($_REQUEST['Digits'])){
	if($_REQUEST['Digits'] == '1')
	{	
		//business wants to confirm receipt of order
		$output = new TwiML();
		$output->say('When should the customer expect to come pick up the food?');
		$gather = $output->gather(['action'=> 'https://www.swipetobites.com/twilio-estimate-time/', 'method'=>'POST', 'timeout' => '15', 'numDigits'=>'1']);
		$gather->say('Press 1 if in 15 minutes,, 2 if 20 to 30 minutes,, 3 for 35 to 45 minutes,, or 4 if roughly an hour or more.');
		$output->redirect('https://www.swipetobites.com/twilio-estimate-time',['method'=>'POST']);
		echo $output;
	}
	else{
		$order = getSID()[1];
		//business want menu repeated or button 2 not pressed 
		$output = new TwiML();
		$output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.substr($order, 0,15).'Menu.xml', ['method'=>'POST']);
		echo $output;
	}
}
else{
	echo '<p>Digits empty</p>';
}
?>