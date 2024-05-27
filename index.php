<?php
if (isset($_ENV['DEBUG'])) {
    // let us turn on errors if DEBUG env var is set
    error_reporting(E_ALL);
    mysqli_report(MYSQLI_REPORT_ALL);
}

require_once('lti.php');

/* Check if a valid lti request received */
$lti = new Lti();
$lti->require_valid(); // Returns error message if not a valid LTI request

$key = !empty($_SESSION['config']['tool_consumer_instance_guid']) ?
    $_SESSION['config']['tool_consumer_instance_guid'] : $_SESSION['config']['oauth_consumer_key'];
$context_id = $_SESSION['config']['context_id'];
$question_id = $_SESSION['config']['resource_link_id'] . $key;

if (mysqli_connect_error()) {
    echo 'Failed to connect to question database: ' . mysqli_connect_error();
    die();
}

$null = NULL;

// Check to see if user exists
$select_user_query = mysqli_stmt_init($conn);
mysqli_stmt_prepare($select_user_query, 'SELECT id FROM user WHERE userId=? LIMIT 1');
mysqli_stmt_bind_param($select_user_query, 's', $_SESSION['config']['user_id']);
mysqli_stmt_execute($select_user_query);
mysqli_stmt_bind_result($select_user_query, $userId);
mysqli_stmt_fetch($select_user_query);
mysqli_stmt_close($select_user_query);

// Check to see if resource exists
$select_resource_query = mysqli_stmt_init($conn);
mysqli_stmt_prepare($select_resource_query, 'SELECT id FROM resource WHERE course_id=? AND map_id=? LIMIT 1');
mysqli_stmt_bind_param($select_resource_query, 'ss', $context_id, $question_id);
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
    mysqli_stmt_bind_param($add_resource_query, 'sss', $context_id, $question_id, $null);
    mysqli_stmt_execute($add_resource_query);
    $_SESSION['resource'] = array('id' => mysqli_stmt_insert_id($add_resource_query), 'map_id' => $question_id);
    mysqli_stmt_close($add_resource_query);
} else {
    $_SESSION['resource'] = array('id' => $resourceId, 'map_id' => $question_id);
}

// if user and resource exists
if ($_SESSION['user']['id'] && $_SESSION['resource']['id']) {
    header('Location: map.php');
}

mysqli_close($conn);
?>
