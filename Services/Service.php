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
        // 通知考生结束面试
        foreach ($ws_worker->room[$connection->room_id] as $key => $value) {
            if ($value['type'] == 3) {
                $aStudent[] = [
                    'user_id'=>$key,
                    'name'=>$value['info']['name'],
                    'status'=>$value['status'],
                    'step'=>$value['step'],
                ];
            }
        }

        // 给所有老师发送学生列表
        foreach ($ws_worker->room[$connection->room_id] as $key => $value) {
            if (in_array($value['type'], [1,2,4])) {
                $value['connection']->send(Base::success('student_list', '学生列表', $aStudent));
            }
        }
    }

    /**
     * 给学生发送排队信息
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function wait() {

    }

    /**
     * 给学生发送二机位码
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function double() {

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
        $ws_worker[$connection->room_id][$connection->user_id]['start_time'] = time();
    }

    /**
     * 给教师发送面试即将超时
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function overTime() {

    }
}