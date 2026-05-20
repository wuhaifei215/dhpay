<?php
/* *
 * 功能：代付调试入口页面
 */

// 加载 .env 配置
$envFile = dirname(__FILE__) . '/../.env';
$env = array();
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }
    }
}

$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$glht_domain = isset($env['DOMAIN']) ? $env['DOMAIN'] : 'pglht.i8pay.com';
$default_notifyurl = $http_type . $glht_domain . "/dhfpay/notify.php";
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head runat="server">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>代付申请</title>
	<link rel="stylesheet" type="text/css" href="df.css">
	<script type="text/javascript" src="https://cdn.bootcss.com/jquery/1.12.4/jquery.min.js"></script>
</head>
<body>
   <div class="container">
	   <div class="header">
		   <h3>代付申请</h3>
	   </div>

	<div class="main">
		 <form target="_blank" method="post" action="dodf.php">
			<ul>

				<li>
					<label>金额</label>
					<input type="text" name="money" value="100" />
				</li>
				<li>
					<label>通道编码</label>
					<input type="text" name="bankcode" value="7710" />
				</li>
                <li>
                    <label>开户行</label>
                    <input type="text" name="bankname" value="" />
                </li>
                <li>
                    <label>支行</label>
                    <input type="text" name="subbranch" value="" />
                </li>
				<li>
					<label>开户名</label>
					<input type="text" name="accountname" value="" />
				</li>
				<li>
					<label>银行卡号</label>
					<input type="text" name="cardnumber" value="" />
				</li>
				<li>
					<label>备注</label>
					<input type="text" name="extends" value="" />
				</li>
				<li>
					<label>回调地址</label>
					<input type="text" name="notifyurl" value="<?php echo $default_notifyurl; ?>"  />
				</li>
				<li style="margin-top: 50px">
					<label></label>
					<button type="submit">提交</button>
				</li>
             </ul>
		</form>
	  </div>
    </div>
  </body>
</html>
