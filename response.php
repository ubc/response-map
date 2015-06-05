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
	require_once('config.php');

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
}

	$assigned_filename = md5($_SESSION['lti']['user_id'] . $_SESSION['resource']['map_id']);

	if (isset($_POST['submit']) && $_POST['submit'] == "Save" && !empty($_POST['user_location'])) {
		$geocode = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($_POST['user_location']) . "&sensor=false&key=" . $google_key));
		if ($geocode->status === "OK") {
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
			mysqli_stmt_execute($insert_response_query);
			mysqli_stmt_close($insert_response_query);
			include('grade.php');
			header('Location: map.php?success=1');
		}
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
								$('#image-preview').attr('src', data.result.imagefile[0].thumbnailUrl);
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

	<body>
		<form action="response.php" method="post">
			<input class="question-did" name="lis_result_sourcedid" value="<?php echo $_SESSION['lti']['lis_result_sourcedid'] ?>">

			<div class="input-group">
				<span class="input-group-addon">Name</span>
				<input type="text" class="form-control user-fullname" name="user_fullname">
			</div>
			<div class="input-group">
				<span class="input-group-addon">Location</span>
				<input type="text" class="form-control user-location" name="user_location">
			</div>
			<div class="input-group">
				<?php $response_label = 'Response'; ?>
				<?php
				if(isset($_POST['custom_responsetext'])) {
					$response_label = $_POST['custom_responsetext'];
				}
				?>
				<span class="input-group-addon"><?php echo $response_label; ?></span>
				<textarea class="form-control user-response" rows="5" name="user_response"></textarea>
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
						<img id="image-preview">
					</div>
				</div>
			</div>

			<input type="text" class="image-url" name="user_image_url">
			<input type="text" class="thumbnail-url" name="user_thumbnail_url">

			<input type="hidden" name="ltifix_user_id" value="<?php echo $_SESSION["lti"]['user_id']; ?>" />

			<button type="submit" class="save-question btn btn-primary" name="submit" value="Save">Save</button>
		</form>
	</body>
</html>