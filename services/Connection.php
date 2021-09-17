<?php

namespace Services;

use Services\Base;
use Services\Service;
use Workerman\Lib\Timer;

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
        if (!in_array($data['type'], [1,2,3,4,5])) {
            $connection->close(Base::success('token_error', '身份验证失败(0)'));
        }

        // 验证token
        $token = unserialize(Base::decrypt($data['kaowu_token']));
        if (time() - $token['time'] > 86400) {
            $connection->close(Base::success('token_error', 'token验证失败(0)'));
        }
        if ($data['type'] == 3) {
            $user = $db->select('user_id,token')->from('ims_user')->where('user_id= :user_id AND token = :token')->bindValues(array('user_id' => $token['user_id'], 'token'=>$data['kaowu_token']))->row();
        } else {
            $user = $db->select('teacher_id,token')->from('face_teacher')->where('teacher_id= :user_id AND token = :token')->bindValues(array('user_id' => $token['user_id'], 'token'=>$data['kaowu_token']))->row();
        }
        if (!$user) {
            $connection->close(Base::success('token_error', 'token验证失败(1)'));
        }

        $room = $db->select('scene_id,status')->from('face_room')->where('room_id= :room_id')->bindValues(array('room_id' => $data['room_id']))->row();
        if (!$room) {
            $connection->close(Base::success('room_error', '面试房间错误'));
        }
        if ($room['status'] != 2) {
            $connection->close(Base::success('room_error', '该考场未在考试'));
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
                // $students = $db->select("student_id,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
                // if ($students) {
                //     $users = $db->select("user_id,AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('ims_user')->where("AES_DECRYPT(card_id, '".Base::getEolKey()."') in ('".implode("','", array_column($students, 'card_id'))."')")->query();
                //     if ($users) {
                //         $users = array_column($users, 'user_id', 'card_id');
                //     }
                // }
                // $students = array_column($students, 'card_id', 'student_id');
                
                // 初始化房间
                $ws_worker->room[$data['room_id']] = array();
                $ws_worker->room[$data['room_id']]['double'] = [
                    'type'=>5,
                    'connection' => '',
                    'status'=>1,
                ];
                foreach ($members as $member) {
                    if ($member['type'] == 3) {
                        $ws_worker->room[$data['room_id']]['student'][$member['member_id']] = [
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
                        $ws_worker->room[$data['room_id']]['teacher'][$member['member_id']] = [
                            'type' => $member['type'],
                            'connection' => '',
                            'status'=>1,
                        ];
                    }
                }

                // // 定时器24小时后关闭连接
                // $ws_worker->close_timer_id[$data['room_id']] = Timer::add(3600, function()use($ws_worker, $data){
                //     $close_flag = true;
                //     foreach ($ws_worker->room[$data['room_id']] as $value) {
                //         if ($value['status'] == 2) {
                //             $close_flag = false;
                //         }
                //     }
                //     if ($close_flag) {
                //         Timer::del($data['room_id']);
                //         unset($ws_worker->room[$data['room_id']]);
                //     }
                // }, null, false);
            }
        }

        // 初始化连接
        if ($data['type'] == 5) {
            $user = $db->select("AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('ims_user')->where('user_id= :user_id')->bindValues(array('user_id' => $token['user_id']))->row();
            $students = $db->select("student_id")->from('face_student')->where("AES_DECRYPT(card_id, '".Base::getEolKey()."')= :card_id")->bindValues(array('card_id' => $user['card_id']))->query();
            $members = $db->select('member_id')->from('face_room_member')->where('room_id= :room_id AND member_id in ('.implode(',', array_column($students, 'student_id')).')')->bindValues(array('room_id' => $data['room_id']))->row();
            
            if ($ws_worker->room[$data['room_id']]['student'][$members['member_id']]['step'] != 3) {
                $connection->close(Base::success('room_error', '请在开始面试后扫描二维码'));
            }

            $connection->member_id = $members['member_id'];
            $connection->school_id = $token['school_id'];
            $connection->school_year = $token['school_year'];
            $connection->scene_id = $room['scene_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];
            $ws_worker->room[$connection->room_id]['double']['connection'] = $connection;
            $ws_worker->room[$connection->room_id]['double']['status'] = 2;
        } else {
            $connection->school_id = $token['school_id'];
            $connection->school_year = $token['school_year'];
            $connection->scene_id = $room['scene_id'];
            $connection->room_id = $data['room_id'];
            $connection->type = $data['type'];

            if ($data['type'] == 3) {
                $user = $db->select("AES_DECRYPT(card_id, '".Base::getEolKey()."') as card_id")->from('ims_user')->where('user_id= :user_id')->bindValues(array('user_id' => $token['user_id']))->row();
                $students = $db->select("student_id")->from('face_student')->where("AES_DECRYPT(card_id, '".Base::getEolKey()."')= :card_id")->bindValues(array('card_id' => $user['card_id']))->query();
                $members = $db->select('member_id')->from('face_room_member')->where('room_id= :room_id AND member_id in ('.implode(',', array_column($students, 'student_id')).')')->bindValues(array('room_id' => $data['room_id']))->row();

                if ($ws_worker->room[$data['room_id']]['student'][$members['member_id']]['type'] != $data['type']) {
                    $connection->close(Base::success('token_error', '身份验证失败(1)'));
                }
                $connection->member_id = $members['member_id'];
                $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['connection'] = $connection;
                $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['status'] = 2;
            } else {
                if ($ws_worker->room[$data['room_id']]['teacher'][$token['user_id']]['type'] != $data['type']) {
                    $connection->close(Base::success('token_error', '身份验证失败(1)'));
                }
                $connection->member_id = $token['user_id'];
                $ws_worker->room[$connection->room_id]['teacher'][$connection->member_id]['connection'] = $connection;
                $ws_worker->room[$connection->room_id]['teacher'][$connection->member_id]['status'] = 2;
            }
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
    public static function ready($connection, &$ws_worker, $db) {
        // 执行当前状态下应执行的任务
        Service::studentList($connection, $ws_worker);
        if ($connection->type == 3) {
            if ($ws_worker->room[$connection->room_id]['student'][$connection->member_id]['step'] == 3) {
                Service::resumeFace($connection, $ws_worker);
            } else {
                Service::wait($connection, $ws_worker);
            }

            // 记录操作
            $db->insert('face_log_room_active')->cols(array(
                'school_id'=>$connection->school_id,
                'year'=>$connection->school_year,
                'scene_id'=>$connection->scene_id,
                'room_id'=>$connection->room_id,
                'member_id'=>$connection->member_id,
                'type'=>$connection->type,
                'active'=>1,
                'active_time'=>date('Y-m-d H:i:s')
            ))->query();
        }
    }

    /**
     * 关闭连接
     *
     * @author yangjian
     * @date   2021-08-25
     * @return void
     */
    public static function closeConnect($connection, &$ws_worker, $db) {
        if (!empty($connection->member_id)) {
            if ($connection->type == 5) {
                $ws_worker->room[$connection->room_id]['double']['connection'] = '';
                $ws_worker->room[$connection->room_id]['double']['status'] = 1;
            } else {
                if (in_array($connection->type, [1,2])) {
                    $ws_worker->room[$connection->room_id]['teacher'][$connection->member_id]['connection'] = '';
                    $ws_worker->room[$connection->room_id]['teacher'][$connection->member_id]['status'] = 1;
                    Service::teacherList($connection, $ws_worker);
                }
                if ($connection->type == 3) {
                    $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['connection'] = '';
                    $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['status'] = 1;
                    Service::studentList($connection, $ws_worker);
                    
                    if ($ws_worker->room[$connection->room_id]['student'][$connection->member_id]['step'] == 3) {
                        $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['end_time'] = time();
                        $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['count_time'] += $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['end_time'] - $ws_worker->room[$connection->room_id][$connection->member_id]['start_time'];
                        $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['times'][] = $ws_worker->room[$connection->room_id]['student'][$connection->member_id]['start_time'].'-'.$ws_worker->room[$connection->room_id][$connection->member_id]['end_time'];
                    }

                    // 记录操作
                    $db->insert('face_log_room_active')->cols(array(
                        'school_id'=>$connection->school_id,
                        'year'=>$connection->school_year,
                        'scene_id'=>$connection->scene_id,
                        'room_id'=>$connection->room_id,
                        'member_id'=>$connection->member_id,
                        'type'=>$connection->type,
                        'active'=>1,
                        'active_time'=>date('Y-m-d H:i:s')
                    ))->query();
                }
            }
        }
    }
}