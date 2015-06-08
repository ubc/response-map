<?php
	session_start();
	$session_id = session_id();

	require_once('config.php');
	require_once('process-text.php');

	if (mysqli_connect_error()) {
		echo 'Failed to connect to question database: ' . mysqli_connect_error();
		die();
	}

	$student_responses = array();
	$row = array();
	$all_text = '';
	$start = null;
	$query = 'SELECT r.id, r.user_id, head as fullname, description as response, location, latitude as lat, longitude as lng, ' .
		'image_url, thumbnail_url, COALESCE(SUM(f.vote_count), 0) as vote_count ' .
		'FROM response as r ' .
		'LEFT JOIN feedback as f ON r.id=f.response_id ' .
		'WHERE r.resource_id=? ' .
		'GROUP BY r.id';
	$select_response_query = mysqli_stmt_init($conn);
	mysqli_stmt_prepare($select_response_query, $query);
	mysqli_stmt_bind_param($select_response_query, 'i', $_SESSION['resource']['id']);
	mysqli_stmt_execute($select_response_query);
	// get the fields
	$response_meta = mysqli_stmt_result_metadata($select_response_query);
	$parameters = array($select_response_query);
	while ($field = mysqli_fetch_field($response_meta)) {
		$parameters[] = &$row[$field->name];
	}
	call_user_func_array("mysqli_stmt_bind_result", $parameters);

	while (mysqli_stmt_fetch($select_response_query)) {
		$tmp = new stdClass();
		foreach ($row as $key => $val) {
			$tmp->$key = $val;
		}
		$tmp->thumbs_up = $tmp->vote_count > 0;

		if ($tmp->user_id == $_SESSION['user']['id'])
			$start = $tmp;

		$all_text .= ' ' . $tmp->response;
		$student_responses[] = $tmp;
	}
	mysqli_stmt_close($select_response_query);

	$start = json_encode($start);
	$all_student_responses = json_encode($student_responses);
	$word_frequency = json_encode(wordCount($all_text));
?>
<html>
	<head>
		<?php include('html/header.html'); ?>

		<script type="text/javascript">
			var allStudentResponses = '<?php echo $all_student_responses ?>';
			var mapResponses = JSON.parse(allStudentResponses);
			var sourcedid = '<?php echo $_SESSION["lti"]["lis_result_sourcedid"] ?>';
			var sessid = '<?php echo $session_id ?>';
			var userId = '<?php echo $_SESSION['user']['id'] ?>';

			var markerBounds = new google.maps.LatLngBounds();
			var iterator = 0;
			var map, markers = [];
			var openedMarker = null;

			// initialize to first response in case the user has no responses - eg. Instructor
			var startLocation = new google.maps.LatLng(mapResponses[0].lat, mapResponses[0].lng);
			var myResponse = '<?php echo $start ?>';
			if (myResponse) {
				startLocation = new google.maps.LatLng(myResponse.lat, myResponse.lng)
			}

			function toggleThumbsUp(respid, element) {
				$.ajax({
					type: 'POST',
					url: 'vote.php',
					data: JSON.stringify({
						sourcedid: sourcedid,
						sessid: sessid,
						respid: respid,
					}),
					dataType: 'json',
					success: function(data) {
						var voteCountElem = $($(element).parent().find('.vote-count')[0]);

						if (data.vote) {
							openedMarker.voteCount++;
							$(element).removeClass('btn-default');
							$(element).addClass('btn-primary');
							openedMarker.thumbsUp = true;
						} else {
							openMarker.voteCount--;
							$(element).removeClass('btn-primary');
							$(element).addClass('btn-default');
							openedMarker.thumbsUp = false;
						}

						voteCountElem.text(openedMarker.voteCount);
					}
				});
			}

			function mapInitialise() {
				var mapOptions = {
					center: startLocation,
					zoomControl: true,
					streetViewControl: false,
				};

				map = new google.maps.Map(
					document.getElementById('map-canvas'),
					mapOptions
				);

				for (var key in mapResponses) {
					mapResp = mapResponses[key];
					mapResp.myMarker = false;

					mapResp.lat = parseFloat(mapResp.lat);
					mapResp.lng = parseFloat(mapResp.lng);

					// nudges
					nudgeLat = Math.random() * 0.00005 * Math.floor(Math.random()*2) == 1 ? 1 : -1;
					nudgeLng = Math.random() * 0.00005 * Math.floor(Math.random()*2) == 1 ? 1 : -1;

					mapResp.distanceToCentre = Math.sqrt(Math.pow(mapResp.lat - mapResp.lat, 2) + Math.pow(mapResp.lng - mapResp.lng, 2));

					var marker = new google.maps.Marker({
						position: new google.maps.LatLng(mapResp.lat, mapResp.lng),
						map: map,
						draggable: false
					});

					if (userId == mapResp.user_id) {
						marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
						mapResp.myMarker = true;
					}

					marker.distanceToCentre = mapResp.distanceToCentre;
					marker.fullname = mapResp.fullname;
					marker.responseBody = mapResp.response;
					marker.myMarker = mapResp.myMarker;
					marker.fullImageUrl = mapResp.image_url;
					marker.thumbnailImageUrl = mapResp.thumbnail_url;
					marker.fullname = mapResp.fullname;
					marker.responseId = mapResp.id;
					marker.thumbsUp = mapResp.thumbs_up;
					marker.voteCount = parseInt(mapResp.vote_count);
					marker.infoWindow = new google.maps.InfoWindow();

					markers.push(marker);

					google.maps.event.addListener(marker, 'click', function() {
						if (openedMarker !== null) {
							openedMarker.infoWindow.close();
							openedMarker = null;
						}

						$('.response-full-image').attr('src', this.fullImageUrl);
						$('.response-fullname').text(this.fullname + '\'s Image Response');

						var buttonClass = 'btn-default';
						if (this.thumbsUp) {
							buttonClass = 'btn-primary';
						}

						var contentString = '<div id="content">' +
											'<h3 id="firstHeading" class="firstHeading">' + this.fullname + '</h3>' +
											'<div id="bodyContent">' +
												'<p>' + this.responseBody + '</p>';

						if ((this.thumbnailImageUrl !== null) && (this.fullImageUrl !== null)) {
							contentString += 		'<a href="#myModal" data-toggle="modal"><img src="' + this.thumbnailImageUrl + '" alt=""/></a>';
						}

						contentString +=			'<div class="vote-group"><button type="button" class="vote-btn btn ' + buttonClass + ' btn-xs" onclick="toggleThumbsUp(' + this.responseId + ', this)"><i class="fa fa-thumbs-o-up"></i></button>' +
													'<span class="vote-count">' + this.voteCount + '</span></div>' +
												'</div>';
						if (this.myMarker) {
							contentString += '<a href="edit_response.php?id=' + this.responseId + '">Edit</a>';
						}
						contentString += '</div>';

						this.infoWindow.setContent(contentString);

						this.infoWindow.open(map,this);
						openedMarker = this;
					});
				}

				// Sort the markers based on distance to center point
				markers.sort(function(a, b) {
					return (a.distanceToCentre - b.distanceToCentre);
				});

				// Only fit the view to a maximum of 28 closest markers
				var numVisibleMarkers = markers.length >= 28 ? 28 : markers.length;

				for (var i = 0; i < numVisibleMarkers; i++) {
					markerBounds.extend(markers[i].getPosition());
				}

				map.fitBounds(markerBounds);

				var mcOptions = {
					gridSize: 50,
					maxZoom: 18
				};

				var markerCluster = new MarkerClusterer(map, markers, mcOptions);
			}

			google.maps.event.addDomListener(window, 'load', mapInitialise);

			// Plotting word cloud
			$(function() {
				var frequencyList = <?php echo $word_frequency ?>;
				var color_range = ['#ddd', '#ccc', '#bbb', '#aaa', '#999', '#888', '#777', '#666', '#555', '#444', '#333', '#222'];
				var use_color = false;
				<?php if(isset($_POST['custom_usecolor']) && $_POST['custom_usecolor'] == 'true') { ?>
				use_color = true;
				<?php } ?>
				if(use_color) {
					color_range = d3.scale.category20().range();
				}

				var color = d3.scale.linear()
					.domain([0, 1, 2, 3, 4, 5, 6, 10, 15, 20, 70])
					.range(color_range);

				d3.layout.cloud().size([750, 240])
					.words(frequencyList)
					.rotate(0)
					.fontSize(function(d) { return d.size; })
					.on('end', draw)
					.start();

				function draw(words) {
					d3.select('.response-word-cloud').append('svg')
						.attr('width', 772)
						.attr('height', 250)
						.attr('class', 'wordcloud')
						.append('g')
						// without the transform, words words would get cutoff to the left and top, they would
						// appear outside of the SVG area
						.attr('transform', 'translate(340,125)')
						.selectAll('text')
						.data(words)
						.enter().append('text')
							.style('font-size', function(d) { return d.size + 'px'; })
							.style('fill', function(d, i) { return color(i); })

							.attr("text-anchor", "middle")
							.attr('transform', function(d) {
								return 'translate(' + [d.x, d.y] + ')rotate(' + d.rotate + ')';
							})
							.text(function(d) { return d.text; });
				}
			});
		</script>
	</head>

	<body>
		<?php if (!empty($_GET['message'])) { ?>
			<div class="alert alert-success" role="alert"><?php echo $_GET['message'] ?></div>
		<?php } ?>
		<div id="map-canvas"></div>
		<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">
							<span aria-hidden="true">&times;</span>
							<span class="sr-only">Close</span>
						</button>
						<h4 class="modal-title response-fullname"></h4>
					</div>
					<div class="modal-body">
						<img class="response-full-image" src="">
					</div>
				</div>
			</div>
		</div>

		<?php if(isset($_SESSION['lti']['custom_showcloud']) && $_SESSION['lti']['custom_showcloud'] == 'true') { ?>
			<div class="response-word-cloud"></div>
		<?php } ?>
	</body>
</html>