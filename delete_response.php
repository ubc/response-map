<?php
require_once('configuration.php');

session_start();
setcookie(session_name(), session_id(), time()+1800);

if (empty($_SESSION['authenticated'])) {
	echo 'Error: You do not have permission to visit this page.';
	die();
}

$body = json_decode(file_get_contents('php://input'));

if (!empty($body->respId)) {
	// query for response
	$get_response_query = mysqli_stmt_init($conn);
	mysqli_stmt_prepare($get_response_query, 'SELECT id, resource_id FROM response WHERE id=? LIMIT 1');
	mysqli_stmt_bind_param($get_response_query, 'i', $body->respId);
	mysqli_stmt_execute($get_response_query);
	mysqli_stmt_bind_result($get_response_query, $respId, $resourceId);
	mysqli_stmt_fetch($get_response_query);
	mysqli_stmt_close($get_response_query);

	if ($resourceId == $_SESSION['resource']['id']) {
		$delete_response_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($delete_response_query, 'UPDATE response SET deleted=1 WHERE id=?');
		mysqli_stmt_bind_param($delete_response_query, 'i', $respId);
		mysqli_stmt_execute($delete_response_query);
		mysqli_stmt_close($delete_response_query);

		echo json_encode(array('responseId' => $respId));
	} else {
		http_response_code(500);
		echo 'Response was not successfully deleted.';
		die();
	}
} else {
	http_response_code(400);
	echo 'Response Id is required.';
}

mysqli_close($conn);