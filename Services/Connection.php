<?php

namespace Services;

use Services\Base;
use Services\Service;

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
    public static function openConnect(&$connection, &$ws_worker, $data, $db) {
        // 验证token
        $token = unserialize(Base::decrypt($data['kaowu_token']));
        if (time() - $token['time'] > 86400) {
            return Base::success('token_error', 'token验证失败');
        }
        $user = $db->select('user_id,token')->from('ims_user')->where('user_id= :user_id')->bindValues(array('user_id' => $token['user_id']))->row();
        if (!$user) {
            return Base::success('token_error', 'token验证失败');
        }

        // 初始化房间
        if (empty($ws_worker->room[$data['room_id']]) && in_array($data['type'], [1,2,3,4])) {
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

            $students = $db->select("student_id,AES_DECRYPT(ks_name, '".Base::getEolKey()."') as ks_name,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id,AES_DECRYPT(mobile, '".Base::getEolKey()."') as mobile,college_id,special_id,direction_id")->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
            if ($students) {
                $students = array_column($students, null, 'student_id');
                $users = $db->select("user_id,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('ims_user')->where("card_id in ('".implode("','", array_column($students, "card_id"))."')")->query();
                if ($users) {
                    $users = array_column($users, 'user_id', 'card_id');
                }
            }
            

            // 初始化连接
            $ws_worker->room[$data['room_id']] = array();
            $ws_worker->room[$data['room_id']]['double'] = [
                'connection' => '',
                'status'=>1,
            ];
            foreach ($members as $member) {
                if ($member['type'] == 3) {
                    $ws_worker->room[$data['room_id']][$users[$students[$member['member_id']]['card_id']]] = [
                        'type' => $member['type'],
                        'connection' => '',
                        'status'=>1,
                        'step'=>1,
                        'quota'=>1,
                        'start_time'=>'',
                        'end_time'=>'',
                        'times'=>[],
                        'info'=>[
                            'name'=>'',
                            'card_id'=>'',
                            'mobile'=>'',
                            'college'=>'',
                            'special'=>'',
                            'direction'=>''
                        ]
                    ];
                }
                if (in_array($member['type'], [1,2,4])) {
                    $ws_worker->room[$data['room_id']][$member['member_id']] = [
                        'type' => $member['type'],
                        'connection' => '',
                        'status'=>1,
                        'info'=>[
                            'name'=>''
                        ]
                    ];
                }
            }
        }

        if (in_array($data['type'], [1,2,3,4])) {
            // 验证身份
            if ($ws_worker->room[$data['room_id']][$token['user_id']]['type'] != $data['type']) {
                return Base::success('room_error', '房间信息错误');
            }

            // 建立连接
            $connection->user_id = $token['user_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];
            $ws_worker->room[$connection->room_id][$connection->user_id]['connection'] = $connection;
            $ws_worker->room[$connection->room_id][$connection->user_id]['status'] = 2;
        }
        if ($data['type'] == 5) {
            // 验证状态
            if (empty($ws_worker->room[$data['room_id']]) || $ws_worker->room[$data['room_id']][$token['user_id']]['step'] != 3) {
                return Base::success('room_error', '房间信息错误');
            }

            $connection->user_id = $token['user_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];
            $ws_worker->room[$connection->room_id]['double']['connection'] = $connection;
            $ws_worker->room[$connection->room_id]['double']['status'] = 2;
        }

        return Base::success('token_success', '连接成功');
    }

    /**
     * 连接完成需要执行的操作
     *
     * @author yangjian
     * @date   2021-08-25
     * @param [type] $connection
     * @return void
     */
    public static function ready($connection, &$ws_worker) {
        // 执行当前状态下应执行的任务
        if (in_array($connection->type, [1,2])) {
            Service::studentList($connection, $ws_worker);
        }
        if ($connection->type == 3) {
            if ($ws_worker->room[$connection->room_id][$connection->user_id]['setp'] == 3) {
                Service::resumeFace($connection, $ws_worker);
                Service::double();
            } else {
                Service::wait();
            }
        }
        if (in_array($connection->type, [4])) {
            Service::teacherList($connection, $ws_worker);
            Service::showInfo($connection, $ws_worker);
        }
    }

    /**
     * 关闭连接
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function closeConnect($connection, &$ws_worker) {
        $ws_worker->room[$connection->room_id][$connection->user_id]['connection'] = '';
        $ws_worker->room[$connection->room_id][$connection->user_id]['status'] = 1;
        
        if (in_array($connection->type, [1,2])) {
            Service::teacherList($connection, $ws_worker);
        }
        if ($connection->type == 3) {
            Service::studentList($connection, $ws_worker);
            if ($ws_worker->room[$connection->room_id][$connection->user_id]['step'] == 3) {
                $ws_worker->room[$connection->room_id][$connection->user_id]['end_time'] = time();
                $ws_worker->room[$connection->room_id][$connection->user_id]['times'][] = $ws_worker->room[$connection->room_id][$connection->user_id]['start_time'].'-'.$ws_worker->room[$connection->room_id][$connection->user_id]['end_time'];     
                Service::showInfo($connection, $ws_worker);
            }
        }
    }
}