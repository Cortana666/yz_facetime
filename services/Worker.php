<?php

namespace Services;

use Services\Base;

/**
 * Worker操作类
 *
 * @package   Worker
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Worker {
    /**
     * 建立关联
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $data
     * @param [type] $connection
     * @return void
     */
    public static function build_link(&$ws_worker, &$connection, $data) {
        // 初始化房间
        if (!isset($ws_worker->channel[$data['channel']])) {
            $ws_worker->channel[$data['channel']] = array();
            $ws_worker->channel[$data['channel']][1] = array();
            $ws_worker->channel[$data['channel']][2] = array();
        }

        // 个人数据关联
        $connection->user_id = $data['user_id'];
        $connection->channel = $data['channel'];
        $connection->type = $data['type'];

        // 建立user_id与wolakerman对应
        if (!isset($ws_worker->channel[$data['channel']][$data['type']])) {
            $connection->close(Base::error("login", "账号类型错误", 'list'));
        } else {
            $ws_worker->channel[$data['channel']][$data['type']][$data['user_id']] = $connection;
        }
    }

    /**
     * 广播房间学生列表
     *
     * @author yangjian
     * @date   2021-07-08
     * @param [type] $channel
     * @return void
     */
    public static function send_list($ws_worker, $db, $connection) {
        $user_ids = array();
        if ($ws_worker->channel[$connection->channel][2]) {
            $user_ids = array_keys($ws_worker->channel[$connection->channel][2]);
        }

        // 获取账号名称
        $ims_user = array();
        if ($user_ids) {
            $ims_user = $db->select('user_id,AES_DECRYPT(name, "'.Base::get_eol_key().'") as name')->from('ims_user')->where('user_id in ('.implode(',', $user_ids).')')->query();
        }

        if (!empty($ws_worker->channel[$connection->channel][1])) {
            // 广播房间内学生列表
            foreach($ws_worker->channel[$connection->channel][1] as $connect) {
                $connect->send(Base::success('list', '获取学生列表成功', '', ['list'=>$ims_user]));
            }
        }
        if (!empty($ws_worker->channel[$connection->channel][2])) {
            // 广播房间内学生列表
            foreach($ws_worker->channel[$connection->channel][2] as $connect) {
                $connect->send(Base::success('list', '获取学生列表成功', '', ['list'=>$ims_user]));
            }
        }
    }
}