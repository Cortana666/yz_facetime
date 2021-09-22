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
        if ((time() - $token['time'] ?? 0) > 86400) {
            $connection->close(Base::success('token_error', 'token验证失败'));
        }

        // 验证考场
        $room = $db->select('scene_id,status')->from('face_room')->where('room_id= :room_id')->bindValues(array('room_id' => $data['room_id']))->row();
        if (2 != ($room['status'] ?? '')) {
            $connection->close(Base::success('room_error', '该考场未在考试'));
        }

        // 初始化房间
        if (empty($ws_worker->room[$data['room_id']])) {
            if ($data['type'] == 5) {
                $connection->close(Base::success('room_error', '请先进入面试房间'));
            } else {
                // 获取成员信息
                $members = $db->select('id,member_id,type')->from('face_room_member')->where('room_id= :room_id')->bindValues(array('room_id' => $data['room_id']))->query();
                $members = array_column($members, null, 'member_id');

                // 初始化房间
                $ws_worker->room[$data['room_id']] = array();
                $ws_worker->room[$data['room_id']]['member'] = array();
                $ws_worker->room[$data['room_id']]['double'] = [
                    'connection'=>'',
                    'member_id'=>'',
                    'type'=>5,
                    'status'=>1,
                    'quota'=>1,
                    'step'=>1,
                    'start_time'=>'',
                    'end_time'=>'',
                    'count_time'=>0,
                    'times'=>[],
                ];
                foreach ($members as $member) {
                    $ws_worker->room[$data['room_id']]['member'][$member['id']] = [
                        'connection' => '',
                        'member_id' => $member['member_id'],
                        'type' => $member['type'],
                        'status'=>1,
                        'quota'=>1,
                        'step'=>1,
                        'start_time'=>'',
                        'end_time'=>'',
                        'count_time'=>0,
                        'times'=>[],
                    ];
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

        // 查询成员
        if (in_array($data['type'], [3,5])) {
            $students = $db->select("student_id")->from('face_student')->where("AES_DECRYPT(card_id, '".Base::getEolKey()."')= :card_id")->bindValues(array('card_id' => $token['card_id']))->query();
            $members = $db->select('id,member_id')->from('face_room_member')->where('room_id= :room_id AND type = :type AND member_id in ('.implode(',', array_column($students, 'student_id')).')')->bindValues(array('room_id' => $data['room_id'], 'type'=> 3))->row();
        } else {
            $members = $db->select('id,member_id')->from('face_room_member')->where('room_id= :room_id AND type in (1,2,4) AND member_id = :member_id')->bindValues(array('room_id' => $data['room_id'], 'member_id'=>$token['user_id']))->row();
        }

        // 初始化连接
        if ($data['type'] == 5) {
            if ($ws_worker->room[$data['room_id']]['double']['type'] != $data['type']) {
                $connection->close(Base::success('token_error', '身份验证失败(1)'));
            }
            if ($ws_worker->room[$data['room_id']]['member'][$members['id']]['step'] != 3) {
                $connection->close(Base::success('room_error', '请在开始面试后扫描二维码'));
            }
        } else {
            if ($ws_worker->room[$data['room_id']]['member'][$members['id']]['type'] != $data['type']) {
                $connection->close(Base::success('token_error', '身份验证失败(1)'));
            }
        }

        $connection->exam_id = $members['id'];
        $connection->member_id = $members['member_id'];
        $connection->school_id = $token['school_id'];
        $connection->school_year = $token['school_year'];
        $connection->scene_id = $room['scene_id'];
        $connection->room_id = $data['room_id'];
        $connection->type = $data['type'];

        if ($data['type'] == 5) {
            $ws_worker->room[$connection->room_id]['double']['connection'] = $connection;
            $ws_worker->room[$connection->room_id]['double']['status'] = 2;
        } else {
            $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['connection'] = $connection;
            $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['status'] = 2;
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
            if ($ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['step'] == 3) {
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
        if (!empty($connection->exam_id)) {
            if ($connection->type == 5) {
                $ws_worker->room[$connection->room_id]['double']['connection'] = '';
                $ws_worker->room[$connection->room_id]['double']['status'] = 1;
            } else {
                $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['connection'] = '';
                $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['status'] = 1;
                // if (in_array($connection->type, [1,2])) {
                //     Service::teacherList($connection, $ws_worker);
                // }
                if ($connection->type == 3) {
                    Service::studentList($connection, $ws_worker);
                    
                    if ($ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['step'] == 3) {
                        $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['end_time'] = time();
                        $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['count_time'] += $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['end_time'] - $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['start_time'];
                        $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['times'][] = $ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['start_time'].'-'.$ws_worker->room[$connection->room_id]['member'][$connection->exam_id]['end_time'];
                    }
                }
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