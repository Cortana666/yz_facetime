<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
Use Services\Base;
Use Services\Init;
Use Services\Get;
Use Services\Send;

require_once __DIR__ . '/vendor/autoload.php';

// 连接心跳超时时间
define('HEARTBEAT_TIME', 30);

$ws_worker = new Worker("websocket://0.0.0.0:2000");
$ws_worker->count = 1;

// 考场数据
$ws_worker->room = array();

$worker->onConnect = function ($connection) {
    // 30秒内未发送token断开连接
    $connection->auth_timer_id = Timer::add(30, function()use($connection){
        $connection->close(Base::error('token', '未接受到登录信息'));
    }, null, false);
};

$ws_worker->onWorkerStart = function ($ws_worker) {
    // 初始化Database操作
    global $db;
    $db = new \Workerman\MySQL\Connection('82.156.126.93', '3306', 'remote', 'Qwer1234;', 'yz_kaowu');

    // 定时器心跳检测发送心跳
    Timer::add(10, function()use($ws_worker){
        // 发送心跳
        Base::heart();

        // 心跳检测
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
    global $ws_worker;
    global $db;
    global $service_list;

    // 更新心跳时间
    $connection->lastMessageTime = time();

    // 解析json
    $data = json_decode($data, true);

    if ($data['send_type'] == 'token') {
        Connection::setConnection();
    } else {
        switch ($connection->type) {
            case '1':
                Teacher::$data['send_type']();
                break;
            case '2':
                Student::$data['send_type']();
                break;
            case '3':
                Controller::$data['send_type']();
                break;
            default:
                # code...
                break;
        }
    }
};

$ws_worker->onClose = function ($connection) {
    Connection::closeConnection();
};


Worker::runAll();
