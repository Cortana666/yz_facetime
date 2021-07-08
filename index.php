<?php
use Workerman\Worker;
require_once __DIR__ . '/Vender/Workerman/Autoloader.php';
require_once __DIR__ . '/App/service.php';

$ws_worker = new Worker("websocket://0.0.0.0:2000");

$ws_worker->count = 1;

$ws_worker->channel = array();

$ws_worker->onMessage = function ($connection, $data)use($ws_worker) {
    $data = json_decode($data, true);

    if ($data['send_type'] == "login") {
        // 验证学生端/教师端token
        if (!$face_token = check_student_token($data)) {
            // token验证失败
            $connection->close(return_error("login", "签名验证失败", 'list'));
        } else {
            // 建立连接
            build_link($data, $connection);

            // token验证成功返回面试token
            $connection->send(return_success("login", "建立连接成功", '', ['face_token'=>$face_token]));

            // 广播列表
            send_list($data['channel']);
        }
    }

    if ($data['send_type'] == "kick") {
        // 验证面试token
        if (!$face_token = check_face_token($data['face_token'])) {
            // token验证失败
            $connection->close(return_error("login", "签名验证失败", 'list'));
            unset($ws_worker->channel[$connection->channel]['teacher'][$connection->user_id]);
        } else {
            // token验证成功执行踢出操作
            $ws_worker->channel[$connection->channel]['student'][$data['user_id']]->close(return_success("kick", "教师踢出操作", '', ['face_token'=>$face_token]));
            unset($ws_worker->channel[$connection->channel]['student'][$data['user_id']]);
        }

        // 广播
        send_list($connection->channel);
    }
};

// 建立关联
function build_link($data, &$connection) {
    global $ws_worker;

    // 初始化房间
    if (!isset($ws_worker->channel[$data['channel']])) {
        $ws_worker->channel[$data['channel']] = array();
        $ws_worker->channel[$data['channel']]['teacher'] = array();
        $ws_worker->channel[$data['channel']]['student'] = array();
    }

    // 个人数据关联
    $connection->user_id = $data['user_id'];
    $connection->channel = $data['channel'];

    // 建立user_id与wolakerman对应
    switch ($data['type']) {
        case '1':
            $ws_worker->channel[$data['channel']]['teacher'][$data['user_id']] = $connection;
            break;
        
        case '2':
            $ws_worker->channel[$data['channel']]['student'][$data['user_id']] = $connection;
            break;
        
        default:
            $connection->close(return_error("login", "账号类型错误", 'list'));
            break;
    }
}

// 广播房间学生列表
function send_list($channel) {
    global $ws_worker;

    // 获取账号名称
    // get_student_name();

    if (!empty($ws_worker->channel[$channel]['student'])) {
        // 广播房间内学生列表
        foreach($ws_worker->channel[$channel]['student'] as $connection) {
            $connection->send(return_success('list', '获取学生列表成功', '', ['list'=>array_keys($ws_worker->channel[$channel]['student'])]));
        }
    }
    if (!empty($ws_worker->channel[$channel]['teacher'])) {
        // 广播房间内学生列表
        foreach($ws_worker->channel[$channel]['teacher'] as $connection) {
            $connection->send(return_success('list', '获取学生列表成功', '', ['list'=>array_keys($ws_worker->channel[$channel]['student'])]));
        }
    }
}

Worker::runAll();
