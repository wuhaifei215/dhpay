<?php
/**
 * 策略路由服务类
 * 根据金额档位自动分配支付通道
 *
 * @author 系统开发
 * @date 2026-05-29 (从 r97 迁移)
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
        $redis = new \Redis();
        $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
        $redis->auth(C('REDIS_PWD'));
        return $redis;
    }

    /**
     * 根据金额匹配策略档位，获取对应通道
     * @param int $userid 商户ID
     * @param float $amount 交易金额
     * @param int $type 1:代收 2:代付
     * @param string $outTradeNo 订单号（可选）
     * @return array|null 返回通道配置或null
     */
    public function routeChannel($userid, $amount, $type = 1, $outTradeNo = '')
    {
        // 获取商户策略配置
        $strategyConfig = $this->getMemberStrategy($userid, $type);
        if (!$strategyConfig || $strategyConfig['status'] != 1) {
            // 未配置策略或策略未启用，返回null走原有逻辑
            $this->logRoute($userid, $type, $amount, null, null, null, 'no_strategy', $outTradeNo);
            return null;
        }

        // 获取策略信息
        $strategy = $this->getStrategyInfo($strategyConfig['strategyid']);
        if (!$strategy || $strategy['status'] != 1) {
            $this->logRoute($userid, $type, $amount, $strategyConfig['strategyid'], null, null, 'strategy_disabled', $outTradeNo);
            return null;
        }

        // 查询策略的金额档位
        $tier = $this->getTierByAmount($strategyConfig['strategyid'], $amount, $type);
        if (!$tier) {
            $this->logRoute($userid, $type, $amount, $strategyConfig['strategyid'], null, null, 'no_tier_match', $outTradeNo);
            return null;
        }

        // 根据档位配置获取通道
        if ($type == 1) {
            $channel = $this->routeCollectionChannel($tier, $amount);
        } else {
            $channel = $this->routeWithdrawalChannel($tier, $amount);
        }

        // 记录日志
        if ($channel) {
            $this->logRoute($userid, $type, $amount, $strategyConfig['strategyid'], $tier['id'], $channel['id'], 'success', $outTradeNo);
        } else {
            $this->logRoute($userid, $type, $amount, $strategyConfig['strategyid'], $tier['id'], null, 'no_channel_available', $outTradeNo);
        }

        return $channel;
    }

    /**
     * 获取商户策略配置（带缓存）
     * @param int $userid 商户ID
     * @param int $type 类型
     * @return array|null
     */
    private function getMemberStrategy($userid, $type)
    {
        $cacheKey = 'MemberStrategy_' . $userid . '_' . $type;
        $cached = $this->redis->get($cacheKey);

        if ($cached) {
            $data = json_decode($cached, true);
            if (!empty($data)) {
                return $data;
            }
        }

        $data = M('MemberStrategy')
            ->where(['userid' => $userid, 'type' => $type, 'status' => 1])
            ->find();

        if ($data) {
            $this->redis->set($cacheKey, json_encode($data, JSON_UNESCAPED_UNICODE), $this->cacheExpire);
        }

        return $data ?: null;
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
    private function routeCollectionChannel($tier, $amount)
    {
        // 如果配置了权重（多通道轮询）
        if (!empty($tier['channel_config'])) {
            $channelConfig = json_decode($tier['channel_config'], true);
            if (!empty($channelConfig) && is_array($channelConfig)) {
                return $this->routeByWeight($channelConfig, $amount, 1);
            }
        }

        // 如果配置了通道ID列表
        if (!empty($tier['channel_ids'])) {
            $channelIds = explode(',', $tier['channel_ids']);
            $channelId = $channelIds[0]; // 默认取第一个
            return $this->getChannelInfo($channelId, 1);
        }

        return null;
    }

    /**
     * 代付通道路由
     * @param array $tier 档位配置
     * @param float $amount 金额
     * @return array|null
     */
    private function routeWithdrawalChannel($tier, $amount)
    {
        // 如果配置了权重（多通道轮询）
        if (!empty($tier['channel_config'])) {
            $channelConfig = json_decode($tier['channel_config'], true);
            if (!empty($channelConfig) && is_array($channelConfig)) {
                return $this->routeByWeight($channelConfig, $amount, 2);
            }
        }

        // 如果配置了通道ID列表
        if (!empty($tier['channel_ids'])) {
            $channelIds = explode(',', $tier['channel_ids']);
            $channelId = $channelIds[0];
            return $this->getPayForAnotherInfo($channelId);
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
    private function routeByWeight($channelConfig, $amount, $type)
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
            if ($channel && $this->checkChannelAvailable($channel, $amount, $type)) {
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
    private function checkChannelAvailable($channel, $amount, $type)
    {
        // 检查通道状态
        if (isset($channel['status']) && $channel['status'] != 1) {
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
    public function clearMemberStrategyCache($userid, $type = null)
    {
        if ($type !== null) {
            $this->redis->del('MemberStrategy_' . $userid . '_' . $type);
        } else {
            $this->redis->del('MemberStrategy_' . $userid . '_1');
            $this->redis->del('MemberStrategy_' . $userid . '_2');
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
    private function logRoute($userid, $type, $amount, $strategyId, $tierId, $channelId, $result, $outTradeNo = '')
    {
        // 检查是否开启日志记录
        if (C('STRATEGY_LOG_ENABLED') !== true) {
            return;
        }

        try {
            $logData = [
                'userid' => $userid,
                'type' => $type,
                'amount' => $amount,
                'strategyid' => $strategyId,
                'tier_id' => $tierId,
                'channel_id' => $channelId,
                'match_result' => $result,
                'out_trade_no' => $outTradeNo,
                'createtime' => date('Y-m-d H:i:s'),
            ];

            M('StrategyLog')->add($logData);
        } catch (\Exception $e) {
            // 日志写入失败不影响主流程
        }
    }

    /**
     * 获取商户当前策略信息
     * @param int $userid 商户ID
     * @param int $type 类型
     * @return array
     */
    public function getMemberStrategyInfo($userid, $type)
    {
        $strategyConfig = $this->getMemberStrategy($userid, $type);
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
