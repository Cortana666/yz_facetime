<?php

namespace Services;

use Services\Config;
use Workerman\Lib\Timer;

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
    public static function invite($connection, &$ws_worker, $data) {
        $ws_worker[$connection->room_id][$connection->user_id]['step'] = 3;
        $ws_worker[$connection->room_id][$connection->user_id]['start_time'] = time();
        Service::studentList($connection, $ws_worker);

        // 学生面试时间检测
        // $ws_worker->face_timer_id[$connection->room_id] = Timer::add(Config::$faceTime - 30, function()use($connection){
        //     $connection->send(Base::success('time_out', '即将超过一名考生的面试时间'));
        //     Timer::del($ws_worker->face_timer_id[$connection->room_id]);
        // }, null, false);
    }

    /**
     * 学生未邀请
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function shelve($connection, &$ws_worker, $data) {
        $ws_worker[$connection->room_id][$connection->user_id]['step'] = 2;
        foreach ($ws_worker[$connection->room_id] as $value) {
            if ($value['type'] == 1) {
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
    public static function hangUp($connection, &$ws_worker, $data) {
        // 通知考生结束面试
        $ws_worker->room[$connection->room_id][$connection->user_id]['step'] == 4;
        if ($ws_worker->room[$connection->room_id]['double']['status'] == 2) {
            $ws_worker->room[$connection->room_id]['double']['connection']->send(Base::success('hang_up'));
        }

        // 给所有老师发送学生列表
        Service::studentList($connection, $ws_worker);
        foreach ($ws_worker[$connection->room_id] as $value) {
            if ($value['type'] == 1) {
                $value['coonection']->send(Base::success('hang_up'));
            }
        }
    }
}