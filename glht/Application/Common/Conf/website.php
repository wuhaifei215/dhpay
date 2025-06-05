<?php 
	return array(
		'WEB_TITLE' => 'DH国际支付',
		'DOMAIN' => 'hx.dhpay.vip',
		'MODULE_ALLOW_LIST'   => array('Home','User','hxadmin','Install', 'Weixin','Pay','Cashier','Agent','Payment','Cli'),
		'URL_MODULE_MAP'  => array('hxadmin'=>'admin', 'agent'=>'user', 'user'=>'user'),
		'LOGINNAME' => 'user',
		'HOUTAINAME' => 'sysadmin',
		'API_DOMAIN' => 'api.dhpay.vip',
        'NOTIFY_DOMAIN' => 'napi.dhpay.vip',
        'LOG_API_URL' => 'http://log.dhpay.vip',
    );
?>