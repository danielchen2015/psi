<?php
return array(
    //'配置项'=>'配置值'
    'type' => 'mysql',     // 数据库类型
    'hostname' => 'localhost', // 服务器地址
    'database' => 'psi',          // 数据库名
    'username' => 'root',      // 用户名
    'password' => 'daniel',          // 密码
    'hostport' => '3306',        // 端口
    'dsn' => '', //
    'params' => array(), // 数据库连接参数
    'charset' => 'utf8',      // 数据库编码默认采用utf8
    'prefix' => '',    // 数据库表前缀
    'debug' => false, // 数据库调试模式
    'deploy' => 0, // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'rw_separate' => false,       // 数据库读写是否分离 主从式有效
    'master_num' => 1, // 读写分离后 主服务器数量
    'slave_no' => '', // 指定从服务器序号
    'db_like_fields' => '',


);