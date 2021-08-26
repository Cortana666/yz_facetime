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
     * 学生接受邀请
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function invite($connection, &$ws_worker) {
        $ws_worker[$connection->room_id][$connection->user_id]['step'] = 3;
        $ws_worker[$connection->room_id][$connection->user_id]['start_time'] = time();
        Service::double();
        Service::studentList($connection, $ws_worker);
        Service::showInfo($connection, $ws_worker, ['user_id'=>$connection->user_id]);
    }

    /**
     * 学生未邀请
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function shelve($connection, &$ws_worker) {
        $ws_worker[$connection->room_id][$connection->user_id]['step'] = 2;
        foreach ($ws_worker[$connection->room_id] as $value) {
            if (in_array($value['type'], [1,2])) {
                $value['coonection']->send(Base::success('shelve'));
            }
        }
        Service::studentList($connection, $ws_worker);
    }

    /**
     * 学生挂断面试
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function hangUp($connection, &$ws_worker) {
        // 通知考生结束面试
        $ws_worker->room[$connection->room_id][$connection->user_id]['step'] == 4;
        if ($ws_worker->room[$connection->room_id]['double']['connection']
        || $ws_worker->room[$connection->room_id]['double']['status'] == 2) {
            $ws_worker->room[$connection->room_id]['double']['connection']->send(Base::success('hang_up'));
            $ws_worker->room[$connection->room_id]['double']['status']->send(Base::success('hang_up'));
        }

        // 给所有老师发送学生列表
        Service::studentList($connection, $ws_worker);
        Service::showInfo($connection, $ws_worker, ['user_id'=>'']);
    }
}