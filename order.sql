/*
* @Author: gaozhi
* @Date:   2020-06-24 16:59:59
* @Last Modified by:   gaozhi
* @Last Modified time: 2020-06-24 17:00:15
*/

CREATE TABLE `order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(100) DEFAULT NULL COMMENT '订单号',
  `user_id` int(10) unsigned DEFAULT NULL COMMENT '用户ID',
  `payment_time` datetime NOT NULL COMMENT '支付时间',
  `order_amount` decimal(10,0) DEFAULT NULL COMMENT '总金额',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '订单状态 1-待支付 50-已支付 100-已过期',
  `created_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_time` datetime DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `sended_need_pay_notify` tinyint(255) unsigned DEFAULT '2' COMMENT '是否已发送支付通知',
  PRIMARY KEY (`id`),
  KEY `idx_payment_time` (`payment_time`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8