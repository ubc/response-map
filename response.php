<?php
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

	session_start();
	setcookie(session_name(), session_id(), time()+1800);

	if (empty($_SESSION['authenticated'])) {
		echo 'Error: You do not have permission to visit this page.';
		die();
	}

	require_once('configuration.php');
	require_once('process-text.php');

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
	}

	// custom form field names
	$head_label = !empty($_SESSION['config']['custom_head_label']) ? $_SESSION['config']['custom_head_label'] : 'Name';
	$location_label = !empty($_SESSION['config']['custom_location_label']) ? $_SESSION['config']['custom_location_label'] : 'Location';
	$response_label = !empty($_SESSION['config']['custom_response_label']) ? $_SESSION['config']['custom_response_label'] : 'Response';

	$assigned_filename = md5($_SESSION['config']['user_id'] . $_SESSION['resource']['map_id'] . time());
	$success = false;

	if (isset($_POST['submit']) && $_POST['submit'] == "Save" && !empty($_POST['user_location'])) {
		$geocode = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($_POST['user_location']) . "&sensor=false&key=" . $config->google_key));
		if ($geocode->status === "OK") {
			$_POST = array_map('escapeInput', $_POST);
			$head = empty($_POST['user_fullname']) ? NULL : $_POST['user_fullname'];
			$description = empty($_POST['user_response']) ? NULL: $_POST['user_response'];
			$image = NULL;
			$thumbnail = NULL;
			if (!empty($_POST['user_image_url']) && !empty($_POST['user_thumbnail_url'])) {
				$image = $_POST['user_image_url'];
				$thumbnail = $_POST['user_thumbnail_url'];
			}

			$insert_response_query = mysqli_stmt_init($conn);
			mysqli_stmt_prepare($insert_response_query,
				'INSERT INTO response (user_id, resource_id, head, description, location, latitude, longitude, '.
				'image_url, thumbnail_url, create_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
			);
			mysqli_stmt_bind_param($insert_response_query, 'issssddsss', $_SESSION['user']['id'], $_SESSION['resource']['id'],
				$head, $description, $_POST['user_location'], $geocode->results[0]->geometry->location->lat, $geocode->results[0]->geometry->location->lng,
				$image, $thumbnail, $null);
			$success = mysqli_stmt_execute($insert_response_query);
			mysqli_stmt_close($insert_response_query);

			mysqli_close($conn);

			if ($success) {
				// send back a grade
				$message = 'Thank you for posting.';
				if (!empty($_SESSION['config']['lis_outcome_service_url']))
					require('grade.php');
					$message .= ' You have been given a participation mark.';
				if (isset($_SESSION['config']['custom_showcloud']) && $_SESSION['config']['custom_showcloud'] == 'true')
					$message .= ' Please see the cloud tag.';
				header('Location: map.php?message='.$message);
			}
		}

		if (!$success) {
			// generic error message for now.
			$message = 'Error: Please try submitting again.';
		}
	}

	mysqli_close($conn);
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
								$('#image-preview').attr('src', data.result.imagefile[0].thumbnailUrl + "?" + Math.random().toString());
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
			function deleteImage() {
				$.post("delete-image.php",
					{
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
				$('#delete-image').hide();
			});
			fixload();
		</script>
	</head>

	<body>
	<?php if (!$success && isset($message)) {?>
		<div class="alert alert-danger" role="alert"><?php echo $message ?></div>
	<?php } ?>
		<div class="alert alert-success" id="image-message"></div>
		<form action="response.php" method="post">
			<input class="question-did" name="lis_result_sourcedid" value="<?php echo $_SESSION['config']['lis_result_sourcedid'] ?>">

			<div class="input-group">
				<span class="input-group-addon"><?php echo $head_label ?></span>
				<input type="text" class="form-control user-fullname" name="user_fullname" value="<?php echo isset($_POST['user_fullname']) ? $_POST['user_fullname'] : '' ?>">
			</div>
			<div class="input-group">
				<span class="input-group-addon"><?php echo $location_label ?></span>
				<input type="text" class="form-control user-location" name="user_location" value="<?php echo isset($_POST['user_location']) ? $_POST['user_location'] : '' ?>">
			</div>
			<div class="input-group">
				<span class="input-group-addon"><?php echo $response_label; ?></span>
				<textarea class="form-control user-response" rows="5" name="user_response" ><?php echo isset($_POST['user_response']) ? $_POST['user_response'] : '' ?></textarea>
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
							<button type="button" class="btn btn-s btn-danger" id="delete-image"
									onclick="deleteImage()"><i class="fa fa-trash-o"></i></button>
						</span>
					</div>
					<div class="panel-body">
						<img id="image-preview" <?php echo isset($_POST['user_thumbnail_url']) ? 'src="'.$_POST['user_thumbnail_url'].'""' : '' ?>>
					</div>
				</div>
			</div>

			<input type="hidden" id="image-url" name="user_image_url" value="<?php echo isset($_POST['user_image_url']) ? $_POST['user_image_url'] : '' ?>">
			<input type="hidden" id="thumbnail-url" name="user_thumbnail_url" value="<?php echo isset($_POST['user_thumbnail_url']) ? $_POST['user_thumbnail_url'] : '' ?>">

			<input type="hidden" name="ltifix_user_id" value="<?php echo $_SESSION["config"]['user_id']; ?>" />

			<button type="submit" class="save-question btn btn-primary" name="submit" value="Save">Save</button>
		</form>
	</body>
</html>