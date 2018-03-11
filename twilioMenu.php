<?php
/*
Template Name: twilioMenu
*/
?>
<?php
require_once '/opt/bitnami/php/composer/vendor/autoload.php';
require_once '/opt/bitnami/php/composer/vendor/t-conf.php';
use Twilio\Twiml;

chdir($ROOT_LOC);

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

$file = "messageRepeatTrack";
$handle = fopen($file, "r");
if ($handle) {
    while (($buffer = fgets($handle)) !== false) {
        list($sid, $goToFile) = explode("=>", $buffer);
        if ($sid === $_REQUEST['CallSid']){
            $order = $goToFile;
            break;
        }
    }
    fclose($handle);
}

if ($order == 'DESC'){
    logTwil('TwilioMenu: No CallSid proved or matched');
    die();
}
header('content-type: text/xml');
if ($_REQUEST['Digits'] == 1){
    //continue to say menu
    $order= substr($order, 0,15).'Menu.xml';
    $output = new TwiML();
    $output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order, ['method'=>'POST']);
    echo $output;
}
else{
    //Repeat initial message
    $output = new TwiML();
    $output->redirect('https://www.swipetobites.com/wp-content/uploads/twilio/'.$order, ['method'=>'POST']);
    echo $output;
}
?>