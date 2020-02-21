<?php
require './pdos/DatabasePdo.php';
require './pdos/IndexPdo.php';
require './vendor/autoload.php';
require './controllers/myFunction.php';
require './controllers/validationFunction.php';

use \Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('Asia/Seoul');
ini_set('default_charset', 'utf8mb4');

//에러출력하게 하는 코드
//error_reporting(E_ALL); ini_set("display_errors", 1);

//Main Server API
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {

    $r->addRoute('GET', '/jwt', ['IndexController', 'jwtUser']);      #특정 토큰으로 사용자 알기 & test용
    $r->addRoute('POST', '/user', ['IndexController', 'createUser']);
    $r->addRoute('POST', '/jwt', ['IndexController', 'login']);
    $r->addRoute('PUT', '/user/body/initial', ['IndexController', 'userBodyInfo']);
    $r->addRoute('POST', '/user/goal/initial', ['IndexController', 'userGoal']);
    $r->addRoute('GET', '/profile/{userNo}', ['IndexController', 'userProfile']);   #추후에 친구인지 아닌지에 따라 다른 화면 구현예정 우선은 프로필이미지, 이름, 회원가입일만 보임.
    $r->addRoute('PUT', '/profile', ['IndexController', 'editProfile']);
    $r->addRoute('GET', '/friend', ['IndexController', 'userFriend']);
    $r->addRoute('POST', '/friend', ['IndexController', 'addFriend']);
    $r->addRoute('GET', '/friend/request', ['IndexController', 'requestedFriend']);
    $r->addRoute('DELETE', '/friend/request/{type}', ['IndexController', 'acceptOrDenyRequest']);

    $r->addRoute('GET', '/sneakers/brand', ['IndexController', 'searchSneakersBrand']);
    $r->addRoute('GET', '/sneakers/model/{brandNo}', ['IndexController', 'searchSneakersModel']);
    $r->addRoute('POST', '/sneakers', ['IndexController', 'addSneakers']);
    $r->addRoute('PUT', '/sneakers', ['IndexController', 'editSneakers']);
    $r->addRoute('DELETE', '/sneakers', ['IndexController', 'deleteSneakers']);
    $r->addRoute('GET', '/user/sneakers', ['IndexController', 'userSneakers']);
    $r->addRoute('GET', '/sneakers', ['IndexController', 'sneakersInfo']);      #추후에 sneakers관련된것중 활동, 운동km수 누적 구현해야함.

    $r->addRoute('POST', '/upload/{type}', ['IndexController', 'uploadImage']);


   //$r->addRoute('GET', '/profile-tab', ['IndexController', 'profileTab']);
    //$r->addRoute('PUT', '/profile', ['IndexController', 'editProfile']);

//    $r->addRoute('GET', '/users', 'get_all_users_handler');
//    // {id} must be a number (\d+)
//    $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
//    // The /{title} suffix is optional
//    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');
});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 로거 채널 생성
$accessLogs = new Logger('ACCESS_LOGS');
$errorLogs = new Logger('ERROR_LOGS');
// log/your.log 파일에 로그 생성. 로그 레벨은 Info
$accessLogs->pushHandler(new StreamHandler('logs/access.log', Logger::INFO));
$errorLogs->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// add records to the log
//$log->addInfo('Info log');
// Debug 는 Info 레벨보다 낮으므로 아래 로그는 출력되지 않음
//$log->addDebug('Debug log');
//$log->addError('Error log');

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        switch ($routeInfo[1][0]) {
            case 'IndexController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/IndexController.php';
                break;
            case 'MainController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MainController.php';
                break;
            /*case 'EventController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/EventController.php';
                break;
            case 'ProductController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ProductController.php';
                break;
            case 'SearchController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/SearchController.php';
                break;
            case 'ReviewController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ReviewController.php';
                break;
            case 'ElementController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ElementController.php';
                break;
            case 'AskFAQController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/AskFAQController.php';
                break;*/
        }

        break;
}
