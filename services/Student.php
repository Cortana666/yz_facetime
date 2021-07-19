<?php

namespace Services;

use Services\Base;

/**
 * Worker操作类
 *
 * @package   Worker
 * @author    yangjian
 * @date      2021-07-15
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Student {
    /**
     * 考生状态恢复
     *
     * @author yangjian
     * @date   2021-07-15
     * @param [type] $ws_worker
     * @param [type] $connection
     * @return void
     */
    public static function init(&$ws_worker, $connection) {
        if ($ws_worker->room[$connection->user_id][$connection->user_id]['step'] == 2) {
            $connection->send(Base::success("cStart", "开始面试"));
        }
    }

    /**
     * 发送学生等待页状态(单播/广播)
     *
     * @author lizg
     * @date   2021-07-15
     * @return void
     */
    public static function s_get_status(&$ws_worker, $db, $connection, $data)
    {
        $aRoomStudentsStatus = [];

        if ( !empty($ws_worker->room[$connection->room_id])) {
            foreach ($ws_worker->room[$connection->room_id] as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if ($value1['type'] == 2) {
                        $aRoomStudentsStatus[$key]['name'] = $value1['name'];
                        $aRoomStudentsStatus[$key]['status'] = $value1['status'];
                        if ($value1['status'] == 1) {
                            $aRoomStudentsStatus[$key]['_status'] = '离线';
                        } elseif ($value1['status'] == 2) {
                            $aRoomStudentsStatus[$key]['_status'] = '在线';
                        }
                    }
                }
            }
            $connection->send(Base::success('status', '请求学生状态成功', '', ['status' => $aRoomStudentsStatus]));
        } else {
            $connection->send(Base::error('status', '教室内无学生', '', []));
        }
    }

    /**
     * 发送邀请学生面试通知
     *
     * @author lizg
     * @date   2021-07-15
     * @return void
     */
    public static function s_set_start(&$ws_worker, $db, $connection, $data)
    {
        if ('invite' == $data['send_type']) {
            if (array_key_exists($connection->user_id, $ws_worker->room[$connection->room_id])) {
                if (2 == $connection->type) {
                    $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                        Base::success('invite', '开始面试', '', [])
                    );
                }
                $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                    Base::error('status', 'type类型错误', '', [])
                );
            } else {
                $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                    Base::error('status', '该学生不在此教室', '', [])
                );
            }
        }
    }

    /**
     * 发送学生面试结束通知
     *
     * @author lizg
     * @date   2021-07-15
     * @return void
     */
    public static function s_set_end(&$ws_worker, $db, $connection, $data)
    {
        $connection->user_id = $data['user_id'];
        $connection->room_id = $data['room_id'];
        $connection->type = $data['type'];
        $connection->face_status = $data['face_status'];

        if ('end' == $data['send_type']) {
            if (array_key_exists($connection->user_id, $ws_worker->room[$connection->room_id])) {
                if (2 == $connection->type) {
                    $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                        Base::success('end', '结束面试')
                    );
                } else {
                    $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                        Base::error('status', 'type类型错误', '', [])
                    );
                }
            } else {
                $ws_worker->room[$connection->room_id][$connection->user_id]['connection']->send(
                    Base::error('status', '该学生不在此教室', '', [])
                );
            }
        }
    }
}