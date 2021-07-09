<?php

use Workerman\Worker;
Use Services\Base;
Use Services\Worker as WorkerService;

require_once __DIR__ . '/vendor/autoload.php';

$ws_worker = new Worker("websocket://0.0.0.0:2000");

$ws_worker->count = 1;

$ws_worker->channel = array();

$ws_worker->onWorkerStart = function ($ws_worker) {
    global $db;
    $db = new \Workerman\MySQL\Connection('82.156.126.93', '3306', 'remote', 'Qwer1234;', 'yz_kaowu');
};

$ws_worker->onMessage = function ($connection, $data) {
    global $ws_worker;
    global $db;

    $data = json_decode($data, true);
    
    if ($data['send_type'] == "login") {
        // 验证学生端/教师端token
        if (!$face_token = Base::check_student_token($db, $data)) {
            // token验证失败
            $connection->close(Base::error("login", "签名验证失败", 'list'));
        } else {
            // 建立连接
            WorkerService::build_link($ws_worker, $connection, $data);

            // token验证成功返回面试token
            $connection->send(Base::success("login", "建立连接成功", '', ['face_token'=>$face_token]));

            // 广播列表
            WorkerService::send_list($ws_worker, $db, $connection);
        }
    }

    if ($data['send_type'] == "kick") {
        // 验证面试token
        if (!$face_token = Base::check_face_token($connection, $data['face_token'])) {
            // token验证失败
            $connection->close(Base::error("login", "签名验证失败", 'list'));
            unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
        } else {
            // token验证成功执行踢出操作
            $ws_worker->channel[$connection->channel][2][$data['user_id']]->close(Base::success("kick", "教师踢出操作"));
            unset($ws_worker->channel[$connection->channel][2][$data['user_id']]);
        }

        // 广播列表
        WorkerService::send_list($ws_worker, $db, $connection);
    }

    if ($data['send_type'] == "start") {
        // 验证面试token
        if (!$face_token = Base::check_face_token($connection, $data['face_token'])) {
            // token验证失败
            $connection->close(Base::error("login", "签名验证失败", 'list'));
            unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
        } else {
            // token验证成功开始面试
            $ws_worker->channel[$connection->channel][2][$data['user_id']]->send(Base::success("start", "开始面试"));
        }

        // 广播列表
        WorkerService::send_list($ws_worker, $db, $connection);
    }

    if ($data['send_type'] == "end") {
        // 验证面试token
        if (!$face_token = Base::check_face_token($connection, $data['face_token'])) {
            // token验证失败
            $connection->close(Base::error("login", "签名验证失败", 'list'));
            unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
        } else {
            // token验证成功开始面试
            $ws_worker->channel[$connection->channel][2][$data['user_id']]->send(Base::success("end", "结束面试"));
        }

        // 广播列表
        WorkerService::send_list($ws_worker, $db, $connection);
    }
};

Worker::runAll();
