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
}