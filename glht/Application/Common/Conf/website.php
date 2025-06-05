<?php 
	return array(
		'WEB_TITLE' => 'YUN国际支付',
		'DOMAIN' => 'yunadmin.yunpay.me',
		'MODULE_ALLOW_LIST'   => array('Home','User','sysadmin','Install', 'Weixin','Pay','Cashier','Agent','Payment','Cli'),
		'URL_MODULE_MAP'  => array('sysadmin'=>'admin', 'agent'=>'user', 'user'=>'user'),
		'LOGINNAME' => 'user',
		'HOUTAINAME' => 'sysadmin',
		'API_DOMAIN' => 'api.yunpay.me',
        'NOTIFY_DOMAIN' => 'napi.yunpay.me',
        'LOG_API_URL' => 'https://log.yunpay.me',
    );
?>