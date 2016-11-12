<?php
// auth listener
$auth = function ($request, $response, $next) {
    $session = $this->session;
    if ($session->loggedIn) {
        $response = $next($request, $response);
        return $response;
    } else {
        return $response->withStatus(302)->withHeader('Location', '/login');
    }
};

$app->group('/account', function () use ($app) {
    // main dashboard
    $app->get('/dashboard', function ($request, $response, $args) {
        global $conn;
        global $categoryMenuArray;
        $session = $this->session;

        $userid = $session->userid;

        $query = "SELECT *
              FROM users
              LEFT JOIN structures s
                ON s.userid = users.userid
              WHERE users.userid='$userid'";

        $structures = [];
        $user = [];
        if (!($result = $conn->query($query))) {
            die('there was an error [' . $conn->error . ']');
        }
        $counter = 0;
        while (($data = $result->fetch_assoc()) != null) {
            if ($data['name']) {
                $structures[$counter] = $data;
                $counter++;
            }

            if (empty($user)) {
                $user = [
                    "username" => $data['username'],
                    "level" => $data['level'],
                    "title" => $data['title'],
                    "profileImage" => $data['image'],
                    "blurb" => $data['profiletext'],
                    "twitter" => $data['twitter'],
                    "facebook" => $data['facebook'],
                    "youtube" => $data['youtube'],
                    "website" => $data['website']
                ];
            }

        }

        return $this->renderer->render($response, 'account/dashboard.twig', [
            'user' => $user,
            'structureList' => $structures,
            'loggedIn' => $session->loggedIn
        ]);
    });
    // profile editor
    $app->get('/dashboard/edit', function ($request, $response, $args) {
        global $conn;
        global $classList;
        $session = $this->session;
        $userid = $session->userid;
        $query = "SELECT * FROM USERS WHERE userid='$userid'";
        var_dump($session->valid);
        if (!($result = mysqli_query($conn, $query))) {
            die("MYSQL ERROR: " . mysqli_error($conn));
        }
        if($session->message){
            var_dump($session->message);
        }
        $data = $result->fetch_assoc();
        return $this->renderer->render($response, 'account/profile-editor.twig', [
            'classList' => $classList,
            'user' => $data,
            'loggedIn' => $session->loggedIn
        ]);

    });

    $app->get('/structure/create', function ($request, $response, $args) {
        $session = $this->session;

        return $this->renderer->render($response, 'account/structure-editor.twig', [
            'loggedIn' => $session->loggedIn
        ]);

    });

    $app->get('/structure/edit/{id}', function ($request, $response, $args) {
        global $conn;
        $session = $this->session;

        $id = mysqli_real_escape_string($conn, $args['id']);
        $query = "SELECT * FROM structures JOIN users ON users.userid=structures.userid WHERE id='$id'";

        $data = $conn->query($query);

        // check that the structure was made by the same person
        // who now wants to edit it
        if (!$data['userid'] === $session->userid) {
            die("bad person");
            // TODO: handle this better
        }


    });

    // profile submission
    $app->post('/dashboard/submit', function ($request, $response, $args) {
        global $conn;
        $session = $this->session;
        $profiletext = mysqli_real_escape_string($conn, $_POST['profile-blurb']);
        $twitter = mysqli_real_escape_string($conn, $_POST['twitter']);
        $youtube = mysqli_real_escape_string($conn, $_POST['youtube']);
        $website = mysqli_real_escape_string($conn, $_POST['website']);
        $title = "";//mysqli_real_escape_string($conn, $_POST['title']);
        $class = mysqli_real_escape_string($conn, $_POST['class']);
        $valid = false;
        $filename = "";

        echo(__DIR__);
        if ($_FILES['avatar']['name']) {

            if (!$_FILES['avatar']['error']) {
                $filename = strtolower($_FILES['avatar']['name']);
                $valid = true;

                if ($_FILES['avatar']['size'] > (204800)) {
                    $valid = false;
                    $message = "file size too large";
                }

                if ($valid) {
                    move_uploaded_file($_FILES['avatar']['tmp_name'], '../public/img/' . $filename);
                }
            }
        }

        $session->valid = $valid;
        if ($valid) {
            $query = "UPDATE users
                  SET profiletext='$profiletext',twitter='$twitter',youtube='$youtube',website='$website',title='$title', class='$class', image='$filename'
                  WHERE users.userid='$session->userid'";
        } else {
            $query = "UPDATE users
                  SET profiletext='$profiletext',twitter='$twitter',youtube='$youtube',website='$website',title='$title', class='$class'
                  WHERE users.userid='$session->userid'";
        }

        if (!mysqli_query($conn, $query)) {
            die("need better error messages" . $conn->error);
        } else {
            $session->message = $message;

            return $response->withStatus(302)->withHeader('Location', '/account/dashboard/edit');
        }

    });
    // structure creation
    $app->post('/structure/create', function ($request, $response, $args) {

    });

})->add($auth);

