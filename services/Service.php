<?php

namespace Services;

/**
 * 服务类
 *
 * @package   Service
 * @author    yangjian
 * @date      2021-08-25
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Service {
    /**
     * 给教师发送学生列表
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function studentList($connection, $ws_worker) {
        foreach ($ws_worker->room[$connection->room_id]['member'] as $key => $value) {
            if ($value['type'] == 3) {
                $aStudent[] = [
                    'user_id'=>$key,
                    'status'=>$value['status'],
                    'step'=>$value['step'],
                ];
            }
        }

        // 给所有老师发送学生列表
        foreach ($ws_worker->room[$connection->room_id]['member'] as $key => $value) {
            if (in_array($value['type'], [1,2])) {
                if ($value['connection']) {
                    $value['connection']->send(Base::success('student_list', '学生列表', $aStudent));
                }
            }
        }
    }

    // /**
    //  * 给学生发送教师列表
    //  *
    //  * @author yangjian
    //  * @date   2021-08-25
    //  * @return void
    //  */
    // public static function teacherList($connection, $ws_worker) {
    //     foreach ($ws_worker->room[$connection->room_id]['teacher'] as $key => $value) {
    //         if (in_array($value['type'], [1,2])) {
    //             $aTeacher[] = [
    //                 'user_id'=>$key,
    //                 'status'=>$value['status'],
    //             ];
    //         }
    //     }

    //     // 给所有学生发送老师列表
    //     foreach ($ws_worker->room[$connection->room_id]['student'] as $key => $value) {
    //         if ($value['connection']) {
    //             $value['connection']->send(Base::success('teacher_list', '教师列表', $aTeacher));
    //         }
    //     }
    // }

    /**
     * 给学生发送排队信息
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function wait($connection, $ws_worker) {
        $face_time = 5;
        $wait_number = 0;
        foreach ($ws_worker->room[$connection->room_id]['member'] as $value) {
            if ($value['type'] == 3) {
                if ($value['connection']) {
                    $value['connection']->send(Base::success('wait', '学生等待信息', ['position'=>$wait_number, 'wait_time'=>$wait_number * $face_time, 'step'=>$value['step']]));
                }
                if ($value['step'] == 1) {
                    $wait_number ++;
                }
                if ($value['step'] == 4) {
                    $face_time = $value['count_time'] / 60;
                }
            }
        }
    }

    /**
     * 学生继续面试
     *
     * @author yangjian
     * @date   2021-08-26
     * @return void
     */
    public static function resumeFace($connection, &$ws_worker) {
        $connection->send(Base::success('resume_face'));
        $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['start_time'] = time();
    }

    /**
     * 网络状态
     *
     * @author yangjian
     * @date   2021-09-16
     * @param [type] $connection
     * @param [type] $ws_worker
     * @param [type] $data
     * @return void
     */
    public static function netQuality($connection, &$ws_worker, $data) {
        foreach ($ws_worker->room[$connection->room_id]['member'] as $value) {
            if ($value['connection']) {
                $value['connection']->send(Base::success('quality', '网络信息', ['user_id'=>$connection->exam_id, 'quality'=>$data['quality'], 'type'=>$connection->type]));
            }
        }
    } 
}