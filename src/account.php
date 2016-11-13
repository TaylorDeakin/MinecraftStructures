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

        $userStats = [
            "likes" => 0,
            "downloads" => 0,
            "views" => 0,
        ];

        if (!($result = $conn->query($query))) {
            die('there was an error [' . $conn->error . ']');
        }
        $counter = 0;
        while (($data = $result->fetch_assoc()) != null) {
            // has name column, ergo is structure
            if ($data['name']) {
                $structures[$counter] = $data;
                $counter++;

                $userStats['likes'] += $data['likes'];
                $userStats['downloads'] += $data['downloads'];
                $userStats['views'] += $data['views'];
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
                    "website" => $data['website'],
                    "class" => $data['class']
                ];
            }
        }

        $user['stats'] = $userStats;

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
        if ($session->message) {
            var_dump($session->message);
        }
        $data = $result->fetch_assoc();
        return $this->renderer->render($response, 'account/profile-editor.twig', [
            'classList' => $classList,
            'user' => $data,
            'loggedIn' => $session->loggedIn
        ]);

    });

    // structure creation - GET
    $app->get('/structure/create', function ($request, $response, $args) {
        $session = $this->session;
        global $categoryMenuArray;

        return $this->renderer->render($response, 'account/structure-editor.twig', [
            'loggedIn' => $session->loggedIn,
            'categoryList' => $categoryMenuArray
        ]);

    });

    $app->get('/structure/edit/{id}', function ($request, $response, $args) {
        global $conn;
        global $categoryMenuArray;
        $session = $this->session;

        $id = mysqli_real_escape_string($conn, $args['id']);

        $session->currentlyEditing = $id;

        $query = "SELECT * FROM structures JOIN users ON users.userid=structures.userid WHERE id='$id'";

        if (($result = mysqli_query($conn, $query)) === false) {
            die("error to be handled" . mysqli_error($conn));
        }

        $data = $result->fetch_assoc();

        // check that the structure was made by the same person
        // who now wants to edit it
        if (!$data['userid'] === $session->userid) {
            die("bad person");
            // TODO: handle this better
        }

        return $this->renderer->render($response, 'account/structure-editor.twig', [
            'editing' => true,
            'structure' => $data,
            'categoryList' => $categoryMenuArray

        ]);


    });

    // profile submission - POST (form handling)
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
    // structure creation - POST (form handling)
    $app->post('/structure/create', function ($request, $response, $args) {
        global $conn;
        $session = $this->session;

        $name = mysqli_real_escape_string($conn, $_POST['structureName']);
        $description = mysqli_real_escape_string($conn, $_POST['structureDescription']);
        $tags = mysqli_real_escape_string($conn, $_POST['structureTags']);
        $category = mysqli_real_escape_string($conn, $_POST['structureCategory']);

        // just so they're set
        $structureImage = $structureFile = null;

        if ($_FILES['structureImage']['name']) {

            if (!$_FILES['structureImage']['error']) {
                $structureImage = strtolower($_FILES['structureImage']['name']);
                $valid = true;

                if ($valid) {
                    move_uploaded_file($_FILES['structureImage']['tmp_name'], '../public/img/' . $structureImage);
                }
            }
        }

        if ($_FILES['structureFile']['name']) {

            if (!$_FILES['structureFile']['error']) {
                $structureFile = strtolower($_FILES['structureFile']['name']);
                $valid = true;

                if ($valid) {
                    move_uploaded_file($_FILES['structureFile']['tmp_name'], '../public/structures/' . $structureFile);
                }
            }
        }

        $url = urlify($name);


        $query = "INSERT INTO structures (name,  description, mainImage, file, userid, tags, category, url, timestamp) VALUES ('$name', '$description', '$structureImage', '$structureFile','$session->userid','$tags', '$category', '$url',NOW())";


        if (mysqli_query($conn, $query) === false) {
            // handle error
            die('I have an error: ' . mysqli_error($conn));
        }

    });
    // structure update - POST (form handling)
    $app->post('/structure/update', function ($request, $response, $args) {
        global $conn;
        $session = $this->session;
        $valid = false;
        $name = mysqli_real_escape_string($conn, $_POST['structureName']);
        $description = mysqli_real_escape_string($conn, $_POST['structureDescription']);
        $tags = mysqli_real_escape_string($conn, $_POST['structureTags']);
        $category = mysqli_real_escape_string($conn, $_POST['structureCategory']);

        // just so they're set
        $structureImage = null;

        if ($_FILES['structureImage']['name']) {

            if (!$_FILES['structureImage']['error']) {
                $structureImage = strtolower($_FILES['structureImage']['name']);
                $valid = true;

                if ($valid) {
                    move_uploaded_file($_FILES['structureImage']['tmp_name'], '../public/img/' . $structureImage);
                }
            }
        }

        if ($valid) {
            $query = "UPDATE structures
                  SET name='$name', description='$description', tags='$tags',   category='$category', mainImage='$structureImage'
                  WHERE id='$session->currentlyEditing'";
        } else {
            $query = "UPDATE structures
                  SET name='$name', description='$description', tags='$tags',   category='$category'
                  WHERE id='$session->currentlyEditing'";
        }

        if (!mysqli_query($conn, $query)) {
            die("need better error messages" . $conn->error);
        } else {
            $session->message = "Success! Your structure has been successfully edited";

            return $response->withStatus(302)->withHeader('Location', '/account/dashboard');
        }


    });

})->add($auth);
/**
 * @param $name
 * @return mixed|string
 */
function urlify($name)
{
    $url = preg_replace("#[[:punct:]]#", "", $name);
    $url = strtolower($name);
    $url = str_replace(" ", "-", $url);
    $url = substr($url, 0, 30);

    return $url;
}