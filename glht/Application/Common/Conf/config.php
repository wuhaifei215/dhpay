<?php
return array(
    /* 系统数据加密设置 */
    'DATA_AUTH_KEY' => 'SFY!+9hFQs?sb46LMV).X0W@oAQ(5lVYt=x7awE6d', //默认数据加密KEY
    'SESSION_EXPIRE' => 7200,
    'COOKIE_EXPIRE' => 7200,
    'COOKIE_SECURE' => false,
    'COOKIE_HTTPONLY' => true,
    'LOAD_EXT_CONFIG' => 'website,db,tags,route,disable,version,paytype,merchants,planning,additional,deploy',
    'DEFAULT_MODULE' => 'Home',
    /* 全局过滤配置 */
    'DEFAULT_FILTER' =>  'strip_tags,htmlspecialchars',
    'MODULE_DENY_LIST'=>  array('Common','Runtime'),
    /* URL配置 */
    'URL_CASE_INSENSITIVE' => false, //默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'            => 2, //URL模式
    'URL_PATHINFO_DEPR' => '_', //PATHINFO URL分割符

    //默认错误跳转对应的模板文件
    // 'TMPL_ACTION_ERROR' => THINK_PATH . 'Tpl/dispatch_jump.tpl',
    //默认成功跳转对应的模板文件
    // 'TMPL_ACTION_SUCCESS' => THINK_PATH . 'Tpl/dispatch_jump.tpl',

    /* 模板引擎设置 */
    'TMPL_TEMPLATE_SUFFIX' => '.html',
    //'TMPL_EXCEPTION_FILE'   =>  APP_PATH . 'Tpl/think_exception.tpl',// 异常页面的模板文件

    'URL_HTML_SUFFIX' => 'html',
    'TOKEN_ON'      =>    true,
    'TMPL_L_DELIM' => '<{',
    'TMPL_R_DELIM' => '}>',
    'SHOW_PAGE_TRACE'=>false,
    'INVITECODE' => 6,//验证码的长度
    'user'=>'user',//普通用户url
    'agent'=>'agent',//代理url
    'imageDriver'=>'gd',//二维码画图 Supported: "gd", "imagick"
    'google_discrepancy'=>1,//谷歌验证码容差时间 如果这里是2 那么就是 2* 30 sec 一分钟.
    'DEFAULT_COUNTRY' =>  'BRL',
//    'DEFAULT_TIMEZONE' => 'America/Belem'
) ;
?>