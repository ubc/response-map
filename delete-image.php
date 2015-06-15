<?php
	require_once('config.php');

	session_set_cookie_params(1800);
	session_start();

	if (empty($_SESSION['authenticated'])) {
		echo 'Error: You do not have permission to visit this page.';
		die();
	}

	if (!empty($_POST['image'])) {
		$filename = explode('/', $_POST['image']);
		$filename = end($filename);
		$success = false;

		// make sure the 'filename' does not cause rerouting before proceeding
		if (strstr($filename, '..')) {
			http_response_code(400);
			echo 'Invalid file name is given.';
			die();
		}

		if (!empty($_POST['id'])) {
			$query = "UPDATE response SET image_url=NULL, thumbnail_url=NULL WHERE id=?";
			$update_response_query = mysqli_stmt_init($conn);
			mysqli_stmt_prepare($update_response_query, $query);
			mysqli_stmt_bind_param($update_response_query, 'i', $_POST['id']);
			$success = mysqli_stmt_execute($update_response_query);
			mysqli_stmt_close($update_response_query);
		} else {
			$success = true;
		}

		if ($success) {
			$directory = dirname(__FILE__);
			if (file_exists($directory.'/files/'.$filename) && file_exists($directory.'/files/thumbnail/'.$filename)) {
				$success = unlink($directory . '/files/' . $filename) && unlink($directory . '/files/thumbnail/' . $filename);
			}
		}

		echo json_encode(array('result' => $success, 'file' => $filename));
	} else {
		http_response_code(400);
		echo 'No response id has been given.';
	}