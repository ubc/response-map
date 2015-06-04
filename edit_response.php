<?php
    session_start();
    require_once('config.php');

    $id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];

    if (isset($_POST['submit']) && $_POST['submit'] == "Edit" && !empty($_POST['user_location'])) {
        $head = empty($_POST['user_fullname']) ? NULL: $_POST['user_fullname'];
        $description = empty($_POST['user_response']) ? NULL: $_POST['user_response'];
        $update_response_query = mysqli_stmt_init($conn);
        $geocode = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($_POST['user_location']) . "&sensor=false&key=" . $google_key));
        if ($geocode->status === "OK") {
            mysqli_stmt_prepare($update_response_query, 'UPDATE response SET head=?, description=?, location=?, ' .
              'latitude=?, longitude=? WHERE id=?');
            mysqli_stmt_bind_param($update_response_query, 'sssddi', $head, $description, $_POST['user_location'],
                $geocode->results[0]->geometry->location->lat, $geocode->results[0]->geometry->location->lng, $id);
            mysqli_stmt_execute($update_response_query);
            header('Location: index.php');
        }
        mysqli_stmt_close($update_response_query);
    }

    // get original response
    $response_query = mysqli_query($conn, 'SELECT * FROM response WHERE id="' . $id .
        '" and resource_id="' . $_SESSION['resource']['id'] . '" LIMIT 1');
    $response = mysqli_fetch_object($response_query);
?>
<html>
    <head>
        <?php include('html/header.html'); ?>
    </head>

    <form action="edit_response.php" method="post">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <div class="input-group">
            <span class="input-group-addon">Name</span>
            <input type="text" class="form-control user-fullname" name="user_fullname" value="<?php echo $response->head ?>">
        </div>
        <div class="input-group">
            <span class="input-group-addon">Location</span>
            <input type="text" class="form-control user-location" name="user_location" value="<?php echo $response->location ?>">
        </div>
        <div class="input-group">
            <?php $response_label = 'Response'; ?>
            <?php
            if(isset($_POST['custom_responsetext'])) {
                $response_label = $_POST['custom_responsetext'];
            }
            ?>
            <span class="input-group-addon"><?php echo $response_label; ?></span>
            <textarea class="form-control user-response" rows="5" name="user_response"><?php echo $response->description ?></textarea>
        </div>
        <button type="submit" class="save-question btn btn-primary" name="submit" value="Edit">Edit</button>
    </form>
</html>
