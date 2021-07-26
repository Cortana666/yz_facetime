<?php

namespace Services;

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
     * 发送学生列表
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendList(&$ws_worker, $connection, $data, $self = false) {
        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            if ($value['type'] == 2) {
                $list_info[] = $value;
            }
        }

        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            if ($value['type'] == 1) {
                if (self) {
                    if ($connection->user_id == $key) {
                        $value['connection']->send(Base::success('list', '学生列表', $list_info));
                        break;
                    }
                } else {
                    $value['connection']->send(Base::success('list', '学生列表', $list_info));
                }
            }
        }
    }

    /**
     * 发送面试邀请
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendInvite(&$ws_worker, $connection, $data) {
        Student::sendInvite($ws_worker, $connection, $data);
    }

    /**
     * 发送断线等待
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendWait(&$ws_worker, $connection, $data) {
        Student::sendInvite($ws_worker, $connection, $data);
    }

    /**
     * 发送学生信息
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendInfo(&$ws_worker, $connection, $data) {
        $connection->send(Base::success('info', '学生信息', $ws_worker->room[$connection->room_id]['members'][$data['user_id']]));
    }

    /**
     * 结束面试
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function endFace($ws_worker, $connection, $data) {
        $ws_worker->room[$connection->room_id]['members'][$data['user_id']]['status'] = 4;

        Student::sendWait($ws_worker, $connection, $data);

        static::sendList($ws_worker, $connection, $data);
    }

    /**
     * 面试时间超时
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function sendTimeOut(&$ws_worker, $connection, $data) {
        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            if ($value['type'] == 1) {
                $value['connection']->send(Base::success('time_out', '面试时间超时'));
            }
        }
    }

    /**
     * 延长考试时间
     *
     * @author yangjian
     * @date   2021-07-26
     * @return void
     */
    public static function extendTime() {
        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            if ($value['type'] == 1) {
                $value['connection']->send(Base::success('extend_time', '延长面试时间'));
            }
        }

        $end_time = $ws_worker->room[$connection->room_id]['members']["end_time"];
        $ws_worker->room[$connection->room_id]['members']["end_time"] = date('Y-m-d H:i:s', strtotime('+1 hours', strtotime($end_time)));
    }

    /**
     * 结束考试
     *
     * @author yangjian
     * @date   2021-07-26
     * @return void
     */
    public static function endExam() {
        foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
            $value['connection']->send(Base::success('end_exam', '考场面试结束'));
        }
    }
}