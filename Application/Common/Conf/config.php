<?php

return array(
    //'配置项'=>'配置值'

    'DEFAULT_MODULE'        =>  'Home',  // 默认模块
    'DEFAULT_CONTROLLER'    =>  'Index', // 默认控制器名称
    'DEFAULT_ACTION'        =>  'index', // 默认操作名称

    'URL_CASE_INSENSITIVE' => true, //URL地址不区分大小写
    // 'TMPL_FILE_DEPR'        =>  '_',   //简化模板的目录层次
    //'DEFAULT_THEME'         =>  'default',// 设置默认的模板主题
      'URL_MODEL'          => 2, //URL模式
//    'DEFAULT_V_LAYER'       =>  'Template', // 设置默认的视图层名称
//   简化目录路径
    'TMPL_PARSE_STRING'    => array(
        '__Adminlmcq__'        => __ROOT__ . '/Public/Adminlmcq',
        '__HOME__'         => __ROOT__ . '/Public/Home',
        '__PUBLIC__'       => __ROOT__ . '/Public'
    ),
    'user_id'              => 'sdhfskfllxlxxnc', // 用户sesion存储的 键名

    ###################数据库基本设置##########################
    /* 数据库设置 */
    // 'DB_TYPE'   => 'mysql', // 数据库类型
    // 'DB_HOST'              =>  'localhost', // 服务器地址
    // 'DB_NAME'              =>  bth', // 数据库名
    // 'DB_USER'              => 'root', // 用户名
    // 'DB_PWD'               => 'wsNjNfrGBfw7wbSu', // 密码
    // 'DB_PORT'   => 3306, // 端口
    // 'DB_PREFIX' => 'mf_', // 数据库表前缀 
    // 'DB_CHARSET'=> 'utf8', // 字符集
    // 'DEFAULT_THEME'    =>    '',// 设置默认的模板主题
    // 'SHOW_PAGE_TRACE'=>false,
    // 'SHOW_ERROR_MSG' =>  false,
    // 'TOKEN_ON'      =>    false,
    // 'DEFAULT_FILTER' => 'strip_tags,htmlspecialchars',
    // //'URL_MODEL'          => '2',
    // 'LANG_SWITCH_ON' => false,
    // //'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
    // 'DEFAULT_LANG' => 'zh-tw',
    // //'LANG_LIST'        => 'zh-tw', // 允许切换的语言列表 用逗号分隔
    // 'VAR_LANGUAGE'     => 'l', // 默认语言切换变量

    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'              =>  '127.0.0.1', // 服务器地址
    'DB_NAME'              =>  'city', // 数据库名
    'DB_USER'              => 'root', // 用户名
    'DB_PWD'               => 'root', // 密码
    'DB_PORT'   => 3306, // 端口
    'DB_PREFIX' => 'mf_', // 数据库表前缀 
    'DB_CHARSET'=> 'utf8', // 字符集
    'DEFAULT_THEME'    =>    '',// 设置默认的模板主题
    'SHOW_PAGE_TRACE'=>false,
    'SHOW_ERROR_MSG' =>  false,
    'TOKEN_ON'      =>    false,
    'DEFAULT_FILTER' => 'strip_tags,htmlspecialchars,trim',
    //'URL_MODEL'          => '2',
    'LANG_SWITCH_ON' => false,
    'LANG_AUTO_DETECT' => true, // 自动侦测语言 开启多语言功能后有效
    'DEFAULT_LANG' => 'zh-tw',
    'LANG_LIST'        => 'zh-tw', // 允许切换的语言列表 用逗号分隔
    'VAR_LANGUAGE'     => 'l', // 默认语言切换变量

);


