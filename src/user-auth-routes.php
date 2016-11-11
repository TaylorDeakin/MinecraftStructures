<?php

$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.twig', [

    ]);
});

$app->post('/login', function ($request, $response, $args) {
    global $conn;
    $session = $this->session;
    if (!$_POST['username'] || !$_POST['password']) {
        // fail

    }
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT userid, username, password FROM users WHERE username='$username'";


    $result = $conn->query($query);
    $data = $result->fetch_assoc();
    $hash = $data['password'];

    // if verify succeeds, we've logged in
    if (password_verify($password, $hash)) {
        $session->username = $data['username'];
        $session->userid = $data['userid'];
        $session->loggedIn = true;

        return $response->withStatus(302)->withHeader('Location', '/account/dashboard');
    }


});

$app->get('/logoff', function ($request, $response, $args) {
    $session = $this->session;

    $session::destroy();
});

$app->get('/register', function ($request, $response, $args) {
    return $this->renderer->render($response, 'register.twig', [

    ]);
});


$app->post('/register', function ($request, $response, $args) {
    global $conn;

    var_dump($_POST);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $password = $_POST['password'];
    $confirmedPassword = $_POST['password-confirm'];

    if (!$password === $confirmedPassword) {
        die("fuck my life");
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    var_dump($password);

    $query = "INSERT INTO users (username, email, password,registeredOn)
                          VALUES ('$username', '$email', '$password', NOW())";

    var_dump($query);
    mysqli_query($conn,$query);
    var_dump(mysqli_affected_rows($conn));
    var_dump(mysqli_error($conn));
});