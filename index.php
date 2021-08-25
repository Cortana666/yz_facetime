<?php

require_once __DIR__ . '/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Lib\Timer;
Use Services\Config;
Use Services\Base;
Use Services\Connection;
Use Services\Teacher;
Use Services\Student;
Use Services\Controller;
Use Services\Service;

// require_once __DIR__ . '/service/config.php';
// require_once __DIR__ . '/service/base.php';
// require_once __DIR__ . '/service/Connection.php';
// require_once __DIR__ . '/service/teacher.php';
// require_once __DIR__ . '/service/student.php';
// require_once __DIR__ . '/service/controller.php';

$ws_worker = new Worker("websocket://0.0.0.0:" . Config::$wsPort);
$ws_worker->count = Config::$wsCount;
$ws_worker->room = array();

$ws_worker->onConnect = function ($connection) {
    // 30秒内未发送token断开连接
    $connection->auth_timer_id = Timer::add(15, function()use($connection){
        $connection->close(Base::error('no_token', '未接受到登录信息'));
    }, null, false);
};

$ws_worker->onWorkerStart = function ($ws_worker) {
    // 初始化Database操作
    global $db;
    $db = new \Workerman\MySQL\Connection(Config::$dbHost, Config::$dbPort, Config::$dbUser, Config::$dbPassword, Config::$dbName);

    // 定时器心跳检测发送心跳
    Timer::add(Config::$heartTime, function()use($ws_worker){
        $time_now = time();
        foreach($ws_worker->connections as $connection) {
            $connection->send(Base::heart());
            if ($time_now - $connection->lastMessageTime > Config::$heartOutTime) {
                $connection->close(Base::error('no_heart', '未检测到心跳'));
            }
        }
    });
};

$ws_worker->onMessage = function ($connection, $data) {
    global $ws_worker;
    global $db;

    // 更新心跳时间
    $connection->lastMessageTime = time();

    // 解析json
    $data = json_decode($data, true);
    var_dump($data);

    if ($data['func'] == 'token') {
        Connection::openConnect($ws_worker, $db, $connection, $data);
        Connection::ready($ws_worker, $connection);
    } elseif ($data['func'] == 'heart') {
        # code...
    } elseif ($data['func'] == 'close') {
        # code...
    } else {
    //     switch ($connection->type) {
    //         case '1':
    //             Teacher::$data['func']();
    //             break;
    //         case '2':
    //             Student::$data['func']();
    //             break;
    //         case '3':
    //             Controller::$data['func']();
    //             break;
    //         default:
    //             # code...
    //             break;
    //     }
    }
};

$ws_worker->onClose = function ($connection) {
    Connection::closeConnect($ws_worker, $connection);
};

Worker::runAll();