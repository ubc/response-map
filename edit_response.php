<?php
	session_set_cookie_params(1800);
	session_start();

	if (empty($_SESSION['authenticated'])) {
		echo 'Error: You do not have permission to visit this page.';
		die();
	}

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

	// custom form field names
	$head_label = !empty($_SESSION['lti']['custom_head_label']) ? $_SESSION['lti']['custom_head_label'] : 'Name';
	$location_label = !empty($_SESSION['lti']['custom_location_label']) ? $_SESSION['lti']['custom_location_label'] : 'Location';
	$response_label = !empty($_SESSION['lti']['custom_response_label']) ? $_SESSION['lti']['custom_response_label'] : 'Response';

	$id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
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
			require('grade.php');
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

	if ($image_url) {
		$assigned_filename = explode('/', $image_url);
		$assigned_filename = explode('.', end($assigned_filename));
		$assigned_filename = $assigned_filename[0];
	} else {
		$assigned_filename = md5($_SESSION['lti']['user_id'] . $_SESSION['resource']['map_id'] . time());
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
					start: function(e) {
						$('#progress .progress-bar').css('width', '0%');
						$('#image-message').hide();
					},
					done: function (e, data) {
						$.each(data.result.imagefile, function (index, file) {
							if(file.error) {
								$('#errors').html('<p>Error: '+file.error+'</p>');
								$('#image-message').hide();
							} else {
								$('#errors').html('');
								$('#image-preview').show().attr('src', data.result.imagefile[0].thumbnailUrl + "?" + Math.random().toString());
								//$('#player').attr('src','videoplayer.php?user_id=<?php //echo $hashedplayer_id; ?>');
								$('#uploadtext').text('');

								$('#image-url').val(data.result.imagefile[0].url);
								$('#thumbnail-url').val(data.result.imagefile[0].thumbnailUrl);
								$('#image-message').show().text('The image has been successfully uploaded.');
								$('#delete-image').show();
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
			function deleteImage(id) {
				$.post("delete-image.php",
					{
						id: id,
						image: $('#image-url').val()
					},
					function(data) {
						var result = $.parseJSON(data);
						if (result['result']) {
							$('#image-preview').hide().attr('src', '');
							$('#delete-image').hide();
							$('#image-url').val('');
							$('#thumbnail-url').val('');
							$('#progress .progress-bar').css('width', '0%');
							$('#image-message').show().text('The image has been successfully deleted.');
						}
					}
				)
			}
			$(document).ready(function() {
				$('#image-message').hide();
				if (!$('#image-url').val()) {
					$('#delete-image').hide();
				}
			});
			fixload();
		</script>
	</head>

	<?php if (!$success && isset($message)) {?>
		<div class="alert alert-danger" role="alert"><?php echo $message ?></div>
	<?php } ?>
	<div class="alert alert-success" id="image-message"></div>
	<form action="edit_response.php" method="post">
		<input type="hidden" name="id" value="<?php echo $id ?>">
		<div class="input-group">
			<span class="input-group-addon"><?php echo $head_label ?></span>
			<input type="text" class="form-control user-fullname" name="user_fullname" value="<?php echo $head ?>">
		</div>
		<div class="input-group">
			<span class="input-group-addon"><?php echo $location_label ?></span>
			<input type="text" class="form-control user-location" name="user_location" value="<?php echo $location ?>">
		</div>
		<div class="input-group">
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
					<span>Preview
						<button type="button" class="<?php $thumbnail_url ? '' : 'image-preview-none' ?> btn btn-s btn-danger"
								id="delete-image" onclick="deleteImage(<?php echo $id ?>)"><i class="fa fa-trash-o"></i></button>
					</span>
				</div>
				<div class="panel-body">
					<img id="image-preview" src="<?php echo $thumbnail_url ?>">
				</div>
			</div>
		</div>

		<input type="hidden" id="image-url" name="user_image_url" value="<?php echo $image_url ?>">
		<input type="hidden" id="thumbnail-url" name="user_thumbnail_url" value="<?php echo $thumbnail_url ?>">

		<input type="hidden" name="ltifix_user_id" value="<?php echo $_SESSION["lti"]['user_id']; ?>" />
		<button type="submit" class="save-question btn btn-primary" name="submit" value="Edit">Edit</button>
	</form>
</html>
