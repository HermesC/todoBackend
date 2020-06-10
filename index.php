<?php
	define('__ROOT__', dirname(dirname(__FILE__)));
	require_once(__ROOT__.'\todo\api\todoAPI.php');
	try {
    $api = new todoAPI();
    echo $api->run();
	} catch (Exception $e) {
    echo json_encode(Array('error' => $e->getMessage()));
}
	exit;
?>
Something is wrong with the XAMPP installation :-(
