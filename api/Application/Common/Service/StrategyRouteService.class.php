<?php
/**
 * 策略路由服务类
 * 根据金额档位自动分配支付通道
 *
 * @author 系统开发
 * @date 2026-04-10
 */

namespace Common\Service;

use Think\Model;

class StrategyRouteService
{
    /**
     * @var \Redis Redis连接
     */
    protected $redis;

    /**
     * @var int 缓存有效期（秒）
     */
    protected $cacheExpire = 300;

    /**
     * 构造函数，初始化Redis连接
     */
    public function __construct()
    {
        $this->redis = $this->connectRedis();
    }

    /**
     * 连接Redis
     * @return \Redis
     */
    private function connectRedis()
    {
        try {
            $redis = new \Redis();
            if (!$redis->connect(C('REDIS_HOST'), C('REDIS_PORT'))) {
                return new StrategyNullRedis();
            }
            if (C('REDIS_PWD') && !$redis->auth(C('REDIS_PWD'))) {
                return new StrategyNullRedis();
            }
            return $redis;
        } catch (\Exception $e) {
            return new StrategyNullRedis();
        }
    }

    /**
     * 根据金额匹配策略档位，获取对应通道
     * @param int $userid 商户ID
     * @param int $productId 产品ID
     * @param float $amount 交易金额
     * @param int $type 1:代收 2:代付
     * @param string $outTradeNo 订单号（可选）
     * @param string $currency 国家货币代码（可选，默认从商户表获取）
     * @return array|null 返回通道配置或null
     */
    public function routeChannel($userid, $productId, $amount, $type = 1, $outTradeNo = '', $currency = '')
    {
        $userid = intval($userid);
        $productId = intval($productId);
        $type = intval($type);
        if ($userid <= 0 || $productId <= 0 || !in_array($type, [1, 2], true)) {
            $this->logRoute($userid, $productId, $type, $amount, null, null, null, 'invalid_route_context', $outTradeNo, $currency, 'none');
            return null;
        }

        // 获取商户的国家货币
        if (empty($currency)) {
            $member = M('Member')->where(['id' => $userid])->find();
            $currency = isset($member['currency']) ? $member['currency'] : '';
        }

        if (!$this->validateProduct($productId, $type, $currency)) {
            $this->logRoute($userid, $productId, $type, $amount, null, null, null, 'invalid_product', $outTradeNo, $currency, 'none');
            return null;
        }
        if (!$this->validateMerchantProduct($userid, $productId, $type)) {
            $this->logRoute($userid, $productId, $type, $amount, null, null, null, 'merchant_product_disabled', $outTradeNo, $currency, 'none');
            return null;
        }

        $strategySource = 'product';
        $strategyConfig = $this->getMemberStrategyByProduct($userid, $productId, $type);
        if (!$strategyConfig) {
            $strategySource = 'legacy_fallback';
            $strategyConfig = $this->getLegacyMemberStrategy($userid, $type);
        }
        if (!$strategyConfig || $strategyConfig['status'] != 1) {
            $this->logRoute($userid, $productId, $type, $amount, null, null, null, 'no_strategy', $outTradeNo, $currency, 'none');
            return null;
        }

        // 获取策略信息，并校验国家匹配
        $strategy = $this->getStrategyInfo($strategyConfig['strategyid']);
        if (!$strategy || $strategy['status'] != 1) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], null, null, 'strategy_disabled', $outTradeNo, $currency, $strategySource);
            return null;
        }

        if (intval($strategy['type']) !== $type) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], null, null, 'type_mismatch', $outTradeNo, $currency, $strategySource);
            return null;
        }
        if ($strategySource === 'product' && intval($strategy['productid']) !== $productId) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], null, null, 'product_mismatch', $outTradeNo, $currency, $strategySource);
            return null;
        }

        // 国家校验：策略的currency必须与商户一致
        if (!empty($currency) && isset($strategy['currency']) && $strategy['currency'] !== '' && $strategy['currency'] !== $currency) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], null, null, 'currency_mismatch', $outTradeNo, $currency, $strategySource);
            return null;
        }

        // 查询策略的金额档位
        $tier = $this->getTierByAmount($strategyConfig['strategyid'], $amount, $type);
        if (!$tier) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], null, null, 'no_tier_match', $outTradeNo, $currency, $strategySource);
            return null;
        }

        // 根据档位配置获取通道
        if ($type == 1) {
            $channel = $this->routeCollectionChannel($tier, $amount, $currency);
        } else {
            $channel = $this->routeWithdrawalChannel($tier, $amount, $currency);
        }

        // 记录日志
        if ($channel) {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], $tier['id'], $channel['id'], 'success', $outTradeNo, $currency, $strategySource);
        } else {
            $this->logRoute($userid, $productId, $type, $amount, $strategyConfig['strategyid'], $tier['id'], null, 'no_channel_available', $outTradeNo, $currency, $strategySource);
        }

        return $channel;
    }

    /**
     * 旧调用方兼容入口。新代码必须调用 routeChannel 并显式传入产品 ID。
     */
    public function routeChannelLegacy($userid, $amount, $type = 1, $outTradeNo = '', $currency = '')
    {
        if (C('STRATEGY_LEGACY_PRODUCT_ID')) {
            return $this->routeChannel($userid, intval(C('STRATEGY_LEGACY_PRODUCT_ID')), $amount, $type, $outTradeNo, $currency);
        }
        return null;
    }

    /**
     * 获取商户策略配置（带缓存）
     * @param int $userid 商户ID
     * @param int $type 类型
     * @return array|null
     */
    public function getMemberStrategyByProduct($userid, $productId, $type)
    {
        $cacheKey = 'MemberStrategy_' . $userid . '_' . $productId . '_' . $type;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            if (!empty($data)) {
                return $data;
            }
        }

        $data = M('MemberStrategy')
            ->where(['userid' => $userid, 'productid' => $productId, 'status' => 1])
            ->find();

        if ($data) {
            $this->redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        return $data ?: null;
    }

    private function getLegacyMemberStrategy($userid, $type)
    {
        return M('MemberStrategy')->where([
            'userid' => $userid,
            'productid' => ['exp', 'IS NULL'],
            'type' => $type,
            'status' => 1,
        ])->order('id DESC')->find() ?: null;
    }

    public function validateProduct($productId, $type, $currency = '')
    {
        $where = ['id' => intval($productId), 'status' => 1];
        if ($currency !== '') {
            $where['currency'] = $currency;
        }
        return (bool) M('Product')->where($where)->find();
    }

    public function validateMerchantProduct($userid, $productId, $type)
    {
        $model = intval($type) === 1 ? M('ProductUser') : M('UserPayForAnother');
        return (bool) $model->where([
            'userid' => intval($userid),
            'pid' => intval($productId),
            'status' => 1,
        ])->find();
    }

    /**
     * 获取策略信息（带缓存）
     * @param int $strategyId 策略ID
     * @return array|null
     */
    private function getStrategyInfo($strategyId)
    {
        $cacheKey = 'StrategyInfo_' . $strategyId;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            if (!empty($data)) {
                return $data;
            }
        }

        $data = M('Strategy')
            ->where(['id' => $strategyId, 'status' => 1])
            ->find();

        if ($data) {
            $this->redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        return $data ?: null;
    }

    /**
     * 根据金额获取匹配的档位
     * @param int $strategyId 策略ID
     * @param float $amount 金额
     * @param int $type 类型
     * @return array|null
     */
    private function getTierByAmount($strategyId, $amount, $type)
    {
        $cacheKey = 'StrategyTier_' . $strategyId . '_' . $type;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $tiers = json_decode($cached, true);
        } else {
            $tiers = M('StrategyTier')
                ->where(['strategyid' => $strategyId, 'status' => 1])
                ->order('sort ASC')
                ->select();
            $this->redis->set($cacheKey, json_encode($tiers, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        if (empty($tiers)) {
            return null;
        }

        // 按金额范围匹配
        foreach ($tiers as $tier) {
            if ($amount >= $tier['tier_min'] && $amount <= $tier['tier_max']) {
                return $tier;
            }
        }

        return null;
    }

    /**
     * 代收通道路由
     * @param array $tier 档位配置
     * @param float $amount 金额
     * @return array|null
     */
    private function routeCollectionChannel($tier, $amount, $currency = '')
    {
        // 如果配置了权重（多通道轮询）
        if (!empty($tier['channel_config'])) {
            $channelConfig = json_decode($tier['channel_config'], true);
            if (!empty($channelConfig) && is_array($channelConfig)) {
                return $this->routeByWeight($channelConfig, $amount, 1, $currency);
            }
        }

        // 如果配置了通道ID列表
        if (!empty($tier['channel_ids'])) {
            $channelIds = explode(',', $tier['channel_ids']);
            $channelId = $channelIds[0]; // 默认取第一个
            $channel = $this->getChannelInfo($channelId, 1);
            return $this->checkChannelAvailable($channel, $amount, 1, $currency) ? $channel : null;
        }

        return null;
    }

    /**
     * 代付通道路由
     * @param array $tier 档位配置
     * @param float $amount 金额
     * @return array|null
     */
    private function routeWithdrawalChannel($tier, $amount, $currency = '')
    {
        // 如果配置了权重（多通道轮询）
        if (!empty($tier['channel_config'])) {
            $channelConfig = json_decode($tier['channel_config'], true);
            if (!empty($channelConfig) && is_array($channelConfig)) {
                return $this->routeByWeight($channelConfig, $amount, 2, $currency);
            }
        }

        // 如果配置了通道ID列表
        if (!empty($tier['channel_ids'])) {
            $channelIds = explode(',', $tier['channel_ids']);
            $channelId = $channelIds[0];
            $channel = $this->getPayForAnotherInfo($channelId);
            return $this->checkChannelAvailable($channel, $amount, 2, $currency) ? $channel : null;
        }

        return null;
    }

    /**
     * 根据权重配置路由通道
     * @param array $channelConfig 通道权重配置
     * @param float $amount 金额
     * @param int $type 类型 1:代收 2:代付
     * @return array|null
     */
    private function routeByWeight($channelConfig, $amount, $type, $currency = '')
    {
        if (empty($channelConfig) || !is_array($channelConfig)) {
            return null;
        }

        $availableChannels = [];

        foreach ($channelConfig as $item) {
            $channelId = isset($item['channel_id']) ? intval($item['channel_id']) : 0;
            $weight = isset($item['weight']) ? intval($item['weight']) : 1;

            if ($channelId <= 0 || $weight <= 0) {
                continue;
            }

            // 获取通道信息并检查可用性
            if ($type == 1) {
                $channel = $this->getChannelInfo($channelId, 1);
            } else {
                $channel = $this->getPayForAnotherInfo($channelId);
            }

            // 检查通道是否可用
            if ($channel && $this->checkChannelAvailable($channel, $amount, $type, $currency)) {
                // 根据权重添加通道到候选列表
                for ($i = 0; $i < $weight; $i++) {
                    $availableChannels[] = $channel;
                }
            }
        }

        if (empty($availableChannels)) {
            return null;
        }

        // 随机选择一个通道（加权随机）
        return $availableChannels[array_rand($availableChannels)];
    }

    /**
     * 获取代收通道信息（带缓存）
     * @param int $channelId 通道ID
     * @return array|null
     */
    private function getChannelInfo($channelId, $type = 1)
    {
        $cacheKey = 'ChannelInfo_' . $channelId;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            if (!empty($data)) {
                return $data;
            }
        }

        $data = M('Channel')
            ->where(['id' => $channelId, 'status' => 1])
            ->find();

        if ($data) {
            $this->redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        return $data ?: null;
    }

    /**
     * 获取代付通道信息（带缓存）
     * @param int $channelId 通道ID
     * @return array|null
     */
    private function getPayForAnotherInfo($channelId)
    {
        $cacheKey = 'PayForAnotherInfo_' . $channelId;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            if (!empty($data)) {
                return $data;
            }
        }

        $data = M('PayForAnother')
            ->where(['id' => $channelId, 'status' => 1])
            ->find();

        if ($data) {
            $this->redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        return $data ?: null;
    }

    /**
     * 检查通道是否可用（风控检查等）
     * @param array $channel 通道信息
     * @param float $amount 金额
     * @param int $type 类型
     * @return bool
     */
    private function checkChannelAvailable($channel, $amount, $type, $currency = '')
    {
        if (!$channel) {
            return false;
        }
        // 检查通道状态
        if (isset($channel['status']) && $channel['status'] != 1) {
            return false;
        }
        if ($currency !== '' && isset($channel['currency']) && $channel['currency'] !== '' && $channel['currency'] !== $currency) {
            return false;
        }

        // 检查金额是否在通道范围内
        if (isset($channel['min_amount']) && $amount < $channel['min_amount']) {
            return false;
        }
        if (isset($channel['max_amount']) && $amount > $channel['max_amount']) {
            return false;
        }

        // 代收通道风控检查
        if ($type == 1) {
            if (isset($channel['control_status']) && $channel['control_status'] == 1) {
                if (isset($channel['offline_status']) && $channel['offline_status'] == 0) {
                    return false; // 风控下线
                }
            }
        }

        return true;
    }

    /**
     * 清除商户策略缓存
     * @param int $userid 商户ID
     * @param int $type 类型
     */
    public function clearMemberStrategyCache($userid, $productId = null, $type = null)
    {
        if ($productId !== null && $type !== null) {
            $this->redis->del('MemberStrategy_' . $userid . '_' . $productId . '_' . $type);
        } else {
            $keys = $this->redis->keys('MemberStrategy_' . intval($userid) . '_*');
            if (!empty($keys)) {
                call_user_func_array([$this->redis, 'del'], $keys);
            }
        }
    }

    /**
     * 清除策略缓存
     * @param int $strategyId 策略ID
     * @param int $type 类型
     */
    public function clearStrategyCache($strategyId, $type)
    {
        $this->redis->del('StrategyInfo_' . $strategyId);
        $this->redis->del('StrategyTier_' . $strategyId . '_' . $type);
    }

    /**
     * 记录路由日志
     * @param int $userid 商户ID
     * @param int $type 类型
     * @param float $amount 金额
     * @param int $strategyId 策略ID
     * @param int $tierId 档位ID
     * @param int $channelId 通道ID
     * @param string $result 结果
     * @param string $outTradeNo 订单号
     */
    private function logRoute($userid, $productId, $type, $amount, $strategyId, $tierId, $channelId, $result, $outTradeNo = '', $currency = '', $strategySource = 'none')
    {
        if (C('STRATEGY_LOG_ENABLED') !== true) {
            return;
        }

        try {
            $logData = [
                'userid' => $userid,
                'productid' => $productId,
                'type' => $type,
                'amount' => $amount,
                'strategyid' => $strategyId,
                'tier_id' => $tierId,
                'channel_id' => $channelId,
                'match_result' => $result,
                'out_trade_no' => $outTradeNo,
                'currency' => $currency,
                'strategy_source' => $strategySource,
                'createtime' => date('Y-m-d H:i:s'),
            ];

            M('StrategyLog')->add($logData);
        } catch (\Exception $e) {
        }
    }

    /**
     * 获取商户当前策略信息
     * @param int $userid 商户ID
     * @param int $type 类型
     * @return array
     */
    public function getMemberStrategyInfo($userid, $productId, $type)
    {
        $strategyConfig = $this->getMemberStrategyByProduct($userid, $productId, $type);
        if (!$strategyConfig) {
            return ['has_strategy' => false, 'strategy' => null, 'tiers' => []];
        }

        $strategy = $this->getStrategyInfo($strategyConfig['strategyid']);
        if (!$strategy) {
            return ['has_strategy' => false, 'strategy' => null, 'tiers' => []];
        }

        $tiers = M('StrategyTier')
            ->where(['strategyid' => $strategy['id'], 'status' => 1])
            ->order('sort ASC')
            ->select();

        return [
            'has_strategy' => true,
            'strategy' => $strategy,
            'tiers' => $tiers,
            'member_config' => $strategyConfig,
        ];
    }
}

/** Redis 不可用时禁用缓存，路由继续访问数据库。 */
class StrategyNullRedis
{
    public function get($key) { return false; }
    public function set($key, $value, $expire = null) { return false; }
    public function del($key) { return 0; }
    public function keys($pattern) { return []; }
}
