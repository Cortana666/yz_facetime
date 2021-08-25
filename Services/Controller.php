<?php

namespace Services;

/**
 * 监察员类
 *
 * @package   Controller
 * @author    yangjian
 * @date      2021-07-08
 * @copyright 中教智网
 * @license   http://www.eol.cn
 * @link      http://www.eol.cn
 * @since     Version 2.0
 */
class Controller {
    // /**
    //  * 发送面试列表
    //  *
    //  * @author yangjian
    //  * @date   2021-07-23
    //  * @return void
    //  */
    // public static function sendList(&$ws_worker, $connection, $data, $self = false) {
    //     foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
    //         if ($value['type'] != 3) {
    //             $list_info[] = $value;
    //         }
    //     }

    //     foreach ($ws_worker->room[$connection->room_id]['members'] as $key => $value) {
    //         if ($value['type'] == 3) {
    //             if (self) {
    //                 if ($connection->user_id == $key) {
    //                     $value['connection']->send(Base::success('list', '面试列表', $list_info));
    //                     break;
    //                 }
    //             } else {
    //                 $value['connection']->send(Base::success('list', '面试列表', $list_info));
    //             }
    //         }
    //     }
    // }
}