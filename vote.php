<?php
	$postBody = file_get_contents('php://input');
	$null = null;

	require_once('configuration.php');

	if ($body = json_decode($postBody)) {
		if (!empty($body->sessid)) {
			$session_id = $body->sessid;
			session_id($body->sessid);
			session_start();
			setcookie(session_name(), session_id(), time()+1800);
		}
		else {
			session_start();
			$session_id = session_id();
			setcookie(session_name(), session_id(), time()+1800);
		}

		if (empty($_SESSION['authenticated'])) {
			echo 'Error: You do not have permission to visit this page.';
			die();
		}

		// check response exists
		$get_response_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($get_response_query, 'SELECT id FROM response WHERE id=? LIMIT 1');
		mysqli_stmt_bind_param($get_response_query, 'i', $body->respid);
		mysqli_stmt_execute($get_response_query);
		mysqli_stmt_bind_result($get_response_query, $respId);
		mysqli_stmt_fetch($get_response_query);
		mysqli_stmt_close($get_response_query);

		// if response exists
		if ($respId) {
			$vote_resp_query = mysqli_stmt_init($conn);
			mysqli_stmt_prepare($vote_resp_query, 'SELECT id, vote_count FROM feedback WHERE user_id=? and response_id=? LIMIT 1');
			mysqli_stmt_bind_param($vote_resp_query, 'ii', $_SESSION['user']['id'], $respId);
			mysqli_stmt_execute($vote_resp_query);
			mysqli_stmt_bind_result($vote_resp_query, $feedbackId, $vote);
			mysqli_stmt_fetch($vote_resp_query);
			mysqli_stmt_close($vote_resp_query);

			// feedback already exists
			$insert_vote_query = mysqli_stmt_init($conn);
			if ($feedbackId) {
				$query = 'UPDATE feedback SET vote_count=? WHERE id=?';
				$vote = $vote ? 0 : 1;
				mysqli_stmt_prepare($insert_vote_query, $query);
				mysqli_stmt_bind_param($insert_vote_query, 'ii', $vote, $feedbackId);
			} else {
				$query = 'INSERT INTO feedback (user_id, response_id, vote_count, create_time) VALUES (?, ?, 1, ?)';
				$vote = 1;
				mysqli_stmt_prepare($insert_vote_query, $query);
				mysqli_stmt_bind_param($insert_vote_query, 'iis',$_SESSION['user']['id'], $respId, $null);
			}
			$success = mysqli_stmt_execute($insert_vote_query);
			mysqli_stmt_close($insert_vote_query);

			if (!$success) {
				http_response_code(500);
				echo 'Vote was not successfully inserted into database';
				die();
			}

			$result = array('vote' => $vote);
			echo json_encode($result);
		}

		mysqli_close($conn);
	}
	else {
		mysqli_close($conn);
		http_response_code(400);
		echo 'lis_result_sourcedid is required in the body';
	}
?>