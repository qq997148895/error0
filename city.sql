/*
Navicat MySQL Data Transfer

Source Server         : xiangxin
Source Server Version : 50553
Source Host           : 122.114.150.35:3306
Source Database       : city

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-05-23 21:53:29
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for mf_access
-- ----------------------------
DROP TABLE IF EXISTS `mf_access`;
CREATE TABLE `mf_access` (
  `role_id` smallint(6) unsigned NOT NULL,
  `node_id` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  KEY `groupId` (`role_id`) USING BTREE,
  KEY `nodeId` (`node_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_access
-- ----------------------------

-- ----------------------------
-- Table structure for mf_admin
-- ----------------------------
DROP TABLE IF EXISTS `mf_admin`;
CREATE TABLE `mf_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '管理员id',
  `admin_name` varchar(16) NOT NULL COMMENT '管理员名称',
  `admin_pwd` varchar(32) NOT NULL COMMENT '管理员密码',
  `create_time` int(10) NOT NULL COMMENT '创建时间',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：0为锁定，1为正常',
  `last_login` int(10) NOT NULL DEFAULT '0' COMMENT '上次登录时间',
  `tel` varchar(11) NOT NULL,
  `zfb` varchar(255) DEFAULT NULL,
  `idcard` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_admin
-- ----------------------------
INSERT INTO `mf_admin` VALUES ('1', 'admin', 'e10adc3949ba59abbe56e057f20f883e', '1320650651', '1', '1558611770', '18530011894', null, null);
INSERT INTO `mf_admin` VALUES ('2', '123', '202cb962ac59075b964b07152d234b70', '1556245468', '1', '0', '18530011894', null, null);

-- ----------------------------
-- Table structure for mf_admin_zf
-- ----------------------------
DROP TABLE IF EXISTS `mf_admin_zf`;
CREATE TABLE `mf_admin_zf` (
  `id` int(11) NOT NULL,
  `ali_num` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_admin_zf
-- ----------------------------
INSERT INTO `mf_admin_zf` VALUES ('1', 'admin', '150', '132');

-- ----------------------------
-- Table structure for mf_agreement
-- ----------------------------
DROP TABLE IF EXISTS `mf_agreement`;
CREATE TABLE `mf_agreement` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号(1:用户注册协议  2:投资风险协议)',
  `content` longtext COMMENT '协议内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_agreement
-- ----------------------------

-- ----------------------------
-- Table structure for mf_askhelp_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_askhelp_order`;
CREATE TABLE `mf_askhelp_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID(接受帮助订单号)',
  `user_id` int(10) unsigned NOT NULL COMMENT '接受帮助者id',
  `user_name` varchar(255) NOT NULL COMMENT '接受帮助者昵称',
  `user_truename` varchar(255) NOT NULL COMMENT '接受帮助者真实姓名',
  `user_phone` varchar(30) NOT NULL COMMENT '接受帮助者联系电话',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '接受帮助金额',
  `order_number` varchar(32) NOT NULL COMMENT '订单编号',
  `order_type` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '订单类型(1:总订单   2:子订单)',
  `wallet_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '卖出的钱包类型(1:静态钱包   2:动态钱包)',
  `parent_id` varchar(32) NOT NULL DEFAULT '' COMMENT '父级订单编号',
  `parent_amount` decimal(10,2) unsigned NOT NULL COMMENT '父级订单总金额',
  `matching` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '订单匹配状态(0:待匹配   1:交易中  2:已完成)',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '订单交易状态(0:待支付  1:待确认收款   2:已确认收款)',
  `addtime` datetime NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=193 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_askhelp_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_banner
-- ----------------------------
DROP TABLE IF EXISTS `mf_banner`;
CREATE TABLE `mf_banner` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(80) NOT NULL DEFAULT '',
  `thumb` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `order_by` int(10) unsigned NOT NULL DEFAULT '0',
  `is_display` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `order_by` (`is_display`,`order_by`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='轮播图';

-- ----------------------------
-- Records of mf_banner
-- ----------------------------

-- ----------------------------
-- Table structure for mf_car
-- ----------------------------
DROP TABLE IF EXISTS `mf_car`;
CREATE TABLE `mf_car` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `goods_id` int(11) NOT NULL,
  `goods_num` int(10) NOT NULL,
  `type` int(1) NOT NULL DEFAULT '2' COMMENT '订单类型1:注册单 2：复购单',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_car
-- ----------------------------

-- ----------------------------
-- Table structure for mf_change_rebate
-- ----------------------------
DROP TABLE IF EXISTS `mf_change_rebate`;
CREATE TABLE `mf_change_rebate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `amount` decimal(30,6) NOT NULL DEFAULT '0.000000' COMMENT '控投的直推金额',
  `is_return` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否奖励0否1是',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='动态奖金表';

-- ----------------------------
-- Records of mf_change_rebate
-- ----------------------------

-- ----------------------------
-- Table structure for mf_config
-- ----------------------------
DROP TABLE IF EXISTS `mf_config`;
CREATE TABLE `mf_config` (
  `id` int(1) NOT NULL AUTO_INCREMENT COMMENT '序号',
  `order_limit1` int(30) unsigned DEFAULT '0' COMMENT '排单金额下线',
  `order_limit2` int(30) unsigned DEFAULT '0' COMMENT '排单金额上线',
  `order_double` int(30) unsigned DEFAULT '1000' COMMENT '投资基础金额(默认以1000的整数倍排单)',
  `is_man_match` tinyint(2) unsigned DEFAULT '2' COMMENT '系统自动匹配开关(1:开 2:关)',
  `pay_time_limit1` decimal(10,3) unsigned DEFAULT '0.000' COMMENT '预付款打款时间限制',
  `pay_time_limit2` decimal(10,3) unsigned DEFAULT '0.000' COMMENT '非预付款打款时间限制',
  `gain_time_limit` decimal(10,3) unsigned DEFAULT '0.000' COMMENT '收款时间限制',
  `matching_limit` int(10) unsigned DEFAULT '1' COMMENT '生成订单几个小时后开始自动匹配预付款金额(预付款金额不能拆分)',
  `kfphone` varchar(25) CHARACTER SET utf8 DEFAULT NULL COMMENT '客服电话(请勿删除)',
  `sys_gain_code` varchar(255) DEFAULT '' COMMENT '平台收款二维码',
  `day_trade_starttime` int(10) DEFAULT '0' COMMENT '每日交易开始时间',
  `day_trade_endtime` int(10) DEFAULT '0' COMMENT '每日交易结束时间',
  `dynamic_burn` tinyint(2) unsigned DEFAULT '0' COMMENT '动态烧伤制度开启状态(0:关闭  1:开启)',
  `active_switch` tinyint(2) unsigned DEFAULT '1' COMMENT '平台账户激活开关(1:开  0:关)',
  `reward_rate1` decimal(10,2) DEFAULT '0.00' COMMENT '第一代推荐奖励百分比',
  `reward_rate2` decimal(10,2) DEFAULT NULL COMMENT '第二代推荐奖励百分比',
  `reward_rate3` decimal(10,2) DEFAULT NULL COMMENT '第三代推荐奖励百分比',
  `reward_rate4` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '第四代推荐奖励百分比',
  `reward_rate5` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '第五代推荐奖励百分比',
  `reward_rate6` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '第六代推荐奖励百分比',
  `reward_rate7` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '第七代推荐奖励百分比',
  `interest_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '投资利益收益百分比',
  `putdown_price` decimal(10,2) unsigned DEFAULT '5.00' COMMENT '上级代付收益百分比',
  `push_vip2` int(10) unsigned DEFAULT '2' COMMENT '等级晋升vip2需要直推人数',
  `push_vip3` int(10) unsigned DEFAULT '5' COMMENT '等级晋升vip3需要直推的人数',
  `push_vip4` int(10) unsigned DEFAULT '10' COMMENT '等级晋升vip4需要直推的人数',
  `push_vip5` int(10) unsigned DEFAULT '20' COMMENT '等级晋升vip5需要直推人数',
  `push_vip6` int(10) unsigned DEFAULT '35' COMMENT '用户晋升vip钻石需要直推人数',
  `push_vip7` int(10) unsigned DEFAULT '50' COMMENT '等级晋升为vip至尊需要直推人数',
  `push_team3` int(10) unsigned DEFAULT '20' COMMENT '等级晋升vip3需要推广的团队人数',
  `push_team4` int(10) unsigned DEFAULT '50' COMMENT '等级晋升vip4需要推广的团队人数',
  `push_team5` int(10) unsigned DEFAULT '150' COMMENT '等级晋升vip5需要推广的团队人数',
  `push_team6` int(10) unsigned DEFAULT '300' COMMENT '等级晋升vip钻石需要推广的团队人数',
  `push_team7` int(10) unsigned DEFAULT '500' COMMENT '等级晋升为vip至尊需要推广的团队人数',
  `register_number` int(10) unsigned DEFAULT NULL COMMENT '总注册会员数量',
  `openorclose_paidan` tinyint(2) unsigned DEFAULT '1' COMMENT '排单功能开启状态(0:关闭  1:开启)',
  `helpfor_number` int(10) unsigned DEFAULT NULL COMMENT '排单(提供帮助总金额)',
  `askforhelp_number` int(10) unsigned DEFAULT NULL COMMENT '接受帮助总金额',
  `threehours_inner` decimal(10,2) unsigned DEFAULT NULL COMMENT '12小时内打款奖励百分比',
  `paidan_limit` int(10) unsigned DEFAULT '7' COMMENT '完成预付款几天天后可以进行排单',
  `principal_cold` int(10) unsigned DEFAULT '15' COMMENT '冻结倒计时(单位:天)',
  `dynamic_hightone` int(10) unsigned DEFAULT '1000' COMMENT 'VIP1-4用户动态钱包最高卖出额度',
  `dynamic_highttow` int(10) unsigned DEFAULT '3000' COMMENT 'VIP5动态钱包最高卖出额度',
  `dynamic_hightthree` int(10) unsigned DEFAULT '5000' COMMENT 'VIP钻石动态钱包最高卖出金额',
  `dynamic_hightfour` int(10) unsigned DEFAULT '7000' COMMENT 'VIP至尊动态钱包最高卖出金额',
  `signin_number` decimal(10,2) unsigned DEFAULT NULL COMMENT '每日签到奖励值',
  `shop_openclose` tinyint(2) unsigned DEFAULT '2' COMMENT '商城开启状态(1:开启 2:关闭 )',
  `run_house` longtext CHARACTER SET utf8 COMMENT '跑马灯',
  `stock_give` decimal(10,0) DEFAULT NULL COMMENT '股权增值券赠送比例',
  `stock_enter` int(10) DEFAULT NULL COMMENT '商家入驻需要消耗的股权值',
  `stock_price` decimal(10,2) DEFAULT '0.00' COMMENT '股权价格',
  `paidna_expend` decimal(10,2) DEFAULT '10.00' COMMENT '挂买单消耗股权增值券比例',
  `paidan_price` decimal(10,2) DEFAULT '100.00' COMMENT '股权增值券价值',
  `paidan_unit` varchar(5) DEFAULT NULL COMMENT '排单单位',
  `paidan_max` int(10) DEFAULT '20000' COMMENT '排单上限',
  `paidan_yufu` decimal(10,2) DEFAULT '40.00' COMMENT '挂买预付款比例',
  `collect_time` int(10) DEFAULT NULL COMMENT '卖出到账时间',
  `frozen_time` int(10) DEFAULT '1' COMMENT '冻结期',
  `convertible_equity` decimal(10,2) DEFAULT NULL COMMENT '积分兑换股权扣除比例',
  `buy_goods` decimal(10,2) DEFAULT NULL COMMENT '积分兑换商品扣除比例',
  `pay_time_max` int(10) DEFAULT '6' COMMENT '最大打款时间',
  `pay_time_min` int(10) DEFAULT '2' COMMENT '提前时间打款有奖',
  `pay_time_award` decimal(10,2) DEFAULT '3.00' COMMENT '提前打款奖励百分比',
  `paidan_divide` decimal(10,2) DEFAULT '1000.00' COMMENT '排单单位（倍数）',
  `first_max_reward` decimal(10,2) DEFAULT '0.00' COMMENT '最大一代推荐奖励比例',
  `second_max_reward` decimal(10,2) DEFAULT '0.00' COMMENT '最大二代推荐奖励比例',
  `third_max_reward` decimal(10,2) DEFAULT '0.00' COMMENT '最大三代推荐奖励比例',
  `infinite` decimal(10,2) DEFAULT '0.00' COMMENT '无限代奖励比例',
  `equity_ratio` decimal(10,2) DEFAULT '0.00' COMMENT '注册单股权扣除比例',
  `repurchase_proportion` decimal(10,2) DEFAULT '0.00' COMMENT '复购单比例商家得到比例',
  `draw` decimal(10,2) DEFAULT '0.00' COMMENT '积分抢购',
  `frist_award` decimal(10,2) DEFAULT '0.00' COMMENT '一代商品积分奖励',
  `second_award` decimal(10,2) DEFAULT '0.00' COMMENT '二代商品积分奖励',
  `third_award` decimal(10,0) DEFAULT '0' COMMENT '三代商品积分奖励',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COMMENT='配置表';

-- ----------------------------
-- Records of mf_config
-- ----------------------------
INSERT INTO `mf_config` VALUES ('1', '0', '0', '1000', '2', '0.000', '0.000', '0.000', '1', null, '', '0', '0', '0', '1', '10.00', '3.00', '5.00', '0.00', '0.00', '0.00', '0.00', '5.00', '5.00', '2', '5', '10', '20', '35', '50', '20', '50', '150', '300', '500', null, '1', null, null, null, '7', '15', '1000', '3000', '5000', '7000', null, '2', null, '5', '3000', '1.00', '0.10', '50.00', null, '20000', '40.00', '1', '2', '5.00', '0.00', '12', '0', '0.00', '1000.00', '10.00', '3.00', '5.00', '1.00', '20.00', '80.00', '50.00', '0.00', '0.00', '0');

-- ----------------------------
-- Table structure for mf_double_log
-- ----------------------------
DROP TABLE IF EXISTS `mf_double_log`;
CREATE TABLE `mf_double_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0',
  `amount` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '转入金额',
  `grant_amount_interest` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '累计生息数量',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '转入时间',
  `end_time` int(11) NOT NULL DEFAULT '0' COMMENT '生息结束时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='倍增钱包转入记录';

-- ----------------------------
-- Records of mf_double_log
-- ----------------------------

-- ----------------------------
-- Table structure for mf_feedback
-- ----------------------------
DROP TABLE IF EXISTS `mf_feedback`;
CREATE TABLE `mf_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(11) unsigned NOT NULL COMMENT '申述用户ID',
  `category` varchar(500) NOT NULL DEFAULT '' COMMENT '申述问题反馈内容',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题(申述原因)',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '用户反馈的问题内容(申述详情内容)',
  `addtime` int(11) unsigned NOT NULL COMMENT '添加时间',
  `backtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '后台回复时间',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '问题处理状态(0:未处理  1:已处理)',
  `is_del` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '逻辑删除(0:未删除  1:已删除)',
  `is_see` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '查看状态(1:未查看 2:已查看)',
  `img1` varchar(255) DEFAULT NULL COMMENT '申诉图',
  `img2` varchar(255) DEFAULT NULL COMMENT '申诉图',
  `img3` varchar(255) DEFAULT NULL COMMENT '申诉图片',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_feedback
-- ----------------------------

-- ----------------------------
-- Table structure for mf_friends
-- ----------------------------
DROP TABLE IF EXISTS `mf_friends`;
CREATE TABLE `mf_friends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `parent_id` int(10) NOT NULL COMMENT '父亲ID',
  `son_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户邀伙伴ID',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `parent_id` (`parent_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_friends
-- ----------------------------

-- ----------------------------
-- Table structure for mf_goods
-- ----------------------------
DROP TABLE IF EXISTS `mf_goods`;
CREATE TABLE `mf_goods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `goods_name` varchar(100) NOT NULL COMMENT '商品名称',
  `goods_id` varchar(100) NOT NULL COMMENT '商品编号',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品价格',
  `goods_number` varchar(100) NOT NULL COMMENT '商品剩余数量',
  `goods_details` varchar(255) DEFAULT NULL COMMENT '商品简介',
  `imagepath1` varchar(100) DEFAULT NULL COMMENT '图片1',
  `imagepath2` varchar(100) DEFAULT NULL,
  `imagepath3` varchar(100) DEFAULT NULL,
  `one` decimal(4,2) NOT NULL DEFAULT '0.00' COMMENT '一代奖励',
  `two` decimal(4,2) NOT NULL DEFAULT '0.00',
  `three` decimal(4,2) NOT NULL DEFAULT '0.00',
  `addtime` int(11) NOT NULL COMMENT '添加时间',
  `status` int(1) NOT NULL DEFAULT '0' COMMENT '状态：0上架 1下架',
  `goods_sell` int(100) DEFAULT '0' COMMENT '商品已售数量',
  `goods_oldprice` decimal(10,2) NOT NULL,
  `image_declare1` varchar(100) DEFAULT NULL COMMENT '图文详情1',
  `image_declare2` varchar(100) DEFAULT NULL COMMENT '图文详情2',
  `image_declare3` varchar(100) DEFAULT NULL COMMENT '图文详情3',
  `isadmin` int(1) DEFAULT '0' COMMENT '添加商品 0：用户添加 1：平台普通商品  2：平台抢购商品',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_goods
-- ----------------------------

-- ----------------------------
-- Table structure for mf_help_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_help_order`;
CREATE TABLE `mf_help_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID(订单编号) ',
  `user_id` int(10) unsigned NOT NULL COMMENT '会员ID',
  `user_name` varchar(255) NOT NULL COMMENT '会员账号(昵称)',
  `user_truename` varchar(255) NOT NULL COMMENT '提供帮助者真实姓名',
  `user_phone` varchar(30) NOT NULL DEFAULT '' COMMENT '会员手机号码',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '金额',
  `order_number` varchar(32) NOT NULL COMMENT '订单编号',
  `order_type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '订单类型(0:总订单  1:预付款订单   2:非预付款订单)',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级订单ID',
  `parent_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '父级订单总金额',
  `matching` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态(0:待匹配  1:交易中  2:已完成)',
  `status` tinyint(10) NOT NULL DEFAULT '0' COMMENT '订单支付状态0待支付 1已支付',
  `addtime` datetime DEFAULT NULL COMMENT '添加时间',
  `is_good` tinyint(2) unsigned NOT NULL DEFAULT '2' COMMENT '是否已点赞(1:是  2:否)',
  `user_parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级用户id,订单超时未打款时,订单由直推上级负责交易',
  `buy` tinyint(2) NOT NULL COMMENT '购买类型1预约购买2排单购买',
  `pay` tinyint(2) DEFAULT NULL COMMENT '付款方式1银行卡2支付宝',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8 COMMENT='买入订单';

-- ----------------------------
-- Records of mf_help_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_indeximg
-- ----------------------------
DROP TABLE IF EXISTS `mf_indeximg`;
CREATE TABLE `mf_indeximg` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `imgpath` varchar(500) NOT NULL COMMENT '首页轮播图',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 COMMENT='首页轮播图管理';

-- ----------------------------
-- Records of mf_indeximg
-- ----------------------------
INSERT INTO `mf_indeximg` VALUES ('42', '/Uploads/Pic/2019-05-17/5cde1199ea1f1.jpeg');
INSERT INTO `mf_indeximg` VALUES ('43', '/Uploads/Pic/2019-05-17/5cde11a93aa53.jpeg');
INSERT INTO `mf_indeximg` VALUES ('44', '/Uploads/Pic/2019-05-17/5cde11ba60cad.jpeg');
INSERT INTO `mf_indeximg` VALUES ('45', '/Uploads/Pic/2019-05-17/5cde11cd86f07.jpeg');
INSERT INTO `mf_indeximg` VALUES ('46', '/Uploads/Pic/2019-05-17/5cde11db86f07.jpeg');

-- ----------------------------
-- Table structure for mf_interest
-- ----------------------------
DROP TABLE IF EXISTS `mf_interest`;
CREATE TABLE `mf_interest` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `buy_order` int(11) unsigned NOT NULL COMMENT '提供帮助订单编号(也就是ID值)',
  `benjin` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '本金值',
  `amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '利息值',
  `allamount` decimal(10,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '总金额(利息+本金)',
  `addtime` int(11) unsigned NOT NULL COMMENT '添加时间',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '本金提现状态(1:未提现   2:已提现)',
  `statustow` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '可提现状态(1:不可提现   2:可以提现)',
  `turntime` int(11) unsigned NOT NULL COMMENT '提现时间戳',
  `coldday` int(2) unsigned NOT NULL DEFAULT '0' COMMENT '冻结天数',
  `runnum` int(2) unsigned NOT NULL DEFAULT '0' COMMENT '定时任务执行次数',
  `runtime` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上一次的定时任务执行时间戳',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_interest
-- ----------------------------

-- ----------------------------
-- Table structure for mf_lucky_log
-- ----------------------------
DROP TABLE IF EXISTS `mf_lucky_log`;
CREATE TABLE `mf_lucky_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `log_note` varchar(300) NOT NULL DEFAULT '' COMMENT '奖励描述',
  `addtime` int(11) DEFAULT '0' COMMENT '变动时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=176 DEFAULT CHARSET=utf8 COMMENT='抽奖记录';

-- ----------------------------
-- Records of mf_lucky_log
-- ----------------------------

-- ----------------------------
-- Table structure for mf_match_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_match_order`;
CREATE TABLE `mf_match_order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '匹配订单ID',
  `buy_order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '提供帮助订单ID(也是提供帮助的订单编号)',
  `sale_order_id` int(11) NOT NULL DEFAULT '0' COMMENT '接受帮助订单id(也是接受订单的订单编号)',
  `buy_id` int(11) unsigned NOT NULL COMMENT '提供帮助者id',
  `sale_id` int(11) unsigned NOT NULL COMMENT '接受帮助者id',
  `buy_name` varchar(50) NOT NULL COMMENT '提供帮助者昵称',
  `sale_name` varchar(50) NOT NULL COMMENT '接受帮助者昵称',
  `amount` decimal(30,0) NOT NULL DEFAULT '0' COMMENT '匹配金额',
  `order_number` varchar(255) NOT NULL DEFAULT '' COMMENT '匹配订单号(暂时不用,无效字段)',
  `status` tinyint(11) NOT NULL DEFAULT '0' COMMENT '匹配状态0匹配中1已打款2已确认收款3未收到款',
  `create_time` datetime DEFAULT NULL COMMENT '开始时间(也就是匹配时间)',
  `payed_time` datetime DEFAULT NULL COMMENT '打款支付时间',
  `receive_time` datetime DEFAULT NULL COMMENT '收款确认时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=130 DEFAULT CHARSET=utf8 COMMENT='匹配订单表';

-- ----------------------------
-- Records of mf_match_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_merchant
-- ----------------------------
DROP TABLE IF EXISTS `mf_merchant`;
CREATE TABLE `mf_merchant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT '商家id',
  `merchant_name` varchar(50) NOT NULL COMMENT '商家/店铺名称',
  `merchant_id` varchar(50) DEFAULT NULL COMMENT '商家/店铺编号',
  `merchant_truename` varchar(20) DEFAULT NULL COMMENT '商家真实姓名',
  `merchant_phone` varchar(11) DEFAULT NULL COMMENT '商家手机号',
  `merchant_status` int(1) DEFAULT '0' COMMENT '商家的审核状态 0:待审核 1：审核通过 2：驳回',
  `jointime` datetime DEFAULT NULL COMMENT '商家入驻时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COMMENT='商家表';

-- ----------------------------
-- Records of mf_merchant
-- ----------------------------

-- ----------------------------
-- Table structure for mf_mer_config
-- ----------------------------
DROP TABLE IF EXISTS `mf_mer_config`;
CREATE TABLE `mf_mer_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reward_rate1` int(5) NOT NULL COMMENT '一代推荐奖励',
  `reward_rate2` int(5) NOT NULL COMMENT '二代推荐奖励',
  `reward_rate3` int(5) NOT NULL COMMENT '三点推荐奖励',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_mer_config
-- ----------------------------

-- ----------------------------
-- Table structure for mf_news
-- ----------------------------
DROP TABLE IF EXISTS `mf_news`;
CREATE TABLE `mf_news` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '公告类型(1:新闻公告   2:平台制度  3:新手指南  4:关于我们)',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '文章标题',
  `new_img` varchar(255) NOT NULL DEFAULT '' COMMENT '插图',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '文章内容',
  `date` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加日期',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8 COMMENT='新闻';

-- ----------------------------
-- Records of mf_news
-- ----------------------------

-- ----------------------------
-- Table structure for mf_node
-- ----------------------------
DROP TABLE IF EXISTS `mf_node`;
CREATE TABLE `mf_node` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `remark` varchar(255) DEFAULT NULL,
  `sort` smallint(6) unsigned DEFAULT NULL,
  `pid` smallint(6) unsigned NOT NULL,
  `level` tinyint(1) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `level` (`level`) USING BTREE,
  KEY `pid` (`pid`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_node
-- ----------------------------

-- ----------------------------
-- Table structure for mf_order_period
-- ----------------------------
DROP TABLE IF EXISTS `mf_order_period`;
CREATE TABLE `mf_order_period` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `order_id` int(10) NOT NULL DEFAULT '0' COMMENT '对应的订单ID',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `endtime` int(11) NOT NULL DEFAULT '0' COMMENT '结束时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8 COMMENT='买单周期表';

-- ----------------------------
-- Records of mf_order_period
-- ----------------------------

-- ----------------------------
-- Table structure for mf_payed_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_payed_order`;
CREATE TABLE `mf_payed_order` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_name` char(30) NOT NULL DEFAULT '' COMMENT '用户账户',
  `gain_user_id` int(11) NOT NULL DEFAULT '0' COMMENT '对方用户ID',
  `gain_user_name` char(30) NOT NULL DEFAULT '' COMMENT '收款方账户',
  `match_id` int(10) NOT NULL DEFAULT '0' COMMENT '匹配订单id',
  `amount` int(10) NOT NULL DEFAULT '0' COMMENT '交易数量',
  `img_payed` varchar(100) NOT NULL DEFAULT '' COMMENT '打款凭证照片',
  `status` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '交易状态1是已打款，2是已确认收款  ',
  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '交易开始时间',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '交易结束时间',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=200 DEFAULT CHARSET=utf8 COMMENT='打款订单';

-- ----------------------------
-- Records of mf_payed_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_principal
-- ----------------------------
DROP TABLE IF EXISTS `mf_principal`;
CREATE TABLE `mf_principal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(10) unsigned NOT NULL COMMENT '用户ID',
  `buy_order` int(11) unsigned NOT NULL COMMENT '提供帮助订单编号(也是ID值)',
  `amount` decimal(10,2) unsigned NOT NULL COMMENT '本金',
  `addtime` int(10) unsigned NOT NULL COMMENT '添加时间(对方确认收款后添加此记录)',
  `status` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '提现状态(0:未提现   1:已提现)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_principal
-- ----------------------------

-- ----------------------------
-- Table structure for mf_prize
-- ----------------------------
DROP TABLE IF EXISTS `mf_prize`;
CREATE TABLE `mf_prize` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `prize_value` int(10) unsigned NOT NULL COMMENT '单次抽奖消耗金额',
  `prize_open` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '抽奖开关(0:关闭 1:开启)',
  `prize_name1` int(10) NOT NULL DEFAULT '0' COMMENT '一等奖奖品名称',
  `prize_name2` int(10) NOT NULL DEFAULT '0' COMMENT '二等奖奖品名称',
  `prize_name3` int(10) NOT NULL DEFAULT '0' COMMENT '三等奖奖品名称',
  `prize_name4` int(10) NOT NULL DEFAULT '0' COMMENT '四等奖红包下线',
  `prize_name42` int(10) NOT NULL DEFAULT '0' COMMENT '四等奖红包上线',
  `prize_name5` int(10) NOT NULL DEFAULT '0' COMMENT '五等奖奖品名称',
  `prize_name6` int(10) NOT NULL COMMENT '六等奖奖品名称',
  `prize_name7` int(10) NOT NULL DEFAULT '0' COMMENT '七等奖奖品名称',
  `prize_level1` decimal(10,2) unsigned NOT NULL COMMENT '一等奖中奖概率',
  `prize_level2` decimal(10,2) unsigned NOT NULL COMMENT '二等奖中奖概率',
  `prize_level3` decimal(10,2) unsigned NOT NULL COMMENT '三等奖中奖概率',
  `prize_level4` decimal(10,2) unsigned NOT NULL COMMENT '四等奖中奖概率',
  `prize_level5` decimal(10,2) unsigned NOT NULL COMMENT '五等奖中奖概率',
  `prize_level6` decimal(10,2) unsigned NOT NULL COMMENT '六等奖中奖概率',
  `prize_level7` decimal(10,2) unsigned NOT NULL COMMENT '七等奖中奖概率',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_prize
-- ----------------------------

-- ----------------------------
-- Table structure for mf_recharge_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_recharge_order`;
CREATE TABLE `mf_recharge_order` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `order_number` varchar(20) NOT NULL COMMENT '订单号',
  `price` float(8,2) NOT NULL COMMENT '充值金额',
  `user_id` int(10) NOT NULL COMMENT '用户id',
  `goods_name` varchar(30) CHARACTER SET utf8 NOT NULL COMMENT '商品名称',
  `body` varchar(100) CHARACTER SET utf8 DEFAULT NULL COMMENT '描述',
  `order_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单状态0(待付款)1（已付款）',
  `add_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '订单生成时间',
  `pay_time` timestamp NULL DEFAULT NULL COMMENT '支付时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='充值订单';

-- ----------------------------
-- Records of mf_recharge_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_role
-- ----------------------------
DROP TABLE IF EXISTS `mf_role`;
CREATE TABLE `mf_role` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `pid` smallint(6) DEFAULT NULL,
  `status` tinyint(1) unsigned DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_role
-- ----------------------------

-- ----------------------------
-- Table structure for mf_role_admin
-- ----------------------------
DROP TABLE IF EXISTS `mf_role_admin`;
CREATE TABLE `mf_role_admin` (
  `role_id` mediumint(9) unsigned DEFAULT NULL,
  `user_id` char(32) DEFAULT NULL,
  KEY `group_id` (`role_id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_role_admin
-- ----------------------------

-- ----------------------------
-- Table structure for mf_shop_leibie
-- ----------------------------
DROP TABLE IF EXISTS `mf_shop_leibie`;
CREATE TABLE `mf_shop_leibie` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(50) DEFAULT '' COMMENT '分类名称',
  `addtime` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_shop_leibie
-- ----------------------------

-- ----------------------------
-- Table structure for mf_shop_orderform
-- ----------------------------
DROP TABLE IF EXISTS `mf_shop_orderform`;
CREATE TABLE `mf_shop_orderform` (
  `id` int(255) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(30) DEFAULT NULL COMMENT '用户id',
  `user_phone` varchar(30) DEFAULT NULL COMMENT '手机号',
  `user_name` varchar(30) DEFAULT NULL COMMENT '收货人',
  `order` varchar(25) DEFAULT NULL COMMENT '订单编号',
  `project` varchar(30) DEFAULT NULL COMMENT '名称',
  `count` int(11) DEFAULT NULL COMMENT '产品数量',
  `sumprice` decimal(10,2) DEFAULT NULL COMMENT '总价',
  `addtime` datetime DEFAULT NULL COMMENT '添加时间',
  `zt` int(1) DEFAULT '0' COMMENT '状态0待发货1待收货2已收货3平台取消订单(不可用,下单后不可取消订单)4待支付',
  `address` varchar(255) DEFAULT NULL COMMENT '收货地址',
  `note` text COMMENT '备注信息(买家留言)',
  `project_id` int(10) DEFAULT '0' COMMENT '产品id',
  `is_del` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '订单删除状态(0:未删除  1:已删除)',
  `type` int(1) NOT NULL DEFAULT '2' COMMENT '订单类型 1：注册单  2：复购单 3:兑换商品单',
  `img` varchar(255) DEFAULT NULL COMMENT '打款图片',
  PRIMARY KEY (`id`),
  KEY `user` (`user`) USING BTREE,
  KEY `zt` (`zt`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=268 DEFAULT CHARSET=utf8 COMMENT='订单表';

-- ----------------------------
-- Records of mf_shop_orderform
-- ----------------------------

-- ----------------------------
-- Table structure for mf_shop_project
-- ----------------------------
DROP TABLE IF EXISTS `mf_shop_project`;
CREATE TABLE `mf_shop_project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `pid` int(11) unsigned DEFAULT NULL COMMENT '类别',
  `name` varchar(40) DEFAULT NULL COMMENT '名称',
  `title` varchar(40) DEFAULT NULL COMMENT '标题',
  `content` varchar(100) DEFAULT NULL COMMENT '商品简介',
  `info` text COMMENT '商品详情',
  `old_price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '原价',
  `price` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '现价',
  `zt` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:下架  1:上架',
  `imagepath` text COMMENT '缩略图',
  `addtime` int(11) DEFAULT NULL COMMENT '添加时间',
  `nums` int(10) NOT NULL DEFAULT '0' COMMENT '总数量(商品总量)',
  `num` int(10) NOT NULL DEFAULT '0' COMMENT '已兑换数量(已销量)',
  `express` int(10) NOT NULL DEFAULT '0' COMMENT '快递费',
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`) USING BTREE,
  KEY `zt` (`zt`) USING BTREE
) ENGINE=MyISAM AUTO_INCREMENT=141 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_shop_project
-- ----------------------------

-- ----------------------------
-- Table structure for mf_static_rebate
-- ----------------------------
DROP TABLE IF EXISTS `mf_static_rebate`;
CREATE TABLE `mf_static_rebate` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `buy_order_id` int(10) NOT NULL DEFAULT '0' COMMENT '买入订单ID',
  `amount` decimal(30,2) NOT NULL DEFAULT '0.00',
  `is_return` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否奖励0否1是',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='静态收益表';

-- ----------------------------
-- Records of mf_static_rebate
-- ----------------------------

-- ----------------------------
-- Table structure for mf_stock_coupon
-- ----------------------------
DROP TABLE IF EXISTS `mf_stock_coupon`;
CREATE TABLE `mf_stock_coupon` (
  `id` int(1) NOT NULL AUTO_INCREMENT COMMENT '序号',
  `couponnumber` int(255) DEFAULT NULL COMMENT '优惠券数量',
  `robnumber` int(255) DEFAULT NULL COMMENT '允许抢购的人数',
  `isaverage` int(1) DEFAULT NULL COMMENT '是否是平均分配  0：不是 1：是',
  `aneragevalue` int(255) DEFAULT NULL COMMENT '平均值：数量/人数  修改的时候修改',
  `idlist` varchar(255) DEFAULT NULL COMMENT '参加抢购的人 每次修改后需要重置',
  `up_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='增值券表';

-- ----------------------------
-- Records of mf_stock_coupon
-- ----------------------------

-- ----------------------------
-- Table structure for mf_stock_price
-- ----------------------------
DROP TABLE IF EXISTS `mf_stock_price`;
CREATE TABLE `mf_stock_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` datetime DEFAULT NULL,
  `stock_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_stock_price
-- ----------------------------
INSERT INTO `mf_stock_price` VALUES ('33', '2019-05-23 17:19:08', '1.00');

-- ----------------------------
-- Table structure for mf_sys_notice
-- ----------------------------
DROP TABLE IF EXISTS `mf_sys_notice`;
CREATE TABLE `mf_sys_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `title` varchar(100) NOT NULL COMMENT '公告标题',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '公告内容',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='系统公告';

-- ----------------------------
-- Records of mf_sys_notice
-- ----------------------------

-- ----------------------------
-- Table structure for mf_tixian_log
-- ----------------------------
DROP TABLE IF EXISTS `mf_tixian_log`;
CREATE TABLE `mf_tixian_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户id',
  `user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户昵称',
  `user_phone` varchar(255) NOT NULL DEFAULT '0' COMMENT '用户手机号',
  `amount` float(20,2) NOT NULL DEFAULT '0.00' COMMENT '提现金额',
  `real_amount` float(20,2) NOT NULL DEFAULT '0.00' COMMENT '实际到账',
  `service_money` float(20,2) NOT NULL DEFAULT '0.00' COMMENT '手续费',
  `user_zhifubao` varchar(155) NOT NULL DEFAULT '0' COMMENT '支付宝账号',
  `status` tinyint(10) NOT NULL DEFAULT '0' COMMENT '提现状态0提现中1提现成功2提现失败',
  `add_time` int(11) NOT NULL,
  `type` int(2) NOT NULL DEFAULT '0' COMMENT '提现钱包种类：1静态钱包提现 2动态钱包提现',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of mf_tixian_log
-- ----------------------------

-- ----------------------------
-- Table structure for mf_trade_info
-- ----------------------------
DROP TABLE IF EXISTS `mf_trade_info`;
CREATE TABLE `mf_trade_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `b_user_id` int(11) NOT NULL COMMENT '买家ID',
  `b_user_name` varchar(50) NOT NULL COMMENT '买家昵称',
  `s_user_id` int(11) NOT NULL COMMENT '卖家ID',
  `s_user_name` varchar(50) NOT NULL COMMENT '卖家昵称',
  `amount` int(11) NOT NULL COMMENT '交易数量',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  `type` tinyint(1) NOT NULL COMMENT '预留类型',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 开始 1 确付 2 确收',
  `class` tinyint(1) NOT NULL COMMENT '0 鱼卵 1鱼',
  PRIMARY KEY (`id`),
  KEY `b_user_id` (`b_user_id`) USING BTREE,
  KEY `b_user_name` (`b_user_name`) USING BTREE,
  KEY `s_user_id` (`s_user_id`) USING BTREE,
  KEY `s_user_name` (`s_user_name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_trade_info
-- ----------------------------

-- ----------------------------
-- Table structure for mf_trade_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_trade_order`;
CREATE TABLE `mf_trade_order` (
  `order_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '订单id',
  `user_id` int(10) NOT NULL COMMENT '用户ID',
  `amount` int(10) NOT NULL COMMENT '交易数量',
  `create_time` int(10) NOT NULL COMMENT '交易开始时间',
  `end_time` int(10) NOT NULL DEFAULT '0' COMMENT '交易结束时间',
  `type` tinyint(1) NOT NULL COMMENT '交易种类，0是买，1是卖',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '交易状态,0是开始，1是已打款，2是确认收款',
  `order_type` tinyint(1) NOT NULL COMMENT '订单类型：0为鱼卵，1为鱼',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='交易订单';

-- ----------------------------
-- Records of mf_trade_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_trading
-- ----------------------------
DROP TABLE IF EXISTS `mf_trading`;
CREATE TABLE `mf_trading` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '卖家id',
  `user_id_buy` int(10) NOT NULL DEFAULT '0' COMMENT '买家用户id',
  `buying_num` mediumint(8) NOT NULL DEFAULT '0' COMMENT 'jhc交易数量',
  `all_price` float(8,2) NOT NULL DEFAULT '0.00' COMMENT '总价',
  `price` float(8,2) NOT NULL DEFAULT '0.00' COMMENT '单价',
  `type` tinyint(10) NOT NULL DEFAULT '0' COMMENT 'jhc交易类型0求购1出售2点对点交易',
  `trade_status` tinyint(10) NOT NULL DEFAULT '0' COMMENT '交易状态0等待交易1待付款2交易完成3.交易取消',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='交易中心';

-- ----------------------------
-- Records of mf_trading
-- ----------------------------

-- ----------------------------
-- Table structure for mf_treasure
-- ----------------------------
DROP TABLE IF EXISTS `mf_treasure`;
CREATE TABLE `mf_treasure` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `lssue` int(10) NOT NULL COMMENT '期次',
  `take_part` float(8,2) NOT NULL DEFAULT '0.00' COMMENT '已参与额度',
  `price` float(8,2) NOT NULL COMMENT '价格',
  `goods_name` varchar(50) NOT NULL COMMENT '商品名称',
  `pic` varchar(100) NOT NULL COMMENT '商品图片地址',
  `category` int(10) NOT NULL COMMENT '分类id',
  `add_time` int(10) NOT NULL COMMENT '添加时间',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否有效0(进行中）1（已开奖）2（暂停）',
  `up_time` int(10) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='幸运夺宝';

-- ----------------------------
-- Records of mf_treasure
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user
-- ----------------------------
DROP TABLE IF EXISTS `mf_user`;
CREATE TABLE `mf_user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `user_name` varchar(15) DEFAULT NULL COMMENT '用户昵称',
  `user_sex` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '用户性别(0:未知 1:男 2:女)',
  `user_phone` char(11) NOT NULL DEFAULT '' COMMENT '用户手机号',
  `user_password` varchar(32) NOT NULL DEFAULT '' COMMENT '用户密码',
  `user_secpwd` varchar(32) NOT NULL DEFAULT '' COMMENT '安全密码(二次密码)',
  `user_parent` varchar(255) DEFAULT NULL COMMENT '用户推荐人账号(用逗号拼接所有上级推荐人,方便后续查找代数)',
  `user_status` tinyint(2) NOT NULL DEFAULT '1' COMMENT '用户状态：0为封号，1为正常',
  `cold_resone` varchar(255) NOT NULL COMMENT '冻结原因',
  `user_reputation` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户信誉值',
  `grant_lower_number` int(10) NOT NULL DEFAULT '0' COMMENT '累计推荐人数',
  `is_active` tinyint(10) unsigned NOT NULL DEFAULT '0' COMMENT '账号是否已激活0否1是',
  `last_buy_amount` int(30) NOT NULL DEFAULT '0' COMMENT '最近一次买单数量',
  `user_add_time` int(11) NOT NULL DEFAULT '0' COMMENT '用户注册时间',
  `user_lastlogin_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户上一次登录时间(记录上一次登录的退出时间)',
  `user_lastsee_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '用户最近一次查看推送消息的时间',
  `user_recomand_time` int(10) NOT NULL DEFAULT '0' COMMENT '最近一次推荐下级的时间',
  `user_truename` varchar(10) NOT NULL DEFAULT '' COMMENT '用户姓名',
  `user_link` varchar(500) NOT NULL COMMENT '用户推广链接',
  `user_reg_code` varchar(500) NOT NULL DEFAULT '' COMMENT '用户邀请码',
  `user_headimg` varchar(100) NOT NULL DEFAULT '' COMMENT '用户头像',
  `info_perfected` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '个人资料完善情况(0:未完善  1:已完善)',
  `user_active_time` datetime NOT NULL COMMENT '用户账户激活时间(date格式,方便后期查询当天激活量)',
  `user_wechat` varchar(25) NOT NULL COMMENT '微信号',
  `user_province` varchar(150) NOT NULL COMMENT '省份地址',
  `user_ismerchant` int(1) DEFAULT '0' COMMENT '用户是否是商家  0：不是 1：是',
  `continuous_sign` int(100) DEFAULT '0' COMMENT '连续签到天数',
  PRIMARY KEY (`user_id`),
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `user_name` (`user_name`) USING BTREE,
  KEY `user_phone` (`user_phone`) USING BTREE,
  KEY `user_parent` (`user_parent`) USING BTREE,
  KEY `user_status` (`user_status`) USING BTREE,
  KEY `user_reg_code` (`user_reg_code`(255)) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=127 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Records of mf_user
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_active_code
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_active_code`;
CREATE TABLE `mf_user_active_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `code` varchar(32) NOT NULL DEFAULT '0' COMMENT '激活码',
  `is_used` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否已经失效0否1已失效',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8 COMMENT='用户购买的激活码';

-- ----------------------------
-- Records of mf_user_active_code
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_active_log
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_active_log`;
CREATE TABLE `mf_user_active_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `from_user_name` varchar(11) NOT NULL DEFAULT '' COMMENT '用户名',
  `to_user_name` varchar(11) NOT NULL DEFAULT '' COMMENT '转入方用户名',
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '数量',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COMMENT='转让激活码';

-- ----------------------------
-- Records of mf_user_active_log
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_ali_number
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_ali_number`;
CREATE TABLE `mf_user_ali_number` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `name` varchar(255) NOT NULL COMMENT '姓名',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `money_code_img` varchar(255) NOT NULL DEFAULT '' COMMENT '收款二维码',
  `ali_num` varchar(50) NOT NULL COMMENT '支付宝账号',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `del` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '逻辑删除(0:未删除  1:已删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8 COMMENT='支付宝账号';

-- ----------------------------
-- Records of mf_user_ali_number
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_bite_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_bite_order`;
CREATE TABLE `mf_user_bite_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '购买者ID',
  `user_name` varchar(11) NOT NULL DEFAULT '' COMMENT '用户名',
  `user_phone` varchar(11) NOT NULL DEFAULT '' COMMENT '账号',
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '购买拍单币数量',
  `price` int(10) NOT NULL DEFAULT '0' COMMENT '总价格',
  `img_evidence` varchar(255) NOT NULL DEFAULT '' COMMENT '凭证照片',
  `status` tinyint(10) NOT NULL DEFAULT '0' COMMENT '状态0待拨币1已拨币2拒绝拨币3已驳回',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `givetime` int(11) NOT NULL DEFAULT '0' COMMENT '发放时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='购买拍单币';

-- ----------------------------
-- Records of mf_user_bite_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_bite_transfer
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_bite_transfer`;
CREATE TABLE `mf_user_bite_transfer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `from_user_name` varchar(11) NOT NULL DEFAULT '' COMMENT '用户名',
  `to_user_name` varchar(11) NOT NULL DEFAULT '' COMMENT '转入方用户名',
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '转让拍单币数量',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8 COMMENT='转让拍单币';

-- ----------------------------
-- Records of mf_user_bite_transfer
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_code_order
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_code_order`;
CREATE TABLE `mf_user_code_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) unsigned NOT NULL COMMENT '购买者ID',
  `number` int(10) NOT NULL DEFAULT '0' COMMENT '购买激活码数量',
  `price` int(10) NOT NULL DEFAULT '0' COMMENT '总价格',
  `img_evidence` varchar(255) NOT NULL DEFAULT '' COMMENT '凭证照片',
  `status` tinyint(10) NOT NULL DEFAULT '0' COMMENT '状态0已提交1审核通过2审核不通过',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `givetime` int(11) NOT NULL DEFAULT '0' COMMENT '处理时间',
  `is_del` tinyint(2) unsigned NOT NULL DEFAULT '0' COMMENT '删除状态(0:未删除  1:已删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='购买激活码';

-- ----------------------------
-- Records of mf_user_code_order
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_idcard
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_idcard`;
CREATE TABLE `mf_user_idcard` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `user_truename` varchar(255) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `id_card` char(24) NOT NULL DEFAULT '0' COMMENT '银行卡号',
  `card_kaihu` varchar(255) NOT NULL DEFAULT '' COMMENT '银行卡开户行',
  `card_address` varchar(255) DEFAULT '' COMMENT '支行地址',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `del` int(1) unsigned NOT NULL DEFAULT '0' COMMENT '逻辑删除(0:未删除  1:已删除)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_user_idcard
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_notice
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_notice`;
CREATE TABLE `mf_user_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `content` varchar(500) NOT NULL DEFAULT '' COMMENT '公告内容',
  `createtime` int(10) NOT NULL DEFAULT '0' COMMENT '发布时间',
  `is_see` tinyint(2) unsigned NOT NULL DEFAULT '1' COMMENT '查看状态(1:未查看  2:已查看)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1378 DEFAULT CHARSET=utf8 COMMENT='用户通知公告';

-- ----------------------------
-- Records of mf_user_notice
-- ----------------------------
INSERT INTO `mf_user_notice` VALUES ('1376', '126', '亲爱的会员,您的卖出订单2019052321454155479已匹配,请等待对方打款.', '1558619160', '1');
INSERT INTO `mf_user_notice` VALUES ('1377', '125', '亲爱的会员,您的买入订单2019052321450915697已匹配,请在规定时间内完成打款操作.', '1558619160', '1');

-- ----------------------------
-- Table structure for mf_user_ship_address
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_ship_address`;
CREATE TABLE `mf_user_ship_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '收货人',
  `phone` varchar(100) NOT NULL DEFAULT '' COMMENT '收货人联系方式',
  `address_pca` varchar(100) NOT NULL DEFAULT '0' COMMENT '收货地址 省市区',
  `address_city` varchar(100) NOT NULL DEFAULT '0' COMMENT '收货地址 市',
  `address_county` varchar(100) NOT NULL DEFAULT '0' COMMENT '收货地址 区/县',
  `address_detailed` varchar(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  `is_default` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否设为默认 0 否 1是',
  `postal_code` varchar(100) NOT NULL DEFAULT '' COMMENT '邮政编码',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_del` tinyint(10) DEFAULT '0' COMMENT '是否删除0未删除1删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_user_ship_address
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_signin
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_signin`;
CREATE TABLE `mf_user_signin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT '签到人的id',
  `sign_time` date NOT NULL COMMENT '签到时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=134 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_user_signin
-- ----------------------------

-- ----------------------------
-- Table structure for mf_user_sms_code
-- ----------------------------
DROP TABLE IF EXISTS `mf_user_sms_code`;
CREATE TABLE `mf_user_sms_code` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(255) NOT NULL DEFAULT '',
  `sms_code` varchar(255) NOT NULL DEFAULT '',
  `created_at` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=284 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of mf_user_sms_code
-- ----------------------------
INSERT INTO `mf_user_sms_code` VALUES ('282', '13223037933', '7475', '1558618503');
INSERT INTO `mf_user_sms_code` VALUES ('283', '13223037933', '1987', '1558618624');

-- ----------------------------
-- Table structure for mf_wallet
-- ----------------------------
DROP TABLE IF EXISTS `mf_wallet`;
CREATE TABLE `mf_wallet` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `static_amount` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '静态钱包可以随时卖出部分',
  `change_amount` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '积分钱包',
  `exchange_amount` decimal(50,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '兑换钱包',
  `cash_amount` decimal(50,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '可提现钱包(本金+利息)',
  `order_byte` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '股权增值券',
  `change_is_freeze` tinyint(10) NOT NULL DEFAULT '0' COMMENT '是否冻结动态奖金0否1是',
  `addtime` int(11) NOT NULL DEFAULT '0' COMMENT '时间',
  `grant_change_amount` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '累计动态收益',
  `grant_static_amount` decimal(50,2) NOT NULL DEFAULT '0.00' COMMENT '累计静态收益',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8 COMMENT='用户钱包';

-- ----------------------------
-- Records of mf_wallet
-- ----------------------------

-- ----------------------------
-- Table structure for mf_wallet_log
-- ----------------------------
DROP TABLE IF EXISTS `mf_wallet_log`;
CREATE TABLE `mf_wallet_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '序号',
  `user_id` int(10) NOT NULL DEFAULT '0' COMMENT '会员ID',
  `user_name` varchar(255) DEFAULT '' COMMENT '用户名',
  `user_phone` char(11) NOT NULL DEFAULT '0' COMMENT '会员手机号',
  `amount` decimal(30,2) NOT NULL DEFAULT '0.00' COMMENT '资金(或排单币)变动数量',
  `old_amount` decimal(30,2) NOT NULL DEFAULT '0.00' COMMENT '原来余额',
  `remain_amount` decimal(30,2) NOT NULL DEFAULT '0.00' COMMENT '余额',
  `change_date` int(11) DEFAULT '0' COMMENT '变动时间',
  `log_note` varchar(300) NOT NULL COMMENT '信息描述',
  `wallet_type` tinyint(10) NOT NULL DEFAULT '0' COMMENT '变动钱包类1股权钱包2积分钱包3兑换钱包4股权增值券5信誉值6排单币后台充值/扣除7邀请码后台管理',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1116 DEFAULT CHARSET=utf8 COMMENT='用户仓库日志';

-- ----------------------------
-- Records of mf_wallet_log
-- ----------------------------
