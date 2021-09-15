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
     * @date   2021-08-26
     * @return string
     */
    public static function getEolKey() {
        return "eol@2021#kw";
    }

    /**
     * 获取加密辅助key
     *
     * @author yangjian
     * @date   2021-07-08
     * @return string
     */
    public static function getEolSalt() {
        return 'eol@2021#kw$salt';
    }

    /**
     * 数据加密
     *
     * @author yangjian
     * @date   2021-08-26
     * @param string $data
     * @return string
     */
    public static function encrypt($data = '') {
        return openssl_encrypt($data, 'aes-256-cfb', str_pad(static::getEolKey(), 32, "0", STR_PAD_RIGHT), 0, str_pad(static::getEolSalt(), 16, "0", STR_PAD_RIGHT));
    }

    /**
     * 数据解密
     *
     * @author yangjian
     * @date   2021-08-26
     * @param string $data
     * @return string
     */
    public static function decrypt($data = '') {
        return openssl_decrypt($data, 'aes-256-cfb', str_pad(static::getEolKey(), 32, "0", STR_PAD_RIGHT), 0, str_pad(static::getEolSalt(), 16, "0", STR_PAD_RIGHT));
    }

    /**
     * 格式化发送信息信息
     *
     * @author yangjian
     * @date   2021-08-26
     * @param string $code
     * @param string $message
     * @param array $data
     * @return string
     */
    public static function success($code = '', $message = '', $data = []) {
        $return['code'] = $code;
        $return['message'] = $message;
        $return['data'] = $data;

        return json_encode($return);
    }
}