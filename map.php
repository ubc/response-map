<?php
session_start();
setcookie(session_name(), session_id(), time() + 1800);

if (empty($_SESSION['authenticated'])) {
    echo 'Error: You do not have permission to visit this page. The UBC Response Map Tool is not loading, which may be caused by your web browser blocking third party cookies. Please enable third party cookies via your browser preferences or add responsemap.elearning.ubc.ca to the whitelist. (<a href"https://support.bigcommerce.com/articles/Public/How-do-I-allow-third-party-cookies-to-be-set-in-my-browser" target="_blank">How do I do this?</a>)';
    die();
}

$session_id = session_id();

require_once('configuration.php');
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
    'WHERE r.resource_id=? and deleted=0 ' .
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
call_user_func_array('mysqli_stmt_bind_result', $parameters);

while (mysqli_stmt_fetch($select_response_query)) {
    $tmp = new stdClass();
    foreach ($row as $key => $val) {
        $tmp->$key = $val;
    }
    $tmp->response = '<p>' . nl2br(htmlspecialchars($tmp->response, ENT_NOQUOTES, 'UTF-8')) . '</p>';
    $tmp->thumbs_up = $tmp->vote_count > 0;

    if ($tmp->user_id == $_SESSION['user']['id']) {
        $start = $tmp;
    }

    $all_text .= ' ' . $tmp->response;
    $student_responses[] = $tmp;
}
mysqli_stmt_close($select_response_query);

$instructors = json_decode($config->instructor_roles);    // allowed roles
$roles = explode(',', $_SESSION['config']['roles']);    // user's roles
$allowed = array_intersect($roles, $instructors);

$start = json_encode($start);
$all_student_responses = json_encode($student_responses);
$word_frequency = json_encode(wordCount($all_text));

mysqli_close($conn);
?>
<html>
<head>
    <?php include('header.php'); ?>

    <script type="application/json" id="all_responses"><?php echo $all_student_responses ?></script>
    <script type="application/json" id="start"><?php echo $start ?></script>

    <script id="info-template" type="text/x-handlebars-template">
        <div id="content">
            {{#if (showPager response.marker.responses)}}
            <div id="pager">
                {{#unless (firstResponse response.id response.marker.responses)}}
                <a onclick="changeResponse({{ this.key }}, {{ responsePosition response.id response.marker.responses }}, -1)"><i class="fa fa-caret-left fa-lg"></i></a>
                {{/unless}}
                {{ responsePosition response.id response.marker.responses }} of {{ response.marker.responses.length }} responses
                {{#unless (lastResponse response.id response.marker.responses)}}
                <a onclick="changeResponse({{ this.key }}, {{ responsePosition response.id response.marker.responses }}, 1)"><i class="fa fa-caret-right fa-lg"></i></a>
                {{/unless}}
            </div>
            {{/if}}
            <h3 id="firstHeading" class="firstHeading">{{ response.fullname }}</h3>

            <div id="bodyContent">
                {{{ response.response }}}
                <a href="#myModal" data-toggle="modal"><img src="{{ response.thumbnail_url }}" alt=""/></a>

                <div class="footer">
                    <button type="button" class="vote-btn btn {{ buttonClass }} btn-xs"
                            onclick="toggleThumbsUp({{ this.key }}, {{ response.id }}, this)">
                        <i class="fa fa-thumbs-o-up"></i>
                        <span class="vote-count">{{ response.vote_count }}</span>
                    </button>

                    {{#if response.myMarker}}
                    <a class="btn btn-default btn-xs button" href="edit_response.php?id={{ response.id }}">Edit</a>
                    {{/if}}

                    {{#if allowed}}
                    <a class="btn btn-danger btn-xs button" onclick="deleteResponse({{ this.key }}, {{ response.id }})">Delete</a>
                    {{/if}}
                </div>
            </div>
        </div>
    </script>
    <script type="text/javascript">
        var allowed = <?php echo !empty($allowed)?'true':'false'; ?>;
        var mapResponses = JSON.parse(document.getElementById('all_responses').innerHTML);
        var sourcedid = '<?php echo $_SESSION['config']['lis_result_sourcedid'] ?>';
        var sessid = '<?php echo $session_id ?>';
        var userId = '<?php echo $_SESSION['user']['id'] ?>';

        var markerBounds = new google.maps.LatLngBounds();
        var iterator = 0;
        var map, markers = [];
        var infoWindow = new google.maps.InfoWindow();
        var source = $("#info-template").html();
        var template = Handlebars.compile(source);

        // set center of the map
        var myResponse = JSON.parse(document.getElementById('start').innerHTML);
        var startLocation = new google.maps.LatLng(0, 0);
        if (myResponse) {
            startLocation = new google.maps.LatLng(myResponse.lat, myResponse.lng)
        } else if (mapResponses.length > 0) {
            startLocation = new google.maps.LatLng(mapResponses[0].lat, mapResponses[0].lng);
        }

        function responsePosition(responseId, responses) {
            return $.map(responses, function(response) { return response.id}).indexOf(responseId) + 1;
        }

        Handlebars.registerHelper('firstResponse', function(responseId, responses) {
            return responses.length > 1 && responses[0].id == responseId;
        });

        Handlebars.registerHelper('lastResponse', function(responseId, responses) {
            return responses.length > 1 && responses[responses.length - 1].id == responseId;
        });

        Handlebars.registerHelper('responsePosition', responsePosition);

        Handlebars.registerHelper('showPager', function(responses) {
            return responses.length > 1;
        });

        function toggleThumbsUp(markerKey, responseId, element) {
            $.ajax({
                type: 'POST',
                url: 'vote.php',
                data: JSON.stringify({
                    sourcedid: sourcedid,
                    sessid: sessid,
                    respid: responseId
                }),
                dataType: 'json',
                success: function (data) {
                    var voteCountElem = $($(element).find('.vote-count')[0]);

                    var response = $.grep(markers[markerKey].responses, function (response) {
                        return response.id == responseId;
                    })[0];
                    if (data.vote) {
                        response.vote_count++;
                        $(element).removeClass('btn-default');
                        $(element).addClass('btn-primary');
                        response.thumbsUp = true;
                    } else {
                        response.vote_count--;
                        $(element).removeClass('btn-primary');
                        $(element).addClass('btn-default');
                        response.thumbsUp = false;
                    }

                    voteCountElem.text(response.vote_count);
                }
            });
        }

        function deleteResponse(markerKey, responseId) {
            if (confirm("Are you sure you want to delete this response?")) {
                $.ajax({
                    type: 'POST',
                    url: 'delete_response.php',
                    data: JSON.stringify({
                        respId: responseId
                    }),
                    dataType: 'json',
                    success: function (data) {
                        markers[markerKey].responses = $.grep(markers[markerKey].responses, function (response) {
                            return response.id !== responseId
                        });
                        if (!markers[markerKey].responses.length) {
                            // remove pin if not responses attached
                            markers[markerKey].setMap(null);
                        } else {
                            updateInfoWindow(markers[markerKey].responses[0])
                        }
                    }
                });
            }
        }

        function updateInfoWindow(response) {
            $('.response-full-image').attr('src', response.image_url);
            $('.response-fullname').text(response.fullname + '\'s Image Response');

            var context = {
                response: response,
                buttonClass: response.thumbsUp ? 'btn-primary' : 'btn-default',
                thumbnail: (response.thumbnail_url !== null) && (response.image_url !== null),
                allowed: allowed,
                key: response.marker.key
            };
            infoWindow.setContent(template(context));
            infoWindow.open(map, response.marker);
        }

        function changeResponse(markerKey, responsePosition, direction) {
            var response = markers[markerKey].responses[responsePosition - 1 + direction];
            updateInfoWindow(response);
        }

        function mapInitialise() {
            var mapOptions = {
                center: startLocation,
                zoomControl: true,
                streetViewControl: false,
                zoom: 1
            };

            map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

            for (var key in mapResponses) {
                var mapResp = mapResponses[key];
                mapResp.myMarker = false;

                mapResp.lat = parseFloat(mapResp.lat);
                mapResp.lng = parseFloat(mapResp.lng);

                var latLng = new google.maps.LatLng(mapResp.lat, mapResp.lng);

                var marker = $.grep(markers, function (latLng) {
                    return function (m) {
                        return m.position.toString() == latLng.toString()
                    }
                }(latLng));

                if (marker.length == 1) {
                    marker = marker[0];
                } else if (marker.length == 0) {
                    marker = new google.maps.Marker({position: latLng, map: map, draggable: false});
                    marker.responses = [];
                    marker.distanceToCentre = google.maps.geometry.spherical.computeDistanceBetween(startLocation, latLng);
                    markers.push(marker);
                } else {
                    console.warn('Invalid number of markers with position: ' + latLng.toString());
                    // fail back to use the first one
                    marker = marker[0];
                }

                mapResp.marker = marker;

                if (userId == mapResp.user_id) {
                    marker.setIcon('http://maps.google.com/mapfiles/ms/icons/green-dot.png');
                    mapResp.myMarker = true;
                }

                marker.responses.push(mapResp);

                google.maps.event.addListener(marker, 'click', function () {
                    updateInfoWindow(this.responses[0]);
                });
            }

            // Sort the markers based on distance to center point
            markers.sort(function (a, b) {
                return (a.distanceToCentre - b.distanceToCentre);
            });

            // Only fit the view to a maximum of 28 closest markers
            var numVisibleMarkers = markers.length >= 28 ? 28 : markers.length;

            for (var i = 0; i < numVisibleMarkers; i++) {
                //markers[i].setIcon('http://maps.google.com/mapfiles/ms/icons/blue-dot.png');
                markerBounds.extend(markers[i].getPosition());
            }

            if (numVisibleMarkers > 0) {
                map.fitBounds(markerBounds);
            }

            var mcOptions = {
                gridSize: 50,
                maxZoom: 18
            };

            // get index of marker in the list
            for (var key in markers) {
                markers[key].key = key;
            }

            var markerCluster = new MarkerClusterer(map, markers, mcOptions);
        }

        google.maps.event.addDomListener(window, 'load', mapInitialise);

        // Plotting word cloud
        $(function () {
            var frequencyList = <?php echo $word_frequency ?>;
            var color_range = ['#ddd', '#ccc', '#bbb', '#aaa', '#999', '#888', '#777', '#666', '#555', '#444', '#333', '#222'];
            var use_color = false;
            <?php if(isset($_POST['custom_usecolor']) && $_POST['custom_usecolor'] == 'true') { ?>
            use_color = true;
            <?php } ?>
            if (use_color) {
                color_range = d3.scale.category20().range();
            }

            var color = d3.scale.linear()
                .domain([0, 1, 2, 3, 4, 5, 6, 10, 15, 20, 70])
                .range(color_range);

            d3.layout.cloud().size([750, 240])
                .words(frequencyList)
                .rotate(0)
                .fontSize(function (d) {
                    return d.size;
                })
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
                    .style('font-size', function (d) {
                        return d.size + 'px';
                    })
                    .style('fill', function (d, i) {
                        return color(i);
                    })

                    .attr("text-anchor", "middle")
                    .attr('transform', function (d) {
                        return 'translate(' + [d.x, d.y] + ')rotate(' + d.rotate + ')';
                    })
                    .text(function (d) {
                        return d.text;
                    });
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

<div class="button-row">
    <a href="response.php" class="btn btn-primary">Add Response</a>
</div>

<?php if (isset($_SESSION['config']['custom_showcloud']) && $_SESSION['config']['custom_showcloud'] == 'true') { ?>
    <div class="response-word-cloud"></div>
<?php } ?>
</body>
</html>
