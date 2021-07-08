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
    private static function get_eol_key() {
        return "eol@2021#kw";
    }

    /**
     * 获取加密辅助key
     *
     * @author yangjian
     * @date   2021-07-08
     * @return void
     */
    private static function get_eol_salt() {
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
     * 验证学生端token并返回面试token
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @return void
     */
    public static function check_student_token($db, $data) {
        if (empty($data['user_id']) || empty($data['student_token'])) {
            return false;
        }

        // $ims_user = $db->select('user_id')->from('ims_user')->where('token= :token')->bindValues(array('token'=>$data['student_token']))->row();
        // if (!$ims_user) {
        //     return false;
        // }

        // $student_token = unserialize(self::decrypt($data['student_token']));
        // if ($student_token['user_id'] != $ims_user['user_id']) {
        //     return false;
        // }
        // if (time() - $student_token['time'] > 10800) {
        //     return false;
        // }

        $face_token['user_id'] = $data['user_id'];
        $face_token['channel'] = $data['channel'];
        $face_token['time'] = time();

        return self::encrypt(serialize($face_token));
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
     * 验证面试token
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $token
     * @return void
     */
    public static function check_face_token($connection, $token = '') {
        if (!$token) {
            return false;
        }

        $token = unserialize(self::decrypt($token));

        if ($token['user_id'] != $connection->user_id) {
            return false;
        }

        if ($token['channel'] != $connection->channel) {
            return false;
        }

        if (time() - $token['time'] > 10800) {
            return false;
        }

        return true;
    }
}