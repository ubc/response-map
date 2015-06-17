<head>
	<link rel="stylesheet" href="../css/response-map.css"/>
</head>
<body>
	<?php
		session_start();
		setcookie(session_name(), session_id(), time()+1800);
		if(isset($_POST['qid']) && isset($_POST['password'])) {
			require_once('../config.php');
			if($_POST['password'] != $adminpassword) {
				echo 'invalid password';
				die();
			}

			// Check connection
			if (mysqli_connect_errno()) {
			  echo "Failed to connect to MySQL: " . mysqli_connect_error();
			}

			// get responses
			$query = "SELECT u.userId, r.head, r.thumbnail_url, r.description, r.location, r.id, r.update_time " .
				"FROM response AS r " .
				"JOIN user as u ON u.id=r.user_id " .
				"WHERE r.resource_id = (" .
					"SELECT id FROM resource " .
					"WHERE resource.map_id=? LIMIT 1" .
				")" .
				"ORDER BY r.update_time DESC";
			$select_query = mysqli_stmt_init($conn);
			mysqli_stmt_prepare($select_query, $query);
			mysqli_stmt_bind_param($select_query, 's', $_POST['qid']);
			mysqli_stmt_execute($select_query);
			mysqli_stmt_bind_result($select_query, $userId, $head, $image, $description, $location, $responseId, $time);
	?>
			<table class="admin_results">
				<tr>
					<th>User ID</th>
					<th>Head</th>
					<th>Image</th>
					<th>Response</th>
					<th>Location</th>
					<th>Response ID</th>
					<th>Date</th>
				</tr>
				<?php while(mysqli_stmt_fetch($select_query)) { ?>
					<tr>
						<td><?php echo $userId?></td>
						<td><?php echo $head;?></td>
						<td>
							<?php echo $image ? "<img src='" . $image . "' />" : "" ?>
						</td>
						<td style='width:300px;'><?php echo $description;?></td>
						<td><?php echo $location;?></td>
						<td><?php echo $responseId;?></td>
						<td><?php echo date("l, F j, Y g:i a", strtotime($time));?></td>
					</tr>
				<?php }

			mysqli_stmt_close($select_query);
			mysqli_close($conn);

				?>
			</table>
		<?php } else { ?>
			<form method="post">
				<label for='qid'>Resource ID: </label><input type="text" name='qid' />
				<label for='password'>Password: </label><input type="password" name='password' />
				<input type="submit" />
			</form>
		<?php } ?>
</body>