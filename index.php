<?php
$data = file_get_contents("php://input");
if(empty($data)) {
	require_once 'classes/class.guest.php';
	$guest = new guest();
} else {
	require_once 'classes/class.api.php';
	$api = new api($data);
}
?>