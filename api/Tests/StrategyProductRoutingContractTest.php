<?php

/**
 * 无测试框架依赖的产品策略静态契约测试。
 * 运行：php api/Tests/StrategyProductRoutingContractTest.php
 */

$root = dirname(__DIR__, 2);
$service = file_get_contents($root . '/api/Application/Common/Service/StrategyRouteService.class.php');
$payin = file_get_contents($root . '/api/Application/Pay/Controller/CreateController.class.php');
$payout = file_get_contents($root . '/api/Application/Payment/Controller/CreateDFController.class.php');
$migration = file_get_contents($root . '/api/Sql/20260905_strategy_product.sql');
$admin = file_get_contents($root . '/glht/Application/Admin/Controller/StrategyController.class.php');
$assignView = file_get_contents($root . '/glht/Application/Admin/View/Strategy/assignMember.html');

$contracts = [
    '路由入口包含产品参数' => strpos($service, 'routeChannel($userid, $productId, $amount') !== false,
    '产品维度缓存键存在' => strpos($service, "'MemberStrategy_' . \$userid . '_' . \$productId . '_' . \$type") !== false,
    '产品策略精确查询存在' => strpos($service, "'productid' => \$productId") !== false,
    '历史 NULL 产品回退存在' => strpos($service, "'productid' => ['exp', 'IS NULL']") !== false,
    '路由日志记录策略来源' => strpos($service, "'strategy_source' => \$strategySource") !== false,
    '收款传入产品 ID' => preg_match('/routeChannel\s*\([\s\S]{0,160}?\$this->memberid[\s\S]{0,160}?\$this->bankcode/', $payin) === 1,
    '代付传入产品 ID' => preg_match('/routeChannel\s*\([\s\S]{0,160}?\$this->userid[\s\S]{0,160}?\$this->bankcode/', $payout) === 1,
    '迁移建立产品唯一键' => strpos($migration, 'uk_userid_productid') !== false,
    '管理端读取产品参数' => strpos($admin, "I('post.productid'") !== false,
    '管理端按商户产品 Upsert' => strpos($admin, "['userid' => \$userid, 'productid' => \$productId]") !== false,
    '单商户支持提交多个产品' => strpos($admin, "I('post.productids/a'") !== false,
    '单商户多产品使用事务' => preg_match('/function assignMember\(\)[\s\S]{0,9000}?startTrans\(\)/', $admin) === 1,
    '产品勾选后显示对应策略' => strpos($assignView, 'name="strategyids[<{$p.id}>]"') !== false,
    '商户列表展示多个产品策略' => strpos($admin, "['strategy_configs']") !== false,
    '管理端校验商户产品开通' => strpos($admin, 'merchantHasProduct($userid, $productId, $type)') !== false,
    '管理端清理产品维度缓存' => strpos($admin, "'MemberStrategy_' . intval(\$userid) . '_' . intval(\$productId)") !== false,
    '档位通道按产品类型过滤' => strpos($admin, "'paytype' => \$product['paytype']") !== false,
    '档位通道按产品币种过滤' => strpos($admin, "'currency' => \$product['currency']") !== false,
    '档位保存校验通道归属产品' => strpos($admin, '通道不属于当前产品或已关闭') !== false,
    'Redis 故障使用空缓存降级' => strpos($service, 'new StrategyNullRedis()') !== false,
    '通道校验包含币种' => strpos($service, "\$channel['currency'] !== \$currency") !== false,
    '运行时通道校验产品类型' => strpos($service, "intval(\$channel['paytype']) !== intval(\$product['paytype'])") !== false,
];

$failed = array_keys(array_filter($contracts, function ($passed) {
    return !$passed;
}));

if ($failed) {
    fwrite(STDERR, "FAILED\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo 'OK (' . count($contracts) . " contracts)\n";
