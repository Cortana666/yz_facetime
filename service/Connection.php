<?php

namespace Services;

/**
 * 初始化连接类
 *
 * @package   Connection
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Connection {
    /**
     * 验证Token
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function checkToken(&$connection, $data) {
        $user_token = unserialize(Base::decrypt($data['user_token']));
        if (time() - $user_token['time'] > 10800) {
            return false;
        }
        return true;
    }

    /**
     * 初始化当前channel（考场）
     *
     * @author yangjian
     * @date   2021-07-15
     * @param [type] $ws_worker
     * @return void
     */
    public static function init(&$ws_worker, $db, $connection) {
        // 初始化房间
        if (!isset($ws_worker->room[$connection->room_id])) {
            $ws_worker->room[$connection->room_id]['start_time'] = '';
            $ws_worker->room[$connection->room_id]['end_time'] = '';
            $ws_worker->room[$connection->room_id]['members'] = array();

            $members = $db->select('member_id,type')->from('face_room_member')->where('room_id= :room_id')->bindValues(array('room_id' => $connection->room_id))->query();
            if ($members) {
                foreach ($members as $value) {
                    if ($value['type'] == 1) {
                        $teacher_ids[$value['member_id']] = $value['type'];
                    }
                    if ($value['type'] == 2) {
                        $student_ids[] = $value['member_id'];
                    }
                    if ($value['type'] == 3) {
                        $teacher_ids[$value['member_id']] = $value['type'];
                    }
                }
            }

            if ($teacher_ids) {
                $teachers = $db->select('teacher_id,realname')->from('face_teacher')->where('teacher_id in ('.implode(',', array_keys($teacher_ids)).')')->query();
                foreach ($teachers as $key => $value) {
                    $ws_worker->room[$connection->room_id]['members'][$value['teacher_id']] = [
                        'connection' => '',
                        'type' => $teacher_ids[$value['teacher_id']],
                        'status' => 1,
                        'name'=> $value['realname'],
                        'online_time'=> 0,
                        'face_time'=> 0,
                    ];
                }
            }
            if ($student_ids) {
                $students = $db->select('user_id,ks_name,card_id,bk_college,bk_special')->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
                foreach ($students as $key => $value) {
                    $ws_worker->room[$connection->room_id]['members'][$value['user_id']] = [
                        'connection' => '',
                        'type' => 2,
                        'status' => 1,
                        'step' => 1,
                        'name'=> $value['ks_name'],
                        'card_id'=> $value['card_id'],
                        'bk_college'=> $value['bk_college'],
                        'bk_special'=> $value['bk_special'],
                        'face_status'=> 2,
                        'online_time'=> 0,
                        'face_time'=> 0,
                    ];
                }
            }
        }
    }

    /**
     * 设置连接
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function setConnection(&$ws_worker, $db, $connection, $data) {
        if (!static::checkToken($connection, $data)) {
            $connection->close(Base::error('cLeave', "Token验证失败，请重新进入考场"));
        }

        $connection->user_id = $data['user_id'];
        $connection->room_id = $data['room_id'];
        $connection->type = $data['type'];

        static::init();

        $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['connection'] = $connection;
        $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['status'] = 2;
        $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['face_status'] = $data['face_status'];

        if ($ws_worker->room[$connection->room_id]['members'][$connection->user_id]['step'] == 3) {
            Student::sendInvite($ws_worker, $connection, $data);
        }

        switch ($connection->type) {
            case '1':
                Teacher::sendList($ws_worker, $connection, $data, true);
                Controller::sendList($ws_worker, $connection, $data);
                break;
            case '2':
                Teacher::sendList($ws_worker, $connection, $data);
                Student::sendWait($ws_worker, $connection, $data, true);
                Controller::sendList($ws_worker, $connection, $data);
                break;
            case '3':
                Controller::sendList($ws_worker, $connection, $data, true);
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * 关闭连接
     *
     * @author yangjian
     * @date   2021-07-23
     * @return void
     */
    public static function closeConnection(&$ws_worker, $connection) {
        $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['status'] = 1;
        
        switch ($connection->type) {
            case '1':
                Teacher::sendList($ws_worker, $connection, $data, true);
                Controller::sendList($ws_worker, $connection, $data);
                break;
            case '2':
                Teacher::sendList($ws_worker, $connection, $data);
                Student::sendWait($ws_worker, $connection, $data, true);
                Controller::sendList($ws_worker, $connection, $data);
                break;
            case '3':
                Controller::sendList($ws_worker, $connection, $data, true);
                break;
            default:
                # code...
                break;
        }
    }
}