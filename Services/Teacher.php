<?php

namespace Services;

use Services\Base;
use Services\Service;

/**
 * 教师类
 *
 * @package   Teacher
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Teacher {
    /**
     * 教师发送面试邀请
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function invite($connection, $ws_worker, $data) {
        $ws_worker->room[$connection->room_id][$data['user_id']]['connection']->send(Base::success('invite'));
    }

    /**
     * 教师查看学生信息
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function showInfo($connection, $ws_worker, $data) {
        $connection->send($ws_worker->room[$connection->room_id][$data['user_id']]['info']);
    }

    /**
     * 教师挂断面试
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function hangUp($connection, &$ws_worker) {
        // 通知考生结束面试
        foreach ($ws_worker->room[$connection->room_id] as $key => $value) {
            if ($value['type'] == 3 && $value['step'] == 3) {
                $ws_worker->room[$connection->room_id][$key]['step'] == 4;
                $value['connection']->send(Base::success('hang_up'));
                if ($ws_worker->room[$connection->room_id]['double']['connection']
                || $ws_worker->room[$connection->room_id]['double']['status'] == 2) {
                    $ws_worker->room[$connection->room_id]['double']['connection']->send(Base::success('hang_up'));
                }
            }
        }

        // 给所有老师发送学生列表
        Service::studentList($connection, $ws_worker);
    }

    /**
     * 教师结束面试
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function endFace($connection, &$ws_worker) {
        foreach ($ws_worker->room[$connection->room_id] as $value) {
            $value['connection']->send(Base::success('end_face', '考场结束面试'));
        }
    }



    /**
     * 教师延长时间
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function extend($connection, &$ws_worker, $data) {
        $ws_worker->room[$connection->room_id][$data['user_id']]['quota'] += 1;
    }
}