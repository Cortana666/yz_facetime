<?php

namespace Services;

use Services\Base;
use Services\Services;

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
     * 打开连接
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function openConnect(&$ws_worker, $db, &$connection, $data) {
        // 验证token
        $data['kaowu_token'] = 'ICPTcwlGAJlcuzNa7DOmpD3wFtGPipxomMZ5pnDyColEQwjyRpkX+Drld0TegcGG';
        $token = unserialize(Base::decrypt($data['kaowu_token']));
        if (time() - $token['time'] > 86400) {
            return Base::error('token_error', 'token验证失败');
        }
        $user = $db->select('user_id,token')->from('ims_user')->where('user_id= :user_id')->bindValues(array('user_id' => $token['user_id']))->query();
        if (!$user) {
            return Base::error('token_error', 'token验证失败');
        }

        // 初始化房间
        if (empty($ws_worker->room[$data['room_id']])) {
            $members = $db->select('member_id,type')->from('face_room_member')->where('room_id= :room_id')->bindValues(array('room_id' => $data['room_id']))->query();
            foreach ($members as $member) {
                if ($member['type'] == 1) {
                    $main_ids[] = $member['member_id'];
                }
                if ($member['type'] == 2) {
                    $teacher_ids[] = $member['member_id'];
                }
                if ($member['type'] == 3) {
                    $student_ids[] = $member['member_id'];
                }
                if ($member['type'] == 4) {
                    $controller_ids[] = $member['member_id'];
                }
            }

            $teachers = $db->select('teacher_id,realname')->from('face_teacher')->where('teacher_id in ('.implode(',', array_merge($main_ids, $teacher_ids, $controller_ids)).')')->query();
            $students = $db->select("student_id,AES_decrypt(ks_name, '".Base::getEolKey()."'),AES_decrypt(card_id, '".Base::getEolKey()."'),AES_decrypt(mobile, '".Base::getEolKey()."'),bk_college,bk_special")->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
            $users = $db->select("user_id,AES_decrypt(card_id, '".Base::getEolKey()."')")->from('ims_user')->where('card_id in ('.implode(',', array_column($students, 'card_id')).')')->query();

            // 初始化连接
            $ws_worker->room[$data['room_id']] = array();
            $ws_worker->room[$data['room_id']]['double'] = [
                'connection' => '',
                'status'=>1,
            ];
            foreach ($members as $member) {
                $ws_worker->room[$data['room_id']][$token['user_id']] = [
                    'type' => $member['type'],
                    'connection' => '',
                    'status'=>1,
                    'step'=>1,
                ];
            }
        }

        // 验证身份
        if (empty($ws_worker->room[$data['room_id']][$token['user_id']]['type']) || $ws_worker->room[$data['room_id']][$token['user_id']]['type'] != $data['type']) {
            return Base::error('room_error', '房间信息错误');
        }

        // 建立连接
        $connection->user_id = $token['user_id'];
        $connection->room_id = $data['room_id'];
        $connection->type = $data['type'];
        $ws_worker->room['$connection->room_id'][$connection->user_id]['connection'] = $connection;
        $ws_worker->room['$connection->room_id'][$connection->user_id]['status'] = 2;

        return Base::success('token', '连接成功');
    }

    /**
     * 连接完成需要执行的操作
     *
     * @author yangjian
     * @date   2021-08-25
     * @param [type] $connection
     * @return void
     */
    public static function ready($ws_worker, $connection) {
        // 执行当前状态下应执行的任务
        if ($connection->type == 3) {
            if ($ws_worker->room[$connection->room_id][$connection->user_id]['setp'] == 3) {
                // 直接进入面试
                Service::startFace($connection->user_id);
            }
        }
    }

    /**
     * 关闭连接
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function closeConnect(&$ws_worker, $connection) {
        $ws_worker->room[$connection->user_id]['connection'] = '';
        $ws_worker->room[$connection->user_id]['status'] = 1;
    }
    
    // /**
    //  * 设置连接
    //  *
    //  * @author yangjian
    //  * @date   2021-07-08
    //  * @param [type] $data
    //  * @return void
    //  */
    // public static function setConnection(&$ws_worker, $db, &$connection, $data) {
    //     if (!static::checkToken($connection, $db, $data)) {
    //         $connection->close(Base::error('cLeave', "Token验证失败，请重新加入考场"));
    //     }

    //     $connection->room_id = $data['room_id'];
    //     $connection->type = $data['type'];

    //     static::init($ws_worker, $db, $connection);

    //     if ($data['type'] == 4) {
    //         $ws_worker->room[$connection->room_id]['double']['connection'] = $connection;
    //         $ws_worker->room[$connection->room_id]['double']['status'] = 2;
    //         // $ws_worker->room[$connection->room_id]['double']['face_status'] = $connection;
    //     } else {
    //         $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['connection'] = $connection;
    //         $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['status'] = 2;
    //         // $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['face_status'] = $data['face_status'];
    //     }

    //     if ($ws_worker->room[$connection->room_id]['members'][$connection->user_id]['step'] == 3) {
    //         // Student::sendInvite($ws_worker, $connection, $data);
    //         // 直接进入面试
    //     }

    //     switch ($connection->type) {
    //         case '1':
    //             Teacher::sendList($ws_worker, $connection, $data, true);
    //             Controller::sendList($ws_worker, $connection, $data);
    //             break;
    //         case '2':
    //             Teacher::sendList($ws_worker, $connection, $data);
    //             Student::sendWait($ws_worker, $connection, $data, true);
    //             Controller::sendList($ws_worker, $connection, $data);
    //             break;
    //         case '3':
    //             Controller::sendList($ws_worker, $connection, $data, true);
    //             break;
    //         default:
    //             # code...
    //             break;
    //     }
    // }

    // /**
    //  * 验证Token
    //  *
    //  * @author yangjian
    //  * @date   2021-07-08
    //  * @param [type] $data
    //  * @return void
    //  */
    // public static function checkToken(&$connection, $db, $data) {
    //     $data['user_token'] = 'ICPTcwlGAJlcuzNa7DOmpD3wFtGPipxomMZ5pnDyColEQwjyRpkX+Drld0TegcGG';
    //     $user_token = unserialize(Base::decrypt($data['user_token']));
    //     if (time() - $user_token['time'] > 86400) {
    //         return false;
    //     }

    //     $user = $db->select('user_id,token')->from('ims_user')->where('user_id= :user_id')->bindValues(array('user_id' => $user_token['user_id']))->query();
    //     if (!$user) {
    //         return false;
    //     }

    //     $connection->user_id = $user_token['user_id'];

    //     return true;
    // }

    // /**
    //  * 初始化当前channel（考场）
    //  *
    //  * @author yangjian
    //  * @date   2021-07-15
    //  * @param [type] $ws_worker
    //  * @return void
    //  */
    // public static function init(&$ws_worker, $db, $connection) {
    //     // 初始化房间
    //     if (!isset($ws_worker->room[$connection->room_id])) {
    //         $ws_worker->room[$connection->room_id]['start_time'] = '';
    //         $ws_worker->room[$connection->room_id]['end_time'] = '';
    //         $ws_worker->room[$connection->room_id]['members'] = array();

    //         $members = $db->select('member_id,type')->from('face_room_member')->where('room_id= :room_id')->bindValues(array('room_id' => $connection->room_id))->query();
    //         if ($members) {
    //             foreach ($members as $value) {
    //                 if ($value['type'] == 1) {
    //                     $teacher_ids[$value['member_id']] = $value['type'];
    //                 }
    //                 if ($value['type'] == 2) {
    //                     $student_ids[] = $value['member_id'];
    //                 }
    //                 if ($value['type'] == 3) {
    //                     $teacher_ids[$value['member_id']] = $value['type'];
    //                 }
    //             }
    //         }

    //         if ($teacher_ids) {
    //             $teachers = $db->select('teacher_id,realname')->from('face_teacher')->where('teacher_id in ('.implode(',', array_keys($teacher_ids)).')')->query();
    //             foreach ($teachers as $key => $value) {
    //                 $ws_worker->room[$connection->room_id]['members'][$value['teacher_id']] = [
    //                     'connection' => '',
    //                     'type' => $teacher_ids[$value['teacher_id']],
    //                     'status' => 1,
    //                     'name'=> $value['realname'],
    //                     'online_time'=> 0,
    //                     'face_time'=> 0,
    //                 ];
    //             }
    //         }
    //         if ($student_ids) {
    //             $students = $db->select('user_id,ks_name,card_id,bk_college,bk_special')->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
    //             foreach ($students as $key => $value) {
    //                 $ws_worker->room[$connection->room_id]['members'][$value['user_id']] = [
    //                     'connection' => '',
    //                     'type' => 2,
    //                     'status' => 1,
    //                     'step' => 1,
    //                     'name'=> $value['ks_name'],
    //                     'card_id'=> $value['card_id'],
    //                     'bk_college'=> $value['bk_college'],
    //                     'bk_special'=> $value['bk_special'],
    //                     // 'face_status'=> 2,
    //                     'online_time'=> 0,
    //                     'face_time'=> 0,
    //                 ];
    //             }
    //         }
    //         $ws_worker->room[$connection->room_id]['double'] = [
    //             'connection' => '',
    //             'type' => 4,
    //             'status' => 1,
    //             // 'face_status'=> 2,
    //             'online_time'=> 0,
    //             'face_time'=> 0,
    //         ];
    //     }
    // }

    // /**
    //  * 关闭连接
    //  *
    //  * @author yangjian
    //  * @date   2021-07-23
    //  * @return void
    //  */
    // public static function closeConnection(&$ws_worker, $connection) {
    //     if ($connection->type == 4) {
    //         $ws_worker->room[$connection->room_id]['double']['status'] = 1;
    //         $ws_worker->room[$connection->room_id]['double']['connection'] = '';
    //     } else {
    //         $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['status'] = 1;
    //         $ws_worker->room[$connection->room_id]['members'][$connection->user_id]['connection'] = '';
    //     }
        
    //     switch ($connection->type) {
    //         case '1':
    //             Teacher::sendList($ws_worker, $connection, $data, true);
    //             Controller::sendList($ws_worker, $connection, $data);
    //             break;
    //         case '2':
    //             Teacher::sendList($ws_worker, $connection, $data);
    //             Student::sendWait($ws_worker, $connection, $data, true);
    //             Controller::sendList($ws_worker, $connection, $data);
    //             break;
    //         case '3':
    //             Controller::sendList($ws_worker, $connection, $data, true);
    //             break;
    //         default:
    //             # code...
    //             break;
    //     }
    // }
}