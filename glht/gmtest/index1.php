<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");
$pay_memberid = "10002";//商户ID
$pay_orderid = $_POST["orderid"];    //订单号
$pay_amount = $_POST["amount"];    //交易金额
$pay_bankcode = $_POST["channel"];   //银行编码
if (empty($pay_memberid) || empty($pay_amount) || empty($pay_bankcode)) {
    die("信息不完整！");
}
$pay_applydate = date("Y-m-d H:i:s");  //订单时间
$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
$pay_notifyurl = $http_type . "pglht.i8pay.com/gmtest/server.php";   //服务端返回地址
$pay_callbackurl = $http_type . "pglht.i8pay.com/gmtest/page.php";  //页面跳转返回地址
$Md5key = "1ni0s1x77i94vusp4m0ssq0dsk3rrlu2";   //密钥

$apidomain = $http_type . "papi.i8pay.com";
if (in_array($pay_bankcode, [7710, 7711, 7712])) {
    $pay_currency = 'BRL';
    $tjurl = $apidomain . "/Pay_Create_payinBRL.html";   //巴西
} elseif (in_array($pay_bankcode, [7713, 7719, 7720, 7721, 7722])) {
    $pay_currency = 'PKR';
    $tjurl = $apidomain . "/Pay_Create_payinPAK.html";   //巴基斯坦
}elseif (in_array($pay_bankcode, [7714, 7715, 7716,7717])) {
    $pay_currency = 'PHP';
    $tjurl = $apidomain . "/Pay_Create_payinPHP.html";   //菲律宾
} elseif (in_array($pay_bankcode, [7723, 7724, 7725, 7726, 7727, 7728, 7729])) {
    $pay_currency = 'VND';
    $tjurl = $apidomain . "/Pay_Create_payinVND.html";   //越南
}elseif (in_array($pay_bankcode, [7730, 7731])) {
    $pay_currency = 'IDR‌';
    $tjurl = $apidomain . "/Pay_Create_payinIDR.html";   //印尼
}

//扫码
$native = array(
    "pay_memberid" => $pay_memberid,
    "pay_orderid" => $pay_orderid,
    "pay_amount" => $pay_amount,
    "pay_applydate" => $pay_applydate,
    "pay_bankcode" => $pay_bankcode,
    "pay_notifyurl" => $pay_notifyurl,
    "pay_callbackurl" => $pay_callbackurl,
    "pay_currency" => $pay_currency,
);
ksort($native);
$md5str = "";
foreach ($native as $key => $val) {
    $md5str = $md5str . $key . "=" . $val . "&";
}
//echo($md5str . "key=" . $Md5key);
$sign = strtoupper(md5($md5str . "key=" . $Md5key));
$native["pay_md5sign"] = $sign;
$native['pay_attach'] = "1234|456";
$native['pay_productname'] = 'Vip基础服务';
$native['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];

if(in_array($pay_bankcode,[7713, 7719, 7720, 7721, 7722])){
    $native['pay_extends'] = base64_encode(json_encode([
        'cnic' => '42101-1234567-8',
        'phone' => '03488161552',
    ]));
}
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
                foreach ($native as $key => $val) {
                    echo '<input type="hidden" name="' . $key . '" value="' . $val . '">';
                }
                ?>
                <button type="submit" style='display:none;'></button>
            </form>
        </div>
    </div>
</div>
<script>
    document.forms['payform'].submit();
</script>
</body>
</html>
