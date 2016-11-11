<?php
// Application middleware

// e.g: $app->add(new \Slim\Csrf\Guard);


$app->add(new \Slim\Middleware\Session([
    'name' => 'mc_structures',
    'autorefresh' => true,
    'lifetime' => '1 hour'
]));