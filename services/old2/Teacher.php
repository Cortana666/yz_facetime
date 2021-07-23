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
class Teacher {
    /**
     * 教师登录
     *
     * @author yangjian
     * @date   2021-07-15
     * @param [type] $ws_worker
     * @param [type] $db
     * @param [type] $connection
     * @param [type] $data
     * @return void
     */
    public function s_login(&$ws_worker, $db, &$connection, $data) {
        Base::s_check_token($connection, $data);
        Base::init($ws_worker, $db, $connection);
        Base::s_set_connection($ws_worker, $connection);
    }

    /**
     * 获取学生列表
     *
     * @author yangjian
     * @date   2021-07-15
     * @param [type] $ws_worker
     * @param [type] $db
     * @param [type] $connection
     * @param [type] $data
     * @return void
     */
    public static function s_get_student($ws_worker, $db, $connection, $data) {
        $face_user = $ws_worker->room[$connection->room_id];
        $student_list = array();
        foreach ($face_user as $key => $value) {
            if ($value['type'] == 2) {
                $student_list[] = $value;
            }
        }

        $connection->send(Base::success('cStudentList', '获取成功', '', $student_list));
    }

    // /**
    //  * 广播房间学生列表
    //  *
    //  * @author yangjian
    //  * @date   2021-07-08
    //  * @param [type] $channel
    //  * @return void
    //  */
    // public static function send_student_list($ws_worker, $db, $connection) {
    //     $face_student = static::get_student_list($ws_worker, $db, $connection);

    //     $connection->send(Base::success('list', '获取学生列表成功', '', ['list'=>$face_student]));
    // }

    // /**
    //  * 广播房间学生状态
    //  *
    //  * @author yangjian
    //  * @date   2021-07-08
    //  * @param [type] $channel
    //  * @return void
    //  */
    // public static function send_student_status($ws_worker, $connection, $air = false) {
    //     $status = array();
    //     if ($student = $ws_worker->channel[$connection->channel][2]) {
    //         foreach ($student as $key => $value) {
    //             $status[] = ['user_id'=>$value['user_id'], 'status'=>$value['status']];
    //         }
    //     }

    //     if ($air) {
    //         if (!empty($ws_worker->channel[$connection->channel][1])) {
    //             // 广播房间内学生状态
    //             foreach($ws_worker->channel[$connection->channel][1] as $teacher) {
    //                 $teacher['connection']->send(Base::success('status', '获取学生状态成功', '', ['status'=>$status]));
    //             }
    //         }
    //     } else {
    //         $connection->send(Base::success('status', '获取学生状态成功', '', ['status'=>$status]));
    //     }
    // }

    // /**
    //  * 广播房间学生等待信息
    //  *
    //  * @author yangjian
    //  * @date   2021-07-08
    //  * @param [type] $channel
    //  * @return void
    //  */
    // public static function send_student_wait($ws_worker, $db, $connection) {
    //     $face_student = static::get_student_list($ws_worker, $db, $connection);

    //     $face_time = 5;
    //     foreach ($ws_worker->channel[$connection->channel][2] as $student) {
    //         $position = 0;
    //         $wait_number = 0;
    //         foreach ($face_student as $value) {
    //             $position ++;
    //             if ($value['user_id'] != $student['user_id']) {
    //                 if ($list_student = $ws_worker->channel[$connection->channel][2][$value['user_id']] ?? []) {
    //                     if ($list_student['status'] == 1) {
    //                         $wait_number ++;
    //                     }
    //                     if ($list_student['status'] == 3) {
    //                         $face_time = $list_student['face_time'];
    //                     }
    //                 } else {
    //                     $wait_number ++;
    //                 }
    //             } else {
    //                 break;
    //             }
    //         }
    //         $students_wait[$student['user_id']]['position'] = $position;
    //         $students_wait[$student['user_id']]['wait_number'] = $wait_number;
    //         $students_wait[$student['user_id']]['wait_time'] = $face_time * ($wait_number+1);
    //     }

    //     if (!empty($ws_worker->channel[$connection->channel][2])) {
    //         // 广播房间内学生状态
    //         foreach($ws_worker->channel[$connection->channel][2] as $student) {
    //             // if (in_array($student['status'], [1,3])) {
    //                 $student['connection']->send(Base::success('wait', '获取学生等待信息成功', '', ['status'=>$students_wait[$student['user_id']]]));
    //             // }
    //         }
    //     }
    // }
}