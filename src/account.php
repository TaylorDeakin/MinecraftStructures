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
        $session = $this->session;
        $userid = $session->userid;
        $query = "SELECT * FROM USERS WHERE userid='$userid'";

        if (!($result = mysqli_query($conn, $query))) {
            die("MYSQL ERROR: " . mysqli_error($conn));
        }

        $data = $result->fetch_assoc();
        return $this->renderer->render($response, 'account/profile-editor.twig', [
            'user' => $data,
            'loggedIn' => $session->loggedIn
        ]);

    });
    // profile submission
    $app->post('/dashboard/submit', function ($request, $response, $args) {
        global $conn;
        $session = $this->session;
        $profiletext = mysqli_real_escape_string($conn, $_POST['profile-blurb']);
        $twitter = mysqli_real_escape_string($conn, $_POST['twitter']);
        $youtube = mysqli_real_escape_string($conn, $_POST['youtube']);
        $facebook = mysqli_real_escape_string($conn, $_POST['facebook']);
        $website = mysqli_real_escape_string($conn, $_POST['website']);
        $title = "";//mysqli_real_escape_string($conn, $_POST['title']);

        $query = "UPDATE users
                  SET profiletext='$profiletext',twitter='$twitter',youtube='$youtube',facebook='$facebook',website='$website',title='$title'
                  WHERE users.userid='$session->userid'";


        if (!mysqli_query($conn, $query)) {
            die("need better error messages" . $conn->error);
        } else {

            return $response->withStatus(302)->withHeader('Location', '/account/dashboard/edit');
        }

    });


})->add($auth);

