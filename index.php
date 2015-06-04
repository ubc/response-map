<?php
	if (isset($_POST['session_id']) && $_POST['session_id']) {
		$session_id = $_POST['session_id'];
		session_id($_POST['session_id']);
		session_start();
	}
	else {
		session_start();
		$session_id = session_id();
	}

	require_once('lti.php');

	/* Check if a valid lti request received */
	$lti = new Lti();
	$lti->require_valid(); // Returns error message if not a valid LTI request

	$question_id = end(explode('-', $_SESSION['lti']['resource_link_id']));

	require_once('config.php');

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
	}

	$null = NULL;

	require_once('process-text.php');

	// Ensure that the name, location and response are http and quote escaped
	$_POST = escapeUserInput($_POST);

	// Check to see if student has submitted fullname and location
	$select_user_query = mysqli_query($conn, 'SELECT id FROM user WHERE userId="' . $_SESSION['lti']['user_id'] . '" LIMIT 1');
	$user_row = mysqli_fetch_object($select_user_query);
	$select_resource_query = mysqli_query($conn, 'SELECT id FROM resource WHERE map_id="' . $question_id . '" LIMIT 1');
	$resource_row = mysqli_fetch_object($select_resource_query);

	// if user does not exist in the system, add the user
	if (empty($user_row)) {
		$add_user_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($add_user_query, 'INSERT INTO user (userId, create_time) VALUES(?, ?)');
		mysqli_stmt_bind_param($add_user_query, "ss", $_SESSION['lti']['user_id'], $null);
		mysqli_stmt_execute($add_user_query);
		$_SESSION['user'] = array('id' => mysqli_stmt_insert_id($add_user_query));
		mysqli_stmt_close($add_user_query);
	} else {
		$_SESSION['user'] = array('id' => $user_row->id);
	}

	// if map does not exist in the system, add the resource
	if (empty($resource_row)) {
		$add_resource_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($add_resource_query, 'INSERT INTO resource (course_id, map_id, create_time) VALUES (?, ?, ?)');
		mysqli_stmt_bind_param($add_resource_query, 'sss', $_SESSION['lti']['context_id'], $question_id, $null);
		mysqli_stmt_execute($add_resource_query);
		$_SESSION['resource'] = array('id' => mysqli_stmt_insert_id($add_resource_query));
		mysqli_stmt_close($add_resource_query);
	} else {
		$_SESSION['resource'] = array('id' => $resource_row->id, 'map_id' => $question_id);
	}

	// if user and resource exists
	if ($_SESSION['user']['id'] && $_SESSION['resource']['id']) {
		$select_response_query = mysqli_query($conn, 'SELECT count(*) as count FROM response WHERE resource_id = "' . $_SESSION['resource']['id'] . '" AND user_id = "' . $_SESSION['user']['id'] . '"');
		$count = mysqli_fetch_object($select_response_query)->count;

		if ($count > 0) {
			// Show map
			header('Location: map.php');
		} else {
			header('Location: response.php');
		}
	}
?>