<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");

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

$mchid = "10002";
$Md5key = "1ni0s1x77i94vusp4m0ssq0dsk3rrlu2";
$out_trade_no = date("YmdHis").mt_rand(1000,9999);    //订单号
$_POST['out_trade_no'] = $out_trade_no;
$money =  $_POST["money"];    //交易金额
$_POST['mchid'] = $mchid;
if(empty($mchid)||empty($_POST['money']) || empty($_POST['accountname']) || empty($_POST['cardnumber']) ){
		die("信息不完整！");
}


$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

$api_domain = isset($env['API_DOMAIN']) ? $env['API_DOMAIN'] : 'papi.i8pay.com';

$apidomain = $http_type . $api_domain;
if (in_array($_POST['bankcode'], [7710, 7711, 7712])) {
    $pay_currency = 'BRL';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutBRL.html";   //巴西
} elseif ($_POST['bankcode'] == 7713) {
    if($_POST['extends']) {
        $_POST['extends'] = base64_encode($_POST['extends']);
    }
    $pay_currency = 'PKR';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutPAK.html";   //巴基斯坦
}elseif (in_array($_POST['bankcode'], [7714, 7715, 7716,7717])) {
    $pay_currency = 'PHP';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutPHP.html";   //菲律宾
} elseif (in_array($_POST['bankcode'], [7723, 7724, 7725, 7726, 7727, 7728, 7729])) {
    $pay_currency = 'VND';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutVN.html";   //越南
} elseif (in_array($_POST['bankcode'], [7730, 7731])) {
    $pay_currency = 'IDR';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutIDR.html";   //越南
} elseif (in_array($_POST['bankcode'], [7732, 7733, 7734])) {
    $pay_currency = 'INR';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutIND.html";   //印度
} elseif (in_array($_POST['bankcode'], [7735, 7736, 7737])) {
    $pay_currency = 'BDT';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutBD.html";   //印度
}

$_POST['currency'] = $pay_currency;

ksort($_POST);
//var_dump($_POST);die;
$md5str = "";
foreach ($_POST as $key => $val) {
    $md5str = $md5str . $key . "=" . $val . "&";

}
$sign = strtoupper(md5($md5str . "key=" . $Md5key));
$param = $_POST;
$param["pay_md5sign"] = $sign;
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>

</head>
<body>
<div class="container">
    <div class="row" style="margin:15px;0;">
        <div class="col-md-12">
            <form class="form-inline" id="payform" method="post" action="<?php echo $tjurl; ?>">
                <?php
                foreach ($param as $key => $val) {
                    echo '<input type="hidden" name="' . $key . '" value="' . $val . '">';
                }
                ?>
                <button type="submit" style='display:none;' ></button>
            </form>
        </div>
    </div>
</div>
<script>
   document.forms['payform'].submit();
</script>
</body>
</html>
