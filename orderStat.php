<?php
/*
Template Name: orderStat
*/
?>
<?php
header("Content-Type: application/json; charset=UTF-8");

require '/opt/bitnami/php/composer/vendor/t-conf.php';

function provide_info(){
    global $wpdb;

    //Set up SQL and query database
	$sql = $SQL_STATUS.$_REQUEST["ord"].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    echo $result[0]['order_id'].$result[0]['confirmed'].$result[0]['est_time'].$result[0]['attempt_time'].$result[0]['twilio_CallSID'];
    echo $result[1]['order_id'].$result[1]['confirmed'].$result[1]['est_time'].$result[1]['attempt_time'].$result[1]['twilio_CallSID'];
    echo 'Deh ya';
    return json_encode($result);
}

if (!empty($_REQUEST["ord"])){
    //Check entry
	if (strlen($_REQUEST["ord"])!=15){
        $arr = array('message' => 'Request cannot be completed.');
        echo json_encode($arr);
		die();
	}
	elseif (substr($_REQUEST["ord"], 0,3) != "ord"){
        $arr = array('message' => 'Request cannot be completed.');
        echo json_encode($arr);
		die();
    }

    echo provide_info();
}
else{
    $arr = array('message' => 'ID not given', 'content' => $_REQUEST["ord"]);
    echo json_encode($arr);
}
?>