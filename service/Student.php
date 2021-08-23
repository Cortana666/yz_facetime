<?php

namespace Services;

/**
 * 学生类
 *
 * @package   Student
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Student {
    /**
     * 发送等待信息
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendWait(&$ws_worker, $connection, $data, $self = false) {
        $wait = 0;
        $face_time = 5;
        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            if ($value['type'] == 2) {
                $wait_info['wait'] = $wait;
                $wait_info['wait_time'] = ($wait + 1) * $face_time;
            }

            if ($value['step'] == 1) {
                $wait ++;
            }
            if ($value['step'] == 4) {
                $face_time = $value['face_time'];
            }

            if (self) {
                if ($connection->user_id == $key) {
                    $value['connection']->send(Base::success('wait', '等待信息', $wait_info));
                    break;
                }
            } else {
                $value['connection']->send(Base::success('wait', '等待信息', $wait_info));
            }
        }
    }

    /**
     * 发送等待信息
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendInvite(&$ws_worker, $connection, $data) {
        $ws_worker->room[$connection->room_id]['members'][$data['user_id']]['connection']->send(Base::success('invite', '面试邀请', ['step'=>$ws_worker->room[$connection->room_id]['members'][$data['user_id']]['step']]));
    }

    /**
     * 未进入面试
     *
     * @author yangjian
     * @date   2021-07-26
     * @return void
     */
    public static function cancelFace(&$ws_worker, $connection, $data) {
        $ws_worker->room[$connection->room_id]['members'][$data['user_id']]['step'] = 2;

        static::sendWait($ws_worker, $connection, $data);

        Teacher::sendList($ws_worker, $connection, $data);
    }

    /**
     * 确认进入面试
     *
     * @author yangjian
     * @date   2021-07-26
     * @return void
     */
    public static function startFace(&$ws_worker, $connection, $data) {
        $ws_worker->room[$connection->room_id]['members'][$data['user_id']]['step'] = 3;

        static::sendDoubleCode($ws_worker, $connection, $data);

        Teacher::sendList($ws_worker, $connection, $data);
    }

    /**
     * 发送二机位二维码
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendDoubleCode(&$ws_worker, $connection, $data) {
        $url = 'asdasd';
        $ws_worker->room[$connection->room_id]['members'][$data['user_id']]->send(Base::success('double_code', '二机位二维码', QrCode::create($url)));
    }

    /**
     * 结束面试
     *
     * @author yangjian
     * @date   2021-07-26
     * @return void
     */
    public static function endFace(&$ws_worker, $connection, $data) {
        $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['status'] = 4;

        static::sendWait($ws_worker, $connection, $data);

        Teacher::sendList($ws_worker, $connection, $data);
    }
}