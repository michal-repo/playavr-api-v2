<?php

namespace Router;

require_once 'vendor/autoload.php';
require_once 'vrp/api.php';

use VRPAPI\VRP as VRP;
use \Bramus\Router\Router as BRouter;

$router = new BRouter();

header('Content-Type: application/json');

$router->set404('/api(/.*)?', function () {
    header('HTTP/1.1 404 Not Found');

    $jsonArray = array ();
    $jsonArray['status'] = "404";
    $jsonArray['status_text'] = "route not defined";

    echo json_encode($jsonArray);
});

// Define routes
$router->get('/', function () {
    echo json_encode(['status' => ['code' => 1, 'message' => 'ok'], "data" => 'nothing here']);
});

$router->get('/version', function () {
    $api = new VRP(true);
    echo json_encode($api->getVersion());
});

$router->get('/config', function () {
    $api = new VRP(true);
    echo json_encode($api->getConfig());
});

$router->get('/user/profile', function () {
    $api = new VRP();
    echo json_encode($api->getProfile());
});

$router->post('/auth/sign-in-password', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 3, 'message' => 'Accepts only JSON']]);
    } elseif (!isset ($j['login']) || !isset ($j['password'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 3, 'message' => 'Invalid credentials']]);
    } else {
        $api = new VRP(true);
        echo json_encode($api->login($j['login'], $j['password']));
    }
});

$router->post('/auth/refresh', function () {
    $j = json_decode(file_get_contents("php://input"), true);
    if (is_null($j) || $j === false) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 3, 'message' => 'Accepts only JSON']]);
    } elseif (!isset ($j['refresh_token'])) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['status' => ["code" => 3, 'message' => 'Invalid refresh_token']]);
    } else {
        $api = new VRP(true);
        echo json_encode($api->refreshToken($j['refresh_token']));
    }
});

function checkGetParam($param, $default)
{
    if (isset($_GET[$param])) {
        return $_GET[$param];
    }
    return $default;
}

$router->get(
    '/videos',
    function () {
        $page = checkGetParam('page-index', 0);
        $order = checkGetParam('order', "release_date");
        $direction = checkGetParam('direction', "desc");
        $excluded_categories = checkGetParam('excluded-categories', null);
        $included_categories = checkGetParam('included-categories', null);
        $title = checkGetParam('title', null);
        $page_size = checkGetParam('page-size', 12);
        $api = new VRP(true);
        echo json_encode($api->getVideos($page, $included_categories, $order, $direction, $excluded_categories, $title, $page_size));
    }
);

$router->get(
    '/video/(\w+)',
    function ($id) {
        $api = new VRP();
        echo json_encode($api->getVideoDetails($id));
    }
);

$router->get(
    '/categories',
    function () {
        $api = new VRP();
        echo json_encode($api->getCategories());
    }
);

$router->run();