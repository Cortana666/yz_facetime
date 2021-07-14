<?php

namespace Services;

use Services\Base;

/**
 * Worker操作类
 *
 * @package   Worker
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Worker {
    /**
     * 建立关联
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @param [type] $connection
     * @return void
     */
    public static function build_link(&$ws_worker, &$connection, $data) {
        // 初始化房间
        if (!isset($ws_worker->channel[$data['channel']])) {
            $ws_worker->channel[$data['channel']] = array();
            $ws_worker->channel[$data['channel']][1] = array();
            $ws_worker->channel[$data['channel']][2] = array();
        }

        // 个人数据关联
        $connection->user_id = $data['user_id'];
        $connection->channel = $data['channel'];
        $connection->type = $data['type'];


        if (!isset($ws_worker->channel[$data['channel']][$data['type']][$data['user_id']])) {
            // 建立user_id与wolakerman对应
            if (!isset($ws_worker->channel[$data['channel']][$data['type']])) {
                $connection->close(Base::error("login", "账号类型错误", 'list'));
            } else {
                $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['connection'] = $connection;
                $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['user_id'] = $data['user_id'];
                $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['status'] = 1;
                $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['leave_status'] = 0;
                $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['face_time'] = 0;
            }
        } else {
            $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['connection'] = $connection;
            $leave_status = $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['leave_status'];
            $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['status'] = $leave_status;
        }

        if ($data['type'] == 2) {
            if ($ws_worker->channel[$data['channel']][$data['type']][$data['user_id']]['status'] == 2) {
                $connection->send(Base::success("doinvite", "开始面试"));
            }
        }
    }

    /**
     * 获取学生列表
     *
     * @author yangjian
     * @date   2021-07-14
     * @param [type] $db
     * @return void
     */
    private static function get_student_list($ws_worker, $db, $connection) {
        if (!$face_student = $ws_worker->student_list) {
            // 获取房间内学生id
            $student_ids = $db->select('student_id')->from('face_room_student')->where('room_id= :room_id')->bindValues(array('room_id'=>$connection->channel))->query();
            if ($student_ids) {
                $student_ids = array_column($student_ids, 'student_id');
            }

            // 获取账号名称
            $face_student = array();
            if ($student_ids) {
                $face_student = $db->select('student_id,user_id,name,card_id')->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
            }

            $ws_worker->student_list = $face_student;
        }

        return $face_student;
    }

    /**
     * 广播房间学生列表
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $channel
     * @return void
     */
    public static function send_student_list($ws_worker, $db, $connection) {
        $face_student = static::get_student_list($ws_worker, $db, $connection);

        $connection->send(Base::success('list', '获取学生列表成功', '', ['list'=>$face_student]));
    }

    /**
     * 广播房间学生状态
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $channel
     * @return void
     */
    public static function send_student_status($ws_worker, $connection, $air = false) {
        $status = array();
        if ($student = $ws_worker->channel[$connection->channel][2]) {
            foreach ($student as $key => $value) {
                $status[] = ['user_id'=>$value['user_id'], 'status'=>$value['status']];
            }
        }

        if ($air) {
            if (!empty($ws_worker->channel[$connection->channel][1])) {
                // 广播房间内学生状态
                foreach($ws_worker->channel[$connection->channel][1] as $teacher) {
                    $teacher['connection']->send(Base::success('status', '获取学生状态成功', '', ['status'=>$status]));
                }
            }
        } else {
            $connection->send(Base::success('status', '获取学生状态成功', '', ['status'=>$status]));
        }
    }

    /**
     * 广播房间学生等待信息
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $channel
     * @return void
     */
    public static function send_student_wait($ws_worker, $db, $connection) {
        $face_student = static::get_student_list($ws_worker, $db, $connection);

        $face_time = 5;
        foreach ($ws_worker->channel[$connection->channel][2] as $student) {
            $position = 0;
            $wait_number = 0;
            foreach ($face_student as $value) {
                $position ++;
                if ($value['user_id'] != $student['user_id']) {
                    if ($list_student = $ws_worker->channel[$connection->channel][2][$value['user_id']] ?? []) {
                        if ($list_student['status'] == 1) {
                            $wait_number ++;
                        }
                        if ($list_student['status'] == 3) {
                            $face_time = $list_student['face_time'];
                        }
                    } else {
                        $wait_number ++;
                    }
                } else {
                    break;
                }
            }
            $students_wait[$student['user_id']]['position'] = $position;
            $students_wait[$student['user_id']]['wait_number'] = $wait_number;
            $students_wait[$student['user_id']]['wait_time'] = $face_time * ($wait_number+1);
        }

        if (!empty($ws_worker->channel[$connection->channel][2])) {
            // 广播房间内学生状态
            foreach($ws_worker->channel[$connection->channel][2] as $student) {
                // if (in_array($student['status'], [1,3])) {
                    $student['connection']->send(Base::success('wait', '获取学生等待信息成功', '', ['status'=>$students_wait[$student['user_id']]]));
                // }
            }
        }
    }
}