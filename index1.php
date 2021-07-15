<?php

use Workerman\Worker;
use Workerman\Lib\Timer;
Use Services\Base;
Use Services\Worker as WorkerService;

require_once __DIR__ . '/vendor/autoload.php';

define('HEARTBEAT_TIME', 60);

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
                $connection->close(Base::error("cLogout", "系统检测到您在考场中出现问题，请重新进入考场"));
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

    switch ($data['send_type']) {
        case 'sLogin':
            Timer::del($connection->auth_timer_id);
            if (!Base::check_token($connection, $data)) {
                $connection->close(Base::error("cLogout", "Token验证失败，请重新进入考场"));
            } else {
                WorkerService::set_connection($ws_worker, $db, $connection);
            }
            break;

        case 'sLogin':
            Timer::del($connection->auth_timer_id);
            Base::check_token($data);
            break;
        
        default:
            # code...
            break;
    }

    // WorkerService::$data['send_type']($ws_worker, $db, $connection, $data);

    // if ($data['send_type'] == "sLogin") {
    //     // 验证学生端/教师端token
    //     if (!$face_token = Base::check_user_token($db, $data)) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //     } else {
    //         // 建立连接
    //         WorkerService::build_link($ws_worker, $connection, $data);

    //         // token验证成功返回面试token
    //         $connection->send(Base::success("login", "建立连接成功", '', ['face_token'=>$face_token]));

    //         if ($data['type'] == 1) {
    //             // 发送学生列表
    //             WorkerService::send_student_list($ws_worker, $db, $connection);

    //             // 发送学生状态
    //             WorkerService::send_student_status($ws_worker, $connection);
    //         }

    //         if ($data['type'] == 2) {
    //             // 广播列表
    //             WorkerService::send_student_wait($ws_worker, $db, $connection);

    //             // 广播状态
    //             WorkerService::send_student_status($ws_worker, $connection, true);
    //         }
    //     }
    // }
    
    // if ($data['send_type'] == "login") {
    //     // 验证学生端/教师端token
    //     if (!$face_token = Base::check_user_token($db, $data)) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //     } else {
    //         // 建立连接
    //         WorkerService::build_link($ws_worker, $connection, $data);

    //         // token验证成功返回面试token
    //         $connection->send(Base::success("login", "建立连接成功", '', ['face_token'=>$face_token]));

    //         if ($data['type'] == 1) {
    //             // 发送学生列表
    //             WorkerService::send_student_list($ws_worker, $db, $connection);

    //             // 发送学生状态
    //             WorkerService::send_student_status($ws_worker, $connection);
    //         }

    //         if ($data['type'] == 2) {
    //             // 广播列表
    //             WorkerService::send_student_wait($ws_worker, $db, $connection);

    //             // 广播状态
    //             WorkerService::send_student_status($ws_worker, $connection, true);
    //         }
    //     }
    // }

    // if ($data['send_type'] == "invite") {
    //     // 验证面试token
    //     if (!Base::check_face_token($connection, $data['face_token'])) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //         unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
    //     } else {
    //         // token验证成功开始面试
    //         if ($data['user_id']) {
    //             $ws_worker->channel[$connection->channel][2][$data['user_id']]['connection']->send(Base::success("invite", "开始面试"));
    //         } else {
    //             // foreach ($variable as $key => $value) {
    //             //     # code...
    //             // }
    //         }
    //     }
    // }

    // if ($data['send_type'] == "doinvite") {
    //     // 验证面试token
    //     if (!Base::check_face_token($connection, $data['face_token'])) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //         unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
    //     } else {
    //         // token验证成功开始面试
    //         $ws_worker->channel[$connection->channel][2][$connection->user_id]['status'] = 2;

    //         // 广播列表
    //         WorkerService::send_student_wait($ws_worker, $db, $connection);

    //         // 广播状态
    //         WorkerService::send_student_status($ws_worker, $connection, true);
    //     }
    // }

    // if ($data['send_type'] == "close") {
    //     // 验证面试token
    //     if (!Base::check_face_token($connection, $data['face_token'])) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //         unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
    //     } else {
    //         // token验证成功开始面试
    //         $connection->close(Base::error("login", "未接受邀请，请重新进入房间！"));
    //     }
    // }

    // if ($data['send_type'] == "kick") {
    //     // 验证面试token
    //     if (!Base::check_face_token($connection, $data['face_token'])) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //         unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
    //     } else {
    //         // token验证成功执行踢出操作
    //         $ws_worker->channel[$connection->channel][2][$data['user_id']]->close(Base::success("kick", "教师踢出操作"));
    //         unset($ws_worker->channel[$connection->channel][2][$data['user_id']]);
    //     }

    //     // 广播列表
    //     WorkerService::send_list($ws_worker, $db, $connection);
    // }

    // if ($data['send_type'] == "end") {
    //     // 验证面试token
    //     if (!Base::check_face_token($connection, $data['face_token'])) {
    //         // token验证失败
    //         $connection->close(Base::error("login", "签名验证失败"));
    //         unset($ws_worker->channel[$connection->channel][1][$connection->user_id]);
    //     } else {
    //         // token验证成功开始面试
    //         $ws_worker->channel[$connection->channel][2][$data['user_id']]->send(Base::success("end", "结束面试"));
    //     }

    //     // 广播列表
    //     WorkerService::send_list($ws_worker, $db, $connection);
    // }
};

$ws_worker->onClose = function ($connection) {
    // global $ws_worker;
    
    // if ($connection->type == 2) {
    //     // 广播状态
    //     $ws_worker->channel[$connection->channel][2][$connection->user_id]['leave_status'] = $ws_worker->channel[$connection->channel][2][$connection->user_id]['status'];
    //     $ws_worker->channel[$connection->channel][2][$connection->user_id]['status'] = 0;
    //     WorkerService::send_student_status($ws_worker, $connection, true);
    // }
};


Worker::runAll();
