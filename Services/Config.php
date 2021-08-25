<?php

namespace Services;

class Config {
    /**
     * websocket信息
     *
     * @var string
     */
    public static $wsPort = '2000';
    public static $wsCount = 1;

    /**
     * 数据库信息
     *
     * @var string
     */
    public static $dbHost = '82.156.126.93';
    public static $dbPort = '3306';
    public static $dbUser = 'remote';
    public static $dbPassword = 'Qwer1234;';
    public static $dbName = 'yz_kaowu';

    /**
     * 心跳信息
     *
     * @var integer
     */
    public static $heartTime = 2;
    public static $heartOutTime = 30;
}