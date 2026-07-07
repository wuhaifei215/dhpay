<?php
$pay_orderid = 'E' . date("YmdHis") . rand(10000, 99999);    //订单号
$pay_amount = "0.01";    //交易金额
$product_name = "Vip基础服务";
?>

<!DOCTYPE html>
<html>
<head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0" name="viewport">
    <title>收银台</title>
    <link href="./demo/css/Reset.css" rel="stylesheet" type="text/css">
    <script src="./demo/js/jquery-1.11.3.min.js"></script>
    <link href="./demo/css/main12.css" rel="stylesheet" type="text/css">
    <style>
        .pay_li input {
            display: none;
        }

        .immediate_pay {
            border: none;
        }

        .PayMethod12 {
            min-height: 150px;
        }

        @media screen and (max-width: 700px) {
            .PayMethod12 {
                padding-top: 0;
            }

            .order-amount12 {
                margin-bottom: 0;
            }

            .order-amount12, .PayMethod12 {
                padding-left: 15px;
                padding-right: 15px;
            }
        }

        .order-amount12-right input {
            border: 1px solid #efefef;
            width: 6em;
            padding: 5px 20px;
            font-size: 15px;
            text-indent: 0.5em;
            line-height: 1.8em;
        }
        .PayMethod12 ul li {
            margin: 20px;
        }



    </style>
    <script>
        var lastClickTime;
        var orderNo = "15248148988132090444";
        $(function () {
            $('.PayMethod12 ul li').each(function (index, element) {
//                $('.PayMethod12 ul li').eq(5 * index + 4).css('margin-right', '0')    //第5个right清空
            });

            //支付方式选择
            $('.PayMethod12 ul li').click(function (e) {
                $(this).addClass('active').siblings().removeClass('active');
            });

            $(".pay_li").click(function () {
                $(".pay_li").removeClass("active");
                $(this).addClass("active");
            });
            //点击立即支付按钮
            $(".immediate_pay").click(function () {
                //判断用户是否选择了支付渠道
                if (!$(".pay_li").hasClass("active")) {
                    message_show("请选择支付功能");
                    return false;
                }
                //获取选择的支付渠道的li
                var payli = $(".pay_li[class='pay_li active']");
                if (payli[0]) {
                    prepay(payli.attr("data_power_id"), payli.attr("data_product_id"));
                } else {
                    message_show("请重新选择支付功能");
                }

            });


            $('.mt_agree').click(function (e) {
                $('.mt_agree').fadeOut(300);
            });

            $('.mt_agree_main').click(function (e) {
                return false;
            });

            //弹窗
            // 		$('.pay_sure12').click(function(e) {
            // 			$(this).fadeOut();
            // 		});

            $('.pay_sure12-main').click(function (e) {
                //e. stopPropagation();
                return false;
            });
        });

    </script>

    <script>
        if (/Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent)) {
            //window.location.href = "mobile.php";
        } else {

        }
    </script>
</head>
<body style="background-color:#f9f9f9">
<form action="index1.php" method="post" autocomplete="off">
    <!--弹窗开始-->
    <div class="pay_sure12">
        <div class="pay_sure12-main">
            <h2>支付确认</h2>
            <h3 class="h3-01">请在新打开的页面进行支付！<br><strong>支付完成前请不要关闭此窗口。</strong></h3>
            <div class="pay_sure12-btngroup">
                <a class="immediate_button immediate_payComplate" onclick="callback_pc();">已完成支付</a>
                <a class="immediate_button immediate_payChange" onclick="hide();">更换支付方式</a>
            </div>
            <p>支付遇到问题？请联系 <span class="f12 blue">支付</span> 客服获得帮助。</p>
        </div>
    </div>
    <!--弹窗结束-->
    <!--导航-->
    <div class="w100 navBD12">
        <div class="w1080 nav12">
            <div class="nav12-left">
                <span class="shouyintai"></span>
            </div>
            <div class="nav12-right">
            </div>
        </div>
    </div>
    <!--订单金额-->
    <div class="w1080 order-amount12" style="border-radius: 1em;">
        <ul class="order-amount12-left">
            <li>
                <span>商品名称：</span>
                <span><?php echo $product_name; ?></span>
            </li>
            <li>
                <span>订单编号：</span>
                <span><?php echo $pay_orderid; ?></span>
            </li>
        </ul>
        <div class="order-amount12-right">
            <span>订单金额：</span>
            <strong><input type="text" name="amount" value="1"></strong>
            <span>元</span>
        </div>
    </div>
    <!--支付方式-->

    <input type="hidden" name="orderid" value="<?php echo $pay_orderid; ?>">

    <div class="w1080 PayMethod12" style="border-radius: 1em;">
        <div class="row">
            <h2>支付方式:</h2>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="BRLH5">
                    <li class="pay_li active" data_power_id="3000000001" data_product_id="3000000001">
                        <input value="7710" checked="checked" name="channel" id="BRLH5" type="radio">
                        <span>巴西H5</span>
                    </li>
                </label>
                <label for="BRLQR">
                    <li class="pay_li" data_power_id="3000000002" data_product_id="3000000002">
                        <input value="7711" name="channel" id="BRLQR" type="radio">
                        <span>巴西QR</span>
                    </li>
                </label>
                <label for="BRLH5CPF">
                    <li class="pay_li" data_power_id="3000000003" data_product_id="3000000003">
                        <input value="7712" name="channel" id="BRLH5CPF" type="radio">
                        <span>巴西H5--CPF</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="PKR">
                    <li class="pay_li" data_power_id="3000000004" data_product_id="3000000004">
                        <input value="7713" name="channel" id="PKR" type="radio">
                        <span>巴基斯坦PK</span>
                    </li>
                </label>
                <label for="PKRJZ">
                    <li class="pay_li" data_power_id="3000000004" data_product_id="3000000004">
                        <input value="7719" name="channel" id="PKRJZ" type="radio">
                        <span>jazzcash</span>
                    </li>
                </label>
                <label for="PKREP">
                    <li class="pay_li" data_power_id="3000000004" data_product_id="3000000004">
                        <input value="7720" name="channel" id="PKREP" type="radio">
                        <span>easypaisa</span>
                    </li>
                </label>
                <label for="PKRJZz">
                    <li class="pay_li" data_power_id="3000000004" data_product_id="3000000004">
                        <input value="7721" name="channel" id="PKRJZz" type="radio">
                        <span>jazzcash-直连</span>
                    </li>
                </label>
                <label for="PKREPz">
                    <li class="pay_li" data_power_id="3000000004" data_product_id="3000000004">
                        <input value="7722" name="channel" id="PKREPz" type="radio">
                        <span>easypaisa-直连</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="GcashWap2">
                    <li class="pay_li" data_power_id="3000000005" data_product_id="3000000005">
                        <input value="7714" name="channel" id="GcashWap2" type="radio">
                        <span>Gcash伪原生</span>
                    </li>
                </label>
                <label for="GcashScan">
                    <li class="pay_li" data_power_id="3000000006" data_product_id="3000000006">
                        <input value="7715" name="channel" id="GcashScan" type="radio">
                        <span>Gcash(QRPH)</span>
                    </li>
                </label>
                <label for="Maya">
                    <li class="pay_li" data_power_id="3000000007" data_product_id="3000000007">
                        <input value="7716" name="channel" id="Maya" type="radio">
                        <span>Maya</span>
                    </li>
                </label>
                <label for="GcashWap">
                    <li class="pay_li" data_power_id="3000000008" data_product_id="3000000008">
                        <input value="7717" name="channel" id="GcashWap" type="radio">
                        <span>Gcash原生</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="ZALO">
                    <li class="pay_li" data_power_id="3000000009" data_product_id="3000000009">
                        <input value="7723" name="channel" id="ZALO" type="radio">
                        <span>越南-zalo-扫码</span>
                    </li>
                </label>
                <label for="MOMO">
                    <li class="pay_li" data_power_id="3000000010" data_product_id="3000000010">
                        <input value="7724" name="channel" id="MOMO" type="radio">
                        <span>越南-momo-扫码</span>
                    </li>
                </label>
                <label for="VIETTEL">
                    <li class="pay_li" data_power_id="3000000011" data_product_id="3000000011">
                        <input value="7725" name="channel" id="VIETTEL" type="radio">
                        <span>越南-viettelpay-扫码</span>
                    </li>
                </label>
                <label for="VN-BANK">
                    <li class="pay_li" data_power_id="3000000012" data_product_id="3000000012">
                        <input value="7726" name="channel" id="VN-BANK" type="radio">
                        <span>越南-银行卡</span>
                    </li>
                </label>
                <label for="VN-DIRECT">
                    <li class="pay_li" data_power_id="3000000013" data_product_id="3000000013">
                        <input value="7727" name="channel" id="VN-DIRECT" type="radio">
                        <span>越南-银行卡直连</span>
                    </li>
                </label>
                <label for="VN-BANK-QR">
                    <li class="pay_li" data_power_id="3000000014" data_product_id="3000000014">
                        <input value="7728" name="channel" id="VN-BANK-QR" type="radio">
                        <span>越南-银行卡-扫码</span>
                    </li>
                </label>
                <label for="VN-PERPAID">
                    <li class="pay_li" data_power_id="3000000015" data_product_id="3000000015">
                        <input value="7729" name="channel" id="VN-PERPAID" type="radio">
                        <span>越南-点卡</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="IdrQris">
                    <li class="pay_li" data_power_id="3000000016" data_product_id="3000000016">
                        <input value="7730" name="channel" id="IdrQris" type="radio">
                        <span>印尼Qris</span>
                    </li>
                </label>
                <label for="IdrDana">
                    <li class="pay_li" data_power_id="3000000017" data_product_id="3000000017">
                        <input value="7731" name="channel" id="IdrDana" type="radio">
                        <span>印尼Dana</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="InrUPI">
                    <li class="pay_li" data_power_id="3000000018" data_product_id="3000000018">
                        <input value="7732" name="channel" id="InrUPI" type="radio">
                        <span>印度UPI</span>
                    </li>
                </label>
                <label for="InrWap">
                    <li class="pay_li" data_power_id="3000000019" data_product_id="3000000019">
                        <input value="7733" name="channel" id="InrWap" type="radio">
                        <span>印度原生</span>
                    </li>
                </label>
                <label for="IdrWake">
                    <li class="pay_li" data_power_id="3000000020" data_product_id="3000000020">
                        <input value="7734" name="channel" id="IdrWake" type="radio">
                        <span>印度唤醒</span>
                    </li>
                </label>
            </ul>
            <ul style="border-bottom: 1px dashed #000; ">
                <label for="BDBKash">
                    <li class="pay_li" data_power_id="3000000018" data_product_id="3000000018">
                        <input value="7735" name="channel" id="BDBKash" type="radio">
                        <span>孟加拉-BKash</span>
                    </li>
                </label>
                <label for="BDNagad">
                    <li class="pay_li" data_power_id="3000000019" data_product_id="3000000019">
                        <input value="7736" name="channel" id="BDNagad" type="radio">
                        <span>孟加拉-Nagad</span>
                    </li>
                </label>
                <label for="BDRocket">
                    <li class="pay_li" data_power_id="3000000020" data_product_id="3000000020">
                        <input value="7737" name="channel" id="BDRocket" type="radio">
                        <span>孟加拉-Rocket</span>
                    </li>
                </label>
            </ul>
        </div>
    </div>
    <!--立即支付-->
    <div class="w1080 immediate-pay12"
         style="border-radius: 1em; padding-top:1em; padding-bottom: 1em;padding-right: 1em;">
        <div class="immediate-pay12-right">
            <!--        <span>需支付：<strong>0.01</strong>元</span>-->

            <button type="submit" class="immediate_pay">立即支付</button>
        </div>
    </div>
    <div class="mt_agree">
        <div class="mt_agree_main">
            <h2>提示信息</h2>
            <p id="errorContent" style="text-align:center;line-height:36px;"></p>
            <a class="close_btn" onclick="message_hide()">确定</a>
        </div>
    </div>
    <!--底部-->
    <div class="w1080 footer12">
        <p>All Rights Reserved. 2017-2018</p>


    </div>


    <script type="text/javascript">
        function message_show(message) {
            $("#errorContent").html(message);
            $('.mt_agree').fadeIn(300);
        }

        function message_hide() {
            $('.mt_agree').fadeOut(300);
        }

    </script>
</form>

</body>
</html>