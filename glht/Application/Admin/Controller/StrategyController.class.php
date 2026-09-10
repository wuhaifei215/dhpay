<?php
/**
 * 策略管理控制器
 * 用于配置金额档位通道路由策略
 *
 * @author 系统开发
 * @date 2026-04-10
 */

namespace Admin\Controller;

use Org\Net\UserLogService;
use Think\Page;

class StrategyController extends BaseController
{
    /**
     * 策略类型映射
     */
    protected $typeMap = [
        1 => '代收',
        2 => '代付',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->assign("Public", MODULE_NAME);
        $this->assign('typeMap', $this->typeMap);
    }

    /**
     * 代收策略列表（菜单入口）
     */
    public function collection()
    {
        $this->redirect('index', ['type' => 1]);
    }

    /**
     * 代付策略列表（菜单入口）
     */
    public function withdrawal()
    {
        $this->redirect('index', ['type' => 2]);
    }

    /**
     * 策略列表
     */
    public function index()
    {
        UserLogService::HTwrite(1, '查看策略列表', '查看策略列表');

        $type = I('get.type', 1, 'intval');
        $status = I('get.status', '');
        $currency = I('get.currency', C('DEFAULT_COUNTRY'), 'trim,string,strip_tags,htmlspecialchars');
        $productId = I('get.product_id', 0, 'intval');
        $typename = isset($this->typeMap[$type]) ? $this->typeMap[$type] : '代收';

        $where = ['type' => $type];
        if ($status !== '') {
            $where['status'] = $status;
        }
        if ($currency !== '') {
            $where['currency'] = $currency;
        }
        if ($productId > 0) {
            $where['productid'] = $productId;
        }

        // 国家列表
        $country = M('Country')->where(['status' => 1])->select();
        $this->assign('country', $country);
        $this->assign('currency', $currency);

        $count = M('Strategy')->where($where)->count();
        $size = 15;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $Page = new Page($count, $rows);
        $list = M('Strategy')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();

        // 获取每个策略的档位数量
        foreach ($list as $k => $v) {
            $list[$k]['product'] = $v['productid'] ? M('Product')->field('id,name,code')->find($v['productid']) : null;
            $tierCount = M('StrategyTier')->where(['strategyid' => $v['id']])->count();
            $list[$k]['tier_count'] = $tierCount;

            // 获取使用该策略的商户数量
            $memberCount = M('MemberStrategy')->where(['strategyid' => $v['id'], 'type' => $v['type']])->count();
            $list[$k]['member_count'] = $memberCount;
        }

        $this->assign('type', $type);
        $this->assign('typename', $typename);
        $this->assign('status', $status);
        $this->assign('productId', $productId);
        $this->assign('products', M('Product')->where(['status' => 1])->field('id,name,code,currency')->order('id DESC')->select());
        $this->assign('rows', $rows);
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 添加/编辑策略
     */
    public function edit()
    {
        // 获取国家列表
        $country = M('Country')->where(['status' => 1])->select();
        $this->assign('country', $country);

        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            $name = I('post.name', '', 'trim');
            $productId = I('post.productid', 0, 'intval');
            $type = I('post.type', 1, 'intval');
            $status = I('post.status', 1, 'intval');

            if (empty($name)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略名称不能为空']);
            }
            $product = M('Product')->where(['id' => $productId, 'status' => 1])->find();
            if (!$product) {
                $this->ajaxReturn(['status' => 0, 'msg' => '请选择有效产品']);
            }
            $currency = $product['currency'];

            $data = [
                'name' => $name,
                'productid' => $productId,
                'currency' => $currency,
                'type' => $type,
                'status' => $status,
            ];

            if ($id > 0) {
                $oldStrategy = M('Strategy')->find($id);
                if (!$oldStrategy) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '策略不存在']);
                }
                if (intval($oldStrategy['productid']) !== $productId && M('MemberStrategy')->where(['strategyid' => $id])->count() > 0) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '已被商户使用的策略不能修改产品']);
                }
                $data['updatetime'] = date('Y-m-d H:i:s');
                $res = M('Strategy')->where(['id' => $id])->save($data);
                if ($res) {
                    UserLogService::HTwrite(3, '编辑策略', '编辑策略（' . $id . '）：' . $name);
                    $this->clearStrategyRedisCache($id, $type);
                }
            } else {
                $data['createtime'] = date('Y-m-d H:i:s');
                $res = M('Strategy')->add($data);
                if ($res) {
                    UserLogService::HTwrite(2, '添加策略', '添加策略：' . $name);
                }
            }

            $this->ajaxReturn(['status' => $res ? 1 : 0, 'msg' => $res ? '操作成功' : '操作失败']);
        }

        $id = I('get.id', 0, 'intval');
        $type = I('get.type', 1, 'intval');
        $typename = isset($this->typeMap[$type]) ? $this->typeMap[$type] : '代收';

        if ($id > 0) {
            $info = M('Strategy')->find($id);
            $this->assign('info', $info);
        }

        $this->assign('type', $type);
        $this->assign('typename', $typename);
        $this->assign('products', M('Product')->where(['status' => 1])->field('id,name,code,currency')->order('id DESC')->select());
        $this->display();
    }

    /**
     * 删除策略
     */
    public function del()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if ($id <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }

            // 检查是否有商户正在使用该策略
            $strategy = M('Strategy')->find($id);
            if (!$strategy) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略不存在']);
            }

            $memberCount = M('MemberStrategy')->where(['strategyid' => $id, 'type' => $strategy['type']])->count();
            if ($memberCount > 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '该策略已被 ' . $memberCount . ' 个商户使用，无法删除']);
            }

            // 删除策略档位
            M('StrategyTier')->where(['strategyid' => $id])->delete();

            // 删除策略
            $res = M('Strategy')->where(['id' => $id])->delete();
            if ($res) {
                UserLogService::HTwrite(4, '删除策略', '删除策略（' . $id . '）');
                $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '删除失败']);
            }
        }
    }

    /**
     * 策略档位管理
     */
    public function tierList()
    {
        $strategyId = I('get.strategyid', 0, 'intval');
        if ($strategyId <= 0) {
            $this->error('参数错误');
        }

        $strategy = M('Strategy')->find($strategyId);
        if (!$strategy) {
            $this->error('策略不存在');
        }

        $typename = isset($this->typeMap[$strategy['type']]) ? $this->typeMap[$strategy['type']] : '代收';

        $list = M('StrategyTier')
            ->where(['strategyid' => $strategyId])
            ->order('sort ASC, tier_min ASC')
            ->select();

        // 获取通道信息
        foreach ($list as $k => $v) {
            $channelDisplay = [];
            if (!empty($v['channel_config'])) {
                $channelConfig = json_decode($v['channel_config'], true);
                if (is_array($channelConfig)) {
                    foreach ($channelConfig as $item) {
                        if ($strategy['type'] == 1) {
                            $channel = M('Channel')->where(['id' => $item['channel_id']])->find();
                        } else {
                            $channel = M('PayForAnother')->where(['id' => $item['channel_id']])->find();
                        }
                        if ($channel) {
                            $channelDisplay[] = $channel['title'] . '(' . $item['weight'] . ')';
                        }
                    }
                }
            }
            $list[$k]['channel_display'] = !empty($channelDisplay) ? implode(', ', $channelDisplay) : '-';
        }

        $this->assign('strategy', $strategy);
        $this->assign('typename', $typename);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 添加/编辑策略档位
     */
    public function editTier()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            $strategyId = I('post.strategyid', 0, 'intval');
            $tierName = I('post.tier_name', '', 'trim');
            $tierMin = I('post.tier_min', 0, 'floatval');
            $tierMax = I('post.tier_max', 0, 'floatval');
            $sort = I('post.sort', 100, 'intval');
            $status = I('post.status', 1, 'intval');
            $channelIds = I('post.channel_ids', '');
            $weights = I('post.weights', '');

            if ($strategyId <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略ID错误']);
            }
            if ($tierMin <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '最小金额必须大于0']);
            }
            if ($tierMax <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '最大金额必须大于0']);
            }
            if ($tierMin > $tierMax) {
                $this->ajaxReturn(['status' => 0, 'msg' => '最小金额不能大于最大金额']);
            }

            // 获取策略类型
            $strategy = M('Strategy')->find($strategyId);
            if (!$strategy) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略不存在']);
            }
            $product = M('Product')->where([
                'id' => intval($strategy['productid']),
                'status' => 1,
            ])->find();
            if (!$product) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略关联的产品不存在或已关闭']);
            }
            if ($product['currency'] !== $strategy['currency']) {
                $this->ajaxReturn(['status' => 0, 'msg' => '策略与产品币种不匹配']);
            }
            // 构建通道配置
            $channelConfig = [];
            if (!empty($channelIds) && !empty($weights)) {
                $channelIdArr = explode(',', trim($channelIds, ','));
                $weightArr = explode(',', trim($weights, ','));
                foreach ($channelIdArr as $k => $channelId) {
                    $channelId = intval(trim($channelId));
                    $weight = isset($weightArr[$k]) ? intval(trim($weightArr[$k])) : 1;
                    if ($channelId > 0 && $weight > 0) {
                        $channelModel = $strategy['type'] == 1 ? M('Channel') : M('PayForAnother');
                        if (!$channelModel->where([
                            'id' => $channelId,
                            'status' => 1,
                            'currency' => $product['currency'],
                            'paytype' => $product['paytype'],
                        ])->find()) {
                            $this->ajaxReturn(['status' => 0, 'msg' => '通道不属于当前产品或已关闭：' . $channelId]);
                        }
                        $channelConfig[] = [
                            'channel_id' => $channelId,
                            'weight' => $weight,
                        ];
                    }
                }
            }

            $data = [
                'strategyid' => $strategyId,
                'tier_name' => $tierName,
                'tier_min' => $tierMin,
                'tier_max' => $tierMax,
                'sort' => $sort,
                'status' => $status,
                'channel_config' => json_encode($channelConfig, JSON_UNESCAPED_UNICODE),
            ];

            if ($id > 0) {
                $data['updatetime'] = date('Y-m-d H:i:s');
                $res = M('StrategyTier')->where(['id' => $id])->save($data);
                if ($res) {
                    UserLogService::HTwrite(3, '编辑策略档位', '编辑策略档位（' . $id . '）：' . $tierName);
                }
            } else {
                $data['createtime'] = date('Y-m-d H:i:s');
                $res = M('StrategyTier')->add($data);
                if ($res) {
                    UserLogService::HTwrite(2, '添加策略档位', '添加策略档位：' . $tierName);
                }
            }

            if ($res) {
                $this->clearStrategyRedisCache($strategyId, $strategy['type']);
            }
            $this->ajaxReturn(['status' => $res ? 1 : 0, 'msg' => $res ? '操作成功' : '操作失败']);
        }

        $id = I('get.id', 0, 'intval');
        $strategyId = I('get.strategyid', 0, 'intval');

        if ($strategyId <= 0) {
            $this->error('参数错误');
        }

        $strategy = M('Strategy')->find($strategyId);
        if (!$strategy) {
            $this->error('策略不存在');
        }
        $product = M('Product')->where([
            'id' => intval($strategy['productid']),
            'status' => 1,
        ])->find();
        if (!$product) {
            $this->error('策略关联的产品不存在或已关闭');
        }
        if ($product['currency'] !== $strategy['currency']) {
            $this->error('策略与产品币种不匹配');
        }

        // 当前数据模型通过 paytype 关联产品与实际通道。
        $channelWhere = [
            'status' => 1,
            'currency' => $product['currency'],
            'paytype' => $product['paytype'],
        ];
        if ($strategy['type'] == 1) {
            $channels = M('Channel')->where($channelWhere)->field('id,title,rate')->select();
        } else {
            $channels = M('PayForAnother')->where($channelWhere)->field('id,title')->select();
        }

        if ($id > 0) {
            $info = M('StrategyTier')->find($id);
            if ($info && !empty($info['channel_config'])) {
                $info['channel_config_arr'] = json_decode($info['channel_config'], true);
            }
            $this->assign('info', $info);
        }

        $this->assign('strategy', $strategy);
        $this->assign('product', $product);
        $this->assign('channels', $channels);
        $this->display();
    }

    /**
     * 删除策略档位
     */
    public function delTier()
    {
        if (IS_POST) {
            $id = I('post.id', 0, 'intval');
            if ($id <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
            }

            $tier = M('StrategyTier')->find($id);
            $strategy = $tier ? M('Strategy')->find($tier['strategyid']) : null;
            $res = M('StrategyTier')->where(['id' => $id])->delete();
            if ($res) {
                if ($strategy) {
                    $this->clearStrategyRedisCache($strategy['id'], $strategy['type']);
                }
                UserLogService::HTwrite(4, '删除策略档位', '删除策略档位（' . $id . '）');
                $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
            } else {
                $this->ajaxReturn(['status' => 0, 'msg' => '删除失败']);
            }
        }
    }

    /**
     * 商户策略分配
     */
    public function assignMember()
    {
        if (IS_POST) {
            $userid = I('post.userid', 0, 'intval');
            $productId = I('post.productid', 0, 'intval');
            $strategyId = I('post.strategyid', 0, 'intval');
            $type = I('post.type', 1, 'intval');

            if ($userid <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '商户ID错误']);
            }
            if ($productId <= 0) {
                $this->ajaxReturn(['status' => 0, 'msg' => '请选择产品']);
            }

            // 检查商户是否存在
            $member = M('Member')->find($userid);
            if (!$member) {
                $this->ajaxReturn(['status' => 0, 'msg' => '商户不存在']);
            }
            $product = M('Product')->where(['id' => $productId, 'status' => 1, 'currency' => $member['currency']])->find();
            if (!$product || !$this->merchantHasProduct($userid, $productId, $type)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '商户未开通该产品或产品已关闭']);
            }

            // 验证策略
            if ($strategyId > 0) {
                $strategy = M('Strategy')->find($strategyId);
                if (!$strategy || $strategy['status'] != 1) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '策略不存在或已关闭']);
                }
                if ($strategy['type'] != $type) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '策略类型不匹配']);
                }
                if (intval($strategy['productid']) !== $productId) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '策略与产品不匹配']);
                }
                // 国家校验
                if ($strategy['currency'] !== $member['currency']) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '策略国家与商户不匹配']);
                }
            }

            $where = ['userid' => $userid, 'productid' => $productId];
            if ($strategyId > 0) {
                $data = [
                    'userid' => $userid,
                    'productid' => $productId,
                    'strategyid' => $strategyId,
                    'type' => $type,
                    'status' => 1,
                    'createtime' => date('Y-m-d H:i:s'),
                ];
                $existing = M('MemberStrategy')->where($where)->find();
                if ($existing) {
                    unset($data['createtime']);
                    $data['updatetime'] = date('Y-m-d H:i:s');
                    $res = M('MemberStrategy')->where(['id' => $existing['id']])->save($data);
                    if ($res === 0) { $res = true; }
                } else {
                    $res = M('MemberStrategy')->add($data);
                }

                if ($res) {
                    UserLogService::HTwrite(3, '分配商户策略', '为商户（' . $member['username'] . '）分配策略：' . ($strategyId > 0 ? $strategy['name'] : '无'));
                }
            } else {
                $res = M('MemberStrategy')->where($where)->delete();
                if ($res === 0) { $res = true; }
                UserLogService::HTwrite(3, '取消商户策略', '取消商户（' . $member['username'] . '）的策略配置');
            }

            $this->clearMemberStrategyRedisCache($userid, $productId, $type);

            $this->ajaxReturn(['status' => $res ? 1 : 0, 'msg' => $res ? '操作成功' : '操作失败']);
        }

        $userid = I('get.userid', 0, 'intval');
        $type = I('get.type', 1, 'intval');
        $productId = I('get.productid', 0, 'intval');
        $typename = isset($this->typeMap[$type]) ? $this->typeMap[$type] : '代收';

        if ($userid <= 0) {
            $this->error('参数错误');
        }

        $member = M('Member')->find($userid);
        if (!$member) {
            $this->error('商户不存在');
        }

        $mappingModel = $type == 1 ? M('ProductUser') : M('UserPayForAnother');
        $productIds = $mappingModel->where(['userid' => $userid, 'status' => 1])->getField('pid', true);
        $products = empty($productIds) ? [] : M('Product')->where(['id' => ['in', $productIds], 'status' => 1, 'currency' => $member['currency']])->field('id,name,code,currency')->select();
        if ($productId <= 0 && !empty($products)) {
            $productId = intval($products[0]['id']);
        }
        $strategys = M('Strategy')->where(['productid' => $productId, 'type' => $type, 'status' => 1, 'currency' => $member['currency']])->select();

        // 获取当前配置
        $current = M('MemberStrategy')->where(['userid' => $userid, 'productid' => $productId])->find();

        // 获取当前策略的详细信息
        if ($current && $current['strategyid'] > 0) {
            $currentStrategy = M('Strategy')->find($current['strategyid']);
            $currentTiers = M('StrategyTier')->where(['strategyid' => $current['strategyid'], 'status' => 1])->order('sort ASC')->select();
            $current['strategy_info'] = $currentStrategy;
            $current['tiers'] = $currentTiers;
        }

        $this->assign('member', $member);
        $this->assign('strategys', $strategys);
        $this->assign('products', $products);
        $this->assign('productId', $productId);
        $this->assign('current', $current);
        $this->assign('type', $type);
        $this->assign('typename', $typename);
        $this->display();
    }

    /**
     * 商户策略列表（批量管理）
     */
    public function memberList()
    {
        UserLogService::HTwrite(1, '查看商户策略列表', '查看商户策略列表');

        $type = I('get.type', 1, 'intval');
        $hasStrategy = I('get.has_strategy', '', 'trim,string,strip_tags,htmlspecialchars');
        $currency = I('get.currency', C('DEFAULT_COUNTRY'), 'trim,string,strip_tags,htmlspecialchars');
        $typename = isset($this->typeMap[$type]) ? $this->typeMap[$type] : '代收';

        // 构建查询条件
        $where = ['type' => $type];
        if ($hasStrategy === '1') {
            // 有策略的商户
            $memberList = M('MemberStrategy')->where($where)->getField('userid', true);
            if (empty($memberList)) {
                $memberList = [];
            }
            $memberWhere = ['id' => ['in', $memberList]];
        } elseif ($hasStrategy === '0') {
            // 无策略的商户
            $memberWithStrategy = M('MemberStrategy')->where($where)->getField('userid', true);
            if (!empty($memberWithStrategy)) {
                $memberWhere = ['id' => ['not in', $memberWithStrategy]];
            } else {
                $memberWhere = [];
            }
        } else {
            $memberWhere = [];
        }

        if ($currency !== '') {
            $memberWhere['currency'] = $currency;
        }
        // 国家列表
        $country = M('Country')->where(['status' => 1])->select();
        $this->assign('country', $country);
        $this->assign('currency', $currency);

        // 获取商户列表
        $memberWhere['groupid'] = ['eq', 4]; // 排除测试账号
        $count = M('Member')->where($memberWhere)->count();
        $size = 50;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $Page = new Page($count, $rows);
        $members = M('Member')
            ->where($memberWhere)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();

        // 获取商户策略配置
        foreach ($members as $k => $v) {
            $memberStrategy = M('MemberStrategy')->where(['userid' => $v['id'], 'type' => $type])->find();
            if ($memberStrategy && $memberStrategy['strategyid'] > 0) {
                $strategy = M('Strategy')->find($memberStrategy['strategyid']);
                $members[$k]['strategy'] = $strategy;
                $members[$k]['strategy_status'] = $memberStrategy['status'];
            } else {
                $members[$k]['strategy'] = null;
                $members[$k]['strategy_status'] = 0;
            }
        }

        $this->assign('type', $type);
        $this->assign('typename', $typename);
        $this->assign('has_strategy', $hasStrategy);
        $this->assign('rows', $rows);
        $this->assign('list', $members);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 批量分配用户策略
     */
    public function batchAssign()
    {
        $type = I('get.type', 1, 'intval');
        $currency = I('get.currency', C('DEFAULT_COUNTRY'), 'trim,string,strip_tags,htmlspecialchars');

        // 获取商户列表（按国家筛选）
        $where = ['status' => 1];
        if ($currency !== '') {
            $where['currency'] = $currency;
        }
        $userList = M('Member')->where($where)->order('id desc')->select();

        // 获取策略列表
        $strategies = M('Strategy')->where(['type' => $type, 'status' => 1])->order('id ASC')->select();

        // 国家列表
        $country = M('Country')->where(['status' => 1])->select();

        $this->assign('type', $type);
        $this->assign('currency', $currency);
        $this->assign('country', $country);
        $this->assign('strategies', $strategies);
        $this->assign('products', M('Product')->where(['status' => 1])->field('id,name,code,currency')->order('id DESC')->select());
        $this->assign('userList', $userList);
        $this->display();
    }

    /**
     * 按包网批量分配策略
     */
    public function batchAssignByAgency()
    {
        $type = I('get.type', 1, 'intval');
        $currency = I('get.currency', C('DEFAULT_COUNTRY'), 'trim,string,strip_tags,htmlspecialchars');

        // 获取包网列表（按国家筛选：只显示该国家有商户的包网）
        if ($currency !== '') {
            $memberAgencyIds = M('Member')->where(['status' => 1, 'currency' => $currency])->getField('agency_id', true);
            $memberAgencyIds = array_filter($memberAgencyIds);
            if (empty($memberAgencyIds)) {
                $memberAgencyIds = [0];
            }
            $agency = M('Agency')->where(['id' => ['in', $memberAgencyIds]])->select();
        } else {
            $agency = M('Agency')->select();
        }

        // 获取策略列表
        $strategies = M('Strategy')->where(['type' => $type, 'status' => 1])->order('id ASC')->select();

        // 国家列表
        $country = M('Country')->where(['status' => 1])->select();

        $this->assign('type', $type);
        $this->assign('currency', $currency);
        $this->assign('country', $country);
        $this->assign('strategies', $strategies);
        $this->assign('products', M('Product')->where(['status' => 1])->field('id,name,code,currency')->order('id DESC')->select());
        $this->assign('agency', $agency);
        $this->display();
    }

    /**
     * 保存批量分配的策略
     */
    public function saveBatchAssign()
    {
        if (!IS_POST) {
            $this->error('请求方式错误');
        }

        $ids = I('post.ids', '', 'trim');
        $type = I('post.type', 1, 'intval');
        $strategyid = I('post.strategyid', 0, 'intval');
        $productId = I('post.productid', 0, 'intval');
        $status = I('post.status', 1, 'intval');

        if (empty($ids)) {
            $this->error('请选择商户');
        }

        // 解析用户ID列表
        $useridArr = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($useridArr)) {
            $this->error('商户ID格式不正确');
        }

        // 验证策略
        if ($productId <= 0) {
            $this->error('请选择产品');
        }
        if ($strategyid > 0) {
            $strategy = M('Strategy')->where(['id' => $strategyid, 'productid' => $productId, 'type' => $type, 'status' => 1])->find();
            if (!$strategy) {
                $this->error('策略不存在或已禁用');
            }
        }

        $successCount = 0;
        $existCount = 0;
        $skipCount = 0;
        $db = M();
        $db->startTrans();

        try {
            foreach ($useridArr as $userid) {
                // 检查商户国家是否与策略匹配
                if ($strategyid > 0) {
                    $member = M('Member')->find($userid);
                    if (!$member || $member['currency'] !== $strategy['currency'] || !$this->merchantHasProduct($userid, $productId, $type)) {
                        $skipCount++;
                        continue;
                    }
                }

                // 检查是否已存在配置
                $exist = M('MemberStrategy')->where(['userid' => $userid, 'productid' => $productId])->find();

                if ($exist) {
                    M('MemberStrategy')->where(['userid' => $userid, 'productid' => $productId])->save([
                        'strategyid' => $strategyid,
                        'status' => $status,
                        'updatetime' => date('Y-m-d H:i:s'),
                    ]);
                    $existCount++;
                } else {
                    M('MemberStrategy')->add([
                        'userid' => $userid,
                        'productid' => $productId,
                        'type' => $type,
                        'strategyid' => $strategyid,
                        'status' => $status,
                        'createtime' => date('Y-m-d H:i:s'),
                    ]);
                    $successCount++;
                }

                // 清除缓存
                $this->clearMemberStrategyRedisCache($userid, $productId, $type);
            }

            $db->commit();

            UserLogService::HTwrite(1, '批量分配策略', "批量分配策略：类型={$type}，策略ID={$strategyid}，新增={$successCount}，更新={$existCount}，跳过={$skipCount}");

            $this->success("操作成功：新增 {$successCount} 条，更新 {$existCount} 条，跳过 {$skipCount} 条（国家不匹配）");
        } catch (\Exception $e) {
            $db->rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
    }


    /**
     * 按包网保存批量分配的策略
     */
    public function saveBatchAssignByAgency()
    {
        if (!IS_POST) {
            $this->error('请求方式错误');
        }

        $ids = I('post.ids', '', 'trim');
        $type = I('post.type', 1, 'intval');
        $strategyid = I('post.strategyid', 0, 'intval');
        $productId = I('post.productid', 0, 'intval');
        $status = I('post.status', 1, 'intval');

        if (empty($ids)) {
            $this->error('请选择包网');
        }

        // 解析包网ID列表
        $agencyArr = array_filter(array_map('intval', explode(',', $ids)));
        if (empty($agencyArr)) {
            $this->error('包网ID格式不正确');
        }

        // 验证策略
        if ($productId <= 0) {
            $this->error('请选择产品');
        }
        if ($strategyid > 0) {
            $strategy = M('Strategy')->where(['id' => $strategyid, 'productid' => $productId, 'type' => $type, 'status' => 1])->find();
            if (!$strategy) {
                $this->error('策略不存在或已禁用');
            }
        }

        // 获取包网下的所有商户
        $members = M('Member')->where(['agency_id' => ['in', $agencyArr], 'status' => 1])->select();
        if (empty($members)) {
            $this->error('所选包网下没有有效商户');
        }

        $successCount = 0;
        $existCount = 0;
        $skipCount = 0;
        $db = M();
        $db->startTrans();

        try {
            foreach ($members as $member) {
                $userid = $member['id'];

                // 检查商户国家是否与策略匹配
                if ($strategyid > 0) {
                    if ($member['currency'] !== $strategy['currency'] || !$this->merchantHasProduct($userid, $productId, $type)) {
                        $skipCount++;
                        continue;
                    }
                }

                // 检查是否已存在配置
                $exist = M('MemberStrategy')->where(['userid' => $userid, 'productid' => $productId])->find();

                if ($exist) {
                    M('MemberStrategy')->where(['userid' => $userid, 'productid' => $productId])->save([
                        'strategyid' => $strategyid,
                        'status' => $status,
                        'updatetime' => date('Y-m-d H:i:s'),
                    ]);
                    $existCount++;
                } else {
                    M('MemberStrategy')->add([
                        'userid' => $userid,
                        'productid' => $productId,
                        'type' => $type,
                        'strategyid' => $strategyid,
                        'status' => $status,
                        'createtime' => date('Y-m-d H:i:s'),
                    ]);
                    $successCount++;
                }

                // 清除缓存
                $this->clearMemberStrategyRedisCache($userid, $productId, $type);
            }

            $db->commit();

            UserLogService::HTwrite(1, '按包网批量分配策略', "按包网批量分配策略：类型={$type}，策略ID={$strategyid}，包网数=" . count($agencyArr) . "，商户新增={$successCount}，商户更新={$existCount}，跳过={$skipCount}");

            $this->success("操作成功：新增 {$successCount} 条，更新 {$existCount} 条，跳过 {$skipCount} 条（国家不匹配）");
        } catch (\Exception $e) {
            $db->rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 策略路由日志
     */
    public function routeLog()
    {
        UserLogService::HTwrite(1, '查看策略路由日志', '查看策略路由日志');

        $type = I('get.type', '');
        $userid = I('get.userid', 0, 'intval');
        $startDate = I('get.startdate', '');
        $endDate = I('get.enddate', '');
        $outTradeNo = I('get.out_trade_no', '', 'strip_tags,htmlspecialchars');

        $where = [];
        if ($type !== '') {
            $where['type'] = $type;
        }
        if ($userid > 0) {
            $where['userid'] = $userid -10000;
        }
        if (!empty($startDate)) {
            $where['createtime'] = ['egt', $startDate . ' 00:00:00'];
        }
        if (!empty($endDate)) {
            if (isset($where['createtime'])) {
                $where['createtime'] = ['between', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']];
            } else {
                $where['createtime'] = ['elt', $endDate . ' 23:59:59'];
            }
        }
        if (!empty($outTradeNo)) {
            $where['out_trade_no'] = ['like', '%' . $outTradeNo . '%'];
        }

        $count = M('StrategyLog')->where($where)->count();
        $size = 50;
        $rows = I('get.rows', $size, 'intval');
        if (!$rows) {
            $rows = $size;
        }

        $Page = new Page($count, $rows);
        $list = M('StrategyLog')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();

        // 获取商户名称
        foreach ($list as $k => $v) {
            $member = M('Member')->find($v['userid']);
            $list[$k]['username'] = $member ? $member['username'] : '-';

            // 获取策略名称
            if ($v['strategyid'] > 0) {
                $strategy = M('Strategy')->find($v['strategyid']);
                $list[$k]['strategy_name'] = $strategy ? $strategy['name'] : '-';
            } else {
                $list[$k]['strategy_name'] = '-';
            }
        }

        $this->assign('type', $type);
        $this->assign('userid', $userid);
        $this->assign('startdate', $startDate);
        $this->assign('enddate', $endDate);
        $this->assign('out_trade_no', $outTradeNo);
        $this->assign('rows', $rows);
        $this->assign('list', $list);
        $this->assign('page', $Page->show());
        $this->display();
    }

    /**
     * 清除策略缓存
     */
    public function clearCache()
    {
        if (IS_POST) {
            $strategyId = I('post.strategyid', 0, 'intval');
            $type = I('post.type', 0, 'intval');

            try {
                $redis = $this->connectRedis();
                if (!$redis) {
                    $this->ajaxReturn(['status' => 0, 'msg' => 'Redis不可用，当前路由已自动使用数据库']);
                }

                if ($strategyId > 0) {
                    $redis->del('StrategyInfo_' . $strategyId);
                    $redis->del('StrategyTier_' . $strategyId . '_' . $type);
                    $this->ajaxReturn(['status' => 1, 'msg' => '策略缓存已清除']);
                } else {
                    // 清除所有策略相关缓存
                    $keys = $redis->keys('Strategy*');
                    foreach ($keys as $key) {
                        $redis->del($key);
                    }
                    $keys2 = $redis->keys('MemberStrategy_*');
                    foreach ($keys2 as $key) {
                        $redis->del($key);
                    }
                    $this->ajaxReturn(['status' => 1, 'msg' => '所有策略缓存已清除']);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn(['status' => 0, 'msg' => '清除缓存失败：' . $e->getMessage()]);
            }
        }
    }

    private function merchantHasProduct($userid, $productId, $type)
    {
        $model = intval($type) === 1 ? M('ProductUser') : M('UserPayForAnother');
        return (bool) $model->where(['userid' => intval($userid), 'pid' => intval($productId), 'status' => 1])->find();
    }

    private function clearMemberStrategyRedisCache($userid, $productId, $type)
    {
        try {
            $redis = $this->connectRedis();
            if ($redis) {
                $redis->del('MemberStrategy_' . intval($userid) . '_' . intval($productId) . '_' . intval($type));
            }
        } catch (\Exception $e) {
        }
    }

    private function clearStrategyRedisCache($strategyId, $type)
    {
        try {
            $redis = $this->connectRedis();
            if ($redis) {
                $redis->del('StrategyInfo_' . intval($strategyId));
                $redis->del('StrategyTier_' . intval($strategyId) . '_' . intval($type));
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * 连接Redis
     */
    private function connectRedis()
    {
        try {
            $redis = new \Redis();
            if (!$redis->connect(C('REDIS_HOST'), C('REDIS_PORT'))) {
                return null;
            }
            if (C('REDIS_PWD') && !$redis->auth(C('REDIS_PWD'))) {
                return null;
            }
            return $redis;
        } catch (\Exception $e) {
            return null;
        }
    }
}
