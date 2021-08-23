<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
Use Services\Base;
Use Services\Connection;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/service/base.php';
require_once __DIR__ . '/service/Connection.php';


// var_dump(Base::encrypt(serialize(['user_id'=>1, 'time'=>time()])));die;

// 连接心跳超时时间
define('HEARTBEAT_TIME', 30);

$ws_worker = new Worker("websocket://0.0.0.0:2000");
$ws_worker->count = 1;

// 考场数据
$ws_worker->room = array();

$ws_worker->onConnect = function ($connection) {
    // 30秒内未发送token断开连接
    $connection->auth_timer_id = Timer::add(10, function()use($connection){
        $connection->close(Base::error('token', '未接受到登录信息'));
    }, null, false);
};

$ws_worker->onWorkerStart = function ($ws_worker) {
    // 初始化Database操作
    global $db;
    $db = new \Workerman\MySQL\Connection('82.156.126.93', '3306', 'remote', 'Qwer1234;', 'yz_kaowu');

    // 定时器心跳检测发送心跳
    Timer::add(2, function()use($ws_worker){
        $time_now = time();
        foreach($ws_worker->connections as $connection) {
            $connection->send(Base::heart());
            if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                $connection->close(Base::error('heart', '未检测到心跳'));
            }
        }
    });
};

$ws_worker->onMessage = function ($connection, $data) {
    // // global $ws_worker;
    global $db;

    // 更新心跳时间
    $connection->lastMessageTime = time();

    // 解析json
    $data = json_decode($data, true);
    var_dump($data);

    if ($data['func'] == 'token') {
        Timer::del($connection->auth_timer_id);
        Connection::setConnection($ws_worker, $db, $connection, $data);
    } elseif ($data['func'] == 'heart') {
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
    Connection::closeConnection($ws_worker, $connection);
};

Worker::runAll();