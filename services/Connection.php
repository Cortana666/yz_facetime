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
        // 验证身份
        if (in_array($data['type'], [1,2,3,4,5])) {
            $connection->close(Base::success('token_error', '身份验证失败'));
        }

        // 验证token
        $token = unserialize(Base::decrypt($data['kaowu_token']));
        if (time() - $token['time'] > 86400) {
            $connection->close(Base::success('token_error', 'token验证失败'));
        }
        if ($data['type'] == 3) {
            $user = $db->select('user_id,token')->from('ims_user')->where('user_id= :user_id AND token = :token')->bindValues(array('user_id' => $token['user_id'], 'token'=>$data['kaowu_token']))->row();
        } else {
            $user = $db->select('teacher_id,token')->from('face_teacher')->where('teacher_id= :user_id AND token = :token')->bindValues(array('user_id' => $token['user_id'], 'token'=>$data['kaowu_token']))->row();
        }
        if (!$user) {
            $connection->close(Base::success('token_error', 'token验证失败'));
        }

        // 初始化房间
        if (empty($ws_worker->room[$data['room_id']])) {
            if ($data['type'] == 5) {
                $connection->close(Base::success('room_error', '请先进入面试房间'));
            } else {
                // 获取成员user_id
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
                $students = $db->select("student_id,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
                if ($students) {
                    $students = array_column($students, 'card_id', 'student_id');
                    $users = $db->select("user_id,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('ims_user')->where("card_id in ('".implode("','", array_column($students, 'card_id'))."')")->query();
                    // if ($users) {
                    //     $users = array_column($users, 'user_id', 'card_id');
                    // }
                }
                var_dump($students);die;
                // 初始化房间
                $ws_worker->room[$data['room_id']] = array();
                $ws_worker->room[$data['room_id']]['double'] = [
                    'connection' => '',
                    'status'=>1,
                ];
                foreach ($members as $member) {
                    if ($member['type'] == 3) {
                        $ws_worker->room[$data['room_id']][$users[$students[$member['member_id']]]] = [
                            'type' => $member['type'],
                            'connection' => '',
                            'status'=>1,
                            'quota'=>1,
                            'step'=>1,
                            'start_time'=>'',
                            'end_time'=>'',
                            'count_time'=>0,
                            'times'=>[],
                        ];
                    } else {
                        $ws_worker->room[$data['room_id']][$member['member_id']] = [
                            'type' => $member['type'],
                            'connection' => '',
                            'status'=>1,
                        ];
                    }
                }

                // 定时器24小时后关闭连接
                // $ws_worker->close_timer_id = Timer::add(, function()use($connection){
                //     $connection->send(Base::success('time_out', '即将超过一名考生的面试时间'));
                //     Timer::del($connection->face_timer_id);
                // }, null, false);
            }
        }

        // 初始化连接
        if ($data['type'] == 5) {
            if ($ws_worker->room[$data['room_id']][$token['user_id']]['step'] != 3) {
                $connection->close(Base::success('room_error', '请在开始面试后扫描二维码'));
            }

            $connection->user_id = $token['user_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];
            $ws_worker->room[$connection->room_id]['double']['connection'] = $connection;
            $ws_worker->room[$connection->room_id]['double']['status'] = 2;
        } else {
            if ($ws_worker->room[$data['room_id']][$token['user_id']]['type'] != $data['type']) {
                $connection->close(Base::success('token_error', '身份验证失败'));
            }

            $connection->user_id = $token['user_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];
            $ws_worker->room[$connection->room_id][$connection->user_id]['connection'] = $connection;
            $ws_worker->room[$connection->room_id][$connection->user_id]['status'] = 2;
        }

        $connection->send(Base::success('token_success', '连接成功'));
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
            if ($ws_worker->room[$connection->room_id][$connection->user_id]['step'] == 3) {
                Service::resumeFace($connection, $ws_worker);
            } else {
                Service::wait();
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
    public static function closeConnect($connection, &$ws_worker) {
        if (!empty($connection->user_id)) {
            if ($connection->type == 5) {
                $ws_worker->room[$connection->room_id]['double']['connection'] = '';
                $ws_worker->room[$connection->room_id]['double']['status'] = 1;
            } else {
                $ws_worker->room[$connection->room_id][$connection->user_id]['connection'] = '';
                $ws_worker->room[$connection->room_id][$connection->user_id]['status'] = 1;

                if (in_array($connection->type, [1,2])) {
                    Service::teacherList($connection, $ws_worker);
                }
                if ($connection->type == 3) {
                    Service::studentList($connection, $ws_worker);
                    
                    if ($ws_worker->room[$connection->room_id][$connection->user_id]['step'] == 3) {
                        $ws_worker->room[$connection->room_id][$connection->user_id]['end_time'] = time();
                        $ws_worker->room[$connection->room_id][$connection->user_id]['count_time'] += $ws_worker->room[$connection->room_id][$connection->user_id]['end_time'] - $ws_worker->room[$connection->room_id][$connection->user_id]['start_time'];
                        $ws_worker->room[$connection->room_id][$connection->user_id]['times'][] = $ws_worker->room[$connection->room_id][$connection->user_id]['start_time'].'-'.$ws_worker->room[$connection->room_id][$connection->user_id]['end_time'];
                    }
                }
            }
        }
    }
}