<?php

namespace Services;

/**
 * 基本操作类
 *
 * @package   Base
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Base {
    /**
     * 获取加密key
     *
     * @author yangjian
     * @date   2021-07-08
     * @return void
     */
    public static function get_eol_key() {
        return "eol@2021#kw";
    }

    /**
     * 获取加密辅助key
     *
     * @author yangjian
     * @date   2021-07-08
     * @return void
     */
    public static function get_eol_salt() {
        return 'eol@2021#kw$salt';
    }

    /**
     * 数据加密
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function encrypt($data) {
        return openssl_encrypt($data, 'aes-256-cfb', self::get_eol_key(), 0, self::get_eol_salt());
    }

    /**
     * 数据解密
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function decrypt($data) {
        return openssl_decrypt($data, 'aes-256-cfb', self::get_eol_key(), 0, self::get_eol_salt());
    }

    /**
     * 返回错误信息
     *
     * @author yangjian
     * @date   2021-07-08
     * @param string $send_type
     * @param string $message
     * @param string $url
     * @param array $data
     * @return void
     */
    public static function error($send_type = '', $message = '', $url = '', $data = []) {
        $return['status'] = 2;
        $return['send_type'] = $send_type;
        $return['message'] = $message;
        $return['url'] = $url;
        $return['data'] = $data;

        return json_encode($return);
    }

    /**
     * 返回成功信息
     *
     * @author yangjian
     * @date   2021-07-08
     * @param string $send_type
     * @param string $message
     * @param string $url
     * @param array $data
     * @return void
     */
    public static function success($send_type = '', $message = '', $url = '', $data = []) {
        $return['status'] = 1;
        $return['send_type'] = $send_type;
        $return['message'] = $message;
        $return['url'] = $url;
        $return['data'] = $data;

        return json_encode($return);
    }

    /**
     * 验证Token
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function s_check_token(&$connection, $data) {
        // $user_token = unserialize(Base::decrypt($data['user_token']));
        // if (time() - $user_token['time'] > 10800) {
        //     $connection->close(Base::error('cLeave', "Token验证失败，请重新进入考场"));
        // }

        $connection->user_id = $data['user_id'];
        $connection->room_id = $data['room_id'];
        $connection->type = $data['type'];
        $connection->face_status = $data['face_status'];
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
            $ws_worker->room[$connection->room_id] = array();

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
                $teachers = $db->select('user_id')->from('face_teacher')->where('user_id in ('.implode(',', array_keys($teacher_ids)).')')->query();
                foreach ($teachers as $key => $value) {
                    $ws_worker->room[$connection->room_id]['user_id'] = [
                        'connection' => '',
                        'type' => $teacher_ids[$value['user_id']]
                    ];
                }
            }
            if ($student_ids) {
                $students = $db->select('user_id,name,card_id,bk_college,bk_special')->from('face_student')->where('student_id in ('.implode(',', $student_ids).')')->query();
                foreach ($students as $key => $value) {
                    $ws_worker->room[$connection->room_id]['user_id'] = [
                        'connection' => '',
                        'type' => 2,
                        'status' => '1',
                        'step' => '1',
                        'name'=> $value['name'],
                        'card_id'=> $value['card_id'],
                        'face_status'=> '',
                        'bk_college'=> $value['bk_college'],
                        'bk_special'=> $value['bk_special'],
                    ];
                }
            }
        }
    }

    /**
     * 设置链接
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function s_set_connection(&$ws_worker, $connection) {
        $ws_worker->room[$connection->user_id][$connection->user_id]['connection'] = $connection;
        $ws_worker->room[$connection->user_id][$connection->user_id]['status'] = 2;
        $ws_worker->room[$connection->user_id][$connection->user_id]['face_status'] = $connection->face_status;
    }
}