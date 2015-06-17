<?php
	require_once('lti.php');
	require_once('configuration.php');

	/* Check if a valid lti request received */
	$lti = new Lti();
	$lti->require_valid(); // Returns error message if not a valid LTI request

	$question_id = end(explode('-', $_SESSION['config']['resource_link_id']));

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
	}

	$null = NULL;

	// Check to see if user exists
	$select_user_query = mysqli_stmt_init($conn);
	mysqli_stmt_prepare($select_user_query, 'SELECT id FROM user WHERE userId=? LIMIT 1');
	mysqli_stmt_bind_param($select_user_query, 'i', $_SESSION['config']['user_id']);
	mysqli_stmt_execute($select_user_query);
	mysqli_stmt_bind_result($select_user_query, $userId);
	mysqli_stmt_fetch($select_user_query);
	mysqli_stmt_close($select_user_query);

	// Check to see if resource exists
	$select_resource_query = mysqli_stmt_init($conn);
	mysqli_stmt_prepare($select_resource_query, 'SELECT id FROM resource WHERE map_id=? LIMIT 1');
	mysqli_stmt_bind_param($select_resource_query, 'i', $question_id);
	mysqli_stmt_execute($select_resource_query);
	mysqli_stmt_bind_result($select_resource_query, $resourceId);
	mysqli_stmt_fetch($select_resource_query);
	mysqli_stmt_close($select_resource_query);

	// if user does not exist in the system, add the user
	if (!$userId) {
		$add_user_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($add_user_query, 'INSERT INTO user (userId, create_time) VALUES(?, ?)');
		mysqli_stmt_bind_param($add_user_query, "ss", $_SESSION['config']['user_id'], $null);
		mysqli_stmt_execute($add_user_query);
		$_SESSION['user'] = array('id' => mysqli_stmt_insert_id($add_user_query));
		mysqli_stmt_close($add_user_query);
	} else {
		$_SESSION['user'] = array('id' => $userId);
	}

	// if map does not exist in the system, add the resource
	if (!$resourceId) {
		$add_resource_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($add_resource_query, 'INSERT INTO resource (course_id, map_id, create_time) VALUES (?, ?, ?)');
		mysqli_stmt_bind_param($add_resource_query, 'sss', $_SESSION['config']['context_id'], $question_id, $null);
		mysqli_stmt_execute($add_resource_query);
		$_SESSION['resource'] = array('id' => mysqli_stmt_insert_id($add_resource_query), 'map_id' => $question_id);
		mysqli_stmt_close($add_resource_query);
	} else {
		$_SESSION['resource'] = array('id' => $resourceId, 'map_id' => $question_id);
	}

	// if user and resource exists
	if ($_SESSION['user']['id'] && $_SESSION['resource']['id']) {
		$count_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($count_query, 'SELECT count(*) as count FROM response WHERE resource_id=? AND user_id=?');
		mysqli_stmt_bind_param($count_query, 'ii', $_SESSION['resource']['id'], $_SESSION['user']['id']);
		mysqli_stmt_execute($count_query);
		mysqli_stmt_bind_result($count_query, $count);
		mysqli_stmt_fetch($count_query);
		mysqli_stmt_close($count_query);

		mysqli_close($conn);
		print_r($_POST);

		if ($count > 0) {
			// Show map
			header('Location: map.php');
		} else {
			header('Location: response.php');
		}
	}

	mysqli_close($conn);
?>