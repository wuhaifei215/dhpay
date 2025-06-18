<?php
error_reporting(0);
header("Content-type: text/html; charset=utf-8");	
$mchid = "10002";  
$Md5key = "1ni0s1x77i94vusp4m0ssq0dsk3rrlu2";
$out_trade_no = date("YmdHis").mt_rand(1000,9999);    //订单号
$_POST['out_trade_no'] = $out_trade_no;
$money =  $_POST["money"];    //交易金额
$_POST['mchid'] = $mchid;
if(empty($mchid)||empty($_POST['money']) || empty($_POST['accountname']) || empty($_POST['cardnumber']) ){
		die("信息不完整！");
}
//if($_POST['extends']) {
//	$_POST['extends'] = base64_encode(json_encode($_POST['extends']));
//}

$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

$apidomain = $http_type . "api.dhpay.vip";
if (in_array($pay_bankcode, [7710, 7711, 7712])) {
    $pay_currency = 'BRL';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutBRL.html";   //巴西
} elseif ($pay_bankcode == 7713) {
    $pay_currency = 'PKR';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutPAK.html";   //巴基斯坦
}elseif (in_array($pay_bankcode, [7714, 7715, 7716,7717])) {
    $pay_currency = 'PHP';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutPHP.html";   //菲律宾
} elseif ($pay_bankcode == 7718) {
    $pay_currency = 'VND';
    $tjurl = $apidomain . "/Payment_CreateDF_payoutVND.html";   //越南
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
