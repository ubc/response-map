<?php
	session_start();
	require_once('config.php');
	require_once('process-text.php');

	function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}

		return $val;
	}

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
	}

	$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
	$assigned_filename = md5($_SESSION['lti']['user_id'] . $_SESSION['resource']['map_id']);
	$success = false;

	if (isset($_POST['submit']) && $_POST['submit'] == "Edit" && !empty($_POST['user_location'])) {
		$_POST = array_map('escapeInput', $_POST);
		$head = empty($_POST['user_fullname']) ? NULL: $_POST['user_fullname'];
		$description = empty($_POST['user_response']) ? NULL: $_POST['user_response'];
		$image_url = NULL;
		$thumbnail_url = NULL;
		if (!empty($_POST['user_image_url']) && !empty($_POST['user_thumbnail_url'])) {
			$image_url = $_POST['user_image_url'];
			$thumbnail_url = $_POST['user_thumbnail_url'];
		}
		$location = $_POST['user_location'];
		$update_response_query = mysqli_stmt_init($conn);
		$geocode = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($location) . "&sensor=false&key=" . $google_key));
		if ($geocode->status === "OK") {
			mysqli_stmt_prepare($update_response_query, 'UPDATE response SET head=?, description=?, location=?, ' .
			  'latitude=?, longitude=?, image_url=?, thumbnail_url=? WHERE id=?');
			mysqli_stmt_bind_param($update_response_query, 'sssddssi', $head, $description, $location,
				$geocode->results[0]->geometry->location->lat, $geocode->results[0]->geometry->location->lng,
				$image_url, $thumbnail_url, $id);
			$success = mysqli_stmt_execute($update_response_query);
			mysqli_stmt_close($update_response_query);
		}
		if ($success) {
			$message = 'Your response updated successfully.';
			header('Location: map.php?message='.$message);
		} else {
			$head = $_POST['user_response'];
			$description = $_POST['user_response'];
			$location = $_POST['user_location'];
			$image_url = $_POST['user_image_url'];
			$thumbnail_url = $_POST['user_thumbnail_url'];
			// general error message for now
			$message = 'Error: Please try submitting again.';
		}
	} else {
		// get original response
		$response_query = mysqli_stmt_init($conn);
		mysqli_stmt_prepare($response_query, 'SELECT head, location, description, image_url, thumbnail_url FROM response WHERE id=? and resource_id=? LIMIT 1');
		mysqli_stmt_bind_param($response_query, 'ii', $id, $_SESSION['resource']['id']);
		mysqli_stmt_execute($response_query);
		mysqli_stmt_bind_result($response_query, $head, $location, $description, $image_url, $thumbnail_url);
		mysqli_stmt_fetch($response_query);
		mysqli_stmt_close($response_query);
	}
?>
<html>
	<head>
		<?php include('html/header.html'); ?>

		<script>
			/*jslint unparam: true */
			/*global window, $ */
			$(function () {
				'use strict';
				// Change this to the location of your server-side upload handler:
				var url = 'upload.php?user_id=<?php echo $assigned_filename; ?>';
				$('#fileupload').fileupload({
					url: url,
					dataType: 'json',
					done: function (e, data) {
						console.log(data.result.imagefile);
						$.each(data.result.imagefile, function (index, file) {
							if(file.error) {
								$('#errors').html('<p>Error: '+file.error+'</p>');
							} else {
								$('#errors').html('');
								$('#image-preview').attr('src', data.result.imagefile[0].thumbnailUrl + "?" + Math.random().toString());
								//$('#player').attr('src','videoplayer.php?user_id=<?php //echo $hashedplayer_id; ?>');
								$('#uploadtext').text('');

								$('.image-url').val(data.result.imagefile[0].url);
								$('.thumbnail-url').val(data.result.imagefile[0].thumbnailUrl);
							}
						});
					},
					progressall: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						$('#progress .progress-bar').css(
							'width',
							progress + '%'
						);
					}
				}).prop('disabled', !$.support.fileInput)
					.parent().addClass($.support.fileInput ? undefined : 'disabled');
			});
			function fixload() {
				$('iframe').load(function() {
					$('#player').height(''+(this.contentWindow.document.body.offsetHeight));
				});
			}
			fixload();
		</script>
	</head>

	<?php if (!$success && isset($message)) {?>
		<div class="alert alert-danger" role="alert"><?php echo $message ?></div>
	<?php } ?>
	<form action="edit_response.php" method="post">
		<input type="hidden" name="id" value="<?php echo $id ?>">
		<div class="input-group">
			<span class="input-group-addon">Name</span>
			<input type="text" class="form-control user-fullname" name="user_fullname" value="<?php echo $head ?>">
		</div>
		<div class="input-group">
			<span class="input-group-addon">Location</span>
			<input type="text" class="form-control user-location" name="user_location" value="<?php echo $location ?>">
		</div>
		<div class="input-group">
			<?php $response_label = 'Response'; ?>
			<?php
			if(isset($_POST['custom_responsetext'])) {
				$response_label = $_POST['custom_responsetext'];
			}
			?>
			<span class="input-group-addon"><?php echo $response_label; ?></span>
			<textarea class="form-control user-response" rows="5" name="user_response"><?php echo $description ?></textarea>
		</div>
		<div class="upload-group">
			<!-- The fileinput-button span is used to style the file input field as button -->
			<span class="filelimit"><?php echo 'Maximum file size is ' . (return_bytes(ini_get('post_max_size')) / 1024 / 1024) . ' MB'; ?></span>
				<span class="btn btn-primary fileinput-button">
					<i class="glyphicon glyphicon-plus"></i> Upload Image
					<!--<span id="uploadtext"><?php //echo $selecttext; ?></span>-->
					<!-- The file input field used as target for the file upload widget -->
					<input id="fileupload" type="file" name="imagefile">
				</span>

			<!-- The global progress bar -->
			<div id="progress" class="progress">
				<div class="progress-bar progress-bar-success"></div>
			</div>
			<!-- The container for the uploaded files -->
			<div id="errors" class="error"></div>
			<div class="panel panel-default">
				<div class="panel-heading">
					<span>Preview</span>
				</div>
				<div class="panel-body">
					<img id="image-preview" src="<?php echo $thumbnail_url ?>">
				</div>
			</div>
		</div>

		<input type="text" class="image-url" name="user_image_url" value="<?php echo $image_url ?>">
		<input type="text" class="thumbnail-url" name="user_thumbnail_url" value="<?php echo $thumbnail_url ?>">

		<input type="hidden" name="ltifix_user_id" value="<?php echo $_SESSION["lti"]['user_id']; ?>" />
		<button type="submit" class="save-question btn btn-primary" name="submit" value="Edit">Edit</button>
	</form>
</html>
