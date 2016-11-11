<?php
$conn = mysqli_connect("localhost", "root", "root", "mcstructures");
$conn->set_charset('utf8');
// Routes

$app->get('/', function ($request, $response, $args) {
    global $conn;
    global $categoryIdToName;
    global $categoryNameToId;
    global $categoryMenuArray;
    $session = $this->session;


    $query = "
    (SELECT s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    ORDER BY likes DESC
    LIMIT 5)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    LIMIT 5)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    WHERE category = 0
    ORDER BY views DESC
    LIMIT 4)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    WHERE category = 1
    ORDER BY views DESC
    LIMIT 4)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    WHERE category = 2
    ORDER BY views DESC
    LIMIT 4)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    WHERE category = 3
    ORDER BY views DESC
    LIMIT 4)
  UNION ALL
    (SELECT
    s.name, s.id, s.description, s.mainImage, s.file, s.userid,
    s.views, s.downloads, s.likes, s.tags, s.category, s.extraImages,
    s.url, s.timestamp,  users.username,  users.image
    FROM
        structures s
    JOIN users
        ON s.userid=users.userid
    WHERE category = 4
    ORDER BY views DESC
    LIMIT 4)";

    $top = [];
    $latest = [];
    $categories = [];

    for ($i = 0; $i < 5; $i++) {
        $categories[$i]['name'] = ucfirst($categoryIdToName[$i]);
    }
    if ($result = mysqli_query($conn, $query)) {

        $i = 0;
        while (($row = $result->fetch_assoc()) != null) {
            if ($i < 5) {
                $top[] = $row;
            } else if ($i < 10) {
                $latest[] = $row;
            } else {
                switch ($row['category']) {
                    case 0:
                        $categories[0][] = $row;
                        break;
                    case 1:
                        $categories[1][] = $row;

                        break;
                    case 2:
                        $categories[2][] = $row;
                        break;
                    case 3:
                        $categories[3][] = $row;
                        break;
                    case 4:
                        $categories[4][] = $row;
                        break;
                    case 5:
                        $categories[5][] = $row;
                        break;
                }

            }
            $i++;
        }

        return $this->renderer->render($response, 'index.twig', [
            'logged-in' => false,
            'sliderList' => $top,
            'latestStructures' => $latest,
            'categoryMenu' => $categoryMenuArray,
            'categoryListing' => $categories,
            'loggedIn' => $session->loggedIn

        ]);
    } else {
        return $this->renderer->render($response, 'index.twig');
    }
});
// structure
$app->get('/structure/{url}', function ($request, $response, $args) {
    global $conn;
    global $categoryMenuArray;
    $session = $this->session;

    $url = $args['url'];
    $url = mysqli_real_escape_string($conn, $url);
    // the current structure,  the user data
    // and other structures made by the user
    // limited to 7
    $query =
        "(SELECT name,  description,  mainImage,  file,  s.userid,  views,  downloads,  likes,  tags,  category,
                 extraImages,  url,  timestamp,  users.username,  users.image,  users.title,  users.level
            FROM structures s
            JOIN users
              ON s.userid = users.userid
            WHERE s.userid IN
            (
            SELECT s.userid
            FROM structures s
            WHERE url = '$url'
            ) LIMIT 10)
        UNION
        SELECT structures,  usersOnline,  members,  downloads,  NULL ,  NULL ,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL
            FROM site
        UNION
        (SELECT name,  NULL ,  mainImage,  NULL,   s.userid,  NULL,  NULL,  NULL,  NULL,  NULL,  NULL,  url,  timestamp,  users.username,  users.image,  users.title,  users.level
            FROM structures s
            JOIN users
              ON s.userid = users.userid
            LIMIT 5)";

    $author = null;
    $currentStructure = []; // current structure (the one we want)
    $otherStructures = []; // other structures by the same person
    $latestStructures = []; // latest 5 structures on the site (yay)
    $siteData = [];

    if ($result = $conn->query($query)) {
        while (($data = $result->fetch_assoc()) != null) {
            // this is site data,  because userid is null
            if ($data['username'] == null) {
                $siteData['structures'] = $data['name'];
                $siteData['downloads'] = $data['description'];
                $siteData['comments'] = $data['mainImage'];
                $siteData['likes'] = $data['file'];

                continue;
            }
            // this is the current structure - because the URL is the same
            // we're checking empty so we don't overwrite anything
            if ($data['url'] == $url && empty($currentStructure)) {
                $currentStructure = $data;
                if (is_null($author)) {
                    $author = $data['username'];
                }
                continue;


            }
            // we're assuming we know author at this point,  hopefully we do
            // this is for other structures by the same person
            if ($data['username'] === $author) {
                if ($data['name'] != $currentStructure['name']) {
                    $otherStructures[] = $data;

                }
            } else {
                if (!is_null($author))
                    $latestStructures[] = $data;
            }


        }
    } else {
        die('there was an error: ' . $conn->error);
    }

    return $this->renderer->render($response, 'structure.twig', [
        'current' => $currentStructure,
        'otherStructures' => $otherStructures,
        'latestStructures' => $latestStructures,
        'site' => $siteData,
        'categoryMenu' => $categoryMenuArray,
        'loggedIn' => $session->loggedIn
    ]);


});

$app->get('/profile/{username}', function ($request, $response, $args) {
    global $conn;
    global $categoryMenuArray;
    $session = $this->session;
    $username = $args['username'];
    $username = mysqli_real_escape_string($conn, $username);
    $query = "SELECT *
              FROM users
              JOIN structures s
                ON s.userid = users.userid
              WHERE users.username='$username'";

    $structures = [];
    if (!($result = $conn->query($query))) {
        die('there was an error [' . $conn->error . ']');
    }
    $counter = 0;
    while (($data = $result->fetch_assoc()) != null) {
        $structures[$counter] = $data;
        $counter++;
    }
    $user = [
        "username" => $structures[0]['username'],
        "level" => $structures[0]['level'],
        "title" => $structures[0]['title'],
        "profileImage" => $structures[0]['image'],
        "blurb" => $structures[0]['profiletext'],
        "structureCount" => sizeof($structures)
    ];

    return $this->renderer->render($response, 'profile.twig', [
        'user' => $user,
        'structureList' => $structures,
        'categoryMenu' => $categoryMenuArray,
        'loggedIn' => $session->loggedIn

    ]);

});

$app->get('/category/{category}', function ($request, $response, $args) {
    global $conn;
    global $categoryNameToId;
    global $categoryMenuArray;

    // get the category from the URL
    $category = $args['category'];
    // escape it just because safety
    $category = mysqli_real_escape_string($conn, $category);

    // if the 'category' doesn't exist in the array,  it's a fake
    if (!array_key_exists($category, $categoryNameToId)) {
        // TODO: render not found page
        return null;
    }

    $categoryVal = $categoryNameToId[$category];
    $query = "SELECT
    s.name,
    s.id,
    s.description,
    s.mainImage,
    s.file,
    s.userid,
    s.views,
    s.downloads,
    s.likes,
    s.tags,
    s.category,
    s.extraImages,
    s.url,
    s.timestamp,
    users.username,
    users.image
FROM
    structures s
JOIN users
  ON s.userid=users.userid
WHERE
    category=$categoryVal
ORDER BY views DESC
LIMIT 25;
";

    if (!($result = $conn->query($query))) {
        die('there was an error [' . $conn->error . ']');
    }

    $data = [];
    while (($row = $result->fetch_assoc()) != null) {
        $data[] = $row;
    }

    $categoryMenuArray[$categoryVal]['selected'] = true;

    return $this->renderer->render($response, 'list.twig', [
        'structureList' => $data,
        'listName' => ucwords($category),
        'categoryMenu' => $categoryMenuArray,
        'page' => 1
    ]);

});