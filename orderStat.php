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
    global $SQL_STATUS;

    //Set up SQL and query database
	$sql = $SQL_STATUS.$_REQUEST["ord"].'"';
    $result = $wpdb->get_results($sql, "ARRAY_A");
    return json_encode(array('errors' => array('none'), 'status' => ($result == null ? array('empty') : $result)));
}

if (!empty($_REQUEST["ord"])){
    //Check entry
	if (strlen($_REQUEST["ord"])!=15){
        echo json_encode(array('errors' => array('Request cannot be completed.'), 'status' => array('null')));
		die();
	}
	elseif (substr($_REQUEST["ord"], 0,3) != "ord"){
        echo json_encode(array('errors' => array('Request cannot be completed.'), 'status' => array('null')));
		die();
    }

    echo provide_info();
}
else{
    echo json_encode(array('errors' => array('ID not given'), 'status' => array('null')));
}
?>