<?php
use Workerman\Worker;
require_once __DIR__ . '/Vender/Workerman/Autoloader.php';

$ws_worker = new Worker("websocket://0.0.0.0:2000");

$ws_worker->count = 4;

$ws_worker->onMessage = function ($connection, $data) {
    foreach($connection->worker->connections as $con) {
        $con->send($data);
    }
};

Worker::runAll();
