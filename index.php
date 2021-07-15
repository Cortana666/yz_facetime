<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
Use Services\Worker as WorkerService;

require_once __DIR__ . '/vendor/autoload.php';

define('HEARTBEAT_TIME', 60);

$service_list = [
    's_check_token',
];


$ws_worker = new Worker("websocket://0.0.0.0:2000");

$ws_worker->count = 1;

$ws_worker->room = array();

$worker->onConnect = function ($connection) {
    // 30秒内未发送token断开连接
    $connection->auth_timer_id = Timer::add(30, function()use($connection){
        $connection->close(Base::success("cLogout", "未接收到Token"));
    }, null, false);
};

$ws_worker->onWorkerStart = function ($ws_worker) {
    // 初始化Database操作
    global $db;
    $db = new \Workerman\MySQL\Connection('82.156.126.93', '3306', 'remote', 'Qwer1234;', 'yz_kaowu');

    // 心跳检测
    Timer::add(10, function()use($ws_worker){
        $time_now = time();
        foreach($ws_worker->connections as $connection) {
            $connection->send(Base::success("pong"));
            if ($time_now - $connection->lastMessageTime > HEARTBEAT_TIME) {
                $connection->close(Base::error("cLeave", "系统检测到您在考场中出现问题，请重新进入考场"));
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
    if (!in_array($data['send_type'], $service_list)) {
        $connection->send(Base::error("cMessage", "功能不存在"));
    }
    
    WorkerService::{$data['send_type']}($ws_worker, $db, $connection, $data);
};

$ws_worker->onClose = function ($connection) {
    
};


Worker::runAll();
